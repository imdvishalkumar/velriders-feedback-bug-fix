<?php

namespace App\Http\Controllers\AdminApis;

use App\Http\Controllers\Controller; 
use Illuminate\Http\Request;
use App\Models\{Vehicle, Customer, CustomerDocument, AdminRentalBooking, RentalBooking, BookingTransaction, Payment, Refund, CancelRentalBooking, CustomerReferralDetails, Setting, OfferDate, AdminPenalty, RentalBookingImage};
use App\Rules\CheckCoupon;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Jobs\SendNotificationJob;
use Illuminate\Support\Facades\Http;

class AdminBookingController extends Controller
{
    protected $cashfreeClientId;
    protected $cashfreeClientSecret;
    protected $cashfreeAadharApiUrl;
    protected $cashfreeAadharVerifyApiUrl;
    protected $cashfreePassportApiUrl;
    protected $cashfreeElectionApiUrl;

    public function __construct()
    {
        $this->cashfreeClientId = get_env_variable('CASHFREE_CLIENTID');
        $this->cashfreeClientSecret = get_env_variable('CASHFREE_CLIENTSECRET');
        $this->cashfreeDlApiUrl = config('global_values.cashfree_verification_live_url') . 'verification/driving-license';
        $this->cashfreeAadharApiUrl = config('global_values.cashfree_verification_live_url') . 'verification/offline-aadhaar/otp';
        $this->cashfreeAadharVerifyApiUrl = config('global_values.cashfree_verification_live_url') . 'verification/offline-aadhaar/verify';
        $this->cashfreePassportApiUrl = config('global_values.cashfree_verification_live_url') . 'verification/passport';
        $this->cashfreeElectionApiUrl = config('global_values.cashfree_verification_live_url') . 'verification/voter-id';

        // $this->cashfreeClientId = get_env_variable('CASHFREE_TEST_CLIENTID');
        // $this->cashfreeClientSecret = get_env_variable('CASHFREE_TEST_CLIENTSECRET');
        // $this->cashfreeDlApiUrl = config('global_values.cashfree_verification_test_url').'verification/driving-license';
        // $this->cashfreeAadharApiUrl = config('global_values.cashfree_verification_test_url').'verification/offline-aadhaar/otp';
        // $this->cashfreeAadharVerifyApiUrl = config('global_values.cashfree_verification_test_url').'verification/offline-aadhaar/verify';
        // $this->cashfreePassportApiUrl = config('global_values.cashfree_verification_test_url').'verification/passport';
        // $this->cashfreeElectionApiUrl = config('global_values.cashfree_verification_test_url').'verification/voter-id';
    }

    public function getBookingDropdownData(Request $request){
        $details = [];
        $paymentModes = config('global_values.payment_modes');
        if(isset($vehicleArr) && is_countable($vehicleArr) && count($vehicleArr) > 0){
            $vehicleArr->makeHidden(['cutout_image', 'banner_image', 'banner_images', 'regular_images', 'rating', 'total_rating', 'trip_count', 'location']); 
        }
        $bookingStatuses = config('global_values.booking_statuses');
        $details['paymentModes'] = $paymentModes;
        $details['bookingStatuses'] = $bookingStatuses;

        return $this->successResponse($details, 'Admin activity Log fetched successfully');
    }

    public function getBookings(Request $request){
        $page = $request->input('page');
        $pageSize = $request->input('page_size');
        $orderColumn = $request->order_column ?? '';
        $orderType = $request->order_type ?? '';
        $search = $request->search ?? '';
        $orderTypes = config('global_values.order_types');
        $orderTypes = implode(',', $orderTypes);
        $bookingStatuses = config('global_values.booking_statuses');
        $statusIds = array_column($bookingStatuses, 'id');
        $statues = implode(',', $statusIds);
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:'.$statues,
            'booking_id' => 'nullable|exists:rental_bookings,booking_id',
            'customer_id' => 'nullable|exists:customers,customer_id',
            'vehicle_id' => 'nullable|exists:vehicles,vehicle_id',
            'order_type' => 'nullable|in:'.$orderTypes,
            'pickup_date' => 'nullable|date',
            'return_date' => 'nullable|date|after:pickup_date',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $rentalBooking = AdminRentalBooking::with(['customer', 'vehicle']);
        if($request->status && strtolower($request->status) != 'all' && $request->status != ''){
            $rentalBooking = $rentalBooking->where('status', $request->status);
        }
        if($request->booking_id != ''){
            $rentalBooking = $rentalBooking->where('booking_id', $request->booking_id);
        }
        if($request->customer_id != ''){
            $rentalBooking = $rentalBooking->where('customer_id', $request->customer_id);
        }
        if($request->vehicle_id != ''){
            $rentalBooking = $rentalBooking->where('vehicle_id', $request->vehicle_id);
        }
        if($request->model_id != ''){
            $modelId = $request->model_id;
            $rentalBooking = $rentalBooking->whereHas('vehicle.model', function($q) use($modelId){
                $q->where('model_id', $modelId);
            });
        }
        // Filter by Pickup and Return date
        if($request->pickup_date != '' && $request->return_date != ''){
            $startDate = Carbon::parse($request->pickup_date)->format('Y-m-d');
            $endDate = Carbon::parse($request->return_date)->format('Y-m-d');
            $rentalBooking = $rentalBooking->where('pickup_date', '>=', $startDate)->where('return_date', '<=', $endDate);
        }elseif($request->pickup_date != ''){
            $startDate = Carbon::parse($request->pickup_date)->format('Y-m-d');
            $rentalBooking = $rentalBooking->whereDate('pickup_date', $startDate);
        }elseif($request->return_date != ''){
            $endDate = Carbon::parse($request->return_date)->format('Y-m-d');
            $rentalBooking = $rentalBooking->whereDate('return_date', $endDate);
        }

        // Filter by Start and End date
        if($request->start_date != '' && $request->end_date != ''){
            $startDate = Carbon::parse($request->start_date)->format('Y-m-d');
            $endDate = Carbon::parse($request->end_date)->format('Y-m-d');
            $rentalBooking = $rentalBooking->whereDate('start_datetime', '>=', $startDate)->whereDate('end_datetime', '<=', $endDate);
        }elseif($request->start_date != ''){
            $startDate = Carbon::parse($request->start_date)->format('Y-m-d');
            $rentalBooking = $rentalBooking->whereDate('start_datetime', $startDate);
        }elseif($request->end_date != ''){
            $endDate = Carbon::parse($request->end_date)->format('Y-m-d');
            $rentalBooking = $rentalBooking->whereDate('end_datetime', $endDate);
        }

        $rentalBooking = $rentalBooking->orderBy('created_at', 'desc');

        if ($page !== null && $pageSize !== null) {
            $bookingDetails = $rentalBooking->paginate($pageSize, ['*'], 'page', $page);
            if(isset($bookingDetails) && is_countable($bookingDetails) && count($bookingDetails) > 0){
                $bookingDetails->makeHidden(['calculation_details', 'start_images', 'end_images', 'price_summary', 'admin_button_visibility', 'location']);
                $bookingDetails->each(function ($booking) {
                    $booking->start_journey_otp_status = $this->getStartJourneyOtpStatus($booking->booking_id);
                    $booking->end_journey_otp_status = $this->getEndJourneyOtpStatus($booking->booking_id);
                    $booking->add_penalty_status = $this->getAddPenaltyStatus($booking->status);
                    $booking->add_penalty_text = $this->getAddPenaltyText($booking->status);
                    $booking->penalty_info = $this->getPenaltyInfo($booking->booking_id);
                    $booking->start_kilometers = $booking->start_kilometers != '' && $booking->start_kilometers != null ? $booking->start_kilometers : 0;
                    $booking->end_kilometers = $booking->end_kilometers != '' && $booking->end_kilometers != null ? $booking->end_kilometers : 0;
                    if($booking->customer){
                        $booking->customer->makeHidden('billing_address','business_name', 'gst_number', 'shipping_address', 'is_deleted', 'is_blocked', 'device_token', 'device_id', 'gauth_id', 'gauth_type', 'email_verified_at', 'is_test_user', 'is_guest_user', 'my_referral_code', 'used_referral_code', 'account_holder_name', 'bank_name', 'branch_name', 'city', 'account_no', 'ifsc_code', 'nick_name', 'registered_via', 'passbook_image_url');
                    }
                    if($booking->vehicle){
                       $booking->vehicle->makeHidden('branch_id', 'year', 'description', 'availability', 'rental_price','extra_km_rate', 'extra_hour_rate', 'availability_calendar', 'commission_percent', 'publish', 'chassis_no', 'rating','total_rating', 'trip_count', 'location');
                    }

                    $booking->otp_text = getOtpText($booking->booking_id);
                    if($booking->start_journey_otp_status == false){
                        $getStartOtpText = getStartOtpText($booking->booking_id);
                        $booking->start_journey_otp_text = $getStartOtpText;
                    }else{
                        $booking->start_journey_otp_text = '';
                    }
                    if($booking->end_journey_otp_text == false){
                        $getEndOtpText = getEndOtpText($booking->booking_id);
                        $booking->end_journey_otp_text = $getEndOtpText;
                    }else{
                        $booking->end_journey_otp_text = '';
                    }
                });  
            } 
            $decodedBookings = json_decode(json_encode($bookingDetails->getCollection()->values()), FALSE);
            return $this->successResponse([
                'rental_bookings' => $decodedBookings,
                'pagination' => [
                    'total' => $bookingDetails->total(),
                    'per_page' => $bookingDetails->perPage(),
                    'current_page' => $bookingDetails->currentPage(),
                    'last_page' => $bookingDetails->lastPage(),
                    'from' => ($bookingDetails->currentPage() - 1) * $bookingDetails->perPage() + 1,
                    'to' => min($bookingDetails->currentPage() * $bookingDetails->perPage(), $bookingDetails->total()),
                ]], 'Bookings are get successfully');
        }else{
            $rentalBooking = $rentalBooking->get();
            if(isset($rentalBooking) && is_countable($rentalBooking) && count($rentalBooking) > 0){
                $rentalBooking->makeHidden(['calculation_details', 'start_images', 'end_images', 'price_summary', 'admin_button_visibility', 'location']);
                $rentalBooking->each(function ($booking) {
                    $booking->start_journey_otp_status = $this->getStartJourneyOtpStatus($booking->booking_id);
                    $booking->end_journey_otp_status = $this->getEndJourneyOtpStatus($booking->booking_id);
                    $booking->add_penalty_status = $this->getAddPenaltyStatus($booking->status);
                    $booking->add_penalty_text = $this->getAddPenaltyText($booking->status);
                    $booking->penalty_info = $this->getPenaltyInfo($booking->booking_id);
                    if($booking->customer){
                        $booking->customer->makeHidden('billing_address','business_name', 'gst_number', 'shipping_address', 'is_deleted', 'is_blocked', 'device_token', 'device_id', 'gauth_id', 'gauth_type', 'email_verified_at', 'is_test_user', 'is_guest_user', 'my_referral_code', 'used_referral_code', 'account_holder_name', 'bank_name', 'branch_name', 'city', 'account_no', 'ifsc_code', 'nick_name', 'registered_via', 'passbook_image_url');
                    }
                    if($booking->vehicle){
                       $booking->vehicle->makeHidden('branch_id', 'year', 'description', 'availability', 'rental_price','extra_km_rate', 'extra_hour_rate', 'availability_calendar', 'commission_percent', 'publish', 'chassis_no', 'rating','total_rating', 'trip_count', 'location');
                    }

                    $booking->otp_text = getOtpText($booking->booking_id);
                    if($booking->start_journey_otp_status == false){
                        $getStartOtpText = getStartOtpText($booking->booking_id);
                        $booking->start_journey_otp_text = $getStartOtpText;
                    }else{
                        $booking->start_journey_otp_text = '';
                    }
                    if($booking->end_journey_otp_text == false){
                        $getEndOtpText = getEndOtpText($booking->booking_id);
                        $booking->end_journey_otp_text = $getEndOtpText;
                    }else{
                        $booking->end_journey_otp_text = '';
                    }
                });  
            } 
            $rentalBooking = [
                'rental_bookings' => $rentalBooking,
            ];
            if(isset($rentalBooking) && is_countable($rentalBooking) && count($rentalBooking) > 0){
                return $this->successResponse($rentalBooking, 'Bookings are get successfully');
            }else{
                return $this->errorResponse('Bookings are not found');
            }
        }
    }

    protected function getPenaltyInfo($bookingId){
        $adminPenalty = AdminPenalty::where('booking_id', $bookingId)->where('is_paid', 0)->orderBy('created_at', 'desc')->first();
        $data['penalty_amount'] = $adminPenalty->amount ?? 0;
        $data['penalty_details'] = $adminPenalty->penalty_details ?? '';
        
        return $data;
    }

    public function searchBooking(Request $request){
        $page = $request->input('page');
        $pageSize = $request->input('page_size');
        $search = $request->search ?? '';

        $bookings = AdminRentalBooking::select('booking_id');
        if(isset($search) && $search != ''){
            $bookings->whereRaw('booking_id LIKE ?', ["%$search%"]);
        }
        if ($page !== null && $pageSize !== null) {
            $bookings = $bookings->paginate($pageSize, ['*'], 'page', $page);
            if(isset($bookings) && is_countable($bookings) && count($bookings) > 0){
                foreach($bookings as $key => $val){
                    $val->makeHidden('admin_button_visibility', 'price_summary', 'end_images', 'start_images');
                }
            }
            $decodedBookings = json_decode(json_encode($bookings->getCollection()->values()), FALSE);
            return $this->successResponse([
                'bookings' => $decodedBookings,
                'pagination' => [
                    'total' => $bookings->total(),
                    'per_page' => $bookings->perPage(),
                    'current_page' => $bookings->currentPage(),
                    'last_page' => $bookings->lastPage(),
                    'from' => ($bookings->currentPage() - 1) * $bookings->perPage() + 1,
                    'to' => min($bookings->currentPage() * $bookings->perPage(), $bookings->total()),
                ]], 'Bookings fetched successfully');
        }else{
            $bookings = $bookings->get();
            if(isset($bookings) && is_countable($bookings) && count($bookings) > 0){
                foreach($bookings as $key => $val){
                    $val->makeHidden('admin_button_visibility', 'price_summary', 'end_images', 'start_images');
                }
            }
            $bookings = [
                'bookings' => $bookings,
            ];
            if(isset($bookings) && is_countable($bookings) && count($bookings) > 0){
                return $this->successResponse($bookings, 'Bookings fetched successfully');
            }else{
                return $this->errorResponse('Bookings are not found');
            }
        }

        return $this->successResponse($bookings, 'Booking Ids are get Successfully');
    }

    public function getPenaltyDetails(Request $request){
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|exists:rental_bookings,booking_id',
            'end_km' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $data['admin_penalty'] = '';
        $data['admin_penalty_info'] = '';
        $data['admin_penalty_id'] = '';
        $data['exceed_km_limit'] = '';
        $data['exceed_hour_limit'] = '';
        $booking = RentalBooking::select('booking_id', 'customer_id', 'end_otp', 'vehicle_id' ,'end_datetime', 'pickup_date', 'return_date', 'unlimited_kms', 'start_kilometers', 'end_kilometers', 'start_datetime', 'end_datetime', 'status', 'rental_duration_minutes')->where('booking_id', $request->booking_id)->first();
        if (!$booking) {
            return $this->errorResponse($bookings, 'Invalid Booking');
        }
        $booking->end_kilometers = $request->end_km ?? 0;
        $booking->end_datetime = now();
        $booking->end_otp = null;
        $booking->save();

        $adminPenaltyAmount = $exceededHourPenalty = 0;
        $penaltyInfo = $adminPenaltyId = '';
        $adminPenalties = AdminPenalty::where(['booking_id' => $request->booking_id, 'is_paid' => 0])->where('amount', '>', 0)->first();
        if($adminPenalties != ''){
            $adminPenaltyAmount = $adminPenalties->amount ?? 0;
            $penaltyInfo = $adminPenalties->penalty_details ?? '';    
            $adminPenaltyId = $adminPenalties->id;
        }  
        // Calculate penalties based on trip duration and distance
        $pickupDateTime = Carbon::parse($booking->pickup_date);
        $returnDateTime = Carbon::parse($booking->return_date);
        $tripDurationHours = $returnDateTime->diffInHours($pickupDateTime);
        $exceededKilometerPenalty = 0;
        if (!$booking->unlimited_kms) {
            $kilometerLimit = calculateKmLimit($tripDurationHours);
            $kilometerDifference = $booking->end_kilometers - $booking->start_kilometers;
            $exceededKilometerPenalty = max(0, ($kilometerDifference - $kilometerLimit) * ($booking->vehicle->extra_km_rate ?? 0));    
        }
        $actualTripDurationMinutes = Carbon::parse($booking->end_datetime)->diffInMinutes($booking->start_datetime);
        $endDateTime = Carbon::parse($booking->end_datetime);
        if ($endDateTime->greaterThan($returnDateTime)) {
            // If end_datetime is greater than return_date, calculate the exceeded minutes
            $exceededMinutes = $endDateTime->diffInMinutes($returnDateTime);
            $exceededHourPenalty = max(0, ($exceededMinutes * ($booking->vehicle->extra_hour_rate ?? 0) / 60));
            if($booking && $booking->unlimited_kms == 1){
                $exceededHourPenalty = ($exceededHourPenalty * 1.3);
            }
        }
    
        // Calculate final penalty and refundable amount
        $data['admin_penalty'] = round($adminPenaltyAmount);
        $data['admin_penalty_info'] = $penaltyInfo;
        $data['admin_penalty_id'] = $adminPenaltyId;
        $data['exceed_km_limit'] = round($exceededKilometerPenalty);
        $data['exceed_hour_limit'] = round($exceededHourPenalty);
            
        return $this->successResponse($data, "Penalty details are get successfully");
    }

    protected function getStartJourneyOtpStatus($bookingId){
        $startJourneyOtpStatus = $vehicleTypeStatus = false;
        $booking = AdminRentalBooking::where('booking_id', $bookingId)->first();
        $checkVehicleType = CustomerDocument::where(['customer_id' => $booking->customer_id, 'document_type' => 'dl', 'is_approved' => 'approved'])->first();
        $vehicleType = $checkVehicleType->vehicle_type ?? '';
        $vehicleType = explode('/', $vehicleType);
        $selectedVehicleType = strtolower($booking->vehicle->model->category->vehicleType->name);
        if(in_array($selectedVehicleType, $vehicleType)){
            $vehicleTypeStatus = true;
        }
        if($booking != ''){
            $currentDate = Carbon::now()->setTimezone('Asia/Kolkata');
            $pickupDate = Carbon::parse($booking->pickup_date);
            $returnDate = Carbon::parse($booking->return_date);
            $adjustedPickupDate = $pickupDate->copy()->subMinutes(30);
            $adjustedReturnDate = $returnDate->copy();
            if ($currentDate >= $adjustedPickupDate && $currentDate <= $adjustedReturnDate && strtolower($booking->status) == 'confirmed' && $booking->customer->email_verified_at != NULL & strtolower($booking->customer->documents['dl']) == 'approved' && strtolower($booking->customer->documents['govtid']) == 'approved' && $vehicleTypeStatus) {
                $startJourneyOtpStatus = true;
            }
        }
        return  $startJourneyOtpStatus;
    }

    protected function getEndJourneyOtpStatus($bookingId){
        $endJourneyOtpStatus = false;
        $checkBookingStatus = AdminRentalBooking::select('booking_id', 'status', 'customer_id')->where('booking_id', $bookingId)->first();
        if(strtolower($checkBookingStatus->status) == 'running'){
            // Check if previous booking has any penalties are remaining to paid or not
            $duePenalties = false;
            $getBooking = AdminRentalBooking::where('customer_id', $checkBookingStatus->customer_id)->get(); 
            if(isset($getBooking) && is_countable($getBooking) && count($getBooking) > 0){
                foreach($getBooking as $key => $val){
                    $checkOtherBookingsDuePenalties = BookingTransaction::where(['booking_id' => $val->booking_id, 'type' => 'penalty', 'paid' => 0])->exists();
                    if($checkOtherBookingsDuePenalties){
                        $duePenalties = true;
                        break;
                    }
                }
            }
            // Check if booking is not completed
            $calcDetails = BookingTransaction::where(['booking_id' => $bookingId, 'type' => 'completion', 'is_deleted' => 0, 'paid' => 1])->exists();
            if(!$calcDetails && !$duePenalties){
                $endJourneyOtpStatus = true;
            }
        }
        return  $endJourneyOtpStatus;
    }

    protected function getAddPenaltyStatus($status){
        $penltyStatus = true;
        if(strtolower($status) == 'pending' || strtolower($status) == 'confirmed'){
            $penltyStatus = false;
        }
        return $penltyStatus;
    }

    protected function getAddPenaltyText($status){
        $penaltyText = 'Add Penalty';
        if(strtolower($status) == 'pending' || strtolower($status) == 'confirmed'){
            $penaltyText = 'You can not add due to this booking is not started yet';
        }
        return $penaltyText;
    }

