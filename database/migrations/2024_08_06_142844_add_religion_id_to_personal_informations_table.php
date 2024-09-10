<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('personal_informations', function (Blueprint $table) {
            $table->foreignId('religion_id')->nullable()->constrained('religions')->after('blood_type');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal_informations', function (Blueprint $table) {
            //
        });
    }
};
