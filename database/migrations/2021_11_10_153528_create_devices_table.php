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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_name');
            $table->string('ip_address');
            $table->string('com_key');
            $table->string('soap_port');
            $table->string('udp_port');
            $table->string('serial_number')->nullable();
            $table->string('mac_address')->nullable();
            $table->integer('is_registration')->default(0)->comment('1 = For Registration');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
