<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('productProductAttributeValue', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('productId')->nullable();
            $table->unsignedBigInteger('productAttributeValueId')->nullable();
            $table->string('status')->default('true');
            $table->timestamps();

            $table->foreign('productId')->references('id')->on('product')->onDelete('cascade');
            $table->foreign('productAttributeValueId')->references('id')->on('productAttributeValue')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productProductAttributeValue');
    }
};
