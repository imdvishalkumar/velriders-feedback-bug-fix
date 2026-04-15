<?php

namespace App\Http\Controllers\AdminApis\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Vehicle, TripAmountCalculationRule, City, CarHostPickupLocation, CarHost, VehiclePriceDetail, CarHostBank, CarEligibility, CarHostVehicleImageTemp, CarHostVehicleFeatureTemp, CarHostPickupLocationTemp, CarHostVehicleFeature, CarHostVehicleImage, VehicleModel, VehicleDocumentTemp, VehicleDocument, VehiclePriceDetailTemp};
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class CarHostManagementController extends Controller
{
    public function getCarHosts(Request $request){
        $page = $request->input('page');
        $pageSize = $request->input('page_size');
        $orderColumn = $request->order_column ?? '';
        $orderType = $request->order_type ?? '';
        $orderTypes = config('global_values.order_types');
        $orderTypes = implode(',', $orderTypes);
        $search = $request->search ?? '';
        $validator = Validator::make($request->all(), [
            'host_id' => 'nullable|exists:car_hosts,id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $carHosts = CarHost::select('id', 'country_code', 'mobile_number', 'email', 'firstname', 'lastname', 'pan_number', 'dob', 'profile_picture_url', 'created_at', 'is_blocked', 'gst_number', 'business_name')->where('is_deleted', 0);
        
        if(isset($request->host_id) && $request->host_id != NULL){
            $carHosts = $carHosts->where('id', $request->host_id)->first();
            if($carHosts){
                $isHostNewUpdatedChanges = isHostNewUpdatedChanges($carHosts->id);
                $carHosts->is_host_updated_features = $isHostNewUpdatedChanges['newFeatureChanges'];
                $carHosts->is_host_updated_images = $isHostNewUpdatedChanges['newImageChanges'];
                $carHosts->is_host_updated_locations = $isHostNewUpdatedChanges['newLocationChanges'];
                return $this->successResponse($carHosts, 'Carhost get Successfully');
            }else{
                return $this->errorResponse('Carhost are not Found');
            }
        }
        if(isset($search) && $search != ''){
            $checkHost = CarHost::where('id', (int)$search)->exists();
            if($checkHost){
                $carHosts = $carHosts->where('id', $search);
            }
            else{
                $carHosts = $carHosts->where(function ($query) use ($search) {
                    $query->whereRaw('LOWER(country_code) LIKE LOWER(?)', ["%$search%"])
                        ->orWhereRaw('LOWER(mobile_number) LIKE LOWER(?)', ["%$search%"])
                        ->orWhereRaw('LOWER(email) LIKE LOWER(?)', ["%$search%"])
                        ->orWhereRaw('LOWER(firstname) LIKE LOWER(?)', ["%$search%"])
                        ->orWhereRaw('LOWER(lastname) LIKE LOWER(?)', ["%$search%"])
                        ->orWhereRaw('CONCAT(firstname, " ", lastname) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('CONCAT(lastname, " ", firstname) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(pan_number) LIKE LOWER(?)', ["%$search%"])
                        // Search by license plate through car_eligibilities and vehicles
                        ->orWhereExists(function ($subQuery) use ($search) {
                            $subQuery->select(DB::raw(1))
                                ->from('car_eligibilities')
                                ->join('vehicles', 'car_eligibilities.vehicle_id', '=', 'vehicles.vehicle_id')
                                ->whereColumn('car_eligibilities.car_hosts_id', 'car_hosts.id')
                                ->where('vehicles.is_deleted', 0)
                                ->whereRaw('LOWER(vehicles.license_plate) LIKE LOWER(?)', ["%$search%"]);
                        });
                        // Check if search input is a valid date format (DD-MM-YYYY)
                        if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $search)) {
                            try {
                                $dobFormatted = \Carbon\Carbon::createFromFormat('d-m-Y', $search)->format('Y-m-d');
                                $query->orWhereDate('dob', $dobFormatted);
                            } catch (\Exception $e) {}
                        }
                        if(strtolower($search) == 'bloked'){
                            $query->orWhere('is_blocked', 1);
                        }elseif(strtolower($search) == 'active'){
                            $query->orWhere('is_blocked', 0);
                        }
                });
            }
        }
        if($orderColumn != '' && $orderType != ''){
            $carHosts = $carHosts->orderBy($orderColumn, $orderType);
        }
        
        if ($page !== null && $pageSize !== null) {
            $carHosts = $carHosts->paginate($pageSize, ['*'], 'page', $page);
            if(isset($carHosts) && is_countable($carHosts) && count($carHosts) > 0){
                foreach($carHosts as $k => $v){
                    $hostStatus = $this->getCustomerStatus($v);
                    $v->status = $hostStatus;
                    $isHostNewUpdatedChanges = isHostNewUpdatedChanges($v->id);
                    $v->is_host_updated_features = $isHostNewUpdatedChanges['newFeatureChanges'];
                    $v->is_host_updated_images = $isHostNewUpdatedChanges['newImageChanges'];
                    $v->is_host_updated_locations = $isHostNewUpdatedChanges['newLocationChanges'];
                }
            }
            $decodedHosts = json_decode(json_encode($carHosts->getCollection()->values()), FALSE);
            return $this->successResponse([
                'carHosts' => $decodedHosts,
                'pagination' => [
                    'total' => $carHosts->total(),
                    'per_page' => $carHosts->perPage(),
                    'current_page' => $carHosts->currentPage(),
                    'last_page' => $carHosts->lastPage(),
                    'from' => ($carHosts->currentPage() - 1) * $carHosts->perPage() + 1,
                    'to' => min($carHosts->currentPage() * $carHosts->perPage(), $carHosts->total()),
                ]], 'Car Hosts fetched successfully');
        }else{
            $carHosts = $carHosts->get();
            if(isset($carHosts) && is_countable($carHosts) && count($carHosts) > 0){
                foreach($carHosts as $k => $v){
                    $hostStatus = $this->getCustomerStatus($v);
                    $v->status = $hostStatus;
                    $isHostNewUpdatedChanges = isHostNewUpdatedChanges($v->id);
                    $v->is_host_updated_features = $isHostNewUpdatedChanges['newFeatureChanges'];
                    $v->is_host_updated_images = $isHostNewUpdatedChanges['newImageChanges'];
                    $v->is_host_updated_locations = $isHostNewUpdatedChanges['newLocationChanges'];
                }
            }
            $carHosts = [
                'carHosts' => $carHosts,
            ];
            if(isset($carHosts) && is_countable($carHosts) && count($carHosts) > 0){
                return $this->successResponse($carHosts, 'Car Hosts fetched successfully');
            }else{
                return $this->errorResponse('Car Hosts not found');
            }
        }
    }

    public function getCarHostChanges(Request $request){
        $page = $request->input('page');
        $pageSize = $request->input('page_size');
        $data = [];

        $carHostPickupLocationTemp = CarHostPickupLocationTemp::join('car_hosts as h', 'car_host_pickup_location_temps.car_hosts_id', '=', 'h.id')->select('car_host_pickup_location_temps.*','h.firstname','h.lastname','h.mobile_number','h.email');
        $carHostVehicleFeatureTemp = DB::table('car_host_vehicle_feature_temps as f')
            ->join('car_eligibilities as v', 'v.vehicle_id', '=', 'f.vehicles_id')
            ->join('car_hosts as h', 'h.id', '=', 'v.car_hosts_id')
            ->join('vehicle_features as fea', 'fea.feature_id', '=', 'f.feature_id')
            ->select(
                'f.vehicles_id',
                DB::raw('GROUP_CONCAT(fea.name) as features'),
                DB::raw('MIN(h.id) as host_id'),
                DB::raw('MIN(h.firstname) as firstname'),
                DB::raw('MIN(h.lastname) as lastname'),
                DB::raw('MIN(h.email) as email'),
                DB::raw('MIN(h.mobile_number) as mobile_number'),
                DB::raw('MAX(f.created_at) as latest_created_at')
            )->groupBy('f.vehicles_id');
        $carHostVehiclesImageTemp = DB::table('car_host_vehicle_image_temps as img')
            ->join('car_eligibilities as v', 'v.vehicle_id', '=', 'img.vehicles_id')
            ->join('car_hosts as h', 'h.id', '=', 'v.car_hosts_id')
            ->select(
                'img.vehicles_id',
                DB::raw('MAX(h.id) as host_id'),
                DB::raw('MAX(h.firstname) as firstname'),
                DB::raw('MAX(h.lastname) as lastname'),
                DB::raw('MAX(h.email) as email'),
                DB::raw('MAX(h.mobile_number) as mobile_number'),
                DB::raw('MAX(img.created_at) as latest_created_at')
            )->groupBy('img.vehicles_id');
        $carHostVehicleDetails = Vehicle::
            join('car_eligibilities as v', 'v.vehicle_id', '=', 'vehicles.vehicle_id')
            ->join('car_hosts as h', 'h.id', '=', 'v.car_hosts_id')
            ->select('vehicles.*', 'h.id as host_id', 'h.firstname', 'h.lastname', 'h.mobile_number', 'h.email')->where('is_host_updated', 1);
        $carHostVehicleDocuments = VehicleDocumentTemp::with('vehicle')
            ->join('car_eligibilities as v', 'v.vehicle_id', '=', 'vehicle_document_temps.vehicle_id')
            ->join('car_hosts as h', 'h.id', '=', 'v.car_hosts_id') 
            ->select('vehicle_document_temps.*', 'h.id as host_id', 'h.firstname', 'h.lastname', 'h.mobile_number', 'h.email');
        $VehiclePriceDetailTemp = VehiclePriceDetailTemp::
            join('car_eligibilities as v', 'v.vehicle_id', '=', 'vehicle_price_detail_temps.vehicle_id')
            ->join('car_hosts as h', 'h.id', '=', 'v.car_hosts_id')
            ->select('vehicle_price_detail_temps.*', 'h.id as host_id', 'h.firstname', 'h.lastname', 'h.mobile_number', 'h.email');

        if ($page !== null && $pageSize !== null) {
            $carHostPickupLocationTemp = $carHostPickupLocationTemp->orderBy('car_host_pickup_location_temps.created_at', 'DESC')->paginate($pageSize, ['*'], 'page', $page);
            $carHostPickupLocationTempArray = json_decode(json_encode($carHostPickupLocationTemp->getCollection()->values()), FALSE);
            $carHostPickupLocationTemp = [
                'carHostPickupLocations' => $carHostPickupLocationTempArray,
                'pagination' => [
                    'total' => $carHostPickupLocationTemp->total(),
                    'per_page' => $carHostPickupLocationTemp->perPage(),
                    'current_page' => $carHostPickupLocationTemp->currentPage(),
                    'last_page' => $carHostPickupLocationTemp->lastPage(),
                    'from' => ($carHostPickupLocationTemp->currentPage() - 1) * $carHostPickupLocationTemp->perPage() + 1,
                    'to' => min($carHostPickupLocationTemp->currentPage() * $carHostPickupLocationTemp->perPage(), $carHostPickupLocationTemp->total()),
                ]
            ];

            $carHostVehicleFeatureTemp = $carHostVehicleFeatureTemp->orderBy('latest_created_at', 'DESC')->paginate($pageSize, ['*'], 'page', $page);
            if(isset($carHostVehicleFeatureTemp) && is_countable($carHostVehicleFeatureTemp) && count($carHostVehicleFeatureTemp) > 0){
                foreach($carHostVehicleFeatureTemp as $k => $v){
                    $vehicle = Vehicle::where('vehicle_id', $v->vehicles_id)->first();
                    $vehicle->makeHidden('branch_id', 'year', 'description', 'availability', 'is_deleted', 'extra_km_rate', 'extra_hour_rate', 'availability_calendar', 'commission_percent', 'chassis_no', 'created_at', 'updated_at', 'nick_name', 'banner_image', 'banner_images', 'regular_images', 'host_banner_images', 'host_regular_images', 'rating', 'total_rating', 'trip_count', 'model');
                    $v->vehicle = $vehicle;
                }
            }
            $carHostVehicleFeatureTempArray = json_decode(json_encode($carHostVehicleFeatureTemp->getCollection()->values()), FALSE);
            $carHostVehicleFeatureTemp = [
                'carHostVehicleFeatures' => $carHostVehicleFeatureTempArray,
                'pagination' => [
                    'total' => $carHostVehicleFeatureTemp->total(),
                    'per_page' => $carHostVehicleFeatureTemp->perPage(),
                    'current_page' => $carHostVehicleFeatureTemp->currentPage(),
                    'last_page' => $carHostVehicleFeatureTemp->lastPage(),
                    'from' => ($carHostVehicleFeatureTemp->currentPage() - 1) * $carHostVehicleFeatureTemp->perPage() + 1,
                    'to' => min($carHostVehicleFeatureTemp->currentPage() * $carHostVehicleFeatureTemp->perPage(), $carHostVehicleFeatureTemp->total()),
                ]
            ];

            $carHostVehiclesImageTemp = $carHostVehiclesImageTemp->orderByDesc('latest_created_at')->paginate($pageSize, ['*'], 'page', $page);
            if(isset($carHostVehiclesImageTemp) && is_countable($carHostVehiclesImageTemp) && count($carHostVehiclesImageTemp) > 0){
                foreach($carHostVehiclesImageTemp as $k => $v){
                    $vehicle = Vehicle::where('vehicle_id', $v->vehicles_id)->first();
                    $vehicle->makeHidden('branch_id', 'year', 'description', 'availability', 'is_deleted', 'extra_km_rate', 'extra_hour_rate', 'availability_calendar', 'commission_percent', 'chassis_no', 'created_at', 'updated_at', 'nick_name', 'banner_image', 'banner_images', 'regular_images', 'host_banner_images', 'host_regular_images', 'rating', 'total_rating', 'trip_count', 'model');
                    $v->vehicle = $vehicle;
                }
            }
            $carHostVehiclesImageTempArray = json_decode(json_encode($carHostVehiclesImageTemp->getCollection()->values()), FALSE);
            $carHostVehiclesImageTemp = [
                'carHostVehicleImages' => $carHostVehiclesImageTempArray,
                'pagination' => [
                    'total' => $carHostVehiclesImageTemp->total(),
                    'per_page' => $carHostVehiclesImageTemp->perPage(),
                    'current_page' => $carHostVehiclesImageTemp->currentPage(),
                    'last_page' => $carHostVehiclesImageTemp->lastPage(),
                    'from' => ($carHostVehiclesImageTemp->currentPage() - 1) * $carHostVehiclesImageTemp->perPage() + 1,
                    'to' => min($carHostVehiclesImageTemp->currentPage() * $carHostVehiclesImageTemp->perPage(), $carHostVehiclesImageTemp->total()),
                ]
            ];

            $carHostVehicleDetails = $carHostVehicleDetails->orderBy('updated_at', 'DESC')->paginate($pageSize, ['*'], 'page', $page);
            if(isset($carHostVehicleDetails) && is_countable($carHostVehicleDetails) && count($carHostVehicleDetails) > 0){
                foreach($carHostVehicleDetails as $k => $v){
                    $v->makeHidden('branch_id', 'description', 'availability', 'is_deleted', 'extra_km_rate', 'extra_hour_rate', 'availability_calendar', 'commission_percent', 'chassis_no', 'created_at', 'updated_at', 'nick_name', 'banner_image', 'banner_images', 'regular_images', 'host_banner_images', 'host_regular_images', 'rating', 'total_rating', 'trip_count', 'model', 'color', 'host_step_count', 'license_plate', 'rental_price', 'publish', 'vehicle_created_by', 'apply_for_publish', 'temp_city_id', 'deposit_amount','is_deposit_amount_show', 'step_cnt', 'category_name', 'cutout_image', 'location', 'city_name', 'city_id');
                    $vehicle = Vehicle::where('vehicle_id', $v->vehicle_id)->first();
                    $vehicle->makeHidden('branch_id', 'year', 'description', 'availability', 'is_deleted', 'extra_km_rate', 'extra_hour_rate', 'availability_calendar', 'commission_percent', 'chassis_no', 'created_at', 'updated_at', 'nick_name', 'banner_image', 'banner_images', 'regular_images', 'host_banner_images', 'host_regular_images', 'rating', 'total_rating', 'trip_count', 'model');
                    $v->vehicle = $vehicle;
                }
            }
            $carHostVehicleDetailsArray = json_decode(json_encode($carHostVehicleDetails->getCollection()->values()), FALSE);
            $carHostVehicleDetails = [
                'carHostVehicleDetails' => $carHostVehicleDetailsArray,
                'pagination' => [
                    'total' => $carHostVehicleDetails->total(),
                    'per_page' => $carHostVehicleDetails->perPage(),
                    'current_page' => $carHostVehicleDetails->currentPage(),
                    'last_page' => $carHostVehicleDetails->lastPage(),
                    'from' => ($carHostVehicleDetails->currentPage() - 1) * $carHostVehicleDetails->perPage() + 1,
                    'to' => min($carHostVehicleDetails->currentPage() * $carHostVehicleDetails->perPage(), $carHostVehicleDetails->total()),
                ]
            ];

            $carHostVehicleDocuments = $carHostVehicleDocuments->orderBy('vehicle_document_temps.created_at', 'DESC')->paginate($pageSize, ['*'], 'page', $page);
            if(isset($carHostVehicleDocuments) && is_countable($carHostVehicleDocuments) && count($carHostVehicleDocuments) > 0){
                foreach($carHostVehicleDocuments as $k => $v){
                    $v->document_type = ucwords(str_replace('_', ' ', $v->document_type));
                    $v->makeHidden('is_approved', 'approved_by', 'image_type', 'created_at', 'updated_at');
                    $v->vehicle->makeHidden('branch_id', 'year', 'description', 'availability', 'is_deleted', 'extra_km_rate', 'extra_hour_rate', 'availability_calendar', 'commission_percent', 'chassis_no', 'created_at', 'updated_at', 'nick_name', 'banner_image', 'banner_images', 'regular_images', 'host_banner_images', 'host_regular_images', 'rating', 'total_rating', 'trip_count', 'model');
                    $v->vehicle = $v->vehicle;
                }
            }
            $carHostVehicleDocumentsArray = json_decode(json_encode($carHostVehicleDocuments->getCollection()->values()), FALSE);
            $carHostVehicleDocuments = [
                'carHostVehicleDocuments' => $carHostVehicleDocumentsArray,
                'pagination' => [
                    'total' => $carHostVehicleDocuments->total(),
                    'per_page' => $carHostVehicleDocuments->perPage(),
                    'current_page' => $carHostVehicleDocuments->currentPage(),
                    'last_page' => $carHostVehicleDocuments->lastPage(),
                    'from' => ($carHostVehicleDocuments->currentPage() - 1) * $carHostVehicleDocuments->perPage() + 1,
                    'to' => min($carHostVehicleDocuments->currentPage() * $carHostVehicleDocuments->perPage(), $carHostVehicleDocuments->total()),
                ]
            ];
            $VehiclePriceDetailTemp = $VehiclePriceDetailTemp->orderBy('created_at','desc')->get()->unique('vehicle_id')->values();
            if(isset($VehiclePriceDetailTemp) && is_countable($VehiclePriceDetailTemp) && count($VehiclePriceDetailTemp) > 0){
                foreach($VehiclePriceDetailTemp as $k => $v){
                    $v->vehicle->makeHidden('branch_id', 'year', 'description', 'availability', 'is_deleted', 'extra_km_rate', 'extra_hour_rate', 'availability_calendar', 'commission_percent', 'chassis_no', 'created_at', 'updated_at', 'nick_name', 'banner_image', 'banner_images', 'regular_images', 'host_banner_images', 'host_regular_images', 'rating', 'total_rating', 'trip_count', 'model');
                    $v->vehicle = $v->vehicle;
                }
            }
            $VehiclePriceDetailTemp = [
                'carHostVehiclePriceDetails' => $VehiclePriceDetailTemp,
            ];
        }else{
            $carHostPickupLocationTemp = $carHostPickupLocationTemp->orderBy('car_host_pickup_location_temps.created_at', 'DESC')->get();
            $carHostPickupLocationTemp = [
                'carHostPickupLocations' => $carHostPickupLocationTemp,
            ];
            $carHostVehicleFeatureTemp = $carHostVehicleFeatureTemp->orderBy('latest_created_at', 'DESC')->get();
            if(isset($carHostVehicleFeatureTemp) && is_countable($carHostVehicleFeatureTemp) && count($carHostVehicleFeatureTemp) > 0){
                foreach($carHostVehicleFeatureTemp as $k => $v){
                    $vehicle = Vehicle::where('vehicle_id', $v->vehicles_id)->first();
                    $vehicle->makeHidden('branch_id', 'year', 'description', 'availability', 'is_deleted', 'extra_km_rate', 'extra_hour_rate', 'availability_calendar', 'commission_percent', 'chassis_no', 'created_at', 'updated_at', 'nick_name', 'banner_image', 'banner_images', 'regular_images', 'host_banner_images', 'host_regular_images', 'rating', 'total_rating', 'trip_count', 'model');
                    $v->vehicle = $vehicle;
                }
            }
            $carHostVehicleFeatureTemp = [
                'carHostVehicleFeatures' => $carHostVehicleFeatureTemp,
            ];
            $carHostVehiclesImageTemp = $carHostVehiclesImageTemp->orderByDesc('latest_created_at')->get();
            if(isset($carHostVehiclesImageTemp) && is_countable($carHostVehiclesImageTemp) && count($carHostVehiclesImageTemp) > 0){
                foreach($carHostVehiclesImageTemp as $k => $v){
                    $vehicle = Vehicle::where('vehicle_id', $v->vehicles_id)->first();
                    $vehicle->makeHidden('branch_id', 'year', 'description', 'availability', 'is_deleted', 'extra_km_rate', 'extra_hour_rate', 'availability_calendar', 'commission_percent', 'chassis_no', 'created_at', 'updated_at', 'nick_name', 'banner_image', 'banner_images', 'regular_images', 'host_banner_images', 'host_regular_images', 'rating', 'total_rating', 'trip_count', 'model');
                    $v->vehicle = $vehicle;
                }
            }
            $carHostVehiclesImageTemp = [
                'carHostVehicleImages' => $carHostVehiclesImageTemp,
            ];

            $carHostVehicleDetails = $carHostVehicleDetails->orderBy('updated_at', 'DESC')->get();
            if(isset($carHostVehicleDetails) && is_countable($carHostVehicleDetails) && count($carHostVehicleDetails) > 0){
                foreach($carHostVehicleDetails as $k => $v){
                    $v->makeHidden('branch_id', 'description', 'availability', 'is_deleted', 'extra_km_rate', 'extra_hour_rate', 'availability_calendar', 'commission_percent', 'chassis_no', 'created_at', 'updated_at', 'nick_name', 'banner_image', 'banner_images', 'regular_images', 'host_banner_images', 'host_regular_images', 'rating', 'total_rating', 'trip_count', 'model', 'color', 'host_step_count', 'license_plate', 'rental_price', 'publish', 'vehicle_created_by', 'apply_for_publish', 'temp_city_id', 'deposit_amount','is_deposit_amount_show', 'step_cnt', 'category_name', 'cutout_image', 'location', 'city_name', 'city_id');
                    $vehicle = Vehicle::where('vehicle_id', $v->vehicle_id)->first();
                    $vehicle->makeHidden('branch_id', 'year', 'description', 'availability', 'is_deleted', 'extra_km_rate', 'extra_hour_rate', 'availability_calendar', 'commission_percent', 'chassis_no', 'created_at', 'updated_at', 'nick_name', 'banner_image', 'banner_images', 'regular_images', 'host_banner_images', 'host_regular_images', 'rating', 'total_rating', 'trip_count', 'model');
                    $v->vehicle = $vehicle;
                }
            }
            $carHostVehicleDetails = [
                'carHostVehicleDetails' => $carHostVehicleDetails,
            ];

            $carHostVehicleDocuments = $carHostVehicleDocuments->orderBy('vehicle_document_temps.created_at', 'DESC')->get()->values();
            if(isset($carHostVehicleDocuments) && is_countable($carHostVehicleDocuments) && count($carHostVehicleDocuments) > 0){
                foreach($carHostVehicleDocuments as $k => $v){
                    $v->document_type = ucwords(str_replace('_', ' ', $v->document_type));
                    $v->makeHidden('is_approved', 'approved_by', 'image_type', 'created_at', 'updated_at');
                    $v->vehicle->makeHidden('branch_id', 'year', 'description', 'availability', 'is_deleted', 'extra_km_rate', 'extra_hour_rate', 'availability_calendar', 'commission_percent', 'chassis_no', 'created_at', 'updated_at', 'nick_name', 'banner_image', 'banner_images', 'regular_images', 'host_banner_images', 'host_regular_images', 'rating', 'total_rating', 'trip_count', 'model');
                    $v->vehicle = $v->vehicle;
                }
            }
            $carHostVehicleDocuments = [
                'carHostVehicleDocuments' => $carHostVehicleDocuments,
            ];

            $VehiclePriceDetailTemp = $VehiclePriceDetailTemp->orderBy('created_at','desc')->get()->unique('rental_price')->values();
            if(isset($VehiclePriceDetailTemp) && is_countable($VehiclePriceDetailTemp) && count($VehiclePriceDetailTemp) > 0){
                foreach($VehiclePriceDetailTemp as $k => $v){
                    $v->vehicle->makeHidden('branch_id', 'year', 'description', 'availability', 'is_deleted', 'extra_km_rate', 'extra_hour_rate', 'availability_calendar', 'commission_percent', 'chassis_no', 'created_at', 'updated_at', 'nick_name', 'banner_image', 'banner_images', 'regular_images', 'host_banner_images', 'host_regular_images', 'rating', 'total_rating', 'trip_count', 'model');
                    $v->vehicle = $v->vehicle;
                }
            }
            $VehiclePriceDetailTemp = [
                'carHostVehiclePriceDetails' => $VehiclePriceDetailTemp,
            ];
        }
        $data['pickup_locations'] = $carHostPickupLocationTemp;
        $data['features'] = $carHostVehicleFeatureTemp;
        $data['images'] = $carHostVehiclesImageTemp;
        $data['vehicle_details'] = $carHostVehicleDetails;
        $data['carHostVehicleDocuments'] = $carHostVehicleDocuments;
        $data['Price_details'] = $VehiclePriceDetailTemp;
      
        return $this->successResponse($data, 'Host data get successfully');
    }

    public function getUnpublishVehices(Request $request){
        $page = $request->input('page');
        $pageSize = $request->input('page_size');

        $vehicle = Vehicle::select('vehicle_id', 'model_id', 'year', 'description', 'color', 'license_plate', 'rental_price', 'apply_for_publish', 'publish')->where('publish', 0)->where('apply_for_publish', 1)->where('is_deleted', 0)->with('vehicleEligibility.carHost:id,country_code,mobile_number,email,firstname,lastname,dob,business_name,gst_number');

        if ($page !== null && $pageSize !== null) {
            $vehicle = $vehicle->paginate($pageSize, ['*'], 'page', $page);
            if(isset($vehicle) && is_countable($vehicle) && count($vehicle) > 0 ){
                foreach($vehicle as $k => $v){
                    $v->hostid = $v->vehicleEligibility->carHost->id;
                    $v->firstname = $v->vehicleEligibility->carHost->firstname;
                    $v->lastname = $v->vehicleEligibility->carHost->lastname;
                    $v->email = $v->vehicleEligibility->carHost->email;
                    $v->mobile_number = $v->vehicleEligibility->carHost->mobile_number;
                    $v->is_publish = $v->publish != 0 ? 1 : 0;
                    $v->makeHidden('banner_image', 'banner_images', 'regular_images', 'host_banner_images', 'host_regular_images', 'rating', 'total_rating', 'trip_count', 'location', 'model','vehicleEligibility');
                }
            }
            $vehicleArray = json_decode(json_encode($vehicle->getCollection()->values()), FALSE);
            $vehicle = [
                'vehicles' => $vehicleArray,
                'pagination' => [
                    'total' => $vehicle->total(),
                    'per_page' => $vehicle->perPage(),
                    'current_page' => $vehicle->currentPage(),
                    'last_page' => $vehicle->lastPage(),
                    'from' => ($vehicle->currentPage() - 1) * $vehicle->perPage() + 1,
                    'to' => min($vehicle->currentPage() * $vehicle->perPage(), $vehicle->total()),
                ]
            ];
        }else{
            $vehicle = $vehicle->get();
            if(isset($vehicle) && is_countable($vehicle) && count($vehicle) > 0 ){
                foreach($vehicle as $k => $v){
                    $v->hostid = $v->vehicleEligibility->carHost->id;
                    $v->firstname = $v->vehicleEligibility->carHost->firstname;
                    $v->lastname = $v->vehicleEligibility->carHost->lastname;
                    $v->email = $v->vehicleEligibility->carHost->email;
                    $v->mobile_number = $v->vehicleEligibility->carHost->mobile_number;
                    $v->is_publish = $v->publish != 0 ? 1 : 0;
                    $v->makeHidden('banner_image', 'banner_images', 'regular_images', 'host_banner_images', 'host_regular_images', 'rating', 'total_rating', 'trip_count', 'location', 'model', 'vehicleEligibility');
                }
            }
            $vehicle = [
                'vehicles' => $vehicle,
            ];
        }

        return $this->successResponse($vehicle, 'Vehicles data get successfully');
    }

    public function createOrUpdateCarHost(Request $request){
        $oldVal = $newVal = '';
        $carHostId = $request->input('car_host_id'); 
        $rules = [
            'car_host_id' => 'nullable|exists:car_hosts,id',
            'firstname' => 'required|max:100',
            'lastname' => 'required|max:200',
            'email' => 'email:rfc,dns|max:255',
            'dob' => 'required|date|before:' . Carbon::now()->setTimezone('Asia/Kolkata')->toDateTimeString(),
            'profile_picture_url' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:4096',
            'pan_number' => 'nullable|regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
            'gst_number' => 'nullable|regex:/^([0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1})$/',
            'business_name' => 'nullable|max:200',
            'account_holder_name' => 'nullable|max:200',
            'bank_name' => 'nullable|max:200',
        ];
        $rules['mobile_number'] = $carHostId
            ? ['numeric', 'digits_between:8,15', Rule::unique('car_hosts', 'mobile_number')->ignore($carHostId)->where(function ($query) {
                $query->where('is_deleted', 0);
            })]
            : ['numeric', 'digits_between:8,15', Rule::unique('car_hosts', 'mobile_number')->where(function ($query) {
                $query->where('is_deleted', 0);
            })];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        if(isset($request->car_host_id) && $request->car_host_id != ''){
            $carHost = CarHost::where('id', $request->car_host_id)->first();
            $oldVal = clone $carHost;
        }else{
            $carHost = new CarHost();
            $carHost->is_blocked = 1;
        }
        $carHost->firstname = $request->firstname ?? '';
        $carHost->lastname = $request->lastname ?? '';
        $carHost->email = $request->email ?? '';
        $carHost->dob = $request->dob ? date('Y-m-d', strtotime($request->dob)) : '';
        $carHost->mobile_number = $request->mobile_number ?? '';
        $carHost->pan_number = $request->pan_number ?? '';
        $carHost->gst_number = $request->gst_number ?? '';
        $carHost->business_name = $request->business_name ?? '';
        $carHost->save();

        if ($request->hasFile('profile_picture_url')) {
            $file = $request->file('profile_picture_url');
            $filename = 'Carhost_userprofile_'.$carHost->id.'_'.time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('images/profile_pictures'), $filename);
            $carHost->profile_picture_url = $filename;
            $carHost->save();
        }
        $newVal = $carHost;
        if(isset($request->car_host_id) && $request->car_host_id != ''){
            logAdminActivities("Carhost Updated Successfully", $oldVal, $newVal);
            return $this->successResponse($carHost, 'Car host updated Successfully');
        }
        else{
            logAdminActivities('Carhost added successfully', $newVal);
            return $this->successResponse($carHost, 'Car host added Successfully');
        }
    }

    public function blockUnblockCarHost(Request $request){
        $oldVal = $newVal = '';
        $validator = Validator::make($request->all(), [
            'car_host_id' => 'required|exists:car_hosts,id',
            'status' => 'required|in:0,1', //0 - Un-blocked 1 - Blocked
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $carHost = CarHost::where('id', $request->car_host_id)->first();
        $oldVal = clone $carHost;
        if(isset($carHost) && $carHost != ''){
            if($request->status == 1){ // BLock
                $carHost->is_blocked = 1;
                $carHost->save();
                $newVal = $carHost;
                logAdminActivities('Carhost Blocked Successfully', $oldVal, $newVal);
                return $this->successResponse(null, 'Car host blocked Successfully');
            }elseif($request->status == 0){ // Un-Block
                $carHostPickupLocationCheck = CarHostPickupLocation::where('car_hosts_id', $request->car_host_id)->where('is_primary', 1)->exists();
                if($carHostPickupLocationCheck){
                    $carHost->is_blocked = 0;
                    $carHost->save();
                    $newVal = $carHost;
                    logAdminActivities('Carhost Un-blocked Successfully', $oldVal, $newVal);
                    return $this->successResponse(null, 'Car host Un-blocked Sucessfully');
                }else{
                    return $this->errorResponse('You are required to add at least one pickup location (as Primary) for this host to un-block this car host.');
                }   
            }else{
                return $this->errorResponse('Please add valid status');
            }
        }else{
            return $this->errorResponse('Carhost not Found');
        }
    }

    public function getCarHostPickupLocation(Request $request){
        $validator = Validator::make($request->all(), [
            'carhost_id' => 'required|exists:car_hosts,id',
            'pickup_location_id' => 'nullable|exists:car_host_pickup_locations,id'
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $carHostPickupLocations = CarHostPickupLocation::where('car_hosts_id', $request->carhost_id)->with('carHostParkingVehicleImgs')->where('is_deleted', 0);
        if(isset($request->pickup_location_id) && $request->pickup_location_id != ''){
            $carHostPickupLocations = $carHostPickupLocations->where('id', $request->pickup_location_id);
        }
        $carHostPickupLocations = $carHostPickupLocations->get();
        if(isset($carHostPickupLocations) && is_countable($carHostPickupLocations) && count($carHostPickupLocations) > 0){
            foreach($carHostPickupLocations as $key => $val){
                $checkLocations = CarHostPickupLocationTemp::where('car_host_pickup_locations_id', $val->id)->exists();
                $val->is_show_approve_icon = $checkLocations;

                $isVehicleUsed = CarEligibility::where('car_host_pickup_location_id', $val->id)->exists();
                if($isVehicleUsed){
                    $val->is_assign_to_vehicle = true;
                }else{
                    $val->is_assign_to_vehicle = false;
                }
            }
            return $this->successResponse($carHostPickupLocations);
        }else{
            return $this->errorResponse("Host Location deails are not found");
        }
    }

    public function addUpdateCarHostPickuplocation(Request $request){
        $parkingTypes = config('global_values.vehicle_parking_type');
        $parkingTypes = array_keys($parkingTypes);
        $parkingTypes = implode(',', $parkingTypes);
        $validator = Validator::make($request->all(), [
            'car_host_id' => 'required|exists:car_hosts,id',
            'vehicle_id' => 'nullable|exists:vehicles,vehicle_id',
            'car_pickup_location_id' => 'nullable|exists:car_host_pickup_locations,id',
            'latitude' => 'required',
            'longitude' => 'required',
            'location' => 'required|max:500',
            'parking_type' => 'required|in:'.$parkingTypes, 
            'parking_spot_imgs' => 'nullable|array',
            'parking_spot_imgs.*' => ['max:5000',
                function ($attribute, $value, $fail) {
                    if ($value instanceof \Illuminate\Http\UploadedFile) {
                        if (!$value->isValid()) {
                            $fail("$attribute is not a valid file.");
                        }
                    } elseif (is_string($value)) {
                        if (!filter_var($value, FILTER_VALIDATE_URL)) {
                            $fail("$attribute must be a valid URL or uploaded image.");
                        }
                        // Optionally: check if URL ends with an allowed extension
                        if (!preg_match('/\.(jpg|jpeg|png|gif|bmp|svg|webp|heic|heif)$/i', $value)) {
                            $fail("$attribute must be a valid image URL.");
                        }
                    } else {
                        $fail("$attribute must be an uploaded image or a valid image URL.");
                    }
                }
            ], 
        ],[
            'parking_spot_imgs.*.max' => 'Parking Spot image size must be less than 5MB',
        ]);
        $validator->sometimes(['parking_spot_imgs'], 'required', function ($input) {
            return !isset($input->car_pickup_location_id) && $input->car_pickup_location_id == '';
        });
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        
        $checkLocationCnt = CarHostPickupLocation::where('car_hosts_id', $request->car_host_id)->where('is_deleted', 0)->count();
        if($request->car_pickup_location_id == ''){
            if($checkLocationCnt >= 2){
                return $this->errorResponse('You can not add more than 2 host Pickup Locations');
            }
        }

        $oldVal = $newVal = '';
        if(isset($request->car_pickup_location_id) && $request->car_pickup_location_id != ''){
            $carPickupLocation = CarHostPickupLocation::where('id', $request->car_pickup_location_id)->first();
            $oldVal = clone $carPickupLocation;
        }else{
            $carPickupLocation = new CarHostPickupLocation();    
            $carPickupLocation->car_hosts_id = $request->car_host_id;
        }
        
        if($checkLocationCnt == 0){
            $carPickupLocation->is_primary = 1;
        }

        $carPickupLocation->latitude = $request->latitude;
        $carPickupLocation->longitude = $request->longitude;
        $carPickupLocation->location = $request->location;
        $carPickupLocation->parking_type_id = (int)$request->parking_type;
        if($request->latitude != '' && $request->longitude != ''){
            $nearestBranch = City::nearest($request->latitude, $request->longitude);
            $carPickupLocation->city_id = $nearestBranch->id ?? '';
            $carPickupLocation->name = $nearestBranch->name ?? '';
        }
       $carPickupLocation->save();
        if(isset($request->vehicle_id) && $request->vehicle_id != ''){
            $vehicle = CarEligibility::where('vehicle_id', $request->vehicle_id)->first();
            if($vehicle != '' && $vehicle->car_host_pickup_location_id == NULL){
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
        $newVal = $carPickupLocation;

        if(isset($request->car_pickup_location_id) && $request->car_pickup_location_id != ''){
            logAdminActivities('Carhost pickup location updated successfuly', $oldVal, $newVal);
        }else{
            logAdminActivities('Carhost pickup location added successfuly', $newVal);
        }

        $carPickupLocation->latitude = doubleval($carPickupLocation->latitude);
        $carPickupLocation->longitude = doubleval($carPickupLocation->longitude);

        return $this->successResponse($carPickupLocation, 'Vehicle Pickup location added successfully');
    }

    public function setPrimaryCarHostPickuplocation(Request $request){
        $validator = Validator::make($request->all(), [
            'car_host_pickup_location_id' => 'required|exists:car_host_pickup_locations,id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $oldVal = '';
        $carHostPickupLocation = CarHostPickupLocation::where('id', $request->car_host_pickup_location_id)->first();
        $oldVal = clone $carHostPickupLocation;
        if(isset($carHostPickupLocation) && $carHostPickupLocation != ''){
            $getCarHostLocation = CarHostPickupLocation::where('car_hosts_id', $carHostPickupLocation->car_hosts_id)->get();
            if(isset($getCarHostLocation) && is_countable($getCarHostLocation) && count($getCarHostLocation) > 0){
                foreach($getCarHostLocation as $key => $value){
                    if($value->id != $request->car_host_pickup_location_id){
                        $value->is_primary = 2; // Not Primary
                        $value->save();
                    }
                }
            }
            $carHostPickupLocation->is_primary = 1;
            $carHostPickupLocation->save();

            logAdminActivities('Set car host pickup location as Primary', $oldVal);
            return $this->successResponse($carHostPickupLocation, 'Car host Pickup Location set as Primary Successfully');
        }else{
            return $this->errorResponse('Carhost Pickup Location not Found');
        }
    }

    public function deleteHostPickuplocation(Request $request){
        $validator = Validator::make($request->all(), [
            'host_pickup_location_id' => 'required|exists:car_host_pickup_locations,id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $oldVal = '';
        $checkVehicle = CarEligibility::where('car_host_pickup_location_id', $request->host_pickup_location_id)->whereHas('vehicle', function($q){
            $q->where('is_deleted', 0);
        })->first();
        if($checkVehicle == ''){
            $carHostPickupLocation = CarHostPickupLocation::where('id', $request->host_pickup_location_id)->first();
            $oldVal = clone $carHostPickupLocation;
            if($carHostPickupLocation != ''){
                if($carHostPickupLocation->is_primary == 1){
                    return $this->errorResponse('You can delete only non-primary location');
                }else{
                    $carHostPickupLocation->is_deleted = 1;
                    $carHostPickupLocation->save();

                    logAdminActivities('Delete Host Pickup location', $oldVal);
                    return $this->successResponse($carHostPickupLocation, 'Location deleted successfully');
                }
            }else{
                return $this->errorResponse('Location not found');
            }
        }else{
            return $this->errorResponse("You can't delete this location due to its assign with any Vehicle");
        }
    }

    public function deleteHostPickuplocationImage(Request $request){
        $validator = Validator::make($request->all(), [
            'car_host_vehicle_img_id' => 'required|exists:car_host_vehicle_images,id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $oldVal = '';
        $carHostVehicleImage = CarHostVehicleImage::where('id', $request->car_host_vehicle_img_id)->first();
        $oldVal = clone $carHostVehicleImage;
        if(isset($carHostVehicleImage) && $carHostVehicleImage != ''){
            $filePath = public_path().'/images/car_host/'.basename($carHostVehicleImage->vehicle_img);
                if(file_exists($filePath)){
                    unlink($filePath);
                }
                $carHostVehicleImage->delete();
                logAdminActivities("Host Pickup location images Deletion", $oldVal);
            return $this->successResponse(null, 'Car host Pickup Location deleted Successfully');
        }else{
            return $this->errorResponse('Carhost Pickup Location not Found');
        }
    }

    public function getHostBankDetails(Request $request){
        $validator = Validator::make($request->all(), [
            'bank_id' => 'nullable|exists:car_host_banks,id',
            'host_id' => 'required|exists:car_hosts,id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $user = '';
        $carHostBank = CarHostBank::where('car_hosts_id', $request->host_id)->where('is_deleted', 0);
        if(isset($request->bank_id) && $request->bank_id != ''){
            $carHostBank = $carHostBank->where('id', $request->bank_id);
        }
        $carHostBank = $carHostBank->get();
        if(is_countable($carHostBank) && count($carHostBank) > 0){
            foreach ($carHostBank as $key => $value) {
                $value->car_hosts_id = (string)$value->car_hosts_id;
                if(isset($value->passbook_image) && $value->passbook_image != ''){
                    $value->passbook_image = url('host_bank_document').'/'.$value->passbook_image;
                }
            }
            return $this->successResponse($carHostBank, 'Carhost Bank details are get successfully');
        }else{
            return $this->errorResponse('Carhost bank details are not found');
        }
    }

    public function storeHostBankDetails(Request $request){
        $validator = Validator::make($request->all(), [
            'bank_id' => 'nullable|exists:car_host_banks,id',
            'car_host_id' => 'required|exists:car_hosts,id',
            //'account_holder_name' => 'required',
            'account_holder_name' => 'nullable|regex:/^[a-zA-Z\s\.\-\']+$/',
            //'bank_name' => 'required',
            //'branch_name' => 'required',
            //'city' => 'required',
            'account_no' => 'required|max:18',
            'ifsc_code' => 'required',
            'is_primary' => 'required|in:1,2', //1 = Primary, 2 = Not primary
        ]);
        $validator->sometimes(['passbook_image'], 'required|mimetypes:image/heic,image/heif,image/jpeg,image/png,image/jpg,image/bmp,image/gif,image/svg,image/webp|max:5000', function ($input) {
            return !isset($input->bank_id) && $input->bank_id == '';
        });
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $carHostBank = '';
        if($request->bank_id == ''){
            $checkBankCnt = CarHostBank::where('car_hosts_id', $request->car_host_id)->where('is_deleted', 0)->count();
            if($checkBankCnt >= 2){
                return $this->errorResponse('You can not add more than 2 Banks');
            }
            $checkBank = CarHostBank::where(['car_hosts_id' => $request->car_host_id, 'is_deleted' => 0, 'account_no' => $request->account_no, 'ifsc_code' => $request->ifsc_code])->exists();
            if($checkBank){
                return $this->errorResponse('Already Existed');
            }
            $carHostBank = new CarHostBank();
            $carHostBank->car_hosts_id = $request->car_host_id;
        }else{
            $carHostBank = CarHostBank::where('id', $request->bank_id)->where('is_deleted', 0)->first();    
        }
        $bankStatus = false;
        $carHostBank->account_holder_name = $request->account_holder_name;
        $carHostBank->bank_name = $request->bank_name;
        $carHostBank->branch_name = $request->branch_name;
        $carHostBank->city = $request->city;
        $carHostBank->account_no = $request->account_no;
        $carHostBank->ifsc_code = $request->ifsc_code;
        $carHostBank->nick_name = isset($request->nick_name)?$request->nick_name:NULL;
        if ($request->hasFile('passbook_image')) {
            $file = $request->file('passbook_image');
            $filename = 'hostbank_'.$carHostBank->id.'_'.time().'_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('host_bank_document'), $filename);
            $carHostBank->passbook_image = $filename;
        }
        $primaryStatus = $request->is_primary;
        $carHostBankCheck = CarHostBank::where('car_hosts_id', $request->car_host_id)->where('is_deleted', 0)->count();
        if($carHostBankCheck == 0){
            $primaryStatus = 1;
            $carHostBank->is_primary = $request->is_primary;
            $bankStatus = true;
        }
        if(isset($request->is_primary) && $request->is_primary == 1){
            CarHostBank::where('car_hosts_id', $request->car_host_id)->where('id', '!=', $carHostBank->id)->update(['is_primary'=> 2]);
            $carHostBank->is_primary = $request->is_primary;
            $bankStatus = true;
        }elseif(isset($request->is_primary) && $request->is_primary == 2){
            $hostBank = CarHostBank::where('car_hosts_id', $request->car_host_id)->where('id', '!=', $carHostBank->id)->where('is_primary', 1)->first();
            if($hostBank != ''){
                $carHostBank->is_primary = $request->is_primary;
                $bankStatus = true;
            }elseif($carHostBankCheck == 0){
                $carHostBank->is_primary = 1;
                $bankStatus = true;
            }
        }
        if($bankStatus == true){
            $carHostBank->save();
            return $this->successResponse($carHostBank, 'Carhost Bank details are stored successfully');
        }else{
            return $this->errorResponse('Please make any one Bank as primary first');
        }
    }

    public function deleteHostBank(Request $request){
        $validator = Validator::make($request->all(), [
            'bank_id' => 'required|exists:car_host_banks,id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $carHostBank = CarHostBank::where('id', $request->bank_id)->first();
        if($carHostBank != ''){
            $checkPrimaryBank = CarHostBank::where(['id' => $request->bank_id, 'is_primary' => 1])->first();
            if($checkPrimaryBank == ''){
                $carHostBank->is_deleted = 1;
                $carHostBank->save();
                return $this->successResponse($carHostBank, 'Carhost Bank details are deleted successfully');
            }else{
                return $this->errorResponse('You can not delete primary bank. To delete this bank, please make any other bank as primary first');
            }
        }else{
            return $this->errorResponse('Carhost Bank details are not found');
        }
    }

    public function getHostVehicles(Request $request){
        $validator = Validator::make($request->all(), [
            'carhost_id' => 'required|exists:car_hosts,id',
            'vehicle_id' => 'nullable|exists:vehicles,vehicle_id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $carEligibility = CarEligibility::where('car_hosts_id', $request->carhost_id)->pluck('vehicle_id')->toArray();
        $vehicles = Vehicle::whereIn('vehicle_id', $carEligibility)->whereHas('pricingDetails')->with(['carhostFeatures', 'pricingDetails', 'vehicleEligibility', 'hostVehicleImages'])->where('is_deleted', 0);
        $interiorImgArr = $exteriorImgArr = [];
        if(isset($request->vehicle_id) && $request->vehicle_id != ''){
            $vehicles = $vehicles->where('vehicle_id', $request->vehicle_id)->first();
            if(isset($vehicles->hostVehicleImages) && $vehicles->hostVehicleImages != ''){
                foreach($vehicles->hostVehicleImages as $key => $val){
                    if(isset($val->image_type)){
                        if($val->image_type == 2){
                            $interiorImgArr[] = $val->vehicle_img;
                        }elseif($val->image_type == 3){
                            $exteriorImgArr[] = $val->vehicle_img;
                        }
                    }
                }
                $vehicles->interior_images = $interiorImgArr;
                $vehicles->exterior_images = $exteriorImgArr;

                $carHostVehicleFeatureCheck = CarHostVehicleFeatureTemp::where('vehicles_id', $vehicles->vehicle_id)->exists();
                $carHostVehicleImageTempCheck = CarHostVehicleImageTemp::where('vehicles_id', $vehicles->vehicle_id)->exists();
                $approveIconStatus = false;
                if($carHostVehicleFeatureCheck || $carHostVehicleImageTempCheck){
                    $approveIconStatus = true;
                }
                $vehicles->is_show_approve_icon = $approveIconStatus;
                $vehicles->makeHidden(['host_banner_images', 'host_regular_images', 'rating', 'total_rating', 'trip_count', 'location', 'model', 'banner_image', 'banner_images', 'regular_images', 'hostVehicleImages']);
            }

            return $this->successResponse($vehicles, 'Carhost vehicles are get Successfully');
        }else{
            $vehicles = $vehicles->get();
            if(is_countable($vehicles) && count($vehicles) > 0){
                foreach($vehicles as $key => $val){
                    $carHostVehicleFeatureCheck = CarHostVehicleFeatureTemp::where('vehicles_id', $val->vehicle_id)->exists();
                    $carHostVehicleImageTempCheck = CarHostVehicleImageTemp::where('vehicles_id', $val->vehicle_id)->exists();
                    $approveIconStatus = false;
                    if($carHostVehicleFeatureCheck || $carHostVehicleImageTempCheck){
                        $approveIconStatus = true;
                    }
                    $val->is_show_approve_icon = $approveIconStatus;
                    
                    if(isset($val->hostVehicleImages) && $val->hostVehicleImages != ''){
                        foreach($val->hostVehicleImages as $key => $v){
                            if(isset($v->image_type)){
                                if($v->image_type == 2){
                                    $interiorImgArr[] = $v->vehicle_img;
                                }elseif($v->image_type == 3){
                                    $exteriorImgArr[] = $v->vehicle_img;
                                }
                            }
                        }
                        $val->interior_images = $interiorImgArr;
                        $val->exterior_images = $exteriorImgArr;
                        $val->makeHidden(['host_banner_images', 'host_regular_images', 'rating', 'total_rating', 'trip_count', 'location', 'model', 'banner_image', 'banner_images', 'regular_images', 'hostVehicleImages']);
                    }
                }
                return $this->successResponse(['vehicles' => $vehicles], 'Carhost vehicles are get Successfully');
            }else{
                return $this->errorResponse('Carhost vehicles are not Found');
            }
        }
    }

    public function getHostUpdatedFeatures(Request $request){
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $data = [];
        $carHostVehicleFeatureTemp = CarHostVehicleFeatureTemp::where('vehicles_id', $request->vehicle_id)->with('feature:feature_id,name')->get();
        $carHostVehicleFeature = CarHostVehicleFeature::where('vehicles_id', $request->vehicle_id)->with('feature:feature_id,name')->get();
        $data['old'] = $carHostVehicleFeature;
        $data['new'] = $carHostVehicleFeatureTemp;
        if(isset($data) && is_countable($data) && count($data) > 0){
            return $this->successResponse($data, 'Carhost updated features details are get successfully');
        }else{
            return $this->errorResponse('Carhost updated features details are not found');
        }   
    }

    public function getHostUpdatedImages(Request $request){
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $data = [];
        $carHostVehicleImage = CarHostVehicleImage::where('vehicles_id', $request->vehicle_id)->orderBy('image_type', 'ASC')->get();
        $carHostVehicleImageTemp = CarHostVehicleImageTemp::where('vehicles_id', $request->vehicle_id)->orderBy('image_type', 'ASC')->get();

        if(isset($carHostVehicleImage) && is_countable($carHostVehicleImage) && count($carHostVehicleImage) > 0){
            foreach($carHostVehicleImage as $k => $v){
                if(isset($v->image_type)){
                    $imageTypeText = $v->image_type == 2 ? 'Interior' : 'Exterior';
                    $v->image_type_text = $imageTypeText;
                }
            }
            foreach($carHostVehicleImageTemp as $k => $v){
                if(isset($v->image_type)){
                    $imageTypeText = $v->image_type == 2 ? 'Interior' : 'Exterior';
                    $v->image_type_text = $imageTypeText;
                }
            }
            $data['old'] = $carHostVehicleImage;
            $data['new'] = $carHostVehicleImageTemp;
            return $this->successResponse($data, 'Carhost updated images details are get successfully');
        }else{
            return $this->errorResponse('Carhost updated images details are not found');
        }   
    }

    public function getHostUpdatedLocation(Request $request){
        $validator = Validator::make($request->all(), [
            'host_pickup_location_id' => 'required|exists:car_host_pickup_locations,id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $data = [];
        $carHostPickupLocation = CarHostPickupLocation::where('id', $request->host_pickup_location_id)->with('carHostParkingVehicleImgs')->get();
        $carHostPickupLocationTemp = CarHostPickupLocationTemp::where('car_host_pickup_locations_id', $request->host_pickup_location_id)->with('carHostParkingVehicleImgs')->get();
        $data['old'] = $carHostPickupLocation;
        $data['new'] = $carHostPickupLocationTemp;

        if(isset($data) && is_countable($data) && count($data) > 0){
            return $this->successResponse($data, 'Carhost updated Pickup Location details are get successfully');
        }else{
            return $this->errorResponse('Carhost updated Pickup Location details are not found');
        }
    }

    public function getHostUpdatedVehicleDetails(Request $request){
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $data = [];
        $carHostOldVehicleDetail = DB::table('vehicles as v')
            ->where('vehicle_id', $request->vehicle_id)->where('is_host_updated', 1)
            ->join('cities as c', 'c.id', '=', 'v.temp_city_id')
            ->join('vehicle_models as vm', 'vm.model_id', '=', 'v.model_id')
            ->join('vehicle_manufacturers as vma', 'vma.manufacturer_id', '=', 'vm.manufacturer_id')
            ->select('v.vehicle_id', 'v.temp_city_id', 'c.name', 'v.model_id', 'v.year','vm.name as vehicle_model_name', 'vma.name as manufacturer')->first();
        $carHostNewVehicleDetail = DB::table('vehicles as v')
            ->where('vehicle_id', $request->vehicle_id)->where('is_host_updated', 1)
            ->join('cities as c', 'c.id', '=', 'v.updated_temp_city_id')
            ->join('vehicle_models as vmu', 'vmu.model_id', '=', 'v.updated_model_id')
            ->join('vehicle_manufacturers as vma', 'vma.manufacturer_id', '=', 'vmu.manufacturer_id')
            ->select('v.vehicle_id', 'v.updated_temp_city_id', 'c.name', 'v.updated_model_id', 'v.updated_year','vmu.name as vehicle_updated_model_name','vma.name as manufacturer')->first();
        if($carHostOldVehicleDetail == '' || $carHostNewVehicleDetail == ''){
            return $this->errorResponse($data, 'Carhost vehicle details are not found');
        }else{
            $oldModelImg = $newModelImg ='';
            $oldModelDetail = VehicleModel::select('model_id', 'model_image')->where('model_id', $carHostOldVehicleDetail->model_id)->first();
            $newModelDetail = VehicleModel::select('model_id', 'model_image')->where('model_id', $carHostNewVehicleDetail->updated_model_id)->first();
            $oldModelImg = $oldModelDetail->model_image;
            $newModelImg = $newModelDetail->model_image;
            
            $carHostOldVehicleDetail->model_img = $oldModelImg;
            $carHostNewVehicleDetail->model_img = $newModelImg;

            $data['old'] = $carHostOldVehicleDetail;
            $data['new'] = $carHostNewVehicleDetail;
            
            return $this->successResponse($data, 'Carhost vehicle details are get successfully');
        }
    }

    public function getHostUpdatedVehicleDocuments(Request $request){
        $docType = config('global_values.vehicle_document_types');
        $docType = implode(',', $docType);
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
            'document_type' => 'required|in:'.$docType, //It should be either 'rc_doc', 'insurance_doc' or 'puc_doc'
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $data = [];
        $carHostOldVehicleDocument = VehicleDocument::select('document_id', 'vehicle_id', 'document_type', 'expiry_date', 'document_image_url', 'id_number')->where('vehicle_id', $request->vehicle_id)->where('document_type', $request->document_type)->get();
        $carHostNewVehicleDocument = VehicleDocumentTemp::select('document_id', 'vehicle_id', 'document_type', 'expiry_date', 'document_image_url', 'id_number')->where('vehicle_id', $request->vehicle_id)->where('document_type', $request->document_type)->get();
        if(isset($carHostOldVehicleDocument) && is_countable($carHostOldVehicleDocument) && count($carHostOldVehicleDocument) > 0){
            foreach($carHostOldVehicleDocument as $key => $val){
                if($val->document_image_url){
                    $val->document_image_url = asset('images/documents/' . $val->document_image_url);
                }
            }
        }
        if(isset($carHostNewVehicleDocument) && is_countable($carHostNewVehicleDocument) && count($carHostNewVehicleDocument) > 0){
            foreach($carHostNewVehicleDocument as $key => $val){
                if($val->document_image_url){
                    $val->document_image_url = asset('images/documents/' . $val->document_image_url);
                }
            }
        }
        $data['old'] = $carHostOldVehicleDocument;
        $data['new'] = $carHostNewVehicleDocument;

        if(isset($data) && is_countable($data) && count($data) > 0){
            return $this->successResponse($data, 'Carhost updated Vehicle Document details are get successfully');
        }else{
            return $this->errorResponse('Carhost updated Vehicle Document details are not found');
        }
    }

    public function getHostUpdatedVehiclePrices(Request $request){
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $data = [];
        $carHostOldVehiclePrice = VehiclePriceDetail::where('vehicle_id', $request->vehicle_id)->get();
        $carHostNewVehiclePrice = VehiclePriceDetailTemp::where('vehicle_id', $request->vehicle_id)->get();

        $vehicle = Vehicle::select('vehicle_id', 'model_id', 'extra_km_rate', 'deposit_amount', 'updated_extra_km_rate', 'updated_deposit_amount', 'updated_is_deposit_amount_show')->where('vehicle_id', $request->vehicle_id)->first();
        if($vehicle != ''){
            $vehicle->makeHidden('branch_id', 'year', 'description', 'availability', 'is_deleted', 'extra_hour_rate', 'availability_calendar', 'commission_percent', 'chassis_no', 'created_at', 'updated_at', 'nick_name', 'banner_image', 'banner_images', 'regular_images', 'host_banner_images', 'host_regular_images', 'rating', 'total_rating', 'trip_count', 'model', 'color', 'host_step_count', 'license_plate', 'rental_price', 'publish', 'vehicle_created_by', 'apply_for_publish', 'temp_city_id', 'step_cnt', 'is_host_updated', 'cutout_image', 'location', 'city_name', 'city_id', 'updated_model_id', 'updated_year', 'vehicle_name', 'category_name', 'model_id');
        }
       
        $data['old'] = $carHostOldVehiclePrice;
        $data['new'] = $carHostNewVehiclePrice;
        $data['vehicle_detail'] = $vehicle;
        
        return $this->successResponse($data, 'Carhost vehicle prices are get successfully');
    }

    public function storeHostUpdatedFeatures(Request $request){
        $validator = Validator::make($request->all(), [
            //'temp_feature_id' => 'required|exists:car_host_vehicle_feature_temps, id',
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $carHostVehicleFeatureTemp = CarHostVehicleFeatureTemp::where(['vehicles_id' => $request->vehicle_id])->get();
        if(isset($carHostVehicleFeatureTemp) && is_countable($carHostVehicleFeatureTemp) && count($carHostVehicleFeatureTemp) > 0){
            $getExistingFeatures = CarHostVehicleFeature::where('vehicles_id', $request->vehicle_id)->delete();
            if(is_countable($carHostVehicleFeatureTemp) && count($carHostVehicleFeatureTemp) > 0){
                foreach ($carHostVehicleFeatureTemp as $key => $value) {
                    $carHostVehicleFeature = new CarHostVehicleFeature();
                    $carHostVehicleFeature->vehicles_id = $value->vehicles_id;
                    $carHostVehicleFeature->feature_id = $value->feature_id;
                    $carHostVehicleFeature->save();

                    $value->delete();
                }
                return $this->successResponse(null, 'New features are Approved sucessfully');
            }
        }else{
            return $this->errorResponse('New features are not found for this Vehicle');
        }
    }

    public function storeHostUpdatedImages(Request $request){
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $hostInteriorVehicleImageTemp = CarHostVehicleImageTemp::where('vehicles_id', $request->vehicle_id)->where('image_type', 2)->get();
        $hostExteriorVehicleImageTemp = CarHostVehicleImageTemp::where('vehicles_id', $request->vehicle_id)->where('image_type', 3)->get();
        if(isset($hostInteriorVehicleImageTemp) && is_countable($hostInteriorVehicleImageTemp) && count($hostInteriorVehicleImageTemp) > 0){
            $carHostVehicleImage = CarHostVehicleImage::where('vehicles_id', $request->vehicle_id)->where('image_type', 2)->get();  
            if(isset($carHostVehicleImage) && is_countable($carHostVehicleImage) && count($carHostVehicleImage) > 0){
                foreach($carHostVehicleImage as $k => $v){
                    $this->unlinkImages($v);
                }
                foreach($hostInteriorVehicleImageTemp as $k => $v){
                    $carHostVehicleImage = new CarHostVehicleImage();
                    $carHostVehicleImage->vehicles_id = $v->vehicles_id;
                    $carHostVehicleImage->image_type = $v->image_type;
                    $carHostVehicleImage->vehicle_img = basename($v->vehicle_img);
                    $carHostVehicleImage->save();
                    $v->delete();
                }
            }
        }
        if(isset($hostExteriorVehicleImageTemp) && is_countable($hostExteriorVehicleImageTemp) && count($hostExteriorVehicleImageTemp) > 0){
            $carHostVehicleImage = CarHostVehicleImage::where('vehicles_id', $request->vehicle_id)->where('image_type', 3)->get();  
            if(isset($carHostVehicleImage) && is_countable($carHostVehicleImage) && count($carHostVehicleImage) > 0){
                foreach($carHostVehicleImage as $k => $v){
                    $this->unlinkImages($v);
                }
                foreach($hostExteriorVehicleImageTemp as $k => $v){
                    $carHostVehicleImage = new CarHostVehicleImage();
                    $carHostVehicleImage->vehicles_id = $v->vehicles_id;
                    $carHostVehicleImage->image_type = $v->image_type;
                    $carHostVehicleImage->vehicle_img = basename($v->vehicle_img);
                    $carHostVehicleImage->save();
                    $v->delete();
                }
            }
        }
        
        return $this->successResponse(null, 'Carhost updated images details are set successfully');
    }

    public function storeHostUpdatedLocation(Request $request){
        $validator = Validator::make($request->all(), [
            'host_pickup_location_id' => 'required|exists:car_host_pickup_locations,id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $carHostPickupLocationTemp = CarHostPickupLocationTemp::where('car_host_pickup_locations_id', $request->host_pickup_location_id)->with('carHostParkingVehicleImgs')->first();
        if(isset($carHostPickupLocationTemp) && $carHostPickupLocationTemp != ''){
            $carHostPickupLocation = CarHostPickupLocation::where('id', $request->host_pickup_location_id)->with('carHostParkingVehicleImgs')->first();
            if(isset($carHostPickupLocation) && $carHostPickupLocation != ''){
                $carHostPickupLocation->city_id = $carHostPickupLocationTemp->city_id;
                $carHostPickupLocation->name = $carHostPickupLocationTemp->name;
                $carHostPickupLocation->latitude = $carHostPickupLocationTemp->latitude;
                $carHostPickupLocation->longitude = $carHostPickupLocationTemp->longitude; 
                $carHostPickupLocation->location = $carHostPickupLocationTemp->location;
                $carHostPickupLocation->parking_type_id = $carHostPickupLocationTemp->parking_type_id;
                $carHostPickupLocation->is_primary = $carHostPickupLocationTemp->is_primary;
                $carHostPickupLocation->save();
                if(isset($carHostPickupLocation->carHostParkingVehicleImgs) && count($carHostPickupLocation->carHostParkingVehicleImgs) > 0){
                    foreach($carHostPickupLocation->carHostParkingVehicleImgs as $k => $v){
                        $this->unlinkImages($v);
                    }
                }
                if(isset($carHostPickupLocationTemp->carHostParkingVehicleImgs) && count($carHostPickupLocationTemp->carHostParkingVehicleImgs) > 0){
                    foreach($carHostPickupLocationTemp->carHostParkingVehicleImgs as $k => $v){
                        $carHostVehicleImageTemp = new CarHostVehicleImage();
                        $carHostVehicleImageTemp->car_host_pickup_locations_id = $v->car_host_pickup_locations_id;
                        $carHostVehicleImageTemp->image_type = $v->image_type;
                        $carHostVehicleImageTemp->vehicle_img = basename($v->vehicle_img);
                        $carHostVehicleImageTemp->save();
                        $v->delete();
                    }
                }
                $carHostPickupLocationTemp->delete();
            }
            return $this->successResponse($carHostPickupLocation, 'Carhost updated Location details are stored successfully');
        }else{
            return $this->errorResponse('Carhost updated pickup location details are not found');
        }
    }

    public function storeHostUpdatedVehicleDetails(Request $request){
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $vehicle = Vehicle::where(['vehicle_id' => $request->vehicle_id])->first();
        if(isset($vehicle) && $vehicle != ''){
            $vehicle->temp_city_id = $vehicle->updated_temp_city_id;
            $vehicle->model_id = $vehicle->updated_model_id;
            $vehicle->year = $vehicle->updated_year;
            $vehicle->is_host_updated = 0; 
            $vehicle->updated_temp_city_id = NULL; 
            $vehicle->updated_model_id = NULL; 
            $vehicle->updated_year = NULL; 
            $vehicle->save();

            // UPDATED CITIES IN CAR HOST PICKUP LOCATIONS AS PER VEHICLE CITY
            $getCarEligibility = CarEligibility::where('vehicle_id', $request->vehicle_id)->first();
            if($getCarEligibility != ''){
                $carHostPickupLocations = CarHostPickupLocation::where('id', $getCarEligibility->car_host_pickup_location_id)->first();
                if(isset($carHostPickupLocations) && $carHostPickupLocations != ''){
                    $carHostPickupLocations->city_id = $vehicle->temp_city_id;
                    $carHostPickupLocations->save(); 
                }
            }

            return $this->successResponse(null, 'New Vehicle details are Approved sucessfully');
        }else{
            return $this->errorResponse('New Vehicles details are not found for this Vehicle');
        }
    }

    public function storeHostUpdatedVehicleDocuments(Request $request){
        $docType = config('global_values.vehicle_document_types');
        $docType = implode(',', $docType);
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
            'document_type' => 'required|in:'.$docType, //It should be either 'rc_doc', 'insurance_doc' or 'puc_doc'
        ]);

        $vehicleDocumentTemp = VehicleDocumentTemp::where(['vehicle_id' => $request->vehicle_id])->where('document_type', $request->document_type)->get();
        if(isset($vehicleDocumentTemp) && is_countable($vehicleDocumentTemp) && count($vehicleDocumentTemp) > 0){
            $getExistingDocuments = VehicleDocument::where('vehicle_id', $request->vehicle_id)->where('document_type', $request->document_type)->get();
            if(is_countable($getExistingDocuments) && count($getExistingDocuments) > 0){
                foreach($getExistingDocuments as $k => $v){
                    if(isset($v->document_image_url)){
                        $filePath = public_path().'/images/documents/'.basename($v->document_image_url);
                        if(file_exists($filePath)){
                            unlink($filePath);
                        }
                    }
                }
                $getExistingDocuments->each->delete();
            }
            if(is_countable($vehicleDocumentTemp) && count($vehicleDocumentTemp)){
                $rcNum = '';
                foreach ($vehicleDocumentTemp as $key => $value) {
                    $rcNum = $value->id_number;
                    $vehicleDocument = new VehicleDocument();
                    $vehicleDocument->vehicle_id = $value->vehicle_id;
                    $vehicleDocument->document_type = $value->document_type;
                    $vehicleDocument->id_number = $value->id_number;
                    $vehicleDocument->expiry_date = $value->expiry_date;
                    $vehicleDocument->document_image_url = basename($value->document_image_url);
                    $vehicleDocument->is_approved = 1;
                    $vehicleDocument->approved_by = auth()->guard('admin')->user()->admin_id ? auth()->guard('admin')->user()->admin_id : 1;
                    $vehicleDocument->save();
                    $value->delete();
                }
                if($rcNum != ''){
                    $vehicle = Vehicle::where('vehicle_id', $request->vehicle_id)->first();
                    if($vehicle != ''){
                        $vehicle->license_plate = $rcNum;
                        $vehicle->save();
                    }
                }
                return $this->successResponse(null, 'New vehicle documents are Approved sucessfully');
            }
        }else{
            return $this->errorResponse('New vehicle documents are not found for this Vehicle');
        }
    }

    public function storeHostUpdatedVehiclePrices(Request $request){
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $vehiclePriceDetailTemp = VehiclePriceDetailTemp::where(['vehicle_id' => $request->vehicle_id])->get();
        if(isset($vehiclePriceDetailTemp) && is_countable($vehiclePriceDetailTemp) && count($vehiclePriceDetailTemp) > 0){
            $getExistingPrices = VehiclePriceDetail::where('vehicle_id', $request->vehicle_id)->get();
            if(is_countable($getExistingPrices) && count($getExistingPrices) > 0){
                $getExistingPrices->each->delete();
            }
            if(is_countable($vehiclePriceDetailTemp) && count($vehiclePriceDetailTemp)){
                foreach ($vehiclePriceDetailTemp as $key => $value) {
                    $vehiclePriceDetail = new VehiclePriceDetail();
                    $vehiclePriceDetail->vehicle_id = $value->vehicle_id;
                    $vehiclePriceDetail->rental_price = $value->rental_price;
                    $vehiclePriceDetail->hours = $value->hours;
                    $vehiclePriceDetail->rate = $value->rate;
                    $vehiclePriceDetail->multiplier = $value->multiplier;
                    $vehiclePriceDetail->duration = $value->duration;
                    $vehiclePriceDetail->per_hour_rate = $value->per_hour_rate;
                    $vehiclePriceDetail->trip_amount_km_limit = $value->trip_amount_km_limit;
                    $vehiclePriceDetail->unlimited_km_trip_amount = $value->unlimited_km_trip_amount;
                    $vehiclePriceDetail->is_show = $value->is_show;
                    $vehiclePriceDetail->save();
                    $value->delete();
                }
                $checkVehicle = Vehicle::where('vehicle_id', $request->vehicle_id)->first();
                if($checkVehicle != '' && $checkVehicle->updated_extra_km_rate != NULL || $checkVehicle->updated_extra_km_rate != ''){
                    $checkVehicle->deposit_amount = $checkVehicle->updated_deposit_amount;
                    $checkVehicle->is_deposit_amount_show = $checkVehicle->updated_is_deposit_amount_show;
                    if($checkVehicle->updated_extra_km_rate != NULL || $checkVehicle->updated_extra_km_rate != ''){
                        $checkVehicle->extra_km_rate = $checkVehicle->updated_extra_km_rate;
                    }
                    $checkVehicle->updated_deposit_amount = NULL;
                    $checkVehicle->updated_is_deposit_amount_show = NULL;
                    $checkVehicle->updated_extra_km_rate = NULL;
                    $checkVehicle->save();
                }
                return $this->successResponse(null, 'New vehicle prices are Approved sucessfully');
            }
        }else{
            return $this->errorResponse('New vehicle prices are not found for this Vehicle');
        }
    }

    protected function unlinkImages($carHostVehicleImage){
        $filePath = public_path().'/images/car_host/'.basename($carHostVehicleImage->vehicle_img);
        if(file_exists($filePath)){
            unlink($filePath);
        }
        $carHostVehicleImage->delete();
    }

    public function setHostFeatures(Request $request){
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
            'feature_id' => 'required',
            'vehicle_description' => 'nullable|max:500',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        if(isset($request->feature_id) && $request->feature_id != ''){
            $featureArr = explode(',', $request->feature_id);
            $carHostVehicleFeature = CarHostVehicleFeature::where('vehicles_id', $request->vehicle_id)->delete();
            if(is_countable($featureArr) && count($featureArr) > 0){
                foreach ($featureArr as $key => $value) {
                    $carHostVehicleFeature = new CarHostVehicleFeature();
                    $carHostVehicleFeature->vehicles_id = $request->vehicle_id;
                    $carHostVehicleFeature->feature_id = $value;
                    $carHostVehicleFeature->save();
                }
            }
        }
        if(isset($request->vehicle_description) && $request->vehicle_description != ''){
            $vehicle = Vehicle::where('vehicle_id', $request->vehicle_id)->first();
            $vehicle->description = $request->vehicle_description;
            $vehicle->save();    
        }

        return $this->successResponse(null, 'Host features updated successfully');
    }

    public function setHostVehicleImages(Request $request){
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
            'vehicle_interior_imgs' => 'required|array',
            'vehicle_interior_imgs.*' => ['max:80000',
                function ($attribute, $value, $fail) {
                    if ($value instanceof \Illuminate\Http\UploadedFile) {
                        if (!$value->isValid()) {
                            $fail("$attribute is not a valid file.");
                        }
                    } elseif (is_string($value)) {
                        if (!filter_var($value, FILTER_VALIDATE_URL)) {
                            $fail("$attribute must be a valid URL or uploaded image.");
                        }
                        // Optionally: check if URL ends with an allowed extension
                        if (!preg_match('/\.(jpg|jpeg|png|gif|bmp|svg|webp|heic|heif)$/i', $value)) {
                            $fail("$attribute must be a valid image URL.");
                        }
                    } else {
                        $fail("$attribute must be an uploaded image or a valid image URL.");
                    }
                }
            ],
            'vehicle_exterior_imgs' => 'required|array',
            'vehicle_exterior_imgs.*' => ['max:80000',
                function ($attribute, $value, $fail) {
                    if ($value instanceof \Illuminate\Http\UploadedFile) {
                        if (!$value->isValid()) {
                            $fail("$attribute is not a valid file.");
                        }
                    } elseif (is_string($value)) {
                        if (!filter_var($value, FILTER_VALIDATE_URL)) {
                            $fail("$attribute must be a valid URL or uploaded image.");
                        }
                        // Optionally: check if URL ends with an allowed extension
                        if (!preg_match('/\.(jpg|jpeg|png|gif|bmp|svg|webp|heic|heif)$/i', $value)) {
                            $fail("$attribute must be a valid image URL.");
                        }
                    } else {
                        $fail("$attribute must be an uploaded image or a valid image URL.");
                    }
                }
            ],
        ],[
            'vehicle_interior_imgs.*.max' => 'Vehicle image size must be less than 80MB',
            'vehicle_exterior_imgs.*.max' => 'Vehicle image size must be less than 80MB',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $interiorImgCnt = CarHostVehicleImage::where(['vehicles_id' => $request->vehicle_id, 'image_type' => 2])->count(); //image_type = 2 means Vehicle Interior images
        $exteriorImgCnt = CarHostVehicleImage::where(['vehicles_id' => $request->vehicle_id, 'image_type' => 3])->count(); //image_type = 3 means Vehicle Exterior images
        if($interiorImgCnt > 5){
            return $this->errorResponse('You can not add more than 5 images for Internal');
        }
        if($exteriorImgCnt > 5){
            return $this->errorResponse('You can not add more than 5 images for External');
        }
        
        if(is_countable($request->file('vehicle_interior_imgs')) && count($request->file('vehicle_interior_imgs')) > 0){
            foreach ($request->file('vehicle_interior_imgs') as $key => $image) {
                $filename = 'Interior_'.$request->vehicle_id.'_'.$key.'_'.time() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images/car_host'), $filename);
                $carHostVehicleImage = new CarHostVehicleImage();
                $carHostVehicleImage->vehicles_id = $request->vehicle_id;
                $carHostVehicleImage->image_type = 2;
                $carHostVehicleImage->vehicle_img = $filename;
                $carHostVehicleImage->save();
            }
        }
        
        if(is_countable($request->file('vehicle_exterior_imgs')) && count($request->file('vehicle_exterior_imgs')) > 0){ 
            foreach ($request->file('vehicle_exterior_imgs') as $key => $image) {
                $filename = 'Exterior_'.$request->vehicle_id.'_'.$key.'_'.time() . '.' . $image->getClientOriginalExtension();   
                $image->move(public_path('images/car_host'), $filename);
                $carHostVehicleImage = new CarHostVehicleImage();
                $carHostVehicleImage->vehicles_id = $request->vehicle_id;
                $carHostVehicleImage->image_type = 3;
                $carHostVehicleImage->vehicle_img = $filename;
                $carHostVehicleImage->save();
            }
        }
        return $this->successResponse(null, 'Host vehicle images updated successfully');
    }

    public function setHostVehicleDetails(Request $request){
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
            'fast_tag' => 'required|in:0,1', //0 = Fasttag not Exist, 1 = Fasttag Exist
            'night_time' => 'required|in:0,1', //0 = Not Restricted, 1 = Restricted
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $carEligibility = CarEligibility::where('vehicle_id', $request->vehicle_id)->first();
        if($carEligibility != ''){
            $carEligibility->fast_tag = $request->fast_tag;
            $carEligibility->night_time = $request->night_time;
            $carEligibility->save();
        }

        return $this->successResponse($carEligibility, 'Vehicle Night time information stored successfully');
    }

    public function getHostHoldVehicleDates(Request $request){
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $vehicle = Vehicle::where('vehicle_id', $request->vehicle_id)->first();
        if($vehicle != ''){
            if($vehicle->availability_calendar != ''){
                $holding_dates = json_decode($vehicle->availability_calendar, true);
                return $this->successResponse($holding_dates, 'Vehicle holding dates are fetched successfully');
            }else{
                return $this->errorResponse('No holding dates found for this vehicle');
            }
        }else{
            return $this->errorResponse('5Vehicle not found');
        }
    }

    public function setHostVehicleHoldDates(Request $request){
        // Check if the car_eligibilities table is empty
        if (CarEligibility::count() === 0) {
            return $this->successResponse('No car eligibilities found.');
        }
       
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

    public function getVehicleInfo(Request $request){
        $detailArr = [];
        $startYear = 2010;
        $currentYear = date('Y');
        $yearArr = [];

        for ($i = 0; $startYear + $i <= $currentYear; $i++) {
            $yearArr[] = $startYear + $i;
        }
        $yearDescArr = array_reverse($yearArr);
        $kmDrivenArr = config('global_values.vehicle_km_driven');

        $detailArr['years'] = $yearDescArr;
        $detailArr['km_driven'] = $kmDrivenArr;

        return $this->successResponse($detailArr, 'Vehicle Information get Successfully');
    }

    public function getHostVehiclePricingDetails(Request $request){
        $minPrice = 100;
        $maxPrice = 200;

        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id'
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $vehicle = Vehicle::select('vehicle_id', 'model_id', 'rental_price')->find($request->vehicle_id);
        if (!$vehicle) {
            return $this->errorResponse('Vehicle not found');
        }
     
        $vehiclePriceDetails = VehiclePriceDetail::where('vehicle_id', $request->vehicle_id)->where('is_show', 1)->get();
        if(is_countable($vehiclePriceDetails) && count($vehiclePriceDetails)) {
            foreach($vehiclePriceDetails as $k => $v){
                //if($v->rate > 0){
                    $tripAmount = $v->rate;
                    $unKMtripAmount = $v->rate * 1.3;
                    $perHourRate = $tripAmount / $v->hours; // Calculate per hour rate based on the total trip amount and duration
                    $duration = ($v->hours >= 24) ? round($v->hours / 24, 2) . ' days' : $v->hours . ' hours';
                    $vehicleTypeName = $vehicle->model->category->vehicleType->name ?? null;
                    $durationHoursLimit = calculateKmLimit($v->hours, $vehicleTypeName);
                    $pricingShowCase[$k]['duration'] = $duration;
                    $pricingShowCase[$k]['trip_amount_in_rupees'] = '₹' . number_format(($tripAmount), 2)." ( ".$durationHoursLimit." Km )";
                    // $pricingShowCase[$k]['trip_amount_in_rupees'] = '₹' . number_format(($tripAmount), 2);
                    $pricingShowCase[$k]['unlimited_km_trip_amount_in_rupees'] = '₹' . number_format(($unKMtripAmount), 2);
                    $pricingShowCase[$k]['per_hour_rate'] = '₹' . number_format(($perHourRate), 2);
                //}
            }
            //$summaryTable = $this->buildPricingTable($pricingShowCase);  
            $summary = $pricingShowCase;  

            return $this->successResponse(['price_details' => $summary, 'min_price' => $minPrice, 'max_price' => $maxPrice], 'Pricing show case retrieved successfully.');
        } else {
            $rules = TripAmountCalculationRule::select('id', 'hours', 'multiplier')->orderBy('hours', 'desc')->get();
            $summaryTable = [];
            if(is_countable($rules) && count($rules) > 0){
                $pricingShowCase = $rules->map(function ($rule) use ($vehicle) {
                    $tripAmount = $rule->multiplier * $vehicle->rental_price;
                    $unKMtripAmount = ($rule->multiplier * $vehicle->rental_price) * 1.3;
                    $perHourRate = $tripAmount / $rule->hours; // Calculate per hour rate based on the total trip amount and duration
                    $duration = ($rule->hours >= 24) ? round($rule->hours / 24, 2) . ' days' : $rule->hours . ' hours';
                    $vehicleTypeName = $vehicle->model->category->vehicleType->name ?? null;
                    $durationHoursLimit = calculateKmLimit($rule->hours, $vehicleTypeName);
                    return [
                        'duration' => $duration,
                        'trip_amount_in_rupees' => '₹' . number_format(($tripAmount), 2)." ( ".$durationHoursLimit." Km )" ,
                        // 'trip_amount_in_rupees' => '₹' . number_format(($tripAmount), 2),
                        'unlimited_km_trip_amount_in_rupees' => '₹' . number_format(($unKMtripAmount), 2),
                        'per_hour_rate' => '₹' . number_format(($perHourRate), 2)
                    ];
                });
                $summary = $pricingShowCase;

                return $this->successResponse(['price_details' => $summary, 'min_price' => $minPrice, 'max_price' => $maxPrice], 'Pricing show case retrieved successfully.');
            }
        }
        return $this->successResponse(['table_html' => $summaryTable, 'min_price' => $minPrice, 'max_price' => $maxPrice], 'Pricing show case retrieved successfully.');
    }

    public function setHostVehiclePricingDetails(Request $request){
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
            'pricing_update_info' => 'required|json',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        if($request->vehicle_id != ''){
            $vehicle = Vehicle::where('vehicle_id', $request->vehicle_id)->first();
            if($vehicle != ''){
                $rentalPrice = $rentalPriceHour = 0;
                $vehicleModelId = $vehicle->model_id ?? '';
                if($request->pricing_update_info != ''){
                    $pricingDetails = json_decode($request->pricing_update_info, true);
                    if(is_countable($pricingDetails) && count($pricingDetails) > 0){
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
                            return $this->successResponse(['vehicle_pricing_control' => $updatedDetails], 'Pricing Update Info updated successfully');
                        }
                    }
                }else{
                    return $this->errorResponse('Pricing Update Info not found');
                }
            }else{
                return $this->errorResponse('Vehicle not found');
            }
        }else{
            return $this->errorResponse('Vehicle not found');
        }
    }

    public function deleteHostVehicleImage(Request $request){
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
            'image_url' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $baseName = basename($request->image_url);
        $carHostVehicleImage = CarHostVehicleImage::where('vehicle_img', $baseName)->first();
        if($carHostVehicleImage != ''){
            $filePath = public_path().'/images/car_host/'.basename($carHostVehicleImage->vehicle_img);
            if(file_exists($filePath)){
                unlink($filePath);
            }
            $carHostVehicleImage->delete();
            return $this->successResponse(null, 'Carhost vehicle image deleted successfully');
        }else{
            return $this->errorResponse('Carhost vehicle image not found');
        }
    }
    
    private function getCustomerStatus($customers){
        $cStatus = '';
        if($customers != ''){
            if($customers->is_blocked == 1)
                $cStatus = 'Bloked';
            else
                $cStatus = 'Active';
        }

        return $cStatus;
    }

}
