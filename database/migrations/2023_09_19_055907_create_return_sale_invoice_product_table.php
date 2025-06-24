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
        Schema::create('returnSaleInvoiceProduct', function (Blueprint $table) {
            $table->id();
            $table->uuid('invoiceId');
            $table->unsignedBigInteger('productId')->nullable();
            $table->unsignedBigInteger('saleInvoiceProductId')->nullable();
            $table->integer('productQuantity');
            $table->double('productUnitSalePrice');
            $table->double('productFinalAmount');
            $table->double('tax');
            $table->double('taxAmount');
            $table->timestamps();

            // foreign key relation constraints
            $table->foreign('invoiceId')->references('id')->on('returnSaleInvoice')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('productId')->references('id')->on('product');
            $table->foreign('saleInvoiceProductId')->references('id')->on('saleInvoiceProduct');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('returnSaleInvoiceProduct');
    }
};
