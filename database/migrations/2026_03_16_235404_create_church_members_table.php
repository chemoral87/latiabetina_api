<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('church_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conso_sheet_id')->constrained('conso_sheets')->cascadeOnDelete();
            $table->string('name');
            $table->string('last_name')->nullable();
            $table->string('second_last_name')->nullable();
            $table->integer('years_old')->nullable();
            $table->string('cellphone')->nullable();
            $table->string('address')->nullable();
            $table->string('marriage_status')->nullable();
            $table->integer("number_of_children")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('church_members');
    }
};
