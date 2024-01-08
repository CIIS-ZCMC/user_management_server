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
        Schema::create('daily_time_record_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('biometric_id');
            $table->integer('dtr_id');
            $table->text('json_logs');
            $table->integer('validated')->default(1);
            $table->date('dtr_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_time_record_logs');
    }
};
