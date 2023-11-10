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
        Schema::create('employee_overtime_credits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_profile_id')->unsigned();
            $table->foreign('employee_profile_id')->references('id')->on('employee_profiles')->onDelete('cascade');
            $table->unsignedBigInteger('overtime_application_id')->unsigned();
            $table->foreign('overtime_application_id')->references('id')->on('overtime_applications')->onDelete('cascade');
            $table->unsignedBigInteger('cto_application_id')->unsigned();
            $table->foreign('cto_application_id')->references('id')->on('cto_applications')->onDelete('cascade')->nullable();
            $table->string('operation');
            $table->string('overtime_hours')->nullable();
            $table->string('credit_value');
            $table->string('date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_overtime_credits');
    }
};
