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
            $table->unsignedBigInteger('employee_leave_credit_id')->unsigned()->nullable();
            $table->foreign('employee_leave_credit_id')->references('id')->on('employee_leave_credits')->onDelete('cascade')->nullable();
            $table->decimal('previous_credit', 8, 3)->nullable();
            $table->decimal('leave_credits', 8, 3)->nullable();
            $table->text('reason')->nullable();
            $table->text('action')->nullable();
            $table->unsignedBigInteger('action_by')->unsigned()->nullable();
            $table->foreign('action_by')->references('id')->on('employee_profiles')->onDelete('cascade')->nullable();
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
