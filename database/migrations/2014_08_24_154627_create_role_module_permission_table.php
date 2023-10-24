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
        Schema::create('role_module_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('module_permission_id');
            $table->foreign('module_permission_id')->references('id')->on('module_permissions');
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
        Schema::dropIfExists('role_module_permissions');
    }
};
