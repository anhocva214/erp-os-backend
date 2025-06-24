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
        Schema::create('awardHistory', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('userId');
            $table->unsignedBigInteger('awardId');
            $table->dateTime('awardedDate');
            $table->string('comment')->nullable();
            $table->string('status')->default("true");
            $table->timestamps();

            $table->foreign('userId')->references('id')->on('users');
            $table->foreign('awardId')->references('id')->on('award');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('awardHistory');
    }
};
