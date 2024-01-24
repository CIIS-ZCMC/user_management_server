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
        Schema::create('official_business_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_profile_id')->unsigned();
            $table->foreign('employee_profile_id')->references('id')->on('employee_profiles')->onDelete('cascade');
            $table->date('date_from');
            $table->date('date_to');
            $table->time('time_from');
            $table->time('time_to');
            $table->string('purpose');
            $table->string('status')->default('applied');
            $table->string('personal_order_file')->nullable();
            $table->string('personal_order_path')->nullable();
            $table->string('personal_order_size')->nullable();
            $table->string('certificate_of_appearance')->nullable();
            $table->string('certificate_of_appearance_path')->nullable();
            $table->string('certificate_of_appearance_size')->nullable();

            $table->unsignedBigInteger('hrmo_officer')->nullable();
            $table->foreign('hrmo_officer')->references('id')->on('employee_profiles');

            $table->unsignedBigInteger('recommending_officer')->nullable();
            $table->foreign('recommending_officer')->references('id')->on('employee_profiles');

            $table->unsignedBigInteger('approving_officer')->nullable();
            $table->foreign('approving_officer')->references('id')->on('employee_profiles');

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
