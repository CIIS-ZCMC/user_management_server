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
        Schema::create('time_adjusments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('employee_profile_id');
            $table->foreign('employee_profile_id')->references('id')->on('employee_profiles');

            $table->unsignedBigInteger('daily_time_record_id');
            $table->foreign('daily_time_record_id')->references('id')->on('daily_time_records');

            $table->integer('recommended_by')->unasigned();
            $table->integer('approve_by')->unasigned();

            $table->string('first_in')->nullable();
            $table->string('first_out')->nullable();
            $table->string('second_in')->nullable();
            $table->string('second_out')->nullable();

            $table->date('approval_date')->nullable();
            $table->string('remarks')->nullable();
            $table->string('status')->default('applied');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_adjusments');
    }
};
