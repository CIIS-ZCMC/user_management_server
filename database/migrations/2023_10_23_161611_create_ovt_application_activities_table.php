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
        Schema::create('ovt_application_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('overtime_application_id')->unsigned();
            $table->foreign('overtime_application_id')->references('id')->on('overtime_applications')->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('quantity')->nullable();
            $table->string('man_hour')->nullable();
            $table->string('period_covered')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ovt_application_activities');
    }
};
