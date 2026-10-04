<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('number', 21)->unique();
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('type', 4);
            $table->string('platform', 10);
            $table->string('subject', 180);
            $table->text('description');
            $table->string('status', 20)->default('SUBMITTED')->index();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->text('result')->nullable();
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
            $table->index(['submitted_by', 'updated_at']);
        });
        Schema::create('ticket_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role', 20);
            $table->string('actor_name');
            $table->string('status', 20);
            $table->unsignedTinyInteger('progress');
            $table->text('message');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_updates');
        Schema::dropIfExists('support_tickets');
    }
};
