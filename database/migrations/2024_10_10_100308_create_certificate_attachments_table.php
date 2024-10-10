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
    public function up(): void
    {
        Schema::create('certificate_attachments', function (Blueprint $table) {
            $table->id(); // bigint unsigned auto_increment
            $table->unsignedBigInteger('employee_profile_id');
            $table->string('filename', 191)->nullable();
            $table->text('file_path')->nullable();
            $table->string('file_extension', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('img_name', 191)->nullable();
            $table->text('img_path')->nullable();
            $table->string('img_extension', 100)->nullable();
            $table->unsignedBigInteger('img_size')->nullable();
            $table->text('cert_password')->nullable();
            $table->softDeletes();
            $table->timestamps(); // created_at and updated_at columns

            // Define foreign key constraint for `user_id`
            $table->foreign('employee_profile_id')->references('id')->on('employee_profiles');
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
