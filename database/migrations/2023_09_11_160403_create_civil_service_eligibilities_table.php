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
        Schema::create('civil_service_eligibilities', function (Blueprint $table) {
            $table->uuid()->primary();
            $table->string('career_service');
            $table->string('rating');
            $table->date('date_of_examination');
            $table->string('place_of_examination');
            $table->string('license')->nullable();
            $table->uuid('personal_information_id');
            $table->foreign('personal_information_id')->references('uuid')->on('personal_informations');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('civil_service_eligibilities');
    }
};
