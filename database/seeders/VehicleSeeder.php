<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Vehicle;
use App\Models\VehicleManufacturer;
use App\Models\VehicleModel;
use App\Models\VehicleCategory;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Branch: Ahmedabad 1  Jamnagar 2

        // Category: 1-Popular, 2-Hatchback, 3-Sedan, 4-SUV, 5-MUV, 6-Convertible, 7-Luxury Car, 8-Coupe, 9-Scooter, 10-Standard,
        // 11-Sport Bike, 12-Adventure Bike, 13-Touring Bike, 14-Cruiser Bike, 15-ATV

        // Models: 1-WagonR, 2-Baleno, 3-Swift, 4-Dzire, 5-Brezza, 6-Ciaz, 7-Ertiga, 8-i10, 9-i20, 10-Verna,
        //  11-Venue, 12-Altroz, 13-Nexon, 14-XUV 300, 15-Thar, 16-Scorpio, 17-Carens, 18-Amaze, 19-City, 20-Innova, 21-Innova Crysta, 22-320 D

        DB::table('vehicles')->insert([
            [
                'branch_id' => 2,
                'model_id' => 12,
                //'category_id' => 2,
                'year' => 2023,
                'description' => 'ALT GJ01WM9208	C P	M	2023	JAM', 
                'color' => 'White',
                'license_plate' => 'GJ01WM9208',
                'availability' => 1,
                'is_deleted' => 0,
                'rental_price' => 1400.00,
                'availability_calendar' => '{}',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'branch_id' => 2,
                'model_id' => 3,
                //'category_id' => 2,
                'year' => 2023,
                'description' => 'Swift GJ01KT5193 	C P	M	2023	JAM', 
                'color' => 'White',
                'license_plate' => 'GJ01KT5193',
                'availability' => 1,
                'is_deleted' => 0,
                'rental_price' => 1200.00,
                'availability_calendar' => '{}',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'branch_id' => 2,
                'model_id' => 3,
                //'category_id' => 2,
                'year' => 2023,
                'description' => 'Swift GJ01KT5119	P	A	2023	JAM', 
                'color' => 'Black',
                'license_plate' => 'GJ01KT5119',
                'availability' => 1,
                'is_deleted' => 0,
                'rental_price' => 1400.00,
                'availability_calendar' => '{}',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'branch_id' => 2,
                'model_id' => 9,
                //'category_id' => 2,
                'year' => 2013,
                'description' => 'I20 GJ05JD9889	P	M	2013	JAM', 
                'color' => 'White',
                'license_plate' => 'GJ05JD9889',
                'availability' => 1,
                'is_deleted' => 0,
                'rental_price' => 1200.00,
                'availability_calendar' => '{}',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'branch_id' => 2,
                'model_id' => 12,
                //'category_id' => 2,
                'year' => 2023,
                'description' => 'ALT GJ01WM9152	D S	M	2023	JAM', 
                'color' => 'White',
                'license_plate' => 'GJ01WM9152',
                'availability' => 1,
                'is_deleted' => 0,
                'rental_price' => 1400.00,
                'availability_calendar' => '{}',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'branch_id' => 2,
                'model_id' => 18,
                //'category_id' => 3,
                'year' => 2020,
                'description' => 'AMZ GJ10DE3148	P	M	2020	JAM', 
                'color' => 'White',
                'license_plate' => 'GJ10DE3148',
                'availability' => 1,
                'is_deleted' => 0,
                'rental_price' => 1199.00,
                'availability_calendar' => '{}',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'branch_id' => 2,
                'model_id' => 19,
                //'category_id' => 3,
                'year' => 2014,
                'description' => 'CITY GJ03FK2754	D	M	2014	JAM', 
                'color' => 'White',
                'license_plate' => 'GJ03FK2754',
                'availability' => 1,
                'is_deleted' => 0,
                'rental_price' => 1199.00,
                'availability_calendar' => '{}',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'branch_id' => 2,
                'model_id' => 20,
                //'category_id' => 5,
                'year' => 2014,
                'description' => 'INNOVA GJ01CX2244	D	M	2011	JAM', 
                'color' => 'White',
                'license_plate' => 'GJ01CX2244',
                'availability' => 1,
                'is_deleted' => 0,
                'rental_price' => 1199.00,
                'availability_calendar' => '{}',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'branch_id' => 2,
                'model_id' => 15,
                //'category_id' => 4,
                'year' => 2014,
                'description' => 'THAR  GJ 01 WP 0035	D 	M	2023	AMD', 
                'color' => 'White',
                'license_plate' => 'GJ01CX2244',
                'availability' => 1,
                'is_deleted' => 0,
                'rental_price' => 1199.00,
                'availability_calendar' => '{}',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'branch_id' => 2,
                'model_id' => 21,
                //'category_id' => 5,
                'year' => 2018,
                'description' => 'CRST GJ01HV4007	D	M	2018	JAM', 
                'color' => 'White',
                'license_plate' => 'GJ01CX2244',
                'availability' => 1,
                'is_deleted' => 0,
                'rental_price' => 1199.00,
                'availability_calendar' => '{}',
                'created_at' => now(),
                'updated_at' => now(),
            ]
            //
        ]);

        // Insert vehicle data
        // Vehicle::insert();
    }
}
