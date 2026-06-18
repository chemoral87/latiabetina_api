<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        DB::statement('ALTER TABLE church_events CHANGE COLUMN start_date publish_date DATE NOT NULL');
        DB::statement('ALTER TABLE church_events CHANGE COLUMN end_date event_date DATE NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        DB::statement('ALTER TABLE church_events CHANGE COLUMN publish_date start_date DATE NOT NULL');
        DB::statement('ALTER TABLE church_events CHANGE COLUMN event_date end_date DATE NULL');
    }
};
