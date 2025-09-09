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
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('republic_act');
            $table->string('code');
            $table->text('description')->nullable();
            $table->double('period')->nullable();
            $table->integer('before')->nullable();
            $table->integer('after')->nullable();
            $table->text('file_date');
            $table->integer('file_after')->nullable();
            $table->integer('file_before')->nullable();
            $table->float('month_value');
            $table->float('annual_credit');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_special')->default(false);
            $table->boolean('is_country')->default(false);
            $table->boolean('is_illness')->default(false);
            $table->boolean('is_study')->default(false);
            $table->boolean('is_days_recommended')->default(false);
            $table->boolean('is_other')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
