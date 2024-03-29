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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('time_shift_id');
            $table->foreign('time_shift_id')->references('id')->on('time_shifts');

            $table->unsignedBigInteger('holiday_id')->nullable();
            $table->foreign('holiday_id')->references('id')->on('holidays');

            $table->date('date');
            $table->boolean('is_weekend');
            $table->boolean('status')->default(true);
            $table->text('remarks')->nullable();
            $table->softDeletes();
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
