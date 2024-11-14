<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void {
    Schema::create('expense_ticket_images', function (Blueprint $table) {
      $table->id();
      $table->foreignId('expense_ticket_id')->constrained()->onDelete('cascade');
      $table->string('image_path'); 
      $table->string('description')->nullable(); 

      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void {
    Schema::dropIfExists('expense_ticket_images');
  }
};
