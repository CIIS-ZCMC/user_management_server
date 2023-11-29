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
        Schema::create('dtr_anomalies', function (Blueprint $table) {
<<<<<<< HEAD
=======
            // $table->id();
            // $table->integer('biometric_id');
            // $table->string('name');
            // $table->dateTime('dtr_entry');
            // $table->integer('status');
            // $table->string('status_desc');
            // $table->timestamps();

            

>>>>>>> main
            $table->id();
            $table->integer('biometric_id');
            $table->string('first_in')->nullable();
            $table->string('first_out')->nullable();
            $table->string('second_in')->nullable();
            $table->string('second_out')->nullable();
            $table->string('interval_req')->nullable()->comment('3mins byDefault');
            $table->integer('required_working_hours')->nullable();
            $table->integer('required_working_minutes')->nullable();
            $table->string('total_working_hours')->nullable();
            $table->integer('total_working_minutes')->nullable();
            $table->string('overtime')->nullable();
            $table->integer('overtime_minutes')->nullable();
            $table->string('undertime')->nullable();
            $table->integer('undertime_minutes')->nullable();
            $table->integer('overall_minutes_rendered')->nullable();
            $table->integer('total_minutes_reg')->nullable();
            $table->integer('is_biometric')->default(0)->comment('1 if pulled from Bio');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dtr_anomalies');
    }
};
