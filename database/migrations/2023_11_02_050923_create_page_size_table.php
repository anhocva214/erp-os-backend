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
        Schema::create('pageSize', function (Blueprint $table) {
            $table->id();
            $table->string('pageSizeName');
            $table->double('width', 8, 2);
            $table->double('height', 8, 2);
            $table->string('unit')->default('inches');
            $table->string('status')->default('true');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pageSize');
    }
};
