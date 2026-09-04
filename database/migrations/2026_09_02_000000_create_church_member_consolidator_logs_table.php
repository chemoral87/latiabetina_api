<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('church_member_consolidator_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_member_id')->constrained('church_members')->cascadeOnDelete();
            $table->foreignId('consolidator_id')->constrained('users')->cascadeOnDelete();
            $table->enum('action', ['assigned', 'unassigned']);
            $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('church_member_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('church_member_consolidator_logs');
    }
};
