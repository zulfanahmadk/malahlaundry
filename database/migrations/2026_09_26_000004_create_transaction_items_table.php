<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('transaction_uuid')->index();
            $table->uuid('service_uuid')->index();
            $table->decimal('qty', 8, 2)->default(1);
            $table->unsignedBigInteger('price')->default(0);
            $table->string('service_name')->nullable();
            $table->string('unit', 10)->nullable();
            $table->timestamps();

            $table->foreign('transaction_uuid')->references('uuid')->on('transactions')->cascadeOnDelete();
            $table->foreign('service_uuid')->references('uuid')->on('services')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_items');
    }
};
