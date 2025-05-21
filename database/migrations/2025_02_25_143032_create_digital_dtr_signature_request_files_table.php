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
        Schema::dropIfExists('digital_dtr_signature_request_files');
        
        Schema::create('digital_dtr_signature_request_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('digital_dtr_signature_request_id')
                ->constrained('digital_dtr_signature_requests')
                ->onDelete('cascade')
                ->name('fk_dtr_sig_req_files');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_extension', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('digital_dtr_signature_request_files');
    }
};
