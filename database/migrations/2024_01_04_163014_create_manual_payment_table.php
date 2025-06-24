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
        Schema::create('manualPayment', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("paymentMethodId");
            $table->unsignedBigInteger("customerId");
            $table->string("amount");
            $table->string("manualTransactionId")->unique()->nullable();
            $table->string("CustomerAccount")->nullable();
            $table->string("CustomerTransactionId")->nullable();
            $table->enum("isVerified",['Accept','Reject','Pending'])->default("Pending");
            $table->string("status")->default("true");
            $table->timestamps();

            $table->foreign("customerId")->references("id")->on("customer");
            $table->foreign("paymentMethodId")->references("id")->on("paymentMethod");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manualPayment');
    }
};
