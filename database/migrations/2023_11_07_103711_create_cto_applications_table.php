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
            $table->string('time_from');
            $table->string('time_to');
            $table->string('purpose');
            $table->text('remarks')->nullable();
            $table->string('status')->nullable();
            $table->unsignedBigInteger('hrmo_officer')->unsigned()->nullable();
            $table->foreign('hrmo_officer')->references('id')->on('employee_profiles')->onDelete('cascade');
            $table->unsignedBigInteger('recommending_officer')->unsigned()->nullable();
            $table->foreign('recommending_officer')->references('id')->on('employee_profiles')->onDelete('cascade');
            $table->unsignedBigInteger('approving')->unsigned()->nullable();
            $table->foreign('approving')->references('id')->on('employee_profiles')->onDelete('cascade');
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
