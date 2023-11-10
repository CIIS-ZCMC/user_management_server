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
        Schema::create('request_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('profile_update_request_id');
            $table->foreign('profile_update_request_id')->references('id')->on('profile_update_requests');
            $table->string('attachment_url');
            $table->string('target_data');
            $table->string('new_data');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_details');
    }
};
