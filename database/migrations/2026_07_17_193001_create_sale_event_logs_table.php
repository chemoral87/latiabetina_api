<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_event_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id');
            $table->string('number');
            $table->unsignedBigInteger('org_id');
            $table->string('customer_name')->nullable();
            $table->boolean('has_preparation_items')->default(false);
            $table->json('broadcast_data')->nullable();
            $table->timestamps();

            $table->index('sale_id');
            $table->index('org_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_event_logs');
    }
};
