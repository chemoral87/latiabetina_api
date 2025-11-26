<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    // Add team_id to roles table if not exists
    if (!Schema::hasColumn('roles', 'team_id')) {
      Schema::table('roles', function (Blueprint $table) {
        $table->unsignedBigInteger('team_id')->nullable()->default(null)->after('id')->index('roles_team_id_index');
      });
    }
    // Add team_id to model_has_roles table if not exists
    if (!Schema::hasColumn('model_has_roles', 'team_id')) {
      Schema::table('model_has_roles', function (Blueprint $table) {
        $table->unsignedBigInteger('team_id')->nullable()->default(null)->after('model_id')->index('model_has_roles_team_id_index');
      });
    }
    // Add team_id to model_has_permissions table if not exists
    if (!Schema::hasColumn('model_has_permissions', 'team_id')) {
      Schema::table('model_has_permissions', function (Blueprint $table) {
        $table->unsignedBigInteger('team_id')->nullable()->default(null)->after('model_id')->index('model_has_permissions_team_id_index');
      });
    }
  }

  public function down(): void {
    // Remove team_id columns
    if (Schema::hasColumn('roles', 'team_id')) {
      Schema::table('roles', function (Blueprint $table) {
        $table->dropColumn('team_id');
      });
    }
    if (Schema::hasColumn('model_has_roles', 'team_id')) {
      Schema::table('model_has_roles', function (Blueprint $table) {
        $table->dropColumn('team_id');
      });
    }
    if (Schema::hasColumn('model_has_permissions', 'team_id')) {
      Schema::table('model_has_permissions', function (Blueprint $table) {
        $table->dropColumn('team_id');
      });
    }
  }
};
