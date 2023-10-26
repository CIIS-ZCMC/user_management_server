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
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('department_attachment_url')->nullable();
            $table->string('head_attachment_url')->nullable();
            $table->string('job_specification');
            $table->datetime('effective_at');
            $table->string('oic_attachment_url')->nullable();
            $table->datetime('oic_effective_at');
            $table->datetime('oic_end_at');
            $table->unsignedBigInteger('division_id');
            $table->foreign('division_id')->references('id')->on('divisions');
            $table->unsignedBigInteger('head_employee_profile_id')->nullable();
            $table->foreign('head_employee_profile_id')->references('id')->on('employee_profiles');
            $table->unsignedBigInteger('oic_employee_profile_id')->nullable();
            $table->foreign('oic_employee_profile_id')->references('id')->on('employee_profiles');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
