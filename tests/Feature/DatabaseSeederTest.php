<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeated_seeding_keeps_transactions_empty_and_master_data_available(): void
    {
        Storage::fake('public');
        $this->seed();
        $this->seed();

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('transaction_items', 0);
        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseCount('services', 4);
        $this->assertDatabaseCount('store_settings', 1);
    }
}
