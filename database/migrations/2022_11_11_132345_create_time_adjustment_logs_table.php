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
        Schema::create('time_adjustment_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('time_adjustment_id')->unsigned();
            $table->foreign('time_adjustment_id')->references('id')->on('time_adjustments')->onDelete('cascade');

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
        Schema::dropIfExists('time_adjustment_logs');
    }
};
