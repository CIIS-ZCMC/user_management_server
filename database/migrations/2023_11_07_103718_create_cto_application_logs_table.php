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
        Schema::create('cto_application_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cto_application_id')->unsigned();
            $table->foreign('cto_application_id')->references('id')->on('cto_applications')->onDelete('cascade');
            $table->unsignedBigInteger('action_by_id')->unsigned();
            $table->foreign('action_by_id')->references('id')->on('employee_profiles')->onDelete('cascade');
            $table->string('action')->nullable();
            $table->string('status');
            $table->string('date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cto_application_logs');
    }
};
