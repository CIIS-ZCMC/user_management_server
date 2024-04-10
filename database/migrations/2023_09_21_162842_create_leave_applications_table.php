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
        Schema::create('leave_applications', function (Blueprint $table) {
            $table->id();
            $table->datetime('date_from')->nullable();
            $table->datetime('date_to')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->boolean('is_outpatient')->default(false);
            $table->string('illness')->nullable();
            $table->boolean('is_masters')->default(false);
            $table->boolean('is_board')->default(false);
            $table->boolean('is_commutation')->default(false);
            $table->string('applied_credits')->nullable();
            $table->string('status')->nullable();
            $table->text('remarks')->nullable();
            $table->boolean('without_pay')->default(false);
            $table->text('reason')->nullable();
            $table->boolean('is_printed')->default(false);
            $table->datetime('print_datetime')->nullable();
            $table->unsignedBigInteger('employee_profile_id')->unsigned();
            $table->foreign('employee_profile_id')->references('id')->on('employee_profiles')->onDelete('cascade');
            $table->unsignedBigInteger('leave_type_id')->unsigned();
            $table->foreign('leave_type_id')->references('id')->on('leave_types')->onDelete('cascade');
            $table->unsignedBigInteger('hrmo_officer')->unsigned()->nullable();
            $table->foreign('hrmo_officer')->references('id')->on('employee_profiles')->onDelete('cascade');
            $table->unsignedBigInteger('recommending_officer')->unsigned()->nullable();
            $table->foreign('recommending_officer')->references('id')->on('employee_profiles')->onDelete('cascade');
            $table->unsignedBigInteger('approving_officer')->unsigned()->nullable();
            $table->foreign('approving_officer')->references('id')->on('employee_profiles')->onDelete('cascade');
            $table->unsignedBigInteger('employee_oic_id')->unsigned()->nullable();
            $table->foreign('employee_oic_id')->references('id')->on('employee_profiles')->onDelete('no action');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_applications');
    }
};
