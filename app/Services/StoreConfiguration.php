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
        'WA_DITERIMA' => <<<'MESSAGE'
🧺 {outlet}
Cuci • Lipat • Beres

Hai Kak {nama} 👋

Kami dari {outlet} ingin menginformasikan bahwa laundry Kakak sudah kami terima dan akan diproses.

📋 DETAIL TRANSAKSI
No. Transaksi: {no_transaksi}
Layanan:
{items}
Total: Rp{total}

💰 STATUS PEMBAYARAN
Total Bayar: Rp{total_dibayar}
Sisa Pembayaran: Rp{sisa_bayar}
Status: {status_bayar_label}

📦 STATUS CUCIAN
Laundry Kakak sedang menunggu proses pencucian. Kami akan mengabari Kakak saat laundry siap diambil.

📍 OUTLET
{alamat_outlet}
WhatsApp: {telepon_outlet}

🔗 Detail Transaksi & Nota:
{url_nota}

Terima kasih telah mempercayakan kebutuhan laundry Kakak kepada {outlet}. 🙏

🧺 {outlet}
Cuci • Lipat • Beres ✨
MESSAGE,
        'WA_SIAP_DIAMBIL' => <<<'MESSAGE'
🧺 {outlet}
Cuci • Lipat • Beres

Hai Kak {nama} 👋

Kabar baik! Laundry Kakak di {outlet} sudah selesai diproses dan siap diambil.

📋 DETAIL TRANSAKSI
No. Transaksi: {no_transaksi}
Layanan:
{items}
Total: Rp{total}

💰 STATUS PEMBAYARAN
Total Bayar: Rp{total_dibayar}
Sisa Pembayaran: Rp{sisa_bayar}
Status: {status_bayar_label}

📦 STATUS PENGAMBILAN
Laundry Kakak sudah tersedia di {outlet} dan siap diambil.

Silakan datang ke outlet kami dan tunjukkan nota digital saat pengambilan.
Jika membutuhkan layanan antar, silakan hubungi kami melalui WhatsApp.

📍 OUTLET
{alamat_outlet}
WhatsApp: {telepon_outlet}

🔗 Detail Transaksi & Nota:
{url_nota}

Terima kasih telah mempercayakan kebutuhan laundry Kakak kepada {outlet}. 🙏

🧺 {outlet}
Cuci • Lipat • Beres ✨
MESSAGE,
        'WA_SELESAI' => <<<'MESSAGE'
🧺 {outlet}
Cuci • Lipat • Beres

Hai Kak {nama} 👋

Kami dari {outlet} ingin menginformasikan bahwa laundry Kakak sudah selesai diambil.

📋 DETAIL TRANSAKSI
No. Transaksi: {no_transaksi}
Layanan:
{items}
Total: Rp{total}

💰 STATUS PEMBAYARAN
Total Bayar: Rp{total_dibayar}
Sisa Pembayaran: Rp{sisa_bayar}
Status: {status_bayar_label}

📦 STATUS PENGAMBILAN
Laundry Kakak sudah diserahkan. Semoga pakaian Kakak selalu bersih dan wangi. Kami menunggu kunjungan Kakak berikutnya!

📍 OUTLET
{alamat_outlet}
WhatsApp: {telepon_outlet}

🔗 Detail Transaksi & Nota:
{url_nota}

Terima kasih telah mempercayakan kebutuhan laundry Kakak kepada {outlet}. 🙏

🧺 {outlet}
Cuci • Lipat • Beres ✨
MESSAGE,
        'WA_REMINDER' => <<<'MESSAGE'
🧺 {outlet}
Cuci • Lipat • Beres

Hai Kak {nama} 👋

Kami dari {outlet} ingin menginformasikan bahwa laundry Kakak belum dilakukan pengambilan.

📋 DETAIL TRANSAKSI
No. Transaksi: {no_transaksi}
Layanan:
{items}
Total: Rp{total}

💰 STATUS PEMBAYARAN
Total Bayar: Rp{total_dibayar}
Sisa Pembayaran: Rp{sisa_bayar}
Status: {status_bayar_label}

📦 STATUS PENGAMBILAN
Laundry Kakak masih tersedia di {outlet} dan belum dilakukan pengambilan.
Siap diambil sejak: {tanggal_siap}

Silakan datang ke outlet kami untuk melakukan pengambilan.
Jika membutuhkan layanan antar, silakan hubungi kami melalui WhatsApp.

📍 OUTLET
{alamat_outlet}
WhatsApp: {telepon_outlet}

🔗 Detail Transaksi & Nota:
{url_nota}

Terima kasih telah mempercayakan kebutuhan laundry Kakak kepada {outlet}. 🙏

