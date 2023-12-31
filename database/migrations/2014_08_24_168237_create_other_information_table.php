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
        Schema::create('other_informations', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->boolean('skills_hobbies')->default(FALSE);
            $table->boolean('recognition')->default(FALSE);
            $table->boolean('organization')->default(FALSE);
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
        Schema::dropIfExists('other_informations');
    }
};
