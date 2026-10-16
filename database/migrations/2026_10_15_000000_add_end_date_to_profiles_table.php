<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up() {
    Schema::table('profiles', function (Blueprint $table) {
      $table->date('end_date')->nullable()->after('favorite');
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down() {
    Schema::table('profiles', function (Blueprint $table) {
      $table->dropColumn('end_date');
    });
  }
};
