<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('auditorium_events', function (Blueprint $table) {
            $table->string('time', 5)->nullable()->after('event_date')->comment('HH:MM format');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auditorium_events', function (Blueprint $table) {
            $table->dropColumn('time');
        });
    }
};
