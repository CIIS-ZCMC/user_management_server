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
        Schema::create('issuance_informations', function (Blueprint $table) {
            $table->id();
            $table->string('license_no')->nullable();
            $table->string('govt_issued_id')->nullable();
            $table->date('ctc_issued_date')->nullable();
            $table->string('ctc_issued_at')->nullable();
            $table->string('person_administrative_oath')->nullable();
            $table->unsignedBigInteger('employee_profile_id')->nullable();
            $table->foreign('employee_profile_id')->references('id')->on('employee_profiles');
            $table->unsignedBigInteger('in_active_employee_id')->nullable();
            $table->foreign('in_active_employee_id')->references('id')->on('in_active_employees');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issuance_informations');
    }
};
