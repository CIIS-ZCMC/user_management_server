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
        Schema::create('employee_leave_credit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_ot_credit_id')->unsigned();
            $table->foreign('employee_ot_credit_id')->references('id')->on('employee_overtime_credits');
            $table->float('previous_credit');
            $table->float('leave_credits'); // Earned or Deduct
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_leave_credit_logs');
    }
};
