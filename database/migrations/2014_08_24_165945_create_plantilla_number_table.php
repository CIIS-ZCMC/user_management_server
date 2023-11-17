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
        Schema::create('plantilla_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('number');
            $table->datetime('assigned_at')->nullable();
            $table->unsignedBigInteger('plantilla_id');
            $table->foreign('plantilla_id')->references('id')->on('plantillas');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plantilla_numbers');
    }
};
