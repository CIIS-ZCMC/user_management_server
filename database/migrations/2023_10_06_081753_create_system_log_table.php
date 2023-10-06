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
        Schema::create('system_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action');
            $table->string('module');
            $table->boolean('status')->default(false);
            $table->string('ip_address');
            $table->string('remarks')->nullable();
            $table->unsignedBigInteger('module_id')->nullable();
            $table->unsignedBigInteger('employee_profile_id');
            $table->foreign('employee_profile_id')->references('id')->on('employee_profiles');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_logs');
    }
};
