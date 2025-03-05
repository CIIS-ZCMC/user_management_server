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
        Schema::create('digital_signed_dtrs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_profile_id')->constrained('employee_profiles')->onDelete('cascade');
            $table->foreignId('digital_certificate_id')->constrained('digital_certificates')->onDelete('cascade');
            $table->foreignId('digital_dtr_signature_request_id')->constrained('digital_dtr_signature_requests')->onDelete('cascade');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('signer_type'); // owner or in-charge
            $table->boolean('whole_month')->default(false);
            $table->string('status')->default('signed');
            $table->text('signing_details')->nullable(); // JSON encoded details about the signing process
            $table->timestamp('signed_at')->useCurrent();
            $table->timestamps();
            $table->softDeletes();

            // Add indexes for common queries
            $table->index('employee_profile_id');
            $table->index('signer_type');
            $table->index('digital_certificate_id');
            $table->index('digital_dtr_signature_request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('digital_signed_dtrs');
    }
};
