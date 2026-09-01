<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('songs', function (Blueprint $table) {
            // Drop foreign key if exists, then make nullable
            try {
                $table->dropForeign(['org_id']);
            } catch (\Throwable $e) {
                // ignore if foreign key does not exist or different name
            }
            $table->unsignedBigInteger('org_id')->nullable()->change();
            $table->foreign('org_id')->references('id')->on('organizations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('songs', function (Blueprint $table) {
            try {
                $table->dropForeign(['org_id']);
            } catch (\Throwable $e) {
            }
            // Make non-nullable again (requires a default value for existing nulls)
            // Set null org_ids to first organization if exists
            $firstOrgId = \Illuminate\Support\Facades\DB::table('organizations')->value('id');
            if ($firstOrgId) {
                \Illuminate\Support\Facades\DB::table('songs')->whereNull('org_id')->update(['org_id' => $firstOrgId]);
            }
            $table->unsignedBigInteger('org_id')->nullable(false)->change();
            $table->foreign('org_id')->references('id')->on('organizations')->cascadeOnDelete();
        });
    }
};
