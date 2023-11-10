<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDtrAnomaliesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dtr_anomalies', function (Blueprint $table) {
            $table->id();
            $table->integer('biometric_id');
            $table->string('name');
            $table->dateTime('dtr_entry');
            $table->integer('status');
            $table->string('status_desc');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dtr_anomalies');
    }
}
