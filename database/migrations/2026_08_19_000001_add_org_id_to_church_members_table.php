<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The org_id column was added directly to the original create migration,
     * but that migration had already run against existing databases. This
     * migration actually adds the column and backfills it from the member's
     * conso_sheet organization.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('church_members', 'org_id')) {
            Schema::table('church_members', function (Blueprint $table) {
                $table->unsignedBigInteger('org_id')->nullable()->after('id');
            });
        }

        DB::table('church_members as cm')
            ->join('conso_sheets as cs', 'cs.id', '=', 'cm.conso_sheet_id')
            ->whereNull('cm.org_id')
            ->update(['cm.org_id' => DB::raw('cs.org_id')]);

        if (!Schema::hasColumn('church_members', 'org_id') || !$this->hasForeign('church_members', 'org_id')) {
            Schema::table('church_members', function (Blueprint $table) {
                $table->foreign('org_id')->references('id')->on('organizations')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('church_members', function (Blueprint $table) {
            $table->dropForeign(['org_id']);
            $table->dropColumn('org_id');
        });
    }

    private function hasForeign(string $table, string $column): bool
    {
        $fks = collect(DB::select('SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?', [$table, $column]))
            ->pluck('CONSTRAINT_NAME')
            ->map(fn ($name) => strtolower((string) $name));

        return $fks->contains(fn ($name) => str_contains($name, 'org_id'));
    }
};