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
        Schema::create('attendance__information', function (Blueprint $table) {
            $table->id();
            $table->string("biometric_id")->nullable();
            $table->string("name")->nullable();
            $table->text("area")->nullable();
            $table->text("areacode")->nullable();
            $table->string("sector")->nullable();
            $table->dateTime("first_entry")->nullable();
            $table->dateTime("last_entry")->nullable();
            $table->unsignedBigInteger('attendances_id')->unsigned()->nullable();
            $table->foreign('attendances_id')->references('id')->on('attendances');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance__information');
    }
};
