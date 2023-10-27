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
        Schema::create('educational_backgrounds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('personal_information_id');
            $table->foreign('personal_information_id')->references('id')->on('personal_informations');
            $table->string('level');
            $table->string('name');
            $table->string('degree_course');
            $table->date('year_graduated');
            $table->string('highest_grade');
            $table->date('inclusive_from');
            $table->date('inclusive_to');
            $table->string('academic_honors');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('educational_backgrounds');
    }
};
