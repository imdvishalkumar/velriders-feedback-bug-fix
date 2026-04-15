<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\VehicleModel;
use Illuminate\Database\Seeder;

class VehicleModelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $models = [
            ['name' => 'WagonR', 'manufacturer_id' => 1],
            ['name' => 'Baleno', 'manufacturer_id' => 1],
            ['name' => 'Swift', 'manufacturer_id' => 1],
            ['name' => 'Dzire', 'manufacturer_id' => 1],
            ['name' => 'Brezza', 'manufacturer_id' => 1],
            ['name' => 'Ciaz', 'manufacturer_id' => 1],
            ['name' => 'Ertiga', 'manufacturer_id' => 1],

            ['name' => 'i10', 'manufacturer_id' => 2],
            ['name' => 'i20', 'manufacturer_id' => 2],
            ['name' => 'Verna', 'manufacturer_id' => 2],
            ['name' => 'Venue', 'manufacturer_id' => 2],

            ['name' => 'Altroz', 'manufacturer_id' => 3],
            ['name' => 'Nexon', 'manufacturer_id' => 3],

            ['name' => 'XUV 300', 'manufacturer_id' => 4],
            ['name' => 'Thar', 'manufacturer_id' => 4],
            ['name' => 'Scorpio', 'manufacturer_id' => 4],

            ['name' => 'Carens', 'manufacturer_id' => 5],

            ['name' => 'Amaze', 'manufacturer_id' => 6],
            ['name' => 'City', 'manufacturer_id' => 6],
            ['name' => 'Innova', 'manufacturer_id' => 7],
            ['name' => 'Innova Crysta', 'manufacturer_id' => 7],
            ['name' => '320 D', 'manufacturer_id' => 15],
            ['name' => 'splendor', 'manufacturer_id' => 19],
            ['name' => 'HF Delex', 'manufacturer_id' => 19],
            ['name' => 'Pletina', 'manufacturer_id' => 20],
        ];

        // Insert the vehicle models into the database
        VehicleModel::insert($models);
    }
}
