<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCertificatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id(); // bigint unsigned auto_increment
            $table->unsignedBigInteger('personal_information_id');
            $table->string('subject_owner', 150)->nullable();
            $table->text('file_path')->nullable();
            $table->string('issued_by', 150)->nullable();
            $table->string('organization_unit', 150)->nullable();
            $table->string('country', 100)->nullable();
            $table->dateTime('valid_from')->nullable();
            $table->dateTime('valid_until')->nullable();
            $table->text('public_key')->nullable();
            $table->text('private_key')->nullable();
            $table->text('digital_signature')->nullable();
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
        Schema::dropIfExists('certificates');
    }
}
