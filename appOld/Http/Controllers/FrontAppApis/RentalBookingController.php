<?php

namespace App\Http\Controllers\FrontAppApis;

use App\Http\Controllers\Controller;
use App\Models\{RentalBooking, Branch, BookingTransaction, RentalBookingImage, RentalReview, Customer, CustomerDocument, Payment, Vehicle, CompanyDetail, Refund, CancelRentalBooking, Coupon, TripAmountCalculationRule, CarHostPickupLocation, NotificationLog, Setting, UserLocationDetail, AdminPenalty, CustomerReferralDetails, OfferDate, CarEligibility, VehiclePriceDetail};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Razorpay\Api\Api;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\PushNotificationService;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Rules\CheckCoupon;
use Illuminate\Support\Facades\Log;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Jobs\SendNotificationJob;

class RentalBookingController extends Controller
{
    protected $pushNotificationService;
    // protected $setting;
    protected $userAuthDetails;
    protected $currentDateTime;
    protected $cashfreeApiVersion;
    protected $cashfreeTestClientId;
    protected $cashfreeTestClientSecret;
    protected $cashfreeSandBoxUrl;
    protected $cashfreeClientId;
    protected $cashfreeClientSecret;
    protected $cashfreeLiveUrl;

    public function __construct(PushNotificationService $pushNotificationService)
    {
        $this->pushNotificationService = $pushNotificationService;
        $this->userAuthDetails = Auth::guard('api')->user();
        $this->currentDateTime = Carbon::now()->setTimezone('Asia/Kolkata');
        $this->cashfreeApiVersion = '2023-08-01';
        $this->cashfreeTestClientId = get_env_variable('CASHFREE_PAYMENT_TEST_CLIENTID');
        $this->cashfreeTestClientSecret = get_env_variable('CASHFREE_PAYMENT_TEST_CLIENTSECRET');
        $this->cashfreeSandBoxUrl = "https://sandbox.cashfree.com/pg/orders";
        $this->cashfreeClientId = get_env_variable('CASHFREE_PAYMENT_LIVE_CLIENTID');
        $this->cashfreeClientSecret = get_env_variable('CASHFREE_PAYMENT_LIVE_CLIENTSECRET');
        $this->cashfreeLiveUrl = "https://api.cashfree.com/pg/orders";
    }

    public function getPriceDetails(Request $request)
    {
        $cId = Auth::guard('api')->check() ? $this->userAuthDetails->customer_id : '';
        $startDate = Carbon::parse($request->input('start_date'));
        $adjustedStartDate = $startDate->addMinutes(5)->toDateTimeString();

        $setting = Setting::first();
        $bookingGap = $setting->booking_gap ?? 30;

        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
            //'from_branch_id' => 'required|exists:branches,branch_id',
            //'to_branch_id' => 'required|exists:branches,branch_id',
            'start_date' => 'required|date|after_or_equal:' . Carbon::now()->setTimezone('Asia/Kolkata')->subHour()->toDateTimeString(),
            'end_date' => 'required|date|after:' . $adjustedStartDate,
            'coupon_code' => ['nullable', 'string', 'exists:coupons,code', new CheckCoupon()],
            'rental_type' => 'nullable|string',
        ], [
            'start_date.after_or_equal' => 'The Start Date field must be a date after or equal to ' . Carbon::now()->setTimezone('Asia/Kolkata')->subHour()->format('d-m-Y H:i'),
            'end_date.after' => 'The End Date field must be a date after to ' . $startDate->addMinutes(5)->format('d-m-Y H:i'),
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $vehicleId = $request->vehicle_id;
        $user = Customer::where('customer_id', $cId)->first();

        //CHECK IF THE VEHICLE IS AVAILABLE IN VEHICLE TABLE OR NOT
        $vehicle = Vehicle::select('vehicle_id', 'model_id', 'availability', 'availability_calendar', 'rental_price', 'extra_hour_rate', 'extra_km_rate', 'commission_percent', 'vehicle_created_by', 'deposit_amount', 'is_deposit_amount_show')->with('model.category', 'model.category.vehicleType')->where('vehicle_id', $request->vehicle_id)->where('availability', true)->first();
        if ($vehicle == '') {
            return $this->errorResponse('The vehicle is not available.');
        }

        // CHECK IF VEHICLE HOST HAS RESTRICTED THIS VEHICLE TO BOOK IN NIGHT TIME OR NOT
        $checkVehicleInNightTime = checkVehicleInNightTime($vehicle, $startDate, $endDate);
        if ($checkVehicleInNightTime == false) {
            return $this->errorResponse('You can not book this vehicle between 12 AM and 6 AM.');
        }

        //CHECKED IF THIS VEHICLE iS HOLD BY ADMIN OR NOT
        if (!empty($vehicle->availability_calendar)) {
            $availabilityRes = checkAvailabilityDates($vehicle->availability_calendar, $startDate, $endDate);
            if ($availabilityRes != '') {
                return $this->errorResponse($availabilityRes);
            }
        }
        //CHECKED IF THIS VEHICLE ID BOOKED OR NOT WITH OTHER ORDER
        $checkedBookedVehicele = checkedBookedVehicele($vehicleId, $startDate, $endDate, $bookingGap);
        if ($checkedBookedVehicele != '') {
            return $this->errorResponse($checkedBookedVehicele);
        }
        //CHECKED IF USER HAS BOOKED ANOTHER VEHICLE ON SAME TIME OR NOT
        $checkedUserBookedVehicle = checkedUserBookedVehicle($cId, $bookingGap, $startDate, $endDate);
        if ($checkedUserBookedVehicle != '') {
            return $this->errorResponse($checkedUserBookedVehicle);
        }

        $typeId = $vehicle->model->category->vehicleType->type_id ?? NULL;
        $vehicleCommissionPercent = $vehicle->commission_percent ?? 0;
        // Calculate trip duration in hours
        $tripDurationMinutes = $endDate->diffInMinutes($startDate);
        $tripDurationHours = $tripDurationMinutes / 60;

        $rentalPrice = $vehicle->rental_price;
        $checkOffer = OfferDate::where('vehicle_id', $vehicle->vehicle_id)->get();
        if (is_countable($checkOffer) && count($checkOffer) > 0) {
            $rentalPrice = getRentalPrice($rentalPrice, $vehicle->vehicle_id);
        }
        $rentalBooking = new RentalBooking();
        $customerGst = '';
        $customerGst = $user->gst_number ?? '';
        $taxRate = $customerGst ? 0.12 : 0.05;
        $rentalBooking->tax_rate = $taxRate;
        $rentalCostDetails = $rentalBooking->computeRentalCostDetails($rentalPrice, $tripDurationMinutes, $request->input('unlimited_kms', false), $request->coupon_code, $startDate, $endDate, $typeId, null, 'new_booking', $vehicleCommissionPercent, $taxRate, $vehicleId);

        // Calculate and set warning message about km limit
        $kmLimit = calculateKmLimit($tripDurationHours);

        $perMinRate = round($vehicle->extra_hour_rate / 60, 2);
        if ($request->unlimited_kms == true) {
            $perMinRate = ($perMinRate * 1.3);
        }
        $summary_message = $request->input('unlimited_kms', false) ? '' : "Your journey is limited to " . (int) $tripDurationMinutes . " Minutes. Exceeding this limit will incur additional charges at ₹" . $perMinRate . " per Minutes.";
        $priceSummary = $rentalBooking->generatePriceSummary($rentalCostDetails);
        $priceSummary['summary_message'] = '';

        $unlimitedKms = $request->input('unlimited_kms', false);
        $kmLimitText = $unlimitedKms ? "unlimited distance" : (int) $kmLimit . " km";
        $extraKmChargeText = $unlimitedKms ? "" : " and <span style='color: #e74c3c; font-weight: bold;'>₹" . $vehicle->extra_km_rate . " per km</span>";
        $refundableAmount = round($rentalPrice * 2.5 * 2, 2);
        //$refundableDeposit = "A refundable deposit of ₹" . $refundableAmount . " is required at the time of pickup.";
        $refundableDeposit = "";
        if ($vehicle->deposit_amount && $vehicle->is_deposit_amount_show == 1) {
            $depositAmt = "₹" . $vehicle->deposit_amount;
            $refundableDeposit = "You need to pay deposit " . $depositAmt . " at the time of pickup Vehicle";
        }

        $combined_message = "
            <div style='font-family: Arial, sans-serif; color: #333;'>
                <p style='margin: 0px 0; font-size: 14px;'>
                    Your journey is limited to 
                    <span style='color: #2c3e50; font-weight: bold;'>" . (int) $tripDurationMinutes . " minutes</span> 
                    and 
                    <span style='color: #2c3e50; font-weight: bold;'>" . $kmLimitText . "</span>.
                </p>
                <p style='margin: 5px 0; font-size: 14px;'>
                    Exceeding these limits will incur additional charges at 
                    <span style='color: #e74c3c; font-weight: bold;'>₹" . $perMinRate . " per minute</span>" . $extraKmChargeText . ".
                </p>
                <p style='margin: 5px 0; font-size: 14px; color: #2980b9; font-weight: bold;'>
                    " . $refundableDeposit . "
                </p>
                <p style='margin: 5px 0; font-size: 12px; color: #95a5a6;'>
                    Thank you for choosing our service! Have a safe and enjoyable journey.
                </p>
            </div>";

        $priceSummary['warning'] = $combined_message;
        $priceSummary['confirm_booking_message'] = "To complete your reservation, make sure you have a valid identification document, such as a <span style='color: red;'>Government ID and Driving License</span>. Please note that you will need to present this document at the time of vehicle pick-up.";

        return $this->successResponse($priceSummary);
    }

