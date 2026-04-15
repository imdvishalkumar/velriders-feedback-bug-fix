<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\VehicleFeatureMapping;
use App\Models\Vehicle;

class VehicleFeatureMappingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some vehicles to associate features with
        $vehicles = Vehicle::inRandomOrder()->take(20)->pluck('vehicle_id');

        // Get some random features
        $features = \App\Models\VehicleFeature::inRandomOrder()->take(5)->pluck('feature_id');

        // Create mappings for each vehicle and feature
        foreach ($vehicles as $vehicle) {
            foreach ($features as $feature) {
                VehicleFeatureMapping::create([
                    'vehicle_id' => $vehicle,
                    'feature_id' => $feature,
                ]);
            }
        }
    }
}
