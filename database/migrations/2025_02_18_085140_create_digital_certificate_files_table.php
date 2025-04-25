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
        Schema::create('digital_certificate_files', function (Blueprint $table) {
            $table->id(); // bigint unsigned auto_increment
            $table->foreignId('employee_profile_id')->constrained('employee_profiles')->onDelete('cascade');
            $table->string('filename', 191)->nullable();
            $table->text('file_path')->nullable();
            $table->string('file_extension', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('img_name', 191)->nullable();
            $table->text('img_path')->nullable();
            $table->string('img_extension', 100)->nullable();
            $table->unsignedBigInteger('img_size')->nullable();
            $table->text('cert_password')->nullable();
            $table->softDeletes();
            $table->timestamps(); // created_at and updated_at columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('digital_certificate_files');
    }
};
