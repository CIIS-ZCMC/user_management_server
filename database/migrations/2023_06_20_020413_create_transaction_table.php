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
        Schema::create('transaction', function (Blueprint $table) {
            $table->id();
            $table->string('status');

            $table->unsignedBigInteger('FK_system_ID')-> unsigned();
            $table->foreign('FK_system_ID') -> references('id') -> on('system') -> onUpdate('cascade');
            
            $table->unsignedBigInteger('FK_user_ID')-> unsigned();
            $table->foreign('FK_user_ID') -> references('id') -> on('users') -> onUpdate('cascade');

            $table->string('ip_address');
            $table->date('created');
            $table->boolean('deleted') -> default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction');
    }
};
