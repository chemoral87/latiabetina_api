<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('auditorium_event_seats_log', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('auditorium_event_id');
      $table->json('seat_ids'); // stores array like ["1-1-1-1", "1-1-1-2"]
      $table->string('status'); // available, reserved, occupied, blocked

      $table->unsignedBigInteger('created_by')->nullable();
      $table->timestamps();

      $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
    });
  }

  public function down(): void {
    Schema::dropIfExists('auditorium_event_seats_log');
  }
};
