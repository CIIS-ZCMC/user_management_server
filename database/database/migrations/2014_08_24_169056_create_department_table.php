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
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->uuid('area_id')->unique()->nulalble();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('department_attachment_url')->nullable();
            $table->unsignedBigInteger('division_id');
            $table->foreign('division_id')->references('id')->on('divisions');

            /**
             * Head Data
             */
            $table->string('head_attachment_url')->nullable();
            $table->datetime('head_effective_at')->nullable();
            $table->boolean('head_status')->default(FALSE);
            $table->unsignedBigInteger('head_employee_profile_id')->nullable();
            $table->foreign('head_employee_profile_id')->references('id')->on('employee_profiles');

            /**
             * Training Officer Data
             */
            $table->string('training_officer_attachment_url')->nullable();
            $table->datetime('training_officer_effective_at')->nullable();
            $table->boolean('training_officer_status')->default(FALSE);
            $table->unsignedBigInteger('training_officer_employee_profile_id')->nullable();
            $table->foreign('training_officer_employee_profile_id')->references('id')->on('employee_profiles');
            
            /**
             * Training Officer Data
             */
            $table->string('oic_attachment_url')->nullable();
            $table->datetime('oic_effective_at')->nullable();
            $table->datetime('oic_end_at')->nullable();
            $table->unsignedBigInteger('oic_employee_profile_id')->nullable();
            $table->foreign('oic_employee_profile_id')->references('id')->on('employee_profiles');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
