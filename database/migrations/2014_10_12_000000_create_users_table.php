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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->text('password_encrypted');
            $table->datetime('deactivated') -> nullable();
            $table->datetime('approved')-> nullable();
            $table->string("otp") -> nullable();
            $table->date("otp_exp") -> nullable();
            $table->datetime("created_at")->default(now());
            $table->datetime("updated_at")->default(now());
            $table->softDeletes();
        });

        DB::statement('ALTER TABLE users AUTO_INCREMENT = 10000');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
