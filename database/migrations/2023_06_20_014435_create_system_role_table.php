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
        Schema::create('system_role', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('FK_role_ID')-> unsigned();
            $table->foreign('FK_role_ID') -> references('id') -> on('role') -> onUpdate('cascade');
            $table->unsignedBigInteger('FK_system_ID')-> unsigned();
            $table->foreign('FK_system_ID') -> references('id') -> on('system') -> onUpdate('cascade');
            $table->json('abilities')->nullable();
            $table->date("created_at");
            $table->date("updated_at");
            $table->boolean('deleted') -> default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_role');
    }
};
