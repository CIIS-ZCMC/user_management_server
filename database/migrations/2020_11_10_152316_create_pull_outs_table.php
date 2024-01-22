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
        Schema::create('pull_outs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('requested_employee_id');
            $table->foreign('requested_employee_id')->references('id')->on('employee_profiles');
                        
            $table->unsignedBigInteger('approve_by_employee_id');
            $table->foreign('approve_by_employee_id')->references('id')->on('employee_profiles');

            $table->date('pull_out_date');
            $table->date('approval_date');
            $table->boolean('status')->default(false);
            $table->string('reason');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pull_outs');
    }
};
