<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->json('receipt_preferences')->nullable();
            $table->json('opening_exceptions')->nullable();
            $table->json('operational_preferences')->nullable();
            $table->json('message_preferences')->nullable();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 40)->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->boolean('notify_login')->default(true);
        });
        Schema::table('services', fn (Blueprint $table) => $table->string('unit', 10)->default('kg')->change());
        Schema::create('device_sync_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('device_id');
            $table->string('name', 120);
            $table->string('app_version', 40);
            $table->unsignedInteger('pending_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->string('last_error', 1000)->nullable();
            $table->timestamp('last_seen_at');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->unique(['branch_id', 'device_id']);
        });
        Schema::create('owner_notification_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('event_key', 120);
            $table->timestamps();
            $table->unique(['user_id', 'branch_id', 'event_key'], 'owner_notification_event_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_notification_reads');
        Schema::dropIfExists('device_sync_states');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['phone', 'last_login_at', 'notify_login']));
        Schema::table('branches', fn (Blueprint $table) => $table->dropColumn(['receipt_preferences', 'opening_exceptions', 'operational_preferences', 'message_preferences']));
    }
};
