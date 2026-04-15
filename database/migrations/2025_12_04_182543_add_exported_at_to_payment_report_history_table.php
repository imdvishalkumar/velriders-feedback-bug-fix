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
        Schema::table('payment_report_history', function (Blueprint $table) {
            $table->timestamp('exported_at')->nullable()->after('is_completed')->comment('Timestamp when export was completed');
            $table->json('export_filters')->nullable()->after('exported_at')->comment('JSON containing export filters: start_date, end_date, etc.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_report_history', function (Blueprint $table) {
            $table->dropColumn(['exported_at', 'export_filters']);
        });
    }
};
