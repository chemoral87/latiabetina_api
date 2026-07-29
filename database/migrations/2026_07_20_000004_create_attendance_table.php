<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('life_group_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('life_group_sessions')->onDelete('cascade');
            $table->foreignId('person_id')->constrained('life_group_people')->onDelete('cascade');
            $table->enum('type', ['member', 'new_guest', 'convert'])->default('member');
            $table->text('observations')->nullable();
            $table->timestamps();

            $table->unique(['session_id', 'person_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('life_group_attendances');
    }
};
