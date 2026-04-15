<?php

namespace App\Http\Controllers\FrontAppApis\V1;

use App\Http\Controllers\Controller; 
use App\Models\{RentalReview, Vehicle, Branch, CarHostPickupLocation, VehiclePriceDetail, CarEligibility, OfferDate, TripAmountCalculationRule, RentalBooking};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HomeController extends Controller
{
    public function homescreen()
    {
        $response = [
            'image_slider' => [
                [
                    'images' => 'https://velriders.com/images/home_screen/Banner-6.jpg',
                ],
                [
                    'images' => 'https://velriders.com/images/home_screen/Banner-7.jpg',
                ],
                [
                    'images' => 'https://velriders.com/images/home_screen/Banner-8.jpg',
                ],
                [
                    'images' => 'https://velriders.com/images/home_screen/Banner5.jpg',
                ],
            ],
            'why_velriders' => [
                [
                    'icon' => 'https://velriders.com/images/home_screen/icon.png',
                    'label' => 'Wide Range of Vehicles',
                    'description' => 'Choose from a variety of cars and bikes – luxury, SUVs, or fuel-efficient options.',
                ],
                [
                    'icon' => 'https://velriders.com/images/home_screen/icon.png',
                    'label' => 'Affordable Rates',
                    'description' => 'Enjoy competitive pricing with no hidden charges, ensuring value for your money.',
                ],
                [
                    'icon' => 'https://velriders.com/images/home_screen/icon.png',
                    'label' => 'Convenient Locations',
                    'description' => 'Available in multiple cities across Gujarat, including Jamnagar, Ahmedabad, and soon Porbandar.',
                ],
                [
                    'icon' => 'https://velriders.com/images/home_screen/icon.png',
                    'label' => 'Easy Booking Process',
                    'description' => 'Rent a vehicle effortlessly through our app, available on Play Store and App Store.',
                ],
                [
                    'icon' => 'https://velriders.com/images/home_screen/icon.png',
                    'label' => '24/7 Customer Support',
                    'description' => 'Our support team is always ready to assist you with queries or emergencies.',
                ],
                [
                    'icon' => 'https://velriders.com/images/home_screen/icon.png',
                    'label' => 'Flexible Rental Plans',
                    'description' => 'Choose rental durations that fit your schedule, from a few hours to several days.',
                ],
                [
                    'icon' => 'https://velriders.com/images/home_screen/icon.png',
                    'label' => 'Welcome Offer',
                    'description' => 'Enjoy 20% off on your first ride with promo code VELNEW20.',
                ],
                [
                    'icon' => 'https://velriders.com/images/home_screen/icon.png',
                    'label' => 'Well-Maintained Vehicles',
                    'description' => 'All our vehicles are regularly serviced and sanitized for a safe and smooth ride.',
                ],
                [
                    'icon' => 'https://velriders.com/images/home_screen/icon.png',
                    'label' => 'Trust and Reliability',
                    'description' => 'Gujarat\'s first and most trusted car and bike rental app, offering quality and transparency.',
                ],
                [
                    'icon' => 'https://velriders.com/images/home_screen/icon.png',
                    'label' => 'Customer Satisfaction',
                    'description' => 'We prioritize your experience, ensuring hassle-free and enjoyable rentals every time.',
                ],
            ],
            'faq' => [
                [
                    'question' => 'How to book your ride?',
                    'answer' => 'Select your city, preferred travel date & time, choose a vehicle, select kilometer type (limited/unlimited), apply a coupon code (if applicable), choose a payment method (GPay, Debit Card, Credit Card, EMI), and make the payment to confirm your booking.',
                ],
                [
                    'question' => 'How to upload documents?',
                    'answer' => 'Upload the front & back pictures of your valid driving license and a government-issued ID (Aadhar Card, Voter ID, or Passport). Enter the correct details and wait for approval. Once approved, confirm your email to complete the process.',
                ],
                [
                    'question' => 'Is long-term booking possible?',
                    'answer' => 'Yes, you can book a vehicle for a minimum of 4 hours and extend it as per your requirement.',
                ],
                [
                    'question' => 'How can I start my journey?',
                    'answer' => 'Reach the vehicle location, inspect it inside and out, enter the Start OTP, upload at least 5 pictures covering the vehicle’s interior, exterior, and odometer, enter the kilometer reading, and you are ready to drive.',
                ],
                [
                    'question' => 'How to extend the booking?',
                    'answer' => 'You can extend your booking 10 minutes before it ends via the app under the "My Booking" → "Running" page by selecting the extension time & date and paying the extra amount.',
                ],
                [
                    'question' => 'What happens if I cancel my booking?',
                    'answer' => 'You can cancel your booking if needed. For further details, refer to our cancellation policy.',
                ],
                [
                    'question' => 'When will my journey end?',
                    'answer' => 'Return the vehicle 10 minutes before the booking period ends. Check for belongings, inspect for damages, ensure it is clean, upload final pictures, enter the End OTP, pay any dues, and complete the booking.',
                ],
                [
                    'question' => 'Is there a speed limit?',
                    'answer' => 'Yes, the speed limit is governed by local traffic laws and our terms and conditions.',
                ],
                [
                    'question' => 'Can I extend, cancel, or modify the booking?',
                    'answer' => 'Yes, you can manage your booking through the app or contact customer support for assistance.',
                ],
                [
                    'question' => 'What are the booking criteria and required documents?',
                    'answer' => 'You need a valid driver\'s license and a government-issued ID to book a vehicle.',
                ],
            ],
        ];
    
        return response()->json($response);
    }
    
    public function calculateHourAmount($rentalPrice, $tripHours){
        $rentalPrice = (float) $rentalPrice;
        $minTripHoursRule = TripAmountCalculationRule::select('id', 'hours')->orderBy('hours')->first();
        if ($tripHours < $minTripHoursRule->hours) {
            $tripHours = $minTripHoursRule->hours;
        }

        $rules = TripAmountCalculationRule::select('id', 'hours', 'multiplier')->orderBy('hours', 'desc')->get()->toArray();
        $multiplier = 1;
        $hours = $minTripHoursRule->hours;
        foreach ($rules as $rule) {
            if ($tripHours >= $rule['hours']) {
                $multiplier = $rule['multiplier'];
                $hours = $rule['hours'];
                break;
            }
        }
        $finalAmount = (($multiplier * $rentalPrice) / $hours) * 1;
        $finalAmount = round($finalAmount, 2);
        return $finalAmount;
    }

    public function HomescreenAvailableVehStories(Request $request)
    {
        $rentalReview = RentalReview::select(
            'rental_reviews.review_id',
            'rental_reviews.vehicle_id',
            'rental_reviews.customer_id',
            'rental_reviews.rating',
            'rental_reviews.review_text',
            'customers.firstname',
            'customers.lastname',
            'customers.profile_picture_url'
        )
        ->leftJoin('customers', 'customers.customer_id', '=', 'rental_reviews.customer_id')
        ->with('vehicle')
        ->orderBy('vehicle_id', 'desc')->where('rental_reviews.rating', '>', 4)->take(5)->get();

        $rentalReview->each(function ($rentalReview) {
            if ($rentalReview->vehicle) {
                $rentalReview->vehicle->makeHidden('branch_id', 'year', 'description', 'color', 'license_plate','rental_price', 'extra_km_rate', 'extra_hour_rate', 'availability_calendar', 'commission_percent', 'publish', 'category_name', 'banner_images', 'regular_images', 'location', 'rating', 'trip_count');
                if($rentalReview->profile_picture_url != '')
                $rentalReview->profile_picture_url = asset('/images/profile_pictures').'/'.$rentalReview->profile_picture_url;
            }
        });

        $validator = Validator::make($request->all(), [
            'type_id' => 'nullable|exists:vehicle_types,type_id',
            'city_id' => 'nullable|exists:cities,id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $typeId = $request->input('type_id');
        $cityId = $request->input('city_id'); 
        $query = Vehicle::with(['properties' => function ($query) {
                $query->select('vehicle_id', 'transmission_id', 'fuel_type_id', 'mileage', 'seating_capacity');
            }])
            ->where('vehicles.availability', 1)
            ->where('vehicles.is_deleted', 0)
            ->where('rental_price', '!=', 0)->where('publish', 1);

        if ($typeId) {
            $typeIds = is_string($typeId) ? explode(',', $typeId) : $typeId;
            $query = $query->whereHas('model.category', function ($query) use ($typeIds) {
                $query->whereIn('vehicle_type_id', $typeIds);
            });
        }
        if($cityId){
            $branchIdsArray = Branch::select('branch_id', 'city_id')
                ->where('city_id', $cityId)
                ->pluck('branch_id')
                ->toArray();
            $carHostPickupLocation = CarHostPickupLocation::where('city_id', $cityId)->pluck('id')->toArray();
            $carHostVehicleIds = CarEligibility::whereIn('car_host_pickup_location_id', $carHostPickupLocation)->pluck('vehicle_id')->toArray();
            $query = $query->where(function ($subQuery) use ($branchIdsArray, $carHostVehicleIds) {
                $subQuery->whereIn('branch_id', $branchIdsArray)
                    ->orWhereNull('branch_id')
                    ->whereIn('vehicle_id', $carHostVehicleIds);
            });
        }
       
        $vehicles = $query->orderBy('vehicle_id', 'desc')->get();
        foreach ($vehicles as $key => $value) {
            $rentalPrice = $value->rental_price;
            $checkOffer = OfferDate::where('vehicle_id', $value->vehicle_id)->get();
            if(is_countable($checkOffer) && count($checkOffer) > 0){
                $rentalPrice = getRentalPrice($rentalPrice, $value->vehicle_id);
            }
            $tripHours = 24;
            $pricePerHour = $this->calculateHourAmount($rentalPrice, $tripHours);
            $pricePerHour = '₹' . $pricePerHour . '/hr';      
            $value->price_pr_hour = $pricePerHour;  
        }

        $vehicles->each(function ($vehicle) {
            if ($vehicle->properties) { 
                $vehicle->properties->makeHidden(['transmission', 'fuelType']);
            }
            if ($vehicle->model) {
                $vehicle->model->makeHidden(['model_id','category_id', 'model_image', 'manufacturer']);
            } 
            $vehicle->setHidden(['branch_id', 'year', 'description', 'color', 'license_plate', 'availability_calendar', 'rental_price', 'extra_km_rate', 'extra_hour_rate', 'category_name', 'regular_images', 'model_id', 'availability', 'is_deleted', 'created_at', 'updated_at', 'branch']);
        });

        $vehicles = $vehicles->filter(function ($item) {
        //Check if particular vehicle is allocated with any booking then exclude that vehicle to show on list
            $existingBookings = RentalBooking::where('vehicle_id', $item->vehicle_id)->whereIn('status', ['running', 'confirmed'])->exists();
            return !$existingBookings; // This line will determine if the vehicle should be included
        })->values()->take(5);

        $homescreen = $this->homescreen()->getData(true);


        return $this->successResponse([
            'homescreen' => $homescreen,
            'available-vehicles' => $vehicles,
            'velrider-stories' => $rentalReview,
        ]);

    }

    public function vehicleDetailsAndPricingShowcase(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $vehicleId = $request->vehicle_id;

        // Get basic vehicle details
        $vehicle = Vehicle::select('vehicle_id', 'model_id', 'rental_price')
            ->where('vehicle_id', $vehicleId)
            ->first();

        if (!$vehicle) {
            return $this->errorResponse('Vehicle not found');
        }

        // Get vehicle price details
        $vehiclePriceDetails = VehiclePriceDetail::where('vehicle_id', $vehicleId)->where('is_show', 1)->get();

        // Prepare pricing showcase
        $pricingShowCase = [];

        if ($vehiclePriceDetails->isNotEmpty()) {
            foreach ($vehiclePriceDetails as $v) {
                if ($v->rate > 0) {
                    $tripAmount = $v->rate;
                    $unKMtripAmount = $tripAmount * 1.3;
                    $perHourRate = $tripAmount / $v->hours;
                    $duration = ($v->hours >= 24) ? round($v->hours / 24, 2) . ' days' : $v->hours . ' hours';
                    $durationHoursLimit = calculateKmLimit($v->hours);

                    $pricingShowCase[] = [
                        'duration' => $duration,
                        'trip_amount_in_rupees' => '₹' . number_format($tripAmount, 2) . " ( {$durationHoursLimit} Km )",
                        'unlimited_km_trip_amount_in_rupees' => '₹' . number_format($unKMtripAmount, 2),
                        'per_hour_rate' => '₹' . number_format($perHourRate, 2),
                    ];
                }
            }
        } else {
            // Fallback: calculate from rules
            $rules = TripAmountCalculationRule::select('id', 'hours', 'multiplier')->orderBy('hours', 'desc')->get();
            foreach ($rules as $rule) {
                $tripAmount = $rule->multiplier * $vehicle->rental_price;
                $unKMtripAmount = $tripAmount * 1.3;
                $perHourRate = $tripAmount / $rule->hours;
                $duration = ($rule->hours >= 24) ? round($rule->hours / 24, 2) . ' days' : $rule->hours . ' hours';
                $durationHoursLimit = calculateKmLimit($rule->hours);

                $pricingShowCase[] = [
                    'duration' => $duration,
                    'trip_amount_in_rupees' => '₹' . number_format($tripAmount, 2) . " ( {$durationHoursLimit} Km )",
                    'unlimited_km_trip_amount_in_rupees' => '₹' . number_format($unKMtripAmount, 2),
                    'per_hour_rate' => '₹' . number_format($perHourRate, 2),
                ];
            }
        }

        $summaryTableHtml = $this->buildPricingTable($pricingShowCase);

        // Get full vehicle details including properties and features
        $isHostVehicle = false;
        $checkIsHostVehicle = CarEligibility::where('vehicle_id', $vehicleId)->first();
        if(isset($checkIsHostVehicle) && $checkIsHostVehicle != ''){
            $isHostVehicle = true;
        }
        if($isHostVehicle){
            $vehicleDetails = Vehicle::select('vehicle_id', 'description', 'model_id', 'branch_id')
            ->with(['properties' => function ($query) {
                $query->select('vehicle_id', 'seating_capacity', 'engine_cc', 'fuel_capacity', 'transmission_id', 'fuel_type_id', 'mileage');
            }])->with('carhostFeatures')
            ->where(['vehicle_id' => $vehicleId, 'availability' => 1, 'is_deleted' => 0])->first();
            if(isset($vehicleDetails) && $vehicleDetails != ''){
                $vehicleDetails->features = $vehicleDetails->carhostFeatures;
                $vehicleDetails->makeHidden('carhostFeatures', 'host_banner_images', 'host_regular_images');
            }
        }else{
            $vehicleDetails = Vehicle::select('vehicle_id', 'description', 'model_id', 'branch_id')
            ->with('features')
            ->with(['properties' => function ($query) {
                $query->select('vehicle_id', 'seating_capacity', 'engine_cc', 'fuel_capacity', 'transmission_id', 'fuel_type_id', 'mileage');
            }])
            ->where(['vehicle_id' => $vehicleId, 'availability' => 1, 'is_deleted' => 0])->first();
        }

        if ($vehicleDetails && $vehicleDetails->properties) {
            $vehicleDetails->properties->makeHidden(['transmission', 'fuelType']);
        }

        return $this->successResponse([
            'vehicle_details' => $vehicleDetails,
            'pricing_table_html' => $summaryTableHtml,
        ], 'Pricing and vehicle details retrieved successfully.');
    }

    protected function buildPricingTable($pricingShowCase)
    {
        $html = '
        <div style="display: inline-block; padding: 10px; background-color: transparent; width: fit-content;">
            <div style="display: inline-block; padding: 0px; background-color: #fff; font-family: \'Helvetica Neue\', Helvetica, Arial, sans-serif; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden; text-align: center;">
                <table style="border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="background-color: #f9f9f9; padding: 10px; text-align: center; border-bottom: 2px solid #ddd;">Duration</th>
                            <th style="background-color: #f9f9f9; padding: 10px; text-align: center; border-bottom: 2px solid #ddd;">Per Hour Rate</th>
                            <th style="background-color: #f9f9f9; padding: 10px; text-align: center; border-bottom: 2px solid #ddd;">Trip Amount</th>
                            <th style="background-color: #f9f9f9; padding: 10px; text-align: center; border-bottom: 2px solid #ddd;">Unlimited KM</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        foreach ($pricingShowCase as $item) {
            $html .= '
                    <tr>
                        <td style="padding: 10px; border-bottom: 1px solid #eee;">' . htmlspecialchars($item['duration']) . '</td>
                        <td style="padding: 10px; border-bottom: 1px solid #eee;">' . htmlspecialchars($item['per_hour_rate']) . '</td>
                        <td style="padding: 10px; border-bottom: 1px solid #eee;">' . htmlspecialchars($item['trip_amount_in_rupees']) . '</td>
                        <td style="padding: 10px; border-bottom: 1px solid #eee;">' . htmlspecialchars($item['unlimited_km_trip_amount_in_rupees']) . '</td>
                    </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
        </div>
        </div>';
    
        return $html;
    }

}
