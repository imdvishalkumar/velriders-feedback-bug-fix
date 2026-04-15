<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{AdminRentalBooking, CompanyDetail, Payment, Refund, CancelRentalBooking, Customer, Vehicle, Branch, CustomerDocument, Setting, AdminPenalty, BookingTransaction, RentalBooking, RentalBookingImage, OfferDate, CustomerReferralDetails};
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Razorpay\Api\Api;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use App\Rules\CheckCoupon;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\DataTables;
use App\Jobs\SendNotificationJob;
use TCPDF;

class RentalBookingController extends Controller
{
    public function getBookingList(){
        hasPermission('booking-history');

        $bookingIds = AdminRentalBooking::select('booking_id')->pluck('booking_id')->toArray();
        $customerArr = Customer::select('customer_id', 'firstname', 'lastname', 'mobile_number', 'email')->get();
        $vehicleArr = Vehicle::select('vehicle_id', 'model_id', 'license_plate', 'is_deleted')->get();

        return view('admin.bookings', compact('bookingIds', 'customerArr', 'vehicleArr'));
    }

    public function index($from = NULL){
        /*if($from != NULL && $from == 'pending')
            $renalBooking = AdminRentalBooking::with(['customer', 'vehicle', 'refund', 'vehicle.branch'])->where('status', 'pending')->get();
        else{
            if($from != '' && $from != 'pending'){*/
            if($from != ''){
                $renalBooking = AdminRentalBooking::with(['customer', 'vehicle', 'refund', 'vehicle.branch'])->where('status', $from)->get();        
            }
            else{
                $renalBooking = AdminRentalBooking::with(['customer', 'vehicle', 'refund', 'vehicle.branch'])/*->where('status', '!=', 'pending')*/->get();        
            }
        //}

        if(is_countable($renalBooking) && count($renalBooking) > 0){
            foreach ($renalBooking as $key => $value) {
                if(/*$value->calculation_details != '' && */(is_countable($value->price_summary) && count($value->price_summary) > 0)){
                    $cDetails = [];
                    foreach($value->price_summary as $k => $v){
                        if($k == 0){                          
                            $amountPos = strpos($v['key'], "Amount");
                            if ($amountPos !== false) {
                                $newString = substr($v['key'], 0, $amountPos + strlen("Amount"));
                                $v['key'] = strtolower(str_replace(' ', '_', $newString));
                            }
                        }
                        else{
                            $v['key'] = strtolower(str_replace(' ', '_', $v['key']));
                        }

                        if(str_starts_with($v['key'], 'coupon')){
                            $v['key'] = 'coupon' ;
                        }

                        $cDetails[$v['key']] = $v['value'];
                        $value->cDetails = $cDetails;    
                    }
                }else{
                    $value->cDetails = '';    
                }
                
                /*$rentalPrice = $value->vehicle->rental_price;
                $checkOffer = OfferDate::where('vehicle_id', $value->vehicle_id)->get();
                if(is_countable($checkOffer) && count($checkOffer) > 0){
                    $rentalPrice = getRentalPrice($rentalPrice, $value->vehicle_id);
                }*/
                //$value->updated_rental_price = $rentalPrice;
                $value->pDetails = $value->penalty_details ? json_decode($value->penalty_details) : '';

                $endJourneyOtpStatus = false;
                $startJourneyOtpStatus = false;
                $rentalBookingdata = DB::table('rental_bookings')->where('booking_id', $value->booking_id)->first();
                $calcDetails = BookingTransaction::where(['booking_id' => $value->booking_id])->get();
                if(is_countable($calcDetails) && count($calcDetails) > 0){
                    foreach ($calcDetails as $k => $v) {
                        if(isset($v->type) && $v->type == 'completion' && $v->is_deleted == 0) {
                            $endJourneyOtpStatus = true;
                        }
                    }
                }
                $value->endJourneyStaus = $endJourneyOtpStatus;

                $currentDate = Carbon::now()->setTimezone('Asia/Kolkata');
                $pickupDate = Carbon::parse($value->pickup_date);
                $returnDate = Carbon::parse($value->return_date);
                $adjustedPickupDate = $pickupDate->copy()->subMinutes(30);
                $adjustedReturnDate = $returnDate->copy();
                if ($currentDate >= $adjustedPickupDate && $currentDate <= $adjustedReturnDate) {
                    $startJourneyOtpStatus = true;
                }
                $value->startJourneyOtpStatus = $startJourneyOtpStatus;
            }           
        }
        
        return response()->json($renalBooking);
    }

    public function preview(Request $request, $booking_id)
    {
        hasPermission('booking-history');
        $bookingTransaction = '';
        $rentalBookingDetails = AdminRentalBooking::with(['customer', 'vehicle', 'payments'])->where('booking_id', $booking_id)->first();
        if($rentalBookingDetails != ''){
            $bookingTransaction = BookingTransaction::select('booking_id', 'additional_charges', 'additional_charges_info', 'late_return', 'exceeded_km_limit')->where(['booking_id' => $rentalBookingDetails->booking_id, 'type' => 'completion', 'paid' => 0])->first();
        }
        
        $vehicles = Vehicle::where('availability', 1)->where('is_deleted', 0)->where('vehicle_id', '!=', $rentalBookingDetails->vehicle_id)->get();
        $customerId = '';
        if($rentalBookingDetails->customer_id != ''){
            $customerId = $rentalBookingDetails->customer_id;
        }
        $currentDate = Carbon::now()->setTimezone('Asia/Kolkata');
        $returnDate = isset($rentalBookingDetails->return_date)?Carbon::parse($rentalBookingDetails->return_date):'';
        if(is_countable($rentalBookingDetails->price_summary) && count($rentalBookingDetails->price_summary) > 0){
            $cDetails = [];
            foreach($rentalBookingDetails->price_summary as $k => $v){
                $cDetails[$v['key']] = $v['value'];
                $rentalBookingDetails->cDetails = $cDetails;    
            }
        }else{
            $rentalBookingDetails->cDetails = '';    
        }
        return view('admin.bookingPreview' ,compact('rentalBookingDetails', 'vehicles', 'currentDate', 'returnDate', 'bookingTransaction'));
    }

    public function updateVehicle(Request $request){
        $status = false;
        $vehicleId = $request->vehicleId;
        $bookingId = $request->bookingId;
        $oldVal = $newVal = '';
        if($vehicleId != '' && $bookingId != ''){
            $rentalBooking = AdminRentalBooking::where('booking_id', $bookingId)->first();
            $oldVal = clone $rentalBooking;
            if($rentalBooking->initial_vehicle_id != ''){
                $rentalBooking->vehicle_id = $vehicleId;
                $rentalBooking->save();
                $status = true;
            }else{
                /*$rentalBooking->initial_vehicle_id = $rentalBooking->vehicle_id;
                $rentalBooking->vehicle_id = $vehicleId;
                $rentalBooking->save();*/
            }    
            $newVal = $rentalBooking;
        }
        $description = 'Vehicle changed for booking ID: '.$bookingId.' to vehicle ID: '.$vehicleId;
        logAdminActivity($description, $oldVal, $newVal);
        return response()->json($status);
    }

    public function getPaymentHistory(Request $request){
        $bookingId = $request->bookingId ?? '';
        $htmlContent = '';
        $data['status'] = 0;
        $data['details'] = '';
        if($bookingId != ''){
            $paymentDetails = Payment::where('booking_id', $bookingId)->get();
            if(is_countable($paymentDetails) && count($paymentDetails) > 0){
                $rKey = getRazorpayKey();
                $rSecret = getRazorpaySecret();
                $api = new Api($rKey, $rSecret);
                
                foreach ($paymentDetails as $key => $val) {
                    if($val->razorpay_order_id != ''){
                        try{
                            $paymentResponse = $api->order->fetch($val->razorpay_order_id)->payments();
                            if(is_countable($paymentResponse['items']) && count($paymentResponse['items']) > 0){
                                foreach ($paymentResponse['items'] as $k => $v) {
                                    $amount = $v['amount'] != '' ? ($v['amount'] / 100) : '';
                                    $amount = number_format($amount, 2);
                                    $htmlContent .= '<tr><td>'.$v['order_id'].'</td><td>'.$amount.'</td><td>'.strtoupper($v['status']).'</td><td>'.date('d-m-Y', $v['created_at']).'</td></tr>';    
                                }
                            }
                        } catch (\Razorpay\Api\Errors\Error $e) {
                            Log::error($e->getMessage());
                            continue;
                        }
                    } 
                }
                if($htmlContent != ''){
                    $data['status'] = 1;
                    $data['details'] = $htmlContent;
                }
            }
        }
        return response()->json($data);
    }   

    public function updateStartOtp(Request $request , $booking_id)
    {
        $startOtp = mt_rand(1000, 9999);
        $updateOtpStartRide = AdminRentalBooking::find($booking_id);
        $customer = Customer::find($updateOtpStartRide->customer_id);
        if($customer->is_blocked) {
            return response()->json(['success' => 'Customer is blocked can not generate OTP ', 'startOtp' => null]);
        }
        $updateOtpStartRide->start_otp = $startOtp;
        $updateOtpStartRide->save();

        $storeObject = clone $updateOtpStartRide;
        $storeObject->startotp = $startOtp;

        logAdminActivity("Generate Start OTP at Booking List", $storeObject);

        return response()->json(['success' => 'Start OTP sent successfully '. $startOtp, 'startOtp' => $startOtp]);
    }

    public function updateEndOtp(Request $request , $booking_id)
    {
        $endOtp = mt_rand(1000, 9999);
        $updateOtpStartRide = AdminRentalBooking::find($booking_id);
        $updateOtpStartRide->end_otp = $endOtp;
        $updateOtpStartRide->save();

        $storeObject = clone $updateOtpStartRide;
        $storeObject->endotp = $endOtp;

        logAdminActivity("Generate end OTP at Booking List", $storeObject);

        return response()->json(['success' => 'End OTP sent successfully '. $endOtp, 'endOtp' => $endOtp ]);
    }

