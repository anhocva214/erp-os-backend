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
        Schema::create('returnSaleInvoice', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->dateTime('date');
            $table->double('totalAmount');
            $table->double('instantReturnAmount')->default(0);
            $table->double('tax')->nullable();
            $table->string('note')->nullable();
            $table->uuid('saleInvoiceId');
            $table->string('invoiceMemoNo')->nullable();
            $table->string('status')->default('true');
            $table->timestamps();

            $table->foreign('saleInvoiceId')->references('id')->on('saleInvoice');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('returnSaleInvoice');
    }
};
