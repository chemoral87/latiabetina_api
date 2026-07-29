<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('life_group_people', function (Blueprint $table) {
            $table->id();
            $table->foreignId('life_group_id')->nullable()->constrained('life_groups')->nullOnDelete();
            $table->string('name');
            $table->string('last_name')->nullable();
            $table->integer('age')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('photo')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Prevent duplicate people by name + last_name + phone
            $table->unique(['name', 'last_name', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('life_group_people');
    }
};
