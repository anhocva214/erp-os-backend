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
        Schema::create('returnPurchaseInvoiceProduct', function (Blueprint $table) {
            $table->id();
            $table->uuid('invoiceId');
            $table->unsignedBigInteger('purchaseInvoiceProductId');
            $table->unsignedBigInteger('productId')->nullable();
            $table->integer('productQuantity');
            $table->double('productUnitPurchasePrice');
            $table->double('productFinalAmount');
            $table->double('tax');
            $table->double('taxAmount');
            $table->timestamps();

            $table->foreign('invoiceId')->references('id')->on('returnPurchaseInvoice');
            $table->foreign('productId')->references('id')->on('product');
            $table->foreign('purchaseInvoiceProductId')->references('id')->on('purchaseInvoiceProduct');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('returnPurchaseInvoiceProduct');
    }
};
