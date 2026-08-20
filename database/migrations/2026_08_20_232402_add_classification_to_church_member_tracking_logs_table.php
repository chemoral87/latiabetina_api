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
        Schema::table('church_member_tracking_logs', function (Blueprint $table) {
            $table->string('classification')->nullable()->after('medium');
        });
    }

    public function down(): void
    {
        Schema::table('church_member_tracking_logs', function (Blueprint $table) {
            $table->dropColumn('classification');
        });
    }
};
