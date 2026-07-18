<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_sale_items', function (Blueprint $table) {
            // null  → product does not require preparation
            // pending → item is queued in the KDS, not yet ready
            // ready   → kitchen has marked this item as done
            $table->string('preparation_status')
                ->nullable()
                ->after('total_price')
                ->comment('null = no preparation needed | pending = in KDS queue | ready = kitchen done');
        });
    }

    public function down(): void
    {
        Schema::table('pos_sale_items', function (Blueprint $table) {
            $table->dropColumn('preparation_status');
        });
    }
};
