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
        Schema::create('conso_sheets', function (Blueprint $table) {
            $table->id();
            $table->string('folio_number');
            $table->date('date');
            $table->string('how_did_you_hear')->nullable();
            $table->boolean('first_time_christian_church')->default(false);
            $table->text('comments')->nullable();
            $table->text('special_request')->nullable();
            $table->unsignedBigInteger('consolidator_id')->nullable();
            $table->foreign('consolidator_id')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conso_sheets');
    }
};
