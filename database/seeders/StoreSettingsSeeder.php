<?php

namespace Database\Seeders;

use App\Models\StoreSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Fill missing receipt data only; owner-supplied values are preserved. */
class StoreSettingsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            StoreSetting::firstOrCreate(['id' => 1], ['name' => 'Malah Laundry']);
            $store = StoreSetting::whereKey(1)->lockForUpdate()->firstOrFail();
            $defaults = [
                'name' => 'Malah Laundry',
                'address' => 'Jl. Contoh No. 1, Kota Anda (alamat contoh)',
                'phone' => '08xx-xxxx-xxxx (contoh)',
                'receipt_terms' => "1. Simpan nota digital dan tunjukkan saat pengambilan cucian.\n2. Pastikan jumlah dan jenis layanan pada nota sesuai dengan pesanan Anda.\n3. Pengambilan cucian dilakukan setelah pembayaran lunas.\n4. Periksa cucian saat pengambilan dan sampaikan pertanyaan kepada petugas.",
            ];
            foreach ($defaults as $field => $value) {
                if (blank($store->{$field})) {
                    $store->{$field} = $value;
                }
            }
            if (! $store->logo_path || ! Storage::disk('public')->exists($store->logo_path)) {
                $path = 'store/logos/default-laundry.png';
                if (! Storage::disk('public')->put($path, file_get_contents(resource_path('images/store-logo-default.png')))) {
                    throw new \RuntimeException('Logo contoh gagal disimpan.');
                }
                $store->logo_path = $path;
            }
            $store->save();
        });
    }
}
