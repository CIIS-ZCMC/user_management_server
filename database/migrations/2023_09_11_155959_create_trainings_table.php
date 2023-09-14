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
            $table->uuid()->primary();
            $table->date('inclusive_date');
            $table->boolean('is_lnd')->default(FALSE);
            $table->string('conducted_by')->nullable();
            $table->string('total_hours')->nullable();
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
        Schema::dropIfExists('trainings');
    }
};
