<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class TelegramBot
{
    public function configured(): bool
    {
        return filled(config('telegram.bot_token'));
    }

    public function send(string $chatId, string $text, ?string $transactionUrl = null): int
    {
        if (! $this->configured()) {
            throw new TelegramDeliveryException('Token bot belum dikonfigurasi di server.');
        }

        $payload = ['chat_id' => $chatId, 'text' => $text, 'link_preview_options' => ['is_disabled' => true]];
        if ($transactionUrl && preg_match('#^https?://#', $transactionUrl)) {
            $payload['reply_markup'] = ['inline_keyboard' => [[['text' => 'Lihat transaksi', 'url' => $transactionUrl]]]];
        }

        try {
            $response = Http::connectTimeout(5)->timeout(12)->post(
                'https://api.telegram.org/bot'.config('telegram.bot_token').'/sendMessage',
                $payload,
            );
        } catch (Throwable) {
            // HTTP exception URLs contain the token. Never retain or log the original exception.
            throw new TelegramDeliveryException('Koneksi Telegram gagal atau timeout. Pengiriman akan dicoba ulang.', true);
        }

        if ($response->successful() && $response->json('ok') === true && is_numeric($response->json('result.message_id'))) {
            return (int) $response->json('result.message_id');
        }

        $code = (int) ($response->json('error_code') ?: $response->status());
        // Only fixed messages are stored; remote descriptions can include submitted data.
        throw match ($code) {
            429 => new TelegramDeliveryException('Batas pengiriman Telegram tercapai. Menunggu untuk mencoba ulang.', true, max(1, (int) $response->json('parameters.retry_after', 60))),
            401, 404 => new TelegramDeliveryException('Token bot tidak valid. Periksa konfigurasi server.'),
            403 => new TelegramDeliveryException('Bot diblokir atau tidak diizinkan mengirim. Buka bot dan tekan Start, atau periksa akses grup.'),
            400 => new TelegramDeliveryException('Telegram menolak pesan. Periksa Chat ID dan pastikan penerima sudah menekan Start.'),
            default => new TelegramDeliveryException('Telegram belum mengonfirmasi pengiriman pesan.', true),
        };
    }
}
