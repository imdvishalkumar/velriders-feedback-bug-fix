<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\RejectionReason;

class RejectionReasonsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rejectionReasons = [
            ['reason' => 'The document provided is illegible and cannot be verified.'],
            ['reason' => 'The document does not meet our formatting requirements.'],
            ['reason' => 'The identification document has expired and is no longer valid.'],
            ['reason' => 'The document lacks a required signature or official seal.'],
            ['reason' => 'The information provided does not match our records.'],
            ['reason' => 'The image quality is insufficient for verification.'],
            ['reason' => 'A document with the same information has already been submitted.']
        ];
        
        RejectionReason::insert($rejectionReasons);
        
    }
}
