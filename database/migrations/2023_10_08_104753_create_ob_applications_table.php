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
        Schema::create('ob_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_profile_id')->unsigned();
            $table->foreign('employee_profile_id')->references('id')->on('employee_profiles')->onDelete('cascade');
            $table->datetime('date_from');
            $table->datetime('date_to');
            $table->string('time_from');
            $table->string('time_to');
            $table->string('status');
            $table->string('purpose')->nullable();
            $table->string('personal_order_file')->nullable();
            $table->string('personal_order_path')->nullable();
            $table->string('personal_order_size')->nullable();
            $table->string('certificate_of_appearance')->nullable();
            $table->string('certificate_of_appearance_path')->nullable();
            $table->string('certificate_of_appearance_size')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ob_applications');
    }
};