🧺 {outlet}
Cuci • Lipat • Beres ✨

Mohon diabaikan apabila laundry sudah diambil.
MESSAGE,
    ];

    public function read(bool $includeLogoData = false, ?int $branchId = null): array
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
        $branchId ??= request()->attributes->get('branch_id');
        $branch = $branchId ? \App\Models\Branch::find($branchId) : null;
        if ($branch) {
            $data['logo_url'] = $branch->logo_data ? route('store.logo', ['branch' => $branch->id, 'v' => $branch->updated_at?->timestamp]) : null;
            if ($includeLogoData) {
                $data['logo_data'] = $branch->logo_data ?? '';
            }
            $data = array_replace($data, [
                'name' => $branch->show_branch ? $branch->store_name.' — '.$branch->name : $branch->store_name,
                'store_name' => $branch->store_name, 'branch_name' => $branch->name, 'branch_id' => $branch->id,
                'phone' => $branch->phone ?? '', 'address' => $branch->address ?? '',
                'receipt_terms' => $branch->receipt_terms ?? '',
                'templates' => array_replace($data['templates'], $branch->templates ?? []),
                'show_branch' => $branch->show_branch, 'complaint_days' => $branch->complaint_days,
                'receipt_preferences' => array_replace(['show_phone' => true, 'show_terms' => true], $branch->receipt_preferences ?? []),
                'message_preferences' => array_replace(['WA_DITERIMA' => true, 'WA_SIAP_DIAMBIL' => true, 'WA_REMINDER' => true], $branch->message_preferences ?? []),
            ]);
        }
        return $data;
    }

    public function save(Request $request): void
    {
        $branch = $request->attributes->get('branch');
        if ($branch) {
            $data = $request->validate([
                'name' => 'required|string|max:100', 'branch_name' => 'sometimes|required|string|max:100',
                'phone' => 'nullable|string|max:40', 'address' => 'nullable|string|max:1000',
                'receipt_terms' => 'nullable|string|max:5000', 'show_branch' => 'sometimes|boolean',
                'complaint_days' => 'sometimes|integer|between:0,365',
                'receipt_preferences' => 'sometimes|array:show_phone,show_terms', 'receipt_preferences.*' => 'boolean',
                'logo' => 'nullable|image|mimes:jpeg,png,webp|max:1024|dimensions:max_width=4096,max_height=4096',
                'logo_base64' => 'nullable|string|max:1400000', 'remove_logo' => 'sometimes|boolean',
                'templates' => 'sometimes|array:WA_DITERIMA,WA_SIAP_DIAMBIL,WA_SELESAI,WA_REMINDER',
                'templates.*' => 'required|string|max:4000',
            ]);
            $changes = collect($data)->except(['name', 'branch_name', 'logo', 'logo_base64', 'remove_logo'])->all();
            $changes['store_name'] = $data['name'];
            if (isset($changes['receipt_preferences'])) {
                $changes['receipt_preferences'] = array_map(fn ($value) => (bool) $value, $changes['receipt_preferences']);
            }
            if (isset($data['branch_name'])) {
                $changes['name'] = $data['branch_name'];
            }
            if ($request->boolean('remove_logo')) {
                $changes['logo_data'] = null;
            } elseif ($request->hasFile('logo') || ! empty($data['logo_base64'])) {
                $changes['logo_data'] = app(BranchLogo::class)->normalize($request->hasFile('logo')
                    ? base64_encode($request->file('logo')->get()) : $data['logo_base64']);
            }
            // Merge JSON preferences against the locked row so partial API updates
            // do not erase settings saved in the web workspace.
            DB::transaction(function () use ($branch, $changes): void {
                $record = \App\Models\Branch::whereKey($branch->id)->lockForUpdate()->firstOrFail();
                foreach (['templates', 'receipt_preferences'] as $key) {
                    if (isset($changes[$key])) {
                        $changes[$key] = array_replace($record->$key ?? [], $changes[$key]);
                    }
                }
                $record->fill($changes)->save();
            });
            $request->attributes->set('branch', $branch->fresh());
            return;
        }
        $rules = [
            'name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:40',
            'address' => 'nullable|string|max:1000',
            'receipt_terms' => 'nullable|string|max:5000',
            'logo' => 'nullable|image|mimes:jpeg,png,webp|max:1024|dimensions:max_width=4096,max_height=4096',
            'logo_base64' => 'nullable|string|max:1400000',
            'remove_logo' => 'sometimes|boolean',
            'templates' => 'required|array:WA_DITERIMA,WA_SIAP_DIAMBIL,WA_SELESAI,WA_REMINDER',
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
