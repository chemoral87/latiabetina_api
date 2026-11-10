<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('church_members', 'gender')) {
            Schema::table('church_members', function (Blueprint $table) {
                $table->string('gender')->nullable()->after('years_old');
            });
        }
    }

    public function down(): void
    {
        Schema::table('church_members', function (Blueprint $table) {
            $table->dropColumn('gender');
        });
    }
};
