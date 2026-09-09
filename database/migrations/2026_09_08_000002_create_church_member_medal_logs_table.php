<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('church_member_medal_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_member_id')->constrained('church_members')->cascadeOnDelete();
            $table->string('medal');
            $table->json('description')->nullable();
            $table->string('action'); // 'assigned' | 'removed'
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamps();

            $table->foreign('changed_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['church_member_id', 'medal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('church_member_medal_logs');
    }
};