    public function exportBookings(Request $request){
        $data = [];
        $orderColumn = $request->order_column ?? '';
        $orderType = $request->order_type ?? '';
        $search = $request->search ?? '';
        $orderTypes = config('global_values.order_types');
        $orderTypes = implode(',', $orderTypes);
        $bookingStatuses = config('global_values.booking_statuses');
        $statusIds = array_column($bookingStatuses, 'id');
        $statues = implode(',', $statusIds);
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:'.$statues,
            'booking_id' => 'nullable|exists:rental_bookings,booking_id',
            'customer_id' => 'nullable|exists:customers,customer_id',
            'vehicle_id' => 'nullable|exists:vehicles,vehicle_id',
            'order_type' => 'nullable|in:'.$orderTypes,
            'pickup_date' => 'nullable|date',
            'return_date' => 'nullable|date',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $rentalBooking = AdminRentalBooking::with(['customer', 'vehicle']);
        if($request->status && strtolower($request->status) != 'all' && $request->status != ''){
            $rentalBooking = $rentalBooking->where('status', $request->status);
        }
        if($request->booking_id != ''){
            $rentalBooking = $rentalBooking->where('booking_id', $request->booking_id);
        }
        if($request->customer_id != ''){
            $rentalBooking = $rentalBooking->where('customer_id', $request->customer_id);
        }
        if($request->vehicle_id != ''){
            $rentalBooking = $rentalBooking->where('vehicle_id', $request->vehicle_id);
        }
        if($request->pickup_date != '' && $request->return_date != ''){
            $startDate = Carbon::parse($request->pickup_date)->format('Y-m-d H:i');
            $endDate = Carbon::parse($request->return_date)->format('Y-m-d H:i');
            $rentalBooking = $rentalBooking->where('pickup_date', '>=', $startDate)->where('return_date', '<=', $endDate);
        }elseif($request->pickup_date != ''){
            $startDate = Carbon::parse($request->pickup_date)->format('Y-m-d');
            $rentalBooking = $rentalBooking->whereDate('pickup_date', $startDate);
        }elseif($request->return_date != ''){
            $endDate = Carbon::parse($request->return_date)->format('Y-m-d');
            $rentalBooking = $rentalBooking->whereDate('return_date', $endDate);
        }
        $rentalBooking = $rentalBooking->get();
        if(isset($rentalBooking) && is_countable($rentalBooking) && count($rentalBooking) > 0){
            $rentalBooking->makeHidden(['calculation_details', 'start_images', 'end_images', 'price_summary', 'admin_button_visibility', 'location']);
            $rentalBooking->each(function ($booking) {
                if($booking->customer){
                    $booking->customer->makeHidden('profile_picture_url','billing_address','business_name', 'gst_number', 'shipping_address', 'is_deleted', 'is_blocked', 'device_token', 'device_id', 'gauth_id', 'gauth_type', 'email_verified_at', 'is_test_user', 'is_guest_user', 'my_referral_code', 'used_referral_code', 'account_holder_name', 'bank_name', 'branch_name', 'city', 'account_no', 'ifsc_code', 'nick_name', 'registered_via', 'documents', 'passbook_image_url');
                }
                if($booking->vehicle){
                    $booking->vehicle->makeHidden('branch_id', 'year', 'description', 'availability', 'rental_price','extra_km_rate', 'extra_hour_rate', 'availability_calendar', 'commission_percent', 'publish', 'chassis_no', 'cutout_image', 'banner_image', 'banner_images', 'regular_images', 'rating','total_rating', 'trip_count', 'location');
                }
            });  
        }
        $headers = [
            ['key' => 'booking_id', 'name' => 'Booking Id'],
            ['key' => 'customer_details', 'name' => 'Customer Details'],
            ['key' => 'vehicle_details', 'name' => 'Vehicle Details'],
            ['key' => 'pickup_date', 'name' => 'Pickup Date'],
            ['key' => 'return_date', 'name' => 'Return Date'],
            ['key' => 'start_kilometer', 'name' => 'Start Kilometer'],
            ['key' => 'end_kilometer', 'name' => 'End Kilometer'],
            ['key' => 'rental_type', 'name' => 'Rental Type'],
            ['key' => 'status', 'name' => 'Status'],
        ];
        
        $allData = [];
        if(isset($rentalBooking) && is_countable($rentalBooking) && count($rentalBooking) > 0){
            foreach($rentalBooking as $k => $v){
                $customerDetails = $vehicleDetails = $taxDetails = '';
                if (!empty($v) && !empty($v->customer)) {
                    $customerDetails = 'Name: ' . (($v->customer->firstname ?? '') . ' ' . ($v->customer->lastname ?? ''));
                    $customerDetails .= ' Email: ' . ($v->customer->email ?? '');
                    $customerDetails .= ' Mobile: ' . ($v->customer->mobile_number ?? '');
                    $customerDetails .= ' Date of Birth: ' . ($v->customer->dob ?? '');
                    $customerDetails .= ' Driving License Status: ' . ($v->customer->documents['dl'] ?? '');
                    $customerDetails .= ' GovId Status: ' . ($v->customer->documents['govtid'] ?? '');
                }
                if (!empty($v) && !empty($v->vehicle)) {
                    $vehicleDetails = 'Model: ' . ($v->vehicle->vehicle_name ?? '') . ' Color: ' . ($v->vehicle->color ?? '');
                    $vehicleDetails .= ' License Plate: ' . ($v->vehicle->license_plate ?? '');
                }
                $status = $v->paid == 1 ? 'PAID' : 'NOT PAID';

                $allData[$k]['booking_id'] = $v->booking_id;
                $allData[$k]['customer_details'] = $customerDetails;
                $allData[$k]['vehicle_details'] = $vehicleDetails;
                $allData[$k]['pickup_date'] = $v->pickup_date ? date('d-m-Y H:i', strtotime($v->pickup_date)) : '-';
                $allData[$k]['return_date'] = $v->return_date ? date('d-m-Y H:i', strtotime($v->return_date)) : '-';
                $allData[$k]['start_kilometer'] = $v->start_kilometer ?? '-';
                $allData[$k]['end_kilometer'] = $v->end_kilometer ?? '-';
                $allData[$k]['rental_type'] = strtoupper($v->rental_type);
                $allData[$k]['status'] = strtoupper($v->status);
            }
        }

        $allDetails['headers'] = $headers;
        $allDetails['data'] = $allData;
        return $this->successResponse($allDetails, 'Data get successfully');
    }

