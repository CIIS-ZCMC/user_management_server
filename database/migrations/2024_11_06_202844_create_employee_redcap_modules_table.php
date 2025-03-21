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
        Schema::create('employee_redcap_modules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('redcap_module_id')->unsigned();
            $table->foreign('redcap_module_id')->references('id')->on('redcap_modules');
            $table->unsignedBigInteger('employee_profile_id')->unsigned();
            $table->foreign('employee_profile_id')->references('id')->on('employee_profiles');
            $table->string("employee_auth_id");
            $table->dateTime("deactivated_at")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_redcap_modules');
    }
};
