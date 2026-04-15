<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'first_name' => 'Mohit',
                'last_name' => 'Paddhariya',
                'email' => 'mohit@imobiledesigns.com',
                'profile_picture' => 'default.jpg',
                'country_code' => '+91',
                'city_id' => 1,
                'phone_number' => '8401815038',
                'is_phone_verified' => 0,
                'otp' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'first_name' => 'Kaushal',
                'last_name' => 'Rola',
                'email' => 'kaushalrola@gmail.com',
                'profile_picture' => 'default.jpg',
                'country_code' => '+91',
                'city_id' => 1,
                'phone_number' => '8090100711',
                'is_phone_verified' => 0,
                'otp' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
