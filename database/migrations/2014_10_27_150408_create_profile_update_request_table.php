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
        Schema::create('profile_update_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_profile_id');
            $table->foreign('employee_profile_id')->references('id')->on('employee_profiles');
            $table->unsignedBigInteger('approved_by');
            $table->foreign('approved_by')->references('id')->on('employee_profiles');
            $table->string('table_name');
            $table->unsignedBigInteger('data_id');
            $table->unsignedBigInteger('target_id');
            $table->boolean('type_new_or_replace')->default(FALSE);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_update_requests');
    }
};
