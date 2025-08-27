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
        Schema::create('divisions', function (Blueprint $table) {
            $table->id();
            $table->uuid('area_id')->unique()->nulalble();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('division_attachment_url')->nullable();
            $table->boolean('chief_status')->default(false);
            $table->string('chief_attachment_url')->nullable();
            $table->datetime('chief_effective_at')->nullable();
            $table->string('oic_attachment_url')->nullable();
            $table->datetime('oic_effective_at')->nullable();
            $table->datetime('oic_end_at')->nullable();
            $table->unsignedBigInteger('chief_employee_profile_id')->nullable();
            $table->foreign('chief_employee_profile_id')->references('id')->on('employee_profiles');
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
        Schema::dropIfExists('divisions');
    }
};
