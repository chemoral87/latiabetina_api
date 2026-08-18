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
        Schema::create('church_member_consolidator', function (Blueprint $table) {
            $table->foreignId('church_member_id')->constrained('church_members')->cascadeOnDelete();
            $table->foreignId('consolidator_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['church_member_id', 'consolidator_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('church_member_consolidator');
    }
};