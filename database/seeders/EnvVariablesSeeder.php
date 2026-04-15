<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EnvVariablesSeeder extends Seeder
{
    public function run()
    {
        DB::table('env_variables')->truncate();
        $envVariables = [];

        DB::table('env_variables')->insert($envVariables);
    }
}
