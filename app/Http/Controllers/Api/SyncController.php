<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SyncPushRequest;
use App\Models\Attendance;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WhatsAppTemplate;
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
            // Dependencies must exist before transactions from the same offline batch.
            foreach ($payload['customers'] ?? [] as $index => $data) {
                $customer = Customer::firstOrNew(['uuid' => strtolower($data['uuid'])]);
                if (! $customer->exists) {
                    $this->validateRecord($data, ['name' => 'required', 'phone' => 'required'], "customers.$index");
                }
                $customer->fill(Arr::only($data, ['name', 'phone', 'address']))->save();
            }

            foreach ($payload['services'] ?? [] as $index => $data) {
                $service = Service::firstOrNew(['uuid' => strtolower($data['uuid'])]);
                if (! $service->exists) {
                    $this->validateRecord($data, ['name' => 'required', 'price' => 'required'], "services.$index");
                }
                $service->fill(Arr::only($data, ['name', 'unit', 'price', 'is_active']))->save();
            }

            foreach ($payload['users'] ?? [] as $index => $data) {
                $user = User::firstOrNew(['username' => $data['username']]);
                if (! $user->exists) {
                    $this->validateRecord($data, ['name' => 'required', 'password' => 'required'], "users.$index");
                }
                if ($user->id === $actor->id
                    && (($data['role'] ?? $user->role) !== 'owner' || ! ($data['active'] ?? $user->active))) {
                    throw ValidationException::withMessages([
                        "users.$index.username" => 'Owner tidak dapat menonaktifkan atau menurunkan peran akun sendiri.',
                    ]);
                }
                if ($user->exists && isset($data['password']) && Hash::check($data['password'], $user->password)) {
                    unset($data['password']);
                }
                $user->fill(Arr::only($data, ['name', 'role', 'active', 'password']));
                $revokeTokens = $user->exists && ($user->isDirty('password') || $user->isDirty('active') && ! $user->active);
                $user->save();
                if ($revokeTokens) {
                    $user->tokens()->delete();
                }
            }

            foreach ($payload['attendances'] ?? [] as $index => $data) {
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
                $attendance->fill(Arr::only($data, ['device_id', 'check_in_time', 'check_out_time']));
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
                        'transaction_number' => 'TRX-'.$uuid,
                        'payment_status' => 'BELUM',
                        'laundry_status' => 'DITERIMA',
                    ]);
                    if (isset($data['created_at'])) {
                        $transaction->created_at = Carbon::parse($data['created_at'])->setTimezone(config('app.timezone'));
                    }
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
                if (isset($data['customer_uuid'])) {
                    $data['customer_uuid'] = strtolower($data['customer_uuid']);
                }
                $this->validateRecord($data, [
                    'customer_uuid' => ['sometimes', Rule::exists('customers', 'uuid')],
                    'transaction_number' => ['sometimes', Rule::unique('transactions', 'transaction_number')->ignore($uuid, 'uuid')],
                ], "transactions.$index");
                // The creator remains unchanged when another cashier handles pickup.
                $transaction->fill(Arr::only($data, ['customer_uuid', 'transaction_number', 'payment_status', 'laundry_status']));
                if ($transaction->laundry_status === 'SELESAI' && $transaction->payment_status !== 'LUNAS') {
                    throw ValidationException::withMessages([
                        "transactions.$index.payment_status" => 'Transaksi harus lunas sebelum selesai diambil.',
                    ]);
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
                        'items.*.service_uuid' => [Rule::exists('services', 'uuid')],
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
                    if ($wasPaid) {
                        $previousItems = $transaction->items()->get()->map(fn ($item) => [
                            'service_uuid' => $item->service_uuid,
                            'qty' => $item->qty,
                            'price' => $item->price,
                        ])->all();
                        if ($this->itemSnapshot($items) !== $this->itemSnapshot($previousItems)) {
                            throw ValidationException::withMessages([
                                "transactions.$index.items" => 'Item transaksi yang sudah lunas tidak dapat diubah.',
                            ]);
                        }
                    }
                    // Snapshot prices retain the amount charged by the offline cashier.
                    $subtotal = array_sum(array_map(fn (array $item) => (int) round($item['qty'] * $item['price']), $items));
                    $transaction->subtotal = $subtotal;
                    $transaction->total = $subtotal;
                    $transaction->save();
                    $transaction->items()->delete();
                    $transaction->items()->createMany($items);
                } else {
                    $transaction->save();
                }
            }
        }, 3);

        return response()->json([
            'status' => 'success',
            'message' => 'Sinkronisasi push berhasil diproses.',
            'synced_at' => now()->toIso8601String(),
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        $data = $request->validate([
            'entity_type' => ['required', Rule::in(['attendance_photo'])],
            'entity_uuid' => ['required', 'uuid:4'],
            'photo_type' => ['required', Rule::in(['check_in', 'check_out'])],
            'file' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:500'],
        ]);

        $attendance = Attendance::where('uuid', strtolower($data['entity_uuid']))->firstOrFail();
        abort_unless($attendance->user_id === $request->user()->id, 403, 'Absensi ini milik pengguna lain.');

        $path = $request->file('file')->store('attendances/'.now()->format('Y/m'), 'public');
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
        ]);
    }

    public function pull(): JsonResponse
    {
        return response()->json([
            'users' => User::select('id', 'name', 'username', 'role', 'active')->get(),
            'services' => Service::select('uuid', 'name', 'unit', 'price', 'is_active')->get(),
            'wa_templates' => WhatsAppTemplate::select('type', 'content')->get(),
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
