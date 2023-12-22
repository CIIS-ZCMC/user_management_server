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
        Schema::create('requirement_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('requirement_id')->unsigned();
            $table->foreign('requirement_id')->references('id')->on('requirements')->onDelete('cascade');
            $table->unsignedBigInteger('action_by_id')->unsigned();
            $table->foreign('action_by_id')->references('id')->on('employee_profiles')->onDelete('cascade');
            $table->string('action')->nullable();
            $table->string('date');
            $table->string('time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requirement_logs');
    }
};
