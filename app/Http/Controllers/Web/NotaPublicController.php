<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\QrCodeService;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class NotaPublicController extends Controller
{
    /**
     * Tampilkan halaman nota digital publik tanpa kertas (Paperless).
     *
     * URL Format: /n/{uuid} (UUIDv4)
     * Tidak memerlukan otentikasi.
     */
    public function show(string $uuid): Response
    {
        $headers = [
            'Cache-Control' => 'private, no-store',
            'X-Robots-Tag' => 'noindex, nofollow',
            'Referrer-Policy' => 'no-referrer',
        ];

        $transaction = Transaction::with(['customer', 'items.service'])
            ->where('uuid', strtolower($uuid))
            ->first();

        if (! $transaction) {
            return response()->view('nota.not_found', [], 404, $headers);
        }

        // Kamera biasa membuka nota; scanner kasir mengambil UUID dari URL yang sama.
        $qrCodeUri = QrCodeService::generateDataUri($transaction->public_receipt_url);
        $phone = $transaction->customer?->phone;
        $maskedPhone = $phone
            ? Str::mask($phone, '*', 0, mb_strlen($phone) > 4 ? mb_strlen($phone) - 4 : mb_strlen($phone))
            : '-';

        return response()->view('nota.public', [
            'transaction' => $transaction,
            'customer' => $transaction->customer,
            'items' => $transaction->items,
            'qrCode' => $qrCodeUri,
            'maskedPhone' => $maskedPhone,
        ], 200, $headers);
    }
}
