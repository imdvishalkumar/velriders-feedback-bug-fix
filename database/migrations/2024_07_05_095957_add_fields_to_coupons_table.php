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
        Schema::table('coupons', function (Blueprint $table) {
            $table->tinyInteger('single_use_per_customer')->default(NULL)->comment('1 = Yes, 0 = No')->after('is_deleted');
            $table->tinyInteger('one_time_use_among_all')->default(NULL)->comment('1 = Yes, 0 = No')->after('single_use_per_customer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            //
        });
    }
};
