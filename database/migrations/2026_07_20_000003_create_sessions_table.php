<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('life_group_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('life_group_id')->constrained('life_groups')->onDelete('cascade');
            $table->integer('week_number'); // 1-8
            $table->date('date');
            $table->time('start_time')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['scheduled', 'completed', 'cancelled', 'rescheduled'])->default('scheduled');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['life_group_id', 'week_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('life_group_sessions');
    }
};
