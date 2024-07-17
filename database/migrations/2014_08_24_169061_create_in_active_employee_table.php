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
        Schema::create('in_active_employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id');
            $table->date('date_hired');
            $table->date('date_resigned')->nullable();
            $table->unsignedBigInteger('employee_profile_id');
            $table->foreign('employee_profile_id')->references('id')->on('employee_profiles');
            $table->unsignedBigInteger('employment_type_id')->nullable();
            $table->foreign('employment_type_id')->references('id')->on('employment_types');
            $table->integer('biometric_id')->nullable();
            $table->string('status')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('in_active_employees');
    }
};
