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
        Schema::create('monetization_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_profile_id')->unsigned();
            $table->foreign('employee_profile_id')->references('id')->on('employee_profiles')->onDelete('cascade');
            $table->unsignedBigInteger('leave_type_id')->unsigned();
            $table->foreign('leave_type_id')->references('id')->on('leave_types')->onDelete('cascade');;
            $table->text('reason')->nullable();
            $table->string('credit_value');
            $table->string('status')->nullable();
            $table->string('attachment')->nullable();
            $table->unsignedBigInteger('hrmo_officer')->unsigned()->nullable();
            $table->foreign('hrmo_officer')->references('id')->on('employee_profiles')->onDelete('cascade');
            $table->unsignedBigInteger('recommending_officer')->unsigned()->nullable();
            $table->foreign('recommending_officer')->references('id')->on('employee_profiles')->onDelete('cascade');
            $table->unsignedBigInteger('approving_officer')->unsigned()->nullable();
            $table->foreign('approving_officer')->references('id')->on('employee_profiles')->onDelete('cascade');
            $table->unsignedBigInteger('employee_oic_id')->unsigned()->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monetization_applications');
    }
};
