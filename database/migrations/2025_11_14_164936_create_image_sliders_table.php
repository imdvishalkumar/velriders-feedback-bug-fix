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
        Schema::create('image_sliders', function (Blueprint $table) {
            $table->id();
            $table->string('banner_img')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->tinyInteger('banner_for')->default(null)->comment('1 for Customer 2 for Host');
            $table->tinyInteger('is_active')->default(0)->comment('0 for In active 1 for Active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('image_sliders');
    }
};
