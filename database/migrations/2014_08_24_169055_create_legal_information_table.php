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
        Schema::create('legal_informations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('legal_iq_id');
            $table->foreign('legal_iq_id')->references('id')->on('legal_information_questions');
            $table->unsignedBigInteger('personal_information_id');
            $table->foreign('personal_information_id')->references('id')->on('personal_informations');    
            $table->boolean('answer')->nullable();     
            $table->text('details')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legal_informations');
    }
};
