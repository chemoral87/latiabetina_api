<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTestimoniesStatusColumns extends Migration {
  public function up() {
    Schema::table('testimonies', function (Blueprint $table) {
      // Drop old foreign key and columns
      $table->dropForeign(['approved_by']);
      $table->dropColumn('approved_by');
      $table->dropColumn('approved_date');

      // Add new status fields
      $table->foreignId('status_by')->nullable()->constrained('users');
      $table->string('status')->nullable();
    });
  }

  public function down() {
    Schema::table('testimonies', function (Blueprint $table) {
      // Drop new columns
      $table->dropForeign(['status_by']);
      $table->dropColumn('status_by');
      $table->dropColumn('status');

      // Recreate old approval fields
      $table->foreignId('approved_by')->nullable()->constrained('users');
      $table->timestamp('approved_date')->nullable();
    });
  }
}
