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
        Schema::create('digital_signed_leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_profile_id')->constrained('employee_profiles')->onDelete('cascade');
            $table->foreignId('digital_certificate_id')->constrained('digital_certificates')->onDelete('cascade');
            $table->foreignId('leave_attachment_id')->constrained('leave_attachments')->onDelete('cascade');
            $table->foreignId('leave_application_id')->constrained('leave_applications')->onDelete('cascade');
            $table->foreignId('previous_signed_id')->nullable()->constrained('digital_signed_leaves')->onDelete('set null');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('signer_type'); // owner, head, sao or head
            $table->string('status')->default('signed');
            $table->text('signing_details')->nullable(); // JSON encoded details about the signing process
            $table->timestamp('signed_at')->useCurrent();
            $table->timestamps();
            $table->softDeletes();

            // Add indexes for common queries
            $table->index('employee_profile_id');
            $table->index('digital_certificate_id');
            $table->index('leave_attachment_id');
            $table->index('leave_application_id');
            $table->index('signer_type');
            $table->index('status');
            $table->index('signed_at');

            // Add unique constraint to prevent duplicate signatures
            $table->unique(['leave_application_id', 'signer_type'], 'unique_leave_signature');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('digital_signed_leaves');
    }
};
