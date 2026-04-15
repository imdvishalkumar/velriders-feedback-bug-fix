<?php

namespace App\Http\Controllers\CarhostAppApis\V1;

use App\Http\Controllers\Controller; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\{
    Vehicle, CarEligibility, CarHostVehicleImage, CarHostVehicleFeature, CarHostPickupLocation,
    CarHostBank, VehicleDocument, VehicleProperty, FuelType, City, TripAmountCalculationRule, VehiclePriceDetail, VehicleModelPriceDetail, CarHostVehicleStartJourneyImage, CarHostVehicleImageTemp, CarHostVehicleFeatureTemp, CarHostPickupLocationTemp, Setting, VehicleDocumentTemp, VehiclePriceDetailTemp
};
use Carbon\Carbon;

class CarHostVehicleController extends Controller
{
    protected $userAuthDetails;

    public function __construct()
    {
        $this->userAuthDetails = Auth::guard('api-carhost')->user();
    }

    public function storeVehicleEligibility(Request $request){
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'nullable|exists:vehicles,vehicle_id',
            'license_plate' => 'nullable|max:10', //NOT USED
            //'vehicle_brand_id' => 'required',
            'vehicle_model_id' => 'required|exists:vehicle_models,model_id',
            'registration_year' => 'required',
            'km_driven' => 'nullable',
            'color' => 'nullable',
            /*'category_id' => 'required', */
            //'rental_price' => 'required',
            //'car_host_pickup_location_id' => 'required|exists:car_host_pickup_locations,id',
            'car_host_pickup_location_id' => 'nullable|exists:car_host_pickup_locations,id',
            'city_id' => 'required|exists:cities,id',
            //'deposit_amount' => 'nullable|numeric',
            //'is_deposit_amount_show' => 'nullable|in:0,1',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $user = Auth::guard('api-carhost')->user();
        if($user){
            // NEW CODE 1
            $vehicleDetailStatus = 'add';
            if(isset($request->vehicle_id) && $request->vehicle_id != ''){
                $vehicle = Vehicle::where('vehicle_id', $request->vehicle_id)->first();    
                if($vehicle != ''){
                    $vehicle->updated_temp_city_id = $request->city_id;
                    $vehicle->updated_model_id = $request->vehicle_model_id;
                    $vehicle->updated_year = $request->registration_year;
                    if(isset($request->vehicle_model_id) && $request->vehicle_model_id != '' || isset($request->registration_year) && $request->registration_year != '' || isset($request->city_id) && $request->city_id != ''){
                        if($vehicle->vehicle_model_id != $request->vehicle_model_id || $vehicle->year != $request->registration_year || $vehicle->temp_city_id != $request->city_id){
                            $vehicle->is_host_updated = 1;
                            $vehicle->save();
                        }
                    }
                }
                $vehicleDetailStatus = 'update';  
            }else{
                $vehicle = new Vehicle();
                $vehicle->availability = 1;
                $vehicle->vehicle_created_by = 2;
                $vehicle->model_id = $request->vehicle_model_id;
                $vehicle->temp_city_id = $request->city_id;
                $vehicle->license_plate = $request->license_plate;
                $vehicle->year = $request->registration_year;
                $vehicle->rental_price = $request->rental_price ?? 0;
                $vehicle->publish = 0;
                $vehicle->color = isset($request->color) ? $request->color : NULL;
                $vehicle->save();
                $vehicleLocation = NULL;
                $primaryLocation = CarHostPickupLocation::where(['car_hosts_id' => $user->id, 'is_primary' => 1])->first();
                if($request->car_host_pickup_location_id != ''){
                    $vehicleLocation = $request->car_host_pickup_location_id;
                }else if($primaryLocation != ''){
                    $vehicleLocation = $primaryLocation->id;
                }
                $carHost = new CarEligibility();
                $carHost->vehicle_id = $vehicle->vehicle_id;
                $carHost->car_hosts_id = $user->id;
                $carHost->km_driven = $request->km_driven ?? NULL;
                $carHost->car_host_pickup_location_id = $vehicleLocation;
                $carHost->save();

                if($request->vehicle_model_id != ''){
                    $vehicleModelMinPrice = VehicleModelPriceDetail::select('id', 'rental_price')->where('vehicle_model_id', $request->vehicle_model_id)->where('type', 1)->get();
                    $vehicleModelMaxPrice = VehicleModelPriceDetail::select('id', 'rental_price')->where('vehicle_model_id', $request->vehicle_model_id)->where('type', 2)->get();
                    if(isset($vehicleModelMinPrice[0]) && isset($vehicleModelMaxPrice[0])){
                        $getMidlleRentalPrice = getMiddlePrice($vehicleModelMinPrice[0]->rental_price, $vehicleModelMaxPrice[0]->rental_price);
                        $rules = TripAmountCalculationRule::select('id', 'hours', 'multiplier')->orderBy('hours', 'desc')->get();
                        $pricingShowCase = $rules->map(function ($rule) use ($getMidlleRentalPrice) {
                            $tripAmount = $rule->multiplier * $getMidlleRentalPrice;
                            $unKMtripAmount = ($rule->multiplier * $getMidlleRentalPrice) * 1.3;
                            $perHourRate = $tripAmount / $rule->hours; // Calculate per hour rate based on the total trip amount and duration
                            $perHourRate = round($perHourRate, 2);
                            $duration = ($rule->hours >= 24) ? round($rule->hours / 24, 2) . ' days' : $rule->hours . ' hours';
                            $durationHoursLimit = calculateKmLimit($rule->hours, null);
                            $multiplier = $rule->multiplier;
                            $hours = $rule->hours;
                            return [
                                'duration' => $duration,
                                'per_hour_rate' => $perHourRate,
                                'trip_amount' => $tripAmount,
                                'trip_amount_km_limit' => $durationHoursLimit." Km",
                                'unlimited_km_trip_amount' => $unKMtripAmount,
                                'multiplier' => $multiplier,
                                'hours' => $hours,
                            ];
                        });
                        if(is_countable($pricingShowCase) && count($pricingShowCase) > 0){
                            foreach($pricingShowCase as $k => $v){
                                $vehiclePriceDetail = new VehiclePriceDetail();
                                $vehiclePriceDetail->vehicle_id = $vehicle->vehicle_id;
                                $vehiclePriceDetail->rental_price = $getMidlleRentalPrice;
                                $vehiclePriceDetail->hours = $v['hours'];
                                $vehiclePriceDetail->rate = $v['trip_amount'];
                                $vehiclePriceDetail->multiplier = $v['multiplier'];
                                $vehiclePriceDetail->duration = $v['duration'];
                                $vehiclePriceDetail->per_hour_rate = $v['per_hour_rate'];
                                $vehiclePriceDetail->trip_amount_km_limit = $v['trip_amount_km_limit'];
                                $vehiclePriceDetail->unlimited_km_trip_amount =  $v['unlimited_km_trip_amount'];
                                $vehiclePriceDetail->save();  
                            }
                        }
                        $vehicle->rental_price = $getMidlleRentalPrice;
                        $vehicle->extra_hour_rate = $getMidlleRentalPrice / 4;
                        $vehicle->save();
                    }
                }

                $vehicleModelId = $vehicle->model_id;
                $modelKmDetails = getModelKmDetail($vehicleModelId);
                $middleKmLimit = $modelKmDetails['middle_km_limit'];
                $vehicle->extra_km_rate = $middleKmLimit;

                // STORE DEFAULT MIDDLE DEPOSIT AMOUNT
                $modelDepositDetails = getModelDepositDetail($vehicleModelId);
                $middleDepositLimit = $modelDepositDetails['middle_deposit_limit'];
                $vehicle->deposit_amount = $middleDepositLimit;

                $vehicle->save();

            }

            if($vehicleDetailStatus == 'add'){
                return $this->successResponse(['vehicle' => $vehicle], 'Vehicle details are added successfully');
            }else{
                return $this->successResponse(['vehicle' => $vehicle], 'Vehicle details will store once admin will approved');
            }
        }else{
            return $this->errorResponse('User not Found');
        }
    }

