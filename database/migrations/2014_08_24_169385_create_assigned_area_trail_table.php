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
        Schema::create('assigned_area_trails', function (Blueprint $table) {
            $table->id();
            $table->integer('salary_grade_step')->default(1);
            $table->unsignedBigInteger('employee_profile_id')->nullable();
            $table->foreign('employee_profile_id')->references('id')->on('employee_profiles');
            $table->unsignedBigInteger('division_id')->nullable();
            $table->foreign('division_id')->references('id')->on('divisions');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->foreign('department_id')->references('id')->on('departments');
            $table->unsignedBigInteger('section_id')->nullable();
            $table->foreign('section_id')->references('id')->on('sections');
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->foreign('unit_id')->references('id')->on('units');
            $table->unsignedBigInteger('designation_id')->nullable();
            $table->foreign('designation_id')->references('id')->on('designations');
            $table->unsignedBigInteger('plantilla_id')->nullable();
            $table->foreign('plantilla_id')->references('id')->on('plantillas');
            $table->unsignedBigInteger('plantilla_number_id')->nullable();
            $table->foreign('plantilla_number_id')->references('id')->on('plantilla_numbers');
            $table->datetime('started_at');
            $table->datetime('end_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assigned_area_trails');
    }
};
