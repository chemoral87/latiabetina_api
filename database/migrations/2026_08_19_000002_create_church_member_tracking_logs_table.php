<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('church_member_tracking_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_member_id')->constrained('church_members')->cascadeOnDelete();
            $table->date('contact_date');
            $table->string('medium'); // whatsapp, llamada, presencial, sms
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['church_member_id', 'contact_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('church_member_tracking_logs');
    }
};