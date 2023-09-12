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
        Schema::create('family_backgrounds', function (Blueprint $table) {
            $table->id();
            $table->string('spouse')->nullable();
            $table->string('address')->nullable();
            $table->string('zip_code')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('occupation')->nullable();
            $table->string('employer')->nullable();
            $table->date('business_address')->nullable();
            $table->string('telephone_no')->nullable();
            $table->string('tin_no')->nullable();
            $table->date('rdo_no')->nullable();
            $table->string('father_first_name');
            $table->string('father_middle_name')->nullable();
            $table->string('father_last_name');
            $table->string('father_ext_name')->nullable();
            $table->string('mother_first_name');
            $table->string('mother_middle_name')->nullable();
            $table->string('mother_last_name');
            $table->string('mother_ext_name')->nullable();
            $table->unsignedBigInteger('personal_information_id')->unsigned();
            $table->foreign('personal_information_id')->references('id')->on('personal_informations');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('family_background');
    }
};
