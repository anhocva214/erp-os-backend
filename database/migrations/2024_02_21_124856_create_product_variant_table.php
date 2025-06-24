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
        Schema::create('productVariant', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manufacturerId');
            $table->unsignedBigInteger('productBrandId');
            $table->unsignedBigInteger('subCategoryId');
            $table->unsignedBigInteger('purchaseTaxId');
            $table->unsignedBigInteger('salesTaxId');
            $table->unsignedBigInteger('uomId');
            $table->string('status')->default('true');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productVariant');
    }
};
