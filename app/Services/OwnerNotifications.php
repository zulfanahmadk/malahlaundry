<?php

namespace App\Services;

use App\Models\DeviceSyncState;
use App\Models\Attendance;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OwnerNotifications
{
    public function all(): Collection
    {
        $rows = collect();
        foreach (Transaction::with('customer')->where('laundry_status', 'SIAP_DIAMBIL')
            ->whereNotNull('ready_at')->where('ready_at', '<=', now()->subDays(3))->latest('ready_at')->limit(200)->get() as $order) {
            $rows->push([
                'key' => 'pickup:'.$order->uuid, 'category' => 'CUCIAN', 'time' => $order->ready_at,
                'title' => $order->transaction_number.' · '.($order->customer?->name ?? 'Pelanggan').' belum diambil lebih dari 3 hari.',
                'url' => route('transactions.show', $order->uuid),
            ]);
        }
        foreach (DeviceSyncState::with('user')->get() as $device) {
            if (! $device->attention && ! $device->pending_count) {
                continue;
            }
            $rows->push([
                'key' => 'device:'.$device->id.':'.$device->last_seen_at->timestamp,
                'category' => 'SINKRON', 'time' => $device->last_seen_at,
                'stale' => $device->last_seen_at->lt(now()->subMinutes(30)),
                'title' => $device->name.' · '.match (true) {
                    $device->failed_count > 0 => $device->failed_count.' data gagal disinkronkan.',
                    filled($device->last_error) => 'Sinkronisasi terakhir mengalami kesalahan.',
                    $device->pending_count > 0 => $device->pending_count.' data menunggu sinkronisasi.',
                    $device->last_seen_at->lt(now()->subMinutes(30)) => 'Belum melapor kembali dalam 30 menit.',
                    default => 'Belum melaporkan sinkronisasi selesai.',
                },
                'url' => route('sync.index'),
            ]);
        }
        $branch = request()->attributes->get('branch');
        foreach (Attendance::with('user')->where('check_in_time', '>=', today()->subDays(6))->latest('check_in_time')->limit(200)->get() as $attendance) {
            if (app(BranchHours::class)->attendance($branch, $attendance->check_in_time) !== 'TERLAMBAT') continue;
            $date = $attendance->check_in_time->copy()->timezone('Asia/Jakarta')->toDateString();
            $rows->push([
                'key' => 'attendance:'.$attendance->uuid, 'category' => 'PRESENSI', 'time' => $attendance->check_in_time,
                'title' => ($attendance->user?->name ?? 'Staf').' masuk setelah jam buka cabang.',
                'url' => route('attendances.index', ['user_id' => $attendance->user_id, 'from' => $date, 'to' => $date]),
            ]);
        }
        $user = auth()->user();
        if ($user?->last_login_at && $user->notify_login) {
            $rows->push([
                'key' => 'login:'.$user->id.':'.$user->last_login_at->timestamp,
                'category' => 'AKUN', 'time' => $user->last_login_at,
                'title' => 'Login akun '.$user->username.' berhasil.', 'url' => route('profile.edit'),
            ]);
        }
        $read = DB::table('owner_notification_reads')->where('user_id', $user->id)
            ->where('branch_id', request()->attributes->get('branch_id'))->pluck('event_key')->flip();
        return $rows->sortByDesc(fn ($row) => $row['time']->timestamp)->map(function ($row) use ($read) {
            $row['read'] = $read->has($row['key']);
            return $row;
        })->values();
    }

    public function markAllRead(): void
    {
        $rows = $this->all()->map(fn ($row) => [
            'user_id' => auth()->id(), 'branch_id' => request()->attributes->get('branch_id'),
            'event_key' => $row['key'], 'created_at' => now(), 'updated_at' => now(),
        ])->all();
        if ($rows) {
            DB::table('owner_notification_reads')->upsert($rows, ['user_id', 'branch_id', 'event_key'], ['updated_at']);
        }
    }
}
