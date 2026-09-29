<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function createTestTransaction(): \App\Models\Transaction
    {
        $customer = \App\Models\Customer::create(['name' => 'Budi Santoso', 'phone' => '081234567890']);
        $service = \App\Models\Service::where('name', 'Cuci Komplit Reguler')->firstOrFail();
        $transaction = \App\Models\Transaction::create([
            'customer_uuid' => $customer->uuid,
            'subtotal' => 21000,
            'total' => 21000,
            'laundry_status' => 'DITERIMA',
            'payment_status' => 'BELUM',
        ]);
        $transaction->items()->create([
            'service_uuid' => $service->uuid,
            'service_name' => $service->name,
            'unit' => $service->unit,
            'qty' => 3,
            'price' => 7000,
        ]);

        return $transaction;
    }
}
