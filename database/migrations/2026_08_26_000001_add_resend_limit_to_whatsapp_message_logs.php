<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_message_logs', function (Blueprint $table) {
            $table->unsignedInteger('resend_count')->default(0)->after('error_message');
            $table->foreignId('original_log_id')->nullable()->after('resend_count')->constrained('whatsapp_message_logs')->nullOnDelete();
            $table->index('original_log_id');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_message_logs', function (Blueprint $table) {
            $table->dropColumn(['resend_count', 'original_log_id']);
        });
    }
};
