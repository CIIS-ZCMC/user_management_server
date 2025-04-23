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
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->uuid('area_id')->unique()->nulalble();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('unit_attachment_url')->nullable();
            $table->boolean('head_status')->default(FALSE);
            $table->datetime('head_effective_at')->nullable();
            $table->string('head_attachment_url')->nullable();
            $table->string('oic_attachment_url')->nullable();
            $table->datetime('oic_effective_at')->nullable();
            $table->datetime('oic_end_at')->nullable();
            $table->unsignedBigInteger('section_id');
            $table->foreign('section_id')->references('id')->on('sections');
            $table->unsignedBigInteger('head_employee_profile_id')->nullable();
            $table->foreign('head_employee_profile_id')->references('id')->on('employee_profiles');
            $table->unsignedBigInteger('oic_employee_profile_id')->nullable();
            $table->foreign('oic_employee_profile_id')->references('id')->on('employee_profiles');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
