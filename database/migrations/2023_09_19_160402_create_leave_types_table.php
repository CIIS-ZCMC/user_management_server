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
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('period');
            $table->string('file_date');
            $table->string('code');
            $table->string('attachment')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_special')->default(false);
            $table->boolean('is_country')->default(false);
            $table->boolean('is_illness')->default(false);
            $table->string('leave_credit_year')->nullable();
            $table->string('date')->nullable();
            $table->string('time')->nullable();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
