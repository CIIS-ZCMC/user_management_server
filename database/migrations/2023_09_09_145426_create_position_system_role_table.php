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
        Schema::create('position_system_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_position_id');
            $table->foreign('job_position_id')->references('id')->on('job_positions');
            $table->unsignedBigInteger('system_role_id');
            $table->foreign('system_role_id')->references('id')->on('system_roles');
            $table->datetime('in_active')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('position_system_roles');
    }
};
