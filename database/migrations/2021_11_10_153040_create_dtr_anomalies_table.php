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
        Schema::create('dtr_anomalies', function (Blueprint $table) {
            $table->id();
            $table->integer('biometric_id');
            $table->string('name');
            $table->dateTime('dtr_entry');
            $table->integer('status');
            $table->string('status_desc');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dtr_anomalies');
    }
};
