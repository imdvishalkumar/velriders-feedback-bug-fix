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
        Schema::create('vehicle_document_temps', function (Blueprint $table) {
            $table->id('document_id');
            $table->unsignedBigInteger('vehicle_id');
            $table->string('document_type')->nullable();
            $table->string('id_number', 255)->nullable();
            $table->date('expiry_date');
            $table->tinyInteger('is_approved');
            $table->unsignedBigInteger('approved_by');
            $table->string('document_image_url', 255)->nullable();
            $table->timestamps();

            // Define foreign key constraints
            $table->foreign('vehicle_id')->references('vehicle_id')->on('vehicles')->onDelete('cascade');
            $table->foreign('approved_by')->references('admin_id')->on('admin_users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_document_temps');
    }
};
