<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\VehicleImage;

class VehicleImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $images = [
            [
                'vehicle_id' => 1,
                'image_url' => 'altroz_banner1.jpg',
                'image_type' => 'banner',
            ],
            [
                'vehicle_id' => 1,
                'image_url' => 'altroz_banner2.jpg',
                'image_type' => 'banner',
            ],
            [
                'vehicle_id' => 1,
                'image_url' => 'altroz_banner3.jpg',
                'image_type' => 'banner',
            ],
            [
                'vehicle_id' => 1,
                'image_url' => 'altroz_banner4.jpg',
                'image_type' => 'banner',
            ],
            [
                'vehicle_id' => 1,
                'image_url' => 'altroz_regular1.jpg',
                'image_type' => 'regular',
            ],
            [
                'vehicle_id' => 1,
                'image_url' => 'altroz_regular2.jpg',
                'image_type' => 'regular',
            ],
            [
                'vehicle_id' => 2,
                'image_url' => 'swift_banner1.jpg',
                'image_type' => 'banner',
            ],
            [
                'vehicle_id' => 2,
                'image_url' => 'swift_banner2.jpg',
                'image_type' => 'banner',
            ],
            [
                'vehicle_id' => 2,
                'image_url' => 'swift_banner3.jpg',
                'image_type' => 'banner',
            ],
            [
                'vehicle_id' => 2,
                'image_url' => 'swift_banner4.jpg',
                'image_type' => 'banner',
            ],
            [
                'vehicle_id' => 2,
                'image_url' => 'swift_regular1.jpg',
                'image_type' => 'regular',
            ],
            [
                'vehicle_id' => 2,
                'image_url' => 'swift_regular2.jpg',
                'image_type' => 'regular',
            ],
            [
                'vehicle_id' => 2,
                'image_url' => 'swift_regular3.jpg',
                'image_type' => 'regular',
            ],
            [
                'vehicle_id' => 3,
                'image_url' => 'swift_black_banner1.jpg',
                'image_type' => 'banner',
            ],
            [
                'vehicle_id' => 3,
                'image_url' => 'swift_black_banner2.jpg',
                'image_type' => 'banner',
            ],
            [
                'vehicle_id' => 3,
                'image_url' => 'swift_black_banner3.jpg',
                'image_type' => 'banner',
            ],
            [
                'vehicle_id' => 3,
                'image_url' => 'swift_black_banner4.jpg',
                'image_type' => 'banner',
            ],
            [
                'vehicle_id' => 3,
                'image_url' => 'swift_black_regular1.jpg',
                'image_type' => 'regular',
            ],
            [
                'vehicle_id' => 3,
                'image_url' => 'swift_black_regular2.jpg',
                'image_type' => 'regular',
            ],
            [
                'vehicle_id' => 3,
                'image_url' => 'swift_black_regular3.jpg',
                'image_type' => 'regular',
            ],
            //no images for i20
            [
                'vehicle_id' => 4,
                'image_url' => 'mahindra-thar-kappel.png',
                'image_type' => 'cutout',
            ],
            [
                'vehicle_id' => 5,
                'image_url' => 'altroz_banner1.jpg',
                'image_type' => 'banner',
            ],
            [
                'vehicle_id' => 5,
                'image_url' => 'altroz_banner2.jpg',
                'image_type' => 'banner',
            ],
            [
                'vehicle_id' => 5,
                'image_url' => 'altroz_banner3.jpg',
                'image_type' => 'banner',
            ],
            [
                'vehicle_id' => 5,
                'image_url' => 'altroz_banner4.jpg',
                'image_type' => 'banner',
            ],
            [
                'vehicle_id' => 5,
                'image_url' => 'altroz_regular1.jpg',
                'image_type' => 'regular',
            ],
            [
                'vehicle_id' => 5,
                'image_url' => 'altroz_regular2.jpg',
                'image_type' => 'regular',
            ],
            [
                'vehicle_id' => 6,
                'image_url' => 'amaze_banner1.jpg',
                'image_type' => 'banner',
            ],
            // no images for city
            [
                'vehicle_id' => 7,
                'image_url' => 'amaze_banner1.jpg',
                'image_type' => 'banner',
            ],
            [
                'vehicle_id' => 8,
                'image_url' => 'innova_banner1.jpg',
                'image_type' => 'banner',
            ],
            [
                'vehicle_id' => 8,
                'image_url' => 'innova_banner2.jpg',
                'image_type' => 'banner',
            ],
            [
                'vehicle_id' => 8,
                'image_url' => 'innova_banner3.jpg',
                'image_type' => 'banner',
            ],
            [
                'vehicle_id' => 8,
                'image_url' => 'innova_banner4.jpg',
                'image_type' => 'banner',
            ],
            [
                'vehicle_id' => 8,
                'image_url' => 'innova_regular1.jpg',
                'image_type' => 'regular',
            ],
            [
                'vehicle_id' => 8,
                'image_url' => 'innova_regular2.jpg',
                'image_type' => 'regular',
            ],
            [
                'vehicle_id' => 9,
                'image_url' => 'thar_banner1.jpg',
                'image_type' => 'banner',
            ],
            [
                'vehicle_id' => 9,
                'image_url' => 'thar_banner2.jpg',
                'image_type' => 'banner',
            ],
            [
                'vehicle_id' => 9,
                'image_url' => 'thar_banner3.jpg',
                'image_type' => 'banner',
            ],
            [
                'vehicle_id' => 9,
                'image_url' => 'thar_banner4.jpg',
                'image_type' => 'banner',
            ],
            [
                'vehicle_id' => 9,
                'image_url' => 'thar_regular1.jpg',
                'image_type' => 'regular',
            ],
            [
                'vehicle_id' => 9,
                'image_url' => 'thar_regular2.jpg',
                'image_type' => 'regular',
            ],
            [
                'vehicle_id' => 9,
                'image_url' => 'thar_regular3.jpg',
                'image_type' => 'regular',
            ],
            [
                'vehicle_id' => 9,
                'image_url' => 'thar_regular4.jpg',
                'image_type' => 'regular',
            ],
            [
                'vehicle_id' => 9,
                'image_url' => 'thar_regular5.jpg',
                'image_type' => 'regular',
            ],
            [
                'vehicle_id' => 9,
                'image_url' => 'thar_regular6.jpg',
                'image_type' => 'regular',
            ],
            [
                'vehicle_id' => 9,
                'image_url' => 'thar_regular7.jpg',
                'image_type' => 'regular',
            ],
            [
                'vehicle_id' => 9,
                'image_url' => 'thar_regular8.jpg',
                'image_type' => 'regular',
            ],
            [
                'vehicle_id' => 9,
                'image_url' => 'thar_regular9.jpg',
                'image_type' => 'regular',
            ],
            [
                'vehicle_id' => 9,
                'image_url' => 'thar_cutout.png',
                'image_type' => 'cutout',
            ],
            [
                'vehicle_id' => 10,
                'image_url' => 'crysta_cutout.png',
                'image_type' => 'cutout',
            ],

        ];

        foreach ($images as $image) {
            VehicleImage::create($image);
        }
    }
}
