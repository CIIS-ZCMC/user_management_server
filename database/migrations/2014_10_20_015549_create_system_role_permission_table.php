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
        Schema::create('system_role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('action');
            $table->string('module');
            $table->boolean('active')->default(FALSE);
            $table->unsignedBigInteger('permission_id');
            $table->foreign('permission_id')->references('id')->on('permissions');
            $table->unsignedBigInteger('system_role_id');
            $table->foreign('system_role_id')->references('id')->on('system_roles');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_role_permissions');
    }
};
