<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('telegram_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('chat_id', 32)->nullable();
            $table->boolean('enabled')->default(false);
            $table->boolean('notify_created')->default(true);
            $table->boolean('notify_picked_up')->default(true);
            $table->timestamps();
            $table->unique(['user_id', 'branch_id']);
        });

        Schema::create('telegram_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('telegram_subscriptions')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->uuid('transaction_uuid')->nullable();
            $table->string('event', 24);
            $table->string('chat_id', 32);
            $table->text('text');
            $table->string('transaction_url', 500)->nullable();
            $table->string('status', 16)->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('available_at');
            $table->timestamp('queued_at')->nullable();
            $table->unsignedBigInteger('queue_job_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->bigInteger('telegram_message_id')->nullable();
            $table->string('last_error', 500)->nullable();
            $table->timestamps();
            $table->unique(['transaction_uuid', 'event', 'chat_id'], 'telegram_event_recipient_unique');
            $table->index(['status', 'available_at']);
            $table->index(['subscription_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_deliveries');
        Schema::dropIfExists('telegram_subscriptions');
    }
};
