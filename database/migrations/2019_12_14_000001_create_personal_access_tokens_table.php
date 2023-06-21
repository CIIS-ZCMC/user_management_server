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
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table -> unsignedBigInteger('FK_user_ID')-> unsigned();
            $table -> foreign('FK_user_ID') -> references('id') -> on('users') -> onUpdate('cascade');
            $table -> unsignedBigInteger('FK_system_ID')-> unsigned();
            $table -> foreign('FK_system_ID') -> references('id') -> on('system') -> onUpdate('cascade');
            $table->string('token')->unique();
            $table->date('last_used_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->boolean('revoke')->default();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
