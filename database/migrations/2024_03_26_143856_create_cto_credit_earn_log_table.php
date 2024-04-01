<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cto_credit_earn_logs', function (Blueprint $table) {
            $table->id();
            $table->float('credit')->default(0);
            $table->unsignedBigInteger('employee_leave_credit_id')->unsigned()->nullable();
            $table->foreign('employee_leave_credit_id')->references('id')->on('employee_leave_credits')->onDelete('cascade');
            $table->unsignedBigInteger('employee_profile_id')->unsigned()->nullable();
            $table->foreign('employee_profile_id')->references('id')->on('employee_profiles')->onDelete('cascade');
            $table->datetime('expiration')->default(Carbon::now()->addYear()->endOfYear()->month(12)->day(25));
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cto_credit_earn_logs');
    }
};
