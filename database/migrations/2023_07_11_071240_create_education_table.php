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
        Schema::create('education', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('userId');
            $table->string('degree');
            $table->string('institution');
            $table->string('fieldOfStudy');
            $table->string('result');
            $table->dateTime('studyStartDate');
            $table->dateTime('studyEndDate')->nullable();
            $table->string('status')->default("true");
            $table->timestamps();
            // Foreign key constraint
            $table->foreign('userId')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('education');
    }
};
