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
        Schema::create('time_shifts', function (Blueprint $table) {
            $table->id();
            $table->time('first_in');
            $table->time('first_out');
            $table->time('second_in')->nullable();
            $table->time('second_out')->nullable();
            $table->integer('total_hours');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_shifts');
    }
};
