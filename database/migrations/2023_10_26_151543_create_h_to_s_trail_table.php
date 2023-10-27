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
        Schema::create('h_to_s_trails', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_profile_id');
            $table->foreign('employee_profile_id')->references('id')->on('employee_profiles');
            $table->unsignedBigInteger('sector_id');
            $table->string('sector_code');
            $table->string('attachment_url');
            $table->datetime('start_at');
            $table->datetime('end_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('h_to_s_trails');
    }
};
