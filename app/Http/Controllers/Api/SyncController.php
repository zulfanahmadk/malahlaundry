<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SyncPushRequest;
use App\Models\Attendance;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SyncController extends Controller
{
    public function push(SyncPushRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $actor = $request->user();

        DB::transaction(function () use ($payload, $actor): void {
            // Serialize master changes in this branch, including duplicate-phone checks.
            \App\Models\Branch::whereKey(request()->attributes->get('branch_id'))->lockForUpdate()->firstOrFail();
            // Dependencies must exist before transactions from the same offline batch.
            foreach ($payload['customers'] ?? [] as $index => $data) {
                $customer = Customer::firstOrNew(['uuid' => strtolower($data['uuid'])]);
                if (! $customer->exists) {
                    $this->validateRecord($data, ['name' => 'required', 'phone' => 'required'], "customers.$index");
                }
                if (isset($data['phone'])) {
                    $phone = preg_replace('/\D/', '', $data['phone']);
                    if (str_starts_with($phone, '0')) {
                        $phone = '62'.substr($phone, 1);
                    }
                    if (str_starts_with($phone, '8')) {
                        $phone = '62'.$phone;
                    }
                    if (!preg_match('/^62[0-9]{8,13}$/', $phone)) {
                        throw ValidationException::withMessages(["customers.$index.phone" => 'Nomor telepon Indonesia tidak valid.']);
                    }
                    if (Customer::where('phone', $phone)->where('uuid', '!=', $customer->uuid)->exists()) {
                        throw ValidationException::withMessages(["customers.$index.phone" => 'Nomor telepon sudah terdaftar pada pelanggan lain.']);
                    }
                    $data['phone'] = $phone;
                }
                if (array_key_exists('archived_at', $data)) {
                    $archivedAt = $data['archived_at'] !== null
                        ? Carbon::parse($data['archived_at'])->setTimezone(config('app.timezone')) : null;
                    $archiveChanged = $archivedAt?->getTimestamp() !== $customer->archived_at?->getTimestamp();
                    abort_if(! $actor->isOwner() && $archiveChanged, 403, 'Hanya owner dapat mengarsipkan pelanggan.');
                    if ($archiveChanged && $archivedAt && $customer->transactions()->where('laundry_status', '!=', 'SELESAI')->exists()) {
                        throw ValidationException::withMessages(['customer' => 'Selesaikan cucian aktif sebelum mengarsipkan pelanggan.']);
                    }
                    $data['archived_at'] = $archivedAt;
                }
                $customer->fill(Arr::only($data, ['name', 'phone', 'address', 'notes', 'archived_at']))->save();
            }

            foreach ($payload['services'] ?? [] as $index => $data) {
                $service = Service::firstOrNew(['uuid' => strtolower($data['uuid'])]);
                if (! $service->exists) {
                    $this->validateRecord($data, ['name' => 'required', 'price' => 'required'], "services.$index");
                }
                $changes = Arr::only($data, ['name', 'unit', 'price', 'is_active', 'speed', 'duration_hours', 'duration_unit']);
                // Older clients only understand hours; do not retain a stale DAY label on their edits.
                $changes['duration_unit'] = $data['duration_unit'] ?? (isset($data['duration_hours']) ? 'HOUR' : ($service->duration_unit ?? 'HOUR'));
                $hours = $data['duration_hours'] ?? $service->duration_hours ?? 48;
                if ($changes['duration_unit'] === 'DAY' && $hours % 24 !== 0) {
                    throw ValidationException::withMessages(["services.$index.duration_hours" => 'Estimasi hari harus berupa hari penuh.']);
                }
                $service->fill($changes)->save();
            }

            foreach ($payload['users'] ?? [] as $index => $data) {
                abort_if(array_diff(\App\Support\Access::defaults($data['role'] ?? 'cashier'), \App\Support\Access::permissions($actor)), 403, 'Tidak dapat membuat akun dengan akses melebihi akun Anda.');
                $user = User::firstOrNew(['username' => $data['username']]);
                if ($user->exists) {
                    // A lost response may cause the same creation to be sent again.
                    // A replay never changes an existing account or its tokens.
                    $matches = isset($data['name'], $data['password'])
                        && $user->name === $data['name']
                        && Hash::check($data['password'], $user->password)
                        && $user->role === ($data['role'] ?? 'cashier')
                        && $user->active === (bool) ($data['active'] ?? true)
                        && (int) $user->branch_id === (int) ($data['branch_id'] ?? request()->attributes->get('branch_id'));
                    if ($matches) {
                        continue;
                    }
                    throw ValidationException::withMessages(["users.$index.username" => 'Username sudah terdaftar. Gunakan Edit pengguna untuk mengubah akun.']);
                }
                $this->validateRecord($data, ['name' => 'required', 'password' => 'required'], "users.$index");
                $data['branch_id'] ??= request()->attributes->get('branch_id');
                $user->fill(Arr::only($data, ['name', 'role', 'active', 'password', 'branch_id']));
                $user->save();
            }

            foreach ($payload['attendances'] ?? [] as $index => $data) {
                abort_unless($actor->isCashier(), 403, 'Presensi hanya untuk kasir.');
                $branch = request()->attributes->get('branch');
                if ($branch->latitude !== null && $branch->longitude !== null) {
                    $phase = isset($data['check_out_time']) ? 'out' : 'in';
                    $latitude = $data[$phase.'_latitude'] ?? null;
                    $longitude = $data[$phase.'_longitude'] ?? null;
                    if ($latitude === null || $longitude === null) {
                        throw ValidationException::withMessages(['location' => 'Lokasi presensi diperlukan untuk cabang ini.']);
                    }
                    $lat1 = deg2rad((float) $branch->latitude);
                    $lat2 = deg2rad((float) $latitude);
                    $dlat = $lat2 - $lat1;
                    $dlon = deg2rad((float) $longitude - (float) $branch->longitude);
                    $a = sin($dlat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dlon / 2) ** 2;
                    $distance = 6371000 * 2 * atan2(sqrt($a), sqrt(max(0, 1 - $a)));
                    if ($distance > $branch->radius_meters + min(100, (int) ($data[$phase.'_accuracy'] ?? 0))) {
                        throw ValidationException::withMessages(['location' => 'Presensi berada di luar radius cabang.']);
                    }
                }
                $attendance = Attendance::where('uuid', strtolower($data['uuid']))->lockForUpdate()->first();
                abort_if($attendance && $attendance->user_id !== $actor->id, 403, 'Absensi ini milik pengguna lain.');
                if (! $attendance) {
                    $this->validateRecord($data, ['check_in_time' => 'required'], "attendances.$index");
                    $attendance = new Attendance(['uuid' => strtolower($data['uuid']), 'user_id' => $actor->id]);
                }
                foreach (['check_in_time', 'check_out_time'] as $field) {
                    if (isset($data[$field])) {
                        $data[$field] = Carbon::parse($data[$field])->setTimezone(config('app.timezone'));
                    }
                }
                $attendance->fill(Arr::only($data, ['device_id', 'check_in_time', 'check_out_time', 'in_latitude', 'in_longitude', 'in_accuracy', 'out_latitude', 'out_longitude', 'out_accuracy']));
                if ($attendance->check_out_time && (! $attendance->check_in_time || $attendance->check_out_time->lt($attendance->check_in_time))) {
                    throw ValidationException::withMessages([
                        "attendances.$index.check_out_time" => 'Waktu keluar harus setelah atau sama dengan waktu masuk.',
                    ]);
                }
                $attendance->save();
            }

            foreach ($payload['transactions'] ?? [] as $index => $data) {
                $uuid = strtolower($data['uuid']);
                $transaction = Transaction::where('uuid', $uuid)->lockForUpdate()->first();
                if (! $transaction) {
                    $this->validateRecord($data, ['customer_uuid' => 'required', 'items' => 'required'], "transactions.$index");
                    $transaction = new Transaction([
                        'uuid' => $uuid,
                        'user_id' => $actor->id,
                        'payment_status' => 'BELUM',
                        'laundry_status' => 'DITERIMA',
                    ]);
                    if (isset($data['created_at'])) {
                        $transaction->created_at = Carbon::parse($data['created_at'])->setTimezone(config('app.timezone'));
                    }
                }
                if ($transaction->exists && isset($data['expected_total']) && (int) $data['expected_total'] !== (int) $transaction->total) {
                    abort(409, 'Nominal transaksi di server berubah. Tinjau versi server sebelum mencatat pembayaran.');
                }
                if (isset($data['customer_uuid'])) {
                    $data['customer_uuid'] = strtolower($data['customer_uuid']);
                }
                $this->validateRecord($data, [
                    'customer_uuid' => ['sometimes', Rule::exists('customers', 'uuid')->where('branch_id', request()->attributes->get('branch_id'))],
                    'transaction_number' => ['sometimes', Rule::unique('transactions', 'transaction_number')->ignore($uuid, 'uuid')],
                ], "transactions.$index");
                if (!$transaction->exists) {
                    abort_unless(request()->attributes->get('branch')->active, 422, 'Cabang nonaktif tidak menerima transaksi baru.');
                    $customer = Customer::where('uuid', $data['customer_uuid'])->firstOrFail();
                    abort_if($customer->archived_at, 422, 'Pelanggan sudah diarsipkan.');
                }
                $wasPaid = $transaction->exists && $transaction->isPaid();
                $statuses = ['DITERIMA', 'SIAP_DIAMBIL', 'SELESAI'];
                if (isset($data['laundry_status'])
                    && array_search($data['laundry_status'], $statuses, true) < array_search($transaction->laundry_status, $statuses, true)) {
                    throw ValidationException::withMessages([
                        "transactions.$index.laundry_status" => 'Status cucian tidak dapat dikembalikan ke tahap sebelumnya.',
                    ]);
                }
                if ($transaction->payment_status === 'LUNAS' && ($data['payment_status'] ?? 'LUNAS') !== 'LUNAS') {
                    throw ValidationException::withMessages([
                        "transactions.$index.payment_status" => 'Transaksi yang sudah lunas tidak dapat diubah menjadi belum lunas.',
                    ]);
                }
                // The creator remains unchanged when another cashier handles pickup.
                $transaction->fill(Arr::only($data, ['customer_uuid', 'transaction_number', 'payment_status', 'laundry_status']));
                if ($transaction->laundry_status === 'SELESAI' && $transaction->payment_status !== 'LUNAS') {
                    throw ValidationException::withMessages([
                        "transactions.$index.payment_status" => 'Transaksi harus lunas sebelum selesai diambil.',
                    ]);
                }

                if (! $transaction->ready_at && $transaction->laundry_status !== 'DITERIMA' && isset($data['ready_at'])) {
                    $transaction->ready_at = Carbon::parse($data['ready_at'])->setTimezone(config('app.timezone'));
                } elseif ($transaction->laundry_status === 'SIAP_DIAMBIL' && ! $transaction->ready_at) {
                    $transaction->ready_at = now();
                }
                if ($transaction->payment_status === 'LUNAS' && !$transaction->paid_at) {
                    $transaction->paid_at = isset($data['paid_at']) ? Carbon::parse($data['paid_at'])->setTimezone(config('app.timezone')) : now();
                    $transaction->payment_method = $data['payment_method'] ?? null;
                }
                if ($transaction->laundry_status === 'SELESAI' && ! $transaction->picked_up_at) {
                    $pickup = isset($data['picked_up_at'])
                        ? Carbon::parse($data['picked_up_at'])->setTimezone(config('app.timezone')) : now();
                    if ($pickup->lt($transaction->created_at ?? now()) || $pickup->gt(now()->addMinutes(5))) {
                        throw ValidationException::withMessages([
                            "transactions.$index.picked_up_at" => 'Waktu pengambilan harus setelah penerimaan dan tidak melebihi waktu saat ini.',
                        ]);
                    }
                    $transaction->picked_up_at = $pickup;
                } elseif ($transaction->laundry_status !== 'SELESAI' && ! empty($data['picked_up_at'])) {
                    throw ValidationException::withMessages([
                        "transactions.$index.picked_up_at" => 'Tanggal pengambilan hanya untuk cucian selesai.',
                    ]);
                }

                if (array_key_exists('items', $data)) {
                    $items = array_map(fn (array $item) => array_replace($item, [
                        'service_uuid' => strtolower($item['service_uuid']),
                    ]), $data['items']);
                    $this->validateRecord(['items' => $items], [
                        'items.*.service_uuid' => [Rule::exists('services', 'uuid')->where('branch_id', request()->attributes->get('branch_id'))],
                    ], "transactions.$index");
                    $services = Service::whereIn('uuid', array_column($items, 'service_uuid'))->get()->keyBy('uuid');
                    foreach ($items as &$snapshot) {
                        $snapshot['service_name'] = $snapshot['service_name'] ?? $services[$snapshot['service_uuid']]->name;
                        $snapshot['unit'] = $snapshot['unit'] ?? $services[$snapshot['service_uuid']]->unit;
                    }
                    unset($snapshot);
                    foreach ($items as $itemIndex => $item) {
                        if ($item['unit'] === 'pcs' && floor((float) $item['qty']) !== (float) $item['qty']) {
                            throw ValidationException::withMessages([
                                "transactions.$index.items.$itemIndex.qty" => 'Jumlah layanan pcs harus berupa bilangan bulat.',
                            ]);
                        }
                    }
                    $previousItems = $transaction->exists ? $transaction->items()->get()->map(fn ($item) => [
                        'service_uuid' => $item->service_uuid,
                        'qty' => $item->qty,
                        'price' => $item->price,
                    ])->all() : [];
                    $itemsChanged = $this->itemSnapshot($items) !== $this->itemSnapshot($previousItems);
                    if ($wasPaid && $itemsChanged) {
                        throw ValidationException::withMessages([
                            "transactions.$index.items" => 'Item transaksi yang sudah lunas tidak dapat diubah.',
                        ]);
                    }
                    // Snapshot prices retain the amount charged by the offline cashier.
                    $subtotal = array_sum(array_map(fn (array $item) => (int) round($item['qty'] * $item['price']), $items));
                    if (!$transaction->exists) {
                        $duration = $services->max('duration_hours') ?? 48;
                        $transaction->estimated_at = ($transaction->created_at ?? now())->copy()->addHours($duration);
                    }
                    $transaction->subtotal = $subtotal;
                    $transaction->total = $subtotal;
                    if ($transaction->exists && ($transaction->isDirty() || $itemsChanged)) {
                        $transaction->version++;
                    }
                    $transaction->save();
                    if ($itemsChanged) {
                        $transaction->items()->delete();
                        $transaction->items()->createMany($items);
                    }
                } else {
                    if ($transaction->exists && $transaction->isDirty()) {
                        $transaction->version++;
                    }
                    $transaction->save();
                }
            }
        }, 3);

        return response()->json([
            'status' => 'success',
            'message' => 'Sinkronisasi push berhasil diproses.',
            'synced_at' => now()->toIso8601String(),
            'acknowledged' => collect(['customers', 'services', 'users', 'attendances', 'transactions'])
                ->mapWithKeys(fn (string $group) => [$group => collect($payload[$group] ?? [])
                    ->pluck($group === 'users' ? 'username' : 'uuid')
                    ->map(fn (string $identifier) => $group === 'users' ? $identifier : strtolower($identifier))
                    ->values()->all()])->all(),
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        abort_unless($request->user()->canAccess('attendances', 'write'), 403);
        $data = $request->validate([
            'entity_type' => ['required', Rule::in(['attendance_photo'])],
            'entity_uuid' => ['required', 'uuid:4'],
            'photo_type' => ['required', Rule::in(['check_in', 'check_out'])],
            'file' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:500'],
        ]);

        $attendance = Attendance::where('uuid', strtolower($data['entity_uuid']))->firstOrFail();
        abort_unless($attendance->user_id === $request->user()->id, 403, 'Absensi ini milik pengguna lain.');

        $path = $request->file('file')->store(app(\App\Services\AttendancePhotoPath::class)->directory($attendance), 'public');
        abort_unless($path, 500, 'Foto gagal disimpan. Silakan coba lagi.');
        $column = $data['photo_type'].'_photo_path';
        $previousPath = null;
        try {
            DB::transaction(function () use ($attendance, $column, $path, &$previousPath): void {
                $record = Attendance::whereKey($attendance->getKey())->lockForUpdate()->firstOrFail();
                $previousPath = $record->{$column};
                $record->update([$column => $path]);
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }
        if ($previousPath && $previousPath !== $path) {
            Storage::disk('public')->delete($previousPath);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berkas berhasil diunggah.',
            'file_path' => $path,
            'url' => Storage::disk('public')->url($path),
            'entity_uuid' => $attendance->uuid,
            'photo_type' => $data['photo_type'],
        ]);
    }

    public function pull(): JsonResponse
    {
        return response()->json([
            'permissions' => \App\Support\Access::permissions(request()->user()),
            'users' => (request()->user()->canAccess('users') ? User::where('branch_id', request()->attributes->get('branch_id'))->where('role', '!=', 'admin')->select('id', 'name', 'username', 'role', 'active', 'branch_id')->get() : collect()),
            'services' => Service::select('uuid', 'name', 'unit', 'price', 'is_active', 'speed', 'duration_hours', 'duration_unit', 'branch_id')->get(),
            'wa_templates' => collect(app(\App\Services\StoreConfiguration::class)->read()['templates'])->map(fn ($content, $type) => ['type' => $type, 'content' => $content])->values(),
            'store' => app(\App\Services\StoreConfiguration::class)->read(true),
            'branch' => request()->attributes->get('branch'),
            'server_time' => now()->toIso8601String(),
        ]);
    }

    private function validateRecord(array $data, array $rules, string $prefix): void
    {
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->messages() as $field => $messages) {
                $errors[$prefix.'.'.$field] = $messages;
            }
            throw ValidationException::withMessages($errors);
        }
    }

    private function itemSnapshot(array $items): array
    {
        $snapshot = array_map(fn (array $item) => [
            'service_uuid' => $item['service_uuid'],
            'qty' => (float) $item['qty'],
            'price' => (int) $item['price'],
        ], $items);
        sort($snapshot);

        return $snapshot;
    }
}
