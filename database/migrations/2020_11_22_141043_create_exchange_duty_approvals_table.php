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
        Schema::create('exchange_duty_approvals', function (Blueprint $table) {
            $table->id();
            $table->integer('exchange_duty_id')->unasigned();
            $table->integer('employee_profile_id')->unasigned();
            $table->boolean('approval_status')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_duty_approvals');
    }
};
