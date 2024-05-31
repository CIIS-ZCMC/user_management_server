<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('monthly_work_hours', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employment_type_id');
            $table->foreign('employment_type_id')->references('id')->on('employment_types');
            $table->string('month_year');
            $table->string('work_hours');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_work_hours');
    }
};
