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
            $table->uuid()->primary();
            $table->uuid('employee_profile_id');
            $table->foreign('employee_profile_id')->references('uuid')->on('employee_profiles');         
            $table->text('details')->nullable();
            $table->boolean('answer')->default(FALSE);
            $table->uuid('legal_iq_id');
            $table->foreign('legal_iq_id')->references('uuid')->on('legal_information_questions');
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
