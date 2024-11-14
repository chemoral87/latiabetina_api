<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void {
    Schema::create('expense_tickets', function (Blueprint $table) {
      $table->id();
      $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');
      $table->date('date');
      $table->decimal('total', 10, 2);
      $table->string('description')->nullable();
      $table->foreignId('created_by')->constrained("users")->onDelete('cascade');
      $table->foreignId('updated_by')->constrained("users")->onDelete('cascade');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void {
    Schema::dropIfExists('expense_tickets');
  }
};
