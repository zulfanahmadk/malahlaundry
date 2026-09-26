<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Models\WhatsAppTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class SyncController extends Controller
{
    /**
     * POST /sync/push
     * Mengirim Transaksi, Pelanggan Baru, Data Absensi, dan Master Data.
     */
    public function push(Request $request): JsonResponse
    {
        $currentUser = $request->user();

        DB::beginTransaction();
        try {
            // 1. Pelanggan (Customers)
            if ($request->has('customers') && is_array($request->customers)) {
                foreach ($request->customers as $customerData) {
                    if (!empty($customerData['uuid'])) {
                        Customer::updateOrCreate(
                            ['uuid' => $customerData['uuid']],
                            [
                                'name' => $customerData['name'] ?? 'Pelanggan',
                                'phone' => $customerData['phone'] ?? '',
                                'address' => $customerData['address'] ?? null,
                            ]
                        );
                    }
                }
            }

            // 2. Absensi (Attendances)
            if ($request->has('attendances') && is_array($request->attendances)) {
                foreach ($request->attendances as $attData) {
                    if (!empty($attData['uuid'])) {
                        Attendance::updateOrCreate(
                            ['uuid' => $attData['uuid']],
                            [
                                'user_id' => $attData['user_id'] ?? $currentUser->id,
                                'device_id' => $attData['device_id'] ?? null,
                                'check_in_time' => $attData['check_in_time'] ?? null,
                                'check_out_time' => $attData['check_out_time'] ?? null,
                            ]
                        );
                    }
                }
            }

            // 3. Transaksi & Items
            if ($request->has('transactions') && is_array($request->transactions)) {
                foreach ($request->transactions as $trxData) {
                    if (!empty($trxData['uuid'])) {
                        $transaction = Transaction::updateOrCreate(
                            ['uuid' => $trxData['uuid']],
                            [
                                'customer_uuid' => $trxData['customer_uuid'] ?? null,
                                'user_id' => $trxData['user_id'] ?? $currentUser->id,
                                'transaction_number' => $trxData['transaction_number'] ?? ('TRX-' . now()->format('YmdHis')),
                                'subtotal' => $trxData['subtotal'] ?? 0,
                                'total' => $trxData['total'] ?? 0,
                                'payment_status' => $trxData['payment_status'] ?? 'BELUM',
                                'laundry_status' => $trxData['laundry_status'] ?? 'DITERIMA',
                            ]
                        );

                        // Simpan item jika ada dalam payload
                        if (isset($trxData['items']) && is_array($trxData['items'])) {
                            TransactionItem::where('transaction_uuid', $transaction->uuid)->delete();
                            foreach ($trxData['items'] as $item) {
                                if (!empty($item['service_uuid'])) {
                                    TransactionItem::create([
                                        'transaction_uuid' => $transaction->uuid,
                                        'service_uuid' => $item['service_uuid'],
                                        'qty' => $item['qty'] ?? 1,
                                        'price' => $item['price'] ?? 0,
                                    ]);
                                }
                            }
                        }
                    }
                }
            }

            // 4. Master Data (User & Services) — Khusus role Owner
            if ($currentUser->isOwner()) {
                if ($request->has('services') && is_array($request->services)) {
                    foreach ($request->services as $srvData) {
                        if (!empty($srvData['uuid'])) {
                            Service::updateOrCreate(
                                ['uuid' => $srvData['uuid']],
                                [
                                    'name' => $srvData['name'],
                                    'unit' => $srvData['unit'] ?? 'kg',
                                    'price' => $srvData['price'] ?? 0,
                                    'is_active' => $srvData['is_active'] ?? true,
                                ]
                            );
                        }
                    }
                }

                if ($request->has('users') && is_array($request->users)) {
                    foreach ($request->users as $uData) {
                        if (!empty($uData['username'])) {
                            $userFields = [
                                'name' => $uData['name'] ?? $uData['username'],
                                'role' => $uData['role'] ?? 'cashier',
                                'active' => $uData['active'] ?? true,
                            ];
                            if (!empty($uData['password'])) {
                                $userFields['password'] = Hash::make($uData['password']);
                            }
                            User::updateOrCreate(['username' => $uData['username']], $userFields);
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Sinkronisasi push berhasil diproses.',
                'synced_at' => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses sinkronisasi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /sync/upload
     * Upload berkas foto absensi (multipart/form-data).
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'entity_type' => 'required|string',
            'entity_uuid' => 'required|string',
            'photo_type' => 'required|in:check_in,check_out',
            'file' => 'required|file|image|max:2048',
        ]);

        $file = $request->file('file');
        $yearMonth = now()->format('Y/m');
        $path = $file->store("attendances/{$yearMonth}", 'public');

        if ($request->entity_type === 'attendance_photo') {
            $attendance = Attendance::where('uuid', $request->entity_uuid)->first();
            if ($attendance) {
                if ($request->photo_type === 'check_in') {
                    $attendance->check_in_photo_path = $path;
                } else {
                    $attendance->check_out_photo_path = $path;
                }
                $attendance->save();
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berkas berhasil diunggah.',
            'file_path' => $path,
            'url' => Storage::disk('public')->url($path),
        ]);
    }

    /**
     * GET /sync/pull
     * Ditarik secara periodik oleh Android. Memuat Users, Services, dan WA Templates.
     */
    public function pull(): JsonResponse
    {
        $users = User::select('id', 'name', 'username', 'role', 'active')->where('active', true)->get();
        $services = Service::where('is_active', true)->get();
        $templates = WhatsAppTemplate::select('type', 'content')->get();

        return response()->json([
            'users' => $users,
            'services' => $services,
            'wa_templates' => $templates,
            'server_time' => now()->toIso8601String(),
        ]);
    }
}
