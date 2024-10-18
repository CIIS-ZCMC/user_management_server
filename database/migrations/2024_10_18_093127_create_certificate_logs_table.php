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
        Schema::create('certificate_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('certificate_attachment_id')->nullable(false);
            $table->unsignedBigInteger('employee_profile_id')->nullable(false);
            $table->string('action', 100)->nullable();
            $table->text('description')->nullable();
            $table->timestamp('performed_at')->useCurrent();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('certificate_attachment_id')->references('id')->on('certificate_attachments')->onDelete('cascade');
            $table->foreign('employee_profile_id')->references('id')->on('employee_profiles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificate_logs');
    }
};
