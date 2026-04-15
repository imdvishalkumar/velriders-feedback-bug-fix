<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Coupon;


class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed percentage-based coupon
        Coupon::create([
            'customer_id' => 0,
            'code' => 'PERCENT10',
            'type' => 'percentage',
            'percentage_discount' => 10,
            'max_discount_amount' => 1000,
            'valid_from' => now(),
            'valid_to' => now()->addMonths(6), // Example: valid for 6 months
            'is_active' => true,
            'single_use_per_customer' => 0,
            'one_time_use_among_all' => 0,
            'is_show' => 0,
        ]);

        // Seed fixed amount coupon
        Coupon::create([
            'customer_id' => 0,
            'code' => 'FIXED100',
            'type' => 'fixed',
            'fixed_discount_amount' => 100,
            'valid_from' => now(),
            'valid_to' => now()->addMonths(6), // Example: valid for 6 months
            'is_active' => true,
            'single_use_per_customer' => 0,
            'one_time_use_among_all' => 0,
            'is_show' => 0,
        ]);
    }
}
