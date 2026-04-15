<?php

namespace App\Http\Controllers\AdminApis\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{VehicleType, Vehicle, FuelType, Transmission, VehicleFeature, VehicleManufacturer, VehicleModel, TripAmountCalculationRule, VehicleModelPriceDetail, Branch, CarHostPickupLocation, CarHost, VehicleFeatureMapping, VehiclePriceDetail, VehicleProperty, VehicleImage, VehicleDocument, CarEligibility, CarHostVehicleFeature, CarHostVehicleImage, CarHostVehicleFeatureTemp, CarHostVehicleImageTemp, VehicleDocumentTemp, VehiclePriceDetailTemp, RentalBooking};
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class VehicleInfoController extends Controller
{
    public function getVehicles(Request $request){
        $page = $request->input('page');
        $pageSize = $request->input('page_size');
        $orderColumn = $request->order_column ?? '';
        $orderType = $request->order_type ?? '';
        $search = $request->search ?? '';
        $orderTypes = config('global_values.order_types');
        $orderTypes = implode(',', $orderTypes);
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'nullable|exists:vehicles,vehicle_id',
            'model_id' => 'nullable|exists:vehicle_models,model_id',
            'order_type' => 'nullable|in:'.$orderTypes,
            'is_publish' => 'nullable|in:0,1', 
            'vehicle_created_by' => 'nullable|in:1,2',
            'booking_id' => 'nullable|exists:rental_bookings,booking_id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $vehicles = Vehicle::select(
                    'vehicles.vehicle_id',
                    'vehicles.branch_id',
                    'car_eligibilities.car_hosts_id',
                    'vehicles.branch_id as branch',
                    'vehicles.year',
                    'vehicles.description',
                    'vehicles.color',
                    'vehicles.license_plate',
                    'vehicles.availability',
                    'vehicles.rental_price',
                    'vehicles.extra_km_rate',
                    'vehicles.extra_hour_rate',
                    'vehicles.publish',
                    'vehicles.model_id as model_id',
                    'vehicles.deposit_amount',
                    'vehicles.is_deposit_amount_show',
                    'vehicle_categories.name as category_name',
                    'vehicle_manufacturers.name as manufacturer_name',
                    'vehicle_manufacturers.manufacturer_id as manufacturer',
                    'vehicle_models.name as model_name',
                    'vehicle_properties.transmission_id as vehicle_transmission',
                    'vehicle_properties.engine_cc',
                    'vehicle_properties.seating_capacity',
                    'vehicle_properties.mileage',
                    'vehicle_properties.fuel_type_id as fuel_type',
                    'vehicle_properties.fuel_capacity',
                    'vehicles.commission_percent',
                    'vehicles.availability_calendar',
                    'vehicle_types.type_id as vehicle_type',
                    'vehicles.vehicle_created_by',
                    'car_hosts.id as host_id',
                    'car_hosts.firstname as host_firstname',
                    'car_hosts.lastname as host_lastname',
                    'car_hosts.email as host_email',
                    'car_hosts.mobile_number as host_mobile',
                    'car_hosts.dob as host_dob',
                )
                ->with(['model.manufacturer','features','vehicleDocuments', 'CarHostVehicleFeatures','pricingDetails' => function($q) {
                        $q->select('id', 'vehicle_id', 'rental_price', 'hours', 'rate', 'is_show');
                    }
                ])  
                ->leftJoin('car_eligibilities', 'car_eligibilities.vehicle_id', '=', 'vehicles.vehicle_id')
                ->leftJoin('car_hosts', 'car_hosts.id', '=', 'car_eligibilities.car_hosts_id')
                ->leftJoin('vehicle_models', 'vehicle_models.model_id', '=', 'vehicles.model_id')
                ->leftJoin('vehicle_properties', 'vehicle_properties.vehicle_id', '=', 'vehicles.vehicle_id')
                ->leftJoin('vehicle_manufacturers', 'vehicle_manufacturers.manufacturer_id', '=', 'vehicle_models.manufacturer_id')
                ->leftJoin('vehicle_types', 'vehicle_types.type_id', '=', 'vehicle_manufacturers.vehicle_type_id')
                ->leftJoin('vehicle_categories', 'vehicle_categories.category_id', '=', 'vehicle_models.category_id')
                ->leftJoin('branches', 'branches.branch_id', '=', 'vehicles.branch_id')
                ->where('vehicles.is_deleted', 0);

        if(isset($request->is_publish)){
            $vehicles = $vehicles->where('vehicles.publish', $request->is_publish);
        }
        if(isset($request->vehicle_created_by)){
            $vehicles = $vehicles->where('vehicles.vehicle_created_by', $request->vehicle_created_by);
        } 
        if(isset($request->model_id)){
            $vehicles = $vehicles->where('vehicles.model_id', $request->model_id);
        }
        
        // Filter by booking_id: Get same vehicle type (car/bike) from same city
        if (!empty($request->booking_id)) {
            $booking = RentalBooking::with(['vehicle.model.manufacturer', 'vehicle.branch'])->find($request->booking_id);
            
            if (!$booking || !$booking->vehicle) {
                return $this->errorResponse('Booking not found or has no associated vehicle');
            }
            
            $bookedVehicle = $booking->vehicle;
            
            // Get vehicle type (1 = Car, 2 = Bike)
            $vehicleTypeId = null;
            if ($bookedVehicle->model && $bookedVehicle->model->manufacturer) {
                $vehicleTypeId = $bookedVehicle->model->manufacturer->vehicle_type_id;
            }
            
            if (!$vehicleTypeId) {
                return $this->errorResponse('Could not determine vehicle type from booking');
            }
            
            // Get city_id from the booked vehicle
            $cityId = null;
            if ($bookedVehicle->branch_id != null) {
                // Vehicle has a branch, get city_id from branch
                $branch = $bookedVehicle->branch;
                if ($branch && $branch->city_id) {
                    $cityId = $branch->city_id;
                }
            } else {
                // Vehicle is a car host vehicle, get city_id from car host pickup location
                $carEligibility = CarEligibility::where('vehicle_id', $bookedVehicle->vehicle_id)
                    ->with('vehiclePickupLocation')
                    ->first();
                if ($carEligibility && $carEligibility->vehiclePickupLocation && $carEligibility->vehiclePickupLocation->city_id) {
                    $cityId = $carEligibility->vehiclePickupLocation->city_id;
                }
            }
            
            if (!$cityId) {
                return $this->errorResponse('Could not determine city from booked vehicle');
            }
            
            // Filter by vehicle type (car or bike)
            $vehicles = $vehicles->where('vehicle_types.type_id', $vehicleTypeId);
            
            // Filter by same city
            // Get branch IDs for the city
            $branchIdsArray = Branch::select('branch_id', 'city_id')
                ->where('city_id', $cityId)
                ->pluck('branch_id')
                ->toArray();
            
            // Get car host pickup locations for the city
            $carHostPickupLocationIds = CarHostPickupLocation::where('city_id', $cityId)
                ->pluck('id')
                ->toArray();
            
            // Get vehicle IDs from car host pickup locations
            $carHostVehicleIds = CarEligibility::whereIn('car_host_pickup_location_id', $carHostPickupLocationIds)
                ->pluck('vehicle_id')
                ->toArray();
            
            // Filter vehicles by city (either branch vehicles or car host vehicles)
            $vehicles = $vehicles->where(function ($query) use ($branchIdsArray, $carHostVehicleIds) {
                $query->whereIn('vehicles.branch_id', $branchIdsArray)
                    ->orWhere(function ($subQuery) use ($carHostVehicleIds) {
                        $subQuery->whereNull('vehicles.branch_id')
                            ->whereIn('vehicles.vehicle_id', $carHostVehicleIds);
                    });
            });
            
            // Only show available vehicles
            $vehicles = $vehicles->where('vehicles.availability', 1);
        }
        
        if (!empty($request->vehicle_id)) { 
            $vehicles = $vehicles->where('vehicles.vehicle_id', $request->vehicle_id)->first();
            if($vehicles != ''){
                if($vehicles->vehicleDocuments){
                    $rcExpiryDate = $pucExpiryDate = $insuranceExpiryDate = $documentPucImage = $documentInsuranceImage = NULL;
                    $documentRcImage = [];
                    foreach($vehicles->vehicleDocuments as $key => $val){
                        if($val->document_type == 'rc_doc'){
                            $rcExpiryDate = $val->expiry_date;
                            $documentRcImage[] = url('images/documents/'.$val->document_image_url);
                        }elseif($val->document_type == 'puc_doc'){
                            $pucExpiryDate = $val->expiry_date;
                            $documentPucImage = url('images/documents/'.$val->document_image_url);
                        }elseif($val->document_type == 'insurance_doc'){
                            $insuranceExpiryDate = $val->expiry_date;
                            $documentInsuranceImage = url('images/documents/'.$val->document_image_url);
                        }   
                    }
                    $vehicles->rc_expiry_date = $rcExpiryDate;
                    $vehicles->puc_expiry_date = $pucExpiryDate;
                    $vehicles->insurance_expiry_date = $insuranceExpiryDate;
                    $vehicles->document_puc_image = $documentPucImage;
                    $vehicles->document_insurance_image = $documentInsuranceImage;
                    $vehicles->document_rc_image = $documentRcImage;
                }
                $vehicles->makeHidden(['vehicleDocuments']); 
                $vehicles->is_publish = $vehicles->publish != 0 ? 1 : 0;
                $vehicles->publish = $vehicles->publish != 0 ? 'Publish' : 'UnPublish';
                if($vehicles->deposit_amount == NULL){
                    $vehicles->deposit_amount = 0;
                }

                if($vehicles->rental_price <= 0){
                    $rentalPrice = getHostAddedRentalPrice($vehicles->vehicle_id);
                    $vehicles->rental_price = $rentalPrice;
                }
            }
            return $vehicles ? $this->successResponse($vehicles, 'Vehicle details fetched successfully') : $this->errorResponse('Vehicle not found');
        }
        if(isset($search) && $search != ''){
            // $checkVehicle = Vehicle::where('vehicles.vehicle_id', (int)$search)->exists();
            // if($checkVehicle){
            //     $vehicles = $vehicles->where('vehicles.vehicle_id', $search);
            // }
            // else{
                $vehicles = $vehicles->where(function ($query) use ($search) {
                    $query->orWhere('vehicles.vehicle_id', $search)->orWhereRaw('LOWER(vehicle_categories.name) LIKE LOWER(?)', ["%$search%"])
                    //$query->whereRaw('LOWER(vehicle_categories.name) LIKE LOWER(?)', ["%$search%"])
                        ->orWhereRaw('LOWER(vehicles.year) LIKE LOWER(?)', ["%$search%"])
                        ->orWhereRaw('LOWER(vehicles.description) LIKE LOWER(?)', ["%$search%"])
                        ->orWhereRaw('LOWER(vehicles.color) LIKE LOWER(?)', ["%$search%"])
                        ->orWhereRaw('LOWER(vehicles.license_plate) LIKE LOWER(?)', ["%$search%"])
                        ->orWhereRaw("LOWER(CONCAT(SUBSTRING_INDEX(vehicle_manufacturers.name, ' ', 1), ' ', vehicle_models.name)) LIKE LOWER(?)", ["%$search%"])
                        ->orWhereRaw('LOWER(vehicles.rental_price) LIKE LOWER(?)', ["%$search%"]);
                });
            //}
        }
        if($orderColumn != '' && $orderType != ''){
            $vehicles = $vehicles->orderBy($orderColumn, $orderType);
        }else{
            $vehicles = $vehicles->orderBy('vehicles.updated_at', 'desc');
        }
        if ($page !== null && $pageSize !== null) {
            $vehicles = $vehicles->paginate($pageSize, ['*'], 'page', $page);
            if(isset($vehicles) && is_countable($vehicles) && count($vehicles) > 0){
                foreach($vehicles as $key => $val){
                    if($val->vehicleDocuments){
                        $rcExpiryDate = $pucExpiryDate = $insuranceExpiryDate = $documentPucImage = $documentInsuranceImage = NULL;
                        $documentRcImage = [];
                        foreach($val->vehicleDocuments as $k => $v){
                            if($v->document_type == 'rc_doc'){
                                $rcExpiryDate = $v->expiry_date;
                                $documentRcImage[] = url('images/documents/'.$v->document_image_url);
                            }elseif($v->document_type == 'puc_doc'){
                                $pucExpiryDate = $v->expiry_date;
                                $documentPucImage = url('images/documents/'.$v->document_image_url);
                            }elseif($v->document_type == 'insurance_doc'){
                                $insuranceExpiryDate = $v->expiry_date;
                                $documentInsuranceImage = url('images/documents/'.$v->document_image_url);
                            }   
                        }
                        $val->rc_expiry_date = $rcExpiryDate;
                        $val->puc_expiry_date = $pucExpiryDate;
                        $val->insurance_expiry_date = $insuranceExpiryDate;
                        $val->document_puc_image = $documentPucImage;
                        $val->document_insurance_image = $documentInsuranceImage;
                        $val->document_rc_image = $documentRcImage;
                        $val->makeHidden(['vehicleDocuments']);
                    }
                    $val->is_publish = $val->publish != 0 ? 1 : 0;
                    $val->publish = $val->publish != 0 ? 'Publish' : 'UnPublish';
                    if($val->rental_price <= 0){
                        $rentalPrice = getHostAddedRentalPrice($val->vehicle_id);
                        $val->rental_price = $rentalPrice;
                    }
                }
            }
            $decodedVehicles = json_decode(json_encode($vehicles->getCollection()->values()), FALSE);
            return $this->successResponse([
                'vehicles' => $decodedVehicles,
                'pagination' => [
                    'total' => $vehicles->total(),
                    'per_page' => $vehicles->perPage(),
                    'current_page' => $vehicles->currentPage(),
                    'last_page' => $vehicles->lastPage(),
                    'from' => ($vehicles->currentPage() - 1) * $vehicles->perPage() + 1,
                    'to' => min($vehicles->currentPage() * $vehicles->perPage(), $vehicles->total()),
                ]], 'Vehicles fetched successfully');
        }else{
            $vehicles = $vehicles->get();
            if(isset($vehicles) && is_countable($vehicles) && count($vehicles) > 0){
                foreach($vehicles as $key => $val){
                    if($val->vehicleDocuments){
                        $rcExpiryDate = $pucExpiryDate = $insuranceExpiryDate = $documentPucImage = $documentInsuranceImage = NULL;
                        $documentRcImage = [];
                        foreach($val->vehicleDocuments as $k => $v){
                            if($v->document_type == 'rc_doc'){
                                $rcExpiryDate = $v->expiry_date;
                                $documentRcImage[] = url('images/documents/'.$v->document_image_url);
                            }elseif($v->document_type == 'puc_doc'){
                                $pucExpiryDate = $v->expiry_date;
                                $documentPucImage = url('images/documents/'.$v->document_image_url);
                            }elseif($v->document_type == 'insurance_doc'){
                                $insuranceExpiryDate = $v->expiry_date;
                                $documentInsuranceImage = url('images/documents/'.$v->document_image_url);
                            }   
                        }
                        $val->rc_expiry_date = $rcExpiryDate;
                        $val->puc_expiry_date = $pucExpiryDate;
                        $val->insurance_expiry_date = $insuranceExpiryDate;
                        $val->document_puc_image = $documentPucImage;
                        $val->document_insurance_image = $documentInsuranceImage;
                        $val->document_rc_image = $documentRcImage;
                        $val->makeHidden(['vehicleDocuments']);
                    }
                    $val->is_publish = $val->publish != 0 ? 1 : 0;
                    $val->publish = $val->publish != 0 ? 'Publish' : 'UnPublish';
                    if($val->rental_price <= 0){
                        $rentalPrice = getHostAddedRentalPrice($val->vehicle_id);
                        $val->rental_price = $rentalPrice;
                    }
                }
            }

            $vehicles = [
                'vehicles' => $vehicles,
            ];
            if(isset($vehicles) && is_countable($vehicles) && count($vehicles) > 0){
                return $this->successResponse($vehicles, 'Vehicles fetched successfully');
            }else{
                return $this->errorResponse('Vehicle not found');
            }
        }
    }

    public function publishVehicles(Request $request){
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
            'publish_status' => 'required|in:0,1',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $vehicleId = $request->vehicle_id;
        $vehicle = Vehicle::find($vehicleId);
        if($vehicle != '' && $vehicle->rental_price > 0){
            $vehicle->publish = $request->publish_status;
            $vehicle->save();
            if($request->publish_status == 1){
                logAdminActivities("Vehicle Publish Activity", $vehicle);
                return $this->successResponse($vehicle, 'Vehicle Published Successfully');
            }else{
                $vehicle->apply_for_publish = 0;
                $vehicle->save();
                logAdminActivities("Vehicle Un-Published Activity", $vehicle);
                return $this->successResponse($vehicle, 'Vehicle Un-Published Successfully');
            }
        }else{
            return $this->errorResponse('You can not Publish this Vehicle due to its Rental Price is not added');
        }
    }

    public function getVehiclePageData(Request $request){
        $details = [];
        $branches = Branch::select('branch_id', 'name')->get();
        $vehicleTypes = VehicleType::select('type_id', 'name')->where('is_deleted', 0)->get();
        $rules = TripAmountCalculationRule::orderBy('hours', 'desc')->get();
        $vehicleFeature = VehicleFeature::where('is_deleted', 0)->get();
        $carHost = CarHost::select('id', 'firstname', 'lastname', 'email')->where(['is_deleted' => 0, 'is_blocked' => 0])->get();

        $details = [
            'branches' => $branches, 
            'vehicle_types' => $vehicleTypes,
            'rules' => $rules,
            'vehicle_features' => $vehicleFeature,
            'carHost' => $carHost
        ];
        return $this->successResponse($details, 'Vehicle details are get Successfully');
    }

    public function getDependingDetails(Request $request){
        $validator = Validator::make($request->all(), [
            'data_key' => 'required|in:type,manufacturer,model',
        ]);
        $validator->sometimes(['vehicle_type_id'], 'required|exists:vehicle_types,type_id', function ($input) {
            return $input->data_key == 'type';
        });
        $validator->sometimes(['manufacturer_id'], 'required|exists:vehicle_manufacturers,manufacturer_id', function ($input) {
            return $input->data_key == 'manufacturer';
        });
        $validator->sometimes(['model_id'], 'required|exists:vehicle_models,model_id', function ($input) {
            return $input->data_key == 'model';
        });
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        
        if(isset($request->data_key) && $request->data_key == 'type'){
            $details = [];
            $vehicleTypeId = $request->vehicle_type_id;
            $vehicleManufacturers = VehicleManufacturer::select('manufacturer_id', 'name', 'vehicle_type_id')->where('vehicle_type_id', $vehicleTypeId)->where('is_deleted', 0)->get();
            $fuelTypes = FuelType::where('vehicle_type_id', $vehicleTypeId)->where('is_deleted', 0)->get();
            $transmissions = Transmission::where('vehicle_type_id', $vehicleTypeId)->where('is_deleted', 0)->get();
            $details = [
                'vehicleManufacturers' => $vehicleManufacturers,
                'fuelTypes' => $fuelTypes,
                'transmissions' => $transmissions,
            ];
            return $this->successResponse($details, 'Vehicle Type details are get Successfully');
        }elseif(isset($request->data_key) && $request->data_key == 'manufacturer'){
            $details = [];
            $manufacturerId = $request->manufacturer_id;
            $vehicleModels = VehicleModel::select('model_id','name', 'manufacturer_id')->where('manufacturer_id', $manufacturerId)/*->where('is_deleted', 0)*/->get();
            $details = [
                'vehicleModels' => $vehicleModels,
            ];
            return $this->successResponse($details, 'Vehicle Models details are get Successfully');
        }elseif(isset($request->data_key) && $request->data_key == 'model'){
            $details = [];
            $modelId = $request->model_id;
            $vehicleModels = VehicleModel::select('model_id','name', 'category_id', 'min_deposit_amount', 'max_deposit_amount')->where('model_id', $modelId)->first();
            $catName = $vehicleModels->category->name ?? ''; 
            $vehicleIds = Vehicle::where('model_id', $modelId)->pluck('vehicle_id')->unique()->toArray();
            $vehicleFeatureMappings = VehicleFeatureMapping::whereIn('vehicle_id', $vehicleIds)->pluck('feature_id')->unique()->toArray();

            $priceCalculation = $rentalPrice = [];
            $minRate = $maxRate = 0;
            $minPrices = VehicleModelPriceDetail::select('id', 'rental_price', 'hours', 'rate', 'duration')->where(['vehicle_model_id' => $modelId, 'type' => 1])->get();
            $maxPrices = VehicleModelPriceDetail::select('id', 'rental_price', 'hours', 'rate', 'duration')->where(['vehicle_model_id' => $modelId, 'type' => 2])->get();
            if(isset($minPrices) && is_countable($minPrices) && count($minPrices) > 0){
                foreach($minPrices as $key => $val){
                    $minRate = $val->rental_price;
                    $priceCalculation[$key]['hours'] = $val->hours;
                    $priceCalculation[$key]['minRate'] = $val->rate;
                    $priceCalculation[$key]['maxRate'] = getMaxRate($modelId, $val->hours, 2);
                    $priceCalculation[$key]['duration'] = $val->duration;
                }
            }

            $modelKmDetails = getModelKmDetail($modelId);
            $minKmRate = $modelKmDetails['min_km_limit'] ? (float)$modelKmDetails['min_km_limit'] : '';
            $maxKmRate = $modelKmDetails['max_km_limit'] ? (float)$modelKmDetails['max_km_limit'] : '';
           
            if(isset($maxPrices) && is_countable($maxPrices) && count($maxPrices) > 0){
                $maxRate = $maxPrices[0]->rental_price;
            }

            $minDepositAmt = $maxDepositAmt = 0;
            if(isset($vehicleModels) && $vehicleModels->min_deposit_amount != NULL && $vehicleModels->max_deposit_amount != NULL){
                $minDepositAmt = $vehicleModels->min_deposit_amount;
                $maxDepositAmt = $vehicleModels->max_deposit_amount;
            }

            $rentalPrice = [
                'minRate' => (float)$minRate,
                'maxRate' => (float)$maxRate,
            ];

            $kmRange = [
                'minKmRate' => (float)$minKmRate,
                'maxKmRate' => (float)$maxKmRate,
            ];
            
            $depositAmountRange = [
                'minDepositAmt' => (float)$minDepositAmt,
                'maxDepositAmt' => (float)$maxDepositAmt,
            ];

            $details = [
                'category_name' => $catName,
                'vehicleFeatureMappings' => $vehicleFeatureMappings,
                'rentalPrice' => $rentalPrice,
                'kmRange' => $kmRange,
                'deposit_amount_range' => $depositAmountRange,
                'priceCalculation' => $priceCalculation,
            ];
            return $this->successResponse($details, 'Vehicle Models details are get Successfully');
        }else{
            return $this->errorResponse('Data key not found');
        }
    }

    // public function addVehicle(Request $request){ // Un-Used
    //     $validator = Validator::make($request->all(), [
    //         'commission_percent' => 'required|numeric|min:0|max:100',
    //         'car_host' => 'nullable|exists:car_hosts,id',
    //         'year' => 'required|numeric',
    //         'rental_price' => 'required|numeric',
    //         'extra_km_rate' => 'required|numeric',
    //         'extra_hour_rate' => 'required|numeric',
    //         'deposit_amount' => 'nullable|numeric',
    //         'is_deposit_amount_show' => 'nullable|in:0,1',
    //         'deposit_amount' => 'nullable|numeric',
    //         'description' => 'required|max:500',
    //         'color' => 'required',
    //         'seating_capacity' => 'nullable|numeric',
    //         'mileage' => 'nullable',
    //         'license_plate' => 'required',
    //         'availability' => 'required|in:0,1',
    //         'rc_expiry_date' => 'required|date',
    //         //'puc_expiry_date' => 'required|date',
    //         'puc_expiry_date' => 'nullable|date',
    //         'insurance_expiry_date' => 'required|date',
    //         'document_rc_image' => 'required|array|max:2', // Ensure it's an array and allows max 2 files
    //         'document_rc_image.*' => 'required|image|mimetypes:image/heic,image/heif,image/jpeg,image/png,image/jpg,image/bmp,image/gif,image/svg,image/webp|max:2000',
    //         //'document_puc_image' => 'required|image|mimetypes:image/heic,image/heif,image/jpeg,image/png,image/jpg,image/bmp,image/gif,image/svg,image/webp|max:2000',
    //         'document_puc_image' => 'nullable|image|mimetypes:image/heic,image/heif,image/jpeg,image/png,image/jpg,image/bmp,image/gif,image/svg,image/webp|max:2000',
    //         'document_insurance_image' => 'required|image|mimetypes:image/heic,image/heif,image/jpeg,image/png,image/jpg,image/bmp,image/gif,image/svg,image/webp|max:2000',
    //         'cutout_image' => 'required|image|mimetypes:image/heic,image/heif,image/jpeg,image/png,image/jpg,image/bmp,image/gif,image/svg,image/webp|max:2000',
    //         'banner_images' => 'required|array', 
    //         'banner_images.*' => 'image|mimetypes:image/heic,image/heif,image/jpeg,image/png,image/jpg,image/bmp,image/gif,image/svg,image/webp|max:5000', 
    //         'regular_images' => 'required|array',
    //         'regular_images.*' => 'image|mimetypes:image/heic,image/heif,image/jpeg,image/png,image/jpg,image/bmp,image/gif,image/svg,image/webp|max:5000',
    //         'engine_cc' => 'required|numeric',
    //         'fuel_capacity' => 'required|numeric',
    //         'fuel_type' => 'required|numeric|exists:vehicle_fuel_types,fuel_type_id',
    //         'mileage' => 'required|numeric',
    //         'vehicle_transmission' => 'required|numeric|exists:vehicle_transmissions,transmission_id',
    //         'seating_capacity' => 'required|numeric',
    //     ], [
    //         'commission_percent' => 'Commission Percent is required',
    //         'year.required' => 'Manufacture Year is required.',
    //         'rental_price.required' => 'Rental Price is required.',
    //         'extra_km_rate.required' => 'Extra Km Rate Price is required.',
    //         'extra_hour_rate.required' => 'Extra Hour Rate Price is required.',
    //         'model_id.required' => 'Model is required.',
    //         'description.required' => 'Description is required.',
    //         'description.max' => 'Description length is max 500 characters',
    //         'color.required' => 'Color is required.',
    //         'license_plate.required' => 'Registration Number is required.',
    //         'availability.required' => 'Availability is required.',
    //         'availability.in' => 'Availability should be either 0 or 1.',
    //         'rc_expiry_date.required' => 'RC Expiry Date is required.',
    //         //'puc_expiry_date.required' => 'PUC Expiry Date is required.',
    //         'insurance_expiry_date.required' => 'Insurance Expiry Date is required.',
    //         'document_rc_image.*.required' => 'RC Document image is required',
    //         'document_rc_image.*.mimes' => 'You can select only Image',
    //         //'document_puc_image.required' => 'PUC Document image is required',
    //         'document_puc_image.mimes' => 'You can select only Image',
    //         'document_insurance_image.required' => 'Document Insurance image is required',
    //         'document_insurance_image.mimes' => 'You can select only Image',
    //         'cutout_image.required' => 'Document image is required',
    //         'cutout_image.mimes' => 'You can select only Image',
    //         'engine_cc' => 'Please enter Engine CC',
    //         'fuel_capacity' => 'Please enter Fuel Capacity',
    //         'fuel_type' => 'Please select Fuel Type',
    //         'mileage' => 'Please enter Mileage',
    //         'vehicle_transmission' => 'Please select Transmission Type',
    //         'seating_capacity' => 'Please enter Seating Capacity',
    //         'banner_images.*.required' => 'Banner images are required',
    //         'regular_images.*.required' => 'Regular images are required',
    //     ]);
    //     if ($validator->fails()) {
    //         return $this->validationErrorResponse($validator);
    //     }
    //     // VEHICLE
    //     $Vehicle = new Vehicle();
    //     $Vehicle->branch_id = $request->branch;
    //     $Vehicle->model_id = $request->model_id;
    //     $Vehicle->year = $request->year;
    //     $Vehicle->description = $request->description;
    //     $Vehicle->color = $request->color;
    //     $Vehicle->license_plate = $request->license_plate;
    //     $Vehicle->availability = $request->availability;
    //     $vehicle->deposit_amount = $request->deposit_amount ?? 0;
    //     $vehicle->is_deposit_amount_show = $request->is_deposit_amount_show;
    //     $Vehicle->is_deleted = 0;
    //     $Vehicle->rental_price = $request->rental_price;
    //     $Vehicle->extra_km_rate = $request->extra_km_rate;
    //     $Vehicle->extra_hour_rate = $request->extra_hour_rate;
    //     $Vehicle->commission_percent = $request->commission_percent ?? 0;
    //     $Vehicle->created_at = now();
    //     $Vehicle->updated_at = now();
    //     $Vehicle->save();

    //     $vehicleRcDoc = VehicleDocument::where('vehicle_id', $Vehicle->vehicle_id)->where('document_type', 'rc_doc')->get();
    //     if(isset($vehicleRcDoc) && is_countable($vehicleRcDoc) && count($vehicleRcDoc) > 0){
    //         foreach($vehicleRcDoc as $key => $val){
    //             $val->id_number = $request->license_plate;
    //             $val->save();
    //         }
    //     }

    //     // VEHICLE PROPERTIES
    //     $mileage = intval(preg_replace('/[^0-9]/', '', $request->mileage));
    //     $engine_cc_numeric = intval(preg_replace('/[^0-9]/', '', $request->engine_cc));
    //     $fuel_capacity_numeric = intval(preg_replace('/[^0-9]/', '', $request->fuel_capacity));    

    //     $VehicleProperty = new VehicleProperty();
    //     $VehicleProperty->vehicle_id = $Vehicle->vehicle_id;
    //     $VehicleProperty->mileage = isset($mileage)?$mileage:NULL;
    //     $VehicleProperty->fuel_type_id = isset($request->fuel_type)?$request->fuel_type:NULL;
    //     $VehicleProperty->transmission_id  = isset($request->vehicle_transmission)?$request->vehicle_transmission:NULL;
    //     $VehicleProperty->seating_capacity = isset($request->seating_capacity) ? $request->seating_capacity : NULL;
    //     $VehicleProperty->engine_cc = isset($engine_cc_numeric)?$engine_cc_numeric:NULL;
    //     $VehicleProperty->fuel_capacity = isset($fuel_capacity_numeric)?$fuel_capacity_numeric:NULL;
    //     $VehicleProperty->created_at = now();
    //     $VehicleProperty->updated_at = now();
    //     $VehicleProperty->save();

    //     $features = explode(',', $request->features);
    //     // ASSIGN VEHICLE TO HOST
    //     if(isset($request->car_host) && $request->car_host != ''){
    //         $carHostEligibility = new CarEligibility();
    //         $carHostEligibility->vehicle_id = $Vehicle->vehicle_id;
    //         $carHostEligibility->car_hosts_id = $request->car_host;
    //         $getPrimaryLocation = CarHostPickupLocation::where(['car_hosts_id' => $request->car_host, 'is_primary' => 1, 'is_deleted' => 0])->first();
    //         if(isset($getPrimaryLocation) && $getPrimaryLocation != ''){
    //             $carHostEligibility->car_host_pickup_location_id = $getPrimaryLocation->id;
    //         }
    //         $carHostEligibility->save();

    //         // VEHICLE FEATURES
    //         if(is_countable($features) && count($features) > 0){
    //             $carHostVehicleFeature = CarHostVehicleFeature::where('vehicles_id', $Vehicle->vehicle_id)->delete();
    //             foreach ($features as $key => $value) {
    //                 $carHostVehicleFeature = new CarHostVehicleFeature();
    //                 $carHostVehicleFeature->vehicles_id = $Vehicle->vehicle_id;
    //                 $carHostVehicleFeature->feature_id = $value;
    //                 $carHostVehicleFeature->save();
    //             }
    //         }  
    //         $Vehicle->publish = 0;
    //         $Vehicle->save();
    //     }else{
    //         // VEHICLE FEATURES
    //         if(isset($features) && is_countable($features) && count($features) > 0)
    //         {
    //             foreach ($features as $key => $value) {
    //                 $vehicleFeatureMapping = new VehicleFeatureMapping();
    //                 $vehicleFeatureMapping->vehicle_id = $Vehicle->vehicle_id;
    //                 $vehicleFeatureMapping->feature_id  = $value;
    //                 $vehicleFeatureMapping->save();
    //             }
    //         }
    //     }
        
    //     // PRICE SUMMARY
    //     $priceCalc = $request->pricing_details ?? '';
    //     if($priceCalc != '') {
    //         $priceCalc = json_decode($request->pricing_details, true);
    //         $rentalPrice = $rentalPriceHour = 0;
    //         asort($priceCalc);// make sort based on its value on ascending order
    //         if(is_countable($priceCalc) && count($priceCalc) > 0){
    //             foreach ($priceCalc as $key => $value) {
    //                 if($value > 0){
    //                     $rentalPrice = $value;
    //                     $rentalPriceHour = $key;
    //                     break;
    //                 }
    //             }
    //         }
    //         krsort($priceCalc); //make sort based on its key on descending order
    //         $multipliers = []; // Array to hold the multipliers
    //         foreach ($priceCalc as $key => $value) {
    //             $multiplierVal = 0;
    //             if($rentalPrice <= $value){
    //                 $multiplierVal = ($value / $rentalPrice);
    //             }
    //             $multipliers[$key][$value] = round($multiplierVal, 2);
    //         }
    //         if(is_countable($multipliers) && count($multipliers) > 0){
    //             foreach ($multipliers as $key => $value) {
    //                 $vehiclePriceDetail = new VehiclePriceDetail();
    //                 $vehiclePriceDetail->vehicle_id = $Vehicle->vehicle_id;
    //                 $vehiclePriceDetail->rental_price = $rentalPrice;
    //                 $vehiclePriceDetail->hours = $key;
    //                 foreach ($value as $k => $v) {
    //                     $vehiclePriceDetail->rate = $k;
    //                     $vehiclePriceDetail->multiplier = $v;
    //                     $perHourRate = $k / $key;
    //                     $vehiclePriceDetail->per_hour_rate = number_format(($perHourRate), 2);
    //                     $vehiclePriceDetail->unlimited_km_trip_amount = $k * 1.3;
    //                 }   
    //                 $vehiclePriceDetail->duration = ($key >= 24) ? round($key / 24, 2) . ' days' : $key . ' hours';
    //                 $vehiclePriceDetail->trip_amount_km_limit = calculateKmLimit($key)." Km";
    //                 $vehiclePriceDetail->save();   
    //             }
    //         }
    //     }

    //     // VEHICLE IMAGES
    //     if(isset($request->car_host) && $request->car_host != ''){
    //         if(is_countable($request->file('regular_images')) && count($request->file('regular_images')) > 0){
    //             $carHostVehicleImage = CarHostVehicleImage::where(['vehicles_id' => $Vehicle->vehicle_id, 'image_type' => 2])->get(); //image_type = 2 means Vehicle Interior images
    //             if(is_countable($carHostVehicleImage) && count($carHostVehicleImage) > 0){
    //                 $this->unlinkImages($carHostVehicleImage);
    //             }
    //             foreach ($request->file('regular_images') as $key => $image) {
    //                 $filename = 'Interior_'.$Vehicle->vehicle_id.'_'.$key.'_'.time() . '.' . $image->getClientOriginalExtension();
    //                 $image->move(public_path('images/car_host'), $filename);
    //                 $carHostVehicleImage = new CarHostVehicleImage();
    //                 $carHostVehicleImage->vehicles_id = $Vehicle->vehicle_id;
    //                 $carHostVehicleImage->image_type = 2;
    //                 $carHostVehicleImage->vehicle_img = $filename;
    //                 $carHostVehicleImage->save();
    //             }
    //         }
    //         if(is_countable($request->file('banner_images')) && count($request->file('banner_images')) > 0){
    //             $carHostVehicleImage = CarHostVehicleImage::where(['vehicles_id' => $Vehicle->vehicle_id, 'image_type' => 3])->get(); //image_type = 3 means Vehicle Exterior images
    //             if(is_countable($carHostVehicleImage) && count($carHostVehicleImage) > 0){
    //                 $this->unlinkImages($carHostVehicleImage);
    //             }
    //             foreach ($request->file('banner_images') as $key => $image) {
    //                 $filename = 'Exterior_'.$Vehicle->vehicle_id.'_'.$key.'_'.time() . '.' . $image->getClientOriginalExtension();   
    //                 $image->move(public_path('images/car_host'), $filename);
    //                 $carHostVehicleImage = new CarHostVehicleImage();
    //                 $carHostVehicleImage->vehicles_id = $Vehicle->vehicle_id;
    //                 $carHostVehicleImage->image_type = 3;
    //                 $carHostVehicleImage->vehicle_img = $filename;
    //                 $carHostVehicleImage->save();
    //             }
    //         }
    //     }else{
    //         try{
    //             if(isset($request->banner_images) && is_countable($request->banner_images) && count($request->banner_images) > 0){
    //                 foreach($request->banner_images as $key => $val){
    //                     $extension = $val->getClientOriginalExtension();
    //                     $filename = 'banner_img_'.time() . '_' . uniqid() . '.' . $extension;
    //                     $val->move(public_path('images/vehicle_images/'), $filename);
    //                     $vehicleImg = new VehicleImage();
    //                     $vehicleImg->vehicle_id = $Vehicle->vehicle_id;
    //                     $vehicleImg->image_type = 'banner';
    //                     $vehicleImg->image_url = $filename;
    //                     $vehicleImg->save();
    //                 }
    //             }
    //             if(isset($request->regular_images) && is_countable($request->regular_images) && count($request->regular_images) > 0){
    //                 foreach($request->regular_images as $key => $val){
    //                     $extension = $val->getClientOriginalExtension();
    //                     $filename = 'regular_img_'.time() . '_' . uniqid() . '.' . $extension;
    //                     $val->move(public_path('images/vehicle_images/'), $filename);
    //                     $vehicleImg = new VehicleImage();
    //                     $vehicleImg->vehicle_id = $Vehicle->vehicle_id;
    //                     $vehicleImg->image_type = 'regular';
    //                     $vehicleImg->image_url = $filename;
    //                     $vehicleImg->save();
    //                 }
    //             }  
    //             if(isset($request->cutout_image) && $request->cutout_image != '')
    //             {
    //                 $file = $request->cutout_image;
    //                 $extension = $file->getClientOriginalExtension();
    //                 $filename = 'cutout_img_'.time() . '_' . uniqid() . '.' . $extension; // Append a unique identifier to the filename
    //                 $file->move(public_path('images/vehicle_images/'), $filename);
    //                 $vehicleImg = new VehicleImage();
    //                 $vehicleImg->vehicle_id = $Vehicle->vehicle_id;
    //                 $vehicleImg->image_type = 'cutout';
    //                 $vehicleImg->image_url = $filename;
    //                 $vehicleImg->save();
    //             }
    //         } catch (\Exception $e) {} 
    //     }
        
    //     // VEHICLE DOCUMENTS
    //     try {
    //         if(isset($request->document_rc_image) && is_countable($request->document_rc_image) && count($request->document_rc_image) > 0){
    //             foreach($request->document_rc_image as $key => $val){
    //                 $extension = $val->getClientOriginalExtension();
    //                 $filename = 'doc_rc_img_'.$key.'_'.time() . '_' . uniqid() . '.' . $extension;
    //                 $val->move(public_path('images/documents/'), $filename);
    //                 $vehicleDoc = new VehicleDocument();
    //                 $vehicleDoc->vehicle_id = $Vehicle->vehicle_id;
    //                 $vehicleDoc->document_type = 'rc_doc';
    //                 $vehicleDoc->expiry_date = $request->rc_expiry_date;
    //                 $vehicleDoc->is_approved = 1;
    //                 $vehicleDoc->approved_by = 1;
    //                 $vehicleDoc->document_image_url = $filename;
    //                 $vehicleDoc->created_at = now();
    //                 $vehicleDoc->updated_at = now();
    //                 $vehicleDoc->save();
    //             }
    //         }  
    //         if(isset($request->document_puc_image)){
    //             $file = $request->document_puc_image;
    //             $extension = $file->getClientOriginalExtension();
    //             $filename = 'doc_puc_img_'.time() . '_' . uniqid() . '.' . $extension;
    //             $file->move(public_path('images/documents'), $filename);
    //             $vehicleDoc = new VehicleDocument();
    //             $vehicleDoc->vehicle_id = $Vehicle->vehicle_id;
    //             $vehicleDoc->document_type = 'puc_doc';
    //             $vehicleDoc->expiry_date = $request->puc_expiry_date;
    //             $vehicleDoc->is_approved = 1;
    //             $vehicleDoc->approved_by = 1;
    //             $vehicleDoc->document_image_url = $filename;
    //             $vehicleDoc->created_at = now();
    //             $vehicleDoc->updated_at = now();
    //             $vehicleDoc->save();
    //         }
    //         if(isset($request->document_insurance_image)){
    //             $file = $request->document_insurance_image;
    //             $extension = $file->getClientOriginalExtension();
    //             $filename = 'doc_insurance_img_'.time() . '_' . uniqid() . '.' . $extension;
    //             $file->move(public_path('images/documents'), $filename);
    //             $vehicleDoc = new VehicleDocument();
    //             $vehicleDoc->vehicle_id = $Vehicle->vehicle_id;
    //             $vehicleDoc->document_type = 'insurance_doc';
    //             $vehicleDoc->expiry_date = $request->insurance_expiry_date;
    //             $vehicleDoc->is_approved = 1;
    //             $vehicleDoc->approved_by = 1;
    //             $vehicleDoc->document_image_url = $filename;
    //             $vehicleDoc->created_at = now();
    //             $vehicleDoc->updated_at = now();
    //             $vehicleDoc->save();
    //         } 
        
    //         //ADD AVAILABILITY CALANDER
    //         $availabilityCalander = json_decode($request->availability_calendar, true);
    //         if(isset($availabilityCalander) && is_countable($availabilityCalander) && count($availabilityCalander) > 0){
    //             $calArray = [];
    //             foreach ($availabilityCalander as $key => $value) {
    //                 if(isset($value) && isset($value['start_date'])){
    //                     $calArray[$key]['start_date'] = isset($value['start_date'])? date('d-m-Y H:i A', strtotime($value['start_date'])): '';
    //                     $calArray[$key]['end_date'] = isset($value['end_date'])? date('d-m-Y H:i A', strtotime($value['end_date'])): '';
    //                     $calArray[$key]['reason'] = isset($value['reason'])?$value['reason']:'';
    //                 }
    //             }
    //             $Vehicle->availability_calendar = json_encode($calArray);
    //             $Vehicle->save();
    //         }
           
    //         logAdminActivities("Vehicle Creation", $Vehicle);
                
    //         } catch (\Exception $e) {}
        
    //     return $this->successResponse($Vehicle, 'Vehicle added successfully!');
    // }   

    public function addUpdateVehicles(Request $request){
        $vehicleAddSteps = config('global_values.vehicle_steps');
        $vehicleAddSteps = implode(',', $vehicleAddSteps);
        $validator = Validator::make($request->all(), [
            'vehicle_step' => 'required|in:'.$vehicleAddSteps,
            'vehicle_id' => 'nullable|exists:vehicles,vehicle_id',
            // VEHICLE 
            'commission_percent' => 'nullable|numeric|min:0|max:100',
            'car_host' => 'nullable|exists:car_hosts,id',
            'city_id' => 'nullable|exists:cities,id',
            'year' => 'nullable|numeric',
            'model_id' => 'nullable|exists:vehicle_models,model_id',
            'color' => 'nullable',
            'license_plate' => 'nullable',
            'availability' => 'nullable|in:0,1',
            'description' => 'nullable|max:500',
            'branch' => 'nullable|exists:branches,branch_id',
            'deposit_amount' => 'nullable|numeric',
            'is_deposit_amount_show' => 'nullable|in:0,1',
            // VEHICLE PROPERTIES
            'vehicle_transmission' => 'nullable|numeric|exists:vehicle_transmissions,transmission_id',
            'engine_cc' => 'nullable|numeric',
            'seating_capacity' => 'nullable|numeric',
            'mileage' => 'nullable|numeric',
            'fuel_type' => 'nullable|numeric|exists:vehicle_fuel_types,fuel_type_id',
            'fuel_capacity' => 'nullable|numeric',
            //FEAURES
            'features' => 'nullable',
            //DOCUMENTS and IMAGES
            'rc_expiry_date' => 'nullable|date',
            'puc_expiry_date' => 'nullable|date',
            'document_rc_image' => 'nullable|array|max:2', // Ensure it's an array and allows max 2 files
            'document_rc_image.*' => 'nullable|image|mimetypes:image/heic,image/heif,image/jpeg,image/png,image/jpg,image/bmp,image/gif,image/svg,image/webp|max:2000',
            'document_puc_image' => 'nullable|image|mimetypes:image/heic,image/heif,image/jpeg,image/png,image/jpg,image/bmp,image/gif,image/svg,image/webp|max:2000',
            'document_insurance_image' => 'nullable|image|mimetypes:image/heic,image/heif,image/jpeg,image/png,image/jpg,image/bmp,image/gif,image/svg,image/webp|max:2000',
            'cutout_image' => 'nullable|image|mimetypes:image/heic,image/heif,image/jpeg,image/png,image/jpg,image/bmp,image/gif,image/svg,image/webp|max:2000',
            'banner_images' => 'nullable|array', 
            'banner_images.*' => 'image|mimetypes:image/heic,image/heif,image/jpeg,image/png,image/jpg,image/bmp,image/gif,image/svg,image/webp|max:5000', 
            'regular_images' => 'nullable|array',
            'regular_images.*' => 'image|mimetypes:image/heic,image/heif,image/jpeg,image/png,image/jpg,image/bmp,image/gif,image/svg,image/webp|max:5000',
            // PRICE SUMMARY
            'rental_price' => 'nullable|numeric',
            'extra_km_rate' => 'nullable|numeric',
            'extra_hour_rate' => 'nullable|numeric',
            'pricing_details' => 'nullable',
            'availability_calendar' => 'nullable',
        ], [
            // VEHICLE 
            'commission_percent' => 'Commission Percent is required',
            'year.required' => 'Manufacture Year is required.',
            'model_id.required' => 'Model is required.',
            'description.max' => 'Description length is max 500 characters',
            //'color.required' => 'Color is required.',
            'license_plate.required' => 'Registration Number is required.',
            'availability.in' => 'Availability should be either 0 or 1.',
            // VEHICLE PROPERTIES
            'vehicle_transmission.required' => 'Please select Transmission Type',
            //'engine_cc.required' => 'Please enter Engine CC',
            'seating_capacity.required' => 'Please enter Seating Capacity',
            'mileage.required' => 'Please enter Mileage',
            'fuel_type.required' => 'Please select Fuel Type',
            //'fuel_capacity.required' => 'Please enter Fuel Capacity',
            //FEAURES
            //'features.required' => 'Please select Features',
            //DOCUMENTS and IMAGES
            'rc_expiry_date.required' => 'RC Expiry Date is required.',
            'insurance_expiry_date.required' => 'Insurance Expiry Date is required.',
            'document_rc_image.*.required' => 'RC Document image is required',
            'document_rc_image.*.mimes' => 'You can select only Image',
            'document_puc_image.mimes' => 'You can select only Image',
            'document_insurance_image.required' => 'Document Insurance image is required',
            'document_insurance_image.mimes' => 'You can select only Image',
            //'cutout_image.required' => 'Document image is required',
            'cutout_image.mimes' => 'You can select only Image',
            //'banner_images.*.required' => 'Banner images are required',
            //'regular_images.*.required' => 'Regular images are required',
        ]);
        $validator->sometimes(['vehicle_id'], 'required', function ($input) {
            return $input->vehicle_step == 'property_details' || $input->vehicle_step == 'vehicle_features' || $input->vehicle_step == 'vehicle_images' || $input->vehicle_step == 'price_calculation' || $input->vehicle_step == 'availability_dates';
        });
        $validator->sometimes(['commission_percent', 'year', 'model_id', 'license_plate', 'availability'], 'required', function ($input) {
            return $input->vehicle_step == 'vehicle_details';
        });
        $validator->sometimes(['vehicle_transmission', 'seating_capacity', 'mileage', 'fuel_type'], 'required', function ($input) {
            return $input->vehicle_step == 'property_details';
        }); 
        // $validator->sometimes(['features'], 'required', function ($input) {
        //     return $input->vehicle_step == 'vehicle_features';
        // });
        $validator->sometimes(['rc_expiry_date'], 'required', function ($input) {
            return $input->vehicle_step == 'vehicle_images';
        }); 
        $validator->sometimes(['rental_price', 'extra_km_rate', 'extra_hour_rate', 'pricing_details'], 'required', function ($input) {
            return $input->vehicle_step == 'price_calculation';
        });
        $validator->sometimes(['availability_calendar'], 'required', function ($input) {
            return $input->vehicle_step == 'availability_dates';
        });
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $oldVehicle = $newVehicleFeature = $oldVehicleFeature = [];
        $vehicle = Vehicle::where('vehicle_id', $request->vehicle_id)->first();
        if($vehicle){
            $oldVehicle = clone $vehicle;
            $oldVehicleAvailabilityDates = $vehicle->availability_calendar;
        }
        $hostVehicleDetails = CarEligibility::where('vehicle_id', $request->vehicle_id)->first();
        if($request->vehicle_step == 'vehicle_details'){
            // VEHICLE DETAILS
            $checkLicenseExist = Vehicle::where('license_plate', $request->license_plate);
            if(!isset($request->vehicle_id) && $request->vehicle_id == ''){
                $vehicle = new Vehicle();
                $vehicle->publish = 0;
            }else{
                $checkLicenseExist = $checkLicenseExist->where('vehicle_id', '!=', $request->vehicle_id);
            }
            $checkLicenseExist = $checkLicenseExist->where('is_deleted', 0)->exists();
            if($checkLicenseExist){
                return $this->errorResponse('This Registration Number is already Exists');
            }

            $vehicle->branch_id = $request->branch;
            $vehicle->model_id = $request->model_id;
            $vehicle->year = $request->year;
            $vehicle->description = $request->description ?? NULL;
            $vehicle->color = $request->color;
            $vehicle->license_plate = $request->license_plate;
            $vehicle->availability = $request->availability;
            $vehicle->is_deleted = 0;
            $vehicle->commission_percent = $request->commission_percent ?? 0;
            $vehicle->created_at = now();
            $vehicle->updated_at = now();
            $vehicle->deposit_amount = $request->deposit_amount ?? 0;
            $vehicle->is_deposit_amount_show = $request->is_deposit_amount_show;
            $vehicle->save();
            $newVehicle = $vehicle;

            // ASSIGN VEHICLE TO HOST
            if(isset($request->car_host) && $request->car_host != ''){
                $getPrimaryLocation = CarHostPickupLocation::where(['car_hosts_id' => $request->car_host, 'is_primary' => 1, 'is_deleted' => 0])->first();
                if(!$getPrimaryLocation){
                    return $this->errorResponse('First add any one Pickup Location');
                }
                $carHostEligibility = CarEligibility::where('vehicle_id', $vehicle->vehicle_id)->first();
                if(!isset($carHostEligibility) && $carHostEligibility == ''){
                    $carHostEligibility = new CarEligibility();
                }
                if($carHostEligibility){
                    $carHostEligibility->vehicle_id = $vehicle->vehicle_id;
                    $carHostEligibility->car_hosts_id = $request->car_host;
                    if(isset($getPrimaryLocation) && $getPrimaryLocation != ''){
                        $carHostEligibility->car_host_pickup_location_id = $getPrimaryLocation->id;
                    }
                    $carHostEligibility->save();
                }
                if(isset($request->city_id) && $request->city_id != ''){
                    $vehicle->temp_city_id = $request->city_id;
                    $vehicle->save();
                    if(isset($carHostEligibility) && $carHostEligibility != ''){
                        $carHostPickupLocations = CarHostPickupLocation::where('id', $carHostEligibility->car_host_pickup_location_id)->first();
                        if(isset($carHostPickupLocations) && $carHostPickupLocations != ''){
                            $carHostPickupLocations->city_id = $request->city_id;
                            $carHostPickupLocations->save(); 
                        }
                    }
                }
                // $vehicle->publish = 0;
                // $vehicle->save();
            }
            $oldArr = [
                'vehicle' => $oldVehicle, 
            ];
            $newArr = [
                'vehicle' => $newVehicle, 
            ];
            logAdminActivities('Vehicle Add/Updation', $oldArr, $newArr);

            return $this->successResponse($vehicle, 'Vehicle details are stored Successfully');
        }elseif($request->vehicle_step == 'property_details'){
            // VEHICLE PROPERTIES
            $oldVehicleProperty = '';
            $mileage = intval(preg_replace('/[^0-9]/', '', $request->mileage));
            $engine_cc_numeric = intval(preg_replace('/[^0-9]/', '', $request->engine_cc));
            $fuel_capacity_numeric = intval(preg_replace('/[^0-9]/', '', $request->fuel_capacity));    
            if(isset($vehicle->properties) && $vehicle->properties != ''){
                $vehicleProperty = VehicleProperty::where('vehicle_id', $request->vehicle_id)->first();   
                $oldVehicleProperty = clone $vehicleProperty;
            }else{
                $vehicleProperty = new VehicleProperty();
            }
            
            $vehicleProperty->vehicle_id = $request->vehicle_id;
            $vehicleProperty->mileage = isset($mileage)?$mileage:NULL;
            $vehicleProperty->fuel_type_id = isset($request->fuel_type)?$request->fuel_type:NULL;
            $vehicleProperty->transmission_id  = isset($request->vehicle_transmission)?$request->vehicle_transmission:NULL;
            $vehicleProperty->seating_capacity = isset($request->seating_capacity) ? $request->seating_capacity : NULL;
            $vehicleProperty->engine_cc = isset($engine_cc_numeric)?$engine_cc_numeric:NULL;
            $vehicleProperty->fuel_capacity = isset($fuel_capacity_numeric)?$fuel_capacity_numeric:NULL;
            $vehicleProperty->created_at = now();
            $vehicleProperty->updated_at = now();
            $vehicleProperty->save();
            $newVehicleProperty = $vehicleProperty;

            $oldArr = [
                'vehicleProperty' => $oldVehicleProperty, 
            ];
            $newArr = [
                'vehicleProperty' => $newVehicleProperty, 
            ];
            logAdminActivities('Vehicle Properties Add / Updation', $oldArr, $newArr);

            return $this->successResponse($vehicleProperty, 'Vehicle properties details are stored Successfully');
        }elseif($request->vehicle_step == 'vehicle_features'){
            if($request->features != ''){
                $features = explode(',', $request->features);
            }else{
                $features = [];
            }
            
            // VEHICLE FEATURES
            if(isset($hostVehicleDetails) && $hostVehicleDetails != ''){
                if(is_countable($features) && count($features) > 0){
                    $carHostVehicleFeature = CarHostVehicleFeature::where('vehicles_id', $vehicle->vehicle_id)->pluck('feature_id')->toArray();
                    $oldVehicleFeature = $carHostVehicleFeature;
                    $carHostVehicleFeature = CarHostVehicleFeature::where('vehicles_id', $vehicle->vehicle_id)->delete();
                    foreach ($features as $key => $value) {
                        $carHostVehicleFeature = new CarHostVehicleFeature();
                        $carHostVehicleFeature->vehicles_id = $vehicle->vehicle_id;
                        $carHostVehicleFeature->feature_id = $value;
                        $carHostVehicleFeature->save();
                    }
                    $newVehicleFeatureIdsArr = CarHostVehicleFeature::where('vehicles_id', $vehicle->vehicle_id)->pluck('feature_id')->toArray();
                    $newVehicleFeature = $newVehicleFeatureIdsArr;
                }  
            }else{
                $vehicleFeatureIdsArr = VehicleFeatureMapping::where('vehicle_id', $vehicle->vehicle_id)->pluck('feature_id')->toArray();
                $oldVehicleFeature = $vehicleFeatureIdsArr;
                if(isset($features) && is_countable($features) && count($features) > 0)
                {
                    if(isset($vehicle->features) && is_countable($vehicle->features) && count($vehicle->features) > 0){
                        $vehicleFeatureMapping = VehicleFeatureMapping::where('vehicle_id', $vehicle->vehicle_id)->delete();
                    }
                    foreach ($features as $key => $value) {
                        $vehicleFeatureMapping = new VehicleFeatureMapping();
                        $vehicleFeatureMapping->vehicle_id = $vehicle->vehicle_id;
                        $vehicleFeatureMapping->feature_id  = $value;
                        $vehicleFeatureMapping->save();
                    }
                    $newVehicleFeatureIdsArr = VehicleFeatureMapping::where('vehicle_id', $vehicle->vehicle_id)->pluck('feature_id')->toArray();
                    $newVehicleFeature = $newVehicleFeatureIdsArr;
                }
            }
            $oldArr = [
                'vehicleFeature' => $oldVehicleFeature, 
            ];
            $newArr = [
                'vehicleFeature' => $newVehicleFeature, 
            ];
            logAdminActivities('Vehicle features Add/Updation', $oldArr, $newArr);

            return $this->successResponse([], 'Vehicle feature details are stored Successfully');
        }elseif($request->vehicle_step == 'vehicle_images'){
            // VEHICLE IMAGES
            if(isset($hostVehicleDetails) && $hostVehicleDetails != ''){
                if(is_countable($request->file('regular_images')) && count($request->file('regular_images')) > 0){ 
                    $carHostVehicleImage = CarHostVehicleImage::where(['vehicles_id' => $vehicle->vehicle_id, 'image_type' => 2])->get(); //image_type = 2 means Vehicle Interior images
                    if(is_countable($carHostVehicleImage) && count($carHostVehicleImage) > 0){
                        $this->unlinkImagesArr($carHostVehicleImage);
                    }
                    foreach ($request->file('regular_images') as $key => $image) {
                        $filename = 'Interior_'.$vehicle->vehicle_id.'_'.$key.'_'.time() . '.' . $image->getClientOriginalExtension();
                        $image->move(public_path('images/car_host'), $filename);
                        $carHostVehicleImage = new CarHostVehicleImage();
                        $carHostVehicleImage->vehicles_id = $vehicle->vehicle_id;
                        $carHostVehicleImage->image_type = 2;
                        $carHostVehicleImage->vehicle_img = $filename;
                        $carHostVehicleImage->save();
                    }
                }
                if(is_countable($request->file('banner_images')) && count($request->file('banner_images')) > 0){
                    $carHostVehicleImage = CarHostVehicleImage::where(['vehicles_id' => $vehicle->vehicle_id, 'image_type' => 3])->get(); //image_type = 3 means Vehicle Exterior images
                    if(is_countable($carHostVehicleImage) && count($carHostVehicleImage) > 0){
                        $this->unlinkImagesArr($carHostVehicleImage);
                    }
                    foreach ($request->file('banner_images') as $key => $image) {
                        $filename = 'Exterior_'.$vehicle->vehicle_id.'_'.$key.'_'.time() . '.' . $image->getClientOriginalExtension();   
                        $image->move(public_path('images/car_host'), $filename);
                        $carHostVehicleImage = new CarHostVehicleImage();
                        $carHostVehicleImage->vehicles_id = $vehicle->vehicle_id;
                        $carHostVehicleImage->image_type = 3;
                        $carHostVehicleImage->vehicle_img = $filename;
                        $carHostVehicleImage->save();
                    }
                }
            }else{
                try{
                    // EXISTING BANNER IMAGE COMPARISION
                    $oldBannerImages = json_decode($request->old_banner_images, true);
                    if(isset($oldBannerImages) && is_countable($oldBannerImages) && count($oldBannerImages) > 0){
                        $vehicleImgs = VehicleImage::where('vehicle_id', $request->vehicle_id)->where('image_type', 'banner')->get();
                        foreach ($vehicleImgs as $k => $v) {
                            if(!in_array($v->image_url, $oldBannerImages)){
                                $parsedUrl = parse_url($v->image_url);
                                $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : ''; 
                                $path = public_path($path);
                                if (file_exists($path)){
                                    unlink($path);
                                }
                                $v->delete();
                            }
                        }
                    }else{
                        $vehicleImgs = VehicleImage::where('vehicle_id', $request->vehicle_id)->where('image_type', 'banner')->get();
                        foreach ($vehicleImgs as $k => $v) {
                            $parsedUrl = parse_url($v->image_url);
                            $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : ''; 
                            $path = public_path($path);
                            if (file_exists($path)){
                                unlink($path);
                            }
                            $v->delete();
                        }
                    }
                    // NEW BANNER IMAGIES
                    if(isset($request->banner_images) && is_countable($request->banner_images) && count($request->banner_images) > 0){
                        // ADD NEW BANNER IMAGIES
                        foreach($request->banner_images as $key => $val){
                            $extension = $val->getClientOriginalExtension();
                            $filename = 'banner_img_'.time() . '_' . uniqid() . '.' . $extension;
                            $val->move(public_path('images/vehicle_images/'), $filename);
                            $vehicleImg = new VehicleImage();
                            $vehicleImg->vehicle_id = $request->vehicle_id;
                            $vehicleImg->image_type = 'banner';
                            $vehicleImg->image_url = $filename;
                            $vehicleImg->save();
                        }
                    }

                    // EXISTING REGULAR IMAGE COMPARISION
                    $oldRegularImages = json_decode($request->old_regular_images, true);
                    if(isset($oldRegularImages) && is_countable($oldRegularImages) && count($oldRegularImages) > 0){
                        $vehicleImgs = VehicleImage::where('vehicle_id', $request->vehicle_id)->where('image_type', 'regular')->get();
                        foreach ($vehicleImgs as $k => $v) {
                            if(!in_array($v->image_url, $oldRegularImages)){
                                $parsedUrl = parse_url($v->image_url);
                                $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : ''; 
                                $path = public_path($path);
                                if (file_exists($path)){
                                    unlink($path);
                                }
                                $v->delete();
                            }
                        }
                    }else{
                        $vehicleImgs = VehicleImage::where('vehicle_id', $request->vehicle_id)->where('image_type', 'regular')->get();
                        foreach ($vehicleImgs as $k => $v) {
                            $parsedUrl = parse_url($v->image_url);
                            $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : ''; 
                            $path = public_path($path);
                            if (file_exists($path)){
                                unlink($path);
                            }
                            $v->delete();
                        }
                    } 
                    // NEW REGULAR IMAGIES
                    if(isset($request->regular_images) && is_countable($request->regular_images) && count($request->regular_images) > 0){
                        // ADD NEW REGULAR IMAGIES
                        foreach($request->regular_images as $key => $val){
                            $extension = $val->getClientOriginalExtension();
                            $filename = 'regular_img_'.time() . '_' . uniqid() . '.' . $extension;
                            $val->move(public_path('images/vehicle_images/'), $filename);
                            $vehicleImg = new VehicleImage();
                            $vehicleImg->vehicle_id = $request->vehicle_id;
                            $vehicleImg->image_type = 'regular';
                            $vehicleImg->image_url = $filename;
                            $vehicleImg->save();
                        }
                    }  
                    // CUTOUT IMAGE
                    if(isset($request->cutout_image) && $request->cutout_image != '')
                    {
                        $file = $request->cutout_image;
                        $extension = $file->getClientOriginalExtension();
                        $filename = 'cutout_img_'.time() . '_' . uniqid() . '.' . $extension; // Append a unique identifier to the filename
                        $file->move(public_path('images/vehicle_images/'), $filename);
                        $vehicleImg = VehicleImage::where('vehicle_id', $request->vehicle_id)->where('image_type', 'cutout')->first();
                        if($vehicleImg != ''){
                            $parsedUrl = parse_url($vehicleImg->image_url);
                            $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : ''; 
                            $path = public_path($path);
                            if (file_exists($path)){
                                unlink($path);
                            }    
                        }else{
                            $vehicleImg = new VehicleImage();
                            $vehicleImg->vehicle_id = $request->vehicle_id;
                            $vehicleImg->image_type = 'cutout';
                        }
                        $vehicleImg->image_url = $filename;
                        $vehicleImg->save();
                    }
                } catch (\Exception $e) {}
            }
            
            // VEHICLE DOCUMENTS
            if(isset($vehicle->vehicleDocuments) && is_countable($vehicle->vehicleDocuments) && count($vehicle->vehicleDocuments) > 0){
                $oldVehicleDoc = VehicleDocument::where('vehicle_id', $vehicle->vehicle_id)->get();
                try{
                    // COMPARE EXISTING RC DOCUMENTS IMAGES
                    $oldRcImages = json_decode($request->old_rc_images, true);
                    if(isset($oldRcImages) && is_countable($oldRcImages) && count($oldRcImages) > 0){
                        $vehicleRcDocImgs = VehicleDocument::where('vehicle_id', $vehicle->vehicle_id)->where('document_type', 'rc_doc')->get();
                        foreach ($vehicleRcDocImgs as $k => $v) {
                            $checkImg = asset('images/documents/'.$v->document_image_url);
                            if(!in_array($checkImg, $oldRcImages)){
                                $parsedUrl = parse_url($v->document_image_url);
                                $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : ''; 
                                $path = public_path($path);
                                if (file_exists($path)){
                                    unlink($path);
                                }
                                $v->delete();
                            }
                        }
                    }else{
                        $vehicleRcDocImgs = VehicleDocument::where('vehicle_id', $vehicle->vehicle_id)->where('document_type', 'rc_doc')->get();
                        foreach ($vehicleRcDocImgs as $k => $v) {
                            $parsedUrl = parse_url($v->document_image_url);
                            $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : ''; 
                            $path = public_path($path);
                            if (file_exists($path)){
                                unlink($path);
                            }
                            $v->delete();
                        }
                    }   
            
                    if(isset($request->rc_expiry_date) && $request->rc_expiry_date != ''){
                        if(isset($request->document_rc_image) && is_countable($request->document_rc_image) && count($request->document_rc_image) > 0){
                            foreach($request->document_rc_image as $key => $val){
                                $extension = $val->getClientOriginalExtension();
                                $filename = 'doc_rc_img_'.$key.'_'.time() . '_' . uniqid() . '.' . $extension;
                                $val->move(public_path('images/documents'), $filename);
                                $vehicleDoc = new VehicleDocument();
                                $vehicleDoc->vehicle_id = $vehicle->vehicle_id;
                                $vehicleDoc->document_type = 'rc_doc';
                                //$vehicleDoc->id_number = $request->rc_number;
                                $vehicleDoc->expiry_date = $request->rc_expiry_date;
                                $vehicleDoc->is_approved = 1;
                                $vehicleDoc->approved_by = 1;
                                $vehicleDoc->document_image_url = $filename;
                                $vehicleDoc->created_at = now();
                                $vehicleDoc->updated_at = now();
                                $vehicleDoc->save();
                            }
                        }else{
                            $vDoc = VehicleDocument::where(['vehicle_id' => $vehicle->vehicle_id, 'document_type' => 'rc_doc'])->get();
                            if(isset($vDoc) && is_countable($vDoc) && count($vDoc) > 0){
                                foreach ($vDoc as $key => $value) {
                                    $value->expiry_date = $request->rc_expiry_date;
                                    //$value->id_number = $request->rc_number;
                                    $value->is_approved = 1;
                                    $value->approved_by = 1;
                                    $value->updated_at = now();
                                    $value->save();
                                }
                            }
                        }  
                    }
                    if(isset($request->puc_expiry_date) && $request->puc_expiry_date != NULL /*&& isset($request->puc_number) && $request->puc_number != NULL*/){
                        $image = DB::table('vehicle_documents')
                            ->updateOrInsert(
                                ['vehicle_id' => $vehicle->vehicle_id, 'document_type' => 'puc_doc'],
                                [
                                    'expiry_date' => $request->puc_expiry_date,
                                    //'id_number' => $request->puc_number,
                                    'is_approved' => 1,
                                    'approved_by' => 1,
                                    'updated_at' => now()
                                ]
                            );
                    }
                    if(isset($request->document_puc_image) && $request->document_puc_image != NULL){
                        $file = $request->file('document_puc_image');
                        $extension = $file->getClientOriginalExtension();
                        $filename = 'doc_puc_img'.time() . '_' . uniqid() . '.' . $extension; 
                        $file->move(public_path('images/documents'), $filename);
                        $image = DB::table('vehicle_documents')
                                ->updateOrInsert(
                                    ['vehicle_id' => $vehicle->vehicle_id, 'document_type' => 'puc_doc'],
                                    [
                                        'document_image_url' => $filename,
                                        'updated_at' => now()
                                    ]
                                );
                    }
                    if(isset($request->insurance_expiry_date) && $request->insurance_expiry_date != NULL /*&& isset($request->insurance_number) && $request->insurance_number != NULL*/){
                        $image = DB::table('vehicle_documents')
                            ->updateOrInsert(
                                ['vehicle_id' => $vehicle->vehicle_id, 'document_type' => 'insurance_doc'],
                                [
                                    'expiry_date' => $request->insurance_expiry_date,
                                    //'id_number' => $request->insurance_number,
                                    'is_approved' => 1,
                                    'approved_by' => 1,
                                    'updated_at' => now()
                                ]
                            );
                    }
                    if(isset($request->document_insurance_image) && $request->document_insurance_image != NULL){
                        $file = $request->file('document_insurance_image');
                        $extension = $file->getClientOriginalExtension();
                        $filename = 'doc_insurance_img'.time() . '_' . uniqid() . '.' . $extension; 
                        $file->move(public_path('images/documents'), $filename);
                        $image = DB::table('vehicle_documents')
                                ->updateOrInsert(
                                    ['vehicle_id' => $vehicle->vehicle_id, 'document_type' => 'insurance_doc'],
                                    [
                                        'document_image_url' => $filename,
                                        'updated_at' => now()
                                    ]
                                );
                    }
                } catch (\Exception $e) {}     
                $newVehicleDoc = VehicleDocument::where('vehicle_id', $vehicle->vehicle_id)->get();
                $oldArr = [
                    'vehicleDoc' => $oldVehicleDoc, 
                ];
                $newArr = [
                    'vehicleDoc' => $newVehicleDoc, 
                ];
                logAdminActivities('Vehicle Updation', $oldArr, $newArr);
            }else{
                try {
                    if(isset($request->document_rc_image) && is_countable($request->document_rc_image) && count($request->document_rc_image) > 0){
                        foreach($request->document_rc_image as $key => $val){
                            $extension = $val->getClientOriginalExtension();
                            $filename = 'doc_rc_img_'.$key.'_'.time() . '_' . uniqid() . '.' . $extension;
                            $val->move(public_path('images/documents/'), $filename);
                            $vehicleDoc = new VehicleDocument();
                            $vehicleDoc->vehicle_id = $vehicle->vehicle_id;
                            $vehicleDoc->document_type = 'rc_doc';
                            $vehicleDoc->expiry_date = $request->rc_expiry_date;
                            $vehicleDoc->is_approved = 1;
                            $vehicleDoc->approved_by = 1;
                            $vehicleDoc->document_image_url = $filename;
                            $vehicleDoc->created_at = now();
                            $vehicleDoc->updated_at = now();
                            $vehicleDoc->save();
                        }
                    }  
                    if(isset($request->document_puc_image)){
                        $file = $request->document_puc_image;
                        $extension = $file->getClientOriginalExtension();
                        $filename = 'doc_puc_img_'.time() . '_' . uniqid() . '.' . $extension;
                        $file->move(public_path('images/documents'), $filename);
                        $vehicleDoc = new VehicleDocument();
                        $vehicleDoc->vehicle_id = $vehicle->vehicle_id;
                        $vehicleDoc->document_type = 'puc_doc';
                        $vehicleDoc->expiry_date = $request->puc_expiry_date;
                        $vehicleDoc->is_approved = 1;
                        $vehicleDoc->approved_by = 1;
                        $vehicleDoc->document_image_url = $filename;
                        $vehicleDoc->created_at = now();
                        $vehicleDoc->updated_at = now();
                        $vehicleDoc->save();
                    }
                    if(isset($request->document_insurance_image)){
                        $file = $request->document_insurance_image;
                        $extension = $file->getClientOriginalExtension();
                        $filename = 'doc_insurance_img_'.time() . '_' . uniqid() . '.' . $extension;
                        $file->move(public_path('images/documents'), $filename);
                        $vehicleDoc = new VehicleDocument();
                        $vehicleDoc->vehicle_id = $vehicle->vehicle_id;
                        $vehicleDoc->document_type = 'insurance_doc';
                        $vehicleDoc->expiry_date = $request->insurance_expiry_date;
                        $vehicleDoc->is_approved = 1;
                        $vehicleDoc->approved_by = 1;
                        $vehicleDoc->document_image_url = $filename;
                        $vehicleDoc->created_at = now();
                        $vehicleDoc->updated_at = now();
                        $vehicleDoc->save();
                    }  
                } catch (\Exception $e) {}
            }
            
            if(isset($vehicle->license_plate) && $vehicle->license_plate != ''){
                $vehicleRcDoc = VehicleDocument::where('vehicle_id', $vehicle->vehicle_id)->where('document_type', 'rc_doc')->get();
                if(isset($vehicleRcDoc) && is_countable($vehicleRcDoc) && count($vehicleRcDoc) > 0){
                    foreach($vehicleRcDoc as $key => $val){
                        $val->id_number = $vehicle->license_plate;
                        $val->save();
                    }
                }
            }
            logAdminActivities("Vehicle Documents Added", $vehicleRcDoc);
            return $this->successResponse([], 'Vehicle Images details are stored succssfully');
        }elseif($request->vehicle_step == 'price_calculation'){
            // PRICE SUMMARY
            $priceCalc = $request->pricing_details ?? '';
            $oldPriceSummary = $newPriceSummary = [];
            if($priceCalc != '') {
                $priceCalc = json_decode($request->pricing_details, true);
                $rentalPrice = $rentalPriceHour = 0;
                asort($priceCalc);// make sort based on its value on ascending order
                if(is_countable($priceCalc) && count($priceCalc) > 0){
                    foreach ($priceCalc as $key => $value) {
                        if($value > 0){
                            $rentalPrice = $value;
                            $rentalPriceHour = $key;
                            break;
                        }
                    }
                }
                krsort($priceCalc); //make sort based on its key on descending order
                $multipliers = []; // Array to hold the multipliers
                foreach ($priceCalc as $key => $value) {
                    $multiplierVal = 0;
                    if($rentalPrice <= $value){
                        $multiplierVal = ($value / $rentalPrice);
                    }
                    $multipliers[$key][$value] = round($multiplierVal, 2);
                }

                $notShowPrice = [];
                $vehiclePriceDetails = VehiclePriceDetail::where('vehicle_id', $vehicle->vehicle_id)->get();
                $oldPriceSummary = clone $vehiclePriceDetails;
                if(is_countable($vehiclePriceDetails) && count($vehiclePriceDetails) > 0){
                    foreach ($vehiclePriceDetails as $key => $value) {
                        if($value->is_show == 0){
                            $notShowPrice[] = $value->hours;
                        }
                        $value->delete();
                    }
                }

                if(is_countable($multipliers) && count($multipliers) > 0){
                    foreach ($multipliers as $key => $value) {
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
                        $vehiclePriceDetail->trip_amount_km_limit = calculateKmLimit($key)." Km";
                        $vehiclePriceDetail->save();   
                    }
                    $newPriceSummary = $vehiclePriceDetail;
                    $vehiclePriceDetails = VehiclePriceDetail::where('vehicle_id', $vehicle->vehicle_id)->get();
                    if(is_countable($vehiclePriceDetails) && count($vehiclePriceDetails) > 0 && is_countable($notShowPrice) && count($notShowPrice) > 0){
                        foreach ($vehiclePriceDetails as $key => $value) {  
                            if(in_array($value->hours, $notShowPrice)){
                                $value->is_show = 0;
                                $value->save();
                            }
                        }
                    }
                }

                $vehicle->rental_price = $request->rental_price;
                $vehicle->extra_km_rate = $request->extra_km_rate;
                $vehicle->extra_hour_rate = $request->extra_hour_rate;
                $vehicle->save();

                $oldArr = [
                    'oldPriceSummary' => $oldPriceSummary
                ];
                $newArr = [
                    'newPriceSummary' => $newPriceSummary
                ];
                logAdminActivities('Vehicle Price Add/Updation', $oldArr, $newArr);

                return $this->successResponse([], "Vehicle Price details are stored successfully");
            }
        }elseif($request->vehicle_step == 'availability_dates'){
            //ADD AVAILABILITY CALANDER
            $availabilityCalander = json_decode($request->availability_calendar, true);
            if(isset($availabilityCalander) && is_countable($availabilityCalander) && count($availabilityCalander) > 0){
                $calArray = [];
                foreach ($availabilityCalander as $key => $value) {
                    if(isset($value) && isset($value['start_date'])){
                        $calArray[$key]['start_date'] = isset($value['start_date'])? date('d-m-Y H:i A', strtotime($value['start_date'])): '';
                        $calArray[$key]['end_date'] = isset($value['end_date'])? date('d-m-Y H:i A', strtotime($value['end_date'])): '';
                        $calArray[$key]['reason'] = isset($value['reason'])?$value['reason']:'';
                    }
                }
                $vehicle->availability_calendar = json_encode($calArray);
                $vehicle->save();
                $newVehicleAvailabilityDates = $vehicle->availability_calendar;

                $oldArr = [
                    'oldVehicleAvailabilityDates' => $oldVehicleAvailabilityDates
                ];
                $newArr = [
                    'newVehicleAvailabilityDates' => $newVehicleAvailabilityDates
                ];

                logAdminActivities('Vehicle Availability Date Add / Updation', $oldArr, $newArr);
            }
            return $this->successResponse([], "Vehicle Avalability dates are stored successfully");
        }else{
            return $this->errorResponse("Invalid Selection for vehicle add");
        }
    }

    // public function updateVehicle(Request $request){ // Un-used
    //     $isCarHostVehicle = CarEligibility::where('vehicle_id', $request->vehicle_id)->exists();
    //     $validator = Validator::make($request->all(), [
    //         'vehicle_id' => 'required|exists:vehicles,vehicle_id',
    //         'commission_percent' => 'required|numeric|min:0|max:100',
    //         'year' => 'required|numeric',
    //         'rental_price' => 'required|numeric',
    //         'extra_km_rate' => 'required|numeric',
    //         'extra_hour_rate' => 'required|numeric',
    //         'model_id' => 'required|exists:vehicle_models,model_id',
    //         'description' => 'nullable|max:500',
    //         'deposit_amount' => 'nullable|numeric',
    //         'is_deposit_amount_show' => 'nullable|in:0,1',
    //         'color' => 'required',
    //         'seating_capacity' => 'nullable|numeric',
    //         'mileage' => 'nullable',
    //         'license_plate' => 'required',
    //         'availability' => 'required|in:0,1',
    //         'rc_expiry_date' => 'required|date',
    //         //'puc_expiry_date' => 'required|date',
    //         'puc_expiry_date' => 'nullable|date',
    //         'insurance_expiry_date' => 'required|date',
    //         'document_rc_image' => 'nullable|array|max:2', // Ensure it's an array and allows max 2 files
    //         'document_rc_image.*' => 'nullable|image|mimetypes:image/heic,image/heif,image/jpeg,image/png,image/jpg,image/bmp,image/gif,image/svg,image/webp|max:2000',
    //         'document_puc_image' => 'nullable|image|mimetypes:image/heic,image/heif,image/jpeg,image/png,image/jpg,image/bmp,image/gif,image/svg,image/webp|max:2000',
    //         'document_insurance_image' => 'nullable|image|mimetypes:image/heic,image/heif,image/jpeg,image/png,image/jpg,image/bmp,image/gif,image/svg,image/webp|max:2000',
    //         'cutout_image' => 'nullable|image|mimetypes:image/heic,image/heif,image/jpeg,image/png,image/jpg,image/bmp,image/gif,image/svg,image/webp|max:2000',
    //         'banner_images' => 'nullable|array', 
    //         'banner_images.*' => 'image|mimetypes:image/heic,image/heif,image/jpeg,image/png,image/jpg,image/bmp,image/gif,image/svg,image/webp|max:5000', 
    //         'regular_images' => 'nullable|array',
    //         'regular_images.*' => 'image|mimetypes:image/heic,image/heif,image/jpeg,image/png,image/jpg,image/bmp,image/gif,image/svg,image/webp|max:5000',
    //         'engine_cc' => 'required|numeric',
    //         'fuel_capacity' => 'required|numeric',
    //         'fuel_type' => 'required|numeric|exists:vehicle_fuel_types,fuel_type_id',
    //         'mileage' => 'required|numeric',
    //         'vehicle_transmission' => 'required|numeric|exists:vehicle_transmissions,transmission_id',
    //         'seating_capacity' => 'required|numeric',
    //     ], [
    //         'commission_percent' => 'Commission Percent is required',
    //         'year.required' => 'Manufacture Year is required.',
    //         'rental_price.required' => 'Rental Price is required.',
    //         'extra_km_rate.required' => 'Extra Km Rate Price is required.',
    //         'extra_hour_rate.required' => 'Extra Hour Rate Price is required.',
    //         'model_id.required' => 'Model is required.',
    //         'description.required' => 'Description is required.',
    //         'description.max' => 'Description length is max 500 characters',
    //         'color.required' => 'Color is required.',
    //         'license_plate.required' => 'Registration Number is required.',
    //         'availability.required' => 'Availability is required.',
    //         'availability.in' => 'Availability should be either 0 or 1.',
    //         'rc_expiry_date.required' => 'RC Expiry Date is required.',
    //         //'puc_expiry_date.required' => 'PUC Expiry Date is required.',
    //         'insurance_expiry_date.required' => 'Insurance Expiry Date is required.',
    //         // 'document_rc_image.*.required' => 'RC Document image is required',
    //         // 'document_rc_image.*.mimes' => 'You can select only Image',
    //         // 'document_puc_image.required' => 'PUC Document image is required',
    //         // 'document_puc_image.mimes' => 'You can select only Image',
    //         // 'document_insurance_image.required' => 'Document Insurance image is required',
    //         // 'document_insurance_image.mimes' => 'You can select only Image',
    //         // 'cutout_img.required' => 'Document image is required',
    //         // 'cutout_img.mimes' => 'You can select only Image',
    //         'engine_cc' => 'Please enter Engine CC',
    //         'fuel_capacity' => 'Please enter Fuel Capacity',
    //         'fuel_type' => 'Please select Fuel Type',
    //         'mileage' => 'Please enter Mileage',
    //         'vehicle_transmission' => 'Please select Transmission Type',
    //         'seating_capacity' => 'Please enter Seating Capacity',
    //     ]);
    //     if ($validator->fails()) {
    //         return $this->validationErrorResponse($validator);
    //     }

    //     $oldVehicleFeature = [];
    //     $newVehicleFeature = [];

    //     // Vehicle 
    //     $vehicleData = Vehicle::where('vehicle_id', $request->vehicle_id)->first();
    //     $oldVehicleAvailabilityDates = $vehicleData->availability_calendar;
    //     $oldVehicle = clone $vehicleData;
    //     $vehicleData->branch_id = $request->branch;
    //     $vehicleData->model_id = $request->model_id;
    //     $vehicleData->year = $request->year;
    //     $vehicleData->description = $request->description;
    //     $vehicleData->color = $request->color;
    //     $vehicleData->license_plate = $request->license_plate;
    //     $vehicleData->availability = $request->availability;
    //     $vehicleData->rental_price = $request->rental_price;
    //     $vehicleData->extra_km_rate = $request->extra_km_rate;
    //     $vehicleData->extra_hour_rate = $request->extra_hour_rate;
    //     $vehicleData->updated_at = now();
    //     $vehicleData->commission_percent = $request->commission_percent ?? 0;
    //     $vehicleData->deposit_amount = $request->deposit_amount ?? 0;
    //     $vehicleData->is_deposit_amount_show = $request->is_deposit_amount_show;
    //     $vehicleData->save();
    //     $newVehicle = $vehicleData;

    //     $vehicleRcDoc = VehicleDocument::where('vehicle_id', $request->vehicle_id)->where('document_type', 'rc_doc')->get();
    //     if(isset($vehicleRcDoc) && is_countable($vehicleRcDoc) && count($vehicleRcDoc) > 0){
    //         foreach($vehicleRcDoc as $key => $val){
    //             $val->id_number = $request->license_plate;
    //             $val->save();
    //         }
    //     }

    //     // Vehicle Properties
    //     // Extract numeric part of engine displacement (remove "cc")
    //     $engine_cc_numeric = intval(preg_replace('/[^0-9]/', '', $request->engine_cc));
    //     $fuel_capacity_numeric = intval(preg_replace('/[^0-9]/', '', $request->fuel_capacity));
    //     $mileage = intval(preg_replace('/[^0-9]/', '', $request->mileage));

    //     $propertyData = VehicleProperty::where('vehicle_id', $request->vehicle_id)->first();
    //     $oldVehicleProperty = '';
    //     if($propertyData != ''){
    //         $oldVehicleProperty = clone $propertyData;
    //         $propertyData->mileage = $mileage;
    //         $propertyData->fuel_type_id = $request->fuel_type;
    //         $propertyData->transmission_id = $request->vehicle_transmission;
    //         $propertyData->seating_capacity = $request->seating_capacity;
    //         $propertyData->engine_cc = $engine_cc_numeric;
    //         $propertyData->fuel_capacity = $fuel_capacity_numeric;
    //         $propertyData->updated_at = now();
    //         $propertyData->save();
    //     }
    //     $newVehicleProperty = $propertyData;
    //     $priceCalc = $request->pricing_details ?? '';
    //     if($priceCalc != '') {
    //         $priceCalc = json_decode($request->pricing_details, true);
    //         $rentalPrice = $rentalPriceHour = 0;
    //         asort($priceCalc);// make sort based on its value on ascending order
    //         if(is_countable($priceCalc) && count($priceCalc) > 0){
    //             foreach ($priceCalc as $key => $value) {
    //                 if($value > 0){
    //                     $rentalPrice = $value;
    //                     $rentalPriceHour = $key;
    //                     break;
    //                 }
    //             }
    //         }
    //         krsort($priceCalc); //make sort based on its key on descending order
    //         $multipliers = []; // Array to hold the multipliers
    //         foreach ($priceCalc as $key => $value) {
    //             $multiplierVal = 0;
    //             if($rentalPrice <= $value){
    //                 $multiplierVal = ($value / $rentalPrice);
    //             }
    //             $multipliers[$key][$value] = round($multiplierVal, 2);
    //         }

    //         $notShowPrice = [];
    //         $vehiclePriceDetails = VehiclePriceDetail::where('vehicle_id', $vehicleData->vehicle_id)->get();
    //         if(is_countable($vehiclePriceDetails) && count($vehiclePriceDetails) > 0){
    //             foreach ($vehiclePriceDetails as $key => $value) {
    //                 if($value->is_show == 0){
    //                     $notShowPrice[] = $value->hours;
    //                 }
    //                 $value->delete();
    //             }
    //         }
    //         if(is_countable($multipliers) && count($multipliers) > 0){
    //             foreach ($multipliers as $key => $value) {
    //                 $vehiclePriceDetail = new VehiclePriceDetail();
    //                 $vehiclePriceDetail->vehicle_id = $vehicleData->vehicle_id;
    //                 $vehiclePriceDetail->rental_price = $rentalPrice;
    //                 $vehiclePriceDetail->hours = $key;
    //                 foreach ($value as $k => $v) {
    //                     $vehiclePriceDetail->rate = $k;
    //                     $vehiclePriceDetail->multiplier = $v;
    //                     $perHourRate = $k / $key;
    //                     $vehiclePriceDetail->per_hour_rate = number_format(($perHourRate), 2);
    //                     $vehiclePriceDetail->unlimited_km_trip_amount = $k * 1.3;
    //                 }
    //                 $vehiclePriceDetail->duration = ($key >= 24) ? round($key / 24, 2) . ' days' : $key . ' hours';
    //                 $vehiclePriceDetail->trip_amount_km_limit = calculateKmLimit($key)." Km";
    //                 $vehiclePriceDetail->save();   
    //             }
    //             $vehiclePriceDetails = VehiclePriceDetail::where('vehicle_id', $vehicleData->vehicle_id)->get();
    //             if(is_countable($vehiclePriceDetails) && count($vehiclePriceDetails) > 0 && is_countable($notShowPrice) && count($notShowPrice) > 0){
    //                 foreach ($vehiclePriceDetails as $key => $value) {  
    //                     if(in_array($value->hours, $notShowPrice)){
    //                         $value->is_show = 0;
    //                         $value->save();
    //                     }
    //                 }
    //             }
    //         }
    //     }

    //     // Vehicle Documents
    //     $oldVehicleDoc = VehicleDocument::where('vehicle_id', $request->vehicle_id)->get();
    //     try{
    //         // COMPARE EXISTING RC DOCUMENTS IMAGES
    //         $oldRcImages = json_decode($request->old_rc_images, true);
    //         if(isset($oldRcImages) && is_countable($oldRcImages) && count($oldRcImages) > 0){
    //             $vehicleRcDocImgs = VehicleDocument::where('vehicle_id', $request->vehicle_id)->where('document_type', 'rc_doc')->get();
    //             foreach ($vehicleRcDocImgs as $k => $v) {
    //                 $checkImg = asset('images/documents/'.$v->document_image_url);
    //                 if(!in_array($checkImg, $oldRcImages)){
    //                     $parsedUrl = parse_url($v->document_image_url);
    //                     $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : ''; 
    //                     $path = public_path($path);
    //                     if (file_exists($path)){
    //                         unlink($path);
    //                     }
    //                     $v->delete();
    //                 }
    //             }
    //         }else{
    //             $vehicleRcDocImgs = VehicleDocument::where('vehicle_id', $request->vehicle_id)->where('document_type', 'rc_doc')->get();
    //             foreach ($vehicleRcDocImgs as $k => $v) {
    //                 $parsedUrl = parse_url($v->document_image_url);
    //                 $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : ''; 
    //                 $path = public_path($path);
    //                 if (file_exists($path)){
    //                     unlink($path);
    //                 }
    //                 $v->delete();
    //             }
    //         }   
            
    //         if(isset($request->rc_expiry_date) && $request->rc_expiry_date != ''){
    //             if(isset($request->document_rc_image) && is_countable($request->document_rc_image) && count($request->document_rc_image) > 0){
    //                 foreach($request->document_rc_image as $key => $val){
    //                     $extension = $val->getClientOriginalExtension();
    //                     $filename = 'doc_rc_img_'.$key.'_'.time() . '_' . uniqid() . '.' . $extension;
    //                     $val->move(public_path('images/documents'), $filename);
    //                     $vehicleDoc = new VehicleDocument();
    //                     $vehicleDoc->vehicle_id = $request->vehicle_id;
    //                     $vehicleDoc->document_type = 'rc_doc';
    //                     //$vehicleDoc->id_number = $request->rc_number;
    //                     $vehicleDoc->expiry_date = $request->rc_expiry_date;
    //                     $vehicleDoc->is_approved = 1;
    //                     $vehicleDoc->approved_by = 1;
    //                     $vehicleDoc->document_image_url = $filename;
    //                     $vehicleDoc->created_at = now();
    //                     $vehicleDoc->updated_at = now();
    //                     $vehicleDoc->save();
    //                 }
    //             }else{
    //                 $vDoc = VehicleDocument::where(['vehicle_id' => $request->vehicle_id, 'document_type' => 'rc_doc'])->get();
    //                 if(isset($vDoc) && is_countable($vDoc) && count($vDoc) > 0){
    //                     foreach ($vDoc as $key => $value) {
    //                         $value->expiry_date = $request->rc_expiry_date;
    //                         //$value->id_number = $request->rc_number;
    //                         $value->is_approved = 1;
    //                         $value->approved_by = 1;
    //                         $value->updated_at = now();
    //                         $value->save();
    //                     }
    //                 }
    //             }  

    //         }
    //         if(isset($request->puc_expiry_date) && $request->puc_expiry_date != NULL /*&& isset($request->puc_number) && $request->puc_number != NULL*/){
    //             $image = DB::table('vehicle_documents')
    //                 ->updateOrInsert(
    //                     ['vehicle_id' => $request->vehicle_id, 'document_type' => 'puc_doc'],
    //                     [
    //                         'expiry_date' => $request->puc_expiry_date,
    //                         //'id_number' => $request->puc_number,
    //                         'is_approved' => 1,
    //                         'approved_by' => 1,
    //                         'updated_at' => now()
    //                     ]
    //                 );
    //         }
    //         if(isset($request->document_puc_image) && $request->document_puc_image != NULL){
    //             $file = $request->file('document_puc_image');
    //             $extension = $file->getClientOriginalExtension();
    //             $filename = 'doc_puc_img'.time() . '_' . uniqid() . '.' . $extension; 
    //             $file->move(public_path('images/documents'), $filename);
    //             $image = DB::table('vehicle_documents')
    //                     ->updateOrInsert(
    //                         ['vehicle_id' => $request->vehicle_id, 'document_type' => 'puc_doc'],
    //                         [
    //                             'document_image_url' => $filename,
    //                             'updated_at' => now()
    //                         ]
    //                     );
    //         }
    //         if(isset($request->insurance_expiry_date) && $request->insurance_expiry_date != NULL /*&& isset($request->insurance_number) && $request->insurance_number != NULL*/){
    //             $image = DB::table('vehicle_documents')
    //                 ->updateOrInsert(
    //                     ['vehicle_id' => $request->vehicle_id, 'document_type' => 'insurance_doc'],
    //                     [
    //                         'expiry_date' => $request->insurance_expiry_date,
    //                         //'id_number' => $request->insurance_number,
    //                         'is_approved' => 1,
    //                         'approved_by' => 1,
    //                         'updated_at' => now()
    //                     ]
    //                 );
    //         }
    //         if(isset($request->document_insurance_image) && $request->document_insurance_image != NULL){
    //             $file = $request->file('document_insurance_image');
    //             $extension = $file->getClientOriginalExtension();
    //             $filename = 'doc_insurance_img'.time() . '_' . uniqid() . '.' . $extension; 
    //             $file->move(public_path('images/documents'), $filename);
    //             $image = DB::table('vehicle_documents')
    //                     ->updateOrInsert(
    //                         ['vehicle_id' => $request->vehicle_id, 'document_type' => 'insurance_doc'],
    //                         [
    //                             'document_image_url' => $filename,
    //                             'updated_at' => now()
    //                         ]
    //                     );
    //         }

    //         if($isCarHostVehicle){
    //             if(is_countable($features) && count($features) > 0){
    //                 $carHostVehicleFeature = CarHostVehicleFeature::where('vehicles_id', $request->vehicle_id)->pluck('feature_id')->toArray();
    //                 $oldVehicleFeature = $carHostVehicleFeature;
    //                 $carHostVehicleFeature = CarHostVehicleFeature::where('vehicles_id', $request->vehicle_id)->delete();
    //                 foreach ($features as $key => $value) {
    //                     $carHostVehicleFeature = new CarHostVehicleFeature();
    //                     $carHostVehicleFeature->vehicles_id = $request->vehicle_id;
    //                     $carHostVehicleFeature->feature_id = $value;
    //                     $carHostVehicleFeature->save();
    //                 }
    //                 $newVehicleFeatureIdsArr = CarHostVehicleFeature::where('vehicle_id', $request->vehicle_id)->pluck('feature_id')->toArray();
    //                 $newVehicleFeature = $newVehicleFeatureIdsArr;
    //             }  
    //         }else{
    //             $vehicleFeatureIdsArr = VehicleFeatureMapping::where('vehicle_id', $request->vehicle_id)->pluck('feature_id')->toArray();
    //             $oldVehicleFeature = $vehicleFeatureIdsArr;
    //             $features = explode(',', $request->features);
    //             if(isset($features) && is_countable($features) && count($features) > 0)
    //             {   
    //                 $vehicleFeatureIds = VehicleFeatureMapping::where('vehicle_id', $request->vehicle_id)->delete();
    //                 foreach ($features as $key => $value) {
    //                     $vFeatureMapping = new VehicleFeatureMapping();
    //                     $vFeatureMapping->vehicle_id = $request->vehicle_id;
    //                     $vFeatureMapping->feature_id = $value;
    //                     $vFeatureMapping->save();
    //                 }
    //             }
    //             $newVehicleFeatureIdsArr = VehicleFeatureMapping::where('vehicle_id', $request->vehicle_id)->pluck('feature_id')->toArray();
    //             $newVehicleFeature = $newVehicleFeatureIdsArr;
    //         }
 
    //     } catch (\Exception $e) {}        
        
    //     if($isCarHostVehicle){
    //         if(is_countable($request->file('regular_images')) && count($request->file('regular_images')) > 0){
    //             $carHostVehicleImage = CarHostVehicleImage::where(['vehicles_id' => $request->vehicle_id, 'image_type' => 2])->get(); //image_type = 2 means Vehicle Interior images
    //             if(is_countable($carHostVehicleImage) && count($carHostVehicleImage) > 0){
    //                 $this->unlinkImagesArr($carHostVehicleImage);
    //             }
    //             foreach ($request->file('regular_images') as $key => $image) {
    //                 $filename = 'Interior_'.$request->vehicle_id.'_'.$key.'_'.time() . '.' . $image->getClientOriginalExtension();
    //                 $image->move(public_path('images/car_host'), $filename);
    //                 $carHostVehicleImage = new CarHostVehicleImage();
    //                 $carHostVehicleImage->vehicles_id = $request->vehicle_id;
    //                 $carHostVehicleImage->image_type = 2;
    //                 $carHostVehicleImage->vehicle_img = $filename;
    //                 $carHostVehicleImage->save();
    //             }
    //         }
    //         if(is_countable($request->file('banner_images')) && count($request->file('banner_images')) > 0){
    //             $carHostVehicleImage = CarHostVehicleImage::where(['vehicles_id' => $request->vehicle_id, 'image_type' => 3])->get(); //image_type = 3 means Vehicle Exterior images
    //             if(is_countable($carHostVehicleImage) && count($carHostVehicleImage) > 0){
    //                 $this->unlinkImagesArr($carHostVehicleImage);
    //             }
    //             foreach ($request->file('banner_images') as $key => $image) {
    //                 $filename = 'Exterior_'.$request->vehicle_id.'_'.$key.'_'.time() . '.' . $image->getClientOriginalExtension();   
    //                 $image->move(public_path('images/car_host'), $filename);
    //                 $carHostVehicleImage = new CarHostVehicleImage();
    //                 $carHostVehicleImage->vehicles_id = $request->vehicle_id;
    //                 $carHostVehicleImage->image_type = 3;
    //                 $carHostVehicleImage->vehicle_img = $filename;
    //                 $carHostVehicleImage->save();
    //             }
    //         }  
    //     }else{
    //         try{
    //             if(isset($request->cutout_image) && $request->cutout_image != '')
    //             {
    //                 $file = $request->cutout_image;
    //                 $extension = $file->getClientOriginalExtension();
    //                 $filename = 'cutout_img_'.time() . '_' . uniqid() . '.' . $extension; // Append a unique identifier to the filename
    //                 $file->move(public_path('images/vehicle_images/'), $filename);
    //                 $vehicleImg = VehicleImage::where('vehicle_id', $request->vehicle_id)->where('image_type', 'cutout')->first();
    //                 if($vehicleImg != ''){
    //                     $parsedUrl = parse_url($vehicleImg->image_url);
    //                     $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : ''; 
    //                     $path = public_path($path);
    //                     if (file_exists($path)){
    //                         unlink($path);
    //                     }    
    //                 }else{
    //                     $vehicleImg = new VehicleImage();
    //                     $vehicleImg->vehicle_id = $request->vehicle_id;
    //                     $vehicleImg->image_type = 'cutout';
    //                 }
    //                 $vehicleImg->image_url = $filename;
    //                 $vehicleImg->save();
    //             }
    //         } catch (\Exception $e) {} 
    //         // EXISTING BANNER IMAGE COMPARISION
    //         $oldBannerImages = json_decode($request->old_banner_images, true);
    //         if(isset($oldBannerImages) && is_countable($oldBannerImages) && count($oldBannerImages) > 0){
    //             $vehicleImgs = VehicleImage::where('vehicle_id', $request->vehicle_id)->where('image_type', 'banner')->get();
    //             foreach ($vehicleImgs as $k => $v) {
    //                 if(!in_array($v->image_url, $oldBannerImages)){
    //                     $parsedUrl = parse_url($v->image_url);
    //                     $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : ''; 
    //                     $path = public_path($path);
    //                     if (file_exists($path)){
    //                         unlink($path);
    //                     }
    //                     $v->delete();
    //                 }
    //             }
    //         }else{
    //             $vehicleImgs = VehicleImage::where('vehicle_id', $request->vehicle_id)->where('image_type', 'banner')->get();
    //             foreach ($vehicleImgs as $k => $v) {
    //                 $parsedUrl = parse_url($v->image_url);
    //                 $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : ''; 
    //                 $path = public_path($path);
    //                 if (file_exists($path)){
    //                     unlink($path);
    //                 }
    //                 $v->delete();
    //             }
    //         }
    //         // NEW BANNER IMAGIES
    //         try{
    //             if(isset($request->banner_images) && is_countable($request->banner_images) && count($request->banner_images) > 0){
                   
    //                 // ADD NEW BANNER IMAGIES
    //                 foreach($request->banner_images as $key => $val){
    //                     $extension = $val->getClientOriginalExtension();
    //                     $filename = 'banner_img_'.time() . '_' . uniqid() . '.' . $extension;
    //                     $val->move(public_path('images/vehicle_images/'), $filename);
    //                     $vehicleImg = new VehicleImage();
    //                     $vehicleImg->vehicle_id = $request->vehicle_id;
    //                     $vehicleImg->image_type = 'banner';
    //                     $vehicleImg->image_url = $filename;
    //                     $vehicleImg->save();
    //                 }
    //             }
    //         } catch (\Exception $e) {} 
    //         // EXISTING REGULAR IMAGE COMPARISION
    //         $oldRegularImages = json_decode($request->old_regular_images, true);
    //         if(isset($oldRegularImages) && is_countable($oldRegularImages) && count($oldRegularImages) > 0){
    //             $vehicleImgs = VehicleImage::where('vehicle_id', $request->vehicle_id)->where('image_type', 'regular')->get();
    //             foreach ($vehicleImgs as $k => $v) {
    //                 if(!in_array($v->image_url, $oldRegularImages)){
    //                     $parsedUrl = parse_url($v->image_url);
    //                     $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : ''; 
    //                     $path = public_path($path);
    //                     if (file_exists($path)){
    //                         unlink($path);
    //                     }
    //                     $v->delete();
    //                 }
    //             }
    //         }else{
    //             $vehicleImgs = VehicleImage::where('vehicle_id', $request->vehicle_id)->where('image_type', 'regular')->get();
    //             foreach ($vehicleImgs as $k => $v) {
    //                 $parsedUrl = parse_url($v->image_url);
    //                 $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : ''; 
    //                 $path = public_path($path);
    //                 if (file_exists($path)){
    //                     unlink($path);
    //                 }
    //                 $v->delete();
    //             }
    //         } 
    //         // NEW REGULAR IMAGIES
    //         try{
    //             if(isset($request->regular_images) && is_countable($request->regular_images) && count($request->regular_images) > 0){
                    
    //                 // ADD NEW REGULAR IMAGIES
    //                 foreach($request->regular_images as $key => $val){
    //                     $extension = $val->getClientOriginalExtension();
    //                     $filename = 'regular_img_'.time() . '_' . uniqid() . '.' . $extension;
    //                     $val->move(public_path('images/vehicle_images/'), $filename);
    //                     $vehicleImg = new VehicleImage();
    //                     $vehicleImg->vehicle_id = $request->vehicle_id;
    //                     $vehicleImg->image_type = 'regular';
    //                     $vehicleImg->image_url = $filename;
    //                     $vehicleImg->save();
    //                 }
    //             }  
    //         } catch (\Exception $e) {}
    //     }
         
    //     $newVehicleDoc = VehicleDocument::where('vehicle_id', $request->vehicle_id)->get();
    //     //ADD AVAILABILITY CALANDER
    //     $calNewArray = [];
    //     $availabilityCalander = json_decode($request->availability_calendar, true);
    //     if(isset($availabilityCalander) && is_countable($availabilityCalander) && count($availabilityCalander) > 0){
    //         foreach ($availabilityCalander as $key => $value) {
    //             if(isset($value) && isset($value['start_date'])){
    //                 $calNewArray[$key]['start_date'] = isset($value['start_date'])?date('d-m-Y H:i A', strtotime($value['start_date'])):'';
    //                 $calNewArray[$key]['end_date'] = isset($value['end_date'])?date('d-m-Y H:i A', strtotime($value['end_date'])):'';
    //                 $calNewArray[$key]['reason'] = isset($value['reason'])?$value['reason']:'';
    //             }
    //         }
    //     }
    //     $Vehicle = Vehicle::where('vehicle_id', $request->vehicle_id)->first();
    //     $Vehicle->availability_calendar = json_encode($calNewArray);
    //     $Vehicle->save();
    //     $newVehicleAvailabilityDates = $Vehicle->availability_calendar;

    //     $oldArr = [
    //         'vehicle' => $oldVehicle, 
    //         'vehicleProperty' => $oldVehicleProperty, 
    //         'vehicleDoc' => $oldVehicleDoc, 
    //         'vehicleFeature' => $oldVehicleFeature, 
    //         'vehicleAvailabilityDates' => $oldVehicleAvailabilityDates
    //     ];
    //     $newArr = [
    //         'vehicle' => $newVehicle, 
    //         'vehicleProperty' => $newVehicleProperty, 
    //         'vehicleDoc' => $newVehicleDoc, 
    //         'vehicleFeature' => $newVehicleFeature, 
    //         'vehicleAvailabilityDates' => $newVehicleAvailabilityDates
    //     ];

    //     logAdminActivities('Vehicle Updation', $oldArr, $newArr);
        
    //     return $this->successResponse($Vehicle, 'Vehicle Updated Successfully');
    // }

    public function unlinkImages($carHostVehicleImage){
        $filePath = public_path().'/images/car_host/'.basename($carHostVehicleImage->vehicle_img);
        if(file_exists($filePath)){
            unlink($filePath);
        }
        $carHostVehicleImage->delete();
    }

    protected function unlinkImagesArr($carHostVehicleImages){
        foreach($carHostVehicleImages as $k => $v){
            $filePath = public_path().'/images/car_host/'.basename($v->vehicle_img);
            if(file_exists($filePath)){
                unlink($filePath);
            }
            $v->delete();
        }
    }

    public function setPriceStatus(Request $request){
        $validator = Validator::make($request->all(), [
            'price_id' => 'required|exists:vehicle_price_details,id',
            'status' => 'required|in:0,1',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $oldVal = $newVal = '';
        $vehiclePriceDetail = VehiclePriceDetail::where('id', $request->price_id)->first();
        $oldVal = clone $vehiclePriceDetail;
        $vehiclePriceDetail->is_show = $request->status;
        $vehiclePriceDetail->save();
        $newVal = $vehiclePriceDetail;

        logAdminActivities('Vehicle Price Status Updation', $oldVal, $newVal);


        return $this->successResponse($vehiclePriceDetail, 'Vehicle Price status set Successfully');   
    }

    public function deleteVehicle(Request $request){
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $vehicle = Vehicle::where('vehicle_id', $request->vehicle_id)->first();
        $vehicle->is_deleted = 1;
        $vehicle->save();
        CarHostVehicleFeatureTemp::where('vehicles_id', $request->vehicle_id)->delete();
        CarHostVehicleImageTemp::where('vehicles_id', $request->vehicle_id)->delete();
        VehicleDocumentTemp::where('vehicle_id', $request->vehicle_id)->delete();
        VehiclePriceDetailTemp::where('vehicle_id', $request->vehicle_id)->delete();
        
        logAdminActivities('Vehicle Deletion', $vehicle);
        return $this->successResponse($vehicle, 'Vehicle Deleted Successfully');    
    }

}
