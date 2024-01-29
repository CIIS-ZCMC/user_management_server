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
        Schema::create('personal_informations', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('name_extension')->nullable();
            $table->string('years_of_service')->nullable();
            $table->string('name_title')->nullable();
            $table->string('sex');
            $table->date('date_of_birth');
            $table->string('place_of_birth');
            $table->string('civil_status');
            $table->date('date_of_marriage')->nullable();
            $table->string('citizenship')->default('Filipino');
            $table->string('country')->default('Philippines');
            $table->integer('height')->nullable();//cm
            $table->integer('weight')->nullable();;
            $table->string('blood_type')->nullable();;
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_informations');
    }
};
