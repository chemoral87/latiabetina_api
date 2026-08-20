<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('church_members', 'status')) {
            Schema::table('church_members', function (Blueprint $table) {
                // ACTIVO, NO CONTESTA, NO MOLESTAR
                $table->string('status')->default('ACTIVO')->after('number_of_children');
            });
        }
    }

    public function down(): void
    {
        Schema::table('church_members', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};