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
        Schema::create('leave_application_date_times', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('leave_application_id')->unsigned();
            $table->foreign('leave_application_id')->references('id')->on('leave_applications')->onDelete('cascade');
            $table->string('date_from');
            $table->string('date_to');
            $table->string('time_from')->nullable();
            $table->string('time_to')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_application_date_times');
    }
};
