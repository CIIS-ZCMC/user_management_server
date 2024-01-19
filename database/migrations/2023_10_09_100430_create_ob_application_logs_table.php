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
        Schema::create('ob_application_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ob_application_id')->unsigned();
            $table->foreign('ob_application_id')->references('id')->on('ob_applications')->onDelete('cascade');
            $table->unsignedBigInteger('action_by')->unsigned();
            $table->foreign('action_by')->references('id')->on('employee_profiles')->onDelete('cascade');
            $table->string('action')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ob_application_logs');
    }
};
