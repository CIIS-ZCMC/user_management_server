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
        Schema::create('leave_type_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('leave_type_id')->unsigned();
            $table->foreign('leave_type_id')->references('id')->on('leave_types');
            $table->unsignedBigInteger('action_by')->unsigned();
            $table->foreign('action_by')->references('id')->on('employee_profiles');
            $table->string('action')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_type_logs');
    }
};
