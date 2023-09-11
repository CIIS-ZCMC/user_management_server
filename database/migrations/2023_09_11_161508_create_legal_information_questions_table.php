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
        Schema::create('legal_information_questions', function (Blueprint $table) {
            $table->id();
            $table->text('content_question');
            $table->boolean('is_sub_question')->default(FALSE);
            $table->unsignedBigInteger('legal_information_question_id')->unsigned();
            $table->foreign('legal_information_question_id')->references('id')->on('legal_information_questions');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legal_information_questions');
    }
};
