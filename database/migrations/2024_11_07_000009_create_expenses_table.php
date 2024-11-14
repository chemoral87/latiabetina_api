<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void {
    Schema::create('expenses', function (Blueprint $table) {
      $table->id();
      $table->foreignId('concept_id')->constrained("expense_concepts")->onDelete('cascade');
      $table->foreignId('ticket_id')->nullable()->constrained("expense_tickets")->onDelete('cascade');
      $table->string('unit');
      $table->decimal('quantity', 9, 2);
      $table->decimal('amount', 9, 2);
      $table->decimal('total', 10, 2);
      $table->date('date');
      $table->foreignId('created_by')->constrained("users")->onDelete('cascade');
      $table->foreignId('updated_by')->constrained("users")->onDelete('cascade');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void {
    Schema::dropIfExists('expenses');
  }
};
