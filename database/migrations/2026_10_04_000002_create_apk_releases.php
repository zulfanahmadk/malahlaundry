<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->string('role', 20)->default('cashier')->change());
        Schema::create('apk_releases', function (Blueprint $table) {
            $table->id();
            $table->string('package_name');
            $table->unsignedBigInteger('version_code');
            $table->string('version_name', 100);
            $table->string('path');
            $table->unsignedBigInteger('size');
            $table->char('sha256', 64);
            $table->text('notes')->nullable();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();
            $table->unique(['package_name', 'version_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apk_releases');
        // Keep the expanded role column so existing admin accounts survive rollback.
    }
};
