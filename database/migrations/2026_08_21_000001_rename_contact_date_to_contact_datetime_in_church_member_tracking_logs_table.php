<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE church_member_tracking_logs CHANGE contact_date contact_datetime DATETIME NOT NULL');

        Schema::table('church_member_tracking_logs', function (Blueprint $table) {
            $table->index(['church_member_id', 'contact_datetime'], 'track_log_mem_dt_idx');
        });
    }

    public function down(): void
    {
        Schema::table('church_member_tracking_logs', function (Blueprint $table) {
            $table->dropIndex('track_log_mem_dt_idx');
        });

        DB::statement('ALTER TABLE church_member_tracking_logs CHANGE contact_datetime contact_date DATE NOT NULL');

        Schema::table('church_member_tracking_logs', function (Blueprint $table) {
            $table->index(['church_member_id', 'contact_date']);
        });
    }
};
