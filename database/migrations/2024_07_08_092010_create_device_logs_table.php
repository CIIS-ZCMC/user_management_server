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
        Schema::create('device_logs', function (Blueprint $table) {
            $table->id();
            $table->string("biometric_id");
            $table->string("name");
            $table->date("dtr_date");
            $table->dateTime("date_time");
            $table->integer("status");
            $table->integer("is_Shifting");
            $table->text("schedule")->nullable();
            $table->integer('active')->comment("True if Existing Employee.");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_logs');
    }
};
