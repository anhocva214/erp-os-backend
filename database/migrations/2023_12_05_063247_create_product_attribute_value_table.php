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
        Schema::create('productAttributeValue', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('productAttributeId')->nullable();
            $table->string('name')->nullable();
            $table->string('status')->default('true');
            $table->timestamps();

            $table->foreign('productAttributeId')->references('id')->on('productAttribute')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productAttributeValue');
    }
};
