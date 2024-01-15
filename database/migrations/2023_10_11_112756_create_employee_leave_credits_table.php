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
        Schema::create('employee_leave_credits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_profile_id')->unsigned();
            $table->foreign('employee_profile_id')->references('id')->on('employee_profiles')->onDelete('cascade');
            $table->unsignedBigInteger('leave_type_id')->unsigned();
            $table->foreign('leave_type_id')->references('id')->on('leave_types')->onDelete('cascade');
            $table->unsignedBigInteger('leave_application_id')->unsigned()->nullable();
            $table->foreign('leave_application_id')->references('id')->on('leave_applications')->onDelete('cascade');
            $table->string('operation');
            $table->string('absent_total')->nullable();
            $table->string('undertime_total')->nullable();
            $table->string('working_hours_total')->nullable();
            $table->string('credit_value');
            $table->string('true_credit_value')->nullable();
            $table->string('reason');
            $table->string('date');
            $table->string('time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_leave_credits');
    }
};