    public function store(Request $request)
    {
        $startDate = Carbon::parse($request->input('start_date'));
        $adjustedStartDate = $startDate->addMinutes(5)->toDateTimeString();
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
            //'from_branch_id' => 'required|exists:branches,branch_id',
            //'to_branch_id' => 'required|exists:branches,branch_id',
            'start_date' => 'required|date|after_or_equal:' . Carbon::now()->setTimezone('Asia/Kolkata')->subHour()->toDateTimeString(),
            'end_date' => 'required|date|after:' . $adjustedStartDate,
            //'coupon_code' => 'nullable|string|exists:coupons,code',
            'coupon_code' => ['nullable', 'string', 'exists:coupons,code', new CheckCoupon()],
            'rental_type' => 'nullable|string',
        ]);
        // Check if validation fails
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        // Retrieve vehicle details
        $vehicle = Vehicle::select('vehicle_id', 'model_id', 'availability', 'rental_price', 'availability_calendar', 'commission_percent', 'vehicle_created_by')->with('model.category', 'model.category.vehicleType')->where('vehicle_id', $request->vehicle_id)->where('availability', true)->first();
        $rentalPrice = $vehicle->rental_price;
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        // Check if Vehicle host has restricted this vehicle to book in Night time or not
        $checkVehicleInNightTime = checkVehicleInNightTime($vehicle, $startDate, $endDate);
        if ($checkVehicleInNightTime == false) {
            return $this->errorResponse('You can not book this vehicle between 12 AM and 6 AM.');
        }

        $tripDurationMinutes = $endDate->diffInMinutes($startDate);
        $tripDurationHours = $tripDurationMinutes / 60;
        $customerId = $this->userAuthDetails->customer_id;
        $mobileNo = $this->userAuthDetails->mobile_number;

        $setting = Setting::first();
        $bookingGap = $setting->booking_gap ?? 30;
        $checkOffer = OfferDate::where('vehicle_id', $vehicle->vehicle_id)->get();
        if (is_countable($checkOffer) && count($checkOffer) > 0) {
            $rentalPrice = getRentalPrice($rentalPrice, $vehicle->vehicle_id);
        }
        $user = Customer::where('customer_id', $customerId)->first();
        //CHECK IF THE VEHICLE IS AVAILABLE IN VEHICLE TABLE OR NOT
        if ($vehicle == '') {
            return $this->errorResponse('The vehicle is not available.');
        }
        //CHECKED IF THIS VEHICLE iS HOLD BY ADMIN OR NOT
        if (!empty($vehicle->availability_calendar)) {
            $availabilityRes = checkAvailabilityDates($vehicle->availability_calendar, $startDate, $endDate);
            if ($availabilityRes != '') {
                return $this->errorResponse($availabilityRes);
            }
        }
        //CHECKED IF THIS VEHICLE ID BOOKED OR NOT WITH OTHER ORDER
        $checkedBookedVehicele = checkedBookedVehicele($vehicle->vehicle_id, $startDate, $endDate, $bookingGap);
        if ($checkedBookedVehicele != '') {
            return $this->errorResponse($checkedBookedVehicele);
        }
        //CHECKED IF USER HAS BOOKED ANOTHER VEHICLE ON SAME TIME OR NOT
        $checkedUserBookedVehicle = checkedUserBookedVehicle($customerId, $bookingGap, $startDate, $endDate);
        if ($checkedUserBookedVehicle != '') {
            return $this->errorResponse($checkedUserBookedVehicle);
        }

        $typeId = $vehicle->model->category->vehicleType->type_id ?? NULL;
        $vehicleCommissionPercent = $vehicle->commission_percent ?? 0;

        $rentalBooking = new RentalBooking();
        $customerGst = '';
        $customerGst = $user->gst_number ?? '';
        $taxRate = $customerGst ? 0.12 : 0.05;
        $rentalBooking->tax_rate = $taxRate;

        $rentalCostDetails = $rentalBooking->computeRentalCostDetails($rentalPrice, $tripDurationMinutes, $request->input('unlimited_kms', false), $request->coupon_code, $startDate, $endDate, $typeId, null, 'new_booking', $vehicleCommissionPercent, $taxRate, $vehicle->vehicle_id);

        // Create the rental booking
        $rentalBooking->customer_id = $customerId;
        $rentalBooking->vehicle_id = $request->vehicle_id;
        $rentalBooking->initial_vehicle_id = $request->vehicle_id;
        $rentalBooking->pickup_date = $request->start_date;
        $rentalBooking->return_date = $request->end_date;
        $rentalBooking->rental_duration_minutes = $tripDurationMinutes;
        $rentalBooking->unlimited_kms = $request->input('unlimited_kms', false);
        $rentalBooking->total_cost = $rentalCostDetails['total_amount'];
        $rentalBooking->status = 'pending'; // or any other default status
        $rentalBooking->rental_type = $request->rental_type ?? 'default'; // or any other default rental type
        $rentalBooking->save();

        if ($rentalBooking->vehicle_id != '') {
            $responseDetails = getLocationDetails($rentalBooking->vehicle_id);
            if ($responseDetails != null) {
                $rentalBooking->location_id = (int) $responseDetails['id'] ?? null;
                $rentalBooking->location_from = $responseDetails['from'] ?? null;
                $rentalBooking->save();
            }
        }
        $setting = Setting::first();
        $finalAmount = round($rentalCostDetails['final_amount'], 2);
        $finalAmount = (int) $finalAmount;

        $razorpayOrderID = $cashfreeOrderId = $cashfreepaymentSessionId = '';
        if ($setting && $setting->payment_gateway_type != '') {
            if (strtolower($setting->payment_gateway_type) == 'razorpay') {
                $razorpayOrder = $this->createOrder(strval($rentalBooking->booking_id), $finalAmount);
                //Below code will check if specified amount is valid by razorpay or not
                if ($razorpayOrder && isset($razorpayOrder['status_code']) && strtoupper($razorpayOrder['status_code']) == 'BAD_REQUEST_ERROR') {
                    if (isset($razorpayOrder['status_message']) && $razorpayOrder['status_message'] != '') {
                        RentalBooking::where('booking_id', $rentalBooking->booking_id)->when(RentalBooking::where('booking_id', $rentalBooking->booking_id)->exists(), function ($query) {
                            //$query->delete();
                        });
                        return $this->errorResponse($razorpayOrder['status_message']);
                    }
                }
                $razorpayOrderID = $razorpayOrder->id;

            } elseif (strtolower($setting->payment_gateway_type) == 'cashfree') {

                $cashfreeOrder = $this->createCashfreeOrder(strval($rentalBooking->booking_id), $finalAmount);

                //Below code handle failed status codes
                if ($cashfreeOrder && isset($cashfreeOrder['status_code']) && strtoupper($cashfreeOrder['status_code']) != 200) {
                    RentalBooking::where('booking_id', $rentalBooking->booking_id)->when(RentalBooking::where('booking_id', $rentalBooking->booking_id)->exists(), function ($query) {
                        //$query->delete();
                    });
                    $errorMessage = $this->handleCashfreeStatusCode($cashfreeOrder['status_code']);
                    return $this->errorResponse($errorMessage);
                } else {
                    if ($cashfreeOrder && $cashfreeOrder['order_id'] != '' && $cashfreeOrder['payment_session_id'] != '' && $cashfreeOrder['order_status'] != '' && (strtolower($cashfreeOrder['order_status']) == 'active') || strtolower($cashfreeOrder['order_status']) == 'paid') {
                        $cashfreeOrderId = $cashfreeOrder['order_id'];
                        $cashfreepaymentSessionId = $cashfreeOrder['payment_session_id'];
                        // $rentalCostDetails['order'] = ['paid' => false,'razorpay_order_id'=>'','razorpay_payment_id'=>'', 'cashfree_order_id' => $cashfreeOrderId, 'cashfree_payment_session_id' => $cashfreepaymentSessionId];
                    }
                }
            } else {
                return $this->errorResponse('Please contact admin as no any payment gateway is activated');
            }
        } else {
            return $this->errorResponse('Please contact admin as no any payment gateway is activated');
        }

        $bookingTransaction = new BookingTransaction();
        $bookingTransaction->booking_id = $rentalBooking->booking_id;
        $bookingTransaction->timestamp = now()->toDateTimeString();
        $bookingTransaction->type = 'new_booking';
        $bookingTransaction->start_date = $request->start_date;
        $bookingTransaction->end_date = $request->end_date;
        $bookingTransaction->unlimited_kms = $request->input('unlimited_kms', false);
        $bookingTransaction->rental_price = $rentalPrice;
        $bookingTransaction->trip_duration_minutes = $tripDurationMinutes;
        $bookingTransaction->trip_amount = $rentalCostDetails['trip_amount'];
        $bookingTransaction->tax_amt = $rentalCostDetails['tax_amt'];
        $bookingTransaction->coupon_discount = $rentalCostDetails['coupon_discount'] ?? 0;
        $bookingTransaction->coupon_code = $request->coupon_code;
        $bookingTransaction->coupon_code_id = $rentalCostDetails['coupon_code_id'] ?? null;
        $bookingTransaction->trip_amount_to_pay = $rentalCostDetails['trip_amount_to_pay'];
        $bookingTransaction->convenience_fee = $rentalCostDetails['convenience_fee'];
        $bookingTransaction->total_amount = $rentalCostDetails['total_amount'];
        $bookingTransaction->refundable_deposit = $rentalCostDetails['refundable_deposit'] ?? 0;
        $bookingTransaction->final_amount = $rentalCostDetails['final_amount'];
        $bookingTransaction->order_type = 'new_booking';
        $bookingTransaction->paid = false;
        $bookingTransaction->razorpay_order_id = $razorpayOrderID;
        $bookingTransaction->razorpay_payment_id = '';
        $bookingTransaction->cashfree_order_id = $cashfreeOrderId;
        $bookingTransaction->cashfree_payment_session_id = $cashfreepaymentSessionId;
        $bookingTransaction->vehicle_commission_amount = $rentalCostDetails['vehicle_commission_amt'] ?? 0;
        $bookingTransaction->vehicle_commission_tax_amt = $rentalCostDetails['vehicle_commission_tax_amt'] ?? 0;
        $bookingTransaction->save();

        $pg = $rKey = '';
        $setting = Setting::first();
        if ($setting) {
            $pg = $setting->payment_gateway_type ?? '';
            if (strtolower($pg) == 'razorpay') {
                $rKey = getRazorpayKey();
            }
        }

        $payment = new Payment();
        $payment->booking_id = $rentalBooking->booking_id;
        $payment->razorpay_order_id = $razorpayOrderID;
        $payment->cashfree_order_id = $cashfreeOrderId;
        $payment->cashfree_payment_session_id = $cashfreepaymentSessionId;
        $payment->amount = $finalAmount;
        $payment->payment_date = now()->toDateString(); // Adjust this based on your requirements
        $payment->status = 'pending'; // or any other default status
        $payment->payment_gateway_used = $pg;
        //$payment->payment_gateway_charges = 0;
        $payment->save();

        try {
            //$mobileArr = config('global_values.mobile_no_array');
            if (isset($this->userAuthDetails) && $this->userAuthDetails->is_test_user != 1) {
                $payment->payment_env = 'live';
            } else {
                $payment->payment_env = 'test';
            }
            $payment->save();
        } catch (Exception $e) {
        }

        return $this->successResponse(['razorpay_order_id' => $razorpayOrderID, 'razorpay_key' => $rKey, 'final_amount' => (string) $finalAmount, 'booking_id' => $rentalBooking->booking_id, 'which_pg' => strtoupper($pg), 'cashFree_order_id' => $cashfreeOrderId, 'cashFree_session_id' => $cashfreepaymentSessionId], 'Rental booking created successfully.');
    }

    public function handleCashfreeStatusCode($cashfreeStatusCode)
    {
        $errorMessage = '';
        if (strtoupper($cashfreeStatusCode) == 401) {
            $errorMessage = "Authentication Failed";
        } elseif (strtoupper($cashfreeStatusCode) == 400) {
            $errorMessage = "Bad Url Request Failed";
        } elseif (strtoupper($cashfreeStatusCode) == 404) {
            $errorMessage = "Something not Found";
        } elseif (strtoupper($cashfreeStatusCode) == 409) {
            $errorMessage = "Order with same id is already present";
        } elseif (strtoupper($cashfreeStatusCode) == 422) {
            $errorMessage = "Something is not found";
        } elseif (strtoupper($cashfreeStatusCode) == 429) {
            $errorMessage = "Too many requests from IP.";
        } elseif (strtoupper($cashfreeStatusCode) == 500) {
            $errorMessage = "Internal Server Error";
        }

        return $errorMessage;
    }

    public function getExtendOrderPriceDetails(Request $request)
    {
        $startDate = Carbon::parse($request->input('start_date'));
        $adjustedStartDate = $startDate->addMinutes(5)->toDateTimeString();
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|exists:rental_bookings,booking_id',
            'start_date' => 'required|date:' . Carbon::now()->setTimezone('Asia/Kolkata')->addMinutes(1)->toDateTimeString(),
            //'start_date' => 'required|date|after_or_equal:' . Carbon::now()->setTimezone('Asia/Kolkata')->addMinutes(1)->toDateTimeString(),
            'end_date' => 'required|date|after:' . $adjustedStartDate,
            'coupon_code' => ['nullable', 'string', 'exists:coupons,code', new CheckCoupon()],
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $rentalBooking = RentalBooking::select('booking_id', 'status', 'customer_id', 'return_date', 'vehicle_id', 'unlimited_kms', 'tax_rate')->with('vehicle')->where('booking_id', $request->booking_id)->first();
        if (!$rentalBooking) {
            return $this->errorResponse('The rental booking is not available.');
        }
        if ($rentalBooking->status != 'confirmed' && $rentalBooking->status != 'running') {
            return $this->errorResponse('The rental booking is not in a valid state for extension.');
        }
        $startDate = Carbon::parse($rentalBooking->return_date);
        $endDate = Carbon::parse($request->end_date);
        if ($endDate->lte($startDate)) {
            return $this->errorResponse('The end date must be greater than the return date.');
        }

        // Check if Vehicle host has restricted this vehicle to book in Night time or not
        $vehicle = $rentalBooking->vehicle;
        $checkVehicleInNightTime = checkVehicleInNightTime($vehicle, $startDate, $endDate);
        if ($checkVehicleInNightTime == false) {
            return $this->errorResponse('You can not book this vehicle between 12 AM and 6 AM.');
        }

        $checkVehicleStatus = checkVehicleStatus($rentalBooking->vehicle_id, $rentalBooking->booking_id, $startDate, $endDate);
        if ($checkVehicleStatus != '') {
            return $this->errorResponse($checkVehicleStatus);
        }

        $tripDurationMinutes = $endDate->diffInMinutes($startDate);
        $vehicle = $rentalBooking->vehicle;
        $typeId = $vehicle->model->category->vehicleType->type_id ?? NULL;
        $vehicleCommissionPercent = $vehicle->commission_percent ?? 0;
        $rentalPrice = $vehicle->rental_price;
        $checkOffer = OfferDate::where('vehicle_id', $vehicle->vehicle_id)->get();
        if (is_countable($checkOffer) && count($checkOffer) > 0) {
            $rentalPrice = getRentalPrice($rentalPrice, $vehicle->vehicle_id);
        }

        $taxRate = $rentalBooking->tax_rate ?? 0;
        if ($taxRate <= 0) {
            $user = Customer::where('customer_id', $rentalBooking->customer_id)->first();
            $customerGst = $user->gst_number ?? '';
            $taxRate = $customerGst ? 0.12 : 0.05;
        }

        $rentalCostDetails = $rentalBooking->computeRentalCostDetails(
            $rentalPrice,
            $tripDurationMinutes,
            $rentalBooking->unlimited_kms,
            $request->coupon_code,
            $startDate,
            $endDate,
            $typeId,
            true,
            'extension',
            $vehicleCommissionPercent,
            $taxRate,
            $vehicle->vehicle_id
        );

        $kmLimit = calculateKmLimit($tripDurationMinutes / 60);
        $warning = $rentalBooking->unlimited_kms ? '' : "Upon extension, you will receive additional $kmLimit kilometers for your journey. Exceeding this limit will incur additional charges at ₹{$vehicle->extra_km_rate} per km.";

        $priceSummary = $rentalBooking->generatePriceSummary($rentalCostDetails);
        $priceSummary['warning'] = $warning;

        return $this->successResponse($priceSummary);
    }

    public function extendOrder(Request $request)
    {
        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));
        $adjustedStartDate = $startDate->addMinutes(5)->toDateTimeString();
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|exists:rental_bookings,booking_id',
            'start_date' => 'required|date:' . Carbon::now()->setTimezone('Asia/Kolkata')->addMinutes(1)->toDateTimeString(),
            //'start_date' => 'required|date|after_or_equal:' . Carbon::now()->setTimezone('Asia/Kolkata')->addMinutes(1)->toDateTimeString(),
            'end_date' => 'required|date|after:' . $adjustedStartDate,
            'coupon_code' => ['nullable', 'string', 'exists:coupons,code', new CheckCoupon()],
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $rentalBooking = RentalBooking::select('booking_id', 'status', 'customer_id', 'return_date', 'vehicle_id', 'unlimited_kms', 'tax_rate')->with('vehicle')->where('booking_id', $request->booking_id)->first();

        //CHECKED IF THIS VEHICLE iS HOLD BY ADMIN OR NOT
        // if (!empty($rentalBooking->vehicle->availability_calendar)) {
        //     $availabilityRes = checkAvailabilityDates($rentalBooking->vehicle->availability_calendar, $startDate, $endDate);
        //     if($availabilityRes != ''){
        //         return $this->errorResponse($availabilityRes);
        //     }
        // }
        //CHECKED IF THIS VEHICLE ID BOOKED OR NOT WITH OTHER ORDER
        // $checkedBookedVehicele = checkedBookedVehicele($rentalBooking->vehicle->vehicle_id, $startDate, $endDate, $bookingGap, $rentalBooking->booking_id);
        // if($checkedBookedVehicele != ''){
        //     return $this->errorResponse($checkedBookedVehicele);
        // }
        //CHECKED IF USER HAS BOOKED ANOTHER VEHICLE ON SAME TIME OR NOT
        // $checkedUserBookedVehicle = checkedUserBookedVehicle($customerId, $bookingGap, $startDate, $endDate);
        // if($checkedUserBookedVehicle != ''){
        //     return $this->errorResponse($checkedUserBookedVehicle);
        // }
        $user = Customer::where('customer_id', $rentalBooking->customer_id)->first();
        if ($user != '' && $user->is_blocked == 1) {
            return $this->errorResponse('You are blocked by admin, please contact admin for more details.');
        }
        if (!$rentalBooking) {
            return $this->errorResponse('The rental booking is not available.');
        }
        if ($rentalBooking->status != 'confirmed' && $rentalBooking->status != 'running') {
            return $this->errorResponse('The rental booking is not in a valid state for extension.');
        }
        $startDate = Carbon::parse($rentalBooking->return_date);
        $endDate = Carbon::parse($request->end_date);
        if ($endDate->lte($startDate)) {
            return $this->errorResponse('The end date must be greater than the return date.');
        }
        $checkVehicleStatus = checkVehicleStatus($rentalBooking->vehicle_id, $rentalBooking->booking_id, $startDate, $endDate);
        if ($checkVehicleStatus != '') {
            return $this->errorResponse($checkVehicleStatus);
        }

        // Check if Vehicle host has restricted this vehicle to book in Night time or not
        $vehicle = $rentalBooking->vehicle;
        $checkVehicleInNightTime = checkVehicleInNightTime($vehicle, $startDate, $endDate);
        if ($checkVehicleInNightTime == false) {
            return $this->errorResponse('You can not book this vehicle between 12 AM and 6 AM.');
        }

        $tripDurationMinutes = $endDate->diffInMinutes($startDate);
        $vehicle = $rentalBooking->vehicle;
        $typeId = $vehicle->model->category->vehicleType->type_id ?? NULL;
        $vehicleCommissionPercent = $vehicle->commission_percent ?? 0;
        $rentalPrice = $vehicle->rental_price;
        $setting = Setting::first();
        $checkOffer = OfferDate::where('vehicle_id', $vehicle->vehicle_id)->get();
        if (is_countable($checkOffer) && count($checkOffer) > 0) {
            $rentalPrice = getRentalPrice($rentalPrice, $vehicle->vehicle_id);
        }
        // extand delay panulty 
        $bookingTransaction = BookingTransaction::select('end_date')->where('booking_id', $request->booking_id)->orderBy('id', 'desc')->first();

        $exceededEndDateTime = Carbon::parse($bookingTransaction->end_date);
        $exceededStartDate = Carbon::parse($request->start_date);

        if ($exceededStartDate->greaterThanOrEqualTo($exceededEndDateTime)) {
            $exceededMinutes = $exceededEndDateTime->diffInMinutes($exceededStartDate);
            $extraHourRate = (float) ($rentalBooking->vehicle->extra_hour_rate ?? 0);
            $exceededMiuteDelayPenalty = max(0, ($exceededMinutes * $extraHourRate) / 60);
        } else {
            $exceededMiuteDelayPenalty = null;
        }

        $taxRate = $rentalBooking->tax_rate ?? 0;
        if ($taxRate <= 0) {
            $customerGst = $user->gst_number ?? '';
            $taxRate = $customerGst ? 0.12 : 0.05;
        }
        $rentalCostDetails = $rentalBooking->computeRentalCostDetails(
            $rentalPrice,
            $tripDurationMinutes,
            $rentalBooking->unlimited_kms,
            $request->coupon_code,
            $startDate,
            $endDate,
            $typeId,
            true,
            'extension',
            $vehicleCommissionPercent,
            $taxRate,
            $vehicle->vehicle_id
        );

        $finalAmount = $rentalCostDetails['final_amount'] + $exceededMiuteDelayPenalty;
        $finalAmount = round($finalAmount, 2);
        $finalAmount = (int) $finalAmount;

        $razorpayOrderID = $cashfreeOrderId = $cashfreepaymentSessionId = '';
        if ($setting && $setting->payment_gateway_type != '') {
            if (strtolower($setting->payment_gateway_type) == 'razorpay') {
                $razorpayOrder = $this->createOrder(strval($rentalBooking->booking_id), $finalAmount);
                //Below code will check if specified amount is valid by razorpay or not
                if ($razorpayOrder && isset($razorpayOrder['status_code']) && strtoupper($razorpayOrder['status_code']) == 'BAD_REQUEST_ERROR') {
                    if (isset($razorpayOrder['status_message']) && $razorpayOrder['status_message'] != '') {
                        return $this->errorResponse($razorpayOrder['status_message']);
                    }
                }
                $razorpayOrderID = $razorpayOrder->id;
                //=$rentalCostDetails['order'] = ['paid' => false,'razorpay_order_id'=>$razorpayOrderID,'razorpay_payment_id'=>''];

            } elseif (strtolower($setting->payment_gateway_type) == 'cashfree') {
                $cashfreeOrder = $this->createCashfreeOrder(strval($rentalBooking->booking_id), $finalAmount);
                //Below code handle failed status codes
                if ($cashfreeOrder && isset($cashfreeOrder['status_code']) && strtoupper($cashfreeOrder['status_code']) != 200) {
                    RentalBooking::where('booking_id', $rentalBooking->booking_id)->when(RentalBooking::where('booking_id', $rentalBooking->booking_id)->exists(), function ($query) {
                        //$query->delete();
                    });
                    $errorMessage = $this->handleCashfreeStatusCode($cashfreeOrder['status_code']);
                    return $this->errorResponse($errorMessage);
                } else {
                    if ($cashfreeOrder && $cashfreeOrder['order_id'] != '' && $cashfreeOrder['payment_session_id'] != '' && $cashfreeOrder['order_status'] != '' && (strtolower($cashfreeOrder['order_status']) == 'active') || strtolower($cashfreeOrder['order_status']) == 'paid') {
                        $cashfreeOrderId = $cashfreeOrder['order_id'];
                        $cashfreepaymentSessionId = $cashfreeOrder['payment_session_id'];
                        //$rentalCostDetails['order'] = ['paid' => false,'razorpay_order_id'=>'','razorpay_payment_id'=>'', 'cashfree_order_id' => $cashfreeOrderId, 'cashfree_payment_session_id' => $cashfreepaymentSessionId];
                    }
                }
            } else {
                return $this->errorResponse('Please contact admin as no any payment gateway is activated');
            }
        } else {
            return $this->errorResponse('Please contact admin as no any payment gateway is activated');
        }

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
        $bookingTransaction->coupon_code = $request->coupon_code;
        $bookingTransaction->coupon_code_id = $rentalCostDetails['coupon_code_id'] ?? null;
        $bookingTransaction->trip_amount_to_pay = $rentalCostDetails['trip_amount_to_pay'];
        $bookingTransaction->convenience_fee = $rentalCostDetails['convenience_fee'];
        $bookingTransaction->total_amount = $rentalCostDetails['total_amount'];
        $bookingTransaction->refundable_deposit = $rentalCostDetails['refundable_deposit'] ?? 0;
        $bookingTransaction->final_amount = $rentalCostDetails['final_amount'];
        $bookingTransaction->order_type = 'extension';
        $bookingTransaction->paid = false;
        $bookingTransaction->razorpay_order_id = $razorpayOrderID;
        $bookingTransaction->razorpay_payment_id = '';
        $bookingTransaction->cashfree_order_id = $cashfreeOrderId;
        $bookingTransaction->cashfree_payment_session_id = $cashfreepaymentSessionId;
        $bookingTransaction->vehicle_commission_amount = $rentalCostDetails['vehicle_commission_amt'] ?? 0;
        $bookingTransaction->vehicle_commission_tax_amt = $rentalCostDetails['vehicle_commission_tax_amt'] ?? 0;
        $bookingTransaction->save();

        $pg = $rKey = '';
        $setting = Setting::first();
        if ($setting) {
            $pg = $setting->payment_gateway_type ?? '';
            if (strtolower($pg) == 'razorpay') {
                $rKey = getRazorpayKey();
            }
        }
        $payment = new Payment();
        $payment->booking_id = $request->booking_id;
        $payment->razorpay_order_id = $razorpayOrderID;
        $payment->cashfree_order_id = $cashfreeOrderId;
        $payment->cashfree_payment_session_id = $cashfreepaymentSessionId;
        $payment->amount = $finalAmount;
        $payment->payment_type = 'extension';
        $payment->payment_date = now()->toDateString(); // Adjust this based on your requirements
        $payment->status = 'pending';
        $payment->payment_gateway_used = $pg;
        //$payment->payment_env = 'live';
        $payment->save();

        try {
            $mobileNo = $this->userAuthDetails->mobile_number;
            //$mobileArr = config('global_values.mobile_no_array');
            //if (!in_array($mobileNo, $mobileArr)) {
            if (isset($this->userAuthDetails) && $this->userAuthDetails->is_test_user != 1) {
                $payment->payment_env = 'live';
            } else {
                $payment->payment_env = 'test';
            }
            $payment->save();
        } catch (Exception $e) {
        }

        return $this->successResponse(['razorpay_order_id' => $razorpayOrderID, 'razorpay_key' => $rKey, 'final_amount' => (string) $finalAmount, 'booking_id' => $rentalBooking->booking_id, 'which_pg' => strtoupper($pg), 'cashFree_order_id' => $cashfreeOrderId, 'cashFree_session_id' => $cashfreepaymentSessionId], 'Rental booking created successfully.');
    }

    public function createOrder(string $booking_id, $amount)
    {
        $apiKey = getRazorpayKey();
        $apiSecret = getRazorpaySecret();
        $api = new Api($apiKey, $apiSecret);
        $booking = RentalBooking::select('booking_id', 'customer_id')->where('booking_id', $booking_id)->first();

        try {
            $razorpayOrder = $api->order->create(array('receipt' => $booking_id, 'amount' => $amount * 100, 'currency' => 'INR'));
            return $razorpayOrder;
        } catch (\Razorpay\Api\Errors\Error $e) {
            $responseDetails['status_code'] = $e->getCode();
            $responseDetails['status_message'] = $e->getMessage();

            return $responseDetails;
            //return $this->errorResponse('Razorpay order creation failed: ' . $e->getMessage());
        }
    }

    public function createCashfreeOrder(string $booking_id, $amount)
    {
        $user = Auth::guard('api')->user();
        $cClientId = $cSecretId = $cUrl = '';
        if ($user && $user->is_test_user != 1) {
            $cClientId = $this->cashfreeClientId;
            $cSecretId = $this->cashfreeClientSecret;
            $cUrl = $this->cashfreeLiveUrl;
        } else {
            $cClientId = $this->cashfreeTestClientId;
            $cSecretId = $this->cashfreeTestClientSecret;
            $cUrl = $this->cashfreeSandBoxUrl;
        }

        $customerId = $customerPhone = '';
        if ($this->userAuthDetails) {
            $customerId = $this->userAuthDetails->customer_id;
            $customerMobileNumber = $this->userAuthDetails->mobile_number;
        }
        $client = new Client();
        $orderData = [
            'order_id' => 'Order_' . $booking_id . '_' . $customerId . '_' . generateRandomString(7),
            'order_amount' => $amount,
            'order_currency' => 'INR',
            'customer_details' => [
                'customer_id' => $customerId . '_' . generateRandomString(7),
                'customer_phone' => $customerMobileNumber,
            ],
        ];
        try {
            $response = $client->request('POST', $cUrl, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'x-api-version' => $this->cashfreeApiVersion,
                    'x-client-id' => $cClientId,
                    'x-client-secret' => $cSecretId,
                ],
                'json' => $orderData,
            ]);
            $res = json_decode($response->getBody()->getContents(), true);
            return $res;
        } catch (RequestException $e) {
            $responseDetails['status_code'] = $e->getCode();
            $responseDetails['status_message'] = $e->getMessage();
            return $responseDetails;
            //return $e->getMessage();
        }
    }

    public function history(Request $request /*,$booking_id = null*/)
    {
        $durationArr = config('global_values.booking_duration');
        $bookingDuration = implode(',', $durationArr);
        $validator = Validator::make($request->all(), [
            'duration' => 'nullable|in:' . $bookingDuration,
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $currentDateTime = $this->currentDateTime;
        $oneHourAgo = $currentDateTime->subHour();
        $query = RentalBooking::select('booking_id', 'customer_id', 'vehicle_id', 'pickup_date', 'return_date', 'status'/*, 'from_branch_id'*/ , 'end_datetime')->with([
            'vehicle' => function ($query) {
                $query->select('vehicle_id', 'branch_id', 'model_id');
            }
        ])->with('customer', function ($q) {
            $q->select('customer_id', 'email_verified_at');
        })->where('customer_id', $this->userAuthDetails->customer_id)->orderBy('created_at', 'desc');

        if (isset($request->status) && $request->status != '') {
            if ($request->status != 'all') {
                $query->where('status', $request->status);
            }
        }
        if (isset($request->duration) && $request->duration != '') {
            if ($request->duration == 'past') {
                $query->where('pickup_date', '<', date('Y-m-d H:i'));
            } elseif ($request->duration == 'upcoming') {
                $query->where('pickup_date', '>=', date('Y-m-d H:i'));
            }
        }

        // Check if page and page_size are provided
        $page = $request->input('page');
        $pageSize = $request->input('page_size');

        if ($page !== null && $pageSize !== null) {
            // Paginate the results
            $rentalBookings = $query->paginate($pageSize, ['*'], 'page', $page);
            if (isset($rentalBookings) && is_countable($rentalBookings) && count($rentalBookings) > 0) {
                foreach ($rentalBookings as $key => $val) {
                    $val->customer->email_verified_at = $val->customer->email_verified_at != NULL ? true : false;
                    $val->customer->documents = $val->customer->documents;
                }
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
                $item->setHidden([/*'from_branch_id',*/ 'rental_duration_minutes', 'penalty_details', 'start_otp', 'end_otp', 'customer_id', 'vehicle_id', 'invoice_pdf', 'summary_pdf', 'dl_status', 'govtid_status', 'allow_rating', 'rating_value', 'feedback_value', 'admin_penalty_amount', 'price_summary', 'admin_invoice_pdf', 'admin_summary_pdf']);
            });

            $rentalBookings->each(function ($booking) {
                if ($booking->vehicle) {
                    $booking->vehicle->makeHidden('properties', 'rating', 'total_rating', 'trip_count', 'category_name', 'regular_images', 'banner_images');
                }
            });

            // Convert to JSON
            $rentalBookingsArray = json_decode(json_encode($rentalBookings->getCollection()->values()), FALSE);
            if (isset($rentalBookingsArray) && is_countable($rentalBookingsArray) && count($rentalBookingsArray) > 0) {
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
                ],
                'start_journey_otp_message' => "<span style='font-weight: bold;'>Verification code sent to the vehicle’s host. Confirm to initiate your trip securely.</span>",
                'end_journey_otp_message' => "<span style='font-weight: bold;'>Your trip conclusion is pending verification. Please confirm the code sent to the host of the vehicle to end your journey.</span>",
                'start_journey_image_message' => "<span style='color: green;'>Please upload atleast 5 images from every angle of the vehicle including odometer.</span>",
                'end_journey_image_message' => "<span style='color: green;'>Please upload atleast 5 images from every angle of the vehicle including odometer.</span>",
                'try_again_payment_message' => "<span style='color: red;'>Please try again</span>"
            ]);
        } else {
            // Get all results
            $rentalBookings = $query->get();
            if (isset($rentalBookings) && is_countable($rentalBookings) && count($rentalBookings) > 0) {
                foreach ($rentalBookings as $key => $val) {
                    $val->customer->email_verified_at = $val->customer->email_verified_at != NULL ? true : false;
                    $val->customer->documents = $val->customer->documents;
                }
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
                $item->setHidden(['rental_duration_minutes', 'penalty_details', 'start_otp', 'end_otp', 'customer_id', 'vehicle_id', 'invoice_pdf', 'summary_pdf', 'dl_status', 'govtid_status', 'allow_rating', 'rating_value', 'feedback_value', 'admin_penalty_amount', 'price_summary', 'admin_invoice_pdf', 'admin_summary_pdf']);
            });

            $rentalBookings->each(function ($booking) {
                if ($booking->vehicle) {
                    $booking->vehicle->makeHidden('properties', 'rating', 'total_rating', 'trip_count', 'category_name', 'regular_images', 'banner_images');
                }
            });

            // Convert to JSON
            $rentalBookingsArray = json_decode(json_encode($rentalBookings->values()), FALSE);
            if (isset($rentalBookingsArray) && is_countable($rentalBookingsArray) && count($rentalBookingsArray) > 0) {
                for ($i = 0; $i < count($rentalBookingsArray); $i++) {
                    $rentalBookingsArray[$i]->vehicle->vehicle_name = "#{$rentalBookingsArray[$i]->booking_id} " . $rentalBookingsArray[$i]->vehicle->vehicle_name;
                }
            }

            return $this->successResponse(['rental_bookings' => $rentalBookingsArray, 'start_journey_otp_message' => "<span style='font-weight: bold;'>Verification code sent to the vehicle’s host. Confirm to initiate your trip securely.</span>", 'end_journey_otp_message' => "<span style='font-weight: bold;'>Your trip conclusion is pending verification. Please confirm the code sent to the host of the vehicle to end your journey.</span>", 'start_journey_image_message' => "<span style='color: green;'>Please upload atleast 5 images from every angle of the vehicle including odometer.</span>", 'end_journey_image_message' => "<span style='color: green;'>Please upload atleast 5 images from every angle of the vehicle including odometer.</span>", 'try_again_payment_message' => "<span style='color: red;'>Please try again</span>"]);
        }
    }

    public function bookingDetails(Request $request, $booking_id)
    {

        $currentDateTime = Carbon::now()->setTimezone('Asia/Kolkata');
        $oneHourAgo = $currentDateTime->subHour();

        $user = Auth::guard('api')->user();
        $rentalBookingArray = [];
        $rentalBooking = RentalBooking::with([
            'vehicle' => function ($query) {
                $query->select('vehicle_id', 'branch_id', 'model_id');
            },
            'vehicle.model.manufacturer',
            'vehicle.properties',
            'vehicle.features',
            'vehicle.images'
        ])->where('booking_id', $booking_id)->where('customer_id', $user->customer_id)->first();

        if ($rentalBooking != '') {
            if ($rentalBooking->vehicle->properties) {
                $rentalBooking->vehicle->makeHidden(['properties', 'features']);
            }
            $rentalBooking->setHidden(['customer_id', 'to_branch_id', 'rental_duration_minutes', 'unlimited_kms', 'total_cost', 'amount_paid', 'rental_type', 'start_otp', 'end_otp', 'start_datetime', 'end_datetime', 'sequence_no', 'penalty_details', 'status_map', 'data_json', 'created_at', 'updated_at', 'pay_now_status', 'admin_penalty_amount']);

            // Convert to JSON
            $rentalBookingArray = json_decode(json_encode($rentalBooking), FALSE);
            $rentalBookingArray->vehicle->vehicle_name = "#{$rentalBookingArray->booking_id} " . $rentalBookingArray->vehicle->vehicle_name;

            return $this->successResponse(['rental_bookings' => $rentalBookingArray]);
        } else {
            return $this->errorResponse("Booking not Found");
        }
    }

    public function uploadImages(Request $request, $booking_id)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:start,end',
            'images' => 'required|array|min:5',
            'images.*' => 'required|mimetypes:image/heic,image/heif,image/jpeg,image/png,image/jpg,image/bmp,image/gif,image/svg,image/webp|max:10000',
            'kilometers' => 'required|integer|regex:/^\d{1,7}$/',
            // 'hours_datetime' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        // Find the rental booking by ID
        $rentalBooking = RentalBooking::select('booking_id', 'start_kilometers', 'start_datetime', 'end_kilometers')->where('booking_id', $booking_id)->first();
        if (!$rentalBooking) {
            return $this->errorResponse('Rental booking not found');
        }
        $currentDatetime = $this->currentDateTime;

        // Update start or end kilometers based on image type
        if ($request->type === 'start') {
            $rentalBooking->start_kilometers = $request->kilometers;
            $rentalBooking->start_datetime = $currentDatetime;
        } elseif ($request->type === 'end') {
            $rentalBooking->end_kilometers = $request->kilometers;
            //$rentalBooking->end_datetime = $currentDatetime;
        }

        // Save the rental booking with updated start or end kilometers
        $rentalBooking->save();

        // Store the uploaded images and retrieve their URLs
        $imageUrls = [];
        foreach ($request->file('images') as $key => $image) {
            $file = $image;
            $filename = $key . '_' . time() . '.' . $image->getClientOriginalExtension();
            $file->move(public_path('images/rental_booking_images'), $filename);
            $imageUrls[] = $filename;
        }

        // Create a new RentalBookingImage instance for each uploaded image
        foreach ($imageUrls as $imageUrl) {
            $rentalBookingImage = new RentalBookingImage();
            $rentalBookingImage->booking_id = $booking_id;
            $rentalBookingImage->image_type = $request->type;
            $rentalBookingImage->image_url = $imageUrl;
            $rentalBookingImage->save();
        }

        return $this->successResponse(null, 'Images uploaded successfully');
    }

    public function getRentalBookingImages(Request $request, $booking_id)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:start,end',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        // Find the rental booking by ID
        $rentalBooking = RentalBooking::select('booking_id')->where('booking_id', $booking_id)->first();
        if (!$rentalBooking) {
            return $this->errorResponse('Rental booking not found');
        }

        $images = RentalBookingImage::select('booking_id', 'image_url', 'image_type', 'created_at')->where('booking_id', $booking_id)
            ->where('image_type', $request->type)
            ->get();

        return $this->successResponse(['images' => $images], 'Images retrieved successfully');
    }

    public function verifyStartJourney(Request $request, $bookingId)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'otp' => 'required|string',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $booking = RentalBooking::select('booking_id', 'return_date', 'start_otp', 'status', 'customer_id')->where('booking_id', $bookingId)->first();
        if (!$booking) {
            return $this->errorResponse('Invalid Booking');
        }

        if ($booking->customer && $booking->customer->is_blocked == 1) {
            return $this->errorResponse('You can not Start this Journey due to this customer has blocked');
        }

        $customerDocument = CustomerDocument::where(['customer_id' => $booking->customer_id, 'is_blocked' => 1])->exists();
        if ($customerDocument) {
            return $this->errorResponse("You can not Start this Journey due to this customer's documents blocked");
        }

        $returnDate = isset($booking->return_date) ? Carbon::parse($booking->return_date) : '';
        $currentDate = $this->currentDateTime;
        if ($returnDate != '' && $currentDate > $returnDate) {
            return $this->errorResponse('Your Booking time is Over');
        }

        if (!$booking->start_otp || $booking->start_otp !== $request->otp) {
            return $this->errorResponse('Invalid OTP');
        }

        // Clear OTP from database after verification
        $booking->start_otp = null;
        $booking->status = 'running';
        $booking->save();

        return $this->successResponse(null, 'Journey started successfully');
    }

    public function verifyEndJourney(Request $request, $bookingId)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), ['otp' => 'required|string']);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        // Retrieve and validate booking
        $booking = RentalBooking::select('booking_id', 'customer_id', 'end_otp', 'vehicle_id', 'end_datetime', 'penalty_details', 'pickup_date', 'return_date', 'unlimited_kms', 'start_kilometers', 'end_kilometers', 'start_datetime', 'end_datetime', 'status', 'rental_duration_minutes', 'tax_rate')
            ->where('booking_id', $bookingId)
            ->first();
        if (!$booking) {
            return $this->errorResponse('Invalid Booking');
        }
        if (!$booking->end_otp || $booking->end_otp !== $request->otp) {
            return $this->errorResponse('Invalid OTP');
        }

        // Set end date and save the booking
        $booking->end_datetime = now();
        $booking->end_otp = null;
        $booking->save();

        // Decode penalty details
        $adminPenaltyAmount = 0;
        $penaltyInfo = '';
        $adminPenalties = AdminPenalty::where(['booking_id' => $booking->booking_id, 'is_paid' => 0])->where('amount', '>', 0)->first();
        if ($adminPenalties != '') {
            $adminPenaltyAmount = $adminPenalties->amount ?? 0;
            $penaltyInfo = $value->penalty_details ?? '';
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
        $exceededHourPenalty = max(0, (($actualTripDurationMinutes - 15) - $booking->rental_duration_minutes) * (($booking->vehicle->extra_hour_rate ?? 0) / 60));
        //Updated New code Start
        $endDateTime = Carbon::parse($booking->end_datetime);
        if ($endDateTime->greaterThan($returnDateTime)) {
            // If end_datetime is greater than return_date, calculate the exceeded minutes
            $exceededMinutes = $endDateTime->diffInMinutes($returnDateTime);
            $exceededHourPenalty = max(0, ($exceededMinutes * ($booking->vehicle->extra_hour_rate ?? 0) / 60));
            if ($booking && $booking->unlimited_kms == 1) {
                $exceededHourPenalty = ($exceededHourPenalty * 1.3);
            }
        } else {
            $exceededHourPenalty = 0;
        }
        //Updated New code End
        // Calculate final penalty and refundable amount
        $totalPenalty = $adminPenaltyAmount + $exceededKilometerPenalty + $exceededHourPenalty;
        $vehicleCommissionTaxAmt = $vehicleCommissionAmt = 0;
        if ($totalPenalty > 0) {
            $vehicleCommissionPercent = $booking->vehicle->commission_percent ?? 0;
            if ($vehicleCommissionPercent > 0) {
                $vehicleCommissionAmt = ($totalPenalty * $vehicleCommissionPercent) / 100;
                $vehicleCommissionAmt = round($vehicleCommissionAmt);
                $vehicleCommissionTaxAmt = ($vehicleCommissionAmt * 18) / 100;
            }
        }
        /*$customerGst = $this->userAuthDetails->gst_number ?? '';
        $taxRate = $customerGst ? 0.12 : 0.05;*/
        $taxRate = $booking->tax_rate ?? 0;
        if ($taxRate <= 0) {
            $user = Customer::where('customer_id', $booking->customer_id)->first();
            $customerGst = $user->gst_number ?? '';
            $taxRate = $customerGst ? 0.12 : 0.05;
        }
        $taxAmt = $totalPenalty * $taxRate;
        $taxAmt += $vehicleCommissionTaxAmt;
        $totalPenalty = $totalPenalty + $taxAmt;

        // Retrieve refundable deposit from booking_transactions
        $initialTransaction = BookingTransaction::where('booking_id', $bookingId)
            ->where('type', 'new_booking')
            ->first();

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

        // Check if completion transaction already exists
        $existingCompletionTransaction = BookingTransaction::where('booking_id', $bookingId)
            ->where('type', 'completion')
            ->first();

        // Create or update completion transaction
        $completionData = [
            'booking_id' => $booking->booking_id,
            'timestamp' => now(),
            'type' => 'completion',
            'late_return' => $exceededHourPenalty,
            'exceeded_km_limit' => $exceededKilometerPenalty,
            'additional_charges' => $adminPenaltyAmount,
            'additional_charges_info' => $penaltyInfo,
            'refundable_deposit_used' => $refundable_deposit_used,
            'refundable_deposit' => $payNow ? 0 : $remainingRefundableAmount,
            'tax_amt' => round($taxAmt, 2),
            'amount_to_pay' => round($amount_to_pay, 2),
            'order_type' => 'completion',
            'paid' => !$payNow,
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

        // Update booking status if no payment is required
        if (!$payNow) {
            $booking->status = 'completed';
            $lastSequence = RentalBooking::max('sequence_no');
            $booking->sequence_no = $lastSequence + 1;
            $booking->save();
            //Unlink Customer Agreement
            $fileName = 'customer_agreements_' . $booking->customer_id . '_' . $booking->booking_id . '.pdf';
            $filePath = public_path() . '/customer_aggrements/' . $fileName;
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            //Send Email and Push notifications to the User
            SendNotificationJob::dispatch($booking->customer_id, $booking->booking_id, 'completion')->onQueue('emails');
        }

        $booking->save();

        $message = $payNow ? 'Please pay remaining amount.' : 'Your journey is ended, You will get refund amount within 7 working days.';

        // Generate payment details summary from booking_transactions
        $payment_details = $booking->generatePriceSummaryFromBookingTransactions();

        return $this->successResponse([
            'message' => $message,
            'pay_now' => $payNow,
            'payment_details' => $payment_details,
        ]);
    }

    public function endJourneyDetails(Request $request, $bookingId)
    {
        $booking = RentalBooking::select('booking_id')
            ->where('booking_id', $bookingId)
            ->first();

        if (!$booking) {
            return $this->errorResponse('Invalid Booking');
        }

        // Check for completion transaction
        $completionTransaction = BookingTransaction::where('booking_id', $booking->booking_id)
            ->where('type', 'completion')
            ->first();

        $payNow = false;
        $message = 'Your journey is ended, You will get refund amount within 7 working days.';

        if ($completionTransaction) {
            $isOrderPaid = $completionTransaction->paid;
            $payNow = !$isOrderPaid;

            if ($payNow) {
                $message = 'Please pay remaining amount.';
            }
        }

        // Generate payment details summary from booking_transactions
        $payment_details = $booking->generatePriceSummaryFromBookingTransactions();

        return $this->successResponse([
            'message' => $message,
            'pay_now' => $payNow,
            'payment_details' => $payment_details,
        ]);
    }

    public function deductPayment(Request $request, $booking_id)
    {
        // Retrieve the booking
        $booking = RentalBooking::select('booking_id')
            ->where('booking_id', $booking_id)
            ->first();
        if (!$booking) {
            return $this->errorResponse('Invalid Booking');
        }

        $checkCompletionTransaction = BookingTransaction::where('booking_id', $booking_id)
            ->where('type', 'completion')->where('paid', 1)
            ->first();
        if ($checkCompletionTransaction != '') {
            return $this->errorResponse("Already Paid");
        }

        // Query for the completion transaction
        $completionTransaction = BookingTransaction::where('booking_id', $booking->booking_id)
            ->where('type', 'completion')
            ->first();

        if (!$completionTransaction) {
            return $this->errorResponse('No completion transaction found for this booking.');
        }

        // Determine if payment is needed
        $isOrderPaid = $completionTransaction->paid;
        $amountToPay = $completionTransaction->amount_to_pay;
        $razorpayOrderId = $completionTransaction->razorpay_order_id ?? '';
        $payNow = !$isOrderPaid;

        $pg = $rKey = '';
        $setting = Setting::first();
        if ($setting) {
            $pg = $setting->payment_gateway_type ?? '';
            if (strtolower($pg) == 'razorpay') {
                $rKey = getRazorpayKey();
            }
        }

        $razorpayOrderID = $cashfreeOrderId = $cashfreepaymentSessionId = '';
        if ($payNow) {
            $amountToPay = round($amountToPay, 2);
            $payableAmt = (int) $amountToPay;

            if ($razorpayOrderId == '') {
                if ($setting && $setting->payment_gateway_type != '') {
                    if (strtolower($setting->payment_gateway_type) == 'razorpay') {
                        // Create a new Razorpay order
                        $razorpayOrder = $this->createOrder(strval($booking_id), $payableAmt);
                        //Below code will check if specified amount is valid by razorpay or not
                        if ($razorpayOrder && isset($razorpayOrder['status_code']) && strtoupper($razorpayOrder['status_code']) == 'BAD_REQUEST_ERROR') {
                            if (isset($razorpayOrder['status_message']) && $razorpayOrder['status_message'] != '') {
                                return $this->errorResponse($razorpayOrder['status_message']);
                            }
                        }
                        $razorpayOrderId = $razorpayOrder->id ?? '';

                    } elseif (strtolower($setting->payment_gateway_type) == 'cashfree') {
                        $cashfreeOrder = $this->createCashfreeOrder(strval($booking_id), $payableAmt);
                        //Below code handle failed status codes
                        if ($cashfreeOrder && isset($cashfreeOrder['status_code']) && strtoupper($cashfreeOrder['status_code']) != 200) {

                            $errorMessage = $this->handleCashfreeStatusCode($cashfreeOrder['status_code']);
                            return $this->errorResponse($errorMessage);
                        } else {
                            if ($cashfreeOrder && $cashfreeOrder['order_id'] != '' && $cashfreeOrder['payment_session_id'] != '' && $cashfreeOrder['order_status'] != '' && (strtolower($cashfreeOrder['order_status']) == 'active') || strtolower($cashfreeOrder['order_status']) == 'paid') {
                                $cashfreeOrderId = $cashfreeOrder['order_id'];
                                $cashfreepaymentSessionId = $cashfreeOrder['payment_session_id'];
                            }
                        }
                    } else {
                        return $this->errorResponse('Please contact admin as no any payment gateway is activated');
                    }
                } else {
                    return $this->errorResponse('Please contact admin as no any payment gateway is activated');
                }

                // Update the completion transaction with the Razorpay order ID
                $completionTransaction->razorpay_order_id = $razorpayOrderId;
                $completionTransaction->cashfree_order_id = $cashfreeOrderId;
                $completionTransaction->cashfree_payment_session_id = $cashfreepaymentSessionId;
                $completionTransaction->save();

                // Create a new payment record
                $payment = new Payment();
                $payment->booking_id = $booking_id;
                $payment->razorpay_order_id = $razorpayOrderId;
                $payment->cashfree_order_id = $cashfreeOrderId;
                $payment->cashfree_payment_session_id = $cashfreepaymentSessionId;
                $payment->payment_type = "completion";
                $payment->amount = $payableAmt;
                $payment->payment_date = now()->toDateString();
                $payment->status = 'pending';
                $payment->payment_gateway_used = $pg;
                $payment->save();

                try {
                    $mobileNo = $this->userAuthDetails->mobile_number;
                    if (isset($this->userAuthDetails) && $this->userAuthDetails->is_test_user != 1) {
                        $payment->payment_env = 'live';
                    } else {
                        $payment->payment_env = 'test';
                    }
                    $payment->save();
                } catch (Exception $e) {
                    // Handle exception
                }

                return $this->successResponse(['paid' => false, 'pending' => false, 'payment_message' => '', 'razorpay_order_id' => $razorpayOrderId, 'razorpay_key' => $rKey, 'final_amount' => (string) $payableAmt, 'booking_id' => $booking->booking_id, 'which_pg' => strtoupper($pg), 'cashFree_order_id' => $cashfreeOrderId, 'cashFree_session_id' => $cashfreepaymentSessionId], 'Order created successfully.');
            } else {
                return $this->successResponse(['paid' => false, 'pending' => true, 'payment_message' => 'If you have already paid, kindly wait for some time to procced the payment.', 'razorpay_order_id' => $razorpayOrderId, 'razorpay_key' => $rKey, 'final_amount' => (string) $payableAmt, 'booking_id' => $booking->booking_id, 'which_pg' => strtoupper($pg), 'cashFree_order_id' => $cashfreeOrderId, 'cashFree_session_id' => $cashfreepaymentSessionId], 'Order fetched successfully.');
            }
        } else {
            return $this->successResponse(['paid' => true, 'pending' => false, 'payment_message' => 'Your booking is already paid, kindly wait for some time to procced the payment.', 'razorpay_order_id' => $razorpayOrderId, 'razorpay_key' => $rKey, 'final_amount' => (string) 0, 'booking_id' => $booking->booking_id, 'which_pg' => strtoupper($pg), 'cashFree_order_id' => $cashfreeOrderId, 'cashFree_session_id' => $cashfreepaymentSessionId], 'Order is already Paid.');
            //         return $this->successResponse([
            //             'paid' => false,
            //             'pending' => false,
            //             'payment_message' => '',
            //             'razorpay_order_id' => $razorpayOrderId,
            //             'razorpay_key' => $rKey,
            //             'final_amount' => (string)$payableAmt,
            //             'booking_id' => $booking->booking_id,
            //         ], 'Order created successfully.');
            //     } else {
            //         return $this->successResponse([
            //             'paid' => false,
            //             'pending' => true,
            //             'payment_message' => 'If you have already paid, kindly wait for some time to proceed with the payment.',
            //             'razorpay_order_id' => $razorpayOrderId,
            //             'razorpay_key' => $rKey,
            //             'final_amount' => (string)$payableAmt,
            //             'booking_id' => $booking->booking_id,
            //         ], 'Order fetched successfully.');
            //     }
            // } else {
            //     return $this->successResponse([
            //         'paid' => true,
            //         'pending' => false,
            //         'payment_message' => 'Your booking is already paid, kindly wait for some time to proceed with the payment.',
            //         'razorpay_order_id' => $razorpayOrderId,
            //         'razorpay_key' => $rKey,
            //         'final_amount' => (string)0,
            //         'booking_id' => $booking->booking_id,
            //     ], 'Order is already Paid.');
        }
    }

    public function adminPenaltyPayment(Request $request, $booking_id)
    {
        $adminPenalty = AdminPenalty::select('id', 'booking_id', 'amount', 'penalty_details', 'is_paid', 'razorpay_order_id', 'cashfree_order_id')->where(['booking_id' => $booking_id, 'is_paid' => 0])->where('amount', '!=', 0)->first();
        if (!$adminPenalty) {
            return $this->errorResponse('No any Penalty has applied');
        }
        $payableAmt = (float) $adminPenalty->amount;
        $bookingTransaction = BookingTransaction::where(['booking_id' => $booking_id, 'type' => 'penalty', 'paid' => 0, 'total_amount' => $adminPenalty->amount])->first();
        if ($bookingTransaction != '') {
            $payableAmt += $bookingTransaction->tax_amt;
        }
        $payableAmt = round($payableAmt);
        $pg = $rKey = '';
        $setting = Setting::first();
        if ($setting) {
            $pg = $setting->payment_gateway_type ?? '';
            if (strtolower($pg) == 'razorpay') {
                $rKey = getRazorpayKey();
            }
        }
        $razorpayOrderId = $cashfreeOrderId = $cashfreepaymentSessionId = '';
        if ($setting && $setting->payment_gateway_type != '') {
            if (strtolower($setting->payment_gateway_type) == 'razorpay') {
                $razorpayOrder = $this->createOrder(strval($booking_id), $payableAmt);
                //Below code will check if specified amount is valid by razorpay or not
                if ($razorpayOrder && isset($razorpayOrder['status_code']) && strtoupper($razorpayOrder['status_code']) == 'BAD_REQUEST_ERROR') {
                    if (isset($razorpayOrder['status_message']) && $razorpayOrder['status_message'] != '') {
                        return $this->errorResponse($razorpayOrder['status_message']);
                    }
                }
                $razorpayOrderId = $razorpayOrder->id ?? '';
            } elseif (strtolower($setting->payment_gateway_type) == 'cashfree') {
                $cashfreeOrder = $this->createCashfreeOrder(strval($booking_id), $payableAmt);
                //Below code handle failed status codes
                if ($cashfreeOrder && isset($cashfreeOrder['status_code']) && strtoupper($cashfreeOrder['status_code']) != 200) {
                    $errorMessage = $this->handleCashfreeStatusCode($cashfreeOrder['status_code']);
                    return $this->errorResponse($errorMessage);
                } else {
                    if ($cashfreeOrder && $cashfreeOrder['order_id'] != '' && $cashfreeOrder['payment_session_id'] != '' && $cashfreeOrder['order_status'] != '' && (strtolower($cashfreeOrder['order_status']) == 'active') || strtolower($cashfreeOrder['order_status']) == 'paid') {
                        $cashfreeOrderId = $cashfreeOrder['order_id'];
                        $cashfreepaymentSessionId = $cashfreeOrder['payment_session_id'];
                    }
                }
            } else {
                return $this->errorResponse('Please contact admin as no any payment gateway is activated');
            }
        } else {
            return $this->errorResponse('Please contact admin as no any payment gateway is activated');
        }
        if ($bookingTransaction != '') {
            $bookingTransaction->timestamp = date('Y-m-d H:i');
            $bookingTransaction->razorpay_order_id = $razorpayOrderId;
            $bookingTransaction->razorpay_payment_id = '';
            $bookingTransaction->cashfree_order_id = $cashfreeOrderId;
            $bookingTransaction->cashfree_payment_session_id = $cashfreepaymentSessionId;
            $bookingTransaction->save();

            $payment = new Payment();
            $payment->booking_id = $booking_id;
            $payment->razorpay_order_id = $razorpayOrderId;
            $payment->cashfree_order_id = $cashfreeOrderId;
            $payment->cashfree_payment_session_id = $cashfreepaymentSessionId;
            $payment->payment_type = "penalty";
            //$payment->amount = $bookingTransaction->total_amount;
            //$payment->amount = $bookingTransaction->final_amount;
            $payment->amount = $payableAmt;
            $payment->payment_date = now()->toDateString();
            $payment->status = 'pending';
            $payment->payment_gateway_used = $pg;
            $payment->save();

            $adminPenalty->cashfree_order_id = $cashfreeOrderId;
            $adminPenalty->razorpay_order_id = $razorpayOrderId;
            $adminPenalty->save();

            try {
                $mobileNo = $this->userAuthDetails->mobile_number;
                if (isset($this->userAuthDetails) && $this->userAuthDetails->is_test_user != 1) {
                    $payment->payment_env = 'live';
                } else {
                    $payment->payment_env = 'test';
                }
                $payment->save();
            } catch (Exception $e) {
            }

            return $this->successResponse(['razorpay_order_id' => $razorpayOrderId, 'razorpay_key' => $rKey, 'final_amount' => (string) $payableAmt, 'booking_id' => (int) $booking_id, 'which_pg' => strtoupper($pg), 'cashFree_order_id' => $cashfreeOrderId, 'cashFree_session_id' => $cashfreepaymentSessionId], 'Order created successfully.');
        } else {
            return $this->errorResponse("Booking Transaction not Found");
        }
    }

    public function successPayment($booking_id)
    {
        // Retrieve the rental booking based on the booking ID
        $booking = RentalBooking::where('booking_id', $booking_id)->first();
        if (!$booking) {
            return $this->errorResponse('Invalid Booking');
        }
        $paymentId = 'test';

        // Create or update the payment record
        $payment = Payment::updateOrCreate(
            ['booking_id' => $booking_id],
            [
                'razorpay_payment_id' => $paymentId,
                'status' => 'captured',
            ]
        );
        $payments = Payment::where('booking_id', $booking_id)->get();

        // Find the corresponding rental booking and process each payment
        foreach ($payments as $payment) {
            $booking->processPayment($payment);
        }
        return $this->successResponse(null, ['message' => 'OTP has been sent to the user for booking ID: ' . $booking_id]);
    }

    public function invoiceData(Request $request, $bookingId)
    {
        $extraKmString = '';
        $data = RentalBooking::with(['vehicle.model.manufacturer', 'vehicle.model.category', 'vehicle.properties', 'vehicle.features', 'vehicle.images', 'customer'])->where('booking_id', $bookingId)->first();
        // $data = RentalBooking::with(['vehicle.model.manufacturer', 'vehicle.model.category', 'vehicle.properties', 'vehicle.features', 'vehicle.images', 'customer'])->where('booking_id', $bookingId)->where('customer_id', $this->userAuthDetails->customer_id)->first();
        $companyDetails = CompanyDetail::select('id', 'address', 'phone', 'alt_phone', 'email', 'gst_no', 'pan_no', 'bank_name', 'bank_account_no', 'bank_ifsc_code')->first();
        $newBooking = $extension = $completion = $cFees = $adminPenaltiesDue = $newBookingVehicleServiceFees = $extensionVehicleServiceFees = $paidPenalties = $paidPenaltyServiceCharge = $duePenalties = $duePenaltyServiceCharge = $completionVehicleServiceFees = [];
        $totalAmt = $totalTax = $convenienceFees = $rateTotal = $completionDisplay = $amountDue = 0;
        $gstStatus = 1; // 1 = Consider CGST/SGST
        if ($data && $data->customer && $data->customer->gst_number != null) {
            if (str_starts_with($data->customer->gst_number, 24) == '') {
                $gstStatus = 2; // 2 = Consider IGST
            }
        }
        $newBookingTimeStamp = $completionNewBooking = $penaltyText = '';
        $calculationDetails = BookingTransaction::where(['booking_id' => $bookingId])->get();
        $gstPercent = $data->tax_rate ?? 0;
        if (is_countable($calculationDetails) && count($calculationDetails) > 0) {
            foreach ($calculationDetails as $key => $value) {
                $commissionTaxAmount = $value->vehicle_commission_tax_amt ?? 0;
                if ($value->type == 'new_booking' && $value->paid == 1) {
                    //$newBookingTimeStamp = $value->timestamp;
                    $newBookingTimeStamp = date('d-m-Y H:i', strtotime($value->start_date)) . ' - ' . date('d-m-Y H:i', strtotime($value->end_date));
                    $newBooking['trip_amount'] = number_format($value->trip_amount - $value->vehicle_commission_amount, 2);
                    $taxPercent = 0;
                    $mainAmt = $value->trip_amount;
                    if (isset($value->coupon_discount) && $value->coupon_discount != 0) {
                        $mainAmt = $value->trip_amount - $value->coupon_discount;
                    }
                    $vehiclePercent = $value->rentalBooking->vehicle->commission_percent ?? 0;
                    $taxPercent = getTaxPercent($mainAmt, $value->tax_amt, $value->trip_amount_to_pay, $vehiclePercent, $gstPercent, $commissionTaxAmount);

                    $newBooking['tax_percent'] = number_format($taxPercent, 2);
                    $newBooking['tax_amount'] = number_format(($value->tax_amt - $value->vehicle_commission_tax_amt), 2);
                    $newBooking['coupon_discount'] = number_format($value->coupon_discount, 2);
                    $newBooking['total_amount'] = $value->total_amount - $value->convenience_fee;
                    $newBooking['total_amount'] = number_format(($newBooking['total_amount'] - ($value->vehicle_commission_amount + $value->vehicle_commission_tax_amt)), 2);

                    $tAmt = $value->total_amount - $value->convenience_fee;
                    $totalAmt += $tAmt;
                    $totalTax += $value->tax_amt;
                    $convenienceFees += $value->convenience_fee;
                    $rateTotal += $value->trip_amount;
                    $rateTotal -= $value->coupon_discount;
                    $newBookingVehicleServiceFees['trip_amount'] = number_format($value->vehicle_commission_amount, 2);
                    $newBookingVehicleServiceFees['tax_percent'] = $value->vehicle_commission_amount != 0 && $value->vehicle_commission_tax_amt != 0 ? number_format(18, 2) : number_format(0, 2);
                    $newBookingVehicleServiceFees['tax_amount'] = number_format($value->vehicle_commission_tax_amt, 2);
                    $newBookingVehicleServiceFees['coupon_discount'] = number_format(0, 2);
                    $newBookingVehicleServiceFees['total_amount'] = number_format(($value->vehicle_commission_amount + $value->vehicle_commission_tax_amt), 2);
                } elseif ($value->type == 'extension' && $value->paid == 1) {
                    $extension['timestamp'][] = date('d-m-Y H:i', strtotime($value->end_date));
                    $extension['trip_amount'][] = number_format(($value->trip_amount - $value->vehicle_commission_amount), 2);
                    $taxPercent = 0;
                    $mainAmt = $value->trip_amount;
                    if (isset($value->coupon_discount) && $value->coupon_discount != 0) {
                        $mainAmt = $value->trip_amount - $value->coupon_discount;
                    }
                    $vehiclePercent = $value->rentalBooking->vehicle->commission_percent ?? 0;
                    $taxPercent = getTaxPercent($mainAmt, $value->tax_amt, $value->trip_amount_to_pay, $vehiclePercent, $gstPercent, $commissionTaxAmount);

                    $extension['tax_percent'][] = number_format($taxPercent, 2);
                    $extension['tax_amount'][] = number_format(($value->tax_amt - $value->vehicle_commission_tax_amt), 2);
                    $extension['coupon_discount'][] = number_format($value->coupon_discount, 2);
                    $tAmt = $value->total_amount - $value->convenience_fee;
                    $totalAmt += $tAmt;
                    $totalTax += $value->tax_amt;
                    $convenienceFees += $value->convenience_fee;
                    $rateTotal += $value->trip_amount;
                    $rateTotal -= $value->coupon_discount;
                    $extensionVehicleServiceFees['trip_amount'][] = number_format($value->vehicle_commission_amount, 2);
                    $extensionVehicleServiceFees['tax_percent'][] = $value->vehicle_commission_amount != 0 && $value->vehicle_commission_tax_amt != 0 ? number_format(18, 2) : number_format(0, 2);
                    $extensionVehicleServiceFees['tax_amount'][] = number_format($value->vehicle_commission_tax_amt, 2);
                    $extensionVehicleServiceFees['coupon_discount'][] = number_format(0, 2);
                    $extensionVehicleServiceFees['total_amount'][] = number_format(($value->vehicle_commission_amount + $value->vehicle_commission_tax_amt), 2);
                    $extension['total_amount'][] = number_format(($value->total_amount - ($value->vehicle_commission_amount + $value->vehicle_commission_tax_amt)), 2);
                } elseif ($value->type == 'completion' && $value->paid == 1) {
                    $completionNewBooking = date('d-m-Y H:i', strtotime($value->timestamp));
                    $additionalCharges = $totalAmount = 0;
                    $penaltyText = '';

                    if (isset($value->late_return) && $value->late_return != '' && $value->late_return != 0) {
                        $additionalCharges += $value->late_return;
                        $penaltyText .= ' Late Return - ' . round($value->late_return, 2);
                    }
                    if (isset($value->exceeded_km_limit) && $value->exceeded_km_limit != '' && $value->exceeded_km_limit != 0) {
                        $additionalCharges += $value->exceeded_km_limit;
                        if ($value->late_return != 0) {
                            $penaltyText .= ' | ';
                        }
                        if (is_countable($data->price_summary) && count($data->price_summary) > 0) {
                            foreach ($data->price_summary as $key => $val) {
                                if (str_starts_with(strtolower($val['key']), 'extra')) {
                                    $extraKmString = $val['key'];
                                }
                            }
                        }
                        $penaltyText .= $extraKmString;
                    }
                    if (isset($value->additional_charges) && $value->additional_charges != '' && $value->additional_charges != 0) {
                        $additionalCharges += $value->additional_charges;
                        if ($value->exceeded_km_limit != 0) {
                            $penaltyText .= ' | ';
                        }
                        $penaltyText .= 'Additional Charges - ' . $value->additional_charges;
                    }
                    $completion['additional_charge'] = number_format((round($additionalCharges, 2) - $value->vehicle_commission_amount), 2);
                    if (isset($value->tax_amt) && $value->tax_amt != '' && $value->tax_amt != 0) {
                        $totalAmount += $value->tax_amt;
                    }
                    $taxPercent = 0;
                    $mainAmt = $additionalCharges;
                    if (isset($value->coupon_discount) && $value->coupon_discount != 0) {
                        $mainAmt = $value->trip_amount - $value->coupon_discount;
                    }
                    $vehiclePercent = $value->rentalBooking->vehicle->commission_percent ?? 0;
                    $taxPercent = getTaxPercent($mainAmt, $value->tax_amt, $value->trip_amount_to_pay, $vehiclePercent, $gstPercent, $commissionTaxAmount);

                    $completion['tax_percent'] = number_format($taxPercent, 2);
                    $completion['tax_amount'] = number_format(($value->tax_amt - $value->vehicle_commission_tax_amt), 2);
                    $completion['coupon_discount'] = number_format(0, 2);
                    $completion['total_amount'] = number_format(round(($totalAmount + $additionalCharges), 2) - ($value->vehicle_commission_amount + $value->vehicle_commission_tax_amt), 2);
                    // $totalAmt += $value->tax_amt; 
                    // $totalAmt += $additionalCharges;
                    // $totalTax += $value->tax_amt;
                    // $rateTotal += $additionalCharges;
                    if ($data->booking_id != 1805) {
                        $totalAmt += $value->tax_amt;
                        $totalAmt += $additionalCharges;
                        $totalTax += $value->tax_amt;
                        $rateTotal += $additionalCharges;
                    } else {
                        $amountDue += 227617.59;
                    }

                    if ($completion['additional_charge'] != 0 || $completion['total_amount'] != 0) {
                        $completionDisplay = 1;
                    }

                    $completionVehicleServiceFees['trip_amount'] = number_format($value->vehicle_commission_amount, 2);
                    $completionVehicleServiceFees['tax_percent'] = $value->vehicle_commission_amount != 0 && $value->vehicle_commission_tax_amt != 0 ? number_format(18, 2) : number_format(0, 2);
                    $completionVehicleServiceFees['tax_amount'] = number_format($value->vehicle_commission_tax_amt, 2);
                    $completionVehicleServiceFees['coupon_discount'] = number_format(0, 2);
                    $completionVehicleServiceFees['total_amount'] = number_format(($value->vehicle_commission_amount + $value->vehicle_commission_tax_amt), 2);
                } elseif ($value->type == 'penalty' && $value->paid == 1) {
                    if ($value->final_amount > 0) {
                        $paidPenalties['timestamp'][] = date('d-m-Y H:i', strtotime($value->timestamp));
                        $mainAmt = $value->total_amount;
                        if (isset($value->coupon_discount) && $value->coupon_discount != 0) {
                            $mainAmt = $value->total_amount - $value->coupon_discount;
                        }
                        $paidPenalties['trip_amount'][] = number_format($value->total_amount ?? 0, 2);
                        $vehiclePercent = $value->rentalBooking->vehicle->commission_percent ?? 0;
                        $paidPenalties['tax_percent'][] = getTaxPercent($mainAmt, $value->tax_amt, $mainAmt, $vehiclePercent, $gstPercent, $commissionTaxAmount);
                        $paidPenalties['tax_amount'][] = number_format($value->tax_amt ?? 0, 2);
                        $paidPenalties['coupon_discount'][] = number_format(0, 2);
                        $paidPenalties['total_amount'][] = number_format(($value->total_amount + $value->tax_amt), 2);

                        $paidPenaltyServiceCharge['trip_amount'][] = number_format($value->vehicle_commission_amount, 2);
                        $paidPenaltyServiceCharge['tax_percent'][] = $value->vehicle_commission_amount != 0 && $value->vehicle_commission_tax_amt != 0 ? number_format(18, 2) : number_format(0, 2);
                        $paidPenaltyServiceCharge['tax_amount'][] = number_format($value->vehicle_commission_tax_amt, 2);
                        $paidPenaltyServiceCharge['coupon_discount'][] = number_format(0, 2);
                        $paidPenaltyServiceCharge['total_amount'][] = number_format(($value->vehicle_commission_amount + $value->vehicle_commission_tax_amt), 2);

                        $rateTotal += $value->total_amount;
                        $rateTotal += $value->vehicle_commission_amount;
                        $totalTax += $value->tax_amt;
                        $totalTax += $value->vehicle_commission_tax_amt;

                        $totalAmt += $value->total_amount + $value->tax_amt;
                        $totalAmt += $value->vehicle_commission_amount + $value->vehicle_commission_tax_amt;
                    }
                } elseif ($value->type == 'penalty' && $value->paid == 0) {
                    $duePenalties['timestamp'][] = date('d-m-Y H:i', strtotime($value->timestamp));
                    $mainAmt = $value->total_amount;
                    if (isset($value->coupon_discount) && $value->coupon_discount != 0) {
                        $mainAmt = $value->total_amount - $value->coupon_discount;
                    }
                    $duePenalties['trip_amount'][] = number_format($value->total_amount ?? 0, 2);
                    $vehiclePercent = $value->rentalBooking->vehicle->commission_percent ?? 0;
                    $taxPercent = ($gstPercent == 0.05) ? 5 : (($gstPercent == 0.12) ? 12 : 0);
                    $duePenalties['tax_percent'][] = $taxPercent;
                    // OLD CODE
                    //$duePenalties['tax_amount'][] = number_format($value->tax_amt ?? 0, 2);
                    //$duePenalties['total_amount'][] = number_format(($value->total_amount + $value->tax_amt), 2);
                    // NEW CODE
                    if ($value->tax_amt > $value->vehicle_commission_tax_amt) {
                        $penaltyTax = $value->tax_amt - $value->vehicle_commission_tax_amt;
                        $duePenalties['tax_amount'][] = number_format($penaltyTax ?? 0, 2);
                        $duePenalties['total_amount'][] = number_format(($value->total_amount + $penaltyTax), 2);
                    } else {
                        $duePenalties['tax_amount'][] = number_format($value->tax_amt ?? 0, 2);
                        $duePenalties['total_amount'][] = number_format(($value->total_amount + $value->tax_amt), 2);
                    }
                    $duePenalties['coupon_discount'][] = number_format(0, 2);

                    $duePenaltyServiceCharge['trip_amount'][] = number_format($value->vehicle_commission_amount, 2);
                    $duePenaltyServiceCharge['tax_percent'][] = $value->vehicle_commission_amount != 0 && $value->vehicle_commission_tax_amt != 0 ? number_format(18, 2) : number_format(0, 2);
                    $duePenaltyServiceCharge['tax_amount'][] = number_format($value->vehicle_commission_tax_amt, 2);
                    $duePenaltyServiceCharge['coupon_discount'][] = number_format(0, 2);
                    $duePenaltyServiceCharge['total_amount'][] = number_format(($value->vehicle_commission_amount + $value->vehicle_commission_tax_amt), 2);

                    $amountDue += ($value->total_amount + $value->tax_amt);
                    //$amountDue += ($value->vehicle_commission_amount + $value->vehicle_commission_tax_amt);
                    $amountDue += ($value->vehicle_commission_amount);
                }
            }
            //Convenience Fees Calculation
            $newConvenienceFees = $convenienceFees / (1 + (18 / 100));
            $newConvenienceFees = round($newConvenienceFees, 2);
            $gstAmt = $convenienceFees - $newConvenienceFees;
            $cFees['trip_amount'] = number_format($newConvenienceFees, 2);
            $cFees['tax_percent'] = number_format(18, 2);
            $cFees['tax_amount'] = number_format($gstAmt, 2);
            $cFees['coupon_discount'] = number_format(0, 2);
            $cFees['total_amount'] = number_format($convenienceFees, 2);
            $totalAmt += $convenienceFees;
            $totalTax += $gstAmt;
            $rateTotal += $newConvenienceFees;
            $rateTotal = round($rateTotal, 2);
            $totalAmt = round($totalAmt, 2);
        }

        $filename = 'booking-invoice-' . $bookingId . '.pdf';
        $pdf = PDF::loadView('booking-invoice', compact('data', 'companyDetails', 'newBooking', 'extension', 'completion', 'totalAmt', 'totalTax', 'convenienceFees', 'cFees', 'rateTotal', 'penaltyText', 'gstStatus', 'completionDisplay', 'extraKmString', 'newBookingTimeStamp', 'completionNewBooking', 'adminPenaltiesDue'/*, 'vehiclePercentAmt'*/ , 'newBookingVehicleServiceFees', 'extensionVehicleServiceFees', 'completionVehicleServiceFees', 'paidPenalties', 'paidPenaltyServiceCharge', 'duePenalties', 'duePenaltyServiceCharge', 'amountDue'))->setPaper('A3');
        return $pdf->stream('booking-invoice.pdf');
    }

    public function summaryData(Request $request, $bookingId)
    {
        $data = RentalBooking::with(['vehicle.model.manufacturer', 'vehicle.model.category', 'vehicle.properties', 'vehicle.features', 'vehicle.images'])->where('booking_id', $bookingId)->first();
        $customerDoc = CustomerDocument::select('document_id', 'customer_id', 'document_type', 'is_approved', 'id_number')->where('customer_id', $data->customer_id)->get();
        // $data = RentalBooking::with(['vehicle.model.manufacturer', 'vehicle.model.category', 'vehicle.properties', 'vehicle.features', 'vehicle.images'])->where('booking_id', $bookingId)->where('customer_id', $this->userAuthDetails->customer_id)->first();
        // $customerDoc = CustomerDocument::select('document_id', 'customer_id', 'document_type', 'is_approved', 'id_number')->where('customer_id', $this->userAuthDetails->customer_id)->get();
        $docDetails['gov_status'] = '';
        $docDetails['gov_id_number'] = '';
        $docDetails['dl_status'] = '';
        $docDetails['dl_id_number'] = '';
        if (is_countable($customerDoc) && count($customerDoc) > 0) {
            foreach ($customerDoc as $key => $val) {
                if (strtolower($val->document_type) == 'govtid') {
                    $docDetails['gov_status'] = isset($val->is_approved) ? $val->is_approved : '';
                    $docDetails['gov_id_number'] = isset($val->id_number) ? $val->id_number : '';
                }
                if (strtolower($val->document_type) == 'dl') {
                    $docDetails['dl_status'] = isset($val->is_approved) ? $val->is_approved : '';
                    $docDetails['dl_id_number'] = isset($val->id_number) ? $val->id_number : '';
                }
            }
        }
        $data->gov_status = $docDetails['gov_status'];
        $data->gov_id_number = $docDetails['gov_id_number'];
        $data->dl_status = $docDetails['dl_status'];
        $data->dl_id_number = $docDetails['dl_id_number'];
        $filename = 'booking-summary-' . $bookingId . '.pdf';
        $pdf = PDF::loadView('booking-summary', compact('data'));
        return $pdf->stream('booking-summary.pdf');
    }

    public function rentalReview(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|exists:rental_bookings,booking_id',
            'rating' => 'nullable|numeric|min:1|max:5',
            'feedback_value' => 'nullable|max:400',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $booking = RentalBooking::select('booking_id', 'vehicle_id')->where('booking_id', $request->booking_id)->first();
        if (!$booking) {
            return $this->errorResponse('Invalid Booking');
        }

        $rentalReview = RentalReview::where('booking_id', $request->booking_id)->first();
        if ($rentalReview == '') {
            $rentalReview = new RentalReview();
        }
        $rentalReview->vehicle_id = $booking->vehicle_id;
        $rentalReview->booking_id = $request->input('booking_id');
        $rentalReview->customer_id = $this->userAuthDetails->customer_id; // Set customer_id based on authenticated user
        if ($request->input('rating') != '') {
            $rentalReview->rating = $request->input('rating');
        }
        if ($request->input('feedback_value') != '') {
            $rentalReview->review_text = $request->input('feedback_value');
        }
        $rentalReview->save();

        return $this->successResponse($rentalReview, 'Rental Review created successfully.');
    }

    public function cancelOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|exists:rental_bookings,booking_id'
        ]);
        // Check if validation fails
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $bookingId = $request->booking_id ?? '';
        $cancelRentalBookingMessage = '';
        $refundPercent = $refundAmount = 0;
        $rentalBooking = RentalBooking::select('booking_id', 'pickup_date', 'total_cost', 'status')
            ->where('status', 'confirmed')
            //->whereDate('pickup_date', '<', now()->format('Y-m-d'))
            ->where('pickup_date', '>', now()->format('Y-m-d H:i'))
            ->where('booking_id', $bookingId)
            ->first();

        if ($rentalBooking != '') {
            if ($rentalBooking->status == 'canceled') {
                return $this->errorResponse('This booking is already Canceled');
            }
            $pickupDateTime = $rentalBooking->pickup_date;
            $finalPaidAmount = $rentalBooking->total_cost;
            $currentDateTime = $this->currentDateTime;
            $pickupDateTime = Carbon::parse($pickupDateTime);
            $diffInHours = $pickupDateTime->diffInHours($currentDateTime);
            // OLD LOGIC
            // if($diffInHours > 48){
            //     $refundPercent = 100;
            // }elseif ($diffInHours > 24 && $diffInHours < 48) {
            //     $refundPercent = 50;
            // }elseif($diffInHours < 24){
            //     $refundPercent = 0;
            // }
            // if($refundPercent == 100){
            //     $refundAmount = $finalPaidAmount;
            // }elseif($refundPercent == 50){
            //     $refundAmount = $finalPaidAmount / 2;
            // }

            // NEW LOGIC
            if ($diffInHours > 72) {
                $refundPercent = 95; // 5 % Platform Fees
            } elseif ($diffInHours > 24 && $diffInHours <= 72) {
                $refundPercent = 50;
            } elseif ($diffInHours <= 24) {
                $refundPercent = 0;
            }
            if ($refundPercent == 95) {
                $refundAmount = ($finalPaidAmount * $refundPercent) / 100;
            } elseif ($refundPercent == 50) {
                $refundAmount = $finalPaidAmount / 2;
            }
        }
        if (isset($request->cancel_action) && strtolower($request->cancel_action) == 'yes') {
            if ($bookingId != '') {
                if ($rentalBooking != '') {
                    $cancelRentalBooking = new CancelRentalBooking();
                    $cancelRentalBooking->booking_id = $bookingId;
                    $cancelRentalBooking->hours_diffrence = $diffInHours;
                    $cancelRentalBooking->refund_percent = $refundPercent;
                    $cancelRentalBooking->refund_amount = $refundAmount;

                    $cancelData = [
                        'cancel_reason' => $request->cancel_reason ?? 'Cancelled by Customer via App',
                        'cancelled_by' => 'Customer'
                    ];
                    $cancelRentalBooking->data_json = json_encode($cancelData);

                    $cancelRentalBooking->save();

                    $rentalBooking->status = 'canceled';
                    $rentalBooking->save();

                    if ($refundAmount > 0) {
                        $cancelRentalBookingMessage = "Booking ID - #" . $bookingId . " is canceled and You will get ₹ " . $refundAmount . " Refund Amount ( " . $refundPercent . " % )";
                    } else {
                        $cancelRentalBookingMessage = "Booking ID - #" . $bookingId . " is canceled. You can't get any refund as you have cancelled vehicle within 24 Hours";
                    }

                    return $this->successResponse(['details' => $cancelRentalBookingMessage], 'Your cancel booking request initiated.. Now you will be get refund once Admin approve your cancel request');

                } else {
                    return $this->errorResponse('Booking can be cancelled if booking status is Confirm');
                }
            } else {
                return $this->errorResponse('Booking Not Found');
            }

        } else {
            if ($refundAmount > 0) {
                $cancelRentalBookingMessage = "Booking ID - #" . $bookingId . " is canceled and You will get ₹ " . $refundAmount . " Refund Amount ( " . $refundPercent . " % )";
            } else {
                $cancelRentalBookingMessage = "Booking ID - #" . $bookingId . " is not canceled. You can't get any refund as you have cancelled vehicle within 24 Hours";
            }
            return $this->successResponse(['details' => $cancelRentalBookingMessage]);
        }
    }

    public function getCoupons(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $customerId = '';
        if (Auth::guard('api')->check()) {
            $customerId = $this->userAuthDetails->customer_id;
        }
        $coupons = getAvailCoupons($request->start_date, $request->end_date, $customerId);

        return $this->successResponse($coupons, 'Coupons get successfully.');
    }

    public function getPricingShowCase(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,vehicle_id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $vehicleId = $request->vehicle_id;
        $vehicle = Vehicle::select('vehicle_id', 'model_id', 'rental_price')->where('vehicle_id', $vehicleId)->first();
        if (!$vehicle) {
            return $this->errorResponse('Vehicle not found');
        }
        $vehiclePriceDetails = VehiclePriceDetail::where('vehicle_id', $vehicleId)->get();
        //$vehiclePricingControl = VehiclePricingControl::where('vehicle_id', $vehicleId)->get();
        if (is_countable($vehiclePriceDetails) && count($vehiclePriceDetails)) {
            foreach ($vehiclePriceDetails as $k => $v) {
                if ($v->rate > 0) {
                    $tripAmount = $v->rate;
                    $unKMtripAmount = $v->rate * 1.3;
                    $perHourRate = $tripAmount / $v->hours; // Calculate per hour rate based on the total trip amount and duration
                    $duration = ($v->hours >= 24) ? round($v->hours / 24, 2) . ' days' : $v->hours . ' hours';
                    $durationHoursLimit = calculateKmLimit($v->hours);
                    //$pricingShowCase = [];
                    $pricingShowCase[$k]['duration'] = $duration;
                    $pricingShowCase[$k]['trip_amount_in_rupees'] = '₹' . number_format(($tripAmount), 2) . " ( " . $durationHoursLimit . " Km )";
                    $pricingShowCase[$k]['unlimited_km_trip_amount_in_rupees'] = '₹' . number_format(($unKMtripAmount), 2);
                    $pricingShowCase[$k]['per_hour_rate'] = '₹' . number_format(($perHourRate), 2);
                }
            }
            $summaryTable = $this->buildPricingTable($pricingShowCase);
            return $this->successResponse(['table_html' => $summaryTable], 'Pricing show case retrieved successfully.');
        } /*elseif(is_countable($vehiclePricingControl) && count($vehiclePricingControl) > 0) {
       foreach($vehiclePricingControl as $k => $v){
           if($v->trip_amount > 0){
               $tripAmount = $v->trip_amount;
               $unKMtripAmount = ($v->trip_amount) * 1.3;
               $perHourRate = $v->per_hour_rate; // Calculate per hour rate based on the total trip amount and duration
               $duration = $v->duration;
               $durationHoursLimit = $v->trip_amount_km_limit;
               //$pricingShowCase = [];
               $pricingShowCase[$k]['duration'] = $duration;
               $pricingShowCase[$k]['trip_amount_in_rupees'] = '₹' . number_format(($tripAmount), 2)." ( ".$durationHoursLimit." )";
               $pricingShowCase[$k]['unlimited_km_trip_amount_in_rupees'] = '₹' . number_format(($unKMtripAmount), 2);
               $pricingShowCase[$k]['per_hour_rate'] = '₹' . number_format(($perHourRate), 2);
           }
       }
       $summaryTable = $this->buildPricingTable($pricingShowCase);          
       return $this->successResponse(['table_html' => $summaryTable], 'Pricing show case retrieved successfully.');
   }*/ else {
            $rules = TripAmountCalculationRule::select('id', 'hours', 'multiplier')->orderBy('hours', 'desc')->get();
            $summaryTable = [];
            if (is_countable($rules) && count($rules) > 0) {
                $pricingShowCase = $rules->map(function ($rule) use ($vehicle) {
                    $tripAmount = $rule->multiplier * $vehicle->rental_price;
                    $unKMtripAmount = ($rule->multiplier * $vehicle->rental_price) * 1.3;
                    $perHourRate = $tripAmount / $rule->hours; // Calculate per hour rate based on the total trip amount and duration
                    $duration = ($rule->hours >= 24) ? round($rule->hours / 24, 2) . ' days' : $rule->hours . ' hours';
                    $durationHoursLimit = calculateKmLimit($rule->hours);
                    return [
                        'duration' => $duration,
                        'trip_amount_in_rupees' => '₹' . number_format(($tripAmount), 2) . " ( " . $durationHoursLimit . " Km )",
                        'unlimited_km_trip_amount_in_rupees' => '₹' . number_format(($unKMtripAmount), 2),
                        'per_hour_rate' => '₹' . number_format(($perHourRate), 2)
                    ];
                });
                $summaryTable = $this->buildPricingTable($pricingShowCase);
            }
            return $this->successResponse(['table_html' => $summaryTable], 'Pricing show case retrieved successfully.');
        }
    }

    protected function convertToHours($time)
    {
        sscanf($time, "%d %s", $value, $unit);
        return ($unit == 'day' || $unit == 'days') ? $value * 24 : $value;
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

    public function getNotifications(Request $request)
    {
        $cId = '';
        if (Auth::guard('api')->check()) {
            $cId = $this->userAuthDetails->customer_id;
        }

        $notificationLog = NotificationLog::select('id', 'customer_id', 'message_text', 'event_type', 'created_at')->where(['type' => 2, 'status' => 1]);
        if ($cId != '') {
            $notificationLog = $notificationLog->where('customer_id', $cId)->orWhereNull('customer_id');
            $page = $request->input('page');
            $pageSize = $request->input('page_size');
            if ($page !== null && $pageSize !== null) {
                $notificationLog = $notificationLog->orderBy('created_at', 'DESC')->paginate($pageSize, ['*'], 'page', $page);
            } else {
                $notificationLog = $notificationLog->orderBy('created_at', 'DESC')->get();
            }

            if (is_countable($notificationLog) && count($notificationLog) > 0) {
                foreach ($notificationLog as $key => $value) {
                    if ($value->event_type == 'new_booking')
                        $value->color_code = '#b6c3e9';
                    elseif ($value->event_type == 'extension')
                        $value->color_code = '#b6e9b8';
                    elseif ($value->event_type == 'completion')
                        $value->color_code = '#b0d3b5';
                    else
                        $value->color_code = '#2833a757';

                    $value->event_type = ucwords(str_replace('_', ' ', $value->event_type));
                }

                if ($page !== null && $pageSize !== null) {
                    $notificationLogArr = json_decode(json_encode($notificationLog->getCollection()->values()), FALSE);
                    return $this->successResponse($notificationLogArr, 'Notifications are get successfully.');
                } else {
                    //return $this->successResponse($notificationLog,'Notifications are get successfully.');
                    $notificationLogArr = json_decode(json_encode($notificationLog->values()), FALSE);
                    return $this->successResponse($notificationLogArr, 'Notifications are get successfully.');
                }
            } else {
                return $this->errorResponse('Notifications are not Found');
            }
        } else {
            return $this->errorResponse('User not Found');
        }
    }

    public function storeUserLocationDetails(Request $request)
    {
        $now = Carbon::now()->setTimezone('Asia/Kolkata');
        $formattedNow = $now->format('d-m-Y h:i A'); // e.g., "31-01-2025 04:28 PM"

        $validator = Validator::make($request->all(), [
            'from_datetime' => 'nullable|date|after_or_equal:' . $now->subHour()->toDateTimeString(),
            'to_datetime' => 'nullable|date|after:from_datetime',
            'city_id' => 'nullable|exists:cities,id',
        ], [
            'from_datetime.after_or_equal' => 'The "From Date and Time" must be at least 1 hour before ' . $formattedNow . '.',
            'from_datetime.date' => 'Please provide a valid date for "From Date and Time".',
            'to_datetime.after' => 'The "To Date and Time" must be after the "From Date and Time".',
            'to_datetime.date' => 'Please provide a valid date for "To Date and Time".',
        ]);

        // Add device_token validation conditionally
        $validator->sometimes('device_token', 'required', function ($input) {
            return !Auth::guard('api')->check();
        });

        // If validation fails, return error
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        // Determine user ID or device token based on authentication status
        if (Auth::guard('api')->check()) {
            $userLocationDetails = UserLocationDetail::firstOrNew([
                'customer_id' => $this->userAuthDetails->customer_id
            ]);
        } else {
            $userLocationDetails = UserLocationDetail::firstOrNew([
                'device_token' => $request->device_token
            ]);
        }

        // Update user location details
        $userLocationDetails->fill([
            'from_datetime' => $request->from_datetime ?? $userLocationDetails->from_datetime,
            'to_datetime' => $request->to_datetime ?? $userLocationDetails->to_datetime,
            'city_id' => $request->city_id ?? $userLocationDetails->city_id,
            'unlimited_km' => $request->unlimited_km ?? $userLocationDetails->unlimited_km,
        ]);

        $userLocationDetails->save();

        // Return the same data as the 'get' API
        $response = [
            'from_datetime' => $userLocationDetails->from_datetime,
            'to_datetime' => $userLocationDetails->to_datetime,
            'city_id' => $userLocationDetails->city_id,
            'city_name' => $userLocationDetails->city->name ?? null,
            'unlimited_km' => $userLocationDetails->unlimited_km
        ];

        return $this->successResponse($response, 'User Location details are stored successfully.');
    }

    public function getUserLocationDetails(Request $request)
    {
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');

        // Validation for device token if the user is not authenticated
        if (!Auth::guard('api')->check()) {
            $validator = Validator::make($request->all(), [
                'device_token' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator); // Return validation errors
            }
        }
        // Check authentication
        if (!Auth::guard('api')->check() && empty($request->device_token)) {
            return $this->errorResponse("Please pass Device Token");
        }

        $query = UserLocationDetail::select('from_datetime', 'to_datetime', 'city_id', 'unlimited_km')
            ->with(['city:id,name']);

        if (Auth::guard('api')->check()) {
            $query->where('customer_id', $this->userAuthDetails->customer_id);
        } else {
            $query->where('device_token', $request->device_token);
        }
        $userLocationDetail = $query->first();
        // If data is not found, return an error response   
        if (!$userLocationDetail) {
            return $this->errorResponse('User Location details are not found.');
        }

        if ($userLocationDetail) {
            if ($userLocationDetail && $userLocationDetail->from_datetime < Carbon::now()->subMinute() || $userLocationDetail->to_datetime < Carbon::now()->subMinute()) {
                $userLocationDetail->from_datetime = null;
                $userLocationDetail->to_datetime = null;
            }

            $typeId = $request->input('type_id') ?? 1;
            // Extract the city name from the related city data
            $response = [
                'from_datetime' => $userLocationDetail->from_datetime,
                'to_datetime' => $userLocationDetail->to_datetime,
                'city_id' => $userLocationDetail->city_id,
                'city_name' => $userLocationDetail->city->name ?? null,
                'unlimited_km' => $userLocationDetail->unlimited_km,
                'vehicles' => $this->getVehicles($userLocationDetail->city_id, $typeId, $userLocationDetail->from_datetime, $userLocationDetail->to_datetime, $latitude, $longitude),
            ];
        } else {
            $response = [];
        }
        if ($response != '') {
            return $this->successResponse($response, 'User Location details are get successfully.');
        } else {
            return $this->errorResponse('User Location details are not found.');
        }
    }

    public function getVehicles($cityId, $typeId = '', $startDate = '', $endDate = '', $latitude = NULL, $longitude = NULL)
    {
        if (isset($startDate) && $startDate != '' && isset($endDate) && $endDate != '') {
            // Parse the provided startDate and endDate as Carbon instances
            $startDate = Carbon::parse($startDate);
            $endDate = Carbon::parse($endDate);
        }
        // Initialize the query builder
        $query = Vehicle::with([/*'model.manufacturer', 'model.category', 'features' , 'images'*/])
            ->with([
                'properties' => function ($query) {
                    $query->select('vehicle_id', 'transmission_id', 'fuel_type_id', 'mileage', 'seating_capacity');
                }
            ])
            ->where('vehicles.availability', 1)
            ->where('vehicles.is_deleted', 0)
            ->withCount('rentalBookings')->withCount(['runningOrConfirmedBookings'])
            ->where('rental_price', '!=', 0)->where('publish', 1);

        if ($cityId) {
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


        if ($typeId != '') {
            $typeIds = is_array($typeId) ? $typeId : (is_string($typeId) ? explode(',', $typeId) : [$typeId]); // Wrap single int in an array
            $query = $query->whereHas('model.category', function ($query) use ($typeIds) {
                $query->whereIn('vehicle_type_id', $typeIds);
            });
        }

        // dd($query->get());
        if ($typeId === 1) { // 1 = Popular Cars
            $vehicle_type_id = 1;
            $query->whereHas('model.manufacturer', function ($query) use ($vehicle_type_id) {
                $query->where('vehicle_type_id', $vehicle_type_id);
            });
        } else if ($typeId === 2) { // 9 = Popular Bikes
            $vehicle_type_id = 2;
            $query->whereHas('model.manufacturer', function ($query) use ($vehicle_type_id) {
                $query->where('vehicle_type_id', $vehicle_type_id);
            });
        }

        // dd($query->get());
        $page = 1;
        $pageSize = 5;
        $setting = Setting::first();

        $vehicles = $query->get();
        //Hide unneccesary items
        $vehicles->each(function ($vehicle) {
            if ($vehicle->properties) {
                $vehicle->properties->makeHidden(['transmission', 'fuelType']);
            }
            if ($vehicle->model) {
                $vehicle->model->makeHidden(['model_id', 'category_id', 'model_image', 'manufacturer']);
            }
            $vehicle->setHidden(['branch_id', 'year', 'description', 'color', 'license_plate', 'availability_calendar', 'rental_price', 'extra_km_rate', 'extra_hour_rate', 'category_name', 'regular_images', 'model_id', 'availability', 'is_deleted', 'created_at', 'updated_at', 'branch']);
        });

        if ($startDate != '' && $endDate != '') {
            $vehicles = $vehicles->filter(function ($item) use ($startDate, $endDate, $setting) {
                //Check if particular vehicle is allocated with any booking then exclude that vehicle to show on list
                //if($setting != '' && $setting->show_all_vehicle == 1){
                if ($setting != '' && $setting->show_all_vehicle != 1) {
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

        if (is_countable($vehicles) && count($vehicles) > 0) {
            foreach ($vehicles as $key => $value) {
                $rentalPrice = $value->rental_price;
                $checkOffer = OfferDate::where('vehicle_id', $value->vehicle_id)->get();
                if (is_countable($checkOffer) && count($checkOffer) > 0) {
                    $rentalPrice = getRentalPrice($rentalPrice, $value->vehicle_id);
                }
                if ($startDate != '' && $endDate != '') {
                    $tripHours = $endDate->diffInHours($startDate);
                } else {
                    $tripHours = 24;
                }
                $pricePerHour = $this->calculateHourAmount($rentalPrice, $tripHours);
                $pricePerHour = '₹' . $pricePerHour . '/hr';
                $value->price_pr_hour = $pricePerHour;
            }
            foreach ($vehicles as $key => $value) {
                if ($startDate != '' && $endDate != '') {
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
                        if (is_countable($unavailabilityCalendar) && count($unavailabilityCalendar) > 0) {
                            foreach ($unavailabilityCalendar as $period) {
                                if (isset($period['start_date']) && isset($period['end_date'])) {
                                    //print_r($period['start_date']); die;
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
                    if ($existingBookings) {
                        $booked = true;
                        $booked_msg = 'RESERVED';
                    }
                    $value->booked = $booked;
                    $value->booked_msg = $booked_msg;
                }

                if (isset($latitude) && isset($longitude)) {
                    $finalDistanceInKm = 0;
                    $requestLat = $latitude;
                    $requestLong = $longitude;
                    if (isset($value->branch) && isset($value->branch->latitude) && isset($value->branch->longitude)) {
                        $branchLat = $value->branch->latitude;
                        $branchLong = $value->branch->longitude;
                        if (isset($branchLat) && isset($branchLong)) {
                            $distanceInKm = getDistanceInKm($requestLat, $requestLong, $branchLat, $branchLong);
                            $finalDistanceInKm = round($distanceInKm, 2);
                        }
                    } else {
                        $carEligibility = CarEligibility::where('vehicle_id', $value->vehicle_id)->first();
                        $carHostPickupLocation = CarHostPickupLocation::where('id', $carEligibility->car_host_pickup_location_id)->first();
                        if ($carHostPickupLocation != '') {
                            $lat = $carHostPickupLocation->latitude;
                            $long = $carHostPickupLocation->longitude;
                            if (isset($lat) && isset($long)) {
                                $distanceInKm = getDistanceInKm($requestLat, $requestLong, $lat, $long);
                                $finalDistanceInKm = round($distanceInKm, 2);
                            }
                        }
                    }
                    $value->distanceInKm = $finalDistanceInKm;
                }
            }

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


        if ($page !== null && $pageSize !== null) {
            // Manual pagination
            $offset = ($page - 1) * $pageSize;
            $vehicles = $vehicles->slice($offset, $pageSize)->values();
            $total = $vehicles->count();

            return $vehicles;

        } else {
            $vehiclesArr = json_decode(json_encode($vehicles->values()), FALSE);
            return $this->successResponse($vehiclesArr, 'Vehicles are get successfully.');
        }
    }

    public function calculateHourAmount($rentalPrice, $tripHours)
    {
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

    public function verifyOrder(Request $request)
    {
        $request->merge(['paymentGateway' => strtolower($request->paymentGateway)]);
        $validator = Validator::make($request->all(), [
            'paymentGateway' => 'required|string|in:cashfree,razorpay',
            'paymentResponse' => 'required|array',
            'paymentResponse.order_id' => 'required|string',
            'paymentResponse.message' => 'nullable|string',
            'paymentResponse.status' => 'required|string|in:SUCCESS,FAILED',
            'paymentResponse.payment_id' => 'nullable|string',
            'paymentResponse.signature' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $paymentGateway = $request->paymentGateway;
        $orderId = $request->paymentResponse['order_id'];
        $user = Auth::guard('api')->user();
        $emailVerifiedStatus = $user->email_verified_at != NULL && $user->email_verified_at != '' ? true : false;
        $dlStatus = $govtStatus = false;
        if (strtolower($user->documents['dl']) == 'approved') {
            $dlStatus = true;
        }
        if (strtolower($user->documents['govtid']) == 'approved') {
            $govtStatus = true;
        }
        $data['email_verified_status'] = $emailVerifiedStatus;
        $data['dl_status'] = $dlStatus;
        $data['govt_status'] = $govtStatus;

        if ($paymentGateway == 'cashfree') {
            // $payment = Payment::where('cashfree_order_id', $orderId)->first();
            $payment = Payment::where(['cashfree_order_id' => $orderId, 'payment_gateway_used' => 'cashfree'])->first();
            if (!$payment) {
                return $this->errorResponse('You have passed an invalid Order Id');
            }
            $cClientId = $cSecretId = $cUrl = '';
            if ($user && $user->is_test_user != 1) {
                $cClientId = $this->cashfreeClientId;
                $cSecretId = $this->cashfreeClientSecret;
                $cUrl = $this->cashfreeLiveUrl;
            } else {
                $cClientId = $this->cashfreeTestClientId;
                $cSecretId = $this->cashfreeTestClientSecret;
                $cUrl = $this->cashfreeSandBoxUrl;
            }

            try {
                $client = new Client();
                $response = $client->request('GET', $cUrl . '/' . $orderId, [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                        'x-api-version' => $this->cashfreeApiVersion,
                        'x-client-id' => $cClientId,
                        'x-client-secret' => $cSecretId,
                    ],
                ]);
                $body = $response->getBody()->getContents();
                $responseData = json_decode($body, true);
                if ($responseData && isset($responseData['order_amount'])) {
                    if ($payment->amount == $responseData['order_amount'] && strtolower($responseData['order_status']) == 'paid') {
                        $cashfreeCharges = 0;
                        /*try {
                            //Get Transaction Chargers
                            $url = $cUrl."/".$orderId."/settlements";
                            $cashfreeSettlementRes = $client->request('GET', $url, [
                                'headers' => [
                                    'x-client-id' => $cClientId,
                                    'x-client-secret' => $cSecretId,
                                    'x-api-version' => $this->cashfreeApiVersion,
                                    'Content-Type' => 'application/json',
                                ],
                                //'http_errors' => false
                            ]);
                            $responseBody = json_decode($cashfreeSettlementRes->getBody()->getContents(), true);
                            $cashfreeFees = $responseBody['service_charge'] ?? 0;
                            $cashfreeTax = $responseBody['service_tax'] ?? 0;
                            $cashfreeCharges = $cashfreeFees + $cashfreeTax;
                        }catch(Exception $e){
                            Log::error("Service charges error". json_encode($e->getMessage()));
                        }*/
                        $payment->payment_gateway_charges = $cashfreeCharges;
                        $payment->status = 'captured';
                        $payment->save();
                        $rentalBooking = RentalBooking::where('booking_id', $payment->booking_id)->first();
                        $rentalBooking->processCashfreePayment($payment);

                        if (strtolower($payment->payment_gateway_used) == 'cashfree' && $responseData['order_id'] != '') {
                            $adminPenalty = AdminPenalty::where('is_paid', 0)->where('booking_id', $payment->booking_id)->where('cashfree_order_id', $responseData['order_id'])->first();
                            if ($adminPenalty != '') {
                                $adminPenalty->is_paid = 1;
                                $adminPenalty->save();
                            }
                        }

                        return $this->successResponse($data, "Your order is paid successfully");
                    } else {
                        return $this->errorResponse("Your order is in Active Mode not yet completed");
                    }
                }
            } catch (Exception $e) {
                return $this->errorResponse($e->getMessage());
            }
        } elseif ($paymentGateway == 'razorpay') {
            if ($user && $user->is_test_user == 1) {
                $rKey = get_env_variable('RAZORPAY_API_KEY');
                $rSecret = get_env_variable('RAZORPAY_API_SECRET');
            } else {
                $rKey = get_env_variable('RAZORPAY_API_LIVE_KEY');
                $rSecret = get_env_variable('RAZORPAY_API_LIVE_SECRET');
            }
            $api = new Api($rKey, $rSecret);
            $payment = Payment::where('razorpay_order_id', $orderId)->first();
            if (!$payment) {
                return $this->errorResponse('You have passed an invalid Order Id');
            }
            $orderStatus = [];
            try {
                $orderStatus = $api->order->fetch($orderId)->payments();
            } catch (\Razorpay\Api\Errors\Error $e) {
                Log::error($e->getMessage());
            }

            if (is_countable($orderStatus['items']) && count($orderStatus['items']) > 0) {
                $items = $orderStatus->toArray();  // Convert the collection to an array
                $items = $items['items'] ?? [];
                //$items = $orderStatus['items'] ?? [];
                $capturedOrAuthorized = array_filter($items, function ($v) {
                    return !empty($v['status']) && in_array($v['status'], ['captured', 'authorized']) && !empty($v['id']);
                });
                if (!empty($capturedOrAuthorized)) {
                    $razorpayFees = 0;
                    $razorpayTax = 0;
                    $paymentData = reset($capturedOrAuthorized);
                    $razorpayFees = $paymentData['fee'] ?? 0;
                    $razorpayTax = $paymentData['tax'] ?? 0;
                    $razorpayCharges = $razorpayFees + $razorpayTax;
                    $razorpayCharges = (int) round($razorpayCharges);
                    $payment->update([
                        'status' => 'captured',
                        'razorpay_payment_id' => $paymentData['id'],
                        'payment_gateway_charges' => $razorpayCharges,
                    ]);
                    $rentalBooking = RentalBooking::where('booking_id', $payment->booking_id)->first();
                    $rentalBooking->processPayment($payment);

                    if (strtolower($payment->payment_gateway_used) == 'razorpay' && $orderId != '') {
                        $adminPenalty = AdminPenalty::where('is_paid', 0)->where('booking_id', $payment->booking_id)->where('razorpay_order_id', $orderId)->first();
                        if ($adminPenalty != '') {
                            $adminPenalty->is_paid = 1;
                            $adminPenalty->save();
                        }
                    }

                    return $this->successResponse($data, "Your order is paid successfully");
                } else {
                    return $this->errorResponse("Your order not yet completed");
                }
            }
        } else {
            return $this->errorResponse("Invalid payment gateway provided.");
        }
    }

    public function getReferralHistory(Request $request)
    {
        $user = '';
        if (Auth::guard('api')->check()) {
            $user = Auth::guard('api')->user();
        }
        $usedReferralUsers = CustomerReferralDetails::select('customer_id', 'payable_amount', 'is_paid', 'used_referral_code', 'booking_id')
            ->where('used_referral_code', trim($user->my_referral_code))
            ->where('payable_Amount', '>', 0);

        $page = $request->input('page');
        $pageSize = $request->input('page_size');
        if ($page !== null && $pageSize !== null) {
            $usedReferralUsers = $usedReferralUsers->paginate($pageSize, ['*'], 'page', $page);
        } else {
            $usedReferralUsers = $usedReferralUsers->get();
        }
        if (is_countable($usedReferralUsers) && count($usedReferralUsers) > 0) {
            foreach ($usedReferralUsers as $key => $val) {
                if ($val->customerDetails) {
                    $name = $val->customerDetails->firstname ?? '';
                    $name .= ' ' . $val->customerDetails->lastname ?? '';
                    $val->name = $name;
                    $val->payable_amount = '+ ' . $val->payable_amount;

                }
            }
            $usedReferralUsers->each(function ($referralUser) {
                if ($referralUser->customerDetails) {
                    $referralUser->makeHidden(['customerDetails']);
                }
            });

            if ($page !== null && $pageSize !== null) {
                $usedReferralUsersArr = json_decode(json_encode($usedReferralUsers->getCollection()->values()), FALSE);
                return $this->successResponse($usedReferralUsersArr, 'Details are get successfully.');
            } else {
                $usedReferralUsersArr = json_decode(json_encode($usedReferralUsers->values()), FALSE);
                return $this->successResponse($usedReferralUsersArr, 'Details are get successfully.');
            }
        } else {
            return $this->errorResponse("Details are not Found");
        }
    }

    public function getBankInfo(Request $request)
    {
        $user = '';
        if (Auth::guard('api')->check()) {
            $user = Auth::guard('api')->user();
        } else {
            return $this->errorResponse('User not found');
        }
        $userBanks = Bank::where('customer_id', $user->customer_id)->get();
        if (is_countable($userBanks) && count($userBanks) > 0) {
            foreach ($userBanks as $key => $value) {
                $value->customer_id = (string) $value->customer_id;
            }
            return $this->successResponse($userBanks, "Customer's Bank details are get successfully");
        } else {
            return $this->errorResponse("Customer's bank details are not found");
        }
    }

    public function storeBankInfo(Request $request)
    {
        $user = '';
        if (Auth::guard('api')->check()) {
            $user = Auth::guard('api')->user();
        } else {
            return $this->errorResponse('User not found');
        }

        // Conditional validation for image upload
        $rules = [
            'account_no' => 'required|max:18',
            'ifsc_code' => 'required',
        ];

        if (!$user->passbook_image) {
            $rules['passbook_image'] = 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048';
        } else {
            $rules['passbook_image'] = 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        if ($user != '') {
            $user->account_holder_name = $request->account_holder_name;
            $user->bank_name = $request->bank_name;
            $user->branch_name = $request->branch_name;
            $user->city = $request->city;
            $user->account_no = $request->account_no;
            $user->ifsc_code = $request->ifsc_code;
            $user->nick_name = $request->nick_name ?? NULL;

            // 📂 Handle file upload
            if ($request->hasFile('passbook_image')) {
                $file = $request->file('passbook_image');
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('bank_document'), $filename);
                $user->passbook_image = 'bank_document/' . $filename;
            }

            $user->save();

            return $this->successResponse($user, "Customer's Bank details are stored successfully");
        } else {
            return $this->errorResponse("User not found");
        }
    }


}
