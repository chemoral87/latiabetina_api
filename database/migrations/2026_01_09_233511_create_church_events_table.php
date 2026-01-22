<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void {
    Schema::create('church_events', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->string('slug_name')->unique();
      $table->string('location')->nullable();
      $table->text('description')->nullable();
      $table->date('start_date');
      $table->date('end_date')->nullable();
      $table->time('time_start')->nullable();
      $table->string('url_image')->nullable();
      $table->unsignedBigInteger('org_id');
      $table->unsignedBigInteger('created_by');
      $table->timestamps();

      $table->foreign('org_id')->references('id')->on('organizations')->onDelete('cascade');
      $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void {
    Schema::dropIfExists('church_events');
  }
};
