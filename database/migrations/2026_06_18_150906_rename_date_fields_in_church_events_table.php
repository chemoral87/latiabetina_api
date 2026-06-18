<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('church_events', function (Blueprint $table) {
            $table->renameColumn('start_date', 'publish_date');
            $table->renameColumn('end_date', 'event_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('church_events', function (Blueprint $table) {
            $table->renameColumn('publish_date', 'start_date');
            $table->renameColumn('event_date', 'end_date');
        });
    }
};
