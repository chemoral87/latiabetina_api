<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_sale_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_price', 12, 2);
            $table->timestamps();

            $table->foreign('sale_id')->references('id')->on('pos_sales')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('pos_products')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_sale_items');
    }
};
