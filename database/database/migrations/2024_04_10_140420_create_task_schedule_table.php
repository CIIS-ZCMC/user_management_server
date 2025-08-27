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
        Schema::create('task_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('action')->nullable();
            $table->datetime('effective_at')->nullable();
            $table->datetime('end_at')->nullable();
            $table->unsignedBigInteger('employee_profile_id')->unsigned()->nullable();
            $table->foreign('employee_profile_id')->references('id')->on('employee_profiles')->onDelete('cascade');
            $table->unsignedBigInteger('candidate_employee')->unsigned()->nullable();
            $table->foreign('candidate_employee')->references('id')->on('employee_profiles')->onDelete('cascade');
            $table->boolean('task_run')->default(false);
            $table->boolean('task_complete')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_schedules');
    }
};
