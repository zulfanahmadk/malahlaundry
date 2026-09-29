<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\User;
use App\Models\WhatsAppTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(StoreSettingsSeeder::class);
        // 1. Users (Owner & Kasir)
        User::firstOrCreate(
            ['username' => 'owner'],
            [
                'name' => 'Owner Malah Laundry',
                'email' => 'owner@malahlaundry.com',
                'password' => Hash::make('password123'),
                'role' => 'owner',
                'active' => true,
            ]
        );

        User::firstOrCreate(
            ['username' => 'kasir1'],
            [
                'name' => 'Kasir Malah Laundry',
                'email' => 'kasir1@malahlaundry.com',
                'password' => Hash::make('password123'),
                'role' => 'cashier',
                'active' => true,
            ]
        );

        // 2. WhatsApp Templates
        WhatsAppTemplate::firstOrCreate(
            ['type' => 'WA_DITERIMA'],
            [
                'content' => "Halo Kak {nama}, cucian Anda di *Malah Laundry* telah kami terima dengan nomor {no_transaksi}.\nTotal tagihan: Rp{total} ({status_bayar}).\n\nCek rincian nota digital Anda di sini:\n{url_nota}\n\nTerima kasih telah mempercayakan pakaian Anda kepada kami!",
            ]
        );

        WhatsAppTemplate::firstOrCreate(
            ['type' => 'WA_SIAP_DIAMBIL'],
            [
                'content' => "Halo Kak {nama}, cucian Anda ({no_transaksi}) di *Malah Laundry* sudah SELESAI dan SIAP DIAMBIL.\n\nSisa tagihan: Rp{sisa_bayar}.\nSilakan tunjukkan nota digital saat pengambilan:\n{url_nota}\n\nTerima kasih!",
            ]
        );

        WhatsAppTemplate::firstOrCreate(
            ['type' => 'WA_SELESAI'],
            [
                'content' => 'Halo Kak {nama}, cucian ({no_transaksi}) telah selesai diambil. Terima kasih banyak telah menggunakan jasa *Malah Laundry*! Semoga pakaian Anda selalu bersih dan wangi.',
            ]
        );

        // 3. Master Services
        Service::firstOrCreate(
            ['name' => 'Cuci Komplit Reguler'],
            [
                'uuid' => (string) Str::uuid(),
                'unit' => 'kg',
                'price' => 7000,
                'is_active' => true,
            ]
        );

        Service::firstOrCreate(
            ['name' => 'Cuci Kering'],
            [
                'uuid' => (string) Str::uuid(),
                'unit' => 'kg',
                'price' => 5000,
                'is_active' => true,
            ]
        );

        Service::firstOrCreate(
            ['name' => 'Setrika Saja'],
            [
                'uuid' => (string) Str::uuid(),
                'unit' => 'kg',
                'price' => 4000,
                'is_active' => true,
            ]
        );

        Service::firstOrCreate(
            ['name' => 'Bed Cover Besar'],
            [
                'uuid' => (string) Str::uuid(),
                'unit' => 'pcs',
                'price' => 25000,
                'is_active' => true,
            ]
        );

        // Data transaksi dan pelanggan diisi melalui aplikasi, tanpa data demo.
    }
}
