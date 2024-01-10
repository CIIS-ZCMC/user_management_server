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
        Schema::create('official_time_applications', function (Blueprint $table) {
            $table->id();
            $table->string('date_from');
            $table->string('date_to');
            // $table->string('time_from');
            // $table->string('time_to');
            $table->string('reason')->nullable();
            $table->string('status');
            $table->string('date')->nullable();
            $table->string('time')->nullable();
            $table->string('decline_reason')->nullable();
            $table->string('personal_order')->nullable();
            $table->string('personal_order_path')->nullable();
            $table->string('personal_order_size')->nullable();
            $table->string('certificate_of_appearance')->nullable();
            $table->string('certificate_of_appearance_path')->nullable();
            $table->string('certificate_of_appearance_size')->nullable();
            $table->string('reference_number')->nullable();
            $table->unsignedBigInteger('employee_profile_id')->unsigned();
            $table->foreign('employee_profile_id')->references('id')->on('employee_profiles')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('official_time_applications');
    }
};
