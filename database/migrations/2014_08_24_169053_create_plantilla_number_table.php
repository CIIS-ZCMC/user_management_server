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
        Schema::create('plantilla_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('number');
            $table->boolean('is_vacant')->default(true);
            $table->boolean('is_dissolve')->default(false);
            $table->datetime('assigned_at')->nullable();
            $table->unsignedBigInteger('plantilla_id');
            $table->foreign('plantilla_id')->references('id')->on('plantillas');
            $table->unsignedBigInteger('employment_type_id')->nullable();
            $table->foreign('employment_type_id')->references('id')->on('employment_types');
            $table->unsignedBigInteger('employee_profile_id')->nullable();
            $table->foreign('employee_profile_id')->references('id')->on('employee_profiles');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plantilla_numbers');
    }
};
