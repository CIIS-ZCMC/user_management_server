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
        Schema::create('identification_numbers', function (Blueprint $table) {
            $table->uuid()->primary();
            $table->string('gsis_id_no')->nullable();
            $table->string('pag_ibig_id_no')->nullable();
            $table->string('philhealth_id_no')->nullable();
            $table->string('sss_id_no')->nullable();
            $table->string('prc_id_no')->nullable();
            $table->string('tin_id_no')->nullable();
            $table->string('rdo_no')->nullable();
            $table->string('bank_account_no')->nullable();
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
        Schema::dropIfExists('identification_numbers');
    }
};
