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
        Schema::table('vehicle_documents', function (Blueprint $table) {
            $table->tinyInteger('image_type')->default(0)->comment('1 = Front, 2 = Back')->after('document_image_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_documents', function (Blueprint $table) {
            //
        });
    }
};
