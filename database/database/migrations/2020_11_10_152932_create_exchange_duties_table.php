<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exchange_duties', function (Blueprint $table) {
            $table->id();

            $table->date('requested_date_to_swap');
            $table->date('requested_date_to_duty');

            $table->unsignedBigInteger('requested_employee_id');
            $table->foreign('requested_employee_id')->references('id')->on('employee_profiles');

            $table->unsignedBigInteger('reliever_employee_id');
            $table->foreign('reliever_employee_id')->references('id')->on('employee_profiles');

            $table->unsignedBigInteger('requested_schedule_id');
            $table->foreign('requested_schedule_id')->references('id')->on('schedules');

            $table->unsignedBigInteger('reliever_schedule_id');
            $table->foreign('reliever_schedule_id')->references('id')->on('schedules');

            $table->unsignedBigInteger('approving_officer');
            $table->foreign('approving_officer')->references('id')->on('employee_profiles');

            $table->date('approval_date')->nullable();
            $table->string('status')->default('applied');
            $table->string('reason');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_duties');
    }
};
