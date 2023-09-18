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
            $table->uuid()->primary();
            $table->datetime('email_verified_at')->nullable();
            $table->string('employee_id')->nullable();
            $table->string('profile_url')->nullable();
            $table->date('date_hired')->nullable();
            $table->string('job_type');
            $table->uuid('department_id')->nullable();
            $table->foreign('department_id')->references('uuid')->on('departments');
            $table->uuid('station_id')->nullable();
            $table->foreign('station_id')->references('uuid')->on('stations');
            $table->uuid('job_position_id')->nullable();
            $table->foreign('job_position_id')->references('uuid')->on('job_positions');
            $table->uuid('plantilla_id')->nullable();
            $table->foreign('plantilla_id')->references('uuid')->on('plantillas');
            $table->uuid('personal_information_id');
            $table->foreign('personal_information_id')->references('uuid')->on('personal_informations');
            $table->text('password_encrypted')->nullable();
            $table->datetime('password_created_date');
            $table->datetime('password_expiration_date');
            $table->integer('otp')->nullable();
            $table->datetime('otp_expiration')->nullable();
            $table->datetime('approved')->nullable();
            $table->datetime('deactivated')->nullable();
            $table->softDeletes();
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
