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
        Schema::create('system', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('domain');
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
        Schema::dropIfExists('system');
    }
};
