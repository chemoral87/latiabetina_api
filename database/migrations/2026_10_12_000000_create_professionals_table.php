<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professionals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('last_name');
            $table->string('phone_number')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['unemployed', 'selfemployed', 'employed', 'student', 'retired'])->default('unemployed');
            $table->timestamps();

            $table->index(['title', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professionals');
    }
};