    public function getOrUpdateBookingInfo(Request $request){
        $bookingInfoUpdateFlag = config('global_values.booking_info_update_flag');
        $bookingInfoUpdateFlag = implode(',', $bookingInfoUpdateFlag);
        $validator = Validator::make($request->all(), [
            'customer_id' => 'nullable|exists:customers,customer_id',
            'vehicle_id' => 'nullable|exists:vehicles,vehicle_id',
            'start_kilometer' => 'nullable|numeric',
            'end_kilometer' => 'nullable|numeric',
            'penalty_amount' => 'nullable|numeric',
            'penalty_details' => 'nullable|max:500',
            'booking_id' => 'nullable|exists:rental_bookings,booking_id',
            'get_penalty' => 'nullable|exists:rental_bookings,booking_id',
            'update_flag' => 'required|in:'.$bookingInfoUpdateFlag,
        ]);
        $validator->sometimes(['customer_id'], 'required', function ($input) {
            return $input->update_flag == 'get_customer_bookings';
        });
        $validator->sometimes(['vehicle_id'], 'required', function ($input) {
            return $input->update_flag == 'get_vehicle_bookings';
        });
        $validator->sometimes(['start_kilometer', 'booking_id'], 'required', function ($input) {
            return $input->update_flag == 'update_start_km';
        });
        $validator->sometimes(['end_kilometer', 'booking_id'], 'required', function ($input) {
            return $input->update_flag == 'update_end_km';
        });
        $validator->sometimes(['booking_id'], 'required', function ($input) {
            return $input->update_flag == 'get_penalty';
        });
        $validator->sometimes(['booking_id'], 'required', function ($input) {
            return $input->update_flag == 'add_penalty';
        });
        $validator->sometimes(['booking_id'], 'required', function ($input) {
            return $input->update_flag == 'start_otp';
        });
        $validator->sometimes(['booking_id'], 'required', function ($input) {
            return $input->update_flag == 'end_otp';
        });
        $validator->sometimes(['booking_id'], 'required', function ($input) {
            return $input->update_flag == 'get_price_summary';
        });
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        if(isset($request->update_flag) && $request->update_flag != ''){
            $bookingId = $request->booking_id;
            $rentalBooking = AdminRentalBooking::where('booking_id', $bookingId)->first();
            $currentDate = Carbon::now()->setTimezone('Asia/Kolkata');
            $returnDate = isset($rentalBooking->return_date)?Carbon::parse($rentalBooking->return_date):'';
            if($request->update_flag ==  'get_customer_bookings'){
                $customerId = $request->customer_id;
                $rentalBookings = $customerInfo = '';
                $details = [];
                if($customerId != ''){
                    $rentalBookings = AdminRentalBooking::where('customer_id', $customerId)->with('vehicle')->get();   
                    $customerDetails = Customer::where('customer_id', $customerId)->first();
                    if($customerDetails->firstname != null && $customerDetails->firstname != null){
                        $customerInfo .= '<div class="col-md-3"><b>Name - </b>'.$customerDetails->firstname .' '.$customerDetails->lastname.'</div> | ';
                    }
                    if($customerDetails->email != null){
                        $customerInfo .= '<div class="col-md-3"><b>Email - </b>' . $customerDetails->email.'</div> | ';
                    }
                    if($customerDetails->mobile_number != null){
                        $customerInfo .= '<div class="col-md-3"><b>Mobile No. - </b>' . $customerDetails->mobile_number.'</div>';
                    }
                    $rentalBookings->makeHidden(['initial_vehicle_id', 'location_id', 'location_from', 'calculation_details', 'start_images', 'end_images', 'price_summary', 'admin_button_visibility', 'location', 'data_json', 'unlimited_kms', 'total_cost', 'amount_paid', 'penalty_details', 'start_otp', 'end_otp', 'tax_rate']);
                    $rentalBookings->each(function ($booking) {
                        if($booking->vehicle){
                            $booking->vehicle->makeHidden('branch_id', 'year', 'description', 'availability', 'rental_price','extra_km_rate', 'extra_hour_rate', 'availability_calendar', 'commission_percent', 'publish', 'chassis_no', 'cutout_image', 'banner_image', 'banner_images', 'regular_images', 'rating','total_rating', 'trip_count', 'location');
                        }
                    }); 
                    $details['bookings'] = $rentalBookings;
                    $details['customerDetails'] = $customerInfo;
                    return $this->successResponse($details, 'Data get successfully');
                }
            }elseif($request->update_flag ==  'get_vehicle_bookings'){
                $vehicleId = $request->vehicle_id;
                $rentalBookings = $vehicleInfo = '';
                $details = [];
                if($vehicleId != ''){
                    $rentalBookings = AdminRentalBooking::where('vehicle_id', $vehicleId)->get();   
                    $vehicleDetails = Vehicle::where('vehicle_id', $vehicleId)->first();
                    if($vehicleDetails->vehicle_name != null && $vehicleDetails->vehicle_name != null){
                        $vehicleInfo .= '<div class="col-md-3"><b>Vehicle Name - </b>'.$vehicleDetails->vehicle_name .'</div> | ';
                    }
                    if($vehicleDetails->color != null){
                        $vehicleInfo .= '<div class="col-md-3"><b>Color - </b>' . $vehicleDetails->color.'</div> | ';
                    }
                    if($vehicleDetails->license_plate != null){
                        $vehicleInfo .= '<div class="col-md-3"><b>License Plate No. - </b>' . $vehicleDetails->license_plate.'</div>';
                    }
                    $rentalBookings->makeHidden(['initial_vehicle_id', 'location_id', 'location_from', 'calculation_details', 'start_images', 'end_images', 'price_summary', 'admin_button_visibility', 'location', 'data_json', 'unlimited_kms', 'total_cost', 'amount_paid', 'penalty_details', 'start_otp', 'end_otp', 'tax_rate']);
                    $rentalBookings->each(function ($booking) {
                        $booking->customer->makeHidden('profile_picture_url','billing_address','business_name', 'gst_number', 'shipping_address', 'is_deleted', 'is_blocked', 'device_token', 'device_id', 'gauth_id', 'gauth_type', 'email_verified_at', 'is_test_user', 'is_guest_user', 'my_referral_code', 'used_referral_code', 'account_holder_name', 'bank_name', 'branch_name', 'city', 'account_no', 'ifsc_code', 'nick_name', 'registered_via', 'documents', 'passbook_image_url');
                    });
                    $details['bookings'] = $rentalBookings;
                    $details['vehicleDetails'] = $vehicleInfo;
                    return $this->successResponse($details, 'Data get successfully');
                }
            }elseif($request->update_flag ==  'update_start_km'){
                if($rentalBooking != ''){
                    $rentalBooking->start_kilometers = $request->start_kilometer;
                    $rentalBooking->save();
                    $object = clone $rentalBooking;
                    logAdminActivities("Start Kilometers Value Updated In Booking History", $object);
                    
                    return $this->successResponse($rentalBooking, 'Data saved successfully');
                }
            }elseif($request->update_flag ==  'update_end_km'){
                if($rentalBooking != ''){
                    $rentalBooking->end_kilometers = $request->end_kilometer;
                    $rentalBooking->save();
                    $object = clone $rentalBooking;
                    logAdminActivities("End Kilometers Value Updated In Booking History", $object);
                    
                    return $this->successResponse($rentalBooking, 'Data saved successfully');
                }
            }elseif($request->update_flag === 'get_penalty'){
                $data['penalty_amt'] = 0;
                $data['penalty_info'] = '';
                if($rentalBooking != '' && $rentalBooking->penalty_details != NULL){
                    $penaltyInfo = json_decode($rentalBooking->penalty_details);
                    $data['penalty_amt'] = $penaltyInfo->amount;
                    $data['penalty_info'] = $penaltyInfo->penalty_details;
                }
                return $this->successResponse($data, 'Penalty details are get Successfully');
            }elseif($request->update_flag ==  'add_penalty'){
                $payableAmt = $request->penalty_amount ?? 0;
                $taxAmt = 0; 
                $adminPenalty = AdminPenalty::where('booking_id', $bookingId)->where('is_paid', 0)->first();
                if($adminPenalty == ''){
                    $adminPenalty = new AdminPenalty();    
                }
                $vehicleCommissionPercent = $rentalBooking->vehicle->commission_percent ?? 0;
                $vehicleCommissionPercent = round($vehicleCommissionPercent);
                $vehicleCommissionAmt = ($payableAmt * $vehicleCommissionPercent) / 100;
                $vehicleCommissionTaxAmt = ($vehicleCommissionAmt * 18) / 100;
                //$payableAmt += round($vehicleCommissionTaxAmt);
                $taxRate = $rentalBooking->tax_rate ?? 0;
                if($taxRate <= 0){
                    $user = Customer::where('customer_id', $rentalBooking->customer_id)->first();
                    $customerGst = $user->gst_number ?? '';    
                    $taxRate = $customerGst ? 0.12 : 0.05;
                }
                $taxAmt = $payableAmt * $taxRate;
                $taxAmt += $vehicleCommissionTaxAmt;
                $adminPenalty->booking_id = $bookingId;
                $adminPenalty->amount = $payableAmt ?? 0;
                $adminPenalty->penalty_details = $request->penalty_details ?? '';
                $adminPenalty->save();

                $bookingTransaction = BookingTransaction::where(['booking_id' => $rentalBooking->booking_id, 'type' => 'penalty', 'paid' => 0])->first();
                if($bookingTransaction == ''){
                    $bookingTransaction = new BookingTransaction();    
                }
                $final_amt = $payableAmt + $taxAmt;
                $final_amt = round($final_amt);
                $bookingTransaction->booking_id = $rentalBooking->booking_id;
                $bookingTransaction->timestamp = now();
                $bookingTransaction->type = 'penalty';
                $bookingTransaction->order_type = 'penalty';
                $bookingTransaction->paid = false;
                $bookingTransaction->total_amount = $payableAmt;
                $bookingTransaction->razorpay_order_id = '';
                $bookingTransaction->razorpay_payment_id = '';
                $bookingTransaction->cashfree_order_id = '';
                $bookingTransaction->cashfree_payment_session_id = '';
                $bookingTransaction->tax_amt = $taxAmt;
                $bookingTransaction->final_amount = $final_amt;
                $bookingTransaction->vehicle_commission_amount = $vehicleCommissionAmt;
                $bookingTransaction->vehicle_commission_tax_amt = $vehicleCommissionTaxAmt;
                $bookingTransaction->save();

                $penalty = $rentalBooking->penalty_details;
                $object = clone $rentalBooking;
                $object->penalty = $penalty;

                logAdminActivities("Admin Penalty Added", $object);
                
                return $this->successResponse($rentalBooking, 'Penalty details are added successfully');
            }elseif($request->update_flag == 'start_otp'){
                $startOtp = mt_rand(1000, 9999);
                $customer = Customer::find($rentalBooking->customer_id);
                if($customer->is_blocked) {
                    return $this->errorResponse("Customer is blocked can not generate OTP");
                }
                $rentalBooking->start_otp = $startOtp;
                $rentalBooking->save();

                $storeObject = clone $rentalBooking;
                $storeObject->startotp = $startOtp;
                logAdminActivities("Generate Start OTP at Booking List", $storeObject);

                return $this->successResponse($startOtp, 'Start OTP sent successfully'.$startOtp);
            }elseif($request->update_flag == 'end_otp'){
                $endOtp = mt_rand(1000, 9999);
                $rentalBooking->end_otp = $endOtp;
                $rentalBooking->save();

                $storeObject = clone $rentalBooking;
                $storeObject->endotp = $endOtp;
                logAdminActivities("Generate end OTP at Booking List", $storeObject);

                return $this->successResponse($endOtp, 'End OTP sent successfully '. $endOtp);
            }elseif($request->update_flag == 'get_price_summary'){
                $html = '';
                $finalPrice = 0; $updatedKey = '';     
                if(is_countable($rentalBooking->price_summary) && count($rentalBooking->price_summary) > 0){
                    $html = '';
                    foreach ($rentalBooking->price_summary as $key => $item){
                        if(strtolower($item['key']) == 'final amount'){
                            $cleanedPrice = str_replace(['₹', ','], '', $item['value']);
                            $finalPrice += $cleanedPrice;
                        }
                        if(strtolower($item['key']) == 'refundable deposit used'){
                            $cleanedPrice = str_replace(['₹', ','], '', $item['value']);
                            $finalPrice += $cleanedPrice;
                        }
                        if($key == 0){
                            $position = strpos($item['key'], "Amount");
                            if ($position !== false) {
                                $position += strlen("Amount");
                                $firstPart = 'Trip Amount';
                                $secondPart = substr($item['key'], $position);
                                $secondPart = str_replace('From', '<br/>From', $secondPart);
                                $updatedKey = $firstPart.'<br/>'.$secondPart;
                            }
                        }
                        else{
                            $updatedKey = $item['key'];
                        }
                        if(strtolower($item['key']) == 'final amount'){
                            $html .= '<strong>Final Price: </strong>₹'.round($finalPrice).'<br/>';
                        }
                        else{
                            $html .= '<strong>'.$updatedKey.': </strong>'.$item['value'].'<br/>';
                        } 
                    }
                }
                return $this->successResponse($html, "Price sumary get Successfully");
            }elseif($request->update_flag == 'get_booking_operation'){
                // END BUTTON VISIBILLITY CONDITION - Booking status == 'running' || status == 'penalty_paid' && !end_journey_otp_status"
                $startJourneyButtonText = $forcefullyStartJourneyButtonText = $extendJourneyButtonText = $forcefullyEndJourneyButtonText = $forcefullyextendJourneyButtonText = $endJourneyButtonText = $cancelBookingButtonText = $forcefullyCancelBookingButtonText = $startJourneyTooltipText = $startJourneyForceFullyLabelText = $extendJourneyTooltipText = $extendJourneyForceFullyLabelText = $endJourneyTooltipText = $endJourneyForceFullyLabelText = $cancelBookingForceFullyLabelText = $cancelBookingTooltipText = '';
                $startJourneyButtonStatus = $extendJourneyButtonStatus = $endJourneyButtonStatus = $cancelBookingButtonStatus = $startJourneyForceFullyLabelStatus = $extendJourneyForceFullyLabelStatus = $endJourneyForceFullyLabelStatus = $cancelJourneyForceFullyLabelStatus = $extendJourneyTooltipStatus = false;
                $data = [];
                // STRAT JOURNEY
                if($rentalBooking->admin_button_visibility['start_journey_button'] == 1 && $returnDate != '' && $currentDate < $returnDate && $rentalBooking->customer->email_verified_at != NULL){
                    $startJourneyButtonText = 'Start Journey';
                    $startJourneyButtonStatus = true;
                }
                elseif(strtolower($rentalBooking->status) == 'running'){
                    $startJourneyButtonText = 'Journey Started';
                }
                else{
                    $startJourneyButtonText = "You can't Start the Journey";
                    if(strtolower($rentalBooking->status) != 'completed'){
                        $startJourneyTooltipText = "You can start the journey only if this user's Government ID & Driver's License (DL) documents are approved, AND the booking pickup time is within 30 minutes of the current time AND Customer email must be verified";
                    }
                }
                if(strtolower($rentalBooking->status) != 'running' && strtolower($rentalBooking->status) != 'completed' && strtolower($rentalBooking->status) != 'pending'){
                    $startJourneyForceFullyLabelText = 'Forcefully allow Start Journey';
                    $startJourneyForceFullyLabelStatus = true;
                    $forcefullyStartJourneyButtonText = 'Start Journey';
                }
                // EXTEND JOURNEY
                if($rentalBooking->admin_button_visibility['end_journey_button'] == 1){
                    $extendJourneyButtonText = 'Extend Booking';
                    $extendJourneyButtonStatus = true;
                }else{
                    $extendJourneyButtonText = "You can't able to Extend Booking this booking";
                    if(strtolower($rentalBooking->status) != 'completed'){
                        $extendJourneyTooltipText = "You can extend the booking only if the user has started their journey and uploaded up to 5 images of the journey's start AND Return date must be greater than current date";
                        $extendJourneyTooltipStatus = true;
                    }
                }
                if($rentalBooking->admin_button_visibility['end_journey_button'] != 1 && strtolower($rentalBooking->status) != 'completed' && strtolower($rentalBooking->status) != 'confirmed' && strtolower($rentalBooking->status) != 'pending'){
                    $extendJourneyForceFullyLabelText = "Forcefully allow Extension";
                    $extendJourneyForceFullyLabelStatus = true;
                    $forcefullyextendJourneyButtonText = 'Extend Booking';
                }
                // END JOURNEY
                if($rentalBooking->admin_button_visibility['end_journey_button'] == 1){
                    $endJourneyButtonText = "End Journey";
                    $endJourneyButtonStatus = true;
                }
                elseif(strtolower($rentalBooking->status) == 'completed'){
                    $endJourneyButtonText = "Journey Ended";
                }
                else{
                    $endJourneyButtonText = "You can't End this Journey";
                    if(strtolower($rentalBooking->status) != 'completed'){
                        $endJourneyTooltipText = "You can End this Journey only if the user has started their journey and uploaded up to 5 images of the journey's start";
                    }
                }
                if($rentalBooking->admin_button_visibility['end_journey_button'] != 1 && strtolower($rentalBooking->status) != 'completed' && strtolower($rentalBooking->status) != 'confirmed' && strtolower($rentalBooking->status) != 'pending'){
                    $endJourneyForceFullyLabelText = "Forcefully allow End Journey";
                    $endJourneyForceFullyLabelStatus = true;
                    $forcefullyEndJourneyButtonText = "End Journey";
                }
                // CANCEL JOURNEY
                if(strtolower($rentalBooking['status']) == 'confirmed' && $rentalBooking['pickup_date'] > now()->format('Y-m-d H:i')){
                    $cancelBookingButtonText = "Cancel Booking";
                    $cancelBookingButtonStatus = true;
                }elseif(strtolower($rentalBooking['status']) == 'canceled'){
                    $cancelBookingButtonText = "This booking is Canceled";
                }else{
                    $cancelBookingButtonText = "You can't Cancel this Booking";
                    $cancelBookingTooltipText = "You can Cancel this booking if Booking status is Confirmed and Pickup date is greater than the Current Date";
                }
                if(strtolower($rentalBooking['status']) != 'pending' && strtolower($rentalBooking['status']) != 'no show' && strtolower($rentalBooking['status']) != 'completed' && strtolower($rentalBooking['status']) != 'failed' && strtolower($rentalBooking['status']) != 'canceled'){
                    $cancelBookingForceFullyLabelText = "Forcefully allow Cancel Booking";
                    $cancelJourneyForceFullyLabelStatus = true;
                    $forcefullyCancelBookingButtonText = "Cancel Booking";
                }

                $data['startJourneyButtonText'] = $startJourneyButtonText;
                $data['startJourneyButtonStatus'] = $startJourneyButtonStatus;
                $data['startJourneyTooltipText'] = $startJourneyTooltipText;
                $data['startJourneyForceFullyLabelText'] = $startJourneyForceFullyLabelText;
                $data['startJourneyForceFullyLabelStatus'] = $startJourneyForceFullyLabelStatus;
                $data['forcefullyStartJourneyButtonText'] = $forcefullyStartJourneyButtonText;
                $data['extendJourneyButtonText'] = $extendJourneyButtonText;
                $data['extendJourneyButtonStatus'] = $extendJourneyButtonStatus;
                $data['extendJourneyTooltipText'] = $extendJourneyTooltipText;
                $data['extendJourneyForceFullyLabelText'] = $extendJourneyForceFullyLabelText;
                $data['extendJourneyForceFullyLabelStatus'] = $extendJourneyForceFullyLabelStatus;
                $data['forcefullyextendJourneyButtonText'] = $forcefullyextendJourneyButtonText;
                $data['endJourneyButtonText'] = $endJourneyButtonText;
                $data['endJourneyButtonStatus'] = $endJourneyButtonStatus;
                $data['endJourneyTooltipText'] = $endJourneyTooltipText;
                $data['endJourneyForceFullyLabelText'] = $endJourneyForceFullyLabelText;
                $data['endJourneyForceFullyLabelStatus'] = $endJourneyForceFullyLabelStatus;
                $data['forcefullyEndJourneyButtonText'] = $forcefullyEndJourneyButtonText;
                $data['cancelBookingButtonText'] = $cancelBookingButtonText;
                $data['cancelBookingButtonStatus'] = $cancelBookingButtonStatus;
                $data['cancelBookingTooltipText'] = $cancelBookingTooltipText;
                $data['cancelBookingForceFullyLabelText'] = $cancelBookingForceFullyLabelText;
                $data['cancelJourneyForceFullyLabelStatus'] = $cancelJourneyForceFullyLabelStatus;
                $data['forcefullyCancelBookingButtonText'] = $forcefullyCancelBookingButtonText;
                
                return $this->successResponse($data, "Booking Operation details are get Successfully");
            }
        }else{
            return $this->errorResponse('Please select Update Flag');
        }
    }

