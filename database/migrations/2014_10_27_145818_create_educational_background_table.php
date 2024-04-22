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
            $table->unsignedBigInteger('personal_information_id')->nullable();
            $table->foreign('personal_information_id')->references('id')->on('personal_informations');
            $table->string('level');
            $table->string('name');
            $table->string('degree_course')->nullable();
            $table->date('year_graduated')->nullable();
            $table->string('highest_grade')->nullable();
            $table->date('inclusive_from')->nullable();
            $table->date('inclusive_to')->nullable();
            $table->string('academic_honors')->nullable();
            $table->text('attachment')->nullable();
            $table->boolean('is_request')->default(false);
            $table->datetime('approved_at')->nullable();
            $table->string('attachment')->nullable();
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
