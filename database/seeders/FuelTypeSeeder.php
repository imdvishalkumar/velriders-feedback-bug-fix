<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FuelTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Seed data for the fuel_types table
        DB::table('vehicle_fuel_types')->insert([
            [
                'vehicle_type_id'=> '1',
                'name' => 'Petrol',
            ],
            [
                'vehicle_type_id'=> '1',
                'name' => 'Diesel',
            ],
            [
                'vehicle_type_id'=> '1',
                'name' => 'Petrol/CNG',
            ],
            [
                'vehicle_type_id'=> '1',
                'name' => 'EV',
            ],
        ]);
    }
}
