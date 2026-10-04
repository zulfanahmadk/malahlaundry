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
    public function show(string $uuid): Response|\Illuminate\Http\RedirectResponse
    {
        $receiptUrl = config('domains.receipt_url');
        if ($receiptUrl && request()->getHost() !== parse_url($receiptUrl, PHP_URL_HOST)) {
            return redirect()->away(rtrim($receiptUrl, '/').'/n/'.$uuid, 302)
                ->header('Cache-Control', 'private, no-store');
        }
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

        $store = app(\App\Services\StoreConfiguration::class)->read(false, $transaction->branch_id);
        if (! ($store['receipt_preferences']['show_phone'] ?? true)) {
            $store['phone'] = '';
        }
        if (! ($store['receipt_preferences']['show_terms'] ?? true)) {
            $store['receipt_terms'] = '';
        }
        $branch = \App\Models\Branch::find($transaction->branch_id);
        $store['opening_status'] = $branch && ($branch->operational_preferences['show_open_status'] ?? true)
            ? app(\App\Services\BranchHours::class)->status($branch) : null;
        $store['receipt_terms'] = strtr($store['receipt_terms'], [
            '{nama_toko}' => $store['store_name'] ?? $store['name'], '{cabang}' => $store['branch_name'] ?? '',
            '{nomor_nota}' => $transaction->transaction_number, '{hari_komplain}' => (string) ($store['complaint_days'] ?? 3),
        ]);
        return response()->view('nota.public', [
            'store' => $store,
            'transaction' => $transaction,
            'customer' => $transaction->customer,
            'items' => $transaction->items,
            'qrCode' => $qrCodeUri,
            'maskedPhone' => $maskedPhone,
        ], 200, $headers);
    }
}
