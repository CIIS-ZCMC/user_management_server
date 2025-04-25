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
        Schema::create('digital_dtr_signature_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('digital_dtr_signature_request_id')
                ->constrained('digital_dtr_signature_requests')
                ->onDelete('cascade')
                ->name('fk_dtr_sig_logs_request_id');
            $table->foreignId('employee_profile_id') // The user who made the action (employee or head)
                ->constrained('employee_profiles')
                ->onDelete('cascade')
                ->name('fk_dtr_sig_logs_employee_id');
            $table->enum('action', ['created', 'submitted', 'approved', 'rejected', 'deleted', 'updated']);
            $table->text('remarks')->nullable(); // Optional comments about the action
            $table->timestamp('action_at')->useCurrent(); // Timestamp when the action was performed
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('digital_dtr_signature_logs');
    }
};
