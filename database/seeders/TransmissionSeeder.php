<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TransmissionSeeder extends Seeder
{
    public function run()
    {
        // Seed data for the transmissions table
        DB::table('vehicle_transmissions')->insert([
            [
                'name' => 'Automatic',
                'vehicle_type_id' => '1'
            ],
            [
                'name' => 'Manual',
                'vehicle_type_id' => '1'
            ],
            [
                'name' => 'iMT',
                'vehicle_type_id' => '1'
            ],
            // Add more transmission types as needed
        ]);
    }
}
