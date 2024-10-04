<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCertificateDetailsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('certificate_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('personal_information_id');
            $table->unsignedBigInteger('certificate_id'); // Foreign key to certificates table
            $table->string('subject_owner', 191)->nullable();
            $table->string('issued_by', 191)->nullable();
            $table->string('organization_unit', 191)->nullable();
            $table->string('country', 191)->nullable();
            $table->dateTime('valid_from')->nullable();
            $table->dateTime('valid_till')->nullable();
            $table->text('public_key')->nullable();
            $table->text('private_key')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('personal_information_id')->references('id')->on('personal_informations');
            $table->foreign('certificate_id')->references('id')->on('certificates');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificate_details');
    }
};
