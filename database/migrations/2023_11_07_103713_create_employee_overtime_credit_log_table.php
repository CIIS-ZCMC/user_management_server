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
        Schema::create('employee_overtime_credit_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('employee_ot_credit_id')->unsigned()->nullable();
            $table->foreign('employee_ot_credit_id')->references('id')->on('employee_overtime_credits');

            $table->unsignedBigInteger('cto_application_id')->unsigned()->nullable();
            $table->foreign('cto_application_id')->references('id')->on('cto_applications');

            $table->unsignedBigInteger('overtime_application_id')->unsigned()->nullable();
            $table->foreign('overtime_application_id')->references('id')->on('overtime_applications');
            $table->string('action')->nullable();
            $table->string('reason')->nullable();
            $table->decimal('previous_overtime_hours', 8, 2)->nullable();
            $table->decimal('hours', 8, 2)->nullable();
            $table->integer('expired_credit_by_hour')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_overtime_credit_logs');
    }
};
