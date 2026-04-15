<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VehicleNightHours extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \DB::table('night_hours')->insert([
            [
                'title' => 'No night time booking (10PM-8AM)',
                'description' => 'New bookings are restricted from being allocated with start or end times between 10PM and 8AM. However, adjustments may be made...',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'No night time booking (10PM-10AM)',
                'description' => 'New bookings are restricted from being allocated with start or end times between 10PM and 10AM. However, adjustments may be made...',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'No night time booking (12PM-8AM)',
                'description' => 'New bookings are restricted from being allocated with start or end times between 12PM and 8AM. However, adjustments may be made...',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'No night time booking (12PM-6AM)',
                'description' => 'New bookings are restricted from being allocated with start or end times between 12PM and 6AM. However, adjustments may be made...',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
        ]);
    }
}
