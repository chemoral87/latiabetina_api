<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('life_group_leaders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('life_group_id')->constrained('life_groups')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['life_group_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('life_group_leaders');
    }
};