    public function getBookingInfoUpdateFlag(Request $request){
        $bookingInfoUpdateFlag = config('global_values.booking_info_update_flag');
        if(isset($bookingInfoUpdateFlag) && is_countable($bookingInfoUpdateFlag) && count($bookingInfoUpdateFlag) > 0){
            return $this->successResponse($bookingInfoUpdateFlag, 'Please select Update Flag');
        }else{
            return $this->errorResponse('Please select Update Flag');
        }
    }

    public function addBooking(Request $request){
        $paymentModes = config('global_values.payment_modes');
        $paymentModes = implode(',', $paymentModes);
        
        $validator = Validator::make($request->all(), [
            'customer' => 'required|exists:customers,customer_id',
            'vehicle' => 'required|exists:vehicles,vehicle_id',
            'booking_start_date' => 'required|date|after_or_equal:' . Carbon::now()->setTimezone('Asia/Kolkata')->toDateTimeString(),
            'booking_end_date' => 'required|date|after:start_date',
            'coupon_code' => ['nullable','string','exists:coupons,code',new CheckCoupon()],
            'ref_number' => 'required',
            'payment_mode' => 'required|in:'.$paymentModes,
            'unlimited_km' => 'nullable',
            'trip_amt_txt' => 'nullable',
        ]);
         if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $vehicle = Vehicle::where('vehicle_id', $request->vehicle)->first();
        $startDate = Carbon::parse($request->booking_start_date);
        $endDate = Carbon::parse($request->booking_end_date);
        // Check if Vehicle host has restricted this vehicle to book in Night time or not
        $checkVehicleInNightTime = checkVehicleInNightTime($vehicle, $startDate, $endDate);
        if($checkVehicleInNightTime == false){
            return $this->errorResponse('You can not book this vehicle between 12 AM and 6 AM.');
        }
        $tripDurationMinutes = $endDate->diffInMinutes($startDate);
        $tripDurationHours = $tripDurationMinutes / 60;
        $customerId = $request->customer;

        $typeId = $vehicle->model->category->vehicleType->type_id ?? NULL;
        $vehicleCommissionPercent = $vehicle->commission_percent ?? 0;
        $rentalBooking = new AdminRentalBooking();
        $rentalBooking->customer_id = $customerId;
        $rentalBooking->vehicle_id = $request->vehicle;
        $rentalBooking->initial_vehicle_id = $request->vehicle;
        $rentalBooking->pickup_date = date('Y-m-d H:i:s', strtotime($request->booking_start_date));
        $rentalBooking->return_date = date('Y-m-d H:i:s', strtotime($request->booking_end_date));
        $rentalBooking->rental_duration_minutes = $tripDurationMinutes;
        $rentalBooking->unlimited_kms = $request->unlimited_km ? 1 : 0;
        $rentalBooking->total_cost = 0;
        $rentalBooking->status = 'confirmed';
        $rentalBooking->rental_type = $request->rental_type ?? 'default';
        $rentalBooking->save();

        $customerGst = '';
        $user = Customer::where('customer_id', $customerId)->first();
        $customerGst = $user->gst_number ?? '';    
        $taxRate = $customerGst ? 0.12 : 0.05;
        $rentalBooking->tax_rate = $taxRate;
        $rentalBooking->save();

        if($rentalBooking->vehicle_id != ''){
            $responseDetails = getLocationDetails($rentalBooking->vehicle_id);
            if($responseDetails != null){
                $rentalBooking->location_id = (int)$responseDetails['id'] ?? null;
                $rentalBooking->location_from = $responseDetails['from'] ?? null;    
                $rentalBooking->save();
            }
        }
        $rentalPrice = $vehicle->rental_price;
        $checkOffer = OfferDate::where('vehicle_id', $vehicle->vehicle_id)->get();
        if(is_countable($checkOffer) && count($checkOffer) > 0){
            $rentalPrice = getRentalPrice($rentalPrice, $vehicle->vehicle_id);
        }
        $tripAmt = $request->trip_amt_txt ?? 0;
        $calculationDetails = $rentalBooking->computeRentalCostDetails($rentalPrice, $tripDurationMinutes, $request->input('unlimited_km', false), $request->coupon_code, $startDate, $endDate, $typeId, null, 'new_booking', $customerId, $request->payment_mode, $request->ref_number, $vehicleCommissionPercent, $taxRate, $tripAmt, 0, $vehicle->vehicle_id);

        $rentalBooking->total_cost = $calculationDetails['total_amount'];
        $finalAmount = $calculationDetails['final_amount'];
        $rentalBooking->save();
        
        $bookingTransaction = new BookingTransaction();
        $bookingTransaction->booking_id = $rentalBooking->booking_id;
        $bookingTransaction->timestamp = now()->toDateTimeString();
        $bookingTransaction->type = 'new_booking';
        $bookingTransaction->start_date = $rentalBooking->pickup_date;
        $bookingTransaction->end_date = $rentalBooking->return_date;
        $bookingTransaction->unlimited_kms = $request->input('unlimited_km', false);
        $bookingTransaction->rental_price = $rentalPrice;
        $bookingTransaction->trip_duration_minutes = $tripDurationMinutes;
        $bookingTransaction->trip_amount = $calculationDetails['trip_amount'];
        $bookingTransaction->tax_amt = $calculationDetails['tax_amt'];
        $bookingTransaction->coupon_discount = $calculationDetails['coupon_discount'] ?? 0;
        $bookingTransaction->coupon_code = $request->coupon_code;
        $bookingTransaction->coupon_code_id = $calculationDetails['coupon_code_id'] ?? null;
        $bookingTransaction->trip_amount_to_pay = $calculationDetails['trip_amount_to_pay'];
        $bookingTransaction->convenience_fee = $calculationDetails['convenience_fee'];
        $bookingTransaction->total_amount = $calculationDetails['total_amount'];
        $bookingTransaction->refundable_deposit = $calculationDetails['refundable_deposit'] ?? 0;
        $bookingTransaction->final_amount = $calculationDetails['final_amount'];
        $bookingTransaction->order_type = 'new_booking';
        $bookingTransaction->paid = true;
        $bookingTransaction->razorpay_order_id = '';
        $bookingTransaction->razorpay_payment_id = '';
        $bookingTransaction->cashfree_order_id = '';
        $bookingTransaction->cashfree_payment_session_id = '';
        $bookingTransaction->vehicle_commission_amount = $calculationDetails['vehicle_commission_amt'] ?? 0 ;
        $bookingTransaction->vehicle_commission_tax_amt = $calculationDetails['vehicle_commission_tax_amt'] ?? 0;
        $bookingTransaction->save();

        $payment = new Payment();
        $payment->booking_id = $rentalBooking->booking_id;
        $payment->razorpay_order_id = 'admin_booking';
        $payment->amount = $finalAmount;
        $payment->payment_date = now()->toDateString();
        $payment->status = 'captured';
        $payment->payment_mode = $request->payment_mode; 
        $payment->transaction_ref_number = $request->ref_number; 
        $payment->save();
        
        try{
            $customerReferralDetails = CustomerReferralDetails::where(['customer_id' => $customerId, 'reward_type' => 2, 'is_paid' => 0])->whereNull('payable_amount')->first();
            if($customerReferralDetails != '' && $bookingTransaction->final_amount > 0 && $customerReferralDetails->reward_amount_or_percent > 0){
                $setting = Setting::select('id', 'reward_max_discount_amount')->first();
                $payAmt = 0;
                $percent = (float)$customerReferralDetails->reward_amount_or_percent;
                $amount = (float)$bookingTransaction->final_amount;
                if($customerReferralDetails->reward_amount_or_percent > 0 && $setting != '' && $setting->reward_max_discount_amount > 0){
                    $payAmt = min(($amount * $percent) / 100, $setting->reward_max_discount_amount);  
                }
                $payAmt = round($payAmt);
                if($payAmt > 0){
                    $customerReferralDetails->booking_id = $rentalBooking->booking_id;
                    $customerReferralDetails->payable_amount = $payAmt;
                    $customerReferralDetails->save();    
                }
            }
        }catch(Exception $e){}

        $activityDescription = 'New Booking is added by admin user';
        $adminId = auth()->guard('admin')->user()->admin_id;
        logAdminActivities($activityDescription, NULL, $rentalBooking, NULL, $adminId);
        
        return $this->successResponse($rentalBooking, 'Rental Booking created successfully');
    }

