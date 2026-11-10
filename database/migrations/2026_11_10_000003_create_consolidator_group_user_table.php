<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consolidator_group_user', function (Blueprint $table) {
            $table->foreignId('consolidator_group_id')->constrained('consolidator_groups')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['consolidator_group_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consolidator_group_user');
    }
};
