<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('admin_ticket_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->unique(['user_id', 'support_ticket_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_ticket_reads');
    }
};
