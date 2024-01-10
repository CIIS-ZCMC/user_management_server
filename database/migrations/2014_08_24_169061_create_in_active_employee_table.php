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
        Schema::create('in_active_employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id');
            $table->string('profile_url');
            $table->date('date_hired');
            $table->integer('biometric_id');
            $table->datetime('employment_end_at')->default(now());
            $table->unsignedBigInteger('employment_type_id');
            $table->foreign('employment_type_id')->references('id')->on('employment_types');
            $table->unsignedBigInteger('personal_information_id');
            $table->foreign('personal_information_id')->references('id')->on('personal_informations');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('in_active_employees');
    }
};
