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
        Schema::create('position_system_access', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('system_id')->unsigned();
            $table->foreign('system_id')->references('id')->on('system');
            $table->unsignedBigInteger('employment_position_id')->unsigned();
            $table->foreign('employment_position_id')->references('id')->on('employment_position'); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('position_system_access');
    }
};
