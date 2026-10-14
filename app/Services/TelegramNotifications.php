<?php

namespace App\Services;

use App\Jobs\SendTelegramNotification;
use App\Models\Branch;
use App\Models\TelegramDelivery;
use App\Models\TelegramSubscription;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Throwable;

class TelegramNotifications
{
    public function record(Transaction $transaction, bool $created, ?string $previousStatus): void
    {
        $events = [];
        if ($created) {
            $events[] = 'created';
        }
        if ($transaction->laundry_status === 'SELESAI' && $transaction->picked_up_at && ($created || $previousStatus !== 'SELESAI')) {
            $events[] = 'picked_up';
        }
        if (! $events) {
            return;
        }

        $subscriptions = TelegramSubscription::where('branch_id', $transaction->branch_id)
            ->where('enabled', true)->whereNotNull('chat_id')
            ->whereHas('user', fn ($q) => $q->where('role', 'owner')->where('active', true))->with('user')->get();
        if ($subscriptions->isEmpty()) {
            return;
        }

        $branch = Branch::findOrFail($transaction->branch_id);
        $transaction->loadMissing(['customer', 'user', 'items']);
        foreach ($events as $event) {
            foreach ($subscriptions as $subscription) {
                if (! $subscription->{'notify_'.$event} || ! $this->allowed($subscription)) {
                    continue;
                }
                TelegramDelivery::firstOrCreate([
                    'transaction_uuid' => $transaction->uuid, 'event' => $event, 'chat_id' => $subscription->chat_id,
                ], [
                    'subscription_id' => $subscription->id, 'branch_id' => $transaction->branch_id,
                    'text' => $this->message($transaction, $branch, $event),
                    'transaction_url' => rtrim(config('app.url'), '/').'/transactions/'.$transaction->uuid,
                    'available_at' => now(),
                ]);
            }
        }
    }

    public function allowed(TelegramSubscription $subscription): bool
    {
        $user = $subscription->user;
        return $user && $user->active && $user->isOwner() && $user->canAccess('transactions')
            && (! $user->accessRole?->branch_id || (int) $user->accessRole->branch_id === (int) $subscription->branch_id);
    }

    public function dispatchPending(): int
    {
        // A dedicated database queue shares the outbox connection: enqueue and claim are atomic.
        $ids = TelegramDelivery::where('status', 'pending')->where('available_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('queued_at')->orWhere(function ($q) {
                    $q->where('queued_at', '<', now()->subMinutes(2))->whereNotExists(function ($q) {
                        $q->selectRaw('1')->from('jobs')->whereColumn('jobs.id', 'telegram_deliveries.queue_job_id');
                    });
                });
            })->orderBy('id')->limit(100)->pluck('id');
        $count = 0;
        foreach ($ids as $id) {
            $count += DB::transaction(function () use ($id) {
                $delivery = TelegramDelivery::whereKey($id)->lockForUpdate()->first();
                if (! $delivery || $delivery->status !== 'pending' || $delivery->available_at->isFuture()) {
                    return 0;
                }
                if ($delivery->queued_at && ($delivery->queued_at->gt(now()->subMinutes(2))
                    || DB::table('jobs')->where('id', $delivery->queue_job_id)->exists())) {
                    return 0;
                }
                $jobId = Queue::connection('telegram')->push(new SendTelegramNotification($id), '', 'telegram');
                $delivery->update(['queued_at' => now(), 'queue_job_id' => $jobId]);
                return 1;
            });
        }
        return $count;
    }

    public function dispatchSafely(): void
    {
        try {
            $this->dispatchPending();
        } catch (Throwable) {
            // A committed sync must still return its acknowledgement. Scheduler recovers the outbox.
            Log::warning('Antrean Telegram belum diproses; akan dicoba ulang oleh scheduler.');
        }
    }

    private function message(Transaction $transaction, Branch $branch, string $event): string
    {
        $lines = [
            $event === 'created' ? '🧺 TRANSAKSI BARU' : '✅ CUCIAN SUDAH DIAMBIL',
            'Toko: '.$this->line($branch->store_name ?? 'Malah Laundry'),
            'Cabang: '.$this->line($branch->name),
            'Nota: '.$this->line($transaction->transaction_number),
            'Pelanggan: '.$this->line($transaction->customer?->name ?? 'Pelanggan'),
            'Layanan:',
        ];
        foreach ($transaction->items->take(12) as $item) {
            $qty = rtrim(rtrim(number_format((float) $item->qty, 3, ',', '.'), '0'), ',');
            $lines[] = '• '.$this->line($item->service_name).' · '.$qty.' '.$this->line($item->unit);
        }
        if ($transaction->items->count() > 12) {
            $lines[] = '… dan '.($transaction->items->count() - 12).' layanan lainnya.';
        }
        $lines[] = 'Total: Rp'.number_format($transaction->total, 0, ',', '.');
        $lines[] = 'Pembayaran: '.($transaction->isPaid() ? 'Lunas' : 'Belum lunas');
        if ($event === 'created') {
            $lines[] = 'Kasir penerima: '.$this->line($transaction->user?->name ?? '-');
            $lines[] = 'Masuk: '.$transaction->created_at->copy()->timezone('Asia/Jakarta')->format('d/m/Y H:i').' WIB';
        } else {
            $lines[] = 'Diambil: '.$transaction->picked_up_at->copy()->timezone('Asia/Jakarta')->format('d/m/Y H:i').' WIB';
        }
        return implode("\n", $lines);
    }

    private function line(string $value): string
    {
        return Str::limit(preg_replace('/\s+/u', ' ', $value), 120);
    }
}
