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
        Schema::create('exchange_duties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('schedule_id');
            $table->foreign('schedule_id')->references('id')->on('schedules')->onUpdate('cascade');

            $table->unsignedBigInteger('requested_employee_id');
            $table->foreign('requested_employee_id')->references('id')->on('employee_profiles')->onUpdate('cascade');
            
            $table->unsignedBigInteger('reliever_employee_id');
            $table->foreign('reliever_employee_id')->references('id')->on('employee_profiles')->onUpdate('cascade');
            
            $table->string('reason');
            $table->json('approve_by');
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
