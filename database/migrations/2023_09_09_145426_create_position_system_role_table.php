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
        Schema::create('position_system_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employment_position_id')->unsigned();
            $table->foreign('employment_position_id')->references('id')->on('employment_positions');
            $table->unsignedBigInteger('system_role_id')->unsigned();
            $table->foreign('system_role_id')->references('id')->on('system_roles');
            $table->datetime('in_active')->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE position_system_roles AUTO_INCREMENT = 10000');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('position_system_roles');
    }
};
