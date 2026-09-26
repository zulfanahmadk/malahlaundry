<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\QrCodeService;
use Illuminate\Contracts\View\View;

class NotaPublicController extends Controller
{
    /**
     * Tampilkan halaman nota digital publik tanpa kertas (Paperless).
     *
     * URL Format: /n/{uuid} (UUIDv4)
     * Tidak memerlukan otentikasi.
     */
    public function show(string $uuid): View
    {
        $transaction = Transaction::with(['customer', 'items.service', 'user'])
            ->where('uuid', $uuid)
            ->first();

        if (!$transaction) {
            return view('nota.not_found', [
                'uuid' => $uuid,
            ]);
        }

        // Generate QR Code untuk dipindai kamera HP kasir saat pengambilan
        $qrCodeData = $transaction->uuid;
        $qrCodeUri = QrCodeService::generateDataUri($qrCodeData);

        return view('nota.public', [
            'transaction' => $transaction,
            'customer' => $transaction->customer,
            'items' => $transaction->items,
            'qrCode' => $qrCodeUri,
        ]);
    }
}
