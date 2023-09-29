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
            $table->datetime('ctc_issued_date')->nullable();
            $table->string('ctc_issued_at')->nullable();
            $table->unsignedBigInteger('employee_profile_id');
            $table->foreign('employee_profile_id')->references('id')->on('employee_profiles');
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
