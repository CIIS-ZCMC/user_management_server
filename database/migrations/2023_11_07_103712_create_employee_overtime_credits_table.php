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
        Schema::create('employee_overtime_credits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_profile_id')->unsigned();
            $table->foreign('employee_profile_id')->references('id')->on('employee_profiles');
            $table->decimal('earned_credit_by_hour', 8, 2); // Adjust precision and scale as needed
            $table->decimal('used_credit_by_hour', 8, 2); // Example of another decimal column
            $table->integer('max_credit_monthly');
            $table->integer('max_credit_annual');
            $table->DateTime('valid_until')->nullable();
            $table->boolean('is_expired')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_overtime_credits');
    }
};
