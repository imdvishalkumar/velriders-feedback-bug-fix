<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //DB::table('admin_users')->truncate();
        DB::table('admin_users')->insert([
            [
                'username' => 'admin',
                'password' => bcrypt('admin@velriders'),
                'role' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
