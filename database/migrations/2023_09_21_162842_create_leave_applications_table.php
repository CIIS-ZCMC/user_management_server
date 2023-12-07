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
        Schema::create('leave_applications', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('patient_type')->nullable();
            $table->string('illness')->nullable();
            $table->string('reason')->nullable();
            $table->string('leave_credit_total')->nullable();
            $table->string('status')->nullable();;
            $table->string('remarks')->nullable();;
            $table->string('date')->nullable();;
            $table->string('time')->nullable();;
            $table->boolean('with_pay')->default(false);
            $table->unsignedBigInteger('employee_profile_id')->unsigned();
            $table->foreign('employee_profile_id')->references('id')->on('employee_profiles')->onDelete('cascade');
            $table->unsignedBigInteger('leave_type_id')->unsigned();
            $table->foreign('leave_type_id')->references('id')->on('leave_types')->onDelete('cascade');;
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_applications');
    }
};
