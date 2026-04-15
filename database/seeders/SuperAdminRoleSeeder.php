<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AdminUser;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class SuperAdminRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = AdminUser::find(1);
        $permission_moduleids = config('global_values.superadmin_permissions');
        $admin->syncPermissions($permission_moduleids);     
    }
}