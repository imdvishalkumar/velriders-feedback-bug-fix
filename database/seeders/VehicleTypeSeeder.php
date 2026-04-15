<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VehicleTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('vehicle_types')->insert([
            ['name' => 'Car', 'convenience_fees' => 100.00],
            ['name' => 'Bike', 'convenience_fees' => 50.00],
            // ['name' => 'Bus'],
            // ['name' => 'Truck'],
            // ['name' => 'Train'],
        ]);
    }
}
