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
        Schema::create('default_passwords', function (Blueprint $table) {
            $table->id();
            $table->string('password');
            $table->boolean('status')->default(FALSE);
            $table->datetime('effective_at')->default(now());
            $table->datetime('end_at')->default(now());
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('default_passwords');
    }
};
