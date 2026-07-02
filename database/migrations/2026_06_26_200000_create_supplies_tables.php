<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('product_supplies');
        Schema::dropIfExists('supplies');

        Schema::create('supplies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->decimal('stock', 12, 2)->default(0);
            $table->string('unit')->nullable();
            $table->timestamps();
        });

        Schema::create('product_supplies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('product_id');
            $table->unsignedBigInteger('supply_id');
            $table->decimal('quantity', 12, 2)->default(1);
            $table->unique(['product_id', 'supply_id']);

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('supply_id')->references('id')->on('supplies')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_supplies');
        Schema::dropIfExists('supplies');
    }
};
