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
        Schema::create('exchange_duty_logs', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('exchange_duty_id')->unsigned();
            $table->foreign('exchange_duty_id')->references('id')->on('exchange_duty')->onDelete('cascade');

            $table->unsignedBigInteger('action_by')->unsigned();
            $table->foreign('action_by')->references('id')->on('employee_profiles')->onDelete('cascade');
            
            $table->string('action')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_duty_logs');
    }
};