    public function setVehicleProperties(Request $request){
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
            'engine_cc' => 'nullable|numeric',
            'fuel_capacity' => 'nullable|numeric',
            'fuel_type_id' => 'required|numeric|exists:vehicle_fuel_types,fuel_type_id',
            'mileage' => 'required|numeric',
            'transmission_id' => 'required|numeric|exists:vehicle_transmissions,transmission_id',
            'seating_capacity' => 'nullable|numeric',
            'color' => 'nullable|string|max:50',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $VehicleProperty = VehicleProperty::where('vehicle_id', $request->vehicle_id)->first();
        if($VehicleProperty == ''){
            $VehicleProperty = new VehicleProperty();    
        }
        $VehicleProperty->vehicle_id = $request->vehicle_id;
        $VehicleProperty->mileage = isset($request->mileage)?$request->mileage:NULL;
        $VehicleProperty->fuel_type_id = isset($request->fuel_type_id)?$request->fuel_type_id:NULL;
        $VehicleProperty->transmission_id  = isset($request->transmission_id)?$request->transmission_id:NULL;
        $VehicleProperty->seating_capacity = isset($request->seating_capacity) ? $request->seating_capacity : NULL;
        $VehicleProperty->engine_cc = isset($request->engine_cc)?$request->engine_cc:NULL;
        $VehicleProperty->fuel_capacity = isset($request->fuel_capacity)?$request->fuel_capacity:NULL;
        $VehicleProperty->created_at = now();
        $VehicleProperty->updated_at = now();
        $VehicleProperty->save();

        $vehicle = Vehicle::where('vehicle_id', $request->vehicle_id)->first();
        if($vehicle != ''){
            $vehicle->color = isset($request->color) ? $request->color : NULL;
            $vehicle->save();
        }

        return $this->successResponse($VehicleProperty, 'Vehicle Properties stored Successfully');
    }

