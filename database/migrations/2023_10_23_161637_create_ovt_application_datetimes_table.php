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
            $table->unsignedBigInteger('ovt_application_employee_id')->unsigned();
            $table->foreign('ovt_application_employee_id')->references('id')->on('ovt_application_employees')->onDelete('cascade');
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
