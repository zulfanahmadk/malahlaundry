<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 30)->unique();
            $table->string('store_name', 100)->default('Malah Laundry');
            $table->string('phone', 40)->nullable();
            $table->text('address')->nullable();
            $table->mediumText('logo_data')->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('show_branch')->default(true);
            $table->json('opening_hours')->nullable();
            $table->json('templates')->nullable();
            $table->text('receipt_terms')->nullable();
            $table->unsignedSmallInteger('complaint_days')->default(3);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('radius_meters')->default(200);
            $table->timestamps();
        });
        $store = DB::table('store_settings')->where('id', 1)->first();
        DB::table('branches')->insert([
            'id' => 1, 'name' => 'Utama', 'code' => 'ML-001',
            'store_name' => $store?->name ?? 'Malah Laundry',
            'phone' => $store?->phone, 'address' => $store?->address,
            'logo_data' => $store?->logo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($store->logo_path)
                ? base64_encode(\Illuminate\Support\Facades\Storage::disk('public')->get($store->logo_path)) : null,
            'receipt_terms' => $store?->receipt_terms,
            'templates' => json_encode(DB::table('whatsapp_templates')->pluck('content', 'type')->all()),
            'opening_hours' => json_encode(array_map(fn ($day) => ['day' => $day, 'open' => $day < 7, 'from' => '08:00', 'to' => '18:00'], range(1, 7))),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach (['users', 'customers', 'services', 'transactions', 'attendances'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->foreignId('branch_id')->default(1)->constrained('branches')->restrictOnDelete();
            });
        }
        Schema::table('customers', function (Blueprint $table) {
            $table->string('notes', 100)->nullable();
            $table->timestamp('archived_at')->nullable()->index();
        });
        Schema::table('services', function (Blueprint $table) {
            $table->string('speed', 10)->default('REGULER');
            $table->unsignedInteger('duration_hours')->default(48);
        });
        Schema::table('transactions', function (Blueprint $table) {
            $table->timestamp('estimated_at')->nullable();
            $table->timestamp('ready_at')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method', 10)->nullable();
            $table->unsignedBigInteger('version')->default(1);
            $table->index(['branch_id', 'laundry_status', 'created_at']);
        });
        Schema::table('attendances', function (Blueprint $table) {
            foreach (['in', 'out'] as $phase) {
                $table->decimal($phase.'_latitude', 10, 7)->nullable();
                $table->decimal($phase.'_longitude', 10, 7)->nullable();
                $table->unsignedInteger($phase.'_accuracy')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendances', fn (Blueprint $t) => $t->dropColumn(['in_latitude', 'in_longitude', 'in_accuracy', 'out_latitude', 'out_longitude', 'out_accuracy']));
        Schema::table('transactions', function (Blueprint $t) {
            $t->dropIndex(['branch_id', 'laundry_status', 'created_at']);
            $t->dropColumn(['estimated_at', 'ready_at', 'paid_at', 'payment_method', 'version']);
        });
        Schema::table('services', fn (Blueprint $t) => $t->dropColumn(['speed', 'duration_hours']));
        Schema::table('customers', fn (Blueprint $t) => $t->dropColumn(['notes', 'archived_at']));
        foreach (['users', 'customers', 'services', 'transactions', 'attendances'] as $name) {
            Schema::table($name, fn (Blueprint $t) => $t->dropConstrainedForeignId('branch_id'));
        }
        Schema::dropIfExists('branches');
    }
};
