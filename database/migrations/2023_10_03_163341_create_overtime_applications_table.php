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
        Schema::create('overtime_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_profile_id')->unsigned();
            $table->foreign('employee_profile_id')->references('id')->on('employee_profiles')->onDelete('cascade');
            $table->string('reference_number')->nullable();
            $table->string('status');
            $table->string('hrmo_officer_id')->nullable();
            $table->string('recommending_officer_id')->nullable();
            $table->string('approving_officer_id')->nullable();
            $table->string('remarks')->nullable();
            $table->string('purpose')->nullable();
            $table->string('overtime_letter_of_request')->nullable();
            $table->string('overtime_letter_of_request_path')->nullable();
            $table->string('overtime_letter_of_request_size')->nullable();
            $table->string('path')->nullable();
            $table->string('date');
            $table->string('time')->nullable();
            $table->string('decline_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtime_applications');
    }
};
