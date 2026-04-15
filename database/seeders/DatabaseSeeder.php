<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(BranchSeeder::class);
        $this->call(VehicleTypeSeeder::class);
        $this->call(VehicleCategorySeeder::class);
        $this->call(PolicySeeder::class);
        $this->call(VehicleFeatureSeeder::class);
        $this->call(VehicleManufacturerSeeder::class);
        $this->call(VehicleModelSeeder::class);
        $this->call(VehicleSeeder::class);
        $this->call(VehicleFeatureMappingSeeder::class);
        $this->call(VehicleImageSeeder::class);
        $this->call(AdminUserSeeder::class);
        $this->call(CouponSeeder::class);
        $this->call(RejectionReasonsTableSeeder::class);
        $this->call(TripAmountCalculationRulesSeeder::class);
        $this->call(FuelTypeSeeder::class);
        $this->call(TransmissionSeeder::class);
        $this->call(VehiclePropertySeeder::class);
        $this->call(CompanyDetailSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(PermissionSeeder::class);
        $this->call(SuperAdminRoleSeeder::class);
        $this->call(AppStatusSeeder::class);
    }
}
