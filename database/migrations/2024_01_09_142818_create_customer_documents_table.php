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
        Schema::create('customer_documents', function (Blueprint $table) {
            $table->id('document_id');
            $table->unsignedBigInteger('customer_id');
            $table->enum('document_type', ['govtid', 'dl']);
            $table->string('id_number', 255);
            $table->date('expiry_date')->nullable();
            $table->enum('is_approved', ['approved', 'rejected', 'awaiting_approval'])->default('awaiting_approval');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->string('document_image_url', 255)->nullable();
            $table->string('document_back_image_url', 255)->nullable();
            $table->string('custom_rejection_message', 510)->nullable();
            $table->unsignedBigInteger('rejection_message_id')->nullable();        
            $table->enum('vehicle_type', ['car', 'bike', '','car/bike'])->nullable();
            $table->boolean('is_blocked')->default(false);
            $table->timestamps();

            // Define foreign key constraints
            $table->foreign('customer_id')->references('customer_id')->on('customers')->onDelete('cascade');
            $table->foreign('approved_by')->references('admin_id')->on('admin_users')->onDelete('cascade');
            $table->foreign('rejection_message_id')->references('id')->on('rejection_messages');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_documents');
    }
};
