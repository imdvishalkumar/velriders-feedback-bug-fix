<?php

namespace App\Http\Controllers\FrontAppApis\V1;

use App\Http\Controllers\Controller; 
use App\Models\{VehicleCategory, VehicleType, FuelType, VehicleManufacturer, VehicleModel, Transmission, Vehicle, VehiclePriceDetail, RentalReview, TripAmountCalculationRule, Faq, ImageSlider};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function __construct()
    {
        //$this->middleware('auth:api');
    }
    
    public function index(Request $request)
    {
        $vehicle_type_ids = $request->input('vehicle_type_id');
        $filters = $request->input('filters', false); // Default is false if not provided
    
        // Initialize the query with a conditional eager loading based on the filters input
        $query = VehicleType::where('is_deleted', 0);
    
        if ($filters) {
            // Apply the filter to exclude categories with specific names and sort them
            $query->with(['categories' => function($query) {
                $query->whereNot(function($q) {
                    $q->where('name', 'like', '%Popular%')
                      ->orWhere('name', 'like', '%All%'); // Add more as needed
                })->orderBy('sort', 'asc'); // Add sorting here
            }]);
        } else {
            // Load all categories and sort them
            $query->with(['categories' => function($query) {
                $query->orderBy('sort', 'asc'); // Sorting for non-filtered categories
            }]);
        }
    
        if ($vehicle_type_ids) {
            if (strpos($vehicle_type_ids, ',') !== false) {
                $vehicle_type_ids = explode(',', $vehicle_type_ids);
                $vehicle_type_ids = array_filter(array_map('trim', $vehicle_type_ids));
                $query->whereIn('type_id', $vehicle_type_ids);
            } else {
                $query->where('type_id', $vehicle_type_ids);
            }
        }
    
        $types = $query->get();
    
        return $this->successResponse($types);
    }
    
    public function vehicleTypes()
    {
        $types = VehicleType::where('is_deleted', 0)->get();
        return $this->successResponse($types);
    }

    // public function fuelTypes(Request $request)
    // {
    //     $typeId = $request->input('vehicle_type_id');
    //     $fuelTypes = FuelType::query();
    //     if ($typeId) {
    //         $fuelTypes->where('vehicle_type_id', $typeId);
    //     }
    //     $fuelTypes = $fuelTypes->get();
    
    //     return $this->successResponse($fuelTypes);
    // }

    public function getCommonDetails(Request $request)
    {
        $typeArr = $finalArr = [];
        // OLD CODE
        // $typeId = $request->input('vehicle_type_id');
        // Get all vehicle types and fuel types with optional filter
        // $vehicleTypes = VehicleType::where('is_deleted', 0)->get();
        // $fuelTypes = FuelType::where('is_deleted', 0)
        //     ->when($typeId, function ($query) use ($typeId) {
        //         $query->where('vehicle_type_id', $typeId);
        //     })->get();

        // Get manufacturers with models and check for popular ones
        // $popularManufacturerIds = [1, 2, 3, 4, 5, 6];
        // $manufacturers = VehicleManufacturer::with('models')
        //     ->when($typeId, function ($query) use ($typeId) {
        //         $typeIds = is_string($typeId) ? explode(',', $typeId) : $typeId;
        //         $query->whereIn('vehicle_type_id', $typeIds);
        //     })->get()->transform(function ($manufacturer) use ($popularManufacturerIds) {
        //         $manufacturer->is_popular = in_array($manufacturer->id, $popularManufacturerIds);
        //         return $manufacturer;
        //     });
        // Get models with type filter
        // $models = VehicleModel::when($typeId, function ($query) use ($typeId) {
        //     $typeIds = is_string($typeId) ? explode(',', $typeId) : $typeId;
        //     $query->whereIn('manufacturer_id', function ($subQuery) use ($typeIds) {
        //         $subQuery->select('manufacturer_id')
        //             ->from('vehicle_manufacturers')
        //             ->whereIn('vehicle_type_id', $typeIds);
        //     });
        // })->get();
        // Get transmissions with type filter
        // $transmissions = Transmission::when($typeId, function ($query) use ($typeId) {
        //     $query->where('vehicle_type_id', $typeId);
        // })->get();

        // return $this->successResponse([
        //     'vehicle_types' => $vehicleTypes,
        //     'fuel_types' => $fuelTypes,
        //     'manufacturers' => $manufacturers,
        //     'models' => $models,
        //     'transmissions' => $transmissions,
        // ]);

        // NEW CODE
        // Get all vehicle types
        $vehicleTypes = VehicleType::select('type_id', 'name', 'is_deleted')->where('is_deleted', 0)->get();
        if(isset($vehicleTypes) && is_countable($vehicleTypes) && count($vehicleTypes) > 0){
            $finalArr['vehicle_types'] = $vehicleTypes;
        }else{
            $finalArr['vehicle_types'] = [];
        }
        // if(isset($typeArr) && is_countable($typeArr) && count($typeArr) > 0){
        //     foreach ($typeArr as $key => $value) {
        //         $typeId = $value['id'];
        //         $typeName = $value['name'];
                // Get all Fuel types with vehicle type filter
                $fuelTypes = FuelType::select('fuel_type_id', 'name', 'is_deleted', 'vehicle_type_id')->where('is_deleted', 0)->get();
                if(isset($fuelTypes) && is_countable($fuelTypes) && count($fuelTypes) > 0){
                    $finalArr['fuel_types'] = $fuelTypes;
                    // foreach ($fuelTypes as $k => $v) {
                    //     $finalArr['fuel_types'][$k]['fuel_type_id'] = $v->fuel_type_id;
                    //     $finalArr['fuel_types'][$k]['vehicle_type_id'] = $v->vehicle_type_id;
                    //     $finalArr['fuel_types'][$k]['name'] = $v->name;
                    //     $finalArr['fuel_types'][$k]['is_deleted'] = $v->is_deleted;
                    // }
                }else{
                    $finalArr['fuel_types'] = [];
                }

                // Get all Manufacturers with vehicle type filter
                $popularManufacturerIds = [1, 2, 3, 4, 5, 6];
                $manufacturers = VehicleManufacturer::with('models')->where('is_deleted', 0)->get();
                if(isset($manufacturers) && is_countable($manufacturers) && count($manufacturers) > 0){
                    foreach ($manufacturers as $k => $v) {
                        if(in_array($v->manufacturer_id, $popularManufacturerIds)){
                            $v->is_popular = true;
                        }else{
                            $v->is_popular = false;
                        }
                        $v->models = $v->models;
                        // $finalArr['manufacturers'][$k]['manufacturer_id'] = $v->manufacturer_id;
                        // $finalArr['manufacturers'][$k]['vehicle_type_id'] = $v->vehicle_type_id;
                        // $finalArr['manufacturers'][$k]['name'] = $v->name;
                        // $finalArr['manufacturers'][$k]['logo'] = $v->logo;
                        // $finalArr['manufacturers'][$k]['is_popular'] = $v->is_popular;
                        // $finalArr['manufacturers'][$k]['models'] = $v->models;
                    }
                    $finalArr['manufacturers'] = $manufacturers;
                }else{
                    $finalArr['manufacturers'] = [];
                }
                // Get Models with type filter
                $models = VehicleModel::where('is_deleted', 0)->get();
                if(isset($models) && is_countable($models) && count($models) > 0){
                    // foreach ($models as $k => $v) {
                    //     $finalArr['models'][$k]['model_id'] = $v->model_id;
                    //     $finalArr['models'][$k]['name'] = $v->name;
                    //     $finalArr['models'][$k]['category_id'] = $v->category_id;
                    //     $finalArr['models'][$k]['model_image'] = $v->model_image;
                    //     $finalArr['models'][$k]['min_price'] = $v->min_price;
                    //     $finalArr['models'][$k]['max_price'] = $v->max_price;
                    // }
                    $finalArr['models'] = $models;
                }else{
                    $finalArr['models'] = [];
                }
                // Get Transmissions with type filter
                $transmissions = Transmission::where('is_deleted', 0)->get();
                if(isset($transmissions) && is_countable($transmissions) && count($transmissions) > 0){
                    // foreach ($transmissions as $k => $v) {
                    //     $finalArr['transmissions'][$k]['transmission_id'] = $v->transmission_id;
                    //     $finalArr['transmissions'][$k]['vehicle_type_id'] = $v->vehicle_type_id;
                    //     $finalArr['transmissions'][$k]['name'] = $v->name;
                    //     $finalArr['transmissions'][$k]['is_deleted'] = $v->is_deleted;
                    // }
                    $finalArr['transmissions'] = $transmissions;
                }else{
                    $finalArr['transmissions'] = [];
                }
                // Get Category with type filter
                $categories = VehicleCategory::select('category_id', 'vehicle_type_id', 'name', 'icon', 'is_deleted', 'sort')->where('is_deleted', 0)->orderBy('sort', 'asc')->get();
                if(isset($categories) && is_countable($categories) && count($categories) > 0){
                    // foreach ($categories as $k => $v) {
                    //     $finalArr['categories'][$k]['category_id'] = $v->category_id;
                    //     $finalArr['categories'][$k]['vehicle_type_id'] = $v->vehicle_type_id;
                    //     $finalArr['categories'][$k]['name'] = $v->name;
                    //     $finalArr['categories'][$k]['icon'] = $v->icon;
                    //     $finalArr['categories'][$k]['is_deleted'] = $v->is_deleted;
                    // }
                    $finalArr['categories'] = $categories;
                }else{
                    $finalArr['categories'] = [];
                }

                // VEHICLE STORIES
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
                $faqs = Faq::select('question', 'answer')->where('is_deleted', 0)->where('faq_for', 1)->get(); // 1 for customer 2 for host
                $finalArr['velrider_stories'] = $rentalReview;
                $imageSliders = ImageSlider::where('is_deleted', 0)->where('banner_for', 1)->pluck('banner_img')
                        ->map(function ($val) {
                            return [
                                'images' => asset('images/banner_sliders/' . $val)
                            ];
                        })->toArray();

                // HOME SCREEN STATIC DATA
                // $imageSliders = [
                //         [
                //             'images' => 'https://velriders.com/images/home_screen/Banner-6.jpg',
                //         ],
                //         [
                //             'images' => 'https://velriders.com/images/home_screen/Banner-7.jpg',
                //         ],
                //         [
                //             'images' => 'https://velriders.com/images/home_screen/Banner-8.jpg',
                //         ],
                //         [
                //             'images' => 'https://velriders.com/images/home_screen/Banner5.jpg',
                //         ],
                //     ];
                $whyVelriders = [
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
                    ];
               
                $finalArr['image_sliders'] = $imageSliders;
                $finalArr['why_velriders'] = $whyVelriders;
                $finalArr['faqs'] = $faqs;
            //}
            //$finalArr['vehicle_types'] = array_values($finalArr['vehicle_types']);
            return $this->successResponse($finalArr, 'Details are get Successfully');
        // }else{
        //     return $this->errorResponse('Vehicle Types are not Found');    
        // }
    }


}
