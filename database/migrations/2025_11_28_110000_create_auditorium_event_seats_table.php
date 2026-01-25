<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('auditorium_event_seats', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('auditorium_event_id');
      $table->string('seat_id');
      $table->string('status')->nullable(); // available, reserved, occupied, blocked
      $table->unsignedBigInteger('created_by')->nullable();
      $table->timestamps();

      $table->foreign('auditorium_event_id')->references('id')->on('auditorium_events')->onDelete('cascade');
      $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

      $table->unique(['auditorium_event_id', 'seat_number']);
    });
  }

  public function down(): void {
    Schema::dropIfExists('auditorium_event_seats');
  }
};
