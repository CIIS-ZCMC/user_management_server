<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSignedDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('signed_documents', function (Blueprint $table) {
            $table->id(); // bigint unsigned auto_increment
            $table->unsignedBigInteger('leave_attachment_id'); // Foreign key to documents table
            $table->unsignedBigInteger('personal_information_id');
            $table->unsignedBigInteger('certificate_id'); // Foreign key to certificates table
            $table->timestamps(); // created_at and updated_at columns

            // Foreign key constraints
            $table->foreign('leave_attachment_id')->references('id')->on('leave_attachments');
            $table->foreign('personal_information_id')->references('id')->on('personal_informations');
            $table->foreign('certificate_id')->references('id')->on('certificates');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signed_documents');
    }
};
