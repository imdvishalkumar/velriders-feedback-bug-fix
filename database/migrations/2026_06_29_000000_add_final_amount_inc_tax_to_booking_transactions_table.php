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
        if (Schema::hasTable('booking_transactions')) {
            Schema::table('booking_transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('booking_transactions', 'final_amount_inc_tax')) {
                    $table->decimal('final_amount_inc_tax', 10, 2)->nullable()->after('final_amount');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('booking_transactions')) {
            Schema::table('booking_transactions', function (Blueprint $table) {
                if (Schema::hasColumn('booking_transactions', 'final_amount_inc_tax')) {
                    $table->dropColumn('final_amount_inc_tax');
                }
            });
        }
    }
};
