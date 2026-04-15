<?php

namespace App\Http\Controllers\AdminApis;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Services\SmsService;
use App\Models\{AdminUser, Vehicle, Customer, CustomerDocument, RentalBooking, BookingTransaction, OfferDate, CompanyDetail, CarEligibility };
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Cache;

class LoginController extends Controller
{
    protected $smsService;
    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function adminLogin(Request $request){
        $validator = Validator::make($request->all(), [
            'username' => 'required|max:50|exists:admin_users,username', 
            'password' => 'required|min:6',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $adminUser = AdminUser::where('username', $request->username)->first();
        if (!$adminUser || !Hash::check($request->password, $adminUser->password)) {
            return $this->errorResponse('Username OR Password is incorrect');
        }
        $token = $adminUser->createToken('Admin'.$request->username)->plainTextToken;
        $adminUser->token = $token;
        
        return $this->successResponse($adminUser, 'Login successful');
    }

    public function bookingInvoiceData(Request $request, $bookingId)
    {
        $extraKmString = '';
        $data = RentalBooking::with(['vehicle.model.manufacturer', 'vehicle.model.category', 'vehicle.properties', 'vehicle.features', 'vehicle.images', 'customer'])->where('booking_id', $bookingId)->first();
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
                }elseif($value->type == 'extension' && $value->paid == 1){
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
                        $penaltyText .=  $extraKmString;
                    }
                    if(isset($value->additional_charges) && $value->additional_charges != '' && $value->additional_charges != 0){
                        $additionalCharges += $value->additional_charges;
                        if($value->exceeded_km_limit != 0){
                             $penaltyText .=  ' | ';    
                        }
                        $penaltyText .=  'Additional Charges - '. $value->additional_charges;
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
                    $taxPercent = ($gstPercent == 0.05) ? 5 : (($gstPercent == 0.12) ? 12 : 0);
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

    public function bookingSummaryData($bookingId, $customerId)
    {
        $data = RentalBooking::with(['vehicle.model.manufacturer', 'vehicle.model.category', 'vehicle.properties', 'vehicle.features', 'vehicle.images'])->where('booking_id', $bookingId)->first();
        $customerDoc = CustomerDocument::select('document_id', 'customer_id', 'document_type', 'is_approved', 'id_number')->where('customer_id', $customerId)->get();
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

    public function customerAggrement($bookingId){
        $vehicleRegistrationNo = '-';
        $bookingStartDate = '';
        $booking = RentalBooking::select('booking_id', 'vehicle_id', 'start_datetime', 'pickup_date', 'customer_id')->where('booking_id', $bookingId)->first();
        $customer = Customer::where('customer_id', $booking->customer_id)->first();
        $name = '';
        $ownerName = 'Shailesh Car & Bikes Pvt. Ltd.';
        if($customer){
            $name .= $customer->firstname ?? '';
            $name .= ' '.$customer->lastname ?? '';
        }
        if($booking){
            $vehicle = Vehicle::where('vehicle_id', $booking->vehicle_id)->first();
            $vehicleRegistrationNo = $vehicle->license_plate ?? '-';
            $carEligibility = CarEligibility::with('carHost')->where('vehicle_id', $booking->vehicle_id)->first();
            if ($carEligibility && $carEligibility->carHost) {
                $ownerName .= $carEligibility->carHost->firstname ?? '';
                $ownerName .= ' '.$carEligibility->carHost->lastname ?? '';
            }
            $bookingStartDate = $booking->start_datetime ? date('d-m-Y H:i', strtotime($booking->start_datetime)) : date('d-m-Y H:i', strtotime($booking->pickup_date));
        }
        $fileName = 'customer_agreements_'.$booking->customer_id.'_'.$bookingId.'.pdf';
        $path = public_path().'/customer_aggrements/';

        $pdf = PDF::loadView('customer_aggrement', compact('name', 'bookingId', 'ownerName', 'bookingStartDate', 'vehicleRegistrationNo'));
        return $pdf->stream($fileName);
    }

    public function forgotPassword(Request $request){
        $validator = Validator::make($request->all(), [		
			'mobile_number' => ['required','numeric','regex:/^\+?[1-9]\d{9,14}$/','digits_between:8,15', 'exists:admin_users,mobile_number'],
		]);		
		if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
		}

        $user = AdminUser::select('admin_id', 'mobile_number')->where('mobile_number', '=', $request->mobile_number)->first();
		if (!isset($user) && $user == '') {
			return $this->errorResponse('Admin User not Found');
		}
        $to = $user->mobile_number ?? '';
        if(isset($to) && $to != ''){
           try{
                $otp = $this->generateAndSendOTP($to);
                if ($otp === null) { 
                    return $this->errorResponse('OTP already sent within 1 Minute.');
                }
                if ($otp && isset($otp['status']) && $otp['status'] !== 200) {
                    $errorMessage = $otp['message'] ?? 'Something went Wrong';
                    return $this->errorResponse($errorMessage);
                } else{
                    return $this->successResponse(['otp' => $otp], 'OTP sent for Verification.');    
                }
            } catch (\Exception $e) {} 
        }

        return $this->successResponse($user, 'Email Reset Link Send Successfully');
    }

    private function generateAndSendOTP($mobileNumber)
    {
        // Retrieve the last OTP sent time from the cache
        $lastOTPSentTime = Cache::get('last_otp_sent_' . $mobileNumber);

        // Check if OTP was sent in the last 30 seconds
        if ($lastOTPSentTime && now()->diffInSeconds($lastOTPSentTime) < 30) {
            return;
        }

        // Generate OTP
        $otp = strval(mt_rand(1000, 9999));
        $checkresponse =  $this->smsService->sendOTP($mobileNumber,$otp);
        // Check the response status and handle errors
        if($checkresponse && isset($checkresponse['status']) && $checkresponse['status'] != 200){
            $checkResponse['message'] = $checkResponse['message'] ?? 'An error occurred while sending OTP.';
            return $checkresponse; 
        }
        
        // Cache the OTP and timestamp
        Cache::put('otp_' . $mobileNumber, strval($otp), 60 * 5);
        // Store the timestamp of the OTP sent
        Cache::put('last_otp_sent_' . $mobileNumber, now(), 30);

        return $otp; 
    }

    public function forgotPasswordVerifyOtp(Request $request){
        $validator = Validator::make($request->all(), [
            'mobile_number' => ['required','numeric','regex:/^\+?[1-9]\d{9,14}$/','digits_between:8,15', 'exists:admin_users,mobile_number'],
            'otp' => 'required|string',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $otp = Cache::get('otp_' . $request->mobile_number);
        if (!$otp || $otp !== $request->otp) {
            return $this->errorResponse('Invalid OTP');
        }
        $user = AdminUser::where('mobile_number', $request->mobile_number)->latest()->first();
        if (!$user) {
            return $this->errorResponse('Admin User not found');
        }

        return $this->successResponse($user, 'OTP verified Successfully');
    }

    public function resetPassword(Request $request){
        $validator = Validator::make($request->all(), [
            //'admin_id' => 'required',
            'password' => 'required',
            'mobile_number' => ['required','numeric','regex:/^\+?[1-9]\d{9,14}$/','digits_between:8,15', 'exists:admin_users,mobile_number'],
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $user = AdminUser::where(['mobile_number' => $request->mobile_number])->first();
        if($user != ''){
            $user->password = Hash::make($request->password);
			$user->save();	
            return $this->successResponse($user, 'Password Reset Successfully');
        }else{
            return $this->errorResponse('User not Found');
        }
    }
    
}
