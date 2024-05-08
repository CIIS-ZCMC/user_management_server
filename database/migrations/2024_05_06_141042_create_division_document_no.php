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
        Schema::create('division_document_no', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('division_id')->unsigned()->nullable();
            $table->foreign('division_id')->references('id')->on('divisions');
            $table->string('document_no');
            $table->string('revision_no');
            $table->string('document_title');
            $table->date('effective_date');
            $table->boolean('is_abroad')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('division_document_no');
    }
};
