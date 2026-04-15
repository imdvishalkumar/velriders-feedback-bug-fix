<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\VehicleProperty;

class VehiclePropertySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        //1-Petrol, 2-Diesel, 3-Petrol/CNG, 4-EV
        //1-Automatic, 2-Manual, 3-iMT

        VehicleProperty::insert([
            [
                'vehicle_id' => 1,
                'mileage' => 26,
                'fuel_type_id' => 3,
                'transmission_id' => 2,
                'seating_capacity' => 5,
                'engine_cc' => 1199,
                'fuel_capacity' => 37,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'vehicle_id' => 2,
                'mileage' => 30,
                'fuel_type_id' => 3,
                'transmission_id' => 2,
                'seating_capacity' => 5,
                'engine_cc' => 1197,
                'fuel_capacity' => 37,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'vehicle_id' => 3,
                'mileage' => 22,
                'fuel_type_id' => 1,
                'transmission_id' => 1,
                'seating_capacity' => 5,
                'engine_cc' => 1197,
                'fuel_capacity' => 37,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'vehicle_id' => 4,
                'mileage' => 18.6,
                'fuel_type_id' => 1,
                'transmission_id' => 2,
                'seating_capacity' => 5,
                'engine_cc' => 1197,
                'fuel_capacity' => 45,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'vehicle_id' => 5,
                'mileage' => 24,
                'fuel_type_id' => 2,
                'transmission_id' => 2,
                'seating_capacity' => 5,
                'engine_cc' => 1497,
                'fuel_capacity' => 45,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'vehicle_id' => 6,
                'mileage' => 18,
                'fuel_type_id' => 1,
                'transmission_id' => 2,
                'seating_capacity' => 5,
                'engine_cc' => 1199,
                'fuel_capacity' => 35,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'vehicle_id' => 7,
                'mileage' => 18,
                'fuel_type_id' => 2,
                'transmission_id' => 2,
                'seating_capacity' => 5,
                'engine_cc' => 1498,
                'fuel_capacity' => 40,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'vehicle_id' => 8,
                'mileage' => 18,
                'fuel_type_id' => 2,
                'transmission_id' => 2,
                'seating_capacity' => 5,
                'engine_cc' => 1498,
                'fuel_capacity' => 40,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'vehicle_id' => 9,
                'mileage' => 18,
                'fuel_type_id' => 2,
                'transmission_id' => 2,
                'seating_capacity' => 5,
                'engine_cc' => 1498,
                'fuel_capacity' => 40,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'vehicle_id' => 10,
                'mileage' => 18,
                'fuel_type_id' => 2,
                'transmission_id' => 2,
                'seating_capacity' => 5,
                'engine_cc' => 1498,
                'fuel_capacity' => 40,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
