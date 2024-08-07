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
            $table->string('status');
            $table->string('remarks')->nullable();
            $table->string('purpose')->nullable();
            $table->string('overtime_letter_of_request')->nullable();
            $table->string('overtime_letter_of_request_path')->nullable();
            $table->string('overtime_letter_of_request_size')->nullable();
            $table->string('decline_reason')->nullable();
            $table->unsignedBigInteger('recommending_officer')->unsigned()->nullable();
            $table->foreign('recommending_officer')->references('id')->on('employee_profiles')->onDelete('cascade');
            $table->unsignedBigInteger('approving_officer')->unsigned()->nullable();
            $table->foreign('approving_officer')->references('id')->on('employee_profiles')->onDelete('cascade');
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
