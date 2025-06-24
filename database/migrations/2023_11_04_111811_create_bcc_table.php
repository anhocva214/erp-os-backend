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
        Schema::create('bcc', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('emailId')->nullable();
            $table->string('bccEmail');
            $table->string('status')->default("true");
            $table->timestamps();

            $table->foreign('emailId')->references('id')->on('email')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bcc');
    }
};
