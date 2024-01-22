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
            $table->datetime('date_from')->nullable();
            $table->datetime('date_to')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('patient_type')->nullable();
            $table->string('illness')->nullable();
            $table->string('applied_credits')->nullable();
            $table->string('status')->nullable();
            $table->string('remarks')->nullable();
            $table->string('hrmo_officer_id')->nullable();
            $table->string('recommending_officer_id')->nullable();
            $table->string('approving_officer_id')->nullable();
            $table->string('remarks')->nullable();
            $table->boolean('without_pay')->default(false);
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
