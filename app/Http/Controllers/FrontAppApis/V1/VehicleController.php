<?php

namespace App\Http\Controllers\FrontAppApis\V1;

use App\Http\Controllers\Controller; 
use App\Models\{Vehicle, Coupon, VehicleManufacturer, VehicleModel, Transmission, FuelType, VehicleType, VehicleCategory, Setting, RentalBooking, Branch, TripAmountCalculationRule, CarHostPickupLocation, OfferDate, CarEligibility, RentalReview};
use Illuminate\Http\Request;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Validator;

class VehicleController extends Controller
{
    public function index(Request $request)
    {
        // Extract filter parameters from the request
        $typeId = $request->input('type_id');
        $categoryId = $request->input('category_id');
        $transmissionId = $request->input('transmission_id');
        $fuelTypeId = $request->input('fuel_type_id');
        $manufacturerId = $request->input('manufacturer_id');
        $modelId = $request->input('model_id');
        $cityId = $request->input('city_id'); 
        $viewAll = $request->input('view_all');
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : '';
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : '';
        
        // Initialize the query builder
        $query = Vehicle::with([/*'model.manufacturer', 'model.category', 'features' , 'images'*/])
            ->with(['properties' => function ($query) {
                $query->select('vehicle_id', 'transmission_id', 'fuel_type_id', 'mileage', 'seating_capacity');
            }])
            ->where('vehicles.availability', 1)
            ->where('vehicles.is_deleted', 0)
            ->withCount('rentalBookings')->withCount(['runningOrConfirmedBookings'])
            ->where('rental_price', '!=', 0)->where('publish', 1);//->where('vehicle_created_by', 1);
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
            // $branchIdsArray = Branch::select('branch_id', 'city_id')->where('city_id', $cityId)->pluck('branch_id')->toArray();
            // $query = $query->whereIn('branch_id', $branchIdsArray);
        }

        if ($typeId) {
            $typeIds = is_string($typeId) ? explode(',', $typeId) : $typeId;
            $query = $query->whereHas('model.category', function ($query) use ($typeIds) {
                $query->whereIn('vehicle_type_id', $typeIds);
            });
        }
        
        if ($categoryId) {
            $categoryIds = is_string($categoryId) ? explode(',', $categoryId) : $categoryId;
            if (in_array(1, $categoryIds)) { // 1 = Popular Cars
                $vehicle_type_id = 1;
                $query->whereHas('model.manufacturer', function ($query) use ($vehicle_type_id) {
                    $query->where('vehicle_type_id', $vehicle_type_id);
                });
            } else if (in_array(9, $categoryIds)) { // 9 = Popular Bikes
                $vehicle_type_id = 2;
                $query->whereHas('model.manufacturer', function ($query) use ($vehicle_type_id) {
                    $query->where('vehicle_type_id', $vehicle_type_id);
                });
            } else if (in_array(26, $categoryIds)) { // 26 = All Cars
                $vehicle_type_id = 1;
                $query->whereHas('model.manufacturer', function ($query) use ($vehicle_type_id) {
                    $query->where('vehicle_type_id', $vehicle_type_id);
                });
            } else if (in_array(27, $categoryIds)) { // 27 = All Bikes
                $vehicle_type_id = 2;
                $query->whereHas('model.manufacturer', function ($query) use ($vehicle_type_id) {
                    $query->where('vehicle_type_id', $vehicle_type_id);
                });
            } else {
                $query->whereHas('model.category', function ($query) use($categoryIds) {
                    $query->whereIn('category_id', $categoryIds);
                });
                //$query->whereIn('category_id', $categoryIds);
            }
        }            
        // if ($manufacturerId) {
        //     $manufacturerIds = is_string($manufacturerId) ? explode(',', $manufacturerId) : $manufacturerId;
        //     $query->whereHas('model.manufacturer', function ($query) use ($manufacturerIds) {
        //         $query->whereIn('manufacturer_id', $manufacturerIds);
        //     });
        // }
        // if ($modelId) {
        //     $modelIds = is_string($modelId) ? explode(',', $modelId) : $modelId;
        //     $query->whereIn('model_id', $modelIds);
        // }
        if ($manufacturerId || $modelId) {
            $query->where(function ($query) use ($manufacturerId, $modelId) {
                if ($manufacturerId) {
                    $manufacturerIds = is_string($manufacturerId) ? explode(',', $manufacturerId) : $manufacturerId;
                    $query->whereHas('model.manufacturer', function ($q) use ($manufacturerIds) {
                        $q->whereIn('manufacturer_id', $manufacturerIds);
                    });
                }
                if ($modelId) {
                    $modelIds = is_string($modelId) ? explode(',', $modelId) : $modelId;
                    $query->orWhereIn('model_id', $modelIds);
                }
            });
        }