    public function invoiceData(Request $request, $customer_id, $bookingId)
    {
        $extraKmString = '';
        $data = AdminRentalBooking::with(['vehicle.model.manufacturer', 'vehicle.model.category', 'vehicle.properties', 'vehicle.features', 'vehicle.images', 'customer'])->where('booking_id', $bookingId)->where('customer_id', $customer_id)->first();
        $companyDetails = CompanyDetail::select('id', 'address', 'phone', 'alt_phone', 'email', 'gst_no', 'pan_no', 'bank_name', 'bank_account_no', 'bank_ifsc_code')->first();
        $newBooking = $extension = $completion = $cFees = $adminPenaltiesDue = $newBookingVehicleServiceFees = $extensionVehicleServiceFees = $paidPenalties = $paidPenaltyServiceCharge = $duePenalties = $duePenaltyServiceCharge = $completionVehicleServiceFees = [];
        $totalAmt = $totalTax = $convenienceFees = $rateTotal = $completionDisplay = $amountDue = 0;
        $gstStatus = 1; // 1 = Consider CGST/SGST
        if($data && $data->customer && $data->customer->gst_number != null){
            if(str_starts_with($data->customer->gst_number, 24) == ''){
                $gstStatus = 2; // 2 = Consider IGST
            }
        }
        $newBookingTimeStamp = $completionNewBooking = $penaltyText = '';

        $calculationDetails = BookingTransaction::where(['booking_id' => $bookingId])->get();
        $gstPercent = $data->tax_rate ?? 0;
        if(is_countable($calculationDetails) && count($calculationDetails) > 0){
            foreach ($calculationDetails as $key => $value) {
                $commissionTaxAmount = $value->vehicle_commission_tax_amt ?? 0;
                if($value->type == 'new_booking' && $value->paid == 1){
                    //$newBookingTimeStamp = $value->timestamp;
                    $newBookingTimeStamp = date('d-m-Y H:i', strtotime($value->start_date)).' - '.date('d-m-Y H:i', strtotime($value->end_date));
                    $newBooking['trip_amount'] = number_format($value->trip_amount - $value->vehicle_commission_amount, 2);
                    $taxPercent = 0;
                    $mainAmt = $value->trip_amount;
                    if(isset($value->coupon_discount) && $value->coupon_discount != 0){
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
                    //$newBooking['total_amount'] = $newBooking['total_amount'] - $value->vehicle_commission_amount;
                    /*$paymentGateway = usedPaymentGateway($bookingId, 'new_booking', $value->razorpay_order_id, $value->cashfree_order_id);
                    $newBooking['payment_gateway'] = $paymentGateway['payment_gateway'];
                    $newBooking['payment_gateway_charges'] = $paymentGateway['payment_gateway_charges'];*/
                }elseif($value->type == 'extension' && $value->paid == 1){
                    //$extension['timestamp'][] = $value->timestamp;
                    $extension['timestamp'][] = date('d-m-Y H:i', strtotime($value->end_date));
                    $extension['trip_amount'][] = number_format(($value->trip_amount - $value->vehicle_commission_amount), 2);
                    $taxPercent = 0;
                    $mainAmt = $value->trip_amount;
                    if(isset($value->coupon_discount) && $value->coupon_discount != 0){
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
                    /*$paymentGateway = usedPaymentGateway($bookingId, 'extension', $value->razorpay_order_id, $value->cashfree_order_id);
                    $extension['payment_gateway'][] = $paymentGateway['payment_gateway'];
                    $extension['payment_gateway_charges'][] = $paymentGateway['payment_gateway_charges'];*/
                }elseif($value->type == 'completion' && $value->paid == 1){
                    $completionNewBooking = date('d-m-Y H:i', strtotime($value->timestamp));
                    $additionalCharges = $totalAmount = 0;
                    $penaltyText = '';
                    if(isset($value->late_return) && $value->late_return != '' && $value->late_return != 0){
                        $additionalCharges += $value->late_return;
                        $penaltyText .= ' Late Return - '. round($value->late_return, 2);
                    }
                    if(isset($value->exceeded_km_limit) && $value->exceeded_km_limit != '' && $value->exceeded_km_limit != 0){
                        $additionalCharges += $value->exceeded_km_limit;
                        if($value->late_return != 0){
                            $penaltyText .=  ' | ';    
                        }
                        if(is_countable($data->price_summary) && count($data->price_summary) > 0){
                            foreach($data->price_summary as $key => $val){
                                if(str_starts_with(strtolower($val['key']), 'extra')){
                                    $extraKmString = $val['key'];
                                }
                            }
                        }
                        //$penaltyText .=  'Exceed KM. Limit - '. $value->details->exceeded_km_limit . ' ( ' .$extraKmString.' )';
                        $penaltyText .=  $extraKmString;
                    }
                    if(isset($value->additional_charges) && $value->additional_charges != '' && $value->additional_charges != 0){
                        $additionalCharges += $value->additional_charges;
                        if($value->exceeded_km_limit != 0){
                             $penaltyText .=  ' | ';    
                        }
                        $penaltyText .=  ' Additional Charges - '. $value->additional_charges;
                    }
                    $completion['additional_charge'] = number_format( (round($additionalCharges, 2) - $value->vehicle_commission_amount), 2);
                    if(isset($value->tax_amt) && $value->tax_amt != '' && $value->tax_amt != 0){
                        $totalAmount += $value->tax_amt;
                    }
                    $taxPercent = 0;
                    $mainAmt = $additionalCharges;
                    if(isset($value->coupon_discount) && $value->coupon_discount != 0){
                        $mainAmt = $value->trip_amount - $value->coupon_discount;
                    }    
                    $vehiclePercent = $value->rentalBooking->vehicle->commission_percent ?? 0;
                    $taxPercent = getTaxPercent($mainAmt, $value->tax_amt, $value->trip_amount_to_pay, $vehiclePercent, $gstPercent, $commissionTaxAmount);
                    $completion['tax_percent'] = number_format($taxPercent, 2);
                    $completion['tax_amount'] = number_format(($value->tax_amt - $value->vehicle_commission_tax_amt), 2);
                    $completion['coupon_discount'] = number_format(0, 2);
                    $completion['total_amount'] =  number_format( round(($totalAmount + $additionalCharges), 2) - ($value->vehicle_commission_amount + $value->vehicle_commission_tax_amt), 2);
                    if($data->booking_id != 1805){
                        $totalAmt += $value->tax_amt; 
                        $totalAmt += $additionalCharges;
                        $totalTax += $value->tax_amt;
                        $rateTotal += $additionalCharges;
                    }else{
                        $amountDue += 227617.59;
                    }
                    if($completion['additional_charge'] != 0 || $completion['total_amount'] != 0){
                        $completionDisplay = 1;
                    }

                    $completionVehicleServiceFees['trip_amount'] = number_format($value->vehicle_commission_amount, 2);
                    $completionVehicleServiceFees['tax_percent'] = $value->vehicle_commission_amount != 0 && $value->vehicle_commission_tax_amt != 0 ? number_format(18, 2) : number_format(0, 2);
                    $completionVehicleServiceFees['tax_amount'] = number_format($value->vehicle_commission_tax_amt, 2);
                    $completionVehicleServiceFees['coupon_discount'] = number_format(0, 2);
                    $completionVehicleServiceFees['total_amount'] = number_format( ($value->vehicle_commission_amount + $value->vehicle_commission_tax_amt), 2);
                    //$completion['total_amount'] = $completion['total_amount'] - $value->vehicle_commission_amount;
                    /*$paymentGateway = usedPaymentGateway($bookingId, 'completion', $value->razorpay_order_id, $value->cashfree_order_id);
                    $completion['payment_gateway'] = $paymentGateway['payment_gateway'];
                    $completion['payment_gateway_charges'] = $paymentGateway['payment_gateway_charges'];*/
                }elseif($value->type == 'penalty' && $value->paid == 1){
                    if($value->final_amount > 0){
                        $paidPenalties['timestamp'][] = date('d-m-Y H:i', strtotime($value->timestamp));
                        $mainAmt = $value->total_amount;
                        if(isset($value->coupon_discount) && $value->coupon_discount != 0){
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
                        $paidPenaltyServiceCharge['total_amount'][] = number_format( ($value->vehicle_commission_amount + $value->vehicle_commission_tax_amt), 2);

                        $rateTotal += $value->total_amount;
                        $rateTotal += $value->vehicle_commission_amount;
                        $totalTax += $value->tax_amt;
                        $totalTax += $value->vehicle_commission_tax_amt;

                        $totalAmt += $value->total_amount + $value->tax_amt;
                        $totalAmt += $value->vehicle_commission_amount + $value->vehicle_commission_tax_amt;
                    }
                }elseif($value->type == 'penalty' && $value->paid == 0){
                    $duePenalties['timestamp'][] = date('d-m-Y H:i', strtotime($value->timestamp));
                    $mainAmt = $value->total_amount;
                    if(isset($value->coupon_discount) && $value->coupon_discount != 0){
                        $mainAmt = $value->total_amount - $value->coupon_discount;
                    }   
                    $duePenalties['trip_amount'][] = number_format($value->total_amount ?? 0, 2);
                    $vehiclePercent = $value->rentalBooking->vehicle->commission_percent ?? 0;
                    $taxPercent = ($gstPercent == 0.05) ? 5 : (($gstPercent == 0.18) ? 18 : 0);
                    $duePenalties['tax_percent'][] = $taxPercent;
                    // OLD CODE
                    //$duePenalties['tax_amount'][] = number_format($value->tax_amt ?? 0, 2);
                    //$duePenalties['total_amount'][] = number_format(($value->total_amount + $value->tax_amt), 2);
                    // NEW CODE
                    if($value->tax_amt > $value->vehicle_commission_tax_amt){
                        $penaltyTax = $value->tax_amt - $value->vehicle_commission_tax_amt;
                        $duePenalties['tax_amount'][] = number_format($penaltyTax ?? 0, 2);
                        $duePenalties['total_amount'][] = number_format(($value->total_amount + $penaltyTax), 2);
                    }else{
                        $duePenalties['tax_amount'][] = number_format($value->tax_amt ?? 0, 2);
                        $duePenalties['total_amount'][] = number_format(($value->total_amount + $value->tax_amt), 2);
                    }
                    $duePenalties['coupon_discount'][] = number_format(0, 2);

                    $duePenaltyServiceCharge['trip_amount'][] = number_format($value->vehicle_commission_amount, 2);
                    $duePenaltyServiceCharge['tax_percent'][] = $value->vehicle_commission_amount != 0 && $value->vehicle_commission_tax_amt != 0 ? number_format(18, 2) : number_format(0, 2);
                    $duePenaltyServiceCharge['tax_amount'][] = number_format($value->vehicle_commission_tax_amt, 2);
                    $duePenaltyServiceCharge['coupon_discount'][] = number_format(0, 2);
                    $duePenaltyServiceCharge['total_amount'][] = number_format( ($value->vehicle_commission_amount + $value->vehicle_commission_tax_amt), 2);

                    $amountDue += ($value->total_amount + $value->tax_amt);
                    //$amountDue += ($value->vehicle_commission_amount + $value->vehicle_commission_tax_amt);
                    $amountDue += ($value->vehicle_commission_amount);
                }
            }
            
            //Convenience Fees Calculation
            $newConvenienceFees = $convenienceFees / (1 + (18/100));
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
        $pdf = PDF::loadView('booking-invoice', compact('data', 'companyDetails', 'newBooking', 'extension', 'completion', 'totalAmt', 'totalTax', 'convenienceFees', 'cFees', 'rateTotal', 'penaltyText', 'gstStatus', 'completionDisplay', 'extraKmString', 'newBookingTimeStamp', 'completionNewBooking', 'adminPenaltiesDue'/*, 'vehiclePercentAmt'*/, 'newBookingVehicleServiceFees', 'extensionVehicleServiceFees', 'completionVehicleServiceFees', 'paidPenalties', 'paidPenaltyServiceCharge', 'duePenalties', 'duePenaltyServiceCharge', 'amountDue'))->setPaper('A3');
        return $pdf->stream('booking-invoice.pdf');
    }


    public function summaryData(Request $request, $customer_id, $bookingId)
    {
        $data = AdminRentalBooking::with(['vehicle.model.manufacturer', 'vehicle.model.category', 'vehicle.properties', 'vehicle.features', 'vehicle.images', 'customer'])->where('booking_id', $bookingId)->where('customer_id', $customer_id)->first();
        $customerDoc = CustomerDocument::select('document_id', 'customer_id', 'document_type', 'is_approved', 'id_number')->where('customer_id', $customer_id)->get();
        $docDetails['gov_status'] = '';
        $docDetails['gov_id_number'] = '';
        $docDetails['dl_status'] = '';
        $docDetails['dl_id_number'] = '';
        if(is_countable($customerDoc) && count($customerDoc) > 0){
            foreach($customerDoc as $key => $val){
                if(strtolower($val->document_type) == 'govtid'){
                    $docDetails['gov_status'] = isset($val->is_approved)?$val->is_approved:'';            
                    $docDetails['gov_id_number'] = isset($val->id_number)?$val->id_number:'';            
                }
                if(strtolower($val->document_type) == 'dl'){
                    $docDetails['dl_status'] = isset($val->is_approved)?$val->is_approved:''; 
                    $docDetails['dl_id_number'] = isset($val->id_number)?$val->id_number:'';
                }
            }
        }
        $data->gov_status = $docDetails['gov_status'] ;
        $data->gov_id_number = $docDetails['gov_id_number'] ;
        $data->dl_status = $docDetails['dl_status'] ;
        $data->dl_id_number = $docDetails['dl_id_number'] ;

        $filename = 'booking-summary-' . $bookingId . '.pdf';
        $pdf = PDF::loadView('booking-summary', compact('data'));
        return $pdf->stream('booking-summary.pdf');
    }

    public function getPenalty(Request $request){
        $data['penalty_amt'] = 0;
        $data['penalty_info'] = '';
        $booking = AdminRentalBooking::where('booking_id', $request->bookingId)->first();
        if($booking != '' && $booking->penalty_details != NULL){
            $penaltyInfo = json_decode($booking->penalty_details);
            $data['penalty_amt'] = $penaltyInfo->amount;
            $data['penalty_info'] = $penaltyInfo->penalty_details;
         }

        return response()->json($data);
    }

    public function storePenalty(Request $request){
        $payableAmt = $request->amount ?? 0;
        $jsonDetails = json_encode($request->except(['_token']));
        $rentalBooking = AdminRentalBooking::where('booking_id', $request->bookingId)->first();
        $rentalBooking->penalty_details = $jsonDetails; //temporary
        $rentalBooking->updated_at = date('Y-m-d H:i:s');
        $rentalBooking->save();

        $taxAmt = 0; 
        $adminPenalty = AdminPenalty::where('booking_id', $request->bookingId)->where('is_paid', 0)->first();
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
            $taxRate = $customerGst ? 0.18 : 0.05;
        }
        
        //$customerGst = $rentalBooking->customer->gst_number ?? ''; 
        //$taxRate = $customerGst ? 0.18 : 0.05;
        $taxAmt = $payableAmt * $taxRate;
        $taxAmt += $vehicleCommissionTaxAmt;
        $adminPenalty->booking_id = $request->bookingId;
        $adminPenalty->amount = $payableAmt ?? 0;
        $adminPenalty->penalty_details = $request->penalty_details ?? '';
        $adminPenalty->save();

        $bookingTransaction = BookingTransaction::where(['booking_id' => $rentalBooking->booking_id, 'type' => 'penalty', 'paid' => 0])->first();
        if($bookingTransaction == ''){
            $bookingTransaction = new BookingTransaction();    
        }
        $final_amt = $payableAmt + $taxAmt;

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

        logAdminActivity("Admin Penalty Added", $object);
        
        return redirect()->route('admin.bookings')->with('success', 'Penalty details are added successfully');
    }

    public function customerRefundList(Request $request){

        hasPermission('customer-refund');
        if($request->ajax()){
            $rentalBooking = AdminRentalBooking::select('booking_id', 'customer_id', 'vehicle_id', 'total_cost', 'status')->with(['customer', 'vehicle', 'refund'])->whereIn('status', ['completed', 'no show'])->get();
            foreach ($rentalBooking as $key => $value) {
                $calcDetails = BookingTransaction::where(['booking_id' => $value->booking_id])->get();
                if(is_countable($calcDetails) && count($calcDetails) > 0){
                    foreach ($calcDetails as $k => $v) {
                        if($value->status == 'completed'){
                            if(isset($v->type) && $v->type == 'completion'){
                                $value->refund_amount = $v->refundable_deposit;
                                break;
                            }
                        }elseif($value->status == 'no show'){
                            if(isset($v->type) && $v->type == 'new_booking'){
                                $value->refund_amount = $v->refundable_deposit;
                                break;
                            }
                        }
                    }
                }
                /*$calcDetails = json_decode($value->calculation_details);
                if(is_countable($calcDetails->versions) && count($calcDetails->versions) > 0){
                    foreach ($calcDetails->versions as $k => $v) {
                        if($value->status == 'completed'){
                            if(isset($v->type) && $v->type == 'completion'){
                                $value->refund_amount = $v->details->refundable_deposit;
                                break;
                            }
                        }elseif($value->status == 'no show'){
                            if(isset($v->type) && $v->type == 'new_booking'){
                                $value->refund_amount = $v->details->refundable_deposit;
                                break;
                            }
                        }
                    }
                }*/
            }
            return $rentalBooking;
        }

        $balance = getRazorpayBalance();
        return view('admin.cutomer-refund', compact('balance'));
    }

    public function customerCanceledRefund(Request $request){
        hasPermission('customer-canceled-refund');
        if($request->ajax()){
            $cancelBookings = CancelRentalBooking::with(['rentalBooking', 'rentalBooking.customer', 'rentalBooking.vehicle', 'refund'])->get();
            return $cancelBookings;
        }

        $balance = getRazorpayBalance();
        return view('admin.customer-canceled-refund', compact('balance'));
    }

    public function customerRefundProcess(Request $request){
        hasPermission('customer-refund');
        $bookingId = $request->bookingId;
        $booking_details = AdminRentalBooking::where('booking_id', $bookingId)->first();
        if($booking_details != ''){
            $refundAmt = 0;
            $razorpayPaymentId = $razorpayOrderId = '';

            $calcDetails = BookingTransaction::where(['booking_id' => $bookingId])->get();
            if(is_countable($calcDetails) && count($calcDetails) > 0){
            /*$calcDetails = json_decode($booking_details->calculation_details);
                if(is_countable($calcDetails->versions) && count($calcDetails->versions) > 0){*/
                    /*foreach ($calcDetails->versions as $k => $v) {
                        if(isset($v->type) && $v->type == 'new_booking'){
                            $newBookRefundAmt = $v->details->refundable_deposit;
                            $newBookrazorpayPaymentId = $v->details->order->razorpay_payment_id != ''?$v->details->order->razorpay_payment_id:'';
                            $newBookrazorpayOrderId = $v->details->order->razorpay_order_id != ''?$v->details->order->razorpay_order_id:'';
                        }

                        if($booking_details->status == 'completed'){
                            if(isset($v->type) && $v->type == 'completion'){
                                $refundAmt = $v->details->refundable_deposit;
                                $razorpayPaymentId = $v->details->order->razorpay_payment_id != ''?$v->details->order->razorpay_payment_id:$newBookrazorpayPaymentId;
                                $razorpayOrderId = $v->details->order->razorpay_order_id != ''?$v->details->order->razorpay_order_id:$newBookrazorpayOrderId;
                                break;
                            }
                        }elseif($booking_details->status == 'no show'){
                            $refundAmt = $newBookRefundAmt;
                            $razorpayPaymentId = $newBookrazorpayPaymentId;
                            $razorpayOrderId = $newBookrazorpayOrderId;
                            break;
                        }
                    }*/
                foreach ($calcDetails as $k => $v) {
                    if(isset($v->type) && $v->type == 'new_booking'){
                        $newBookRefundAmt = $v->refundable_deposit;
                        $newBookrazorpayPaymentId = $v->razorpay_payment_id != ''?$v->razorpay_payment_id:'';
                        $newBookrazorpayOrderId = $v->razorpay_order_id != ''?$v->razorpay_order_id:'';
                    }
                    if($booking_details->status == 'completed'){
                        if(isset($v->type) && $v->type == 'completion'){
                            $refundAmt = $v->refundable_deposit;
                            $razorpayPaymentId = $v->razorpay_payment_id != ''?$v->razorpay_payment_id:$newBookrazorpayPaymentId;
                            $razorpayOrderId = $v->razorpay_order_id != ''?$v->razorpay_order_id:$newBookrazorpayOrderId;
                            break;
                        }
                    }elseif($booking_details->status == 'no show'){
                        $refundAmt = $newBookRefundAmt;
                        $razorpayPaymentId = $newBookrazorpayPaymentId;
                        $razorpayOrderId = $newBookrazorpayOrderId;
                        break;
                    }
                }
            }

            if($refundAmt != 0){
                $payment = Payment::where(function ($query) use($razorpayOrderId, $razorpayPaymentId) {
                    $query->where('razorpay_order_id',  $razorpayOrderId)
                          ->orWhere('razorpay_payment_id', $razorpayPaymentId);
                })->first();
                $paymentId = '';
                if($payment != ''){
                    $paymentId = $payment->payment_id;
                }
                $refund = new Refund();
                $refund->booking_id = $bookingId;
                $refund->payment_id = $paymentId;
                $refund->refund_amount = $refundAmt;
                $refund->status = 'processed';
                $refund->save();
                
                $calculationDetails = BookingTransaction::where(['booking_id' => $bookingId])->get();
                if(is_countable($calculationDetails) && count($calculationDetails) > 0){
                    foreach ($calculationDetails as $version) {
                        if($version->type === 'new_booking') {
                            $version->refund_processed = true;
                            $version->refund_amount = $refundAmt;
                            $version->razorpay_refund_id = '';
                            $version->save();
                        }
                    }
                    /*$calculationDetails = json_decode($booking_details->calculation_details, true);
                    foreach ($calculationDetails['versions'] as &$version) {
                        if($version['type'] === 'new_booking') {
                            $version['details']['refund']['processed'] = true;
                            $version['details']['refund']['amount'] = $refundAmt;
                            $version['details']['refund']['razorpay_refund_id'] = '';
                        }
                    }
                    $booking_details->calculation_details = json_encode($calculationDetails);
                    $booking_details->save();*/

                    return response()->json(['message' => 'Refund Process is Initiated.']);   
                }else{
                    return response()->json(['message' => 'Something went wrong']);
                }
            }
            else{
                return response()->json(['message' => 'Booking Details are not found']);
            }
        }
    }

    public function customerCanceledRefundProcess(Request $request){
        hasPermission('customer-canceled-refund');
        $bookingId = $request->bookingId;
        $cancelBooking = CancelRentalBooking::where('booking_id', $bookingId)->first();
        $booking_details = AdminRentalBooking::where('booking_id', $bookingId)->first();
        if($cancelBooking != ''){
            $refundAmt = 0;
            $razorpayPaymentId = $razorpayOrderId = '';
            $calcDetails = BookingTransaction::where(['booking_id' => $bookingId])->get();
            if(is_countable($calcDetails) && count($calcDetails) > 0){
                foreach ($calcDetails as $k => $v) {
                    if(isset($v->type) && $v->type == 'new_booking'){
                        $razorpayPaymentId = $v->razorpay_payment_id != ''?$v->razorpay_payment_id:'';
                        $razorpayOrderId = $v->razorpay_order_id != ''?$v->razorpay_order_id:'';
                    }
                }
            }
            /*$calcDetails = json_decode($booking_details->calculation_details);
            if(is_countable($calcDetails->versions) && count($calcDetails->versions) > 0){
                foreach ($calcDetails->versions as $k => $v) {
                    if(isset($v->type) && $v->type == 'new_booking'){
                        $razorpayPaymentId = $v->details->order->razorpay_payment_id != ''?$v->details->order->razorpay_payment_id:'';
                        $razorpayOrderId = $v->details->order->razorpay_order_id != ''?$v->details->order->razorpay_order_id:'';
                    }
                }
            }*/

            if(isset($cancelBooking->refund_amount) && $cancelBooking->refund_amount != ''){
                $refundAmt = (int)$cancelBooking->refund_amount;
            }
            
            if($refundAmt != 0){
                $payment = Payment::where(function ($query) use($razorpayOrderId, $razorpayPaymentId) {
                    $query->where('razorpay_order_id',  $razorpayOrderId)
                          ->orWhere('razorpay_payment_id', $razorpayPaymentId);
                })->first();
                $paymentId = '';
                if($payment != ''){
                    $paymentId = $payment->payment_id;
                }
                $refund = new Refund();
                $refund->booking_id = $bookingId;
                $refund->payment_id = $paymentId;
                $refund->refund_amount = $refundAmt;
                $refund->status = 'processed'; 
                $refund->save();    

                $calculationDetails = BookingTransaction::where(['booking_id' => $bookingId])->get();
                if(is_countable($calculationDetails) && count($calculationDetails) > 0){
                    foreach ($calculationDetails as $version) {
                        if($version->type === 'new_booking') {
                            $version->refund_processed = true;
                            $version->refund_amount = $refundAmt;
                            $version->razorpay_refund_id = '';
                            $version->save();
                        }
                    }
                /*$calculationDetails = json_decode($booking_details->calculation_details, true);
                foreach ($calculationDetails['versions'] as &$version) {
                    if($version['type'] === 'new_booking') {
                        $version['details']['refund']['processed'] = true;
                        $version['details']['refund']['amount'] = $refundAmt;
                        $version['details']['refund']['razorpay_refund_id'] = '';
                    }
                }
                $booking_details->calculation_details = json_encode($calculationDetails);
                $booking_details->save();*/
                
                $cancelBooking = CancelRentalBooking::where('booking_id', $bookingId)->first();
                $cancelBooking->refund_status = 1;
                $cancelBooking->save();
                
                return response()->json(['message' => 'Refunded Process is Initiated.']);    
                /*$razorpayRefundOrder = $this->refundOrder($razorpayPaymentId, $refundAmt, $bookingId);
                if($razorpayRefundOrder && isset($razorpayRefundOrder->id) && $razorpayRefundOrder->id != ''){
                    $refund->rezorpay_refund_id = isset($razorpayRefundOrder->id) ? $razorpayRefundOrder->id : NULL;    
                    $refund->save();
                    $res = $this->setRefundStatus($razorpayRefundOrder->id, $razorpayPaymentId, $bookingId, 2); //2 means it called from the cancel refund
                    if($res){
                        logAdminActivity("Customer Refund Process Started", $refund);
                        return response()->json(['message' => 'Refunded Process Initiated. It will be reflact in next 5 to 7 working days..']);    
                    }
                }else{
                    return response()->json(['message' => 'Something went wrong']);
                }*/
                
                }else{
                    return response()->json(['message' => 'Something went wrong']);
                }
            }
            else{
                return response()->json(['message' => 'Booking Details are not found']);
            }
        }
    }

    public function refundOrder($paymentId, $refundAmt, $bookingId){
        $apiKey = getRazorpayKey();
        $apiSecret = getRazorpaySecret();
        $api = new Api($apiKey, $apiSecret);
        try {
            $razorpayRefund = $api->payment->fetch($paymentId)->refund(array("amount"=> $refundAmt * 100,"speed"=>"normal","receipt"=>""));
            //$razorpayRefund = $api->payment->fetch($paymentId)->refund(array("amount"=> $refundAmt * 100,"speed"=>"optimum","receipt"=>""));

            return $razorpayRefund;
        } catch (\Razorpay\Api\Errors\Error $e) {
             return response()->json(['message' => 'Razorpay refund process failed: ' . $e->getMessage()]);
        }
    }

    public function setRefundStatus($razorpayRefundOrderId, $razorpayPaymentId, $booking_id, $calledFrom){
        if($razorpayRefundOrderId != '' && $razorpayPaymentId != '' && $booking_id != ''){
            $apiKey = getRazorpayKey();
            $apiSecret = getRazorpaySecret();
            $api = new Api($apiKey, $apiSecret);
            try{
                $refundResponse = $api->payment->fetch($razorpayPaymentId)->fetchRefund($razorpayRefundOrderId);
                if($refundResponse && isset($refundResponse['status']) && $refundResponse['status'] != '' && strtolower($refundResponse['status']) == 'processed'){
                    $rentalBooking = AdminRentalBooking::where('booking_id', $booking_id)->first();
                    if($rentalBooking != ''){
                        $refund = Refund::where(['rezorpay_refund_id' => $razorpayRefundOrderId,'payment_id' => $rentalBooking->payment->payment_id, 'booking_id' => $booking_id])->first();   
                        if($refund != ''){
                            $refund->status = 'processed';
                            $refund->save();

                            $calculationDetails = BookingTransaction::where(['booking_id' => $booking_id])->get();
                            if(is_countable($calculationDetails) && count($calculationDetails) > 0){
                                foreach ($calculationDetails as $version) {
                                    if($version->type === 'new_booking') {
                                        $version->refund_processed = true;
                                        $version->refund_amount = $refund->refund_amount;
                                        $version->razorpay_refund_id =  $razorpayRefundOrderId;
                                        $version->save();
                                    }
                                }
                            /*$calculationDetails = json_decode($rentalBooking->calculation_details, true);
                            foreach ($calculationDetails['versions'] as &$version) {
                                if($version['type'] === 'new_booking') {
                                    $version['details']['refund']['processed'] = true;
                                    $version['details']['refund']['amount'] = $refund->refund_amount;
                                    $version['details']['refund']['razorpay_refund_id'] = $razorpayRefundOrderId;
                                }
                            }
                            $rentalBooking->calculation_details = json_encode($calculationDetails);
                            $rentalBooking->save();*/
                            }

                            if($calledFrom == 2){
                                $cancelBooking = CancelRentalBooking::where('booking_id', $booking_id)->first();
                                $cancelBooking->refund_status = 1;
                                $cancelBooking->save();
                            }

                            return true;
                        }
                    }
                }
            }catch(\Razorpay\Api\Errors\Error $e){
                //return response()->json(['message' => 'Razorpay refund process failed: ' . $e->getMessage()]);
                return false;
            }
        }else{
            //return response()->json(['message' => 'Refund Id OR Payment Id OR Booking Id not found']);            
            return false;
        }
    }

    public function KmUpdate(Request $request){
        if ($request->ajax()) {
            if(isset($request->name) && isset($request->value) && isset($request->pk)){
                $rentalBooking = AdminRentalBooking::where('booking_id', $request->pk)->first();
                if($rentalBooking != ''){
                    if($request->name == 'startKm'){
                        $rentalBooking->start_kilometers = $request->value;
                        $rentalBooking->save();
                        $object = clone $rentalBooking;
                        logAdminActivity("Start Kilometers Value Updated In Booking History", $object);
                    }
                    if($request->name == 'endKm'){
                        $rentalBooking->end_kilometers = $request->value;
                        $rentalBooking->save();
                        $object = clone $rentalBooking;
                        logAdminActivity("End Kilometers Value Updated In Booking History", $object);
                    }

                    return response()->json(['success' => true]);
                }
                else{
                    return response()->json(['success' => false]);
                }
            }else{
                    return response()->json(['success' => false]);
                }
        }else{
            return response()->json(['success' => false]);
        }
    }

    public function addBooking(Request $request){
        hasPermission('add-booking');

        /*$vehicles = Vehicle::where('is_deleted', 0)->get();
        if(is_countable($vehicles) && count($vehicles) > 0){
            foreach ($vehicles as $key => $value) {
                $value->availability_calendar = '[{"start_date":"","end_date":"","reason":""}]';
                $value->save();
            }
        }*/
        $customers = Customer::select('customer_id', 'firstname', 'lastname')->where('is_deleted', 0)->get();
        $vehicles = Vehicle::where('is_deleted', 0)->where('availability', true)->with('model', function($query){
            $query->select('model_id', 'name');
        })->get();

        // if(is_countable($vehicles) && count($vehicles) > 0){
        //     $currentDateTime = Carbon::now()->setTimezone('Asia/Kolkata');
        //     $vehicles = $vehicles->filter(function ($item) use ($currentDateTime) {
        //         $availabilityCal = isset($item->availability_calendar)?$item->availability_calendar:'';
        //         if($availabilityCal != ''){
        //             $availabilityCal = json_decode($availabilityCal);
        //             if(is_countable($availabilityCal) && count($availabilityCal) > 0){
        //                 foreach($availabilityCal as $k => $v){
        //                     $startDateTime = isset($v->start_date) ? Carbon::parse($v->start_date) : '';
        //                     $endDateTime = isset($v->end_date) ? Carbon::parse($v->end_date) : '';
        //                     if($startDateTime != '' && $endDateTime != '' && $currentDateTime >= $startDateTime && $currentDateTime <= $endDateTime){
        //                         return false;
        //                     }
        //                 }
        //             }
        //         }
        //         return true;
        //     });
        // }
        return view('admin.add-booking', compact('vehicles', 'customers'));
    }

    public function getPriceSummary(Request $request){
        $data['status'] = false;
        $data['message'] = '';
        $data['data'] = '';

        $startDate = Carbon::parse($request->fromDate);
        $endDate = Carbon::parse($request->toDate);
        $vehicleId = $request->vehicle;
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
            $data['message'] = "The vehicle is already booked for the following periods: $bookingPeriods. You can book from $availableFrom onwards.";
            return response()->json($data);
        }

        if($request->customer != null){
            $existingBookingCustomer = AdminRentalBooking::where('customer_id', $request->customer)->whereIn('status', ['running', 'confirmed'])
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('pickup_date', [$startDate, $endDate])
                    ->orWhereBetween('return_date', [$startDate, $endDate])
                    ->orWhere(function ($query) use ($startDate, $endDate) {
                        $query->where('pickup_date', '<', $startDate)
                            ->where('return_date', '>', $endDate);
                    });
            })->exists();
            if ($existingBookingCustomer) {
                $data['message'] = "You have already booked another Vehicle for this specified time period.";
                return response()->json($data);
            }
        }

        $vehicle = Vehicle::where('vehicle_id', $vehicleId)->first();
        $typeId = $vehicle->model->category->vehicleType->type_id ?? NULL;
        $vehicleCommissionPercent = $vehicle->commission_percent ?? 0;
        $tripDurationMinutes = $endDate->diffInMinutes($startDate);
        $tripDurationHours = $tripDurationMinutes / 60;
        $customerId = $request->customer;
        $rentalBooking = new AdminRentalBooking();
        
        $rentalPrice = $vehicle->rental_price;
        $checkOffer = OfferDate::where('vehicle_id', $vehicle->vehicle_id)->get();
        if(is_countable($checkOffer) && count($checkOffer) > 0){
            $rentalPrice = getRentalPrice($rentalPrice,$vehicle->vehicle_id);
        }

        $customerGst = '';
        $user = Customer::where('customer_id', $customerId)->first();
        $customerGst = $user->gst_number ?? '';    
        $taxRate = $customerGst ? 0.18 : 0.05;
        $tripAmt = $request->tripAmt ?? null;
        $unlimitedStatus = $request->unlimitedStatus ?? 0;
        $calculationDetails = $rentalBooking->computeRentalCostDetails($rentalPrice, $tripDurationMinutes, $request->input('unlimitedKm', false), $request->couponCode, $startDate, $endDate, $typeId, false, 'new_booking', $customerId, NULL, NULL, $vehicleCommissionPercent, $taxRate, $tripAmt, $unlimitedStatus, $vehicleId);

        $vehicleTypeName = $vehicle->model->category->vehicleType->name ?? null;
        $kmLimit = calculateKmLimit($tripDurationHours, $vehicleTypeName);
        $warning = $request->input('unlimitedKm', false) ? '' : "Your journey is limited to ".(int)$kmLimit." km. Exceeding this limit will incur additional charges at ₹".$vehicle->extra_km_rate." per km.";
        $data['data'] = $calculationDetails;
        $data['warning'] = $warning;
        $data['status'] = true;
        $data['message'] = '';

        return response()->json($data);
    }

