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
            $table->integer('order_by');
            $table->text('content_question');
            $table->boolean('has_detail')->default(false);
            $table->boolean('has_yes_no')->default(false);
            $table->boolean('has_date')->default(false);
            $table->boolean('has_sub_question')->default(FALSE);
            $table->unsignedBigInteger('legal_iq_id')->nullable();
            $table->timestamps();
        });
        
        Schema::table('legal_information_questions', function (Blueprint $table) {
            $table->foreign('legal_iq_id')->references('id')->on('legal_information_questions');
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
