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
        Schema::create('leave_requirement_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('leave_requirement_id')->unsigned();
            $table->foreign('leave_requirement_id')->references('id')->on('leave_requirements')->onDelete('cascade');
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
        Schema::dropIfExists('leave_requirement_logs');
    }
};
