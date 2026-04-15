<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AppStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('app_statuses')->truncate();
        DB::table('app_statuses')->insert([
            [
                'os_type' => 1,
                'version' => '0.01',
                'maintenance' => false,
                'alert_title' => 'Good to go',
                'alert_message' => 'Good to go',
                'created_at' => now(),
                'updated_at' => now(),
            ], [
                'os_type' => 2,
                'version' => '0.01',
                'maintenance' => false,
                'alert_title' => 'Good to go',
                'alert_message' => 'Good to go',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
