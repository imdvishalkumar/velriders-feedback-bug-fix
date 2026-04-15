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
        Schema::create('app_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('os_type')->comment('1 = Android, 2 = IOS');
            $table->string('version')->nullable();
            $table->string('maintenance')->default(0)->nullable();
            $table->string('alert_title')->nullable();
            $table->string('alert_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_statuses');
    }
};
