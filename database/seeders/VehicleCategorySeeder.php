<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VehicleCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Seed data for the vehicle_categories table
        DB::table('vehicle_categories')->insert([
            [
                'vehicle_type_id' => 1, // Car
                'name' => 'Popular - Cars',
                'icon' => 'popular.svg',
                'sort' => '1',
            ],
            [
                'vehicle_type_id' => 1, // Car
                'name' => 'Hatchback',
                'icon' => 'hatchback-icon.svg',
                'sort' => '3',
            ],
            [
                'vehicle_type_id' => 1, // Car
                'name' => 'Sedan',
                'icon' => 'sedan-icon.svg',
                'sort' => '4',
            ],
            [
                'vehicle_type_id' => 1, // Car
                'name' => 'SUV',
                'icon' => 'suv-icon.svg',
                'sort' => '5',
            ],
            [
                'vehicle_type_id' => 1, // Car
                'name' => 'MUV',
                'icon' => 'muv-icon.svg',
                'sort' => '6',
            ],
            [
                'vehicle_type_id' => 1, // Car
                'name' => 'Convertible',
                'icon' => 'convertible-icon.svg',
                'sort' => '7',
            ],
            [
                'vehicle_type_id' => 1, // Car
                'name' => 'Luxury Car',
                'icon' => 'luxury-car-icon.svg',
                'sort' => '8',
            ],
            [
                'vehicle_type_id' => 1, // Car
                'name' => 'Coupe',
                'icon' => 'coupe-icon.svg',
                'sort' => '9',
            ],
            // Add more vehicle categories as needed
            [
                'vehicle_type_id' => 2, // Car
                'name' => 'Popular - Bikes',
                'icon' => 'popular.svg',
                'sort' => '10',
            ],
            [
                'vehicle_type_id' => 2, // Bike
                'name' => 'Scooter',
                'icon' => 'scooter-icon.svg',
                'sort' => '12',
            ],
            [
                'vehicle_type_id' => 2, // Bike
                'name' => 'Standard',
                'icon' => 'standard-bike-icon.svg',
                'sort' => '13',
            ],
            [
                'vehicle_type_id' => 2, // Bike
                'name' => 'Sport Bike',
                'icon' => 'sport-bike-icon.svg',
                'sort' => '14',
            ],
            [
                'vehicle_type_id' => 2, // Bike
                'name' => 'Adventure Bike',
                'icon' => 'adventure-bike-icon.svg',
                'sort' => '15',
            ],
            [
                'vehicle_type_id' => 2, // Bike
                'name' => 'Touring Bike',
                'icon' => 'touring-bike-icon.svg',
                'sort' => '16',
            ],
            [
                'vehicle_type_id' => 2, // Bike
                'name' => 'Cruiser Bike',
                'icon' => 'cruiser-bike-icon.svg',
                'sort' => '17',
            ],
            [
                'vehicle_type_id' => 2, // Bike
                'name' => 'ATV',
                'icon' => 'atv-bike-icon.svg',
                'sort' => '18',
            ],
            [
                'vehicle_type_id' => 1, // Bike
                'name' => 'All',
                'icon' => 'all.svg',
                'sort' => '2',
            ],
            [
                'vehicle_type_id' => 2, // Bike
                'name' => 'All',
                'icon' => 'all.svg',
                'sort' => '11',
            ],
        ]);
    }
}
