<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->timestamp('picked_up_at')->nullable()->index();
        });
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->string('service_name')->nullable();
            $table->string('unit', 10)->nullable();
        });
        // Existing in-progress laundry remains received; no fabricated pickup timestamps.
        DB::table('transactions')->where('laundry_status', 'DIPROSES')->update(['laundry_status' => 'DITERIMA']);
        DB::table('transaction_items')->orderBy('id')->chunkById(500, function ($items) {
            $services = DB::table('services')->whereIn('uuid', $items->pluck('service_uuid'))->get()->keyBy('uuid');
            foreach ($items as $item) {
                $service = $services->get($item->service_uuid);
                if ($service) {
                    DB::table('transaction_items')->where('id', $item->id)->update(['service_name' => $service->name, 'unit' => $service->unit]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', fn (Blueprint $table) => $table->dropColumn('picked_up_at'));
        Schema::table('transaction_items', fn (Blueprint $table) => $table->dropColumn(['service_name', 'unit']));
    }
};