    public function getAddBookingCalculation(Request $request){
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,customer_id',
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
            'booking_start_date' => 'required|date|after_or_equal:' . Carbon::now()->setTimezone('Asia/Kolkata')->toDateTimeString(),
            'booking_end_date' => 'required|date|after:start_date',
            'coupon_code' => ['nullable','string','exists:coupons,code',new CheckCoupon()],
            'trip_amt_status' => 'required|in:0,1', //0 means event occurs when any changes are made on customer OR Vehicle OR Start & End Date time, 1 Means Trip Amount get changed manually
            'trip_amount' => 'nullable',
            'unlimited_status' => 'nullable',
            'unlimited_km' => 'nullable',
        ]);
        $validator->sometimes(['trip_amount'], 'required', function ($input) {
            return isset($input->trip_amt_status) && $input->trip_amt_status == 1;
        });
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $tripAmt = NULL;
        if($request->trip_amt_status == 1){ //tripAmtStatus = 0 means event occurs when any changes are made on customer OR Vehicle OR Start & End Date time 
            $tripAmt = $request->trip_amount;
        }
        $unlimitedKm = 0;
        $customerId = $request->customer_id ?? '';
        $vehicleId = $request->vehicle_id ?? '';
        $bookingStartDate = $request->booking_start_date ?? '';
        $bookingEndDate = $request->booking_end_date ?? '';
        $couponCode = $request->coupon_code ?? '';
        if($customerId != '' && $vehicleId != '' && $bookingStartDate != '' && $bookingEndDate != ''){
            $startDate = Carbon::parse($bookingStartDate);
            $endDate = Carbon::parse($bookingEndDate);
            $existingBookings = AdminRentalBooking::where('vehicle_id', $vehicleId)
                ->whereIn('status', ['running', 'confirmed'])
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('pickup_date', [$startDate, $endDate])
                        ->orWhereBetween('return_date', [$startDate, $endDate])
                        ->orWhere(function ($query) use ($startDate, $endDate) {
                            $query->where('pickup_date', '<', $startDate)
                                ->where('return_date', '>', $endDate);
                        });
                })->get();
            if ($existingBookings->isNotEmpty()) {
                $bookingPeriods = $existingBookings->map(function ($booking) {
                    return Carbon::parse($booking->pickup_date)->format('d-m-Y H:i') . ' to ' . Carbon::parse($booking->return_date)->format('d-m-Y H:i');
                })->implode(', ');
                $latestReturnDate = $existingBookings->max('return_date');
                $availableFrom = Carbon::parse($latestReturnDate)->addMinute()->format('d-m-Y H:i');    
                $message = "The vehicle is already booked for the following periods: $bookingPeriods. You can book from $availableFrom onwards.";
                return $this->errorResponse($message);
            }
            if($customerId != null){
                $existingBookingCustomer = AdminRentalBooking::where('customer_id', $customerId)->whereIn('status', ['running', 'confirmed'])
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('pickup_date', [$startDate, $endDate])
                        ->orWhereBetween('return_date', [$startDate, $endDate])
                        ->orWhere(function ($query) use ($startDate, $endDate) {
                            $query->where('pickup_date', '<', $startDate)
                                ->where('return_date', '>', $endDate);
                        });
                })->exists();
                if ($existingBookingCustomer) {
                    $message = "You have already booked another Vehicle for this specified time period.";
                    return $this->errorResponse($message);
                }
            }
            $vehicle = Vehicle::where('vehicle_id', $vehicleId)->first();
            
            // Check if Vehicle host has restricted this vehicle to book in Night time or not
            $checkVehicleInNightTime = checkVehicleInNightTime($vehicle, $startDate, $endDate);
            if($checkVehicleInNightTime == false){
                return $this->errorResponse('You can not book this vehicle between 12 AM and 6 AM.');
            }

            $typeId = $vehicle->model->category->vehicleType->type_id ?? NULL;
            $vehicleCommissionPercent = $vehicle->commission_percent ?? 0;
            $tripDurationMinutes = $endDate->diffInMinutes($startDate);
            $tripDurationHours = $tripDurationMinutes / 60;
            $rentalBooking = new AdminRentalBooking();
            $rentalPrice = $vehicle->rental_price;
            $checkOffer = OfferDate::where('vehicle_id', $vehicle->vehicle_id)->get();
            if(is_countable($checkOffer) && count($checkOffer) > 0){
                $rentalPrice = getRentalPrice($rentalPrice,$vehicle->vehicle_id);
            }

            $customerGst = '';
            $user = Customer::where('customer_id', $customerId)->first();
            $customerGst = $user->gst_number ?? '';    
            $taxRate = $customerGst ? 0.12 : 0.05;
            $unlimitedStatus = $request->unlimited_status ?? 0;
            $unlimitedKm = $request->unlimited_km ?? 0;
            $calculationDetails = $rentalBooking->computeRentalCostDetails($rentalPrice, $tripDurationMinutes, $unlimitedKm, $couponCode, $startDate, $endDate, $typeId, false, 'new_booking', $customerId, NULL, NULL, $vehicleCommissionPercent, $taxRate, $tripAmt, $unlimitedStatus, $vehicleId);
            $kmLimit = $rentalBooking->calculateKmLimit($tripDurationHours);
            $warning = $unlimitedKm ? '' : "Your journey is limited to ".(int)$kmLimit." km. Exceeding this limit will incur additional charges at ₹".$vehicle->extra_km_rate." per km.";
            $data['data'] = $calculationDetails;
            $data['warning'] = $warning;

            return $this->successResponse($data, 'Data get Successfully');
        }
    }

    public function getBookingPreviewData(Request $request){
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|exists:rental_bookings,booking_id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $bookingTransaction = '';
        $rentalBookingDetails = RentalBooking::with(['customer:customer_id,firstname,lastname,dob,email,mobile_number,billing_address,shipping_address,email_verified_at', 'vehicle','payment'])->where('booking_id', $request->booking_id)->first();
        if($rentalBookingDetails != ''){
            $bookingTransaction = BookingTransaction::select('booking_id', 'additional_charges', 'additional_charges_info', 'late_return', 'exceeded_km_limit')->where(['booking_id' => $rentalBookingDetails->booking_id, 'type' => 'completion', 'paid' => 0])->first();
        }
        $vehicles = Vehicle::select('vehicle_id', 'license_plate', 'model_id')->where('availability', 1)->where('is_deleted', 0)->where('vehicle_id', '!=', $rentalBookingDetails->vehicle_id)->with('model.category.vehicleType:type_id,name')->get();

        if (!empty($vehicles) && is_iterable($vehicles)) {
            collect($vehicles)->each(function ($item) {
                $item->makeHidden(['branch_id','rental_price','extra_km_rate','extra_hour_rate','availability_calendar','commission_percent','publish','chassis_no', 'cutout_image', 'banner_image', 'banner_images', 'regular_images','rating','total_rating','trip_count','location']);
                $item->model->category->makeHidden(['name', 'icon' ,'sort', 'icon', 'is_deleted']);
            });
        }
        $customerId = '';
        if($rentalBookingDetails->customer_id != ''){
            $customerId = $rentalBookingDetails->customer_id;
        }
        $currentDate = Carbon::now()->setTimezone('Asia/Kolkata');
        $returnDate = isset($rentalBookingDetails->return_date)?Carbon::parse($rentalBookingDetails->return_date):'';
        if(is_countable($rentalBookingDetails->price_summary) && count($rentalBookingDetails->price_summary) > 0){
            $cDetails = [];
            foreach($rentalBookingDetails->price_summary as $k => $v){
                //$cDetails[$v['key']] = $v['value'];
                $cDetails[] = [
                    'key' => $v['key'],
                    'value' => $v['value'],
                ];
                $rentalBookingDetails->cDetails = $cDetails;    
            }
        }else{
            $rentalBookingDetails->cDetails = '';    
        }

        $bookingId = $request->booking_id ?? '';
        $cancelRentalBookingMessage = '';
        $refundPercent = $refundAmount = $diffInHours = 0;
        $responseDetils = getCancelDetails($bookingId);
        if(is_countable($responseDetils) && count($responseDetils) > 0){
            $refundPercent = $responseDetils['refundPercent'] ?? 0;
            $refundAmount = $responseDetils['refundAmount'] ?? 0;
            $diffInHours = $responseDetils['diffInHours'] ?? 0;
        }
        if($refundAmount > 0){
            $cancelRentalBookingMessage = "Booking ID - # <b>".$bookingId. "</b> is canceled and You will get ₹ ".$refundAmount." Refund Amount ( ".$refundPercent." % )";
        }else{
            $cancelRentalBookingMessage = "Booking ID - # <b>".$bookingId. "</b> is canceled. You can't get any refund as you have cancelled vehicle within 24 Hours";
        }
        $rentalBookingDetails->cancelRentalBookingMessage = $cancelRentalBookingMessage;

        $rentalBookingDetails->vehicle->makeHidden(['availability_calendar','commission_percent','publish','chassis_no', 'cutout_image', 'banner_image', 'banner_images', 'regular_images','rating','total_rating','trip_count']);
 
        $data = [
            'rentalBookingDetails' => $rentalBookingDetails,
            'vehicles' => $vehicles,
            'currentDate' => $currentDate,
            'returnDate' => $returnDate,
            'bookingTransaction' => $bookingTransaction,
        ];

        return $this->successResponse($data, 'Preview page Data get Successfully');
    }

    public function bookingPreviewActionList(Request $request){
        $previewActions = config('global_values.preview_actions');       
        if(isset($previewActions) && is_countable($previewActions) && count($previewActions) > 0){
            $previewActionsArr = [];
            foreach ($previewActions as $key => $value) {
                $previewActionsArr[] = [
                    'label' => $value,
                    'value' => $key
                ];
            }
            return $this->successResponse($previewActionsArr, 'Preview Actions are get successfully');
        }else{
            return $this->errorResponse('Preview Actions are not found');
        }
    }

    public function bookingPreviewActions(Request $request){
        $previewActions = config('global_values.preview_actions');
        $previewActions = implode(',', $previewActions);

        $startDate = Carbon::parse($request->input('extend_return_date'));
        $adjustedStartDate = $startDate->addMinutes(5)->toDateTimeString();
        $validator = Validator::make($request->all(), [
            'preview_action' => 'required|in:'.$previewActions,
            'booking_id' => 'required|exists:rental_bookings,booking_id',
            'vehicle_id' => 'nullable|exists:vehicles,vehicle_id',
            'start_journey_imgs' => 'nullable|array|min:5',
            'start_journey_imgs.*' => 'nullable|mimetypes:image/heic,image/heif,image/jpeg,image/png,image/jpg,image/bmp,image/gif,image/svg,image/webp|max:10000',
            'start_km' => 'nullable|integer|regex:/^\d{1,7}$/',
            'extend_return_date' => 'nullable|date:' . Carbon::now()->setTimezone('Asia/Kolkata')->addMinutes(1)->toDateTimeString(),
            'extend_to_date_time' => 'nullable|date|after:' . $adjustedStartDate,
            'extend_coupon_code' => ['nullable','string','exists:coupons,code',new CheckCoupon()],
            'force_extend_status' => 'nullable|in:0,1',
            'trip_amt' => 'nullable',
            'end_km' => 'nullable|integer|regex:/^\d{1,7}$/',
            'end_journey_imgs' => 'nullable|array|min:5',
            //'end_journey_imgs.*' => 'nullable|mimetypes:image/heic,image/heif,image/jpeg,image/png,image/jpg,image/bmp,image/gif,image/svg,image/webp|max:10000',
            'end_journey_imgs.*' => ['max:10000',
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
        ]);
        $validator->sometimes('vehicle_id', 'required', function ($input) {
            return $input->preview_action == 'update_vehicle';
        });
        $validator->sometimes(['start_journey_imgs', 'start_km'], 'required', function ($input) {
            return $input->preview_action == 'start_journey';
        });
        $validator->sometimes(['extend_return_date', 'extend_to_date_time'], 'required', function ($input) {
            return $input->preview_action == 'extend_price_summary';
        });
        $validator->sometimes(['extend_return_date', 'extend_to_date_time', 'trip_amt'], 'required', function ($input) {
            return $input->preview_action == 'extend_journey' || $input->preview_action == 'get_extend_booking_calculation';
        });
        $validator->sometimes(['end_journey_imgs', 'end_km'], 'required', function ($input) {
            return $input->preview_action == 'end_journey';
        });

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        if($request->preview_action == 'update_vehicle'){
            $vehicleId = $request->vehicle_id;
            $bookingId = $request->booking_id;
            $oldVal = $newVal = '';
            if($vehicleId != '' && $bookingId != ''){
                $rentalBooking = AdminRentalBooking::where('booking_id', $bookingId)->first();
                $oldVal = clone $rentalBooking;
                if($rentalBooking->initial_vehicle_id != ''){
                    $rentalBooking->vehicle_id = $vehicleId;
                    $rentalBooking->save();
                }  
                $newVal = $rentalBooking;
            }
            $description = 'Vehicle changed for booking ID: '.$bookingId.' to vehicle ID: '.$vehicleId;
            logAdminActivities($description, $oldVal, $newVal);

            return $this->successResponse($rentalBooking, 'Vehicle updated Successfully');
        }
        if($request->preview_action == 'start_journey'){
            $rentalBooking = RentalBooking::select('booking_id', 'return_date', 'start_otp', 'status', 'customer_id')->where('booking_id', $request->booking_id)->first();
            if ($rentalBooking == '') {
                return $this->errorResponse('Booking is not found');
            }
            $currentDatetime = Carbon::now()->setTimezone('Asia/Kolkata');
            $rentalBooking->start_otp = null;
            $rentalBooking->status = 'running';
            $rentalBooking->start_kilometers = $request->start_km;
            $rentalBooking->start_datetime = $currentDatetime;
            $rentalBooking->save();
            $imageUrls = [];
            if($request->file('start_journey_imgs')){
                foreach ($request->file('start_journey_imgs') as $key => $image) {
                    $file = $image;
                    $filename = 'start_journey_'.$key.'_'.time() . '.' . $image->getClientOriginalExtension();
                    $file->move(public_path('images/rental_booking_images'), $filename);
                    $imageUrls[] = $filename;
                }
                foreach ($imageUrls as $imageUrl) {
                    $rentalBookingImage = new RentalBookingImage();
                    $rentalBookingImage->booking_id = $rentalBooking->booking_id;
                    $rentalBookingImage->image_type = 'start';
                    $rentalBookingImage->image_url = $imageUrl;
                    $rentalBookingImage->save();
                }
            }
            //Store Admin log
            if(auth()->guard('admin')->check()){
                $adminUserId = auth()->guard('admin')->user()->admin_id;
            }else{
                $adminUserId = 0;
            }
            $activityDescription = 'Journey has been Started for Booking Id -'.$rentalBooking->booking_id.' by Admin User';
            logAdminActivities($activityDescription, NULL, $rentalBooking, NULL, $adminUserId);

            return $this->successResponse($rentalBooking, 'Journey started Successfully');
        }
        if($request->preview_action == 'extend_price_summary'){
            $startDate = Carbon::parse($request->extend_return_date);
            $fetchEndDate = date('Y-m-d H:i', strtotime($request->extend_to_date_time));
            $endDate = Carbon::parse($fetchEndDate);
            $bookingId = $request->booking_id;
            $adjustedStartDate = $startDate->addMinutes(5)->toDateTimeString();
            $currentDateTime = Carbon::now()->setTimezone('Asia/Kolkata')->addMinutes(1);
            $rentalBooking = AdminRentalBooking::select('booking_id', 'status', 'return_date', 'vehicle_id', 'unlimited_kms', 'customer_id', 'tax_rate')->with('vehicle')->where('booking_id', $bookingId)->first();

            // Check if Vehicle host has restricted this vehicle to book in Night time or not
            $vehicle = $rentalBooking->vehicle;
            $checkVehicleInNightTime = checkVehicleInNightTime($vehicle, $startDate, $endDate);
            if($checkVehicleInNightTime == false){
                return $this->errorResponse('You can not book this vehicle between 12 AM and 6 AM.');
            }

            $tripDurationMinutes = $endDate->diffInMinutes($startDate);
            $vehicle = $rentalBooking->vehicle;
            $typeId = $vehicle->model->category->vehicleType->type_id ?? NULL;
            $vehicleCommissionPercent = $vehicle->commission_percent ?? 0;
            $rentalPrice = $vehicle->rental_price;
            $checkOffer = OfferDate::where('vehicle_id', $vehicle->vehicle_id)->get();
            if(is_countable($checkOffer) && count($checkOffer) > 0){
                $rentalPrice = getRentalPrice($rentalPrice, $vehicle->vehicle_id);
            }
            $taxRate = $rentalBooking->tax_rate ?? 0;
            if($taxRate <= 0){
                $user = Customer::where('customer_id', $rentalBooking->customer_id)->first();
                $customerGst = $user->gst_number ?? '';    
                $taxRate = $customerGst ? 0.12 : 0.05;
            }
            $tripAmt = $request->trip_amt ?? null;
            $unlimitedKmStatus = $request->unlimited_km_status ?? 0;
            $rentalCostDetails = $rentalBooking->computeRentalCostDetails(
                $rentalPrice,
                $tripDurationMinutes,
                $rentalBooking->unlimited_kms,
                $request->extend_coupon_code,
                $startDate,
                $endDate,
                $typeId,
                true,
                'extension',
                NULL,
                NULL,
                NULL,
                $vehicleCommissionPercent,
                $taxRate,
                $tripAmt,
                0, 
                $rentalBooking->vehicle_id,
                $unlimitedKmStatus,
            );
            $costDetailArr = [];
            if(isset($rentalCostDetails) && is_countable($rentalCostDetails) && count($rentalCostDetails) > 0){
                $keyArr = ['trip_amount', 'tax_amt', 'convenience_fee', 'coupon_discount', 'total_amount', 'refundable_deposit', 'final_amount'];
                foreach($rentalCostDetails as $k => $v){
                    if(in_array($k, $keyArr)){    
                        $costDetailArr[] = [
                            'key' => str_replace('_',' ', ucwords($k)),
                            'value' => $v
                        ];
                    }
                }
            }
            $kmLimit = calculateKmLimit($tripDurationMinutes / 60);
            $warning = $rentalBooking->unlimited_kms ? '' : "Upon extension, you will receive additional $kmLimit kilometers for your journey. Exceeding this limit will incur additional charges at ₹{$vehicle->extra_km_rate} per km.";
            $trimAmount = $rentalCostDetails['trip_amount'] ?? 0;
            $customerId = '';
            if($rentalBooking->customer_id){
                $customerId = $rentalBooking->customer_id;
            }
            $coupons = getAvailCoupons($startDate, $endDate, $customerId);
            $couponDetails = [];
            if(is_countable($coupons) && count($coupons) > 0){
                foreach ($coupons as $key => $value) {
                    $couponDetails[] = [
                        'coupon_title' => $value['coupon_title'],
                        'coupon_code' => $value['code'],
                        'coupon_id' => $value['id']
                    ];
                }
            }
            //$data['data'] = $rentalCostDetails;
            $data['data'] = $costDetailArr;
            $data['warning'] = $warning;
            $data['trimAmount'] = $trimAmount;
            $data['couponDetails'] = $couponDetails;
            
            return $this->successResponse($data, 'Extend Price summary get Successfully');
        }
        if($request->preview_action == 'extend_journey'){
            $startDate = Carbon::parse($request->extend_return_date);
            $endDate = Carbon::parse($request->extend_to_date_time);
            $bookingId = $request->booking_id;
            $adjustedStartDate = $startDate->addMinutes(5)->toDateTimeString();
            $currentDateTime = Carbon::now()->setTimezone('Asia/Kolkata')->addMinutes(1);
            
            $rentalBooking = AdminRentalBooking::select('booking_id', 'status', 'return_date', 'vehicle_id', 'unlimited_kms', 'customer_id', 'tax_rate')->with('vehicle')->where('booking_id', $bookingId)->first();

            // Check if Vehicle host has restricted this vehicle to book in Night time or not
            $vehicle = $rentalBooking->vehicle;
            $checkVehicleInNightTime = checkVehicleInNightTime($vehicle, $startDate, $endDate);
            if($checkVehicleInNightTime == false){
                return $this->errorResponse('You can not book this vehicle between 12 AM and 6 AM.');
            }

            $user = Customer::where('customer_id', $rentalBooking->customer_id)->first();
            if($user != '' && $user->is_blocked == 1){
                return $this->errorResponse("You are blocked by admin, please contact admin for more details.");
            }
            if($request->force_extend_status != 1){
                if (!$startDate || $startDate->lt($currentDateTime)) {
                    return $this->errorResponse("Existing Return date must be at least 1 minute from now.");
                }
            }
            if ($endDate->lt($adjustedStartDate)) {
                return $this->errorResponse("New Extended date must be after the Return date..");
            }
            if (!$rentalBooking) {
                return $this->errorResponse("The Rental booking is not available.");
            }
            if ($rentalBooking->status != 'confirmed' && $rentalBooking->status != 'running') {
                return $this->errorResponse("The Rental booking is not in a valid state for extension.");
            }
            if ($endDate->lte($startDate)) {
                return $this->errorResponse("The new extended date must be greater than the existing return date.");
            }
        
            $tripDurationMinutes = $endDate->diffInMinutes($startDate);
            $vehicle = $rentalBooking->vehicle;
            $typeId = $vehicle->model->category->vehicleType->type_id ?? NULL;
            $vehicleCommissionPercent = $vehicle->commission_percent ?? 0;
            $rentalPrice = $vehicle->rental_price;
            $checkOffer = OfferDate::where('vehicle_id', $vehicle->vehicle_id)->get();
            if(is_countable($checkOffer) && count($checkOffer) > 0){
                $rentalPrice = getRentalPrice($rentalPrice, $vehicle->vehicle_id);
            }
            $taxRate = $rentalBooking->tax_rate ?? 0;
            if($taxRate <= 0){
                $customerGst = $user->gst_number ?? '';    
                $taxRate = $customerGst ? 0.12 : 0.05;
            }
        
            $tripAmt = $request->trip_amt ?? 0;
            $rentalCostDetails = $rentalBooking->computeRentalCostDetails(
                $rentalPrice,
                $tripDurationMinutes,
                $rentalBooking->unlimited_kms,
                $request->extend_coupon_code,
                $startDate,
                $endDate,
                $typeId,
                true,
                'extension',
                NULL,
                NULL,
                NULL,
                $vehicleCommissionPercent,
                $taxRate,
                $tripAmt,
                0,
                $vehicle->vehicle_id
            );
        
            // Insert into booking_transactions table
            $bookingTransaction = new BookingTransaction();
            $bookingTransaction->booking_id = $rentalBooking->booking_id;
            $bookingTransaction->timestamp = now()->toDateTimeString();
            $bookingTransaction->type = 'extension';
            $bookingTransaction->start_date = $startDate;
            $bookingTransaction->end_date = $endDate;
            $bookingTransaction->unlimited_kms = $rentalBooking->unlimited_kms;
            $bookingTransaction->rental_price = $rentalPrice;
            $bookingTransaction->trip_duration_minutes = $tripDurationMinutes;
            $bookingTransaction->trip_amount = $rentalCostDetails['trip_amount'];
            $bookingTransaction->tax_amt = $rentalCostDetails['tax_amt'];
            $bookingTransaction->coupon_discount = $rentalCostDetails['coupon_discount'] ?? 0;
            $bookingTransaction->coupon_code = $request->extend_coupon_code;
            $bookingTransaction->coupon_code_id = $rentalCostDetails['coupon_code_id'] ?? null;
            $bookingTransaction->trip_amount_to_pay = $rentalCostDetails['trip_amount_to_pay'];
            $bookingTransaction->convenience_fee = $rentalCostDetails['convenience_fee'];
            $bookingTransaction->total_amount = $rentalCostDetails['total_amount'];
            $bookingTransaction->refundable_deposit = $rentalCostDetails['refundable_deposit'] ?? 0;
            $bookingTransaction->final_amount = $rentalCostDetails['final_amount'];
            $bookingTransaction->order_type = 'extension';
            $bookingTransaction->paid = true;
            $bookingTransaction->razorpay_order_id = '';
            $bookingTransaction->razorpay_payment_id = '';
            $bookingTransaction->cashfree_order_id = '';
            $bookingTransaction->cashfree_payment_session_id = '';
            $bookingTransaction->vehicle_commission_amount = $rentalCostDetails['vehicle_commission_amt'] ?? 0;
            $bookingTransaction->vehicle_commission_tax_amt = $rentalCostDetails['vehicle_commission_tax_amt'] ?? 0;
            $bookingTransaction->save();

            $payment = new Payment();
            $payment->booking_id = $rentalBooking->booking_id;
            $payment->razorpay_order_id = 'admin_booking_extension';
            $payment->cashfree_order_id = '';
            $payment->cashfree_payment_session_id = '';
            $payment->amount = $rentalCostDetails['final_amount'];
            $payment->payment_type = 'extension';
            $payment->payment_date = now()->toDateString(); // Adjust this based on your requirements
            $payment->status = 'captured'; 
            $payment->save();

            $rentalBooking->return_date = $bookingTransaction->end_date;
            $rentalBooking->rental_duration_minutes = $rentalBooking->rental_duration_minutes + $bookingTransaction->trip_duration_minutes;
            $rentalBooking->total_cost = $rentalBooking->total_cost + $bookingTransaction->trip_amount;
            $rentalBooking->save();

            //Store Admin log
            if(auth()->guard('admin')->check()){
                $adminUserId = auth()->guard('admin')->user()->admin_id;
            }else{
                $adminUserId = 0;
            }
            $activityDescription = 'Journey has been Extended for Booking Id -'.$rentalBooking->booking_id.' by Admin User';
            logAdminActivities($activityDescription, NULL, $rentalBooking, NULL, $adminUserId);

            try{
                $mobileNo = $rentalBooking->customer->mobile_number;
                if (isset($rentalBooking->customer) && $rentalBooking->customer->is_test_user != 1) {
                $payment->payment_env = 'live';
                }else{
                    $payment->payment_env = 'test';    
                }
                $payment->save();
            }catch(Exception $e){}

            return $this->successResponse($rentalBooking, 'Booking Extended Successfully');
        }
        if($request->preview_action == 'get_extend_booking_calculation'){
            $startDate = Carbon::parse($request->extend_return_date);
            $fetchEndDate = date('Y-m-d H:i', strtotime($request->extend_to_date_time));
            $endDate = Carbon::parse($fetchEndDate);
            $bookingId = $request->booking_id;
            $adjustedStartDate = $startDate->addMinutes(5)->toDateTimeString();
            $currentDateTime = Carbon::now()->setTimezone('Asia/Kolkata')->addMinutes(1);
            $rentalBooking = AdminRentalBooking::select('booking_id', 'status', 'return_date', 'vehicle_id', 'unlimited_kms', 'customer_id', 'tax_rate')->with('vehicle')->where('booking_id', $bookingId)->first();
            $tripDurationMinutes = $endDate->diffInMinutes($startDate);
            $vehicle = $rentalBooking->vehicle;
            $typeId = $vehicle->model->category->vehicleType->type_id ?? NULL;
            $vehicleCommissionPercent = $vehicle->commission_percent ?? 0;
            $rentalPrice = $vehicle->rental_price;
            $checkOffer = OfferDate::where('vehicle_id', $vehicle->vehicle_id)->get();
            if(is_countable($checkOffer) && count($checkOffer) > 0){
                $rentalPrice = getRentalPrice($rentalPrice, $vehicle->vehicle_id);
            }
            $taxRate = $rentalBooking->tax_rate ?? 0;
            if($taxRate <= 0){
                $user = Customer::where('customer_id', $rentalBooking->customer_id)->first();
                $customerGst = $user->gst_number ?? '';    
                $taxRate = $customerGst ? 0.12 : 0.05;
            }
            $tripAmt = $request->trip_amt ?? null;
            $unlimitedKmStatus = $request->unlimited_km_status ?? 0;
            $rentalCostDetails = $rentalBooking->computeRentalCostDetails(
                $rentalPrice,
                $tripDurationMinutes,
                $rentalBooking->unlimited_kms,
                $request->extend_coupon_code,
                $startDate,
                $endDate,
                $typeId,
                true,
                'extension',
                NULL,
                NULL,
                NULL,
                $vehicleCommissionPercent,
                $taxRate,
                $tripAmt,
                0, 
                $rentalBooking->vehicle_id,
                $unlimitedKmStatus,
            );
            $costDetailArr = [];
            if(isset($rentalCostDetails) && is_countable($rentalCostDetails) && count($rentalCostDetails) > 0){
                $keyArr = ['trip_amount', 'tax_amt', 'convenience_fee', 'coupon_discount', 'total_amount', 'refundable_deposit', 'final_amount'];
                foreach($rentalCostDetails as $k => $v){
                    if(in_array($k, $keyArr)){    
                        $costDetailArr[] = [
                            'key' => str_replace('_',' ', ucwords($k)),
                            'value' => $v
                        ];
                    }
                }
            }
            $kmLimit = calculateKmLimit($tripDurationMinutes / 60);
            $warning = $rentalBooking->unlimited_kms ? '' : "Upon extension, you will receive additional $kmLimit kilometers for your journey. Exceeding this limit will incur additional charges at ₹{$vehicle->extra_km_rate} per km.";
            $trimAmount = $rentalCostDetails['trip_amount'] ?? 0;
            $customerId = '';
            if($rentalBooking->customer_id){
                $customerId = $rentalBooking->customer_id;
            }
            $coupons = getAvailCoupons($startDate, $endDate, $customerId);
            $couponDetails = [];
            if(is_countable($coupons) && count($coupons) > 0){
                foreach ($coupons as $key => $value) {
                    $couponDetails[] = [
                        'coupon_title' => $value['coupon_title'],
                        'coupon_code' => $value['code'],
                        'coupon_id' => $value['id']
                    ];
                }
            }
            //$data['data'] = $rentalCostDetails;
            $data['data'] = $costDetailArr;
            $data['warning'] = $warning;
            $data['trimAmount'] = $trimAmount;
            $data['couponDetails'] = $couponDetails;
            
            return $this->successResponse($data, 'Extend Price summary get Successfully');
        }
        if($request->preview_action == 'end_journey'){
            $rentalBooking = RentalBooking::where('booking_id', $request->booking_id)->first();
            if($rentalBooking == ''){
                return $this->errorResponse('Invalid bookings');
            }
            $adminPenalty = $request->admin_penalty ?? 0;
            $exceedKmLimit = $request->exceed_km_limit ?? 0;
            $exceedHourLimit = $request->exceed_hours_limit ?? 0;
            $adminPenaltyInfo = $request->admin_penalty_info ?? '';
            $adminPenaltyId = $request->admin_penalty_id ?? '';
            $taxRate = $rentalBooking->tax_rate ?? 0;
            $user = Customer::where('customer_id', $rentalBooking->customer_id)->first();
            $customerGst = $user->gst_number ?? '';    
            if($taxRate <= 0){
                $taxRate = $customerGst ? 0.12 : 0.05;
            }
            $totalPenalty = (float)$adminPenalty + (float)$exceedKmLimit + (float)$exceedHourLimit;
            $vehicleCommissionTaxAmt = $vehicleCommissionAmt = 0;
            if($totalPenalty > 0){
                $vehicleCommissionPercent = $rentalBooking->vehicle->commission_percent ?? 0;
                if($vehicleCommissionPercent > 0){
                    $vehicleCommissionAmt = ($totalPenalty * $vehicleCommissionPercent) / 100;
                    $vehicleCommissionAmt = round($vehicleCommissionAmt);
                    $vehicleCommissionTaxAmt = ($vehicleCommissionAmt * 18) / 100;    
                }
            }
            $taxAmt = $totalPenalty * $taxRate;
            $taxAmt += $vehicleCommissionTaxAmt;
            $totalPenalty = $totalPenalty + $taxAmt;
            // Retrieve refundable deposit from booking_transactions
            $initialTransaction = BookingTransaction::where('booking_id', $rentalBooking->booking_id)->where('type', 'new_booking')->first();
            $refundable_deposit = $initialTransaction->refundable_deposit ?? 0;
            $refundable_deposit_used = 0;
            $amount_to_pay = 0;
            $remainingRefundableAmount = ($refundable_deposit - $totalPenalty);
            if ($remainingRefundableAmount >= 0) {
                $refundable_deposit_used = $totalPenalty;
            } else {
                $refundable_deposit_used = $refundable_deposit;
                $amount_to_pay = abs($remainingRefundableAmount);
                $remainingRefundableAmount = 0;
            }
            $payNow = $amount_to_pay > 0;
            $existingCompletionTransaction = BookingTransaction::where('booking_id', $rentalBooking->booking_id)->where('type', 'completion')->first();
            // Create or update completion transaction
            $completionData = [
                'booking_id' => $rentalBooking->booking_id,
                'timestamp' => now(),
                'type' => 'completion',
                'late_return' => $exceedHourLimit,
                'exceeded_km_limit' => $exceedKmLimit,
                'additional_charges' => $adminPenalty,
                'additional_charges_info' => $adminPenaltyInfo,
                'refundable_deposit_used' => $refundable_deposit_used,
                'refundable_deposit' => $payNow ? 0 : $remainingRefundableAmount,
                'tax_amt' => round($taxAmt, 2),
                'amount_to_pay' => round($amount_to_pay, 2),
                'order_type' => 'completion',
                'paid' => 1,
                'razorpay_order_id' => '',
                'razorpay_payment_id' => '',
                'from_refundable_deposit' => !$payNow,
                'is_deleted' => 0,
                'vehicle_commission_amount' => $vehicleCommissionAmt,
                'vehicle_commission_tax_amt' => $vehicleCommissionTaxAmt,
            ];
            if ($existingCompletionTransaction) {
                $existingCompletionTransaction->update($completionData);
            } else {
                BookingTransaction::create($completionData);
            }
            $imageUrls = [];
            if($request->file('end_journey_imgs')){
                foreach ($request->file('end_journey_imgs') as $key => $image) {
                    $file = $image;
                    $filename = 'end_journey_'.$key.'_'.time() . '.' . $image->getClientOriginalExtension();
                    $file->move(public_path('images/rental_booking_images'), $filename);
                    $imageUrls[] = $filename;
                }
                foreach ($imageUrls as $imageUrl) {
                    $rentalBookingImage = new RentalBookingImage();
                    $rentalBookingImage->booking_id = $rentalBooking->booking_id;
                    $rentalBookingImage->image_type = 'end';
                    $rentalBookingImage->image_url = $imageUrl;
                    $rentalBookingImage->save();
                }
            }
            // Update booking status if no payment is required
            $fileName = 'customer_agreements_'.$rentalBooking->customer_id.'_'.$rentalBooking->booking_id.'.pdf';
            $filePath = public_path().'/customer_aggrements/'.$fileName;
            if(file_exists($filePath)){ 
                unlink($filePath);
            }
            $adminPenaltyObj = '';
            if($adminPenaltyId != ''){
                $adminPenaltyObj = AdminPenalty::where(['id' => $adminPenaltyId, 'is_paid' => 0])->first();
            }
            if($adminPenaltyObj == ''){
                $adminPenaltyObj = new AdminPenalty();
                $adminPenaltyObj->booking_id = $rentalBooking->booking_id;
            }
            $adminPenaltyObj->amount = $adminPenalty;
            $adminPenaltyObj->penalty_details = $adminPenaltyInfo;
            $adminPenaltyObj->is_paid = 1;
            $adminPenaltyObj->save();    
            $penaltyTransaction = BookingTransaction::where(['booking_id' => $rentalBooking->booking_id, 'type' => 'penalty', 'paid' => 0])->first();
            if($penaltyTransaction != ''){
                $penaltyTransaction->paid = 1;
                $penaltyTransaction->tax_amt = 0;
                $penaltyTransaction->total_amount = 0;
                $penaltyTransaction->final_amount = 0;
                $penaltyTransaction->save();
            }

            //Store Admin log
            if(auth()->guard('admin')->check()){
                $adminUserId = auth()->guard('admin')->user()->admin_id;
            }else{
                $adminUserId = 0;
            }
            $activityDescription = 'Journey has been Ended for Booking Id -'.$rentalBooking->booking_id.' by Admin User';
            logAdminActivities($activityDescription, NULL, $rentalBooking, NULL, $adminUserId);

            //Send Email and Push notifications to the User
            SendNotificationJob::dispatch($rentalBooking->customer_id, $rentalBooking->booking_id, 'completion')->onQueue('emails'); 
            $rentalBooking->status = 'completed'; 
            $rentalBooking->save(); 

            $lastSequence = AdminRentalBooking::max('sequence_no');
            $rentalBooking->sequence_no = $lastSequence + 1;
            $rentalBooking->end_kilometers = $request->end_km ?? 0;
            $rentalBooking->end_datetime = now();
            $rentalBooking->end_otp = null;
            $rentalBooking->save();

            return $this->successResponse($rentalBooking, 'Journey ended Successfully');
        }
        if($request->preview_action == 'cancel_journey'){
            $bookingId = $request->booking_id;
            $refundPercent = $refundAmount = $diffInHours = 0;
            $responseDetils = getCancelDetails($bookingId);
            if(is_countable($responseDetils) && count($responseDetils) > 0){
                $refundPercent = $responseDetils['refundPercent'] ?? 0;
                $refundAmount = $responseDetils['refundAmount'] ?? 0;
                $diffInHours = $responseDetils['diffInHours'] ?? 0;
            }
            $checkBooking = CancelRentalBooking::where(['booking_id' => $bookingId, 'hours_diffrence' => $diffInHours, 'refund_percent' => $refundPercent, 'refund_amount' => $refundAmount])->exists();
            if($checkBooking){
                return $this->errorResponse("This booking is already Canceled");
            }
            $cancelRentalBooking = new CancelRentalBooking();
            $cancelRentalBooking->booking_id = $bookingId;
            $cancelRentalBooking->hours_diffrence = $diffInHours;
            $cancelRentalBooking->refund_percent = $refundPercent;
            $cancelRentalBooking->refund_amount = $refundAmount;
            $cancelRentalBooking->save();
            $rBooking = AdminRentalBooking::where('booking_id', $bookingId)->first();
            if($rBooking != ''){
                $rBooking->status = 'canceled';
                $rBooking->save();
            }
            return $this->successResponse($cancelRentalBooking, "Booking Cancel Successfully");
        }
        if($request->preview_action == 'undo_cancel'){
            $bookingId = $request->booking_id;
            if($bookingId != ''){
                $booking = RentalBooking::where('booking_id', $bookingId)->first();
                $cancelRentalBooking = CancelRentalBooking::where('booking_id', $bookingId)->first();
                if($booking != '' && $cancelRentalBooking != ''){
                    $booking->status = 'confirmed';
                    $booking->save();
                    $cancelRentalBooking->is_deleted = 1;
                    $cancelRentalBooking->save();
                }
            }
            return $this->successResponse($cancelRentalBooking, "Cancellation of the booking has been successfully reversed.");
        }
    }
}