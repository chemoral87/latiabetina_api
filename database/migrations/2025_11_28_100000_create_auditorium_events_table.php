<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('auditorium_events', function (Blueprint $table) {
      $table->id();
      $table->date('event_date');
      $table->text('config');
      $table->unsignedBigInteger('auditorium_id');
      $table->unsignedBigInteger('org_id');
      $table->timestamps();
    });
  }

  public function down(): void {
    Schema::dropIfExists('auditorium_events');
  }
};
