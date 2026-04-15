<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CompanyDetail;
use Illuminate\Support\Facades\DB;

class CompanyDetailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('company_details')->truncate();
        CompanyDetail::create([
            'address' => 'SHOP NO. 5 DWARKESH COMPLEX, BELOW SHIVHARI HOTEL, NEAR SAMARPAN OVER BRIDGE, JAMNAGAR - 361008, 24 - Gujarat',
            'phone' => '9909927077',
            'alt_phone' => '9909727077',
            'email' => 'SHAILESHCARBIKE@GMAIL.COM',
            'gst_no' => '24ABDCS1874K1Z4',
            'pan_no' => 'ABDCS1874K',
            'bank_name' => 'ICICI BANK',
            'bank_account_no' => '777705177771',
            'bank_ifsc_code' => 'ICIC0003486',
        ]);
    }
}
