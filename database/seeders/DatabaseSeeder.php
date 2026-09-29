<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\TransactionItem;
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
        $owner = User::firstOrCreate(
            ['username' => 'owner'],
            [
                'name' => 'Owner Malah Laundry',
                'email' => 'owner@malahlaundry.com',
                'password' => Hash::make('password123'),
                'role' => 'owner',
                'active' => true,
            ]
        );

        $cashier = User::firstOrCreate(
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
        $service1 = Service::firstOrCreate(
            ['name' => 'Cuci Komplit Reguler'],
            [
                'uuid' => (string) Str::uuid(),
                'unit' => 'kg',
                'price' => 7000,
                'is_active' => true,
            ]
        );

        $service2 = Service::firstOrCreate(
            ['name' => 'Cuci Kering'],
            [
                'uuid' => (string) Str::uuid(),
                'unit' => 'kg',
                'price' => 5000,
                'is_active' => true,
            ]
        );

        $service3 = Service::firstOrCreate(
            ['name' => 'Setrika Saja'],
            [
                'uuid' => (string) Str::uuid(),
                'unit' => 'kg',
                'price' => 4000,
                'is_active' => true,
            ]
        );

        $service4 = Service::firstOrCreate(
            ['name' => 'Bed Cover Besar'],
            [
                'uuid' => (string) Str::uuid(),
                'unit' => 'pcs',
                'price' => 25000,
                'is_active' => true,
            ]
        );

        // 4. Sample Customer & Transaction for Demonstration
        $customer = Customer::firstOrCreate(
            ['phone' => '081234567890'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Budi Santoso',
                'address' => 'Jl. Pemuda No. 45, Jakarta',
            ]
        );

        $sampleTrxUuid = 'e1a2b3c4-d5e6-4f7a-8b9c-0d1e2f3a4b5c';
        $trx = Transaction::firstOrCreate(
            ['uuid' => $sampleTrxUuid],
            [
                'customer_uuid' => $customer->uuid,
                'user_id' => $cashier->id,
                'transaction_number' => 'TRX-'.now()->format('Ymd').'-001',
                'subtotal' => 21000,
                'total' => 21000,
                'payment_status' => 'LUNAS',
                'laundry_status' => 'DITERIMA',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        TransactionItem::firstOrCreate(
            [
                'transaction_uuid' => $trx->uuid,
                'service_uuid' => $service1->uuid,
            ],
            [
                'qty' => 3.0,
                'price' => 7000,
            ]
        );
    }
}
