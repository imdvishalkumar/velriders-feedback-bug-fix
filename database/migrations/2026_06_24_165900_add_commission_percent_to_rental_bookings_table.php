<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('rental_bookings', 'commission_percent')) {
            Schema::table('rental_bookings', function (Blueprint $table) {
                $table->tinyInteger('commission_percent')->nullable()->after('tax_rate');
            });

            // Populate existing bookings with their vehicle's current commission_percent
            DB::table('rental_bookings')
                ->join('vehicles', 'rental_bookings.vehicle_id', '=', 'vehicles.vehicle_id')
                ->update(['rental_bookings.commission_percent' => DB::raw('vehicles.commission_percent')]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rental_bookings', function (Blueprint $table) {
            $table->dropColumn('commission_percent');
        });
    }
};
