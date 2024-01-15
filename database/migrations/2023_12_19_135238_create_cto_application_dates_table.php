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
        Schema::create('cto_application_dates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cto_application_id')->unsigned()->nullable();
            $table->foreign('cto_application_id')->references('id')->on('cto_applications')->onDelete('cascade')->nullable();
            $table->string('time_from')->nullable();
            $table->string('time_to')->nullable();
            $table->string('date')->nullable();
            $table->string('purpose')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cto_application_dates');
    }
};
