<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTestimoniesTable extends Migration {
  public function up() {
    Schema::create('testimonies', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->string('phone_number')->nullable();
      $table->json('categories')->nullable();
      $table->string('link')->nullable();
      $table->longText('description')->nullable();
      // Approval fields
      $table->foreignId('approved_by')->nullable()->constrained('users');
      $table->timestamp('approved_date')->nullable();
      $table->foreignId('org_id')->constrained("organizations");
      $table->timestamps();
    });
  }

  public function down() {
    Schema::dropIfExists('testimonies');
  }
}
