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
        Schema::create('purchaseInvoice', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->dateTime('date');
            $table->double('totalAmount');
            $table->double('totalTax')->nullable();
            $table->double('paidAmount');
            $table->double('dueAmount');
            $table->unsignedBigInteger('supplierId');
            $table->string('note')->nullable();
            $table->string('supplierMemoNo')->nullable();
            $table->string('invoiceMemoNo')->nullable();
            $table->timestamps();

            $table->foreign('supplierId')->references('id')->on('supplier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchaseInvoice');
    }
};
