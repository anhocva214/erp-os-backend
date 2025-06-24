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
        Schema::create('returnPurchaseInvoice', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->dateTime('date');
            $table->double('totalAmount');
            $table->double('instantReturnAmount')->default(0);
            $table->double('tax')->nullable();
            $table->string('note')->nullable();
            $table->uuid('purchaseInvoiceId');
            $table->string('invoiceMemoNo')->nullable();
            $table->string('status')->default('true');
            $table->timestamps();

            $table->foreign('purchaseInvoiceId')->references('id')->on('purchaseInvoice');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('returnPurchaseInvoice');
    }
};
