<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('permissions')->insert([
            ['id' => '1', 'name' => 'admins', 'guard_name' => 'admin_web', 'title' => 'Admins', 'module_id' => 1, 'created_at' => Carbon::now()->format('Y-m-d H:i:s'), 'updated_at' => Carbon::now()->format('Y-m-d H:i:s')],
            ['id' => '2', 'name' => 'vehicle-types', 'guard_name' => 'admin_web', 'title' => 'Vehicle Types', 'module_id' => 2, 'created_at' => Carbon::now()->format('Y-m-d H:i:s'), 'updated_at' => Carbon::now()->format('Y-m-d H:i:s')],
            ['id' => '3', 'name' => 'vehicle-categories', 'guard_name' => 'admin_web', 'title' => 'Vehicle Categories', 'module_id' => 3, 'created_at' => Carbon::now()->format('Y-m-d H:i:s'), 'updated_at' => Carbon::now()->format('Y-m-d H:i:s')],
            ['id' => '4', 'name' => 'vehicle-fuel-types', 'guard_name' => 'admin_web', 'title' => 'Vehicle Fuel Types', 'module_id' => 4, 'created_at' => Carbon::now()->format('Y-m-d H:i:s'), 'updated_at' => Carbon::now()->format('Y-m-d H:i:s')],
            ['id' => '5', 'name' => 'vehicle-transmissions', 'guard_name' => 'admin_web', 'title' => 'Vehicle Transmissions', 'module_id' => 5, 'created_at' => Carbon::now()->format('Y-m-d H:i:s'), 'updated_at' => Carbon::now()->format('Y-m-d H:i:s')],
            ['id' => '6', 'name' => 'vehicle-features', 'guard_name' => 'admin_web', 'title' => 'Vehicle Features', 'module_id' => 6, 'created_at' => Carbon::now()->format('Y-m-d H:i:s'), 'updated_at' => Carbon::now()->format('Y-m-d H:i:s')],
            ['id' => '7', 'name' => 'vehicle-manufacturers', 'guard_name' => 'admin_web', 'title' => 'Vehicle Manufacturers', 'module_id' => 7, 'created_at' => Carbon::now()->format('Y-m-d H:i:s'), 'updated_at' => Carbon::now()->format('Y-m-d H:i:s')],
            ['id' => '8', 'name' => 'vehicle-models', 'guard_name' => 'admin_web', 'title' => 'Vehicle Models', 'module_id' => 8, 'created_at' => Carbon::now()->format('Y-m-d H:i:s'), 'updated_at' => Carbon::now()->format('Y-m-d H:i:s')],
            ['id' => '9', 'name' => 'vehicle', 'guard_name' => 'admin_web', 'title' => 'Vehicle', 'module_id' => 9, 'created_at' => Carbon::now()->format('Y-m-d H:i:s'), 'updated_at' => Carbon::now()->format('Y-m-d H:i:s')],
            ['id' => '10', 'name' => 'cities', 'guard_name' => 'admin_web', 'title' => 'Cities', 'module_id' => 10, 'created_at' => Carbon::now()->format('Y-m-d H:i:s'), 'updated_at' => Carbon::now()->format('Y-m-d H:i:s')],
            ['id' => '11', 'name' => 'branches', 'guard_name' => 'admin_web', 'title' => 'Branches', 'module_id' => 11, 'created_at' => Carbon::now()->format('Y-m-d H:i:s'), 'updated_at' => Carbon::now()->format('Y-m-d H:i:s')],
            ['id' => '12', 'name' => 'customers', 'guard_name' => 'admin_web', 'title' => 'Customers', 'module_id' => 12, 'created_at' => Carbon::now()->format('Y-m-d H:i:s'), 'updated_at' => Carbon::now()->format('Y-m-d H:i:s')],
            ['id' => '13', 'name' => 'customer-documents', 'guard_name' => 'admin_web', 'title' => 'Customer Documents', 'module_id' => 13, 'created_at' => Carbon::now()->format('Y-m-d H:i:s'), 'updated_at' => Carbon::now()->format('Y-m-d H:i:s')],
            ['id' => '14', 'name' => 'coupon-codes', 'guard_name' => 'admin_web', 'title' => 'Coupon Codes', 'module_id' => 14, 'created_at' => Carbon::now()->format('Y-m-d H:i:s'), 'updated_at' => Carbon::now()->format('Y-m-d H:i:s')],
            ['id' => '15', 'name' => 'booking-history', 'guard_name' => 'admin_web', 'title' => 'Booking History', 'module_id' => 15, 'created_at' => Carbon::now()->format('Y-m-d H:i:s'), 'updated_at' => Carbon::now()->format('Y-m-d H:i:s')],
            ['id' => '16', 'name' => 'customer-refund', 'guard_name' => 'admin_web', 'title' => 'Customer Refund', 'module_id' => 16, 'created_at' => Carbon::now()->format('Y-m-d H:i:s'), 'updated_at' => Carbon::now()->format('Y-m-d H:i:s')],
            ['id' => '17', 'name' => 'customer-canceled-refund', 'guard_name' => 'admin_web', 'title' => 'Customer Canceled Refund', 'module_id' => 17, 'created_at' => Carbon::now()->format('Y-m-d H:i:s'), 'updated_at' => Carbon::now()->format('Y-m-d H:i:s')],
            ['id' => '18', 'name' => 'booking-calculation-list', 'guard_name' => 'admin_web', 'title' => 'Booking Calculation List', 'module_id' => 18, 'created_at' => Carbon::now()->format('Y-m-d H:i:s'), 'updated_at' => Carbon::now()->format('Y-m-d H:i:s')],
            ['id' => '19', 'name' => 'trip-amount-calculation-list', 'guard_name' => 'admin_web', 'title' => 'Trip Amount Calculation List', 'module_id' => 19, 'created_at' => Carbon::now()->format('Y-m-d H:i:s'), 'updated_at' => Carbon::now()->format('Y-m-d H:i:s')],
            ['id' => '20', 'name' => 'reward-list', 'guard_name' => 'admin_web', 'title' => 'Reward List', 'module_id' => 20, 'created_at' => Carbon::now()->format('Y-m-d H:i:s'), 'updated_at' => Carbon::now()->format('Y-m-d H:i:s')],
            ['id' => '21', 'name' => 'send-emails', 'guard_name' => 'admin_web', 'title' => 'Send Emails', 'module_id' => 21, 'created_at' => Carbon::now()->format('Y-m-d H:i:s'), 'updated_at' => Carbon::now()->format('Y-m-d H:i:s')],
            ['id' => '22', 'name' => 'send-mobile-notification', 'guard_name' => 'admin_web', 'title' => 'Send Mobile Notification', 'module_id' => 22, 'created_at' => Carbon::now()->format('Y-m-d H:i:s'), 'updated_at' => Carbon::now()->format('Y-m-d H:i:s')],
            ['id' => '23', 'name' => 'policies-management', 'guard_name' => 'admin_web', 'title' => 'Policies Management', 'module_id' => 23, 'created_at' => Carbon::now()->format('Y-m-d H:i:s'), 'updated_at' => Carbon::now()->format('Y-m-d H:i:s')],
            ['id' => '24', 'name' => 'setting', 'guard_name' => 'admin_web', 'title' => 'Setting', 'module_id' => 24, 'created_at' => Carbon::now()->format('Y-m-d H:i:s'), 'updated_at' => Carbon::now()->format('Y-m-d H:i:s')],
            ['id' => '25', 'name' => 'add-booking', 'guard_name' => 'admin_web', 'title' => 'Add Booking', 'module_id' => 25, 'created_at' => Carbon::now()->format('Y-m-d H:i:s'), 'updated_at' => Carbon::now()->format('Y-m-d H:i:s')],
            ['id' => '26', 'name' => 'booking-transaction-history', 'guard_name' => 'admin_web', 'title' => 'Booking Transaction History', 'module_id' => 26, 'created_at' => Carbon::now()->format('Y-m-d H:i:s'), 'updated_at' => Carbon::now()->format('Y-m-d H:i:s')],
            ['id' => '27', 'name' => 'remaining-booking-penalties', 'guard_name' => 'admin_web', 'title' => 'Remaining Booking Transaction', 'module_id' => 27, 'created_at' => Carbon::now()->format('Y-m-d H:i:s'), 'updated_at' => Carbon::now()->format('Y-m-d H:i:s')],
            ['id' => '28', 'name' => 'admin-activity-log', 'guard_name' => 'admin_web', 'title' => 'Admin Activity Log', 'module_id' => 28, 'created_at' => Carbon::now()->format('Y-m-d H:i:s'), 'updated_at' => Carbon::now()->format('Y-m-d H:i:s')],
            ['id' => '29', 'name' => 'car-host-management', 'guard_name' => 'admin_web', 'title' => 'Car Host Management', 'module_id' => 29, 'created_at' => Carbon::now()->format('Y-m-d H:i:s'), 'updated_at' => Carbon::now()->format('Y-m-d H:i:s')],
            ['id' => '30', 'name' => 'booking-history-indetails', 'guard_name' => 'admin_web', 'title' => 'Booking History In Details', 'module_id' => 30, 'created_at' => Carbon::now()->format('Y-m-d H:i:s'), 'updated_at' => Carbon::now()->format('Y-m-d H:i:s')],
            ['id' => '31', 'name' => 'booking-history-operations', 'guard_name' => 'admin_web', 'title' => 'Booking History Operations (Update Start & End Km. & Preview page)', 'module_id' => 31, 'created_at' => Carbon::now()->format('Y-m-d H:i:s'), 'updated_at' => Carbon::now()->format('Y-m-d H:i:s')],
            ['id' => '32', 'name' => 'dashboard', 'guard_name' => 'admin_web', 'title' => 'Dashboard', 'module_id' => 32, 'created_at' => Carbon::now()->format('Y-m-d H:i:s'), 'updated_at' => Carbon::now()->format('Y-m-d H:i:s')],
            ['id' => '33', 'name' => 'end-journey', 'guard_name' => 'admin_web', 'title' => 'End Journey', 'module_id' => 33, 'created_at' => Carbon::now()->format('Y-m-d H:i:s'), 'updated_at' => Carbon::now()->format('Y-m-d H:i:s')],
            ['id' => '34', 'name' => 'payment-records', 'guard_name' => 'admin_web', 'title' => 'Payment Records', 'module_id' => 34, 'created_at' => Carbon::now()->format('Y-m-d H:i:s'), 'updated_at' => Carbon::now()->format('Y-m-d H:i:s')],
        ]);
    }
}
