<?php

namespace App\Services;

use App\Models\TicketUpdate;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use League\Flysystem\FilesystemException;
use Throwable;
use ZipArchive;

class TicketAttachments
{
    private const MIME_TYPES = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
        'webp' => 'image/webp', 'gif' => 'image/gif', 'pdf' => 'application/pdf',
        'doc' => 'application/msword', 'xls' => 'application/vnd.ms-excel',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    public function rules(): array
    {
        return [
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => ['bail', 'required', 'file', 'max:10240', function (string $attribute, mixed $file, Closure $fail) {
                if (! $file instanceof UploadedFile || ! $this->validType($file)) {
                    $fail('Lampiran harus berupa foto JPG/PNG/WebP/GIF, PDF, Word (DOC/DOCX), atau Excel (XLS/XLSX) dengan isi file yang sesuai.');
                }
            }],
        ];
    }

    public function messages(): array
    {
        return [
            'attachments.array' => 'Lampiran harus berupa daftar file.',
            'attachments.max' => 'Pilih maksimal 5 lampiran per pengiriman.',
            'attachments.*.max' => 'Setiap lampiran maksimal 10 MB.',
            'attachments.*.file' => 'Lampiran gagal diunggah. Pilih ulang file dan coba kembali.',
            'attachments.*.uploaded' => 'Lampiran gagal diunggah. Periksa ukuran file dan coba kembali.',
        ];
    }

    private function validType(UploadedFile $file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if (! isset(self::MIME_TYPES[$extension])) {
            return false;
        }
        if (in_array($extension, ['docx', 'xlsx'], true)) {
            $zip = new ZipArchive();
            if ($zip->open($file->getRealPath(), ZipArchive::RDONLY) !== true) {
                return false;
            }
            try {
                return $zip->locateName('[Content_Types].xml') !== false
                    && $zip->locateName($extension === 'docx' ? 'word/document.xml' : 'xl/workbook.xml') !== false;
            } finally {
                $zip->close();
            }
        }
        if (in_array($extension, ['doc', 'xls'], true)) {
            // Older Office documents are OLE containers; fileinfo may report a generic OLE MIME.
            $contents = file_get_contents($file->getRealPath());
            $streams = $extension === 'doc' ? ['WordDocument'] : ['Workbook', 'Book'];
            return is_string($contents)
                && in_array($file->getMimeType(), [self::MIME_TYPES[$extension], 'application/x-ole-storage', 'application/CDFV2', 'application/octet-stream'], true)
                && str_starts_with($contents, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1")
                && collect($streams)->contains(fn ($stream) => str_contains($contents, mb_convert_encoding($stream, 'UTF-16LE', 'UTF-8')));
        }
        return $file->getMimeType() === self::MIME_TYPES[$extension];
    }

    public function transaction(Closure $callback): mixed
    {
        $paths = [];
        try {
            return DB::transaction(function () use ($callback, &$paths) {
                return $callback($paths);
            });
        } catch (Throwable $exception) {
            if ($paths) {
                try {
                    Storage::disk('ticket_attachments')->delete($paths);
                } catch (Throwable) {
                    Log::channel('tiket-bantuan')->error('Pembersihan lampiran setelah transaksi gagal.');
                }
            }
            throw $exception;
        }
    }

    public function store(TicketUpdate $update, array $files, array &$paths): void
    {
        foreach ($files as $file) {
            $extension = strtolower($file->getClientOriginalExtension());
            $path = $update->support_ticket_id.'/'.Str::uuid().'.'.$extension;
            // Track even a partially written file so failed uploads can be cleaned up.
            $paths[] = $path;
            try {
                if (Storage::disk('ticket_attachments')->putFileAs(dirname($path), $file, basename($path)) === false) {
                    throw ValidationException::withMessages(['attachments' => 'Lampiran gagal disimpan. Silakan coba kembali.']);
                }
            } catch (FilesystemException) {
                throw ValidationException::withMessages(['attachments' => 'Lampiran gagal disimpan. Silakan coba kembali.']);
            }
            $name = preg_replace('/[\x00-\x1F\x7F\/\\\\]/u', '_', mb_convert_encoding($file->getClientOriginalName(), 'UTF-8', 'UTF-8'));
            $name = mb_substr(pathinfo($name, PATHINFO_FILENAME), 0, 220).'.'.$extension;
            $update->attachments()->create([
                'path' => $path, 'original_name' => $name,
                'mime_type' => self::MIME_TYPES[$extension], 'size' => $file->getSize(),
            ]);
        }
    }
}
