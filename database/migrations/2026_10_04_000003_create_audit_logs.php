<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('occurred_at')->index();
            // IDs are snapshots: deleting a user must not erase the audit trail.
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->string('actor_role', 20)->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('feature', 40)->index();
            $table->string('action', 255);
            $table->string('method', 10);
            $table->string('channel', 10);
            $table->unsignedSmallInteger('status');
            $table->string('outcome', 10)->index();
            $table->uuid('request_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
