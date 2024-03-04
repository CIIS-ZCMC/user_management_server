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
            $table->datetime('email_verified_at')->nullable();
            $table->string('employee_id')->nullable();
            $table->string('profile_url')->nullable();
            $table->date('date_hired')->nullable();
            $table->text('password_encrypted')->nullable();
            $table->datetime('password_created_at');
            $table->datetime('password_expiration_at');
            $table->integer('authorization_pin')->nullable();
            $table->integer('biometric_id')->nullable();
            $table->integer('otp')->nullable();
            $table->datetime('otp_expiration')->nullable();
            $table->datetime('deactivated_at')->nullable();
            $table->string('agency_employee_no')->nullable();
            $table->boolean('allow_time_adjustment')->default(FALSE);
            $table->boolean('is_2fa')->default(FALSE);
            $table->unsignedBigInteger('employment_type_id');
            $table->foreign('employment_type_id')->references('id')->on('employment_types');
            $table->unsignedBigInteger('personal_information_id');
            $table->foreign('personal_information_id')->references('id')->on('personal_informations');
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