    public function storeVehicleImages(Request $request){
        $vehicle = Vehicle::where('vehicle_id', $request->vehicle_id)->first();
        $typeId = 1;
        if($vehicle){
            $typeId = $vehicle->model->category->vehicleType->type_id;
        }
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
            'vehicle_interior_imgs' => 'nullable|array',
            'vehicle_interior_imgs.*' => 'image|max:80000',
            'vehicle_exterior_imgs' => 'required|array',
            'vehicle_exterior_imgs.*' => 'image|max:80000',
        ],[
            'vehicle_interior_imgs.*.max' => 'Vehicle image size must be less than 80MB',
            'vehicle_exterior_imgs.*.max' => 'Vehicle image size must be less than 80MB',
        ]);
        $validator->sometimes('vehicle_interior_imgs', 'required', function () use ($typeId) {
            return $typeId == 1;
        });
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        // NEW CODE
        $imageStatus = "add";
        if(is_countable($request->file('vehicle_interior_imgs')) && count($request->file('vehicle_interior_imgs')) > 0){
            $carHostVehicleImage = CarHostVehicleImage::where(['vehicles_id' => $request->vehicle_id, 'image_type' => 2])->get(); //image_type = 2 means Vehicle Interior images
            if(is_countable($carHostVehicleImage) && count($carHostVehicleImage) > 0){
                $carHostVehicleImageTemp = CarHostVehicleImageTemp::where(['vehicles_id' => $request->vehicle_id, 'image_type' => 2])->get();
                if(is_countable($carHostVehicleImageTemp) && count($carHostVehicleImageTemp) > 0){
                    $this->unlinkImages($carHostVehicleImageTemp);
                }
                foreach ($request->file('vehicle_interior_imgs') as $key => $image) {
                    $filename = 'Interior_'.$request->vehicle_id.'_'.$key.'_'.time() . '.' . $image->getClientOriginalExtension();
                    $image->move(public_path('images/car_host'), $filename);
                    $carHostVehicleImage = new CarHostVehicleImageTemp();
                    $carHostVehicleImage->vehicles_id = $request->vehicle_id;
                    $carHostVehicleImage->image_type = 2;
                    $carHostVehicleImage->vehicle_img = $filename;
                    $carHostVehicleImage->save();
                }
                $imageStatus = "update";
            }else{
                foreach ($request->file('vehicle_interior_imgs') as $key => $image) {
                    $filename = 'Interior_'.$request->vehicle_id.'_'.$key.'_'.time() . '.' . $image->getClientOriginalExtension();
                    $image->move(public_path('images/car_host'), $filename);
                    $carHostVehicleImage = new CarHostVehicleImage();
                    $carHostVehicleImage->vehicles_id = $request->vehicle_id;
                    $carHostVehicleImage->image_type = 2;
                    $carHostVehicleImage->vehicle_img = $filename;
                    $carHostVehicleImage->save();
                }
                $imageStatus = "add";
            }
        }
        if(is_countable($request->file('vehicle_exterior_imgs')) && count($request->file('vehicle_exterior_imgs')) > 0){
            $carHostVehicleImage = CarHostVehicleImage::where(['vehicles_id' => $request->vehicle_id, 'image_type' => 3])->get(); //image_type = 3 means Vehicle Exterior images
            if(is_countable($carHostVehicleImage) && count($carHostVehicleImage) > 0){
                $carHostVehicleImageTemp = CarHostVehicleImageTemp::where(['vehicles_id' => $request->vehicle_id, 'image_type' => 3])->get();
                if(is_countable($carHostVehicleImageTemp) && count($carHostVehicleImageTemp) > 0){
                    $this->unlinkImages($carHostVehicleImageTemp);
                }
                foreach ($request->file('vehicle_exterior_imgs') as $key => $image) {
                    $filename = 'Exterior_'.$request->vehicle_id.'_'.$key.'_'.time() . '.' . $image->getClientOriginalExtension();   
                    $image->move(public_path('images/car_host'), $filename);
                    $carHostVehicleImage = new CarHostVehicleImageTemp();
                    $carHostVehicleImage->vehicles_id = $request->vehicle_id;
                    $carHostVehicleImage->image_type = 3;
                    $carHostVehicleImage->vehicle_img = $filename;
                    $carHostVehicleImage->save();
                }
                $imageStatus = "update";
            }else{
                foreach ($request->file('vehicle_exterior_imgs') as $key => $image) {
                    $filename = 'Exterior_'.$request->vehicle_id.'_'.$key.'_'.time() . '.' . $image->getClientOriginalExtension();   
                    $image->move(public_path('images/car_host'), $filename);
                    $carHostVehicleImage = new CarHostVehicleImage();
                    $carHostVehicleImage->vehicles_id = $request->vehicle_id;
                    $carHostVehicleImage->image_type = 3;
                    $carHostVehicleImage->vehicle_img = $filename;
                    $carHostVehicleImage->save();
                }
                $imageStatus = "add";
            }   
        }
        if($imageStatus == "add"){
            return $this->successResponse(null, 'Vehicle images are uploaded successfully');
        }else{
            return $this->errorResponse('Your uploaded Vehicle images will stored once Admin will approved');
        }
    }

    public function unlinkImages($carHostVehicleImage){
        foreach ($carHostVehicleImage as $key => $value) {
            $filePath = public_path().'/images/car_host/'.$value->vehicle_img;
            if(file_exists($filePath)){
                unlink($filePath);
            }
            $value->delete();
        }
    }

    public function storeVehicleFeatures(Request $request){
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
            'feature_id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        // NEW CODE
        $featureStatus = 'add';
        if(isset($request->feature_id) && $request->feature_id != ''){
            $carHostVehicleFeature = CarHostVehicleFeature::where('vehicles_id', $request->vehicle_id)->get();
            $featureArr = explode(',', $request->feature_id);
            if(isset($carHostVehicleFeature) && is_countable($carHostVehicleFeature) && count($carHostVehicleFeature) > 0){
                $carHostVehicleFeature = CarHostVehicleFeatureTemp::where('vehicles_id', $request->vehicle_id)->delete();
                foreach ($featureArr as $key => $value) {
                    $carHostVehicleFeature = new CarHostVehicleFeatureTemp();
                    $carHostVehicleFeature->vehicles_id = $request->vehicle_id;
                    $carHostVehicleFeature->feature_id = $value;
                    $carHostVehicleFeature->save();
                }
                $featureStatus = 'update';
            }else{
                foreach ($featureArr as $key => $value) {
                    $carHostVehicleFeature = new CarHostVehicleFeature();
                    $carHostVehicleFeature->vehicles_id = $request->vehicle_id;
                    $carHostVehicleFeature->feature_id = $value;
                    $carHostVehicleFeature->save();
                }
            }
        }

        if($featureStatus == 'add'){
            return $this->successResponse(['vehicle_feature' => $carHostVehicleFeature], 'Vehicle features are added successfully');
        }else{
            return $this->successResponse(['vehicle_feature' => $carHostVehicleFeature], 'Vehicle features will store once admin will approved');
        }
    }

    public function storeVehicleDesc(Request $request){
         $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
            'vehicle_description' => 'required|max:500',
            'nick_name' => 'nullable'
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $vehicle = Vehicle::where('vehicle_id', $request->vehicle_id)->first();
        if(isset($vehicle) && $vehicle != ''){
            $vehicle->description = $request->vehicle_description ?? '';
            $vehicle->nick_name = isset($request->nick_name)?$request->nick_name:NULL;
            $vehicle->save();    

            return $this->successResponse($vehicle, 'Vehicle details are stored Successfully');
        }else{
            return $this->errorResponse('Vehicle not Found');
        }
    }

    public function getDistanceInKm($lat1, $lon1, $lat2, $lon2) {
        // Convert degrees to radians
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +
                cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos(min(max($dist, -1), 1)); // Clamp value to [-1,1] to avoid NaN
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $km = $miles * 1.609344;

        return $km;
    }

    public function storePickupLocationDetails(Request $request){
        $parkingTypes = config('global_values.vehicle_parking_type');
        $parkingTypes = array_keys($parkingTypes);
        $parkingTypes = implode(',', $parkingTypes);
        $validator = Validator::make($request->all(), [
            'car_pickup_location_id' => 'nullable|exists:car_host_pickup_locations,id',
            'vehicle_id' => 'nullable|exists:vehicles,vehicle_id',
            'latitude' => 'required',
            'longitude' => 'required',
            'location' => 'required|max:500',
            'parking_type' => 'required|in:'.$parkingTypes, 
            'parking_spot_imgs.*' => 'required|mimetypes:image/heic,image/heif,image/jpeg,image/png,image/jpg,image/bmp,image/gif,image/svg,image/webp|max:10000',
            'is_primary' => 'required|in:1,2', //1 = Primary, 2 = Not primary
        ],[
            'parking_spot_imgs.*.max' => 'Parking Spot image size must be less than 10MB',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
       
        $user = Auth::guard('api-carhost')->user();
        $vehicle = Vehicle::where('vehicle_id', $request->vehicle_id)->first();

        $checkLocation = CarHostPickupLocation::where('car_hosts_id', $user->id)->where('is_deleted', 0)->count();
        if($checkLocation >= 1){
            $checkLocation = CarHostPickupLocation::where('car_hosts_id', $user->id)->where('is_deleted', 0)->where('is_primary', 1)->first();
            $setting = Setting::select('location_km_distance_val')->first();
            $locationKmDistanceVal = $setting->location_km_distance_val;
            $existingLat = $checkLocation->latitude;
            $existingLong = $checkLocation->longitude;
            $reqLat = $request->latitude;
            $reqLong = $request->longitude;
            $radius = $locationKmDistanceVal; 
            $distance = 6371 * acos(
                cos(deg2rad($reqLat)) *
                cos(deg2rad($existingLat)) *
                cos(deg2rad($existingLong - $reqLong)) +
                sin(deg2rad($reqLat)) *
                sin(deg2rad($existingLat))
            );
            if($distance > $locationKmDistanceVal){
                return $this->errorResponse('Location is Invalid');
            }
        }

        // NEW CODE
        if(isset($request->car_pickup_location_id) && $request->car_pickup_location_id != ''){
            $checkLocation = CarHostPickupLocation::where('id', $request->car_pickup_location_id)->where('is_deleted', 0)->first();
            if(isset($checkLocation) && $checkLocation != ''){
                if(is_countable($request->file('parking_spot_imgs')) && count($request->file('parking_spot_imgs')) > 0){
                    $carHostVehicleImageTemp = CarHostVehicleImageTemp::where(['car_host_pickup_locations_id' => $request->car_pickup_location_id,'image_type' => 1])->get();
                    if(is_countable($carHostVehicleImageTemp) && count($carHostVehicleImageTemp) > 0){
                        foreach ($carHostVehicleImageTemp as $key => $value) {
                            $filePath = public_path().'/images/car_host/'.$value->vehicle_img;
                            if(file_exists($filePath)){
                                unlink($filePath);
                            }
                            $value->delete();
                        }
                    }                   
                    foreach ($request->file('parking_spot_imgs') as $key => $image) {
                        $filename = 'ParkingSpot_'.$checkLocation->id.'_'.$key.'_'.time() . '.' . $image->getClientOriginalExtension();
                        $image->move(public_path('images/car_host'), $filename);
                        $carHostVehicleImageTemp = new CarHostVehicleImageTemp();
                        $carHostVehicleImageTemp->car_host_pickup_locations_id = $checkLocation->id;
                        $carHostVehicleImageTemp->image_type = 1;
                        $carHostVehicleImageTemp->vehicle_img = $filename;
                        $carHostVehicleImageTemp->save();
                    }
                }
                $carPickupLocationTemp = CarHostPickupLocationTemp::where(['car_host_pickup_locations_id' => $checkLocation->id, 'car_hosts_id' => $user->id])->first();
                if($carPickupLocationTemp == ''){
                    $carPickupLocationTemp = new CarHostPickupLocationTemp();   
                }
                $carPickupLocationTemp->car_host_pickup_locations_id = $checkLocation->id;
                $carPickupLocationTemp->car_hosts_id = $user->id;
                $carPickupLocationTemp->latitude = (double)$request->latitude ?? 0.00;
                $carPickupLocationTemp->longitude = (double)$request->longitude ?? 0.00;
                $carPickupLocationTemp->location = $request->location;
                $carPickupLocationTemp->parking_type_id = (int)$request->parking_type;
                $carPickupLocationTemp->city_id = $vehicle->temp_city_id ?? NULL;
                // if($request->latitude != '' && $request->longitude != ''){
                //     $nearestBranch = City::nearest($request->latitude, $request->longitude);
                //     $carPickupLocationTemp->city_id = $nearestBranch->id ?? '';
                //     $carPickupLocationTemp->name = $nearestBranch->name ?? '';
                // }
                $carPickupLocationTemp->is_primary = 1; 
                $carPickupLocationTemp->save();
                return $this->errorResponse('Your location will be add once admin will approved');
            }else{
                return $this->errorResponse('Pickup location details are not found');
            }
        }else{
            $checkLocationCnt = CarHostPickupLocation::where('car_hosts_id', $user->id)->where('is_deleted', 0)->count();
            $locations = CarHostPickupLocation::where('car_hosts_id', $user->id)->where('is_deleted', 0)->first();
            if ($checkLocationCnt == 1) {
                $lat1 = $locations->latitude ?? 0.0;
                $lon1 = $locations->longitude ?? 0.0;
                $lat2 = $request->latitude ?? 0.0;
                $lon2 = $request->longitude ?? 0.0;
                $distanceKm = getDistanceInKm($lat1, $lon1, $lat2, $lon2);
                if ($distanceKm > 25) {
                    return $this->errorResponse('You cannot add a Pickup Location more than 25 KM away.');
                }

            } else if ($checkLocationCnt >= 2) {
                return $this->errorResponse('You can not add more than 2 host Pickup Locations');
            }
            $carPickupLocation = new CarHostPickupLocation();   
            $carPickupLocation->car_hosts_id = $user->id;
            $carPickupLocation->latitude = (double)$request->latitude ?? 0.00;
            $carPickupLocation->longitude = (double)$request->longitude ?? 0.00;
            $carPickupLocation->location = $request->location;
            $carPickupLocation->parking_type_id = (int)$request->parking_type;
            $carPickupLocation->city_id = $vehicle->temp_city_id ?? NUll;
            // if($request->latitude != '' && $request->longitude != ''){
            //     $nearestBranch = City::nearest($request->latitude, $request->longitude);
            //     $carPickupLocationTemp->city_id = $nearestBranch->id ?? '';
            //     $carPickupLocationTemp->name = $nearestBranch->name ?? '';
            // }
            $primaryStatus = $request->is_primary;
            $locationStatus = false;
            if($checkLocationCnt == 0){
                $primaryStatus = 1;
                $carPickupLocation->is_primary = $request->is_primary;
                $locationStatus = true;
            }
            if(isset($request->is_primary) && $request->is_primary == 1){
                $getLocations = CarHostPickupLocation::where('car_hosts_id', $user->id)->get();
                if(isset($getLocations) && is_countable($getLocations) && count($getLocations) > 0){
                    foreach($getLocations as $k => $v){
                        $v->is_primary = 2;
                        $v->save();
                    }
                }
                $carPickupLocation->is_primary = $request->is_primary;
                $locationStatus = true;
            }elseif(isset($request->is_primary) && $request->is_primary == 2){
                $hostLocation = CarHostPickupLocation::where('car_hosts_id', $user->id)->where('is_primary', 1)->first();
                if($hostLocation != ''){
                    $carPickupLocation->is_primary = $request->is_primary;
                    $locationStatus = true;
                }elseif($checkLocationCnt == 0){
                    $carPickupLocation->is_primary = 1;
                    $locationStatus = true;
                }
            }
            if($locationStatus == true){
                $carPickupLocation->save();
            }
            if(isset($request->vehicle_id) && $request->vehicle_id != ''){ 
                $vehicle = CarEligibility::where('vehicle_id', $request->vehicle_id)->first();
                if($vehicle != ''){
                    $vehicle->car_host_pickup_location_id = $carPickupLocation->id;
                    $vehicle->save();
                }
            }
            if(is_countable($request->file('parking_spot_imgs')) && count($request->file('parking_spot_imgs')) > 0){
                foreach ($request->file('parking_spot_imgs') as $key => $image) {
                    $filename = 'ParkingSpot_'.$carPickupLocation->id.'_'.$key.'_'.time() . '.' . $image->getClientOriginalExtension();
                    $image->move(public_path('images/car_host'), $filename);
                    $carHostVehicleImage = new CarHostVehicleImage();
                    $carHostVehicleImage->car_host_pickup_locations_id = $carPickupLocation->id;
                    $carHostVehicleImage->image_type = 1;
                    $carHostVehicleImage->vehicle_img = $filename; 
                    $carHostVehicleImage->save();
                }
            }
            if($locationStatus == true){
                $carPickupLocation->latitude = doubleval($carPickupLocation->latitude);
                $carPickupLocation->longitude = doubleval($carPickupLocation->longitude);
                return $this->successResponse($carPickupLocation, 'Vehicle Pickup location added successfully');
            }else{
                return $this->errorResponse('Please make any one Location as primary first');
            }
        }
    }

    public function storeHoldVehicleDates(Request $request){
        // Check if the car_eligibilities table is empty
        if (CarEligibility::count() === 0) {
            return $this->successResponse('No car eligibilities found.');
        }
     
        // NEW CODE
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date'
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $data['start_date'] = $request->start_date;
        $data['end_date'] = $request->end_date;
        $data['reason'] = '';
        $requestStart = Carbon::createFromFormat('d-m-Y H:i A', $request->start_date);
        $requestEnd   = Carbon::createFromFormat('d-m-Y H:i A', $request->end_date);
        $data = json_encode($data);
        $holdingDates = json_decode($data);
        $vehicle = Vehicle::where('vehicle_id', $request->vehicle_id)->first();
        if($vehicle != ''){
            if($vehicle->availability_calendar != ''){
                $dateValidated = true;
                $existingDates = json_decode($vehicle->availability_calendar, true);
                if(isset($existingDates) && is_countable($existingDates) && count($existingDates) > 0){
                    foreach($existingDates as $key => $val){
                        $startDate = Carbon::createFromFormat('d-m-Y H:i A', $val['start_date']);
                        $endDate = Carbon::createFromFormat('d-m-Y H:i A', $val['end_date']);
                        $overlaps = $requestStart->between($startDate, $endDate) || 
                                    $requestEnd->between($startDate, $endDate) || 
                                    ($requestStart > $startDate && $requestStart < $endDate) ||
                                    ($requestEnd > $startDate && $requestEnd < $endDate);
                        if ($overlaps) {
                            return $this->errorResponse('Date Range already exist');
                        }
                    }
                }
                if(is_array($existingDates)){
                    $holding_dates = array_merge($existingDates, [$holdingDates]);
                }else{
                    $holding_dates = [$holdingDates];
                }
            }else{  
                $holding_dates = [$holdingDates];
            }
            $vehicle->availability_calendar = json_encode($holding_dates);
            $vehicle->save();
        }
        return $this->successResponse($vehicle, 'Vehicle holding dates are stored successfully'); 
    }

    public function deleteHoldVehicleDate(Request $request){
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
            'start_date' => 'required|date',
            'end_date' => 'required|date'
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $vehicle = Vehicle::where('vehicle_id', $request->vehicle_id)->first();
        if($vehicle != ''){
            if($vehicle->availability_calendar != ''){
                $holding_dates = json_decode($vehicle->availability_calendar, true);
                foreach ($holding_dates as $key => $value) {
                    if(isset($value['start_date']) && $value['start_date'] == $request->start_date && isset($value['end_date']) && $value['end_date'] == $request->end_date){
                        unset($holding_dates[$key]);
                    }
                }
                $vehicle->availability_calendar = json_encode(array_values($holding_dates));
                $vehicle->save();
                return $this->successResponse(json_decode($vehicle->availability_calendar), 'Vehicle holding date is deleted successfully');
            }else{
                return $this->errorResponse('No holding dates found for this vehicle');
            }
        }else{
            return $this->errorResponse('Vehicle not found');
        }
    }
    
    public function storeVehicleRcDetails(Request $request)
    {
        $vehicleRcDocImgs = VehicleDocument::where('vehicle_id', $request->vehicle_id)->where('document_type', 'rc_doc')->get();
        $rules = [
            'vehicle_id'      => 'required|exists:vehicles,vehicle_id',
            'rc_number'       => 'required',
            'doc_image'       => 'nullable|array|min:2|max:10000',
            'doc_image.*'     => 'mimetypes:image/heic,image/heif,image/jpeg,image/png,image/jpg,image/bmp,image/gif,image/svg,image/webp',
            'rc_expiry_date'  => 'required|date|after:' . Carbon::now()->setTimezone('Asia/Kolkata')->addYears(2)->toDateString(),
        ];
        $messages = [
            'doc_image.max' => "RC front image must be less than 10 MB",
        ];
        if ($vehicleRcDocImgs->count() === 0) {
            $rules['doc_image'] = 'required|array|min:2|max:10000';
        }
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        // NEW CODE
        $vehicleDoc = '';
        $status = 'add';
        if(isset($vehicleRcDocImgs) && is_countable($vehicleRcDocImgs) && count($vehicleRcDocImgs) > 0){
            $vehicleRcDocTemp = VehicleDocumentTemp::where('vehicle_id', $request->vehicle_id)->where('document_type', 'rc_doc')->get();
            if(isset($request->doc_image) && is_countable($request->doc_image) && count($request->doc_image) > 0){
                foreach ($vehicleRcDocTemp as $k => $v) {
                    $parsedUrl = parse_url($v->document_image_url);
                    $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : ''; 
                    $path = public_path($path);
                    if (file_exists($path)){
                        unlink($path);
                    }
                    $v->delete();
                }
                foreach($request->doc_image as $key => $val){
                    $extension = $val->getClientOriginalExtension();
                    $filename = 'doc_rc_img_'.$key.'_'.time() . '_' . uniqid() . '.' . $extension;
                    $val->move(public_path('images/documents/'), $filename);
                    $vehicleDoc = new VehicleDocumentTemp();
                    $vehicleDoc->vehicle_id = $request->vehicle_id;
                    $vehicleDoc->document_type = 'rc_doc';
                    $vehicleDoc->id_number = $request->rc_number;
                    $vehicleDoc->expiry_date = date('Y-m-d', strtotime($request->rc_expiry_date));
                    $vehicleDoc->is_approved = 1;
                    $vehicleDoc->approved_by = 1;
                    $vehicleDoc->document_image_url = $filename;
                    $vehicleDoc->created_at = now();
                    $vehicleDoc->updated_at = now();
                    $vehicleDoc->save();
                }    
            }else{
                if(isset($vehicleRcDocTemp) && is_countable($vehicleRcDocTemp) && count($vehicleRcDocTemp) > 0){
                    foreach ($vehicleRcDocTemp as $k => $v) {
                        $v->id_number = $request->rc_number;
                        $v->expiry_date = date('Y-m-d', strtotime($request->rc_expiry_date));
                        $v->save();
                    }
                }else{
                    foreach ($vehicleRcDocImgs as $k => $v) {
                        $vehicleDoc = new VehicleDocumentTemp();
                        $vehicleDoc->vehicle_id = $request->vehicle_id;
                        $vehicleDoc->document_type = 'rc_doc';
                        $vehicleDoc->id_number = $request->rc_number;
                        $vehicleDoc->expiry_date = date('Y-m-d', strtotime($request->rc_expiry_date));
                        $vehicleDoc->is_approved = 1;
                        $vehicleDoc->approved_by = 1;
                        $vehicleDoc->document_image_url = $v->document_image_url;
                        $vehicleDoc->created_at = now();
                        $vehicleDoc->updated_at = now();
                        $vehicleDoc->save();
                    }
                }
            }
            $status = 'update';
            return $this->errorResponse('Vehicle RC details will update once admin will approved');
        }else{
            if(isset($request->doc_image) && is_countable($request->doc_image) && count($request->doc_image) > 0){
                foreach($request->doc_image as $key => $val){
                    $extension = $val->getClientOriginalExtension();
                    $filename = 'doc_rc_img_'.$key.'_'.time() . '_' . uniqid() . '.' . $extension;
                    $val->move(public_path('images/documents/'), $filename);
                    $vehicleDoc = new VehicleDocument();
                    $vehicleDoc->vehicle_id = $request->vehicle_id;
                    $vehicleDoc->document_type = 'rc_doc';
                    $vehicleDoc->id_number = $request->rc_number;
                    $vehicleDoc->expiry_date = date('Y-m-d', strtotime($request->rc_expiry_date));
                    $vehicleDoc->is_approved = 1;
                    $vehicleDoc->approved_by = 1;
                    $vehicleDoc->document_image_url = $filename;
                    $vehicleDoc->created_at = now();
                    $vehicleDoc->updated_at = now();
                    $vehicleDoc->save();
                }    
            }
            $vehicle = Vehicle::where('vehicle_id', $request->vehicle_id)->first();
            if($vehicle != ''){
                $vehicle->license_plate = $request->rc_number;
                $vehicle->save();
            }
            return $this->successResponse(null, 'Vehicle RC details are stored successfully');
        }
    }

    public function storeFastTagDetails(Request $request){
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
            'fast_tag' => 'required|in:0,1', //0 = Fasttag not Exist, 1 = Fasttag Exist
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $carEligibility = CarEligibility::where('vehicle_id', $request->vehicle_id)->first();
        if($carEligibility != ''){
            $carEligibility->fast_tag = $request->fast_tag;
            $carEligibility->save();
        }

        return $this->successResponse($carEligibility, 'Vehicle Fasttag information stored successfully');
    }

    public function storeListingControl(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
            'night_hours_id' => 'nullable|exists:night_hours,id',
            'night_time' => 'required|in:0,1', // 0 = Not Restricted, 1 = Restricted
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $carEligibility = CarEligibility::where('vehicle_id', $request->vehicle_id)->first();

        if ($carEligibility) {
            $carEligibility->night_time = $request->night_time;
            $carEligibility->night_hours_id = isset($request->night_hours_id) ? $request->night_hours_id : null;
            $carEligibility->save();
        } else {
            $carEligibility = CarEligibility::create([
                'vehicle_id' => $request->vehicle_id,
                'night_hours_id' => isset($request->night_hours_id) ? $request->night_hours_id : null,
                'night_time' => $request->night_time,
            ]);
        }

        return $this->successResponse($carEligibility, 'Vehicle night time information stored successfully');
    }

    public function storeFuelTransmission(Request $request){
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
            'fuel_type_id' => 'nullable|exists:vehicle_fuel_types,fuel_type_id', 
            'transmission_id' => 'nullable|exists:vehicle_transmissions,transmission_id', 
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $vehicleProperty = VehicleProperty::where('vehicle_id', $request->vehicle_id)->first();
        if($vehicleProperty == ''){
            $vehicleProperty = new VehicleProperty();
            $vehicleProperty->vehicle_id = $request->vehicle_id;
        }
        $vehicleProperty->fuel_type_id = $request->fuel_type_id;
        $vehicleProperty->transmission_id = $request->transmission_id;
        $vehicleProperty->save();

        return $this->successResponse($vehicleProperty, 'Vehicle Fuel and Transmission deails are stored');
    }

    public function updatePricingDetails(Request $request){
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
            'pricing_update_info' => 'required|json',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        if($request->vehicle_id != ''){
            $vehicle = Vehicle::where('vehicle_id', $request->vehicle_id)->first();
            $rentalPrice = $rentalPriceHour = 0;
            $vehicleModelId = $vehicle->model_id ?? '';
            if($vehicle != '' && $request->pricing_update_info != ''){
                // NEW CODE
                $pricingDetails = json_decode($request->pricing_update_info, true);
                $isVehiclePriceDetails = VehiclePriceDetail::where('vehicle_id', $vehicle->vehicle_id)->get();
                if(is_countable($pricingDetails) && count($pricingDetails) > 0){
                    if(isset($isVehiclePriceDetails) && is_countable($isVehiclePriceDetails) && count($isVehiclePriceDetails) > 0){
                        // UPDATE
                        foreach($pricingDetails as $key => $val){
                            $imitVal = (float)$val['rate']; 
                            if($val['hour'] == 'Deposit Range'){ // STORE DEPOSIT AMOUNT
                                $vehicle->updated_deposit_amount = $imitVal;    
                                $vehicle->updated_is_deposit_amount_show = $val['is_show'] ?? 0;    
                                $vehicle->save();
                            }elseif($val['hour'] == 'Kilometer Range'){ // STORE KM LIMIT
                                $vehicle->updated_extra_km_rate = $imitVal;    
                                $vehicle->save();
                            }
                        }
                        $priceDetailArr = $updatedDetails = [];
                        $messageList = '';
                        $updateStatus = true;
                        foreach ($pricingDetails as $key => $value) {
                            $priceDetailArr[$value['hour']] = $value['rate'];
                        }
                        if(is_countable($priceDetailArr) && count($priceDetailArr) > 0){
                            foreach($priceDetailArr as $hour => $rate){
                                if($rate > 0){
                                    $checkRate = checkRateWIthModelRate($hour, $rate, $vehicleModelId);
                                    if(isset($checkRate) && is_countable($checkRate) && count($checkRate) > 0)
                                    {
                                        if($checkRate['status'] == false){
                                            $updateStatus = false;
                                            $messageList .=  $checkRate['message'].'. ';
                                        }
                                    }
                                }
                            }
                        }  
                        if($updateStatus == false){
                            return $this->errorResponse($messageList);
                        } 
                        asort($priceDetailArr);// make sort based on its value on ascending order
                        if(is_countable($priceDetailArr) && count($priceDetailArr) > 0){
                            foreach ($priceDetailArr as $key => $value) {
                                if($value > 0){
                                    $rentalPrice = $value;
                                    $rentalPriceHour = $key;
                                    break;
                                }
                            }
                        }
                        krsort($priceDetailArr); //make sort based on its key on descending order   
                        $multipliers = []; // Array to hold the multipliers
                        foreach ($priceDetailArr as $key => $value) {
                            $multiplierVal = 0;
                            if($rentalPrice <= $value){
                                $multiplierVal = ($value / $rentalPrice);
                            }
                            $multipliers[$key][$value] = round($multiplierVal, 2);
                        }
                        $vehiclePriceDetails = VehiclePriceDetailTemp::where('vehicle_id', $vehicle->vehicle_id)->get();
                        if(is_countable($vehiclePriceDetails) && count($vehiclePriceDetails) > 0){
                            foreach ($vehiclePriceDetails as $key => $value) {
                                $value->delete();
                            }
                        }
                        if(is_countable($multipliers) && count($multipliers) > 0){
                            foreach ($multipliers as $key => $value) {
                                if($key >= 0 && !is_string($key)){
                                    $vehiclePriceDetail = new VehiclePriceDetailTemp();
                                    $vehiclePriceDetail->vehicle_id = $vehicle->vehicle_id;
                                    $vehiclePriceDetail->rental_price = $rentalPrice;
                                    $vehiclePriceDetail->hours = $key;
                                    foreach ($value as $k => $v) {
                                        $vehiclePriceDetail->rate = $k;
                                        $vehiclePriceDetail->multiplier = $v;
                                        $perHourRate = $k / $key;
                                        $vehiclePriceDetail->per_hour_rate = number_format(($perHourRate), 2);
                                        $vehiclePriceDetail->unlimited_km_trip_amount = $k * 1.3;
                                    }
                                    $vehiclePriceDetail->duration = ($key >= 24) ? round($key / 24, 2) . ' days' : $key . ' hours';
                                    $vehicleTypeName = $vehicle->model->category->vehicleType->name ?? null;
                                    $vehiclePriceDetail->trip_amount_km_limit = calculateKmLimit($key, $vehicleTypeName)." Km";
                                    $vehiclePriceDetail->save(); 
                                    $updatedDetails[] = $vehiclePriceDetail;

                                    $vehicle->rental_price = $rentalPrice;
                                    $vehicle->save();

                                    $targetHour = $vehiclePriceDetail->hours;
                                    $isShow = 1;
                                    foreach ($pricingDetails as $item) {
                                        if (isset($item['hour'], $item['is_show']) && $item['hour'] == $targetHour) {
                                            if($item['is_show'] == 0){
                                                $vehiclePriceDetail->is_show = 0;
                                                $vehiclePriceDetail->save();
                                            }
                                        }
                                    }
                                }
                            }
                            return $this->errorResponse('Pricing details will be updated after admin approval');
                        }
                    }else{
                        // ADD
                        foreach($pricingDetails as $key => $val){
                            $imitVal = (float)$val['rate']; 
                            if($val['hour'] == 'Deposit Range'){ // STORE DEPOSIT AMOUNT
                                $vehicle->deposit_amount = $imitVal;    
                                $vehicle->is_deposit_amount_show = $val['is_show'] ?? 0;    
                                $vehicle->save();
                            }elseif($val['hour'] == 'Kilometer Range'){ // STORE KM LIMIT
                                $vehicle->extra_km_rate = $imitVal;    
                                $vehicle->save();
                            }
                        }
                        $priceDetailArr = $updatedDetails = [];
                        $messageList = '';
                        $updateStatus = true;
                        foreach ($pricingDetails as $key => $value) {
                            $priceDetailArr[$value['hour']] = $value['rate'];
                        }
                        if(is_countable($priceDetailArr) && count($priceDetailArr) > 0){
                            foreach($priceDetailArr as $hour => $rate){
                                if($rate > 0){
                                    $checkRate = checkRateWIthModelRate($hour, $rate, $vehicleModelId);
                                    if(isset($checkRate) && is_countable($checkRate) && count($checkRate) > 0)
                                    {
                                        if($checkRate['status'] == false){
                                            $updateStatus = false;
                                            $messageList .=  $checkRate['message'].'. ';
                                        }
                                    }
                                }
                            }
                        }   
                        if($updateStatus == false){
                            return $this->errorResponse($messageList);
                        }                     
                        asort($priceDetailArr);// make sort based on its value on ascending order
                        if(is_countable($priceDetailArr) && count($priceDetailArr) > 0){
                            foreach ($priceDetailArr as $key => $value) {
                                if($value > 0){
                                    $rentalPrice = $value;
                                    $rentalPriceHour = $key;
                                    break;
                                }
                            }
                        }
                        krsort($priceDetailArr); //make sort based on its key on descending order   
                        $multipliers = []; // Array to hold the multipliers
                        foreach ($priceDetailArr as $key => $value) {
                            $multiplierVal = 0;
                            if($rentalPrice <= $value){
                                $multiplierVal = ($value / $rentalPrice);
                            }
                            $multipliers[$key][$value] = round($multiplierVal, 2);
                        }
                        $vehiclePriceDetails = VehiclePriceDetail::where('vehicle_id', $vehicle->vehicle_id)->get();
                        if(is_countable($vehiclePriceDetails) && count($vehiclePriceDetails) > 0){
                            foreach ($vehiclePriceDetails as $key => $value) {
                                $value->delete();
                            }
                        }
                        if(is_countable($multipliers) && count($multipliers) > 0){
                            foreach ($multipliers as $key => $value) {
                                if($key >= 0 && !is_string($key)){
                                    $vehiclePriceDetail = new VehiclePriceDetail();
                                    $vehiclePriceDetail->vehicle_id = $vehicle->vehicle_id;
                                    $vehiclePriceDetail->rental_price = $rentalPrice;
                                    $vehiclePriceDetail->hours = $key;
                                    foreach ($value as $k => $v) {
                                        $vehiclePriceDetail->rate = $k;
                                        $vehiclePriceDetail->multiplier = $v;
                                        $perHourRate = $k / $key;
                                        $vehiclePriceDetail->per_hour_rate = number_format(($perHourRate), 2);
                                        $vehiclePriceDetail->unlimited_km_trip_amount = $k * 1.3;
                                    }
                                    $vehiclePriceDetail->duration = ($key >= 24) ? round($key / 24, 2) . ' days' : $key . ' hours';
                                    $vehicleTypeName = $vehicle->model->category->vehicleType->name ?? null;
                                    $vehiclePriceDetail->trip_amount_km_limit = calculateKmLimit($key, $vehicleTypeName)." Km";
                                    $vehiclePriceDetail->save(); 
                                    $updatedDetails[] = $vehiclePriceDetail;

                                    $vehicle->rental_price = $rentalPrice;
                                    $vehicle->save();

                                    $targetHour = $vehiclePriceDetail->hours;
                                    $isShow = 1;
                                    foreach ($pricingDetails as $item) {
                                        if (isset($item['hour'], $item['is_show']) && $item['hour'] == $targetHour) {
                                            if($item['is_show'] == 0){
                                                $vehiclePriceDetail->is_show = 0;
                                                $vehiclePriceDetail->save();
                                            }
                                        }
                                    }
                                }
                            }
                            return $this->successResponse(['vehicle_pricing_control' => $updatedDetails], 'Pricing Update Info updated successfully');
                        }
                    }
                }else{
                    return $this->errorResponse('Pricing update info is not Found');
                }
            }else{
                return $this->errorResponse('Vehicle not found');
            }
        }else{
            return $this->errorResponse('Vehicle not found');
        }
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

    public function publishVehicle(Request $request){
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
            'publish' => 'required|in:1,0',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $vehicle = Vehicle::where('vehicle_id', $request->vehicle_id)->where('is_deleted', 0)->first();
        $checkPriceDetails = VehiclePriceDetail::where('vehicle_id', $request->vehicle_id)->first();
        if($vehicle != ''){
            if(!($vehicle->rental_price > 0) || $checkPriceDetails == ''){
                return $this->errorResponse('Please add Price Details for this vehicle before publishing it');
            }
        }else{
            return $this->errorResponse('Vehicle not found');
        }
        $checkLocationDetails = CarEligibility::where('vehicle_id', $request->vehicle_id)->where('car_host_pickup_location_id', '!=', NULL)->exists();
        if($checkLocationDetails == false){
            return $this->errorResponse('Please associate any Location with this Vehicle before publishing it');
        }

        // CHECK IF VEHICLE HAS ALL REQUIRED DETAILS
        $checkVehicleImgs = CarHostVehicleImage::where(['vehicles_id' => $request->vehicle_id])->exists();
        $checkVehicleFeatures = CarHostVehicleFeature::where('vehicles_id', $request->vehicle_id)->exists();
        $checkVehicleProperties = VehicleProperty::where('vehicle_id', $request->vehicle_id)->exists();
        $checkVehicleDesc = Vehicle::where('vehicle_id', $request->vehicle_id)->where('description', '!=', '')->whereNotNull('description')->exists();
        $checkVehicleLicenseChassis = Vehicle::where('vehicle_id', $request->vehicle_id)->where('license_plate', '!=', '')->whereNotNull('license_plate')->exists();
        $carEligibility = CarEligibility::where('vehicle_id', $request->vehicle_id)->where('car_host_pickup_location_id', '!=', '')->exists();
        $vehicleRcDocStatus = VehicleDocument::where(['vehicle_id' => $request->vehicle_id, 'document_type' => 'rc_doc', 'is_approved' => 1])->get();
        $vehicleDocStatus = false;
        if(is_countable($vehicleRcDocStatus) && count($vehicleRcDocStatus)){
            $vehicleDocStatus = true;
        }
        $checkVehiclePublishStatus = Vehicle::where('vehicle_id', $request->vehicle_id)->where('publish', 1)->exists();
        $checkBankStatus = false;
        //$checkPanStatus = false;
        if(Auth::guard('api-carhost')->check() && $this->userAuthDetails){
            $checkBankStatus = CarHostBank::where('car_hosts_id', $this->userAuthDetails->id)->exists();
            $checkBankStatus = true;
            // if($this->userAuthDetails->pan_number != NULL || $this->userAuthDetails->pan_number != ''){
            //     $checkPanStatus = true;
            // }
        }

        if($request->publish == 1){
            if($checkVehicleImgs == false){
                return $this->errorResponse('Please upload Vehicle Images before publishing it');
            }
            if($checkVehicleFeatures == false){
                return $this->errorResponse('Please add Vehicle Features before publishing it');
            }
            if($checkVehicleDesc == false){
                return $this->errorResponse('Please add Vehicle Description before publishing it');
            }
            // if($checkVehicleLicenseChassis == false){
            //     return $this->errorResponse('Please add Vehicle License Number and chassis number before publishing it');
            // }
            if($carEligibility == false){
                return $this->errorResponse('Please select Location before publishing it');
            }
            if($vehicleDocStatus == false){
                return $this->errorResponse('Please upload RC Card before publishing it');
            }
            if($checkBankStatus == false){
                return $this->errorResponse('Please add Bank details before publishing it');
            }
            // if($checkPanStatus == false){
            //     return $this->errorResponse('Please add PAN details before publishing it');
            // }
            if($checkVehiclePublishStatus == true){
                return $this->errorResponse('This vehicle is already published');
            }
            if($checkVehicleProperties == false){
                return $this->errorResponse('Please add Vehicle Properties before publishing it');
            }
        }
        
        if($request->publish == 1){
            $vehicle->apply_for_publish = 1;
            $vehicle->save();
            return $this->successResponse($vehicle, 'Your vehicle will published once admin will approve');
        }elseif($request->publish == 0){
            $vehicle->publish = 0;
            $vehicle->apply_for_publish = 0;
            $vehicle->save();
            return $this->successResponse($vehicle, 'Vehicle UnPublished successfully');
        }
    }
    
    public function deletePickuoLocation(Request $request){
        $validator = Validator::make($request->all(), [
            'car_host_pickup_location_id' => 'required|exists:car_host_pickup_locations,id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $checkVehicle = CarEligibility::where('car_host_pickup_location_id', $request->car_host_pickup_location_id)
        ->whereHas('vehicle', function ($q) {
            $q->where('is_deleted', 0);
        })->first();
        if($checkVehicle == ''){
            $carHostPickupLocation = CarHostPickupLocation::where('id', $request->car_host_pickup_location_id)->first();
            if($carHostPickupLocation != ''){
                $carHostPickupLocation->is_deleted = 1;
                $carHostPickupLocation->save();
                return $this->successResponse($carHostPickupLocation, 'Location deleted successfully');
            }else{
                return $this->errorResponse('Location not found');
            }
        }else{
            return $this->errorResponse("You can't delete this location due to its assign with any Vehicle");
        }
    }

    public function setVehicleLocation(Request $request){
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
            'car_host_pickup_location_id' => 'required|exists:car_host_pickup_locations,id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $isAssign = CarEligibility::where(['vehicle_id' => $request->vehicle_id, 'car_host_pickup_location_id' => $request->car_host_pickup_location_id])->exists();
        if($isAssign){
            return $this->errorResponse('This location has already been assigned to a vehicle.');
        }
        $vehicle = CarEligibility::where('vehicle_id', $request->vehicle_id)->first();
        if($vehicle != ''){
            $vehicle->car_host_pickup_location_id = $request->car_host_pickup_location_id;
            $vehicle->save();
            return $this->successResponse($vehicle, 'Vehicle Location updated successfully');
        }else{
            return $this->errorResponse('Vehicle not found');
        }
    }

    public function getCities(Request $request){
        $hostUser = Auth::guard('api-carhost')->user();
        $validator = Validator::make($request->all(), [
            'latitude' => 'nullable',
            'longitude' => 'nullable',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $cityUpdateStatus = false;
        $cityId = $cityName = '';
        $hostVehicleIds = CarEligibility::where('car_hosts_id', $hostUser->id)->pluck('vehicle_id')->toArray();
        if(isset($hostVehicleIds) && is_countable($hostVehicleIds) && count($hostVehicleIds) > 0){
            foreach($hostVehicleIds as $k => $v){
                $vehicleCityStatus = Vehicle::where('vehicle_id', $v)->whereNotNull('temp_city_id')->where('is_deleted', 0)->exists();
                if($vehicleCityStatus){
                    $vehicleCityStatus = Vehicle::where('vehicle_id', $v)->whereNotNull('temp_city_id')->where('is_deleted', 0)->first();
                    $cityId = $vehicleCityStatus->temp_city_id;
                    $cityName = $vehicleCityStatus->city->name ?? '';
                    $cityUpdateStatus = true;
                    break;
                }
            }
        }
        $page = $request->input('page');
        $pageSize = $request->input('page_size');
        $cities = City::where('is_deleted', 0);
        if(isset($request->latitude) && isset($request->longitude)){
            $nearestBranch = City::nearestNew($request->latitude, $request->longitude);
            if(isset($nearestBranch) && is_countable($nearestBranch) && count($nearestBranch) > 0){
                $cities = $cities->whereIn('id', $nearestBranch);
            }
        }

        if ($page !== null && $pageSize !== null) {
            $cities = $cities->paginate($pageSize, ['*'], 'page', $page);
            $decodedCities = json_decode(json_encode($cities->getCollection()->values()), FALSE);
            return $this->successResponse([
                'cities' => $decodedCities,
                'city_update_status' => $cityUpdateStatus,
                'city_id' => $cityId,
                'city_name' => $cityName,
                'pagination' => [
                    'total' => $cities->total(),
                    'per_page' => $cities->perPage(),
                    'current_page' => $cities->currentPage(),
                    'last_page' => $cities->lastPage(),
                    'from' => ($cities->currentPage() - 1) * $cities->perPage() + 1,
                    'to' => min($cities->currentPage() * $cities->perPage(), $cities->total()),
                ]], 'Cities fetched successfully');
        }else{
            $cities = $cities->get();
            $cities = [
                'cities' => $cities,
                'city_update_status' => $cityUpdateStatus,
                'city_id' => $cityId,
                'city_name' => $cityName,
            ];
            if(isset($cities) && is_countable($cities) && count($cities) > 0){
                return $this->successResponse($cities, 'Cities fetched successfully');
            }else{
                return $this->errorResponse('Cities not found');
            }
        }
    }

    public function uploadVehicleJourneyImages(Request $request){
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|exists:rental_bookings,booking_id',
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
            'vehicle_start_journey_imgs' => 'nullable|array',
            'vehicle_start_journey_imgs.*' => 'image|max:10000',
            'vehicle_end_journey_imgs' => 'nullable|array',
            'vehicle_end_journey_imgs.*' => 'image|max:10000',
            'image_type' => 'required|in:1,2',
        ],[
            'vehicle_start_journey_imgs.*.max' => 'Vehicle image size must be less than 10MB',
            'vehicle_end_journey_imgs.*.max' => 'Vehicle image size must be less than 10MB',
        ]);
        $imgType = $request->image_type;
        $validator->sometimes('vehicle_start_journey_imgs', 'required', function () use ($imgType) {
            return $imgType == 1;
        });
        $validator->sometimes('vehicle_end_journey_imgs', 'required', function () use ($imgType) {
            return $imgType == 2;
        });
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $hostUser = Auth::guard('api-carhost')->user();
        if(is_countable($request->file('vehicle_start_journey_imgs')) && count($request->file('vehicle_start_journey_imgs')) > 0){
            $carHostVehicleImage = CarHostVehicleStartJourneyImage::where(['vehicle_id' => $request->vehicle_id, 'image_type' => 1, 'booking_id' => $request->booking_id])->get(); //image_type = 1 means Vehicle Start Journey images
            if(is_countable($carHostVehicleImage) && count($carHostVehicleImage) > 0){
                $this->unlinkImages($carHostVehicleImage);
            }
            foreach ($request->file('vehicle_start_journey_imgs') as $key => $image) {
                $filename = 'CarHost_start_journey_'.$request->booking_id.'_'.$request->vehicle_id.'_'.$key.'_'.time() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images/carhost_vehicle_start_journey_image'), $filename);
                $carHostVehicleImage = new CarHostVehicleStartJourneyImage();
                $carHostVehicleImage->car_host_id = $hostUser->id;
                $carHostVehicleImage->booking_id = $request->booking_id;
                $carHostVehicleImage->vehicle_id = $request->vehicle_id;
                $carHostVehicleImage->image_type = 1;
                $carHostVehicleImage->vehicle_img = $filename;
                $carHostVehicleImage->save();
            }
        }
        if(is_countable($request->file('vehicle_end_journey_imgs')) && count($request->file('vehicle_end_journey_imgs')) > 0){
            $carHostVehicleImage = CarHostVehicleStartJourneyImage::where(['vehicle_id' => $request->vehicle_id, 'image_type' => 2, 'booking_id' => $request->booking_id])->get(); //image_type = 2 means Vehicle End Journey images
            if(is_countable($carHostVehicleImage) && count($carHostVehicleImage) > 0){
                $this->unlinkImages($carHostVehicleImage);
            }
            foreach ($request->file('vehicle_end_journey_imgs') as $key => $image) {
                $filename = 'CarHost_end_journey_'.$request->booking_id.'_'.$request->vehicle_id.'_'.$key.'_'.time() . '.' . $image->getClientOriginalExtension();   
                $image->move(public_path('images/carhost_vehicle_end_journey_image'), $filename);
                $carHostVehicleImage = new CarHostVehicleStartJourneyImage();
                $carHostVehicleImage->car_host_id = $hostUser->id;
                $carHostVehicleImage->booking_id = $request->booking_id;
                $carHostVehicleImage->vehicle_id = $request->vehicle_id;
                $carHostVehicleImage->image_type = 2;
                $carHostVehicleImage->vehicle_img = $filename;
                $carHostVehicleImage->save();
            }
        }

        return $this->successResponse($carHostVehicleImage, 'Car Host Vehicle images are uploaded successfully');
    }

    public function deleteVehicle(Request $request){
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $deletedVehicle = Vehicle::where('vehicle_id', $request->vehicle_id)->first();
        if($deletedVehicle != ''){
            $deletedVehicle->step_cnt = 0;
        }
        $deletedVehicle->is_deleted = 1;
        $deletedVehicle->publish = 0;
        $deletedVehicle->updated_at = now();
        $deletedVehicle->save();
     
        return $this->successResponse($deletedVehicle, 'Vehicle Deleted Successfully');
    }

    public function setInsuranceDetails(Request $request){
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
            'insurance_expiry_date' => 'required|date|after_or_equal:' . Carbon::now()->setTimezone('Asia/Kolkata')->toDateString(),
            'document_insurance_image' => 'nullable|image|max:10000',
        ],[
            'document_insurance_image.max' => 'Insurance document image size must be less than 10MB',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        // NEW CODE
        $status = 'add';
        $vehicleDocument = VehicleDocument::where(['vehicle_id' => $request->vehicle_id, 'document_type' => 'insurance_doc'])->first();
        if (isset($vehicleDocument) && $vehicleDocument != '' && (isset($request->insurance_expiry_date) && $request->insurance_expiry_date != NULL || isset($request->document_insurance_image) && $request->document_insurance_image != NULL)) {
            $checkInsuranceDoc = VehicleDocumentTemp::where(['vehicle_id' => $request->vehicle_id, 'document_type' => 'insurance_doc'])->first();
            if(isset($checkInsuranceDoc) && $checkInsuranceDoc != ''){
                if($checkInsuranceDoc->document_image_url != ''){
                    $docUrl = asset('images/documents/'.$checkInsuranceDoc->document_image_url);
                    if(file_exists($docUrl)){
                        unlink($docUrl);
                    }
                }
                $checkInsuranceDoc->delete();
            }
            $status = 'update';
            $vehicleDocument = new VehicleDocumentTemp();
        }else{
            $vehicleDocument = new VehicleDocument();
        }
        $vehicleDocument->vehicle_id = $request->vehicle_id;
        $vehicleDocument->document_type = 'insurance_doc';
        $vehicleDocument->expiry_date = $request->insurance_expiry_date != NULL ? date('Y-m-d', strtotime($request->insurance_expiry_date)) : NULL;
        $vehicleDocument->is_approved = 1;
        $vehicleDocument->approved_by = 1;
        if(isset($request->document_insurance_image) && $request->document_insurance_image != NULL){
            $file = $request->file('document_insurance_image');
            $extension = $file->getClientOriginalExtension();
            $filename = 'doc_insurance_img'.time() . '_' . uniqid() . '.' . $extension; 
            $file->move(public_path('images/documents'), $filename);
            $vehicleDocument->document_image_url = $filename;
        }
        $vehicleDocument->save();

        if($status == 'add'){
            return $this->successResponse($vehicleDocument, 'Vehicle Insurance details added successfully');
        }else{
            return $this->errorResponse('Vehicle Insurance details will update once admin will approve the document');
        }
    }

    public function setPucDetails(Request $request){
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
            'puc_expiry_date' => 'required|date',
            'document_puc_image' => 'nullable|image|max:10000',
        ],[
            'document_puc_image.max' => 'PUC document image size must be less than 10MB',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }   

        // NEW CODE
        $status = 'add';
        $vehicleDocument = VehicleDocument::where(['vehicle_id' => $request->vehicle_id, 'document_type' => 'puc_doc'])->first();
        if (isset($vehicleDocument) && $vehicleDocument != '' && (isset($request->puc_expiry_date) && $request->puc_expiry_date != NULL || isset($request->document_puc_image) && $request->document_puc_image != NULL)) {
            $checkPucDoc = VehicleDocumentTemp::where(['vehicle_id' => $request->vehicle_id, 'document_type' => 'puc_doc'])->first();
            if(isset($checkPucDoc) && $checkPucDoc != ''){
                if($checkPucDoc->document_image_url != ''){
                    $docUrl = asset('images/documents/'.$checkPucDoc->document_image_url);
                    if(file_exists($docUrl)){
                        unlink($docUrl);
                    }
                }
                $checkPucDoc->delete();
            }
            $status = 'update';
            $vehicleDocument = new VehicleDocumentTemp();
        }else{
            $vehicleDocument = new VehicleDocument();
        }
        $vehicleDocument->vehicle_id = $request->vehicle_id;
        $vehicleDocument->document_type = 'puc_doc';
        $vehicleDocument->expiry_date = $request->puc_expiry_date != NULL ? date('Y-m-d', strtotime($request->puc_expiry_date)) : NULL;
        $vehicleDocument->is_approved = 1;
        $vehicleDocument->approved_by = 1;
        if(isset($request->document_puc_image) && $request->document_puc_image != NULL){
            $file = $request->file('document_puc_image');
            $extension = $file->getClientOriginalExtension();
            $filename = 'doc_puc_img'.time() . '_' . uniqid() . '.' . $extension; 
            $file->move(public_path('images/documents'), $filename);
            $vehicleDocument->document_image_url = $filename;
        }
        $vehicleDocument->save();

        if($status == 'add'){
            return $this->successResponse($vehicleDocument, 'Vehicle PUC details added successfully');
        }else{
            return $this->errorResponse('Vehicle PUC details will update once admin will approve the document');
        }
    }

    public function storeVehicleSteps(Request $request){
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
            'step_cnt' => 'required|numeric|min:0|max:12',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $vehicle = Vehicle::where('vehicle_id', $request->vehicle_id)->first();
        if(!$vehicle){
            return $this->errorResponse('Vehicle not found', 404);  
        }
        $vehicle->step_cnt = $request->step_cnt;
        $vehicle->save();   

        return $this->successResponse([], 'Vehicle step updated successfully');
    }

}
