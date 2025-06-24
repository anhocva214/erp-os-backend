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
        Schema::create('designationHistory', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('userId');
            $table->unsignedBigInteger('designationId');
            $table->dateTime('startDate');
            $table->dateTime('endDate')->nullable();
            $table->string('comment')->nullable();
            $table->string('status')->default("true");
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('userId')->references('id')->on('users');
            $table->foreign('designationId')->references('id')->on('designation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('designationHistory');
    }
};