    public function getExtendPriceSummary(Request $request){
        $data['status'] = false;
        $data['message'] = '';
        $data['data'] = '';
        
        $startDate = Carbon::parse($request->extendStartDate);
        //$endDate = Carbon::parse($request->extendToDateTime);
        $fetchEndDate = date('Y-m-d H:i', strtotime($request->extendToDateTime));
        $endDate = Carbon::parse($fetchEndDate);

        $bookingId = $request->extendBookingId;
        $adjustedStartDate = $startDate->addMinutes(5)->toDateTimeString();
        $currentDateTime = Carbon::now()->setTimezone('Asia/Kolkata')->addMinutes(1);
        
        //Validation
        if($request->forceExtendStatus != 1){
            if (!$startDate || $startDate->lt($currentDateTime)) {
                $data['message'] = "Existing Return date must be at least 1 minute from now.";
                return response()->json($data);
            }
        }
        if ($endDate->lt($adjustedStartDate)) {
            $data['message'] = "New Extended date must be after the Return date..";
            return response()->json($data);
        }
         $rentalBooking = AdminRentalBooking::select('booking_id', 'status', 'return_date', 'vehicle_id', 'unlimited_kms', 'customer_id', 'tax_rate')->with('vehicle')->where('booking_id', $bookingId)->first();
        if (!$rentalBooking) {
            $data['message'] = "The Rental booking is not available.";
            return response()->json($data);
        }
        if ($rentalBooking->status != 'confirmed' && $rentalBooking->status != 'running') {
            $data['message'] = "The Rental booking is not in a valid state for extension.";
            return response()->json($data);
        }
        if ($endDate->lte($startDate)) {
            $data['message'] = "The new extended date must be greater than the existing return date.";
            return response()->json($data);
        }
        $existingBooking = RentalBooking::where('vehicle_id', $rentalBooking->vehicle_id)->whereIn('status', ['running', 'confirmed'])
            ->where('booking_id', '!=', $rentalBooking->booking_id)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('pickup_date', [$startDate, $endDate])
                    ->orWhereBetween('return_date', [$startDate, $endDate]);
            })
            ->exists();
        if ($existingBooking) {
            $data['message'] = "The vehicle is already booked for the specified time period.";
            return response()->json($data);
        }

        //Price Summary
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
            $taxRate = $customerGst ? 0.18 : 0.05;
        }
        //$tripAmt = $request->tripAmt ?? 0;
        $tripAmt = $request->tripAmt ?? null;
        $unlimitedKmStatus = $request->unlimitedKmStatus ?? 0;
        $rentalCostDetails = $rentalBooking->computeRentalCostDetails(
            $rentalPrice,
            $tripDurationMinutes,
            $rentalBooking->unlimited_kms,
            $request->couponCode,
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
            $vehicle->vehicle_id,
            $unlimitedKmStatus,
        );
        $vehicleTypeName = $vehicle->model->category->vehicleType->name ?? null;
        $kmLimit = calculateKmLimit($tripDurationMinutes / 60, $vehicleTypeName);
        $warning = $rentalBooking->unlimited_kms ? '' : "Upon extension, you will receive additional $kmLimit kilometers for your journey. Exceeding this limit will incur additional charges at ₹{$vehicle->extra_km_rate} per km.";
        $customerId = '';
        if($rentalBooking->customer_id){
            $customerId = $rentalBooking->customer_id;
        }
        $coupons = getAvailCoupons($startDate, $endDate, $customerId);
        $couponsHtml = '';
        if(is_countable($coupons) && count($coupons) > 0){
            foreach ($coupons as $key => $value) {
                $couponsHtml .= '<div class="col-md-4"><div class="card"><div class="card-body text-center">';
                $couponsHtml .= '<h5 class="card-title">'.$value->coupon_title.'</h5>';
                $couponsHtml .= '<p class="card-text">'.$value->code.'</p>';
                $couponsHtml .= '<a href="javascript:void(0);" id="coupon_'.$value->id.'" data-id="'.$value->code.'" class="btn btn-info applyCoupon">TAP TO APPLY</a>';
                $couponsHtml .= '</div></div></div>';
            }
        }

        $data['data'] = $rentalCostDetails;
        $data['warning'] = $warning;
        $data['status'] = true;
        $data['message'] = '';
        $data['couponsHtml'] = $couponsHtml;
        return response()->json($data)->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')->header('Pragma', 'no-cache')->header('Expires', '0');
    }

    public function getCompletionPriceSummary(Request $request){
        $bookingId = $request->bookingId;
        $data['html'] = '';
        $data['status'] = false;
        if($bookingId != ''){
            $rentalBooking = AdminRentalBooking::where('booking_id', $bookingId)->first();
            $finalPrice = 0; $updatedKey = '';     
            if(is_countable($rentalBooking->price_summary) && count($rentalBooking->price_summary) > 0){
                $html = '';
                $html .= '<input type="hidden" value="'.$bookingId.'" id="bId">';
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
                if($html != ''){
                    $data['html'] = $html;
                    $data['status'] = true;    
                }
            }
        }

        return response()->json($data);
    }

    public function insertBooking(Request $request){
        hasPermission('add-booking');
        $validator = Validator::make($request->all(), [
            'customer' => 'required|exists:customers,customer_id',
            'vehicle' => 'required|exists:vehicles,vehicle_id',
            'booking_start_date' => 'required|date',
            'booking_end_date' => 'required|date|after:start_date',
            'coupon_code' => ['nullable','string','exists:coupons,code',new CheckCoupon()],
            'ref_number' => 'required',
            'payment_mode' => 'required',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        
        $vehicle = Vehicle::where('vehicle_id', $request->vehicle)->first();
        $startDate = Carbon::parse($request->booking_start_date);
        $endDate = Carbon::parse($request->booking_end_date);
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
        $rentalBooking->status = 'confirmed'; // or any other default status
        $rentalBooking->rental_type = $request->rental_type ?? 'default'; // or any other default rental type
        $rentalBooking->save();

        $customerGst = '';
        $user = Customer::where('customer_id', $customerId)->first();
        $customerGst = $user->gst_number ?? '';    
        $taxRate = $customerGst ? 0.18 : 0.05;

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
        // $calculationDetails = $rentalBooking->computeRentalCostDetails($rentalPrice, $tripDurationMinutes, $request->input('unlimited_km', false), $request->coupon_code, $startDate, $endDate, $typeId, null, 'new_booking', $customerId, $request->payment_mode, $request->ref_number, $vehicleCommissionPercent, $taxRate, $tripAmt);
        $calculationDetails = $rentalBooking->computeRentalCostDetails($rentalPrice, $tripDurationMinutes, false, $request->coupon_code, $startDate, $endDate, $typeId, null, 'new_booking', $customerId, $request->payment_mode, $request->ref_number, $vehicleCommissionPercent, $taxRate, $tripAmt, 0, $vehicle->vehicle_id);

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

        // $lastSequence = AdminRentalBooking::max('sequence_no');
        // $rentalBooking->sequence_no = $lastSequence + 1;
        // $rentalBooking->save();
        
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
        $adminId = auth()->guard('admin_web')->user()->admin_id;
        logAdminActivity($activityDescription, NULL, $rentalBooking, NULL, $adminId);
        
        return redirect()->route('admin.bookings')->with('success', "Rental Booking created successfully");
    }

    public function getExtendBooking(Request $request){
        $data['status'] = false;
        $data['message'] = '';
        $data['data'] = '';

        $startDate = Carbon::parse($request->extendStartDate);
        $endDate = Carbon::parse($request->extendToDate);

        $bookingId = $request->extendBookingId;
        $adjustedStartDate = $startDate->addMinutes(5)->toDateTimeString();
        $currentDateTime = Carbon::now()->setTimezone('Asia/Kolkata')->addMinutes(1);
        
        $rentalBooking = AdminRentalBooking::select('booking_id', 'status', 'return_date', 'vehicle_id', 'unlimited_kms', 'customer_id', 'tax_rate')->with('vehicle')->where('booking_id', $bookingId)->first();
        $user = Customer::where('customer_id', $rentalBooking->customer_id)->first();
        if($user != '' && $user->is_blocked == 1){
            //return $this->errorResponse('You are blocked by admin, please contact admin for more details.');
            $data['message'] = "You are blocked by admin, please contact admin for more details.";
            return response()->json($data);
        }
        if($request->forceExtendStatus != 1){
            if (!$startDate || $startDate->lt($currentDateTime)) {
                $data['message'] = "Existing Return date must be at least 1 minute from now.";
                return response()->json($data);
            }
        }
        if ($endDate->lt($adjustedStartDate)) {
            $data['message'] = "New Extended date must be after the Return date..";
            return response()->json($data);
        }
     
        if (!$rentalBooking) {
            $data['message'] = "The Rental booking is not available.";
            return response()->json($data);
        }
        if ($rentalBooking->status != 'confirmed' && $rentalBooking->status != 'running') {
            $data['message'] = "The Rental booking is not in a valid state for extension.";
            return response()->json($data);
        }
        if ($endDate->lte($startDate)) {
            $data['message'] = "The new extended date must be greater than the existing return date.";
            return response()->json($data);
        }
        $existingBooking = AdminRentalBooking::where('vehicle_id', $rentalBooking->vehicle_id)->whereIn('status', ['running', 'confirmed'])
            ->where('booking_id', '!=', $rentalBooking->booking_id)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('pickup_date', [$startDate, $endDate])
                    ->orWhereBetween('return_date', [$startDate, $endDate]);
            })
            ->exists();
        if ($existingBooking) {
            $data['message'] = "The vehicle is already booked for the specified time period.";
            return response()->json($data);
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
            $taxRate = $customerGst ? 0.18 : 0.05;
        }
        
        $tripAmt = $request->tripAmt ?? 0;
        $rentalCostDetails = $rentalBooking->computeRentalCostDetails(
            $rentalPrice,
            $tripDurationMinutes,
            $rentalBooking->unlimited_kms,
            $request->couponCode,
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
        $bookingTransaction->coupon_code = $request->couponCode;
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
        $rentalBooking->rental_duration_minutes += $bookingTransaction->trip_duration_minutes;
        $rentalBooking->total_cost = $rentalBooking->total_cost + $bookingTransaction->trip_amount;
        $rentalBooking->save();

        //Store Admin log
        if(auth()->guard('admin_web')->check()){
            $adminUserId = auth()->guard('admin_web')->user()->admin_id;
        }else{
            $adminUserId = 0;
        }
        $activityDescription = 'Journey has been Extended for Booking Id -'.$rentalBooking->booking_id.' by Admin User';
        logAdminActivity($activityDescription, NULL, $rentalBooking, NULL, $adminUserId);

        try{
            $mobileNo = $rentalBooking->customer->mobile_number;
            //$mobileArr = config('global_values.mobile_no_array');
            //if (!in_array($mobileNo, $mobileArr)) {
            if (isset($rentalBooking->customer) && $rentalBooking->customer->is_test_user != 1) {
               $payment->payment_env = 'live';
            }else{
                $payment->payment_env = 'test';    
            }
            $payment->save();
        }catch(Exception $e){}

        $data['status'] = true;
        $data['message'] = 'Booking Extended Successfully';

        return response()->json($data);
    }

    public function checkVehicle(Request $request){
        $data['message'] = '';
        $data['status'] = true;
        $startDate = Carbon::parse($request->bookingStartDate);
        $endDate = Carbon::parse($request->bookingEndDate);
        $existingBookings = AdminRentalBooking::where('vehicle_id', $request->vehicle)
        ->whereIn('status', ['running', 'confirmed'])
        ->where(function ($query) use ($startDate, $endDate) {
            $query->whereBetween('pickup_date', [$startDate, $endDate])
                ->orWhereBetween('return_date', [$startDate, $endDate])
                ->orWhere(function ($query) use ($startDate, $endDate) {
                    $query->where('pickup_date', '<', $startDate)
                        ->where('return_date', '>', $endDate);
                });
        })
        ->get();  
        if ($existingBookings->isNotEmpty()) {
            $bookingPeriods = $existingBookings->map(function ($booking) {
                return Carbon::parse($booking->pickup_date)->format('d-m-Y H:i') . ' to ' . Carbon::parse($booking->return_date)->format('d-m-Y H:i');
            })->implode(', ');
            $latestReturnDate = $existingBookings->max('return_date');
            $availableFrom = Carbon::parse($latestReturnDate)->addMinute()->format('d-m-Y H:i');
            $data['message'] = "The vehicle is already booked for the following periods: $bookingPeriods. You can book from $availableFrom onwards.";
            $data['status'] = false;
        }

        return response()->json($data);
    }

    public function checkCustomer(Request $request){
        $startDate = Carbon::parse($request->bookingStartDate);
        $endDate = Carbon::parse($request->bookingEndDate);
        $existingBookingCustomer = AdminRentalBooking::where('customer_id', $request->customerId)->whereIn('status', ['running', 'confirmed'])
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('pickup_date', [$startDate, $endDate])
                    ->orWhereBetween('return_date', [$startDate, $endDate])
                    ->orWhere(function ($query) use ($startDate, $endDate) {
                        $query->where('pickup_date', '<', $startDate)
                            ->where('return_date', '>', $endDate);
                    });
            })->exists();
        if ($existingBookingCustomer) {
            return false;
        }else{
            return true;
        }
    }

    public function getPendingBooking(){
        hasPermission('booking-history');
        return view('admin.pending-bookings');
    }

    public function resetBooking(Request $request){
        $status = false;
        $message = 'Something went Wrong';
        $bookingId = $request->bookingId;
        $booking = AdminRentalBooking::where('booking_id', $bookingId)->first();
        if($bookingId != ''){
            if($booking->status == 'running' || $booking->status == 'completed'){
                $bookingTransaction = BookingTransaction::where(['booking_id' => $bookingId, 'type' => 'completion'])->first();
                if($bookingTransaction != ''){
                    $bookingTransaction->is_deleted = 1;
                    $bookingTransaction->save();
                }
                $booking->status = 'running';
                $booking->end_datetime = NULL;
                $booking->save();
                $status = true;
                $message = 'Booking Resetted Successfully';
            }else{
                $message = 'You can not reset this booking';
            }
        }else{
            $message = 'Booking id is invalid';
        }

        $data['status'] = $status;
        $data['message'] = $message;

        return response()->json($data);
    }

    public function bookingsAjax(Request $request){
        $pageno = $request->pageno ?? 1;
        $no_of_records_per_page = 10;
        $offset = ($pageno - 1) * $no_of_records_per_page;

        $total_rows = AdminRentalBooking::select('booking_id');
        if($request->selectedStatus && strtolower($request->selectedStatus) != 'all' && $request->selectedStatus != ''){
            $total_rows = $total_rows->where('status', $request->selectedStatus);
        }
        if($request->searchBooking != ''){
            $total_rows = $total_rows->where('booking_id', $request->searchBooking);
        }
        if($request->searchCustomer != ''){
            $total_rows = $total_rows->where('customer_id', $request->searchCustomer);
        }
        if($request->searchVehicle != ''){
            $total_rows = $total_rows->where('vehicle_id', $request->searchVehicle);
        }
        if($request->bookingPickupDateFilter != '' && $request->bookingReturnDateFilter != ''){
            $startDate = Carbon::parse($request->bookingPickupDateFilter)->format('Y-m-d H:i');
            $endDate = Carbon::parse($request->bookingReturnDateFilter)->format('Y-m-d H:i');
            $total_rows = $total_rows->where('pickup_date', '>=', $startDate)->where('return_date', '<=', $endDate);
        }elseif($request->bookingPickupDateFilter != ''){
            $startDate = Carbon::parse($request->bookingPickupDateFilter)->format('Y-m-d');
            $total_rows = $total_rows->whereDate('pickup_date', $startDate);
        }elseif($request->bookingReturnDateFilter != ''){
            $endDate = Carbon::parse($request->bookingReturnDateFilter)->format('Y-m-d');
            $total_rows = $total_rows->whereDate('return_date', $endDate);
        }
        $total_rows = $total_rows->count();
        $total_pages = ceil($total_rows / $no_of_records_per_page);

        $rentalBooking = AdminRentalBooking::with(['customer', 'vehicle', 'refund', 'vehicle.branch']);
        if($request->selectedStatus && strtolower($request->selectedStatus) != 'all' && $request->selectedStatus != ''){
            $rentalBooking = $rentalBooking->where('status', $request->selectedStatus);
        }
        if($request->searchBooking != ''){
            $rentalBooking = $rentalBooking->where('booking_id', $request->searchBooking);
        }
        if($request->searchCustomer != ''){
            $rentalBooking = $rentalBooking->where('customer_id', $request->searchCustomer);
        }
        if($request->searchVehicle != ''){
            $rentalBooking = $rentalBooking->where('vehicle_id', $request->searchVehicle);
        }

        if($request->bookingPickupDateFilter != '' && $request->bookingReturnDateFilter != ''){
            $startDate = Carbon::parse($request->bookingPickupDateFilter)->format('Y-m-d H:i');
            $endDate = Carbon::parse($request->bookingReturnDateFilter)->format('Y-m-d H:i');
            $rentalBooking = $rentalBooking->where('pickup_date', '>=', $startDate)->where('return_date', '<=', $endDate);
        }elseif($request->bookingPickupDateFilter != ''){
            $startDate = Carbon::parse($request->bookingPickupDateFilter)->format('Y-m-d');
            $rentalBooking = $rentalBooking->whereDate('pickup_date', $startDate);
        }elseif($request->bookingReturnDateFilter != ''){
            $endDate = Carbon::parse($request->bookingReturnDateFilter)->format('Y-m-d');
            $rentalBooking = $rentalBooking->whereDate('return_date', $endDate);
        }

        $rentalBooking = $rentalBooking->orderBy('created_at', 'desc')->offset($offset)->limit($no_of_records_per_page)->get();
        if(is_countable($rentalBooking) && count($rentalBooking) > 0){
            $currentDate = Carbon::now()->setTimezone('Asia/Kolkata');
            foreach ($rentalBooking as $key => $value) {
                $endJourneyOtpStatus = false;
                $startJourneyOtpStatus = false;
                $checkBooking = BookingTransaction::where(['booking_id' => $value->booking_id, 'type' => 'completion', 'is_deleted' => 0, 'paid' => 1])->exists();
                if($checkBooking) {
                    $endJourneyOtpStatus = true;
                }
                $value->endJourneyOtpStatus = $endJourneyOtpStatus;

                $pickupDate = Carbon::parse($value->pickup_date);
                $returnDate = Carbon::parse($value->return_date);
                $adjustedPickupDate = $pickupDate->copy()->subMinutes(30);
                $adjustedReturnDate = $returnDate->copy();
                if ($currentDate >= $adjustedPickupDate && $currentDate <= $adjustedReturnDate) {
                    $startJourneyOtpStatus = true;
                }
                $value->startJourneyOtpStatus = $startJourneyOtpStatus;
                $value->pDetails = $value->penalty_details ? json_decode($value->penalty_details) : '';

                $penaltyS = false;
                if(is_countable($value->adminPenalties) && count($value->adminPenalties) > 0 ){
                    $penaltyS = true;
                }
                $value->penaltyStatus = $penaltyS;

                // Check if previous booking has any penalties are remaining to paid or not
                $duePenalties = false;
                $getBooking = AdminRentalBooking::where('customer_id', $value->customer_id)->get(); 
                if(isset($getBooking) && is_countable($getBooking) && count($getBooking) > 0){
                    foreach($getBooking as $key => $val){
                        $checkOtherBookingsDuePenalties = BookingTransaction::where(['booking_id' => $val->booking_id, 'type' => 'penalty', 'paid' => 0])->exists();
                        if($checkOtherBookingsDuePenalties){
                            $duePenalties = true;
                            break;
                        }
                    }
                }
                $value->duePenalties = $duePenalties;
            }
        }

        $data = [
            'pageno' => $pageno,
            'rentalBooking' => $rentalBooking,
            'totalPages' => $total_pages,
            'from' => $offset + 1,
            'to' => min($no_of_records_per_page * $pageno, $total_rows),
            'total' => $total_rows
        ];

        return view('admin.bookings_ajax', compact('data'));

       /* if(is_countable($renalBooking) && count($renalBooking) > 0){
            foreach ($renalBooking as $key => $value) {
                if((is_countable($value->price_summary) && count($value->price_summary) > 0)){
                    $cDetails = [];
                    foreach($value->price_summary as $k => $v){
                        if($k == 0){                          
                            $amountPos = strpos($v['key'], "Amount");
                            if ($amountPos !== false) {
                                $newString = substr($v['key'], 0, $amountPos + strlen("Amount"));
                                $v['key'] = strtolower(str_replace(' ', '_', $newString));
                            }
                        }
                        else{
                            $v['key'] = strtolower(str_replace(' ', '_', $v['key']));
                        }

                        if(str_starts_with($v['key'], 'coupon')){
                            $v['key'] = 'coupon' ;
                        }

                        $cDetails[$v['key']] = $v['value'];
                        $value->cDetails = $cDetails;    

                        $setting = Setting::first();
                        $rentalPrice = $value->vehicle->rental_price;
                        if($setting != ''){
                            $rentalPrice = getRentalPrice($rentalPrice);
                        }
                        $value->updated_rental_price = $rentalPrice;
                    }
                }else{
                    $value->cDetails = '';    
                }
                $value->pDetails = $value->penalty_details ? json_decode($value->penalty_details) : '';

                $endJourneyOtpStatus = false;
                $startJourneyOtpStatus = false;
                $rentalBookingdata = DB::table('rental_bookings')->where('booking_id', $value->booking_id)->first();
                $calcDetails = BookingTransaction::where(['booking_id' => $value->booking_id])->get();
                if(is_countable($calcDetails) && count($calcDetails) > 0){
                    foreach ($calcDetails as $k => $v) {
                        if(isset($v->type) && $v->type == 'completion') {
                            $endJourneyOtpStatus = true;
                        }
                    }
                }
                $value->endJourneyStaus = $endJourneyOtpStatus;
                $currentDate = Carbon::now()->setTimezone('Asia/Kolkata');
                $pickupDate = Carbon::parse($value->pickup_date);
                $returnDate = Carbon::parse($value->return_date);
                $adjustedPickupDate = $pickupDate->copy()->subMinutes(30);
                $adjustedReturnDate = $returnDate->copy();
                if ($currentDate >= $adjustedPickupDate && $currentDate <= $adjustedReturnDate) {
                    $startJourneyOtpStatus = true;
                } 
                $value->startJourneyOtpStatus = $startJourneyOtpStatus;
            }           
        }*/
        
        /*if(is_countable($renalBooking) && count($renalBooking) > 0){
            foreach ($renalBooking as $key => $value) {
                $startJourneyOtpStatus = false;
                $endJourneyOtpStatus = false;
                $startJourneyOtpStatus = false;
                $rentalBookingdata = DB::table('rental_bookings')->where('booking_id', $value->booking_id)->first();
                $calcDetails = BookingTransaction::where(['booking_id' => $value->booking_id])->get();
                if(is_countable($calcDetails) && count($calcDetails) > 0){
                    foreach ($calcDetails as $k => $v) {
                        if(isset($v->type) && $v->type == 'completion') {
                            $endJourneyOtpStatus = true;
                        }
                    }
                }
                $value->endJourneyStaus = $endJourneyOtpStatus;
                $currentDate = Carbon::now()->setTimezone('Asia/Kolkata');
                $pickupDate = Carbon::parse($value->pickup_date);
                $returnDate = Carbon::parse($value->return_date);
                $adjustedPickupDate = $pickupDate->copy()->subMinutes(30);
                $adjustedReturnDate = $returnDate->copy();
                if ($currentDate >= $adjustedPickupDate && $currentDate <= $adjustedReturnDate) {
                    $startJourneyOtpStatus = true;
                } 
                $value->startJourneyOtpStatus = $startJourneyOtpStatus;
            }
        }
        return Datatables::of($renalBooking)    
        ->addColumn('customer_details', function ($result) {     
            $custDetails = '';
            if($result->customer->firstname != null && $result->customer->lastname != null){
                $custDetails .= ' <b>Name - </b>'.$result->customer->firstname .' '.$result->customer->lastname.'<br/>';
            }
            if($result->customer->email != null){
                $custDetails .= ' <b>Email - </b>' . $result->customer->email . '<br/>';
            }
            if($result->customer->mobile_number != null){
                $custDetails .= ' <b>Mobile No - </b>' . $result->customer->mobile_number . '<br/>';
            }
            if($result->customer->dob != null){
                $custDetails .= ' <b>Date of Birth - </b>' . $result->customer->dob . '<br/>';
            }
            if($result->customer->documents != null){
                $custDetails .= ' <b>Driving License Status - </b>' . $result->customer->documents['dl'] . '<br/>';
                $custDetails .= ' <b>GovId Status - </b>' . $result->customer->documents['govtid'];
            }
            return $custDetails;
        })
        ->addColumn('vehicle_details', function ($result) {     
            $vehicleDetails = '';
            if($result->vehicle->vehicle_name != null){
                $vehicleDetails .= ' <b>Model - </b>'.$result->vehicle->vehicle_name.'<br/>';
            }
            if($result->vehicle->vehicle_name != null){
                $vehicleDetails .= ' <b>Color - </b>'.$result->vehicle->color.'<br/>';
            }
            if($result->vehicle->vehicle_name != null){
                $vehicleDetails .= ' <b>License Plate - </b>'.$result->vehicle->license_plate.'<br/>';
            }
            return $vehicleDetails;
        })
        ->addColumn('start_otp', function ($result){
            $startOtp = '';
            if($result && $result->status && strtolower($result->status) == 'confirmed' && $result->customer->documents['dl'] == 'Approved' && $result->customer->documents['govtid'] == 'Approved'){
                if($result->startJourneyOtpStatus){
                    $startOtp .= '<a class="btn btn-success start-otp-btn" href="'.route('admin.booking-updateOtp', $result->booking_id).'" data-booking-id="'.$result->booking_id.'">Start</a><br/><span id="displayStartOtp_'.$result->booking_id.'">';
                }
            }
            return $startOtp;
        })
        ->addColumn('end_otp', function ($result){
            $endOtp = '';
            if($result && $result->status && strtolower($result->status) == 'running' || strtolower($result->status) == 'penalty_paid' && !$result->endJourneyStaus){
                    $endOtp .= '<a class="btn btn-success end-otp-btn" href="'.route('admin.booking-end-otp', $result->booking_id).'" data-booking-id="'.$result->booking_id.'">End</a><br/><span id="displayEndOtp_'.$result->booking_id.'">';
            }
            return $endOtp;
        })
        ->addColumn('action', function ($result) {     
            $actionDetails = '';
            $actionDetails .= '<a class="btn btn-secondary m-2" target="_blank" href="'.route('admin.booking-priview', $result->booking_id).'"><i class="fa fa-eye" aria-hidden="true"></i></a><br/>';
            if($result->end_datetime != null && ($result->status == 'running' || $result->status == 'completed')){
                $actionDetails .= '<a class="btn btn-danger m-2 resetBooking" href="javascript:void(0);" data-id="'.$result->booking_id.'">Reset</a>';
            }
            return $actionDetails;
        })
        ->editColumn('start_kilometers', function ($result) {  
            $startKilometers = '';
            $startKilometers .= '<a href="javascript:void(0);" class="startKmEdit" data-name="startKm" data-type="text" data-pk="'.$result->booking_id.'" title="Click to add/edit Start Km">'.$result->start_kilometers.'</a>';

            return $startKilometers;
        })
        ->editColumn('end_kilometers', function ($result) {  
            $endKilometers = '';
            $endKilometers .= '<a href="javascript:void(0);" class="endKmEdit" data-name="endKm" data-type="text" data-pk="'.$result->booking_id.'" title="Click to add/edit End Km">'.$result->end_kilometers.'</a>';

            return $endKilometers;
        })   
        ->editColumn('pickup_date', function ($result) {  
            return $result->pickup_date ? date('d-m-Y H:i:s') : '-';
        })   
        ->editColumn('return_date', function ($result) {  
            return $result->return_date ? date('d-m-Y H:i:s') : '-';
        })  
        ->editColumn('status', function ($result) {  
            return $result->status ? strtoupper($result->status) : '-';
        })   
        ->escapeColumns([])                
        ->rawColumns(['customer_details', 'vehicle_details', 'action'])
        ->make(true);*/
    }

    public function storeStartJourneyDetails(Request $request){
        $rentalBooking = RentalBooking::select('booking_id', 'return_date', 'start_otp', 'status', 'customer_id')->where('booking_id', $request->booking_id)->first();
        if ($rentalBooking == '') {
            return redirect()->back()->with('error', 'Booking is not found');
        }
        $rentalBooking->start_otp = null;
        $rentalBooking->status = 'running';
        $rentalBooking->save();
        $currentDatetime = Carbon::now()->setTimezone('Asia/Kolkata');

        // Update start or end kilometers based on image type
        if ($request->type === 'start') {
            $rentalBooking->start_kilometers = $request->start_km;
            $rentalBooking->start_datetime = $currentDatetime;
        } elseif ($request->type === 'end') {
            $rentalBooking->end_kilometers = $request->kilometers;
        }   
        $rentalBooking->save();
        $imageUrls = [];
        if($request->file('start_journey_imgs')){
            foreach ($request->file('start_journey_imgs') as $key => $image) {
                $file = $image;
                $filename = 'start_journey_'.$key.'_'.time() . '.' . $image->getClientOriginalExtension();
                $file->move(public_path('images/rental_booking_images'), $filename);
                $imageUrls[] = $filename;
            }
            if(isset($imageUrls) && is_countable($imageUrls) && count($imageUrls) > 0){
                foreach ($imageUrls as $imageUrl) {
                    $rentalBookingImage = new RentalBookingImage();
                    $rentalBookingImage->booking_id = $rentalBooking->booking_id;
                    $rentalBookingImage->image_type = $request->type;
                    $rentalBookingImage->image_url = $imageUrl;
                    $rentalBookingImage->save();
                }
            }
        }
        //Store Admin log
        if(auth()->guard('admin_web')->check()){
            $adminUserId = auth()->guard('admin_web')->user()->admin_id;
        }else{
            $adminUserId = 0;
        }
        $activityDescription = 'Journey has been Started for Booking Id -'.$rentalBooking->booking_id.' by Admin User';
        logAdminActivity($activityDescription, NULL, $rentalBooking, NULL, $adminUserId);

        return redirect()->back()->with('success', 'Journey started Successfully');
    }

     public function getPenaltyDetails(Request $request){
        $data['status'] = false;
        $data['message'] = '';
        $data['data']['admin_penalty'] = '';
        $data['data']['admin_penalty_info'] = '';
        $data['data']['exceed_km_limit'] = '';
        $data['data']['exceed_hour_limit'] = '';

        $booking = RentalBooking::select('booking_id', 'customer_id', 'end_otp', 'vehicle_id' ,'end_datetime', 'pickup_date', 'return_date', 'unlimited_kms', 'start_kilometers', 'end_kilometers', 'start_datetime', 'end_datetime', 'status', 'rental_duration_minutes')
            ->where('booking_id', $request->bookingId)
            ->first();
        if (!$booking) {
            $data['message'] = "Invalid Booking";
            return response()->json($data);
        }
        // $booking->end_kilometers = $request->endKm ?? 0;
        // $booking->end_datetime = now();
        // $booking->end_otp = null;
        // $booking->save();

        $endKilometers = $booking->end_kilometers ? $booking->end_kilometers : $request->end_km;
        $endDatetime = $booking->end_datetime ? $booking->end_datetime : now();

        $adminPenaltyAmount = 0;
        $penaltyInfo = $adminPenaltyId = '';
        $adminPenalties = AdminPenalty::where(['booking_id' => $request->bookingId, 'is_paid' => 0])->where('amount', '>', 0)->first();
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
            $vehicleTypeName = $booking->vehicle->model->category->vehicleType->name ?? null;
            $kilometerLimit = calculateKmLimit($tripDurationHours, $vehicleTypeName);
            //$kilometerDifference = $booking->end_kilometers - $booking->start_kilometers;
            $kilometerDifference = $endKilometers - $booking->start_kilometers;
            $exceededKilometerPenalty = max(0, ($kilometerDifference - $kilometerLimit) * ($booking->vehicle->extra_km_rate ?? 0));    
        }
        //$actualTripDurationMinutes = Carbon::parse($booking->end_datetime)->diffInMinutes($booking->start_datetime);
        $actualTripDurationMinutes = Carbon::parse($endDatetime)->diffInMinutes($booking->start_datetime);
        $exceededHourPenalty = max(0, (($actualTripDurationMinutes - 15) - $booking->rental_duration_minutes) * (($booking->vehicle->extra_hour_rate ?? 0) / 60)); 
        //Updated New code Start
        //$endDateTime = Carbon::parse($booking->end_datetime);
        $endDateTime = Carbon::parse($endDatetime);
        if ($endDateTime->greaterThan($returnDateTime)) {
            // If end_datetime is greater than return_date, calculate the exceeded minutes
            $exceededMinutes = $endDateTime->diffInMinutes($returnDateTime);
            $exceededHourPenalty = max(0, ($exceededMinutes * ($booking->vehicle->extra_hour_rate ?? 0) / 60));
            if($booking && $booking->unlimited_kms == 1){
                $exceededHourPenalty = ($exceededHourPenalty * 1.3);
            }
        } else {
            // If end_datetime is within the allowed time, no extra penalty for exceeded hours
            $exceededHourPenalty = 0;
        }
        //Updated New code End
    
        // Calculate final penalty and refundable amount
        //$totalPenalty = $adminPenaltyAmount + $exceededKilometerPenalty + $exceededHourPenalty;
        $data['data']['admin_penalty'] = round($adminPenaltyAmount);
        $data['data']['admin_penalty_info'] = $penaltyInfo;
        $data['data']['admin_penalty_id'] = $adminPenaltyId;
        $data['data']['exceed_km_limit'] = round($exceededKilometerPenalty);
        $data['data']['exceed_hour_limit'] = round($exceededHourPenalty);
        $data['status'] = true;
            
        return response()->json($data);
    }

    public function storeEndJourneyDetails(Request $request){
        if($request->booking_id != ''){
            $rentalBooking = RentalBooking::where('booking_id', $request->booking_id)->first();
            if($rentalBooking == ''){
                return redirect()->back()->with('error', 'Invalid booking');
            }
        }else{
            return redirect()->back()->with('error', 'Invalid booking');
        }

        $adminPenalty = $request->admin_penalty ?? 0;
        $exceedKmLimit = $request->exceed_km_limit ?? 0;
        $exceedHourLimit = $request->exceed_hours_limit ?? 0;
        $adminPenaltyInfo = $request->admin_penalty_info ?? '';
        $adminPenaltyId = $request->admin_penalty_id ?? '';
        $taxRate = $rentalBooking->tax_rate ?? 0;
        if($taxRate <= 0){
            $user = Customer::where('customer_id', $rentalBooking->customer_id)->first();
            $customerGst = $user->gst_number ?? '';    
            $taxRate = $customerGst ? 0.18 : 0.05;
        }

        $totalPenalty = $adminPenalty + $exceedKmLimit + $exceedHourLimit;
        $vehicleCommissionTaxAmt = $vehicleCommissionAmt = 0;
        if($totalPenalty > 0){
            $vehicleCommissionPercent = $rentalBooking->vehicle->commission_percent ?? 0;
            if($vehicleCommissionPercent > 0){
                $vehicleCommissionAmt = ($totalPenalty * $vehicleCommissionPercent) / 100;
                $vehicleCommissionAmt = round($vehicleCommissionAmt);
                $vehicleCommissionTaxAmt = ($vehicleCommissionAmt * 18) / 100;    
            }
        }
        
        /*$customerGst = $this->userAuthDetails->gst_number ?? '';
        $taxRate = $customerGst ? 0.18 : 0.05;*/
        $taxRate = $rentalBooking->tax_rate ?? 0;
        if($taxRate <= 0){
            $user = Customer::where('customer_id', $rentalBooking->customer_id)->first();
            $customerGst = $user->gst_number ?? '';    
            $taxRate = $customerGst ? 0.18 : 0.05;
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
        // Check if completion transaction already exists
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
            if(isset($imageUrls) && is_countable($imageUrls) && count($imageUrls) > 0){
                foreach ($imageUrls as $imageUrl) {
                    $rentalBookingImage = new RentalBookingImage();
                    $rentalBookingImage->booking_id = $rentalBooking->booking_id;
                    $rentalBookingImage->image_type = $request->type;
                    $rentalBookingImage->image_url = $imageUrl;
                    $rentalBookingImage->save();
                }
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

        //Store Admin log
        if(auth()->guard('admin_web')->check()){
            $adminUserId = auth()->guard('admin_web')->user()->admin_id;
        }else{
            $adminUserId = 0;
        }
        $activityDescription = 'Journey has been Ended for Booking Id -'.$rentalBooking->booking_id.' by Admin User';
        logAdminActivity($activityDescription, NULL, $rentalBooking, NULL, $adminUserId);

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

        //return redirect()->route('admin.bookings')->with('success', 'Journey ended Successfully');
        return redirect()->back()->with('success', 'Journey ended Successfully');
    }

    public function exportBookings(Request $request){
        $rentalBooking = AdminRentalBooking::with(['customer', 'vehicle']);
        if($request->status && strtolower($request->status) != 'all' && $request->status != ''){
            $rentalBooking = $rentalBooking->where('status', $request->status);
        }
        if($request->bookingId != ''){
            $rentalBooking = $rentalBooking->where('booking_id', $request->bookingId);
        }
        if($request->customerId != ''){
            $rentalBooking = $rentalBooking->where('customer_id', $request->customerId);
        }
        if($request->vehicleId != ''){
            $rentalBooking = $rentalBooking->where('vehicle_id', $request->vehicleId);
        }

        if($request->pickupDate != '' && $request->returnDate != ''){
            $startDate = Carbon::parse($request->pickupDate)->format('Y-m-d H:i');
            $endDate = Carbon::parse($request->returnDate)->format('Y-m-d H:i');
            $rentalBooking = $rentalBooking->where('pickup_date', '>=', $startDate)->where('return_date', '<=', $endDate);
        }elseif($request->pickupDate != ''){
            $startDate = Carbon::parse($request->pickupDate)->format('Y-m-d');
            $rentalBooking = $rentalBooking->whereDate('pickup_date', $startDate);
        }elseif($request->returnDate != ''){
            $endDate = Carbon::parse($request->returnDate)->format('Y-m-d');
            $rentalBooking = $rentalBooking->whereDate('return_date', $endDate);
        }

        $rentalBooking->orderBy('booking_id', 'asc');
        $data = $rentalBooking->get();
        
        if (strtolower($request->type) == 'csv') {
            $fileName = 'bookings_'.date('d-m-Y').'.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$fileName\"",
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
            ];
            return response()->stream(function () use ($data) {
                $handle = fopen('php://output', 'w');
                // Add CSV headers
                fputcsv($handle, [
                    'Booking Id',
                    'Customer Details',
                    'Vehicle Details',
                    'Pickup Date',
                    'Return Date',
                    'Start Kilometers',
                    'End Kilometers',
                    'Rental Type',
                    'Status'
                ]);
                foreach ($data as $v) {
                    $customerDetails = $vehicleDetails = '';
                    // Fetch customer details
                    if (!empty($v->customer)) {
                        $customerDetails = 'Name: ' . ($v->customer->firstname ?? '') . ' ' . ($v->customer->lastname ?? '');
                        $customerDetails .= ' Email: ' . ($v->customer->email ?? '');
                        $customerDetails .= ' Mobile: ' . ($v->customer->mobile_number ?? '');
                    }
                    if (!empty($v->vehicle)) {
                        $vehicleDetails = 'Model: ' . ($v->vehicle->vehicle_name ?? '') . ', Color: ' . ($v->vehicle->color ?? '');
                    }
                    $data = [
                        $v->booking_id ?? '',
                        $customerDetails ?? '',
                        $vehicleDetails ?? '',
                        date('d-m-Y H:i', strtotime($v->pickup_date)),
                        date('d-m-Y H:i', strtotime($v->return_date)),
                        $v->start_kilometers ?? 0,
                        $v->end_kilometers ?? 0,
                        $v->rental_type ?? '',
                        strtoupper($v->status) ?? '',
                    ];
                    fputcsv($handle, $data);
                    flush(); // Ensure immediate output
                }
                fclose($handle);
            }, 200, $headers);
        } else {
            $fileName = 'bookings_'.date('d-m-Y').'.pdf';
            //$pdf = new TCPDF();
            $pdf = new TCPDF('L', 'mm', 'A3', true, 'UTF-8', false); // 'L' for Landscape
            $pdf->SetHeaderData('', 0, 'Bookings Report');
            $pdf->AddPage('P', 'A3');
            $html = view('admin.booking_pdf', compact('data'))->render();
            $pdf->writeHTML($html, true, false, true, false, '');
            $pdf->Output($fileName, 'D');

            /*$pdf = PDF::loadView('admin.booking_pdf', compact('data'));
            $pdf->setPaper('A3', 'Portrait');  
            return $pdf->download($fileName);*/
        }
    }

    public function getBookingTransaction(Request $request){
        hasPermission('booking-transaction-history');

        $bookingIds = AdminRentalBooking::select('booking_id')->pluck('booking_id')->toArray();
        $customerArr = Customer::select('customer_id', 'firstname', 'lastname', 'mobile_number', 'email')->get();
        $vehicleArr = Vehicle::select('vehicle_id', 'model_id', 'license_plate', 'is_deleted')->get();

        return view('admin.booking-transactions', compact('bookingIds', 'customerArr', 'vehicleArr'));
    }

    public function bookingTransactionAjax(Request $request){
        $pageno = $request->pageno ?? 1;
        $no_of_records_per_page = 10;
        $offset = ($pageno - 1) * $no_of_records_per_page;

        $total_rows = BookingTransaction::select('booking_id')->where('is_deleted', 0);
        if($request->selectedStatus && strtolower($request->selectedStatus) != 'all' && $request->selectedStatus != ''){
            $total_rows = $total_rows->whereHas('rentalBooking', function ($que) use($request) {
                $que->where('status', $request->selectedStatus);
            });
        }
        if($request->searchBooking != ''){
            $total_rows = $total_rows->where('booking_id', $request->searchBooking);
        }
        if($request->searchCustomer != ''){
            $total_rows = $total_rows->whereHas('rentalBooking', function ($que) use($request) {
                $que->where('customer_id', $request->searchCustomer);
            });
        }
        if($request->searchVehicle != ''){
            $total_rows = $total_rows->whereHas('rentalBooking', function ($que) use($request) {
                $que->where('vehicle_id', $request->searchVehicle);
            });
        }        
        if($request->taxPercent != '' && $request->taxPercent > 0){
            if($request->taxPercent == 5){
                $total_rows = $total_rows->whereHas('rentalBooking.customer', function ($que) use($request) {
                    $que->whereNull('gst_number');
                });
            }elseif($request->taxPercent = 18){
                $total_rows = $total_rows->whereHas('rentalBooking.customer', function ($que) use($request) {
                    $que->where('gst_number', '!=', null);
                });
            }
        }
        if($request->pickupDateFilter != '' && $request->returnDateFilter != ''){
            $startDate = Carbon::parse($request->pickupDateFilter)->format('Y-m-d H:i');
            $endDate = Carbon::parse($request->returnDateFilter)->format('Y-m-d H:i');
            $total_rows = $total_rows->where('start_date', '>=', $startDate)->where('end_date', '<=', $endDate);
        }elseif($request->pickupDateFilter != '' ){
            $startDate = Carbon::parse($request->pickupDateFilter)->format('Y-m-d');
            $total_rows = $total_rows->whereDate('start_date', $startDate);
        }elseif($request->returnDateFilter != ''){
            $endDate = Carbon::parse($request->returnDateFilter)->format('Y-m-d');
            $total_rows = $total_rows->whereDate('end_date', $endDate);
        }elseif (isset($request->paidStatus)) {
            $total_rows = $total_rows->where('paid', $request->paidStatus);
        }

        $total_rows = $total_rows->count();
        $total_pages = ceil($total_rows / $no_of_records_per_page);
        $rentalBookingTransactions = BookingTransaction::select('id', 'booking_id', 'type', 'start_date', 'end_date', 'rental_price', 'trip_duration_minutes', 'trip_amount', 'tax_amt', 'coupon_discount', 'coupon_code', 'trip_amount_to_pay', 'convenience_fee', 'total_amount', 'refundable_deposit', 'final_amount', 'late_return', 'exceeded_km_limit', 'additional_charges', 'amount_to_pay', 'refundable_deposit_used', 'from_refundable_deposit', 'paid', 'timestamp', 'vehicle_commission_amount', 'vehicle_commission_tax_amt')->with(['rentalBooking', 'rentalBooking.customer', 'rentalBooking.vehicle'])->where('is_deleted', 0);
        if($request->selectedStatus && strtolower($request->selectedStatus) != 'all' && $request->selectedStatus != ''){
            $rentalBookingTransactions = $rentalBookingTransactions->whereHas('rentalBooking', function ($que) use($request) {
                $que->where('status', $request->selectedStatus);
            });
        }
        if($request->searchBooking != ''){
            $rentalBookingTransactions = $rentalBookingTransactions->where('booking_id', $request->searchBooking);
        }
        if($request->searchCustomer != ''){
            $rentalBookingTransactions = $rentalBookingTransactions->whereHas('rentalBooking', function ($que) use($request) {
                $que->where('customer_id', $request->searchCustomer);
            });
        }
        if($request->searchVehicle != ''){
            $rentalBookingTransactions = $rentalBookingTransactions->whereHas('rentalBooking', function ($que) use($request) {
                $que->where('vehicle_id', $request->searchVehicle);
            });
        }
        if($request->taxPercent != '' && $request->taxPercent > 0){
            if($request->taxPercent == 5){
                $rentalBookingTransactions = $rentalBookingTransactions->whereHas('rentalBooking.customer', function ($que) {
                    $que->whereNull('gst_number');
                });
            }elseif($request->taxPercent = 18){
                $rentalBookingTransactions = $rentalBookingTransactions->whereHas('rentalBooking.customer', function ($que) {
                    $que->where('gst_number', '!=', null);
                });
            }
        }
        if($request->pickupDateFilter != '' && $request->returnDateFilter != ''){
            $startDate = Carbon::parse($request->pickupDateFilter)->format('Y-m-d H:i');
            $endDate = Carbon::parse($request->returnDateFilter)->format('Y-m-d H:i');
            $rentalBookingTransactions = $rentalBookingTransactions->where('start_date', '>=', $startDate)->where('end_date', '<=', $endDate);
        }elseif($request->pickupDateFilter != '' ){
            $startDate = Carbon::parse($request->pickupDateFilter)->format('Y-m-d');
            $rentalBookingTransactions = $rentalBookingTransactions->whereDate('start_date', $startDate);
        }elseif($request->returnDateFilter != ''){
            $endDate = Carbon::parse($request->returnDateFilter)->format('Y-m-d');
            $rentalBookingTransactions = $rentalBookingTransactions->whereDate('end_date', $endDate);
        }elseif (isset($request->paidStatus)) {
            $rentalBookingTransactions = $rentalBookingTransactions->where('paid', $request->paidStatus);
        }   

        $rentalBookingTransactions = $rentalBookingTransactions->orderBy('created_at', 'desc')->offset($offset)->limit($no_of_records_per_page)->get();
        if(is_countable($rentalBookingTransactions) && count($rentalBookingTransactions) > 0){
            foreach ($rentalBookingTransactions as $key => $value) {
                $finalAmt = 0;
                //$taxableAmt = getTransactionTaxable($value->booking_id, $value->type);
                $taxableAmt = getTransactionTaxable($value->id);
                $convenienceFees = getTransactionConvenienceFees($value->booking_id, $value->type);
                $value->convenienceFees = $convenienceFees;
                $value->taxAmt = ($value->tax_amt - $value->vehicle_commission_tax_amt);
                $finalAmt = $taxableAmt + $convenienceFees + $value->tax_amt;
                $value->finalAmt = $finalAmt;
                if($taxableAmt > 0){
                    $taxableAmt = ($taxableAmt - $value->vehicle_commission_amount);    
                }
                $value->taxableAmt = $taxableAmt;
            }
        }

        $data = [
            'pageno' => $pageno,
            'rentalBookingTransactions' => $rentalBookingTransactions,
            'totalPages' => $total_pages,
            'from' => $offset + 1,
            'to' => min($no_of_records_per_page * $pageno, $total_rows),
            'total' => $total_rows
        ];

        return view('admin.booking_transactions_ajax', compact('data'));
    }

    public function exportBookingTransaction(Request $request){
        $rentalBookingTransactions = BookingTransaction::select('id', 'booking_id', 'type', 'start_date', 'end_date', 'rental_price', 'trip_duration_minutes', 'trip_amount', 'tax_amt', 'coupon_discount', 'coupon_code', 'trip_amount_to_pay', 'convenience_fee', 'total_amount', 'refundable_deposit', 'final_amount', 'late_return', 'exceeded_km_limit', 'additional_charges', 'amount_to_pay', 'refundable_deposit_used', 'from_refundable_deposit', 'paid', 'timestamp')->with(['rentalBooking', 'rentalBooking.customer', 'rentalBooking.vehicle'])->where('is_deleted', 0);

        if($request->searchTranBooking != ''){
            $rentalBookingTransactions = $rentalBookingTransactions->where('booking_id', $request->searchTranBooking);
        }
        if($request->searchTranCustomer != ''){
            $rentalBookingTransactions = $rentalBookingTransactions->whereHas('rentalBooking', function ($que) use($request) {
                $que->where('customer_id', $request->searchTranCustomer);
            });
        }
        if($request->searchTranVehicle != ''){
            $rentalBookingTransactions = $rentalBookingTransactions->whereHas('rentalBooking', function ($que) use($request) {
                $que->where('vehicle_id', $request->searchTranVehicle);
            });
        }
        if($request->taxPercent != '' && $request->taxPercent > 0){
            if($request->taxPercent == 5){
                $rentalBookingTransactions = $rentalBookingTransactions->whereHas('rentalBooking.customer', function ($que) {
                    $que->whereNull('gst_number');
                });
            }elseif($request->taxPercent = 18){
                $rentalBookingTransactions = $rentalBookingTransactions->whereHas('rentalBooking.customer', function ($que) {
                    $que->where('gst_number', '!=', null);
                });
            }
        }
        if($request->pickupDate != '' && $request->returnDate != ''){
            $startDate = Carbon::parse($request->pickupDate)->format('Y-m-d H:i');
            $endDate = Carbon::parse($request->returnDate)->format('Y-m-d H:i');
            $rentalBookingTransactions = $rentalBookingTransactions->where('start_date', '>=', $startDate)->where('end_date', '<=', $endDate);
        }elseif($request->pickupDate != '' ){
            $startDate = Carbon::parse($request->pickupDate)->format('Y-m-d');
            $rentalBookingTransactions = $rentalBookingTransactions->whereDate('start_date', $startDate);
        }elseif($request->returnDate != ''){
            $endDate = Carbon::parse($request->returnDate)->format('Y-m-d');
            $rentalBookingTransactions = $rentalBookingTransactions->whereDate('end_date', $endDate);
        }elseif(isset($request->paidStatus)){
            $rentalBookingTransactions = $rentalBookingTransactions->where('paid', $request->paidStatus);
        }

        $data = $rentalBookingTransactions->orderBy('created_at', 'desc')->get();
        if(is_countable($data) && count($data) > 0){
            foreach ($data as $key => $value) {
                $finalAmt = 0;
                //$taxableAmt = getTransactionTaxable($value->booking_id, $value->type);
                $taxableAmt = getTransactionTaxable($value->id);
                $value->taxableAmt = $taxableAmt;
                $convenienceFees = getTransactionConvenienceFees($value->booking_id, $value->type);
                $value->convenienceFees = $convenienceFees;
                $finalAmt = $taxableAmt + $convenienceFees + $value->tax_amt;
                $value->finalAmt = $finalAmt;
            }
        }
        
        if (strtolower($request->type) == 'csv') {
            $fileName = 'booking_transaction_'.date('d-m-Y').'.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$fileName\"",
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
            ];
            return response()->stream(function () use ($data) {
                $handle = fopen('php://output', 'w');
                // Add CSV headers
                fputcsv($handle, [
                    'Booking Id',
                    'Invoice Id',
                    'Transaction Id',
                    'Customer Details',
                    'Vehicle Details',
                    'Pickup Date',
                    'Return Date',
                    'Taxable Amount(In ₹)',
                    'Tax Details',
                    'Convineince Amount(In ₹)',
                    'Final Amount(In ₹)',
                    'Type',
                    'Paid Status',
                    'Creation Date',
                ]);
                foreach ($data as $v) {
                    $customerDetails = $vehicleDetails = $taxDetails = '';
                    if (!empty($v->rentalBooking) && !empty($v->rentalBooking->customer)) {
                        $customerDetails = 'Name: ' . (($v->rentalBooking->customer->firstname ?? '') . ' ' . ($v->rentalBooking->customer->lastname ?? ''));
                        $customerDetails .= ' Email: ' . ($v->rentalBooking->customer->email ?? '');
                        $customerDetails .= ' Mobile: ' . ($v->rentalBooking->customer->mobile_number ?? '');
                        $customerDetails .= ' Date of Birth: ' . ($v->rentalBooking->customer->dob ?? '');
                        $customerDetails .= ' Driving License Status: ' . ($v->rentalBooking->customer->documents['dl'] ?? '');
                        $customerDetails .= ' GovId Status: ' . ($v->rentalBooking->customer->documents['govtid'] ?? '');
                    }
                    if (!empty($v->rentalBooking) && !empty($v->rentalBooking->vehicle)) {
                        $vehicleDetails = 'Model: ' . ($v->rentalBooking->vehicle->vehicle_name ?? '') . ' Color: ' . ($v->rentalBooking->vehicle->color ?? '');
                        $vehicleDetails .= ' License Plate: ' . ($v->rentalBooking->vehicle->license_plate ?? '');
                    }
                    $taxPercent = 5;
                    if($v->rentalBooking->customer->gst_number != null){
                        $taxPercent = 18;
                    }
                    $taxDetails .= ' Amount - '.($v->tax_amt).'|';
                    $taxDetails .= ' Percent - '.$taxPercent.' %';

                    $status = '';
                    if($v->paid == 1)
                       $status = 'PAID';
                    else
                        $status = 'NOT PAID';
                    $taxableAmt = $v->taxableAmt;
                    $convenienceFees = $v->convenienceFees;
                    $finalAmt = $v->finalAmt;
                    $data = [
                        $v->booking_id,
                        $v->rentalBooking->sequence_no ?? '-',
                        $v->id,
                        $customerDetails,
                        $vehicleDetails,
                        $v->start_date ? date('d-m-Y H:i', strtotime($v->start_date)) : '-',
                        $v->end_date ? date('d-m-Y H:i', strtotime($v->end_date)) : '-',
                        $taxableAmt,
                        $taxDetails ?? '',
                        $convenienceFees,
                        $finalAmt,
                        strtoupper($v->type),
                        $status,
                        date('d-m-Y H:i', strtotime($v->timestamp)),
                    ];
                    fputcsv($handle, $data);
                    flush(); // Ensure immediate output
                }
                fclose($handle);
            }, 200, $headers);
        } else {
            $fileName = 'booking_transactions_'.date('d-m-Y').'.pdf';
            $chunkedData = $data->chunk(100); // Adjust the chunk size
            $pdf = new TCPDF('L', 'mm', 'A3', true, 'UTF-8', false);
            foreach ($chunkedData as $chunk) {
                $html = view('admin.booking_transactions_pdf', compact('chunk'))->render();
                $pdf->AddPage('P', 'A3');
                $pdf->writeHTML($html, true, false, true, false, '');
            }
            $pdf->Output($fileName, 'D');
            /*$pdf = new TCPDF('L', 'mm', 'A3', true, 'UTF-8', false); // 'L' for Landscape
            $pdf->SetHeaderData('', 0, 'Booking Transactions Report');
            $pdf->AddPage('P', 'A3');
            $html = view('admin.booking_transactions_pdf', compact('data'))->render();
            $pdf->writeHTML($html, true, false, true, false, '');
            $pdf->Output($fileName, 'D');*/
        }
    }

    public function getCustomerBookings(Request $request, $customerId){
        $rentalBooking = $customerInfo = '';
        if($customerId != ''){
            $rentalBooking = RentalBooking::where('customer_id', $customerId)->paginate(7);   
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
        }
       
        return view('admin.customer_bookings', compact('rentalBooking', 'customerInfo'));
    }

    public function getVehicleBookings(Request $request, $vehicleId){
        $rentalBooking = $vehicleInfo = '';
        if($vehicleId != ''){
            $rentalBooking = RentalBooking::where('vehicle_id', $vehicleId)->paginate(7);   
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
        }

        return view('admin.vehicle_bookings', compact('rentalBooking', 'vehicleInfo'));
    }

    public function getRemainingBookingPenalties(Request $request){
        $bookingTransaction = BookingTransaction::where('amount_to_pay', '>', 0)->where('is_deleted', 0)->where('paid', 0)->where(function($query) {
                                $query->where('late_return', '>', 0)
                                      ->orWhere('exceeded_km_limit', '>', 0)
                                      ->orWhere('additional_charges', '>', 0);
                            })->with(['rentalBooking', 'rentalBooking.customer', 'rentalBooking.vehicle'])->paginate(7);

        return view('admin.remaining_booking_penalties', compact('bookingTransaction'));
    }

    public function getCompletionPenalties(Request $request){
        $data['status'] = false;
        $data['message'] = '';
        $data['data']['admin_penalty'] = '';
        $data['data']['admin_penalty_info'] = '';
        $data['data']['exceed_km_limit'] = '';
        $data['data']['exceed_hour_limit'] = '';

        $transaction = BookingTransaction::select('booking_id', 'id', 'late_return', 'exceeded_km_limit' ,'additional_charges', 'additional_charges_info', 'amount_to_pay', 'tax_amt')->where('id', $request->bookingTransactionId)->first();
        if (!$transaction) {
            $data['message'] = "Invalid Transaction";
            return response()->json($data);
        }
    
        $data['data']['admin_penalty'] = round($transaction->additional_charges);
        $data['data']['admin_penalty_info'] = $transaction->additional_charges_info;
        $data['data']['exceed_km_limit'] = round($transaction->exceeded_km_limit);
        $data['data']['exceed_hour_limit'] = round($transaction->late_return);
        $data['status'] = true;
            
        return response()->json($data);
    }

    public function storeCompletionPenalties(Request $request){
        $adminPenalty = $request->admin_penalty ?? 0; 
        $adminPenaltyInfo = $request->admin_penalty_info ?? '';
        $lateReturnPenalty = $request->exceed_km_limit ?? 0;
        $kmExceedPenalty = $request->exceed_hours_limit ?? 0;
        $status = false;
        $bookingTransaction = BookingTransaction::where('id', $request->transaction_id)->first();
        $oldVal = $newVal = '';
        if($bookingTransaction != ''){
            $oldVal = clone $bookingTransaction;
            $bookingTransaction->late_return = $lateReturnPenalty;
            $bookingTransaction->exceeded_km_limit = $kmExceedPenalty;
            $bookingTransaction->additional_charges = $adminPenalty;
            $bookingTransaction->additional_charges_info = $adminPenaltyInfo;
            $bookingTransaction->save();

            $totalPenalty = $adminPenalty + $kmExceedPenalty + $lateReturnPenalty;
            $customerGst = $bookingTransaction->rentalBooking->customer->gst_number ?? '';
            $taxRate = $customerGst ? 0.18 : 0.05;
            $taxAmt = $totalPenalty * $taxRate;
            $totalPenalty = $totalPenalty + $taxAmt;

            $initialTransaction = BookingTransaction::where('booking_id', $bookingTransaction->booking_id)
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
            $bookingTransaction->tax_amt = $taxAmt;
            $bookingTransaction->amount_to_pay = $amount_to_pay;
            $bookingTransaction->refundable_deposit_used = $refundable_deposit_used;
            $bookingTransaction->refundable_deposit = $payNow ? 0 : $remainingRefundableAmount;
            $bookingTransaction->from_refundable_deposit = !$payNow;
            $bookingTransaction->save();
            $status = true;

            $newVal = $bookingTransaction;
            $description = 'Penalties are changed for booking ID: '.$bookingTransaction->booking_id.' and Transaction ID: '.$bookingTransaction->id;
            logAdminActivity($description, $oldVal, $newVal);
            return redirect()->back()->with('success', 'Penalty updated Successfully');
        }
        return redirect()->back()->with('error', 'Something went Wrong');
    }

    public function undoCancelled(Request $request){
        $bookingId = $request->bookingId ?? '';
        $status = false;
        if($bookingId != ''){
            $booking = RentalBooking::where('booking_id', $bookingId)->first();
            $cancelRentalBooking = CancelRentalBooking::where('booking_id', $bookingId)->first();
            if($booking != '' && $cancelRentalBooking != ''){
                $booking->status = 'confirmed';
                $booking->save();
                $cancelRentalBooking->is_deleted = 1;
                $cancelRentalBooking->save();
                $status = true;
            }
        }

        return response()->json($status);
    }

    public function getCancelDetails(Request $request){
        $bookingId = $request->bookingId ?? '';
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

        $data['status'] = 1;
        $data['message'] = $cancelRentalBookingMessage; 

        return response()->json($data);
    }

    public function cancelBooking(Request $request){
        $bookingId = $request->bookingId;
        $refundPercent = $refundAmount = $diffInHours = 0;
        $responseDetils = getCancelDetails($bookingId);
        if(is_countable($responseDetils) && count($responseDetils) > 0){
            $refundPercent = $responseDetils['refundPercent'] ?? 0;
            $refundAmount = $responseDetils['refundAmount'] ?? 0;
            $diffInHours = $responseDetils['diffInHours'] ?? 0;
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

        return response()->json("Booking Cancel Successfully");
    }

}