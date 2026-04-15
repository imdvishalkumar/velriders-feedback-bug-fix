<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('settings')->truncate();
        DB::table('settings')->insert([
            [
                'show_all_vehicle' => 0,
                'booking_gap' => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
