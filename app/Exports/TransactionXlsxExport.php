<?php

namespace App\Exports;

use App\Models\Transaction;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

/** A real OOXML workbook. Text cells are explicit inline strings, never formulas. */
class TransactionXlsxExport
{
    public function download(array $filters): BinaryFileResponse
    {
        $sheetPath = tempnam(sys_get_temp_dir(), 'laundry-sheet-');
        $bookPath = tempnam(sys_get_temp_dir(), 'laundry-xlsx-');
        $stream = null;
        try {
            $stream = fopen($sheetPath, 'wb');
            fwrite($stream, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><cols>');
            foreach ([48, 22, 22, 28, 20, 24, 48, 20, 20, 18, 18, 60] as $index => $width) {
                $col = $index + 1;
                fwrite($stream, '<col min="'.$col.'" max="'.$col.'" width="'.$width.'" customWidth="1"/>');
            }
            fwrite($stream, '</cols><sheetData>');
            $headings = ['No. Nota', 'Tanggal Masuk (WIB)', 'Tanggal Pengambilan (WIB)', 'Pelanggan', 'WhatsApp', 'Kasir', 'Paket / Layanan', 'Status Cucian', 'Pembayaran', 'Subtotal (Rp)', 'Total (Rp)', 'URL Nota'];
            $this->row($stream, 1, $headings, true);
            $row = 2;
            $transactions = Transaction::with(['customer', 'user', 'items.service'])->filter($filters)->latest()->orderBy('uuid')->lazy(500);
            foreach ($transactions as $transaction) {
                $this->row($stream, $row++, [
                    $transaction->transaction_number,
                    $transaction->created_at->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    $transaction->picked_up_at?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') ?? '',
                    $transaction->customer?->name ?? '-', $transaction->customer?->phone ?? '-',
                    $transaction->user?->name ?? '-',
                    $transaction->items->map(fn ($item) => ($item->service_name ?? $item->service?->name ?? 'Layanan').' ('.$item->qty.' '.($item->unit ?? $item->service?->unit).')')->join('; '),
                    $transaction->laundry_status, $transaction->payment_status,
                    $transaction->subtotal, $transaction->total, $transaction->public_receipt_url,
                ]);
            }
            fwrite($stream, '</sheetData><autoFilter ref="A1:L'.($row - 1).'"/></worksheet>');
            fclose($stream);
            $stream = null;
            $zip = new ZipArchive();
            if ($zip->open($bookPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Berkas Excel tidak dapat dibuat.');
            }
            try {
                $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
                $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
                $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Transaksi Laundry" sheetId="1" r:id="rId1"/></sheets></workbook>');
                $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
                $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF0369A1"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/><xf numFmtId="3" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>');
                $zip->addFile($sheetPath, 'xl/worksheets/sheet1.xml');
            } finally {
                if (! $zip->close()) {
                    throw new \RuntimeException('Berkas Excel gagal disimpan.');
                }
            }
        } catch (\Throwable $error) {
            if (is_resource($stream)) {
                fclose($stream);
            }
            @unlink($bookPath);
            throw $error;
        } finally {
            @unlink($sheetPath);
        }
        return response()->download($bookPath, 'laporan-malahlaundry-'.now()->format('Ymd-His').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'private, no-store',
        ])->deleteFileAfterSend(true);
    }

    private function row($stream, int $row, array $values, bool $heading = false): void
    {
        fwrite($stream, '<row r="'.$row.'">');
        foreach ($values as $index => $value) {
            $cell = chr(65 + $index).$row;
            if (is_int($value) && ! $heading) {
                fwrite($stream, '<c r="'.$cell.'" s="2"><v>'.$value.'</v></c>');
            } else {
                $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', (string) $value);
                $text = htmlspecialchars(mb_substr($text, 0, 32767), ENT_XML1 | ENT_QUOTES, 'UTF-8');
                fwrite($stream, '<c r="'.$cell.'" s="'.($heading ? 1 : 0).'" t="inlineStr"><is><t xml:space="preserve">'.$text.'</t></is></c>');
            }
        }
        fwrite($stream, '</row>');
    }
}
