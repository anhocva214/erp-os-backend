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
        Schema::create('salaryHistory', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('userId');
            $table->float('salary');
            $table->dateTime('startDate');
            $table->dateTime('endDate')->nullable();
            $table->string('comment')->nullable();
            $table->string('status')->default("true");
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('userId')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salaryHistory');
    }
};