        if ($fuelTypeId) {
            $fuelTypeIds = is_string($fuelTypeId) ? explode(',', $fuelTypeId) : $fuelTypeId;
            $query->whereHas('properties.fuelType', function ($query) use ($fuelTypeIds) {
                $query->whereIn('fuel_type_id', $fuelTypeIds);
            });
        }
        if ($transmissionId) {
            $transmissionIds = is_string($transmissionId) ? explode(',', $transmissionId) : $transmissionId;
            $query->whereHas('properties.transmission', function ($query) use ($transmissionIds) {
                $query->whereIn('transmission_id', $transmissionIds);
            });
        }
        
        $page = $request->input('page');
        $pageSize = $request->input('page_size');
        $setting = Setting::first();
        // if ($page !== null && $pageSize !== null) {
        //     $vehicles = $query->paginate($pageSize, ['*'], 'page', $page);
        //     //Hide unneccesary items
        //     $vehicles->getCollection()->each(function ($vehicle) {
        //         if ($vehicle->properties) { 
        //             $vehicle->properties->makeHidden(['transmission', 'fuelType']);
        //         }
        //         if ($vehicle->model) {
        //             $vehicle->model->makeHidden(['model_id','category_id', 'model_image', 'manufacturer']);
        //         } 
        //         $vehicle->setHidden(['branch_id', 'year', 'description', 'color', 'license_plate', 'availability_calendar', 'rental_price', 'extra_km_rate', 'extra_hour_rate', 'category_name', 'regular_images', 'model_id', 'availability', 'is_deleted', 'created_at', 'updated_at', 'branch']);
        //     });
        // } else {
            $vehicles = $query->get();
            //Hide unneccesary items
            $vehicles->each(function ($vehicle) {
                if ($vehicle->properties) { 
                    $vehicle->properties->makeHidden(['transmission', 'fuelType']);
                }
                if ($vehicle->model) {
                    $vehicle->model->makeHidden(['model_id','category_id', 'model_image', 'manufacturer']);
                } 
                $vehicle->setHidden(['branch_id', 'year', 'description', 'color', 'license_plate', 'availability_calendar', 'rental_price', 'extra_km_rate', 'extra_hour_rate', 'category_name', 'regular_images', 'model_id', 'availability', 'is_deleted', 'created_at', 'updated_at', 'branch']);
            });
       // }
        
        if($startDate != '' && $endDate != ''){
            $vehicles = $vehicles->filter(function ($item) use ($startDate, $endDate, $setting) {
            //Check if particular vehicle is allocated with any booking then exclude that vehicle to show on list
                //if($setting != '' && $setting->show_all_vehicle == 1){
                if($setting != '' && $setting->show_all_vehicle != 1 ){
                    $existingBookings = RentalBooking::where('vehicle_id', $item->vehicle_id)->whereIn('status', ['running', 'confirmed'])->where(function ($query) use ($startDate, $endDate) {
                            $query->whereBetween('pickup_date', [$startDate, $endDate])
                                ->orWhereBetween('return_date', [$startDate, $endDate])
                                ->orWhere(function ($query) use ($startDate, $endDate) {
                                    $query->where('pickup_date', '<', $startDate)
                                        ->where('return_date', '>', $endDate);
                                });
                        })->exists();
                    return !$existingBookings;
                }
                return true;
            })->values();
        }

