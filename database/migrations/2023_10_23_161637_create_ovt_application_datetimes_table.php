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
        Schema::create('ovt_application_datetimes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ovt_application_activity_id')->unsigned()->nullable();
            $table->foreign('ovt_application_activity_id')->references('id')->on('ovt_application_activities')->onDelete('cascade')->nullable();
            $table->unsignedBigInteger('overtime_application_id')->unsigned()->nullable();
            $table->foreign('overtime_application_id')->references('id')->on('overtime_applications')->onDelete('cascade')->nullable();
            $table->string('time_from')->nullable();
            $table->string('time_to')->nullable();
            $table->string('date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ovt_application_datetimes');
    }
};
