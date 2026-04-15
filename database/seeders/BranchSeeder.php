<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('branches')->insert([
            [
                'name' => 'Ahmedabad',
                'manager_name' => 'Anand Parmar',
                'address' => 'Shop No. 232, Someshwar Complex, Satellite Road, Opposite Iscon Emporio, Near Star Bazaar, BRTS Stop, Satellite, Ahmedabad, Gujarat 380015, India',
                'latitude' => 23.025751378774878,
                'longitude' => 72.52432671610318,
                'phone' => '9909927077',
                'email' => 'info@velriders.com',
                'opening_hours' => '24/7',
            ],
            [
                'name' => 'Jamnagar',
                'manager_name' => 'Yogesh Yadav',
                'address' => 'Shop No. 5, Dwarkesh Complex, Below Hotel Shivhari, Near Samarpan Over Bridge, Jamnagar, Gujarat 361006, India',
                'latitude' => 22.4652226,
                'longitude' => 70.039125,
                'phone' => '9909227077',
                'email' => 'info@velriders.com',
                'opening_hours' => '24/7',
            ],
            // Add more branch data as needed
        ]);
    }
}
