<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assistances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('org_id');
            $table->date('assistance_date');
            $table->time('service_time');
            $table->unsignedInteger('adults')->default(0);
            $table->unsignedInteger('teens')->default(0);
            $table->unsignedInteger('kids')->default(0);
            $table->unsignedInteger('babies')->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('org_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            $table->unique(['org_id', 'assistance_date', 'service_time'], 'assistances_org_date_service_unique');
            $table->index(['org_id', 'assistance_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistances');
    }
};
