<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('daily_time_records', function (Blueprint $table) {
            $table->id();
            $table->integer('biometric_id');
            $table->date('dtr_date');
            $table->string('first_in')->nullable();
            $table->string('first_out')->nullable();
            $table->string('second_in')->nullable();
            $table->string('second_out')->nullable();
            $table->string('interval_req')->nullable()->comment('3mins byDefault');
            $table->integer('required_working_hours')->nullable();
            $table->integer('required_working_minutes')->nullable();
            $table->string('total_working_hours')->nullable();
            $table->integer('total_working_minutes')->nullable()->comment('deducted by undertime');
            $table->string('overtime')->nullable();
            $table->integer('overtime_minutes')->nullable();
            $table->string('undertime')->nullable();
            $table->integer('undertime_minutes')->nullable();
            $table->integer('overall_minutes_rendered')->nullable();
            $table->integer('total_minutes_reg')->nullable();
            $table->integer('is_biometric')->default(0)->comment('1 if pulled from Bio');
            $table->boolean('is_time_adjustment')->default(0);
            $table->boolean('is_generated')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_time_records');
    }
};
