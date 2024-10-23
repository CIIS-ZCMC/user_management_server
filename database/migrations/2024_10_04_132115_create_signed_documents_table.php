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
        Schema::create('signed_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_profile_id')->nullable(false);
            $table->string('document_type')->nullable(); // e.g., 'leave_application', 'ob', 'cto', 'dtr'
            $table->unsignedBigInteger('document_id')->nullable(); // The ID of the related document (leave, OB, etc.)
            $table->timestamp('signed_at')->nullable(); // When the document was signed
            $table->string('signature_path')->nullable(); // Path to the signature file
            $table->timestamps();

            // Foreign key constraint for employee_id (assuming there's an employees table)
            $table->foreign('employee_profile_id')->references('id')->on('employee_profiles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signed_documents');
    }
};
