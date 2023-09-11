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
        Schema::create('employee_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id')->nullable();
            $table->string('profile_url')->nullable();
            $table->date('date_hired');
            $table->string('job_type');
            $table->string('password')->nullable();
            $table->datetime('password_created_date');
            $table->datetime('password_expiration_date');
            $table->unsignedBigInteger('department_id')->unsigned();
            $table->foreign('department_id')->references('id')->on('departments');
            $table->unsignedBigInteger('employment_position_id')->unsigned();
            $table->foreign('employment_position_id')->references('id')->on('employment_positions');
            $table->unsignedBigInteger('personal_information_id')->unsigned();
            $table->foreign('personal_information_id')->references('id')->on('personal_informations');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_profiles');
    }
};
