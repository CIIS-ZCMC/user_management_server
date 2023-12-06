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

            // $table->unsignedBigInteger('section_head_id');
            // $table->foreign('section_head_id')->references('id')->on('employee_profiles')->onUpdate('cascade');
            // $table->boolean('supervisor_approval');
                        
            // $table->unsignedBigInteger('department_head_id');
            // $table->foreign('department_head_id')->references('id')->on('employee_profiles')->onUpdate('cascade');
            // $table->boolean('department_head_approval');

            $table->boolean('status')->default(false);
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
