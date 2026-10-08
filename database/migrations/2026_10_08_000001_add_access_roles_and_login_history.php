<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('access_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('base_role', 20);
            $table->json('permissions');
            $table->timestamps();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('access_role_id')->nullable()->constrained('access_roles')->restrictOnDelete();
        });
        Schema::create('user_logins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 10);
            $table->string('ip_address', 45)->nullable();
            $table->string('device', 255)->nullable();
            $table->text('user_agent')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamp('location_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['user_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_logins');
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('access_role_id'));
        Schema::dropIfExists('access_roles');
    }
};
