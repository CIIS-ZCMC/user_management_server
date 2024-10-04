<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCertificateAttachmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('certificate_attachments', function (Blueprint $table) {
            $table->id(); // bigint unsigned auto_increment
            $table->unsignedBigInteger('personal_information_id');
            $table->string('filename', 191)->nullable();
            $table->text('file_cert_path')->nullable();
            $table->text('file_img_cert_path')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->softDeletes();
            $table->timestamps(); // created_at and updated_at columns

            // Define foreign key constraint for `user_id`
            $table->foreign('personal_information_id')->references('id')->on('personal_informations');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('certificate_attachments');
    }
}
