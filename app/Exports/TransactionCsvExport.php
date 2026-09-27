<?php

namespace App\Exports;

use App\Models\Transaction;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionCsvExport
{
    public function download(array $filters): StreamedResponse
    {
        return response()->streamDownload(function () use ($filters): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, [
                'No Transaksi', 'Tanggal (WIB)', 'Nama Pelanggan', 'No WhatsApp', 'Kasir',
                'Status Laundry', 'Status Pembayaran', 'Subtotal (Rp)', 'Total (Rp)', 'URL Nota Digital',
            ], ',', '"', '');

            $transactions = Transaction::with(['customer', 'user'])->filter($filters)
                ->latest()->orderBy('uuid')->lazy(500);

            foreach ($transactions as $transaction) {
                fputcsv($stream, [
                    $this->text($transaction->transaction_number),
                    $transaction->created_at->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    $this->text($transaction->customer?->name ?? '-'),
                    $this->text($transaction->customer?->phone ?? '-'),
                    $this->text($transaction->user?->name ?? '-'),
                    $transaction->laundry_status,
                    $transaction->payment_status,
                    $transaction->subtotal,
                    $transaction->total,
                    $transaction->public_receipt_url,
                ], ',', '"', '');
            }

            fclose($stream);
        }, 'laporan-malahlaundry-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    private function text(string $value): string
    {
        // Spreadsheet applications must treat customer input as text, never a formula.
        return preg_match('/^[\s\x00-\x1F]*[=+@-]|^[\t\r\n]/u', $value) ? "'".$value : $value;
    }
}