        if(is_countable($vehicles) && count($vehicles) > 0){
            foreach ($vehicles as $key => $value) {
                $rentalPrice = $value->rental_price;
                $checkOffer = OfferDate::where('vehicle_id', $value->vehicle_id)->get();
                if(is_countable($checkOffer) && count($checkOffer) > 0){
                    $rentalPrice = getRentalPrice($rentalPrice, $value->vehicle_id);
                }
                if($startDate != '' && $endDate != ''){
                    $tripHours = $endDate->diffInHours($startDate);
                }else{
                    $tripHours = 24;
                }
                $pricePerHour = $this->calculateHourAmount($rentalPrice, $tripHours);
                $pricePerHour = '₹' . $pricePerHour . '/hr';      
                $value->price_pr_hour = $pricePerHour;  
            }
            
            foreach ($vehicles as $key => $value) {
                if($startDate != '' && $endDate != ''){
                    $existingBookings = RentalBooking::where('vehicle_id', $value->vehicle_id)->whereIn('status', ['running', 'confirmed'])->where(function ($query) use ($startDate, $endDate) {
                            $query->whereBetween('pickup_date', [$startDate, $endDate])
                                ->orWhereBetween('return_date', [$startDate, $endDate])
                                ->orWhere(function ($query) use ($startDate, $endDate) {
                                    $query->where('pickup_date', '<', $startDate)
                                        ->where('return_date', '>', $endDate);
                                });
                        })->exists();
                        
                        $booked = false;
                        $booked_msg = '';
                        //Check if specified Start and End date is stored in availability calender or not
                        if (!empty($value->availability_calendar)) {
                            $unavailabilityCalendar = json_decode($value->availability_calendar, true);
                            if(is_countable($unavailabilityCalendar) && count($unavailabilityCalendar) > 0){
                                foreach ($unavailabilityCalendar as $period) {
                                    if(isset($period['start_date']) && isset($period['end_date'])){
                                        // $periodStartDate = Carbon::parse($period['start_date']);
                                        // $periodEndDate = Carbon::parse($period['end_date']);
                                        $periodStartDate = normalizeDateTime($period['start_date']);
                                        $periodEndDate = normalizeDateTime($period['end_date']);
                                        if (
                                            ($startDate->between($periodStartDate, $periodEndDate) ||
                                            $endDate->between($periodStartDate, $periodEndDate) ||
                                            ($startDate <= $periodStartDate && $endDate >= $periodEndDate))
                                        ) {
                                            $booked = true;
                                            $booked_msg = 'RESERVED';
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    if($existingBookings){
                        $booked = true;
                        $booked_msg = 'RESERVED';
                    }
                    $value->booked = $booked;    
                    $value->booked_msg = $booked_msg;
                }

                if(isset($request->latitude) && isset($request->longitude)){
                    $finalDistanceInKm = null;
                    $requestLat = $request->latitude;
                    $requestLong = $request->longitude;
                    if(isset($value->branch) && isset($value->branch->latitude) && isset($value->branch->longitude)){
                        $branchLat = $value->branch->latitude;
                        $branchLong = $value->branch->longitude;
                        if(isset($branchLat) && isset($branchLong)){
                            $distanceInKm = getDistanceInKm($requestLat, $requestLong, $branchLat, $branchLong);
                            $finalDistanceInKm = round($distanceInKm, 2);
                        }
                    }else{
                        $carEligibility = CarEligibility::where('vehicle_id', $value->vehicle_id)->first();
                        $carHostPickupLocation = CarHostPickupLocation::where('id', $carEligibility->car_host_pickup_location_id)->first();
                        if($carHostPickupLocation != ''){
                            $lat = $carHostPickupLocation->latitude;
                            $long = $carHostPickupLocation->longitude;
                            if(isset($lat) && isset($long)){
                                $distanceInKm = getDistanceInKm($requestLat, $requestLong, $lat, $long);
                                $finalDistanceInKm = round($distanceInKm, 2);
                            }   
                        }
                    }
                    $value->distanceInKm = $finalDistanceInKm;
                }
            }
            // commented to show all vehicles even though it is beyond 50km (client requirement)
            // if(isset($request->latitude) && isset($request->longitude)){
            //     // Keep only vehicles within 50km of the provided coordinates
            //     $vehicles = $vehicles->filter(function ($vehicle) {
            //         return $vehicle->distanceInKm !== null && $vehicle->distanceInKm <= 50;
            //     })->values();
            // }
            
            $vehicles = $vehicles->sortBy(function ($vehicle) {
                // Using a negative value for rental bookings count for descending order
                return [
                    $vehicle->booked_msg === 'RESERVED' ? 1 : 0, // RESERVED vehicles at the end (1 means lower priority)
                    -$vehicle->rental_bookings_count, // Descending order - which vehicle has highest number of bookings which will shown secon
                    $vehicle->distanceInKm, // Ascending order - which vehicle kilometer distance in in nearby places it will show first
                    $vehicle->running_or_confirmed_bookings_count, // Ascending order - Booked vehicle will show at last
                ];
            })->values();
        }

        // if(is_countable($vehicles) && count($vehicles) > 0){
        //     $vehiclesArr = json_decode(json_encode($vehicles->values()), FALSE);
        //     return $this->successResponse($vehiclesArr, 'Vehicles are get successfully.');    
        // }else{
        //     return $this->errorResponse('Vehicles are not Found');    
        // }
        
        if ($page !== null && $pageSize !== null) {
            // Manual pagination
            $offset = ($page - 1) * $pageSize;
            $vehicles = $vehicles->slice($offset, $pageSize)->values();
            $total = $vehicles->count();

            return $this->successResponse($vehicles, 'Vehicles are get successfully.', [
                'current_page' => $page,
                'per_page' => $pageSize,
                'total' => $total,
            ]);

        } else {
            $vehiclesArr = json_decode(json_encode($vehicles->values()), FALSE);
            return $this->successResponse($vehiclesArr, 'Vehicles are get successfully.');   
        }
    }

    public function vehicleDetails(Request $request){
        $vehicle_id = $request->vehicle_id;
        if($vehicle_id == ''){
            return $this->errorResponse("Please enter Vehicle Id");
        }
        $isHostVehicle = false;
        $checkIsHostVehicle = CarEligibility::where('vehicle_id', $vehicle_id)->first();
        if(isset($checkIsHostVehicle) && $checkIsHostVehicle != ''){
            $isHostVehicle = true;
        }
        if($isHostVehicle){
            $vehicle = Vehicle::select('vehicle_id', 'description', 'model_id', 'branch_id')
            ->with(['properties' => function ($query) {
                $query->select('vehicle_id', 'seating_capacity', 'engine_cc', 'fuel_capacity', 'transmission_id', 'fuel_type_id', 'mileage');
            }])->with('carhostFeatures')
            ->where(['vehicle_id' => $vehicle_id, 'availability' => 1, 'is_deleted' => 0])->first();
            if(isset($vehicle) && $vehicle != ''){
                $vehicle->features = $vehicle->carhostFeatures;
                $vehicle->makeHidden('carhostFeatures', 'host_banner_images', 'host_regular_images');
            }
        }else{
            $vehicle = Vehicle::select('vehicle_id', 'description', 'model_id', 'branch_id')
            ->with('features')
            ->with(['properties' => function ($query) {
                $query->select('vehicle_id', 'seating_capacity', 'engine_cc', 'fuel_capacity', 'transmission_id', 'fuel_type_id', 'mileage');
            }])
            ->where(['vehicle_id' => $vehicle_id, 'availability' => 1, 'is_deleted' => 0])->first();
        }
        
        if ($vehicle != '') { 
            if($vehicle->properties){
                $vehicle->properties->makeHidden(['transmission', 'fuelType']);    
            }
            return $this->successResponse($vehicle, "Vehicle get successfully");    
        }else{
            return $this->errorResponse("Vehicle id is Invalid");
        }
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

    public function manufacturers(Request $request)
    {
        $typeId = $request->input('vehicle_type_id');
        $query = VehicleManufacturer::with('models');
        if ($typeId) {
            $typeIds = is_string($typeId) ? explode(',', $typeId) : $typeId;
                $query->whereIn('vehicle_type_id', $typeIds);

        }
        $popularManufacturerIds = [1, 2, 3, 4, 5, 6];
        $manufacturers = $query->get();
        $manufacturers->transform(function ($manufacturer) use ($popularManufacturerIds) {
            $manufacturer->is_popular = in_array($manufacturer->id, $popularManufacturerIds);
            return $manufacturer;
        });

        return $this->successResponse($manufacturers);
    }

    public function models(Request $request)
    {
        $typeId = $request->input('vehicle_type_id');
        $query = VehicleModel::query();
        if ($typeId) {
            $typeIds = is_string($typeId) ? explode(',', $typeId) : $typeId;
            $query->whereIn('manufacturer_id', function ($query) use ($typeIds) {
                $query->select('manufacturer_id')
                    ->from('vehicle_manufacturers')
                    ->whereIn('vehicle_type_id', $typeIds);
            });
        }
        $models = $query->get();
        
        return $this->successResponse($models);
    }
    public function transmissions(Request $request)
    {
        $typeId = $request->input('vehicle_type_id');
        $transmissions = Transmission::query();
        if ($typeId) {
            $transmissions->where('vehicle_type_id', $typeId);
        }
        $transmissions = $transmissions->get();

        return $this->successResponse($transmissions);
    }
    
    public function fuelTypes(Request $request)
    {
        $typeId = $request->input('vehicle_type_id');
        $fuelTypes = FuelType::query();
        if ($typeId) {
            $fuelTypes->where('vehicle_type_id', $typeId);
        }
        $fuelTypes = $fuelTypes->get();
    
        return $this->successResponse($fuelTypes);
    }

    public function getAvailableVehicles(Request $request){
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

        
        return $this->successResponse($vehicles, 'Vehicles are get successfully.');
    }

    public function getVelriderStories(Request $request){
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
        if(isset($rentalReview) && is_countable($rentalReview) && count($rentalReview) > 0){
            $rentalReview->each(function ($rentalReview) {
                if($rentalReview->vehicle){
                    if($rentalReview->profile_picture_url != ''){
                        $rentalReview->profile_picture_url = asset('/images/profile_pictures').'/'.$rentalReview->profile_picture_url;
                    }
                    $rentalReview->vehicle->makeHidden('branch_id', 'year', 'description', 'color', 'license_plate','rental_price', 'extra_km_rate', 'extra_hour_rate', 'availability_calendar', 'commission_percent', 'publish', 'category_name', 'banner_images', 'regular_images', 'location', 'rating', 'trip_count');
                }
            });
            return $this->successResponse($rentalReview, 'Reviews are get successfully.');
        }else{
            return $this->errorResponse($rentalReview, 'Reviews are get successfully.');
        }
    }

}
