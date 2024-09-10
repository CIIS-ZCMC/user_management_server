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

            $table->unsignedBigInteger('employee_profile_id');
            $table->foreign('employee_profile_id')->references('id')->on('employee_profiles');

            $table->unsignedBigInteger('requesting_officer');
            $table->foreign('requesting_officer')->references('id')->on('employee_profiles');
                        
            $table->unsignedBigInteger('approving_officer');
            $table->foreign('approving_officer')->references('id')->on('employee_profiles');

            $table->date('pull_out_date');
            $table->date('approval_date')->nullable();
            $table->string('status')->default('pending');
            $table->string('reason')->nullable();
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
