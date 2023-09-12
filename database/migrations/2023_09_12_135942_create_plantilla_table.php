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
        Schema::create('plantillas', function (Blueprint $table) {
            $table->id();
            $table->string('plantilla_no');
            $table->string('tranche')->nullable();
            $table->date('date')->default(now());
            $table->string('category')->nullable();
            $table->unsignedBigInteger('job_position_id')->unsigned();
            $table->foreign('job_position_id')->references('id')->on('job_positions');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
s     */
    public function down(): void
    {
        Schema::dropIfExists('plantillas');
    }
};
