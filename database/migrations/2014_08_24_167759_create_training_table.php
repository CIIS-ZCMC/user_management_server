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
        Schema::create('trainings', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->date('inclusive_from');
            $table->date('inclusive_to');
            $table->string('hours')->nullable();
            $table->string('type_of_ld');
            $table->string('conducted_by')->nullable();
            $table->unsignedBigInteger('personal_information_id')->nullable();
            $table->foreign('personal_information_id')->references('id')->on('personal_informations');
            $table->text('attachment')->nullable();
            $table->boolean('is_request')->default(false);
            $table->datetime('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trainings');
    }
};
