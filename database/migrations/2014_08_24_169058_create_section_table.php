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
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->string('section_attachment_url')->nullable();
            $table->string('supervisor_attachment_url')->nullable();
            $table->datetime('supervisor_effective_at')->nullable();
            $table->boolean('supervisor_status')->default(FALSE);
            $table->string('oic_attachment_url')->nullable();
            $table->datetime('oic_effective_at')->nullable();
            $table->datetime('oic_end_at')->nullable();
            $table->unsignedBigInteger('division_id')->nullable();
            $table->foreign('division_id')->references('id')->on('divisions')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->foreign('department_id')->references('id')->on('departments')->nullable();
            $table->unsignedBigInteger('supervisor_employee_profile_id')->nullable();
            $table->foreign('supervisor_employee_profile_id')->references('id')->on('employee_profiles');
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
        Schema::dropIfExists('sections');
    }
};
