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
            $table->id();
            $table->text('gsis_id_no')->nullable();
            $table->text('pag_ibig_id_no')->nullable();
            $table->text('philhealth_id_no')->nullable();
            $table->text('sss_id_no')->nullable();
            $table->text('prc_id_no')->nullable();
            $table->text('tin_id_no')->nullable();
            $table->text('rdo_no')->nullable();
            $table->text('bank_account_no')->nullable();
            $table->unsignedBigInteger('personal_information_id')->nullable();
            $table->foreign('personal_information_id')->references('id')->on('personal_informations');
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
