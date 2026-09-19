<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('assistances', function (Blueprint $table) {
            $table->unsignedInteger('newcomers')->default(0)->after('babies');
        });
    }

    public function down(): void
    {
        Schema::table('assistances', function (Blueprint $table) {
            $table->dropColumn('newcomers');
        });
    }
};
