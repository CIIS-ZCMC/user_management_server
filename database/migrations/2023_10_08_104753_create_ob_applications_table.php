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
        Schema::create('ob_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_profile_id')->unsigned();
            $table->foreign('employee_profile_id')->references('id')->on('employee_profiles')->onDelete('cascade');
            $table->string('date_from');
            $table->string('date_to');
            $table->string('time_from');
            $table->string('time_to');
            $table->string('reason')->nullable();
            $table->string('status');
            $table->string('date')->nullable();
            $table->string('time')->nullable();
            $table->string('personal_order')->nullable();
            $table->string('certificate_of_apperance')->nullable();
            $table->string('reference_number')->nullable();
           
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ob_applications');
    }
};
