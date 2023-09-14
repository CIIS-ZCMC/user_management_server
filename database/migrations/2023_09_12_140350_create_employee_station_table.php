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
        Schema::create('employee_stations', function (Blueprint $table) {
            $table->uuid()->primary();
            $table->uuid('employee_profile_id');
            $table->foreign('employee_profile_id')->references('uuid')->on('employee_profiles');
            $table->uuid('job_position_id');
            $table->foreign('job_position_id')->references('uuid')->on('job_positions');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_stations');
    }
};
