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
        Schema::create('cto_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_profile_id')->unsigned();
            $table->foreign('employee_profile_id')->references('id')->on('employee_profiles')->onDelete('cascade');
            $table->datetime('date');
            $table->integer('applied_credits');
            $table->boolean('is_am');
            $table->boolean('is_pm');
            $table->string('purpose')->nullable();
            $table->text('remarks')->nullable();
            $table->string('status')->nullable();
            $table->unsignedBigInteger('recommending_officer')->unsigned()->nullable();
            $table->foreign('recommending_officer')->references('id')->on('employee_profiles')->onDelete('cascade');
            $table->unsignedBigInteger('approving_officer')->unsigned()->nullable();
            $table->foreign('approving_officer')->references('id')->on('employee_profiles')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cto_applications');
    }
};
