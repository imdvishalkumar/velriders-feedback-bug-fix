<?php

namespace App\Http\Controllers\CarhostAppApis\V1;

use App\Http\Controllers\Controller; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\{
    Vehicle, CarEligibility, VehicleManufacturer, VehicleModel, CarHostVehicleImage, CarHostVehicleFeature, CarHostPickupLocation, NightHour,
    CarHostBank, VehicleCategory, VehicleDocument, VehicleProperty, RentalBooking, VehicleFeature, FuelType, Transmission, City, TripAmountCalculationRule, VehiclePriceDetail, Customer, VehicleType, CarHostVehicleStartJourneyImage, RentalReview, Faq, ImageSlider
};
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class CarHostVehicleDetailsController extends Controller
{
    protected $userAuthDetails;

    public function __construct()
    {
        $this->userAuthDetails = Auth::guard('api-carhost')->user();
    }

    public function getManufacturers(Request $request)
    {
        $typeId = $request->input('vehicle_type_id');
        $query = VehicleManufacturer::query();
        if ($typeId) {
            $typeIds = is_string($typeId) ? explode(',', $typeId) : $typeId;
                $query = $query->whereIn('vehicle_type_id', $typeIds);
        }
        $manufacturers = $query->get();
        return $this->successResponse($manufacturers);
    }

    public function getModels(Request $request)
    {
        $typeId = $request->input('vehicle_type_id') ?? '';
        $manufacturerId = $request->input('manufacturer_id') ?? '';
        $query = VehicleModel::query();
        
        if($manufacturerId != '') {
            $query->where('manufacturer_id', $manufacturerId);
        }

        if ($typeId != '') {
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

    public function getYears(Request $request){
        $startYear = 2010;
        $currentYear = date('Y');
        $yearArr = [];

        for ($i = 0; $startYear + $i <= $currentYear; $i++) {
            $yearArr[] = $startYear + $i;
        }
        $yearDescArr = array_reverse($yearArr);
        return $this->successResponse($yearDescArr);
    }

    public function getKmDriven(Request $request){
        $kmDrivenArr = config('global_values.vehicle_km_driven');

        return $this->successResponse($kmDrivenArr);
    }

    public function getCarHostVehicles(Request $request){
        $validator = Validator::make($request->all(), [
            //'carhost_id' => 'required',
            'carhost_id' => [
                'required',
                Rule::exists('car_hosts', 'id')->where('is_deleted', 0)
            ],
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $carEligibility = CarEligibility::where('car_hosts_id', $request->carhost_id)->pluck('vehicle_id')->toArray();
        $vehicles = Vehicle::whereIn('vehicle_id', $carEligibility)->with('carHostVehicleImages', function ($query) {
                        $query->select('id', 'vehicles_id', 'image_type', 'vehicle_img');
                    })->with('properties')->whereHas('pricingDetails')/*->whereHas('hostVehicleImages')*/->where('is_deleted', 0);

        if($request->vehicle_id){
            if(in_array($request->vehicle_id, $carEligibility)){
                $vehicles = $vehicles->where('vehicle_id', $request->vehicle_id);    
            }else{
                return $this->errorResponse('Vehicle id is Invalid');    
            }
        }
        $vehicles = $vehicles->get();
        $selectedVehicleId = $request->selected_vehicle_id ?? '';
        if(is_countable($vehicles) && count($vehicles) > 0){
            foreach($vehicles as $key => $val){
                $vehicleType = $val->model->category->vehicleType->name;
                $vehicleTypeId = $val->model->category->vehicleType->type_id;
                $val->vehicle_type = $vehicleType;
                $val->vehicle_type_id = $vehicleTypeId;
                if($val->publish == 0 && $val->apply_for_publish == 0){
                    $val->publish = 0; // Publish
                }elseif($val->publish == 0 && $val->apply_for_publish == 1){
                    $val->publish = 2; // Pending
                }elseif($val->publish == 1 && $val->apply_for_publish == 1){
                    $val->publish = 1; // Unpublish
                }
            }
            return $this->successResponse(['vehicles' => $vehicles, 'selected_vehicle_id' => $selectedVehicleId], 'Carhost vehicles are get Successfully');
        }else{
            return $this->errorResponse('Carhost vehicles are not Found');
        }
    }

    public function getCategories(Request $request){
        $vehicleCategory = VehicleCategory::where('is_deleted', 0)->get();
        if(is_countable($vehicleCategory) && count($vehicleCategory) > 0){
            return $this->successResponse($vehicleCategory, 'Vehicles categories are get Successfully');
        }else{
            return $this->errorResponse('Vehicles categories are not Found');
        }
    }

    public function getVehicleDropdownValues(Request $request){
        $data = [];
        $validator = Validator::make($request->all(), [
            'vehicle_type_id' => 'required|exists:vehicle_types,type_id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $fuelTypes = FuelType::where('vehicle_type_id', $request->vehicle_type_id)->get();
        $transmissions = Transmission::where('vehicle_type_id', $request->vehicle_type_id)->get();
        $data['fuel_types'] = $fuelTypes;
        $data['transmissions'] = $transmissions;

        return $this->successResponse($data, 'Car Eligibility details stored Successfully');
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

    public function getVehicleDesc(Request $request){
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $vehicle = Vehicle::select('vehicle_id', 'description', 'nick_name', 'model_id')->where('vehicle_id', $request->vehicle_id)->first();
        if($vehicle){
            $vehicle->makeHidden(['cutout_image', 'banner_image', 'banner_images', 'regular_images', 'host_banner_images', 'host_regular_images', 'rating', 'total_rating', 'trip_count', 'location', 'model']);
        }
        if(isset($vehicle) && $vehicle != ''){
            return $this->successResponse($vehicle, 'Vehicle details are stored Successfully');
        }else{
            return $this->errorResponse('Vehicle not Found');
        }
    }

    public function getParkingType(Request $request){
        $parkingTypes = config('global_values.vehicle_parking_type');
        if(is_countable($parkingTypes) && count($parkingTypes) > 0)
            return $this->successResponse($parkingTypes, 'Parking Types get successfully');
        else
            return $this->errorResponse('Parking Types not found');
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

    public function getHoldVehicleDates(Request $request){
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

    public function getListingHours(){
        $nightHours = NightHour::all();
        return $this->successResponse($nightHours, 'Get Night Hours successfully');
    }

    public function getVehicleFeatures(Request $request){
        $vehicleFeatures = VehicleFeature::select('feature_id', 'name', 'icon', 'is_deleted')->where('is_deleted', 0)->get();
        if(is_countable($vehicleFeatures) && count($vehicleFeatures) > 0){
            return $this->successResponse($vehicleFeatures, 'Vehicle features are get Successfully');    
        }else{
            return $this->errorResponse('Vehicle features data are not found');    
        }
    }

    public function getBookings(Request $request){
        $validator = Validator::make($request->all(), [
            'booking_status' => 'nullable|in:all,completed,failed,canceled,running,confirmed,no show,pending',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $currentDateTime = Carbon::now()->setTimezone('Asia/Kolkata');
        $oneHourAgo = $currentDateTime->subHour();
        $user = Auth::guard('api-carhost')->user();  
        $carEligibilityIds = CarEligibility::where('car_hosts_id', $user->id)->pluck('vehicle_id')->toArray();
        $query = RentalBooking::with(['pickupLocation' => function ($query) {
            $query->select('id', 'vehicle_id', 'car_hosts_id');
        },'pickupLocation.vehiclePickupLocation' => function($query){
            $query->select('id', 'name', 'latitude', 'longitude', 'location');
        }, 'vehicle' => function ($query) {
            $query->select('vehicle_id', 'model_id');
        }])->whereIn('vehicle_id', $carEligibilityIds)->orderBy('created_at', 'desc');
    
        //Vehicle Filter
        if(isset($request->vehicle_id) && $request->vehicle_id != ''){
            if($request->vehicle_id != 0){ 
                $query->where('vehicle_id', $request->vehicle_id);
            }
        }
        //Booking Status Fileter
        if(isset($request->booking_status) && $request->booking_status != ''){
            if($request->booking_status != 'all'){
                $query->where('status', $request->booking_status);
            }
        }
        //Time Duration Fileter
        if(isset($request->time_duration) && $request->time_duration != ''){
            if($request->time_duration == 'last_week'){
                $query->whereBetween('created_at', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()]);
            }elseif($request->time_duration == 'last_month'){
                $query->whereBetween('created_at', [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()]);
            }
        }
    
        // Check if page and page_size are provided
        $page = $request->input('page');
        $pageSize = $request->input('page_size');
        $now = Carbon::now();
        if ($page !== null && $pageSize !== null) {
            // Paginate the results
            $rentalBookings = $query->paginate($pageSize, ['*'], 'page', $page);
            if(isset($rentalBookings) && is_countable($rentalBookings) && count($rentalBookings)){
                foreach($rentalBookings as $key => $val){
                    $creationDate = Carbon::parse($val->customer->created_at);
                    $diff = $creationDate->diff($now);
                    $val->customer->joining_date = $diff->y . ' years, ' . $diff->m . ' months, ' . $diff->d . ' days';
                }
            }else{
                return $this->errorResponse("Bookings are not Found");
            }
    
            // Filter the paginated results
            $rentalBookings->getCollection()->filter(function ($item) use ($oneHourAgo) {
                if ($item->status != 'pending') {
                    return true;
                } else {
                    $creationDateTime = Carbon::parse($item->created_at);
                    return $creationDateTime->greaterThanOrEqualTo($oneHourAgo);
                }
            });
          
            // Hide specific attributes
            $rentalBookings->getCollection()->each(function ($item) {
                $item->makeHidden(['from_branch_id', 'to_branch_id', 'customer_id', 'vehicle_id', 'rental_duration_minutes', 'penalty_details', 'start_otp', 'end_otp', 'unlimited_kms', 'total_cost', 'amount_paid', 'rental_type', 'data_json', 'start_datetime', 'end_datetime', 'sequence_no', 'created_at', 'updated_at', 'start_images', 'end_images', 'invoice_pdf', 'summary_pdf', 'dl_status', 'govtid_status', 'allow_rating', 'rating_value', 'feedback_value', 'pay_now_status', 'admin_penalty_amount', 'price_summary']);
            });
            
            // Convert to JSON
            $rentalBookingsArray = json_decode(json_encode($rentalBookings->getCollection()->values()), FALSE);
            if(isset($rentalBookingsArray) && is_countable($rentalBookingsArray) && count($rentalBookingsArray) > 0){
                for ($i = 0; $i < count($rentalBookingsArray); $i++) {
                    $rentalBookingsArray[$i]->vehicle->vehicle_name = "#{$rentalBookingsArray[$i]->booking_id} " . $rentalBookingsArray[$i]->vehicle->vehicle_name;
                }
            }
    
            // Return paginated response
            return $this->successResponse([
                'rental_bookings' => $rentalBookingsArray,
                'pagination' => [
                    'total' => $rentalBookings->total(),
                    'per_page' => $rentalBookings->perPage(),
                    'current_page' => $rentalBookings->currentPage(),
                    'last_page' => $rentalBookings->lastPage()
                ]
            ], 'Bookings are get successfully');
        } else {
            // Get all results
            $rentalBookings = $query->get();
            if(isset($rentalBookings) && is_countable($rentalBookings) && count($rentalBookings)){
                foreach($rentalBookings as $key => $val){
                    $creationDate = Carbon::parse($val->customer->created_at);
                    $diff = $creationDate->diff($now);
                    $val->customer->joining_date = $diff->y . ' years, ' . $diff->m . ' months, ' . $diff->d . ' days';
                }
            }else{
                return $this->errorResponse("Bookings are not Found");
            }
            // Filter the results
            $rentalBookings = $rentalBookings->filter(function ($item) use ($oneHourAgo) {
                if ($item->status != 'pending') {
                    return true;
                } else {
                    $creationDateTime = Carbon::parse($item->created_at);
                    return $creationDateTime->greaterThanOrEqualTo($oneHourAgo);
                }
            });
    
            // Hide specific attributes
            $rentalBookings->each(function ($item) {
                $item->setHidden(['from_branch_id', 'to_branch_id', 'customer_id', 'vehicle_id', 'rental_duration_minutes', 'penalty_details', 'start_otp', 'end_otp', 'unlimited_kms', 'total_cost', 'amount_paid', 'rental_type', 'data_json', 'start_datetime', 'end_datetime', 'sequence_no', 'created_at', 'updated_at', 'start_images', 'end_images', 'invoice_pdf', 'summary_pdf', 'dl_status', 'govtid_status', 'allow_rating', 'rating_value', 'feedback_value', 'pay_now_status', 'admin_penalty_amount', 'price_summary']);
            });
           
            // Convert to JSON
            $rentalBookingsArray = json_decode(json_encode($rentalBookings->values()), FALSE);
            if(isset($rentalBookingsArray) && is_countable($rentalBookingsArray) && count($rentalBookingsArray) > 0){
                for ($i = 0; $i < count($rentalBookingsArray); $i++) {
                    $rentalBookingsArray[$i]->vehicle->vehicle_name = "#{$rentalBookingsArray[$i]->booking_id} " . $rentalBookingsArray[$i]->vehicle->vehicle_name;
                }
            }
    
            return $this->successResponse(['rental_bookings' => $rentalBookingsArray], 'Bookings are get successfully');
        }
    }

    public function getBookingDetails(Request $request, $booking_id){
        $currentDateTime = Carbon::now()->setTimezone('Asia/Kolkata');
        $oneHourAgo = $currentDateTime->subHour();
        $user = Auth::guard('api-carhost')->user();  

        $carEligibilityIds = CarEligibility::where('car_hosts_id', $user->id)->pluck('vehicle_id')->toArray();
        $rentalBooking = RentalBooking::with(['vehicle.model.manufacturer', 'vehicle.properties', 'vehicle.features', 'vehicle.images'])->whereIn('vehicle_id', $carEligibilityIds)->with(['vehicle' => function ($query) {
                        $query->select('vehicle_id', 'branch_id' ,'model_id');
                    }])->where('booking_id', $booking_id)->first();

        if($rentalBooking != ''){
            $carHostVehicleStartJourneyImages = CarHostVehicleStartJourneyImage::select('id', 'car_host_id', 'booking_id', 'vehicle_id', 'image_type', 'vehicle_img')->where(['car_host_id' => $user->id, 'booking_id' => $booking_id, 'image_type' => 1])->get();
            if(isset($carHostVehicleStartJourneyImages) && is_countable($carHostVehicleStartJourneyImages) && count($carHostVehicleStartJourneyImages) > 0){
                foreach($carHostVehicleStartJourneyImages as $key => $val){
                    $val->vehicle_img = asset('images/carhost_vehicle_start_journey_image/'.$val->vehicle_img);          
                }
            }else{
                $carHostVehicleStartJourneyImages = [];
            }

            $carHostVehicleEndJourneyImages = CarHostVehicleStartJourneyImage::select('id', 'car_host_id', 'booking_id', 'vehicle_id', 'image_type', 'vehicle_img')->where(['car_host_id' => $user->id, 'booking_id' => $booking_id, 'image_type' => 2])->get();
            if(isset($carHostVehicleEndJourneyImages) && is_countable($carHostVehicleEndJourneyImages) && count($carHostVehicleEndJourneyImages) > 0){
                foreach($carHostVehicleEndJourneyImages as $key => $val){
                    $val->vehicle_img = asset('images/carhost_vehicle_end_journey_image/'.$val->vehicle_img);          
                }
            }else{
                $carHostVehicleEndJourneyImages = [];
            }
            
            $rentalBooking->setHidden(['from_branch_id', 'to_branch_id', 'customer_id', 'rental_duration_minutes', 'penalty_details', 'start_otp', 'end_otp', 'unlimited_kms', 'button_visiblity', 'total_cost', 'amount_paid', 'rental_type', /*'calculation_details', */'data_json', 'start_datetime', 'end_datetime', 'sequence_no', 'created_at', 'updated_at', 'pay_now_status', 'admin_penalty_amount']);
            $rentalBooking = json_decode(json_encode($rentalBooking), FALSE);
            $rentalBooking->vehicle->vehicle_name = "#{$rentalBooking->booking_id} " . $rentalBooking->vehicle->vehicle_name; 
            $rentalBooking->carhost_vehicle_start_journey_images = $carHostVehicleStartJourneyImages;
            $rentalBooking->carhost_vehicle_end_journey_images = $carHostVehicleEndJourneyImages;
            $rentalBooking->invoice_pdf = "https://velriders.com/api/host/v1/carhost-booking-invoice/".$booking_id."";

            return $this->successResponse(['rental_bookings' => $rentalBooking]);
        }else{
            return $this->errorResponse("Booking id is invalid");
        }
    }

    public function getHomeVehicleStatuses(Request $request){
        $vehicleId = $request->vehicle_id;
        $vehicleStatus = [];
        if($vehicleId == ''){
            return $this->errorResponse('Please pass the Vehicle Id');    
        }
        
        $checkVehicleImgs = CarHostVehicleImage::where(['vehicles_id' => $request->vehicle_id])->exists();
        $checkVehicleFeatures = CarHostVehicleFeature::where('vehicles_id', $request->vehicle_id)->exists();
        $checkVehicleProperties = VehicleProperty::where('vehicle_id', $request->vehicle_id)->exists();
        $checkVehicleDesc = Vehicle::where('vehicle_id', $request->vehicle_id)->where('description', '!=', '')->whereNotNull('description')->exists();
        $checkVehiclePublishStatus = Vehicle::where('vehicle_id', $request->vehicle_id)->where('publish', 1)->exists();
        $checkVehicleLicenseChassis = Vehicle::where('vehicle_id', $request->vehicle_id)->where('license_plate', '!=', '')->whereNotNull('license_plate')->exists();
        $carEligibility = CarEligibility::where('vehicle_id', $request->vehicle_id)->where('car_host_pickup_location_id', '!=', '')->exists();
        $checkHoldingDates = Vehicle::select('vehicle_id', 'availability_calendar')->where('vehicle_id', $request->vehicle_id)->whereNotNull('availability_calendar')->where('availability_calendar', '!=', '')->where('availability_calendar', '!=', '[]')->exists();
        $vehicleRcDocStatus = VehicleDocument::where(['vehicle_id' => $request->vehicle_id, 'document_type' => 'rc_doc', 'is_approved' => 1])->get();
        $vehicleDocStatus = false;
        if(is_countable($vehicleRcDocStatus) && count($vehicleRcDocStatus) > 0){
            $vehicleDocStatus = true;
        }
        $checkBankStatus = false;
        //$checkPanStatus = false;
        if(Auth::guard('api-carhost')->check() && $this->userAuthDetails){
            $checkBankStatus = CarHostBank::where('car_hosts_id', $this->userAuthDetails->id)->exists();
            $checkBankStatus = true;
            // if($this->userAuthDetails->pan_number != NULL || $this->userAuthDetails->pan_number != ''){
            //     $checkPanStatus = true;
            // }
        }
        $publishStatus = false;
        //if($checkVehicleImgs == true && $checkVehicleFeatures == true && $checkVehicleDesc == true && $carEligibility == true && $vehicleDocStatus == true && $checkBankStatus == true && $checkVehicleProperties == true){
        if($checkVehicleImgs == true && $checkVehicleFeatures == true && $checkVehicleDesc == true && $carEligibility == true && $vehicleDocStatus == true && $checkVehicleProperties == true){
            $publishStatus = true;
        }
        $isPending = false;
        $vehicleDetail = Vehicle::where('vehicle_id', $request->vehicle_id)->first();
        if($vehicleDetail != ''){
            if($vehicleDetail->publish == 0 && $vehicleDetail->apply_for_publish == 1){
                $isPending = true;
            }else{
                $isPending = false;
            }
        }

        $vehicleStatus['vehicle_profile']['desc'] = 'This helps your listing standout to our 1.2 milion guests';
        $vehicleStatus['vehicle_profile']['vehicle_img'] = $checkVehicleImgs;
        $vehicleStatus['vehicle_profile']['vehicle_img_msg'] = 'Add Vehicle Image';
        $vehicleStatus['vehicle_profile']['vehicle_features'] = $checkVehicleFeatures;
        $vehicleStatus['vehicle_profile']['vehicle_features_msg'] = 'Add Vehicle Feature';
        $vehicleStatus['vehicle_profile']['vehicle_properties'] = $checkVehicleProperties;
        $vehicleStatus['vehicle_profile']['vehicle_properties_msg'] = 'Add Vehicle Property';
        $vehicleStatus['vehicle_profile']['vehicle_desc'] = $checkVehicleDesc;
        $vehicleStatus['vehicle_profile']['vehicle_desc_msg'] = 'Add Vehicle Description';

        $vehicleStatus['share_vehicle']['desc'] = 'Select the location and dates you want to share your vehicle at';
        //$vehicleStatus['share_vehicle']['vehicle_license'] = $checkVehicleLicenseChassis;
        $vehicleStatus['share_vehicle']['vehicle_license'] = true;
        $vehicleStatus['share_vehicle']['vehicle_license_msg'] = 'Add Vehicle License Number and chassis number';
        $vehicleStatus['share_vehicle']['vehicle_location'] = $carEligibility;
        $vehicleStatus['share_vehicle']['vehicle_location_msg'] = 'Select Location';
        // $vehicleStatus['share_vehicle']['vehicle_holding_dates'] = $checkHoldingDates;
        // $vehicleStatus['share_vehicle']['vehicle_holding_dates_msg'] = 'Select Dates for holding vehicles';

        $vehicleStatus['kyc']['desc'] = 'Select the location and dates you want to share your vehicle at';
        $vehicleStatus['kyc']['vehicle_doc'] = $vehicleDocStatus;
        $vehicleStatus['kyc']['vehicle_doc_msg'] = 'Add RC Card';
        // $vehicleStatus['kyc']['vehicle_bank'] = $checkBankStatus;
        $vehicleStatus['kyc']['vehicle_bank'] = true;
        $vehicleStatus['kyc']['vehicle_bank_msg'] = 'Add bank details';
        // $vehicleStatus['kyc']['vehicle_pan'] = $checkPanStatus;
        // $vehicleStatus['kyc']['vehicle_pan_msg'] = 'Add PAN details';
        $vehicleStatus['publish']['vehicle_publish'] = $checkVehiclePublishStatus;
        $vehicleStatus['publish']['publish_status'] = $publishStatus;
        $vehicleStatus['publish']['pending'] = $isPending;

        return $this->successResponse($vehicleStatus);
    }

    public function getPickupLocations(Request $request){
        $validator = Validator::make($request->all(), [
            'pickup_location_id' => 'nullable|exists:car_host_pickup_locations,id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        if(Auth::guard('api-carhost')->check() && $this->userAuthDetails){
            $carPickupLocation = CarHostPickupLocation::where('car_hosts_id', $this->userAuthDetails->id)
                            ->with(['carHostParkingVehicleImgs' => function ($query) {
                                $query->select('car_host_pickup_locations_id', 'image_type', 'vehicle_img');
                            }])->where('is_deleted', 0);
            if($request->pickup_location_id != ''){ 
                $carPickupLocation = $carPickupLocation->where('id', $request->pickup_location_id);
            }
            $carPickupLocation = $carPickupLocation->get();
            $carPickupLocation->each(function ($location) {
                if($location->carHostParkingVehicleImgs){
                    $location->carHostParkingVehicleImgs->makeHidden('vehicle_img');
                }
            });
    
            if(is_countable($carPickupLocation) && count($carPickupLocation) > 0){
                return $this->successResponse($carPickupLocation);
            }else{
                return $this->errorResponse("Vehicle Pickup Location deails are not found");
            }
           
        }else {
            return $this->errorResponse("User not found");
        }
    }

    public function getVehiclePickupLocationDetails(Request $request){
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'nullable|exists:vehicles,vehicle_id',
            'pickup_location_id' => 'nullable|exists:car_host_pickup_locations,id'
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        if(Auth::guard('api-carhost')->check() && $this->userAuthDetails){
            if($request->vehicle_id != ''){
                $carEligibility = CarEligibility::select('id', 'vehicle_id', 'car_hosts_id', 'car_host_pickup_location_id', 'km_driven', 'fast_tag', 'night_time','night_hours_id')->where('vehicle_id', $request->vehicle_id)->with('vehiclePickupLocation', function($q){
                    $q->select('id','city_id', 'name', 'latitude', 'longitude', 'location', 'parking_type_id');
                })->with('vehiclePickupLocation.carHostParkingVehicleImgs', function ($query) {
                    $query->select('id', 'car_host_pickup_locations_id', 'image_type', 'vehicle_img'); 
                })->first();
                
                if(isset($carEligibility) && $carEligibility != '' && isset($carEligibility->vehiclePickupLocation) && $carEligibility->vehiclePickupLocation->carHostParkingVehicleImgs){
                    $carEligibility->vehiclePickupLocation->carHostParkingVehicleImgs->makeHidden('vehicle_img');
                }
                if($carEligibility != ''){
                    return $this->successResponse($carEligibility);
                }else{
                    return $this->errorResponse("Vehicle Pickup Location deails are not found");
                }
            }else{
                $carHostPickupLocations = CarHostPickupLocation::where('car_hosts_id', $this->userAuthDetails->id)->where('is_deleted', 0);
                if(isset($request->pickup_location_id) && $request->pickup_location_id != ''){
                    $carHostPickupLocations = $carHostPickupLocations->where('id', $request->pickup_location_id);
                }
                $carHostPickupLocations = $carHostPickupLocations->get();
                if(isset($carHostPickupLocations) && is_countable($carHostPickupLocations) && count($carHostPickupLocations) > 0){
                    foreach($carHostPickupLocations as $key => $val){
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
        }else {
            return $this->errorResponse("User not found");
        }
    }

    public function getFuelAndTransmissions(Request $request)
    {
        $typeId = $request->input('vehicle_type_id');
        $fuelTypes = FuelType::select('fuel_type_id', 'vehicle_type_id', 'name')->where('is_deleted', 0);
        if ($typeId) {
            $fuelTypes = $fuelTypes->where('vehicle_type_id', $typeId);
        }
        $fuelTypes = $fuelTypes->get();

        $transmissions = Transmission::select('transmission_id', 'vehicle_type_id', 'name')->where('is_deleted', 0);
        if ($typeId) {
            $transmissions = $transmissions->where('vehicle_type_id', $typeId);
        }
        $transmissions = $transmissions->get();

        $response['fuel'] = $fuelTypes;
        $response['transmission'] = $transmissions;

        return $this->successResponse($response);
    }

    public function getVehicleDetails(Request $request){
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        if($request->vehicle_id != ''){ 
            $vehicle = Vehicle::where('vehicle_id', $request->vehicle_id)->with('properties')->with('vehicleEligibility', function ($query){
                        $query->select('vehicle_id', 'fast_tag', 'night_time', 'km_driven','night_hours_id');
                    })->with('CarHostVehicleFeatures', function ($query){
                        $query->select('vehicles_id', 'feature_id');
                    })->with('pricingDetails', function ($query){
                        $query->select('id', 'vehicle_id', 'duration', 'per_hour_rate', 'hours', 'rate', 'is_show');
                    })->with('hostVehicleImages', function ($query){
                        $query->select('vehicles_id', 'car_host_pickup_locations_id', 'image_type', 'vehicle_img');
                    })->where('is_deleted', 0)->first();
            if($vehicle != ''){
                $vehicleModelId = $vehicle->model_id;
                if(isset($vehicle->pricingDetails) && is_countable($vehicle->pricingDetails) && count($vehicle->pricingDetails) > 0){
                    foreach($vehicle->pricingDetails as $key => $val){
                        $vehicleId = $val->vehicle_id ?? '';
                        $hours = $val->hours ?? '';
                        if($vehicleId != '' && $hours != ''){
                            $details = getRateWithModelRate($hours, $vehicleModelId);
                            // if($val->rate > 0){ $val->minPrice = $details['minPrice'] ?? 0; }else{ $val->minPrice = 0;}
                            $val->minPrice = $details['minPrice'] ?? 0;
                            $val->maxPrice = $details['maxPrice'] ?? 0;
                            $val->middlePrice = getMiddlePrice($val->minPrice, $val->maxPrice);
                        }
                    }
                }   
                $modelKmDetails = getModelKmDetail($vehicleModelId);
                $minKmLimit = (float)$modelKmDetails['min_km_limit'];
                $maxKmLimit = (float)$modelKmDetails['max_km_limit'];
                $middleKmLimit = $modelKmDetails['middle_km_limit'];
                $vehicle->pricingDetails[] = [
                    'id' => 0,
                    'vehicle_id' => $vehicle->vehicle_id,
                    'duration' => 'Kilometer Range',
                    'per_hour_rate' => 0,
                    'hours' => 0,
                    'rate' => (float)$vehicle->extra_km_rate ?? 0,
                    'is_show' => 1,
                    'minPrice' => $minKmLimit,
                    'maxPrice' => $maxKmLimit,
                    'middlePrice' => $middleKmLimit,
                ];

                $modelDepositDetails = getModelDepositDetail($vehicleModelId);
                $minDepositLimit = (float)$modelDepositDetails['min_deposit_limit'];
                $maxDepositLimit = (float)$modelDepositDetails['max_deposit_limit'];
                $middleDepositLimit = $modelDepositDetails['middle_deposit_limit'];
                $vehicle->pricingDetails[] = [
                    'id' => 0,
                    'vehicle_id' => $vehicle->vehicle_id,
                    'duration' => 'Deposit Range',
                    'per_hour_rate' => 0,
                    'hours' => 0,
                    'rate' => (float)$vehicle->deposit_amount ?? 0,
                    'is_show' => 1,
                    'minPrice' => $minDepositLimit,
                    'maxPrice' => $maxDepositLimit,
                    'middlePrice' => $middleDepositLimit,
                ];

                $vehicleType = $vehicle->model->category->vehicleType->name;
                $vehicleTypeId = $vehicle->model->category->vehicleType->type_id;
                $vehicle->vehicle_type = $vehicleType;
                $vehicle->vehicle_type_id = $vehicleTypeId;

                $vehiclePriceInfo = VehiclePriceDetail::where('vehicle_id', $vehicle->vehicle_id)->where('is_show', 1)->orderBy('id', 'DESC')->get();
                $msgVal = 0;
                if(isset($vehiclePriceInfo) && is_countable($vehiclePriceInfo) && count($vehiclePriceInfo) > 0 ){
                    foreach($vehiclePriceInfo as $k => $v){
                        if($k == 0){
                            $rate = $v->rate;
                            $hours = $v->hours;
                            if($rate >= $hours){
                                $msgVal = $rate / $hours;
                            }
                            $msgVal = round($msgVal, 2);
                            break;
                        }
                    }
                }
                $msgText = "Calculated per-hour rate is ₹" . $msgVal;
                $vehicle->per_hour_rate_msg = $msgText;
                $vehicle->vehicle_detail_statuses = getVehicleDetailStatuses($vehicle->vehicle_id);
            
                return $this->successResponse($vehicle);
            }else{
                return $this->errorResponse("Vehicle not found");
            }
        }else{
            return $this->errorResponse("Vehicle details are not found");
        }
    }
    
    public function getPricingControl(Request $request){
        $minPrice = 100;
        $maxPrice = 200;

        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id'
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $vehicle = Vehicle::select('vehicle_id', 'model_id', 'rental_price','extra_km_rate','deposit_amount')->with('model.category.vehicleType')->find($request->vehicle_id);
        if (!$vehicle) {
            return $this->errorResponse('Vehicle not found');
        }

        $pricingShowCaseDetails = '';
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
                    // $pricingShowCase[$k]['trip_amount_in_rupees'] = '₹' . number_format(($tripAmount), 2)." ( ".$durationHoursLimit." Km )";
                    $pricingShowCase[$k]['trip_amount_in_rupees'] = '₹' . number_format($tripAmount, 2);
                    $pricingShowCase[$k]['duration_hours_limit'] = "( " . $durationHoursLimit . " Km )";
                    $pricingShowCase[$k]['unlimited_km_trip_amount_in_rupees'] = '₹' . number_format(($unKMtripAmount), 2);
                    $pricingShowCase[$k]['per_hour_rate'] = '₹' . number_format(($perHourRate), 2);
                    $pricingShowCase[$k]['price'] = '₹0.00';
                //}
            }
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
                        'unlimited_km_trip_amount_in_rupees' => '₹' . number_format(($unKMtripAmount), 2),
                        'per_hour_rate' => '₹' . number_format(($perHourRate), 2),
                        'price' => '₹0.00'
                    ];
                });
            }
        }

        // Append static record at the end
        $pricingShowCase[] = [
            "duration" => "Kilometer Range",
            "trip_amount_in_rupees" => null,
            "duration_hours_limit" => null,
            "unlimited_km_trip_amount_in_rupees" => null,
            "per_hour_rate" => null,
            "price" => $vehicle->extra_km_rate
        ];

        $pricingShowCase[] = [
            "duration" => "Deposit Range",
            "trip_amount_in_rupees" => null,
            "duration_hours_limit" => null,
            "unlimited_km_trip_amount_in_rupees" => null,
            "per_hour_rate" => null,
            "price" => $vehicle->deposit_amount
        ];
        $summaryTable = $this->buildPricingTable($pricingShowCase);  
        $pricingShowCaseDetails = $pricingShowCase;

        return $this->successResponse(['table_html' => $summaryTable, 'min_price' => $minPrice, 'max_price' => $maxPrice, 'plan_model' => $pricingShowCaseDetails], 'Pricing show case retrieved successfully.');
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
    
    public function generateStartOtp(Request $request, $booking_id){
        $startOtp = mt_rand(1000, 9999);
        $updateOtpStartRide = RentalBooking::where('booking_id', $booking_id)->first();
      	if($updateOtpStartRide != ''){
          $customer = Customer::find($updateOtpStartRide->customer_id);
          if($customer->is_blocked) {
              return response()->json(['success' => 'Customer is blocked can not generate OTP ', 'startOtp' => null]);
          }
          $updateOtpStartRide->start_otp = $startOtp;
          $updateOtpStartRide->save();
          
          return $this->successResponse(['startOtp' => $startOtp, 'booking_id' => $updateOtpStartRide->booking_id], 'Start OTP sent successfully');
        }else{
          return $this->errorResponse('Booking not Found');
        }
    }

    public function generateEndOtp(Request $request, $booking_id){
        $endOtp = mt_rand(1000, 9999);
        $updateOtpStartRide = RentalBooking::find($booking_id);
        if($updateOtpStartRide != ''){
            $updateOtpStartRide->end_otp = $endOtp;
            $updateOtpStartRide->save();

            return $this->successResponse(['endOtp' => $endOtp, 'booking_id' => $updateOtpStartRide->booking_id], 'End OTP sent successfully');
        }else{
            return $this->errorResponse('Booking not Found');
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
        $types = VehicleType::where('is_deleted', 0)->get();

        //$typeId = $request->input('vehicle_type_id');
        $query = VehicleManufacturer::query();
        // if ($typeId) {
        //     $typeIds = is_string($typeId) ? explode(',', $typeId) : $typeId;
        //         $query = $query->whereIn('vehicle_type_id', $typeIds);
        // }
        $manufacturers = $query->get();

        $models = VehicleModel::select('model_id', 'name', 'manufacturer_id', 'model_image')->where('is_deleted', 0)->with('manufacturer')->get();
        if(isset($models) && is_countable($models) && count($models) > 0){
            $models->each(function ($model) {
                $model->manufacturerid = $model->manufacturer->manufacturer_id; 
                $model->vehicle_typeid = $model->manufacturer->vehicle_type_id; 
                $model->logo = $model->model_image;
                $model->makeHidden(['manufacturer', 'model_image']);
            });
        }

        $detailArr['years'] = $yearDescArr;
        $detailArr['km_driven'] = $kmDrivenArr;
        $detailArr['types'] = $types;
        $detailArr['manufacturers'] = $manufacturers;
        $detailArr['models'] = $models;

        return $this->successResponse($detailArr, 'Vehicle Information get Successfully');
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

    public function getCommonDetails(Request $request)
    {
        $typeArr = $finalArr = [];
        // Get all vehicle types
        $vehicleTypes = VehicleType::select('type_id', 'name', 'is_deleted')->where('is_deleted', 0)->get();
        if(isset($vehicleTypes) && is_countable($vehicleTypes) && count($vehicleTypes) > 0){
            $finalArr['vehicle_types'] = $vehicleTypes;
        }else{
            $finalArr['vehicle_types'] = [];
        }
     
        $fuelTypes = FuelType::select('fuel_type_id', 'name', 'is_deleted', 'vehicle_type_id')->where('is_deleted', 0)->get();
        if(isset($fuelTypes) && is_countable($fuelTypes) && count($fuelTypes) > 0){
            $finalArr['fuel_types'] = $fuelTypes;
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
            }
            $finalArr['manufacturers'] = $manufacturers;
        }else{
            $finalArr['manufacturers'] = [];
        }
        // Get Models with type filter
        $models = VehicleModel::where('is_deleted', 0)->get();
        if(isset($models) && is_countable($models) && count($models) > 0){
            $finalArr['models'] = $models;
        }else{
            $finalArr['models'] = [];
        }
        // Get Transmissions with type filter
        $transmissions = Transmission::where('is_deleted', 0)->get();
        if(isset($transmissions) && is_countable($transmissions) && count($transmissions) > 0){
            $finalArr['transmissions'] = $transmissions;
        }else{
            $finalArr['transmissions'] = [];
        }
        // Get Category with type filter
        $categories = VehicleCategory::select('category_id', 'vehicle_type_id', 'name', 'icon', 'is_deleted', 'sort')->where('is_deleted', 0)->orderBy('sort', 'asc')->get();
        if(isset($categories) && is_countable($categories) && count($categories) > 0){
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
        $finalArr['velrider_stories'] = $rentalReview;
        $faqs = Faq::select('question', 'answer')->where('is_deleted', 0)->where('faq_for', 2)->get(); // 1 for customer 2 for host
        $imageSliders = ImageSlider::where('is_deleted', 0)->where('banner_for', 2)->pluck('banner_img')
                        ->map(function ($val) {
                            return [
                                'images' => asset('images/banner_sliders/' . $val)
                            ];
                        })->toArray();

        // HOME SCREEN STATIC DATA
        // $imageSliders = [
        //     [
        //         'images' => 'https://velriders.com/images/home_screen/Banner-1.png',
        //     ],
        //     [
        //         'images' => 'https://velriders.com/images/home_screen/Banner-2.png',
        //     ],
        // ];
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
        // $faqs = [
        //     [
        //         'question' => 'How to book your ride?',
        //         'answer' => 'Select your city, preferred travel date & time, choose a vehicle, select kilometer type (limited/unlimited), apply a coupon code (if applicable), choose a payment method (GPay, Debit Card, Credit Card, EMI), and make the payment to confirm your booking.',
        //     ],
        //     [
        //         'question' => 'How to upload documents?',
        //         'answer' => 'Upload the front & back pictures of your valid driving license and a government-issued ID (Aadhar Card, Voter ID, or Passport). Enter the correct details and wait for approval. Once approved, confirm your email to complete the process.',
        //     ],
        //     [
        //         'question' => 'Is long-term booking possible?',
        //         'answer' => 'Yes, you can book a vehicle for a minimum of 4 hours and extend it as per your requirement.',
        //     ],
        //     [
        //         'question' => 'How can I start my journey?',
        //         'answer' => 'Reach the vehicle location, inspect it inside and out, enter the Start OTP, upload at least 5 pictures covering the vehicle’s interior, exterior, and odometer, enter the kilometer reading, and you are ready to drive.',
        //     ],
        //     [
        //         'question' => 'How to extend the booking?',
        //         'answer' => 'You can extend your booking 10 minutes before it ends via the app under the "My Booking" → "Running" page by selecting the extension time & date and paying the extra amount.',
        //     ],
        //     [
        //         'question' => 'What happens if I cancel my booking?',
        //         'answer' => 'You can cancel your booking if needed. For further details, refer to our cancellation policy.',
        //     ],
        //     [
        //         'question' => 'When will my journey end?',
        //         'answer' => 'Return the vehicle 10 minutes before the booking period ends. Check for belongings, inspect for damages, ensure it is clean, upload final pictures, enter the End OTP, pay any dues, and complete the booking.',
        //     ],
        //     [
        //         'question' => 'Is there a speed limit?',
        //         'answer' => 'Yes, the speed limit is governed by local traffic laws and our terms and conditions.',
        //     ],
        //     [
        //         'question' => 'Can I extend, cancel, or modify the booking?',
        //         'answer' => 'Yes, you can manage your booking through the app or contact customer support for assistance.',
        //     ],
        //     [
        //         'question' => 'What are the booking criteria and required documents?',
        //         'answer' => 'You need a valid driver\'s license and a government-issued ID to book a vehicle.',
        //     ],
        // ];
        $finalArr['image_sliders'] = $imageSliders;
        $finalArr['why_velriders'] = $whyVelriders;
        $finalArr['faqs'] = $faqs;
            
        return $this->successResponse($finalArr, 'Details are get Successfully');
    }

   public function getInsurancePucRcDetails(Request $request){
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $data = [];
        $vehicleDocDetails = VehicleDocument::where('vehicle_id', $request->vehicle_id)
            ->whereIn('document_type', ['puc_doc', 'insurance_doc', 'rc_doc'])
            ->get(['id_number', 'document_type', 'expiry_date', 'document_image_url']);
        if(isset($vehicleDocDetails) && is_countable($vehicleDocDetails) && count($vehicleDocDetails) > 0){
            $vehicleDocDetails->each(function ($doc) {
                if ($doc->document_image_url) {
                    $doc->document_image_url = asset('images/documents/' . $doc->document_image_url);
                }
            });
        } 
    
        return $this->successResponse($vehicleDocDetails, 'Vehicle Document details are get Successfully');
    }

    public function getVehicleSteps(Request $request){
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        
        $vehicle = Vehicle::select('vehicle_id', 'step_cnt', 'model_id')->where('vehicle_id', $request->vehicle_id)->first();
        if(!$vehicle){
            return $this->errorResponse('Vehicle not found', 404);  
        }
        $vehicle->makeHidden('branch_id', 'year', 'description', 'availability', 'rental_price','extra_km_rate', 'extra_hour_rate', 'availability_calendar', 'commission_percent', 'publish', 'chassis_no', 'cutout_image', 'banner_image', 'banner_images', 'regular_images', 'rating','total_rating', 'trip_count', 'location', 'model', 'model_id', 'vehicle_name', 'category_name', 'host_banner_images', 'host_regular_images', 'city_name', 'city_id');
        
        return $this->successResponse($vehicle, 'Vehicle step count fetched successfully');
    }

}
