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
        Schema::create('user_system_role', function (Blueprint $table) {
            $table->id();
            $table -> unsignedBigInteger('FK_user_ID')-> unsigned();
            $table -> foreign('FK_user_ID') -> references('id') -> on('users') -> onUpdate('cascade');
            $table -> unsignedBigInteger('FK_system_role_ID')-> unsigned();
            $table -> foreign('FK_system_role_ID') -> references('id') -> on('system_role') -> onUpdate('cascade');
            $table->unsignedBigInteger('FK_system_ID')-> unsigned();
            $table->foreign('FK_system_ID') -> references('id') -> on('system') -> onUpdate('cascade');
            $table->unsignedBigInteger('FK_token_ID')-> unsigned() -> nullable();
            $table->foreign('FK_token_ID') -> references('id') -> on('personal_access_tokens') -> onUpdate('cascade');
            $table->date('created_at');
            $table->date('updated_at');
            $table->boolean('deleted') -> default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_system_role');
    }
};
