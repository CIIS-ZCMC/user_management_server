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
        Schema::create('pull_out_employee', function (Blueprint $table) {
            $table->id();
            $table->integer('pull_out_id')->unasigned();
            $table->integer('employee_profile_id')->unasigned();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pull_out_employee');
    }
};
