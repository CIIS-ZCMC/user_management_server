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
        Schema::create('systems', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->string('domain', 360);
            $table->boolean('server-maintainance')->default(FALSE);
            $table->boolean('server-down')->default(FALSE);
            $table->boolean('server-active')->default(TRUE);
            $table->datetime('created_at')->default(now());
            $table->datetime('updated_at')->default(now());
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('systems');
    }
};
