<?php

namespace App\Services;

use App\Models\StoreSetting;
use App\Models\WhatsAppTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StoreConfiguration
{
    public const TEMPLATES = [
        'WA_DITERIMA' => "Halo {nama}, cucian Anda sudah diterima di {outlet}.\nNo. nota: {no_transaksi}\n{items}\nTotal: Rp{total}\nPembayaran: {status_bayar}\nNota: {url_nota}\n{alamat_outlet}\nHubungi: {telepon_outlet}",
        'WA_SIAP_DIAMBIL' => "Halo {nama}, cucian {no_transaksi} sudah siap diambil di {outlet}.\nSisa tagihan: Rp{sisa_bayar}\nNota: {url_nota}\n{alamat_outlet}\nHubungi: {telepon_outlet}",
        'WA_SELESAI' => "Terima kasih {nama}, cucian {no_transaksi} telah diambil.\nTerima kasih telah menggunakan {outlet}.\nNota: {url_nota}\nHubungi: {telepon_outlet}",
    ];

    public function read(bool $includeLogoData = false): array
    {
        $record = StoreSetting::find(1);
        $path = $record?->logo_path;
        $hasLogo = $path && Storage::disk('public')->exists($path);
        $templates = array_replace(self::TEMPLATES, WhatsAppTemplate::pluck('content', 'type')->all());
        $data = [
            'name' => $record?->name ?? 'Malah Laundry',
            'phone' => $record?->phone ?? '',
            'address' => $record?->address ?? '',
            'receipt_terms' => $record?->receipt_terms ?? '',
            'receipt_base_url' => config('domains.receipt_url') ?: config('app.url'),
            'logo_url' => $hasLogo ? route('store.logo', ['v' => $record->updated_at?->timestamp]) : null,
            'templates' => array_intersect_key($templates, self::TEMPLATES),
        ];
        if ($includeLogoData) {
            $data['logo_data'] = $hasLogo ? base64_encode(Storage::disk('public')->get($path)) : '';
        }
        return $data;
    }

    public function save(Request $request): void
    {
        $rules = [
            'name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:40',
            'address' => 'nullable|string|max:1000',
            'receipt_terms' => 'nullable|string|max:5000',
            'logo' => 'nullable|image|mimes:jpeg,png,webp|max:1024|dimensions:max_width=4096,max_height=4096',
            'logo_base64' => 'nullable|string|max:1400000',
            'remove_logo' => 'sometimes|boolean',
            'templates' => 'required|array:WA_DITERIMA,WA_SIAP_DIAMBIL,WA_SELESAI',
        ];
        foreach (array_keys(self::TEMPLATES) as $type) {
            $rules['templates.'.$type] = 'required|string|max:4000';
        }
        $data = $request->validate($rules);
        $bytes = $request->hasFile('logo') ? $request->file('logo')->get() : null;
        if (! empty($data['logo_base64'])) {
            $bytes = base64_decode($data['logo_base64'], true);
        }
        $newPath = null;
        if ($bytes !== null) {
            $info = is_string($bytes) && strlen($bytes) <= 1048576 ? @getimagesizefromstring($bytes) : false;
            if (! $info || ! in_array($info['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)
                || $info[0] > 4096 || $info[1] > 4096) {
                throw ValidationException::withMessages(['logo' => 'Logo harus JPG, PNG, atau WebP, maksimal 1 MB dan 4096 × 4096 piksel.']);
            }
            // Re-encode to a small trusted PNG; strip metadata and preserve transparency.
            $source = imagecreatefromstring($bytes);
            $scale = min(1, 384 / max($info[0], $info[1]));
            $width = max(1, (int) round($info[0] * $scale));
            $height = max(1, (int) round($info[1] * $scale));
            $image = imagecreatetruecolor($width, $height);
            imagealphablending($image, false);
            imagesavealpha($image, true);
            imagecopyresampled($image, $source, 0, 0, 0, 0, $width, $height, $info[0], $info[1]);
            ob_start();
            imagepng($image);
            $png = ob_get_clean();
            imagedestroy($source);
            imagedestroy($image);
            $newPath = 'store/logos/'.Str::uuid().'.png';
            if (! Storage::disk('public')->put($newPath, $png)) {
                throw new \RuntimeException('Logo gagal disimpan.');
            }
        }
        $previousPath = null;
        try {
            DB::transaction(function () use ($data, $newPath, &$previousPath): void {
                $record = StoreSetting::firstOrCreate(['id' => 1], ['name' => 'Malah Laundry']);
                $record = StoreSetting::whereKey(1)->lockForUpdate()->firstOrFail();
                $previousPath = $record->logo_path;
                $record->fill(collect($data)->only(['name', 'phone', 'address', 'receipt_terms'])->all());
                if ($newPath || ($data['remove_logo'] ?? false)) {
                    $record->logo_path = $newPath;
                }
                $record->save();
                foreach ($data['templates'] as $type => $content) {
                    WhatsAppTemplate::updateOrCreate(['type' => $type], ['content' => $content]);
                }
            });
        } catch (\Throwable $error) {
            if ($newPath) {
                Storage::disk('public')->delete($newPath);
            }
            throw $error;
        }
        if ($previousPath && ($newPath || ($data['remove_logo'] ?? false))) {
            Storage::disk('public')->delete($previousPath);
        }
    }
}
