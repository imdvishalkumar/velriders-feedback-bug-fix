<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\VehicleManufacturer;

class VehicleManufacturerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        VehicleManufacturer::insert([
            [ 'vehicle_type_id' => '1' ,  'name' => 'Maruti Suzuki', 'logo' => 'maruti_suzuki_logo.png'],
            [ 'vehicle_type_id' => '1' , 'name' => 'Hyundai Motor India', 'logo' => 'hyundai_motor_india_logo.png'],
            [ 'vehicle_type_id' => '1' , 'name' => 'Tata Motors', 'logo' => 'tata_motors_logo.png'],
            [ 'vehicle_type_id' => '1' , 'name' => 'Mahindra & Mahindra', 'logo' => 'mahindra_mahindra_logo.png'],
            [ 'vehicle_type_id' => '1' , 'name' => 'Kia Motors India', 'logo' => 'kia_motors_india_logo.png'],
            [ 'vehicle_type_id' => '1' , 'name' => 'Honda Cars India', 'logo' => 'honda_cars_india_logo.png'],
            [ 'vehicle_type_id' => '1' , 'name' => 'Toyota Kirloskar Motor', 'logo' => 'toyota_kirloskar_motor_logo.png'],
            [ 'vehicle_type_id' => '1' , 'name' => 'Ford India', 'logo' => 'ford_india_logo.png'],
            [ 'vehicle_type_id' => '1' , 'name' => 'Volkswagen India', 'logo' => 'volkswagen_india_logo.png'],
            [ 'vehicle_type_id' => '1' , 'name' => 'Renault India', 'logo' => 'renault_india_logo.png'],
            [ 'vehicle_type_id' => '1' , 'name' => 'Nissan Motor India', 'logo' => 'nissan_motor_india_logo.png'],
            [ 'vehicle_type_id' => '1' , 'name' => 'Skoda Auto India', 'logo' => 'skoda_auto_india_logo.png'],
            [ 'vehicle_type_id' => '1' , 'name' => 'MG Motor India', 'logo' => 'mg_motor_india_logo.png'],
            [ 'vehicle_type_id' => '1' , 'name' => 'Mercedes-Benz India', 'logo' => 'mercedes_benz_india_logo.png'],
            [ 'vehicle_type_id' => '1' , 'name' => 'Audi India', 'logo' => 'audi_india_logo.png'],
            [ 'vehicle_type_id' => '1' , 'name' => 'BMW India', 'logo' => 'bmw_india_logo.png'],
            [ 'vehicle_type_id' => '1' , 'name' => 'Jeep India', 'logo' => 'jeep_india_logo.png'],
            [ 'vehicle_type_id' => '1' , 'name' => 'Volvo Cars India', 'logo' => 'volvo_cars_india_logo.png'],
            [ 'vehicle_type_id' => '2' , 'name' => 'Hero MotoCorp', 'logo' => 'hero_motocorp_logo.png'],
            [ 'vehicle_type_id' => '2' , 'name' => 'Bajaj Auto', 'logo' => 'bajaj_auto_logo.png'],
            [ 'vehicle_type_id' => '2' , 'name' => 'TVS Motor Company', 'logo' => 'tvs_motor_company_logo.png'],
            [ 'vehicle_type_id' => '2' , 'name' => 'Royal Enfield', 'logo' => 'royal_enfield_logo.png'],
            [ 'vehicle_type_id' => '2' , 'name' => 'Yamaha Motor India', 'logo' => 'yamaha_motor_india_logo.png'],
            [ 'vehicle_type_id' => '2' , 'name' => 'Suzuki Motorcycle India', 'logo' => 'suzuki_motorcycle_india_logo.png'],
            [ 'vehicle_type_id' => '2' , 'name' => 'Honda Motorcycle & Scooter India', 'logo' => 'honda_motorcycle_scooter_india_logo.png'],
            [ 'vehicle_type_id' => '2' , 'name' => 'Kawasaki Motors India', 'logo' => 'kawasaki_motors_india_logo.png'],
            [ 'vehicle_type_id' => '2' , 'name' => 'Harley-Davidson India', 'logo' => 'harley_davidson_india_logo.png'],
            [ 'vehicle_type_id' => '2' , 'name' => 'KTM India', 'logo' => 'ktm_india_logo.png'],
            [ 'vehicle_type_id' => '2' , 'name' => 'BMW Motorrad India', 'logo' => 'bmw_motorrad_india_logo.png'],
        ]);
    }
}
