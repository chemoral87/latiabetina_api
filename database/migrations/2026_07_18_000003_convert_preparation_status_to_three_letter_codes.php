<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('pos_sale_items')
            ->where('preparation_status', 'pending')
            ->update(['preparation_status' => 'PEN']);

        DB::table('pos_sale_items')
            ->where('preparation_status', 'ready')
            ->update(['preparation_status' => 'REA']);

        DB::table('pos_sale_items')
            ->where('preparation_status', 'completed')
            ->update(['preparation_status' => 'COM']);
    }

    public function down(): void
    {
        DB::table('pos_sale_items')
            ->where('preparation_status', 'PEN')
            ->update(['preparation_status' => 'pending']);

        DB::table('pos_sale_items')
            ->where('preparation_status', 'REA')
            ->update(['preparation_status' => 'ready']);

        DB::table('pos_sale_items')
            ->where('preparation_status', 'COM')
            ->update(['preparation_status' => 'completed']);
    }
};
