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
        Schema::create('paymentMethod', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subAccountId');
            $table->string('methodName');
            $table->string('logo')->nullable();
            $table->string('ownerAccount')->nullable();
            $table->longText('instruction')->nullable();
            $table->string('isActive')->default('true');
            $table->string('status')->default('true');
            $table->timestamps();

            $table->foreign('subAccountId')->references('id')->on('subAccount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paymentMethod');
    }
};
