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
        Schema::create('plantilla_requirements', function (Blueprint $table) {
            $table->id();
            $table->string('education');
            $table->string('training')->nullable();
            $table->string('experience')->nullable();
            $table->string('eligibility')->nullable();
            $table->string('competency')->nullable();
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
        Schema::dropIfExists('plantilla_requirements');
    }
};
