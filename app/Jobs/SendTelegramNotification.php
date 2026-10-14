<?php

namespace App\Jobs;

use App\Models\TelegramDelivery;
use App\Services\TelegramBot;
use App\Services\TelegramDeliveryException;
use App\Services\TelegramNotifications;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

class SendTelegramNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;
    public int $timeout = 30;
    public bool $failOnTimeout = true;

    public function __construct(public int $deliveryId)
    {
    }

    public function handle(TelegramBot $bot, TelegramNotifications $notifications): void
    {
        // Serialize duplicate workers for this delivery. Never retry this transaction around HTTP.
        DB::transaction(function () use ($bot, $notifications) {
            $delivery = TelegramDelivery::whereKey($this->deliveryId)->lockForUpdate()->first();
            if (! $delivery || $delivery->status !== 'pending' || $delivery->available_at->isFuture()) {
                return;
            }
            $subscription = $delivery->subscription;
            $preference = $delivery->event === 'test' ? true : $subscription?->{'notify_'.$delivery->event};
            if (! $subscription || $subscription->chat_id !== $delivery->chat_id
                || ($delivery->event !== 'test' && ! $subscription->enabled)
                || ! $preference || ! $notifications->allowed($subscription)) {
                $delivery->update(['status' => 'cancelled', 'last_error' => 'Penerima, preferensi, atau akses sudah berubah.']);
                return;
            }

            $delivery->attempts++;
            try {
                $messageId = $bot->send($delivery->chat_id, $delivery->text, $delivery->transaction_url);
                $delivery->fill(['status' => 'sent', 'sent_at' => now(), 'telegram_message_id' => $messageId, 'last_error' => null]);
            } catch (TelegramDeliveryException $error) {
                $delay = max($error->retryAfter, [60, 120, 300, 600, 900][min($delivery->attempts - 1, 4)]);
                $delivery->fill([
                    'status' => $error->retryable && $delivery->attempts < 5 ? 'pending' : 'failed',
                    'last_error' => $error->getMessage(), 'available_at' => now()->addSeconds($delay),
                ]);
            }
            $delivery->fill(['queued_at' => null, 'queue_job_id' => null])->save();
        });
    }

    public function failed(?Throwable $exception): void
    {
        DB::transaction(function () {
            $delivery = TelegramDelivery::whereKey($this->deliveryId)->lockForUpdate()->first();
            if ($delivery && $delivery->status === 'pending') {
                $delivery->attempts++;
                $delivery->fill([
                    'status' => $delivery->attempts < 5 ? 'pending' : 'failed',
                    'queued_at' => null, 'queue_job_id' => null, 'available_at' => now()->addMinute(),
                    'last_error' => 'Proses pengiriman terhenti. Konfirmasi Telegram belum tercatat.',
                ])->save();
            }
        });
    }
}
