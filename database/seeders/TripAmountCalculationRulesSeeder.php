<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TripAmountCalculationRule;

class TripAmountCalculationRulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $rules = [
            ['hours' => 4, 'multiplier' => 1],
            ['hours' => 8, 'multiplier' => 1.5],
            ['hours' => 12, 'multiplier' => 2],
            ['hours' => 24, 'multiplier' => 2.5],
            ['hours' => 72, 'multiplier' => 6.25],
            ['hours' => 168, 'multiplier' => 12.84],
            ['hours' => 360, 'multiplier' => 25.88],
            ['hours' => 672, 'multiplier' => 47.5],
        ];

        foreach ($rules as $rule) {
            TripAmountCalculationRule::create($rule);
        }
    }
}
