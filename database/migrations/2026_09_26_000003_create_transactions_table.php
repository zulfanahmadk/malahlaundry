<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->uuid('customer_uuid')->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('transaction_number', 50)->unique();
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('total')->default(0);
            $table->enum('payment_status', ['BELUM', 'LUNAS'])->default('BELUM');
            $table->enum('laundry_status', ['DITERIMA', 'SIAP_DIAMBIL', 'SELESAI'])->default('DITERIMA');
            $table->timestamp('picked_up_at')->nullable()->index();
            $table->timestamps();

            $table->foreign('customer_uuid')->references('uuid')->on('customers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
