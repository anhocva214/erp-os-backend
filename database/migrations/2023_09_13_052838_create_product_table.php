<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('productThumbnailImage')->nullable();
            $table->unsignedBigInteger('productSubCategoryId')->nullable();
            $table->unsignedBigInteger('productBrandId')->nullable();
            $table->longText('description')->nullable();
            $table->string('sku')->unique();
            $table->integer('productQuantity')->nullable();
            $table->double('productSalePrice')->nullable();
            $table->double('productPurchasePrice')->nullable();
            $table->unsignedBigInteger('uomId')->nullable();
            $table->double('uomValue')->nullable();
            $table->integer('reorderQuantity')->nullable();
            $table->unsignedBigInteger('productVatId')->nullable();
            $table->unsignedBigInteger('discountId')->nullable();
            $table->string('status')->default('true');
            $table->timestamps();

            $table->index('name');

            $table->foreign('productSubCategoryId')->references('id')->on('productSubCategory');
            $table->foreign('productBrandId')->references('id')->on('productBrand');
            $table->foreign('discountId')->references('id')->on('discount');
            $table->foreign('productVatId')->references('id')->on('productVat');
            $table->foreign('uomId')->references('id')->on('uom');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product');
    }
};
