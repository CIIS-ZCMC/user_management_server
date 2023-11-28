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
<<<<<<<< HEAD:database/migrations/2022_11_10_155715_create_time_adjusments_table.php
        Schema::create('time_adjusments', function (Blueprint $table) {
========
        Schema::create('leave_attachments', function (Blueprint $table) {
>>>>>>>> UMIS-009:database/migrations/2023_11_14_162029_create_leave_attachments_table.php
            $table->id();
            $table->unsignedBigInteger('leave_type_id')->unsigned();
            $table->foreign('leave_type_id')->references('id')->on('leave_types')->onDelete('cascade');
            $table->string('file_name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
<<<<<<<< HEAD:database/migrations/2022_11_10_155715_create_time_adjusments_table.php
        Schema::dropIfExists('time_adjusments');
========
        Schema::dropIfExists('leave_attachments');
>>>>>>>> UMIS-009:database/migrations/2023_11_14_162029_create_leave_attachments_table.php
    }
};
