<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('time_adjustments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('daily_time_record_id')->nullable();
            $table->foreign('daily_time_record_id')->references('id')->on('daily_time_records')->onDelete('cascade');

            $table->unsignedBigInteger('employee_profile_id');
            $table->foreign('employee_profile_id')->references('id')->on('employee_profiles');

            $table->unsignedBigInteger('recommending_officer')->nullable();
            $table->foreign('recommending_officer')->references('id')->on('employee_profiles');

            $table->unsignedBigInteger('approving_officer');
            $table->foreign('approving_officer')->references('id')->on('employee_profiles');

            $table->date('date')->nullable();
            $table->string('first_in')->nullable();
            $table->string('first_out')->nullable();
            $table->string('second_in')->nullable();
            $table->string('second_out')->nullable();
            $table->string('remarks')->nullable();
            $table->string('file_name')->nullable();
            $table->string('path')->nullable();
            $table->string('size')->nullable();
            $table->string('status')->default('applied');
            $table->date('approval_date')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_adjustments');
    }
};
