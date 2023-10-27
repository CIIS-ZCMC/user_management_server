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
        Schema::create('login_trails', function (Blueprint $table) {
            $table->id();
            $table->datetime('signin_at');
            $table->string('ip_address');
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
        Schema::dropIfExists('login_trails');
    }
};
