<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('auditoriums', function (Blueprint $table) {
      $table->unsignedTinyInteger('layout_version')->default(1)->after('config');
    });
  }

  public function down(): void {
    Schema::table('auditoriums', function (Blueprint $table) {
      $table->dropColumn('layout_version');
    });
  }
};
