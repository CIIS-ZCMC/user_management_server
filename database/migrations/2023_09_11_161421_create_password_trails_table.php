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
        Schema::create('password_trails', function (Blueprint $table) {
            $table->uuid()->primary();
            $table->text('old_password');
            $table->datetime('password_created_at');
            $table->datetime('expired_at');
            $table->uuid('employee_profile_id');
            $table->foreign('employee_profile_id')->references('uuid')->on('employee_profiles');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_trails');
    }
};
