<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\VehicleFeature;

class VehicleFeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        VehicleFeature::insert([
            ['name' => 'GPS Navigation System', 'icon' => 'gps-navigation-icon.svg'],
            ['name' => 'Bluetooth Connectivity', 'icon' => 'bluetooth-icon.svg'],
            ['name' => 'Leather Seats', 'icon' => 'leather-seats-icon.svg'],
            // Add more vehicle features here as needed
        ]);
    }
}
