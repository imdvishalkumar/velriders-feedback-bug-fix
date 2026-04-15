<?php

namespace App\Http\Controllers\AdminApis\V1;

use App\Http\Controllers\Controller; 
use Illuminate\Http\Request;
use App\Models\{Vehicle, TripAmountCalculationRule, Customer, AdminRentalBooking, BookingTransaction, Payment, Refund, CancelRentalBooking, CustomerReferralDetails};
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Http;

class AdminBookingDetailsController extends Controller
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

    public function getBookingTransactionsDetails(Request $request){
        $taxPercent = config('global_values.tax_percent');
        $paidStatus = config('global_values.paid_status');
        $bookingStatuses = config('global_values.booking_statuses');
        $paymentModes = config('global_values.payment_modes');
        $details = [
            'taxPercent' => $taxPercent,
            'paidStatus' => $paidStatus,
            'bookingStatuses' => $bookingStatuses,
            'paymentModes' => $paymentModes
        ];

        return $this->successResponse($details, 'Booking Transactions Details fetched successfully');
    }

    public function getBookingTransactions(Request $request){
        $orderColumn = $request->order_column ?? '';
        $orderType = $request->order_type ?? '';
        $search = $request->search ?? '';
        $orderTypes = config('global_values.order_types');
        $orderTypes = implode(',', $orderTypes);
        $validator = Validator::make($request->all(), [
            'booking_id' => 'nullable|exists:rental_bookings,booking_id',
            'customer_id' => 'nullable|exists:customers,customer_id',
            'vehicle_id' => 'nullable|exists:vehicles,vehicle_id',
            'tax_percent' => 'nullable|numeric|in:5,12',
            'paid_status' => 'nullable|in:0,1',
            'pickup_date' => 'nullable',
            'return_date' => 'nullable|after_or_equal:pickup_date',
            'order_type' => 'nullable|in:'.$orderTypes,
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $page = $request->page;
        $pageSize = $request->page_size;
        $offset = ($page - 1) * $pageSize;

        $rentalBookingTransactions = BookingTransaction::select('id', 'booking_id', 'type', 'start_date', 'end_date', 'rental_price', 'trip_duration_minutes', 'trip_amount', 'tax_amt', 'coupon_discount', 'coupon_code', 'trip_amount_to_pay', 'convenience_fee', 'total_amount', 'refundable_deposit', 'final_amount', 'late_return', 'exceeded_km_limit', 'additional_charges', 'amount_to_pay', 'refundable_deposit_used', 'from_refundable_deposit', 'paid', 'timestamp', 'vehicle_commission_amount', 'vehicle_commission_tax_amt', 'razorpay_order_id', 'cashfree_order_id')->with(['rentalBooking', 'rentalBooking.customer', 'rentalBooking.vehicle'])->where('is_deleted', 0);

        if($request->booking_id != ''){
            $rentalBookingTransactions = $rentalBookingTransactions->where('booking_id', $request->booking_id);
        }
        if($request->customer_id != ''){
            $rentalBookingTransactions = $rentalBookingTransactions->whereHas('rentalBooking', function ($que) use($request) {
                $que->where('customer_id', $request->customer_id);
            });
        }
        if($request->vehicle_id != ''){
            $rentalBookingTransactions = $rentalBookingTransactions->whereHas('rentalBooking', function ($que) use($request) {
                $que->where('vehicle_id', $request->vehicle_id);
            });
        }
        if($request->tax_percent != '' && $request->tax_percent > 0){
            if($request->tax_percent == 5){
                $rentalBookingTransactions = $rentalBookingTransactions->whereHas('rentalBooking.customer', function ($que) {
                    $que->whereNull('gst_number');
                });
            }elseif($request->tax_percent = 12){
                $rentalBookingTransactions = $rentalBookingTransactions->whereHas('rentalBooking.customer', function ($que) {
                    $que->where('gst_number', '!=', null);
                });
            }
        }
        if($request->pickup_date != '' && $request->return_date != ''){
            $startDate = Carbon::parse($request->pickup_date)->format('Y-m-d H:i');
            $endDate = Carbon::parse($request->return_date)->format('Y-m-d H:i');
            $rentalBookingTransactions = $rentalBookingTransactions->where('start_date', '>=', $startDate)->where('end_date', '<=', $endDate);
        }elseif($request->pickup_date != '' ){
            $startDate = Carbon::parse($request->pickup_date)->format('Y-m-d');
            $rentalBookingTransactions = $rentalBookingTransactions->whereDate('start_date', $startDate);
        }elseif($request->return_date != ''){
            $endDate = Carbon::parse($request->return_date)->format('Y-m-d');
            $rentalBookingTransactions = $rentalBookingTransactions->whereDate('end_date', $endDate);
        }
        elseif (isset($request->paid_status) && $request->paid_status != NULL) {
            $rentalBookingTransactions = $rentalBookingTransactions->where('paid', $request->paid_status);
        }   

        if($orderColumn != '' && $orderType != ''){
            $rentalBookingTransactions = $rentalBookingTransactions->orderBy($orderColumn, $orderType);
        }else{
            $rentalBookingTransactions = $rentalBookingTransactions->orderBy('id', 'DESC');
        }

        if ($page !== null && $pageSize !== null) {
            $bookingTransactions = $rentalBookingTransactions->paginate($pageSize, ['*'], 'page', $page);
            $decodedBookingTransactions = json_decode(json_encode($bookingTransactions->getCollection()->values()), FALSE);
            if(is_countable($decodedBookingTransactions) && count($decodedBookingTransactions) > 0){
                foreach ($decodedBookingTransactions as $key => $value) {
                    $finalAmt = 0;
                    $taxDetails = '';
                    $taxableAmt = getTransactionTaxable($value->id);
                    $convenienceFees = getTransactionConvenienceFees($value->booking_id, $value->type);
                    $value->convenienceFees = $convenienceFees;
                    $value->taxAmt = ($value->tax_amt - $value->vehicle_commission_tax_amt);
                    $finalAmt = $taxableAmt + $convenienceFees + $value->tax_amt;
                    $value->finalAmt = round($finalAmt, 2);
                    if($taxableAmt > 0){
                        $taxableAmt = ($taxableAmt - $value->vehicle_commission_amount);    
                    }
                    $value->taxableAmt = round($taxableAmt, 2);
                    $paymentGateway = usedPaymentGateway($value->booking_id, $value->type, $value->razorpay_order_id, $value->cashfree_order_id);
                    $value->payment_gateway = $paymentGateway['payment_gateway'];
                    $value->payment_gateway_charges = $paymentGateway['payment_gateway_charges'];
                    $value->creation_date = date('d-m-Y H:i', strtotime($value->timestamp));
                    $value->convenience_fee = $value->convenience_fee != NULL ? $value->convenience_fee : 0;
                    $taxPercent = 5;
                    if(isset($value->rental_booking->customer) && $value->rental_booking->customer->gst_number != null){
                    //if($value->rentalBooking && $value->rentalBooking->customer->gst_number != null){
                        $taxPercent = 12;
                    }
                    $value->tax_percent = $taxPercent;
                }
            }
            return $this->successResponse([
                'bookingTransactions' => $decodedBookingTransactions,
                'pagination' => [
                    'total' => $bookingTransactions->total(),
                    'per_page' => $bookingTransactions->perPage(),
                    'current_page' => $bookingTransactions->currentPage(),
                    'last_page' => $bookingTransactions->lastPage(),
                    'from' => ($bookingTransactions->currentPage() - 1) * $bookingTransactions->perPage() + 1,
                    'to' => min($bookingTransactions->currentPage() * $bookingTransactions->perPage(), $bookingTransactions->total()),
                ]], 'Booking Transactions fetched successfully');
        }else{
            $bookingTransactions = $rentalBookingTransactions->get();
            if(is_countable($bookingTransactions) && count($bookingTransactions) > 0){
                foreach ($bookingTransactions as $key => $value) {
                    $finalAmt = 0;
                    $taxDetails = '';
                    $taxableAmt = getTransactionTaxable($value->id);
                    $convenienceFees = getTransactionConvenienceFees($value->booking_id, $value->type);
                    $value->convenienceFees = $convenienceFees;
                    $value->taxAmt = ($value->tax_amt - $value->vehicle_commission_tax_amt);
                    $finalAmt = $taxableAmt + $convenienceFees + $value->tax_amt;
                    $value->finalAmt = round($finalAmt, 2);
                    if($taxableAmt > 0){
                        $taxableAmt = ($taxableAmt - $value->vehicle_commission_amount);    
                    }
                    $value->taxableAmt = round($taxableAmt, 2);
                    $paymentGateway = usedPaymentGateway($value->booking_id, $value->type, $value->razorpay_order_id, $value->cashfree_order_id);
                    $value->payment_gateway = $paymentGateway['payment_gateway'];
                    $value->payment_gateway_charges = $paymentGateway['payment_gateway_charges'];
                    $value->creation_date = date('d-m-Y H:i', strtotime($value->timestamp));
                    $value->convenience_fee = $value->convenience_fee != NULL ? $value->convenience_fee : 0;
                    $taxPercent = 5;
                    if($value->rentalBooking && $value->rentalBooking->customer->gst_number != null){
                        $taxPercent = 12;
                    }
                    $value->tax_percent = $taxPercent;
                }
            }
            $bookingTransactions = [
                'bookingTransactions' => $bookingTransactions,
            ];
            if(isset($bookingTransactions) && is_countable($bookingTransactions) && count($bookingTransactions) > 0){
                return $this->successResponse($bookingTransactions, 'Booking Transactions fetched successfully');
            }else{
                return $this->errorResponse('Booking Transactions are not found');
            }
        }
    }
    
    public function exportTransactions(Request $request){
        $data = [];
        $orderColumn = $request->order_column ?? '';
        $orderType = $request->order_type ?? '';
        $search = $request->search ?? '';
        $orderTypes = config('global_values.order_types');
        $orderTypes = implode(',', $orderTypes);
        $validator = Validator::make($request->all(), [
            'booking_id' => 'nullable|exists:rental_bookings,booking_id',
            'customer_id' => 'nullable|exists:customers,customer_id',
            'vehicle_id' => 'nullable|exists:vehicles,vehicle_id',
            'tax_percent' => 'nullable|numeric|in:5,12',
            'paid_status' => 'nullable|in:0,1',
            'pickup_date' => 'required',
            'return_date' => 'required|after_or_equal:pickup_date',
            //'type' => 'required|in:csv,pdf',
            'order_type' => 'nullable|in:'.$orderTypes,
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $rentalBookingTransactions = BookingTransaction::select('id', 'booking_id', 'type', 'start_date', 'end_date', 'rental_price', 'trip_duration_minutes', 'trip_amount', 'tax_amt', 'coupon_discount', 'coupon_code', 'trip_amount_to_pay', 'convenience_fee', 'total_amount', 'refundable_deposit', 'final_amount', 'late_return', 'exceeded_km_limit', 'additional_charges', 'amount_to_pay', 'refundable_deposit_used', 'from_refundable_deposit', 'paid', 'timestamp')->with(['rentalBooking', 'rentalBooking.customer', 'rentalBooking.vehicle'])->where('is_deleted', 0);
        if($request->booking_id != ''){
            $rentalBookingTransactions = $rentalBookingTransactions->where('booking_id', $request->booking_id);
        }
        if($request->customer_id != ''){
            $rentalBookingTransactions = $rentalBookingTransactions->whereHas('rentalBooking', function ($que) use($request) {
                $que->where('customer_id', $request->customer_id);
            });
        }
        if($request->vehicle_id != ''){
            $rentalBookingTransactions = $rentalBookingTransactions->whereHas('rentalBooking', function ($que) use($request) {
                $que->where('vehicle_id', $request->vehicle_id);
            });
        }
        if($request->tax_percent != '' && $request->tax_percent > 0){
            if($request->tax_percent == 5){
                $rentalBookingTransactions = $rentalBookingTransactions->whereHas('rentalBooking.customer', function ($que) {
                    $que->whereNull('gst_number');
                });
            }elseif($request->tax_percent = 12){
                $rentalBookingTransactions = $rentalBookingTransactions->whereHas('rentalBooking.customer', function ($que) {
                    $que->where('gst_number', '!=', null);
                });
            }
        }
        if($request->pickup_date != '' && $request->return_date != ''){
            $startDate = Carbon::parse($request->pickup_date)->format('Y-m-d H:i');
            $endDate = Carbon::parse($request->return_date)->format('Y-m-d H:i');
            $rentalBookingTransactions = $rentalBookingTransactions->where('start_date', '>=', $startDate)->where('end_date', '<=', $endDate);
        }elseif($request->pickup_date != '' ){
            $startDate = Carbon::parse($request->pickup_date)->format('Y-m-d');
            $rentalBookingTransactions = $rentalBookingTransactions->whereDate('start_date', $startDate);
        }elseif($request->return_date != ''){
            $endDate = Carbon::parse($request->return_date)->format('Y-m-d');
            $rentalBookingTransactions = $rentalBookingTransactions->whereDate('end_date', $endDate);
        }elseif(isset($request->paid_status) && $request->paid_status != NULL){
            $rentalBookingTransactions = $rentalBookingTransactions->where('paid', $request->paid_status);
        }
        $rentalBookingTransactions = $rentalBookingTransactions->get();
        $headers = [
            ['key' => 'booking_id', 'name' => 'Booking Id'],
            ['key' => 'invoice_id', 'name' => 'Invoice Id'],
            ['key' => 'transaction_id', 'name' => 'Transaction Id'],
            ['key' => 'customer_details', 'name' => 'Customer Details'],
            ['key' => 'vehicle_details', 'name' => 'Vehicle Details'],
            ['key' => 'pickup_date', 'name' => 'Pickup Date'],
            ['key' => 'return_date', 'name' => 'Return Date'],
            ['key' => 'taxable_amount', 'name' => 'Taxable Amount(In ₹)'],
            ['key' => 'tax_details', 'name' => 'Tax Details'],
            ['key' => 'convenience_amount', 'name' => 'Convenience Amount(In ₹)'],
            ['key' => 'final_amount', 'name' => 'Final Amount(In ₹)'],
            ['key' => 'type', 'name' => 'Type'],
            ['key' => 'paid_status', 'name' => 'Paid Status'],
            ['key' => 'creation_date', 'name' => 'Creation Date'],
        ];
        
        $allData = [];
        if(isset($rentalBookingTransactions) && is_countable($rentalBookingTransactions) && count($rentalBookingTransactions) > 0){
            foreach($rentalBookingTransactions as $key => $val){
                $finalAmt = 0;
                $taxableAmt = getTransactionTaxable($val->id);
                $val->taxableAmt = $taxableAmt;
                $convenienceFees = getTransactionConvenienceFees($val->booking_id, $val->type);
                $val->convenienceFees = $convenienceFees;
                $finalAmt = $taxableAmt + $convenienceFees + $val->tax_amt;
                $val->finalAmt = $finalAmt;
            }
            foreach($rentalBookingTransactions as $k => $v){
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
                $taxPercent = $v->rentalBooking->customer->gst_number != null ? 12 : 5;
                $taxDetails .= ' Amount - ' . ($v->tax_amt) . '|';
                $taxDetails .= ' Percent - ' . $taxPercent . ' %';
                $status = $v->paid == 1 ? 'PAID' : 'NOT PAID';

                $allData[$k]['booking_id'] = $v->booking_id;
                $allData[$k]['invoice_id'] = $v->rentalBooking->sequence_no ?? '-';
                $allData[$k]['transaction_id'] = $v->id;
                $allData[$k]['customer_details'] = $customerDetails;
                $allData[$k]['vehicle_details'] = $vehicleDetails;
                $allData[$k]['pickup_date'] = $v->start_date ? date('d-m-Y H:i', strtotime($v->start_date)) : '-';
                $allData[$k]['return_date'] = $v->end_date ? date('d-m-Y H:i', strtotime($v->end_date)) : '-';
                $allData[$k]['taxable_amount'] = $v->taxableAmt;
                $allData[$k]['tax_details'] = $taxDetails ?? '';
                $allData[$k]['convenience_amount'] = $v->convenienceFees;
                $allData[$k]['final_amount'] = $v->finalAmt;
                $allData[$k]['type'] = strtoupper($v->type);
                $allData[$k]['paid_status'] = $status;
                $allData[$k]['creation_date'] = date('d-m-Y H:i', strtotime($v->timestamp));
            }
        }

        $allDetails['headers'] = $headers;
        $allDetails['data'] = $allData;
        return $this->successResponse($allDetails, 'Data get successfully');
    }

    public function remainingBookingPenalties(Request $request){
        $page = $request->input('page');
        $pageSize = $request->input('page_size');

        $bookingTransaction = BookingTransaction::where('amount_to_pay', '>', 0)->where('is_deleted', 0)->where('paid', 0)->where(function($query) {
            $query->where('late_return', '>', 0)
                ->orWhere('exceeded_km_limit', '>', 0)
                ->orWhere('additional_charges', '>', 0);
        })->with(['rentalBooking', 'rentalBooking.customer', 'rentalBooking.vehicle']);
        if ($page !== null && $pageSize !== null) {
            $bookingTransaction = $bookingTransaction->paginate($pageSize, ['*'], 'page', $page);
            $decodedTransaction = json_decode(json_encode($bookingTransaction->getCollection()->values()), FALSE);
            return $this->successResponse([
                'bookings' => $decodedTransaction,
                'pagination' => [
                    'total' => $bookingTransaction->total(),
                    'per_page' => $bookingTransaction->perPage(),
                    'current_page' => $bookingTransaction->currentPage(),
                    'last_page' => $bookingTransaction->lastPage(),
                    'from' => ($bookingTransaction->currentPage() - 1) * $bookingTransaction->perPage() + 1,
                    'to' => min($bookingTransaction->currentPage() * $bookingTransaction->perPage(), $bookingTransaction->total()),
                ]], 'Remaining Booking Penalties fetched successfully');
        }else{
            $bookingTransaction = [
                'bookings' => $bookingTransaction->get(),
            ];
            if(isset($bookingTransaction) && is_countable($bookingTransaction) && count($bookingTransaction) > 0){
                return $this->successResponse($bookingTransaction, 'Remaining Booking Penalties are fetched successfully');
            }else{
                return $this->errorResponse('Remaining Booking Penalties are not found');
            }
        }
    }

    public function getCompletionPenalties(Request $request){
        $validator = Validator::make($request->all(), [
            'booking_transaction_id' => 'required|exists:booking_transactions,id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $data['admin_penalty'] = '';
        $data['admin_penalty_info'] = '';
        $data['exceed_km_limit'] = '';
        $data['exceed_hour_limit'] = '';

        $transaction = BookingTransaction::select('booking_id', 'id', 'late_return', 'exceeded_km_limit' ,'additional_charges', 'additional_charges_info', 'amount_to_pay', 'tax_amt')->where('id', $request->booking_transaction_id)->first();
        if (!$transaction) {
            return $this->errorResponse('Invalid Transaction');
        }
    
        $data['admin_penalty'] = round($transaction->additional_charges);
        $data['admin_penalty_info'] = $transaction->additional_charges_info;
        $data['exceed_km_limit'] = round($transaction->exceeded_km_limit);
        $data['exceed_hour_limit'] = round($transaction->late_return);
            
        return $this->successResponse($data, 'Penalties details are get successfully');
    }

    public function storeCompletionPenalties(Request $request){
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|exists:booking_transactions,id',
            'admin_penalty' => 'nullable|numeric',
            'admin_penalty_info' => 'nullable|string|max:255',
            'exceed_km_limit' => 'nullable|numeric',
            'exceed_hours_limit' => 'nullable|numeric',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        
        $adminPenalty = $request->admin_penalty ?? 0; 
        $adminPenaltyInfo = $request->admin_penalty_info ?? '';
        $lateReturnPenalty = $request->exceed_km_limit ?? 0;
        $kmExceedPenalty = $request->exceed_hours_limit ?? 0;
        $oldVal = $newVal = '';
        $bookingTransaction = BookingTransaction::select('id', 'late_return', 'exceeded_km_limit', 'additional_charges', 'additional_charges_info', 'tax_amt', 'amount_to_pay', 'refundable_deposit_used', 'refundable_deposit', 'from_refundable_deposit')->where('id', $request->transaction_id)->first();
        if($bookingTransaction != ''){
            $oldVal = clone $bookingTransaction;
            $bookingTransaction->late_return = $lateReturnPenalty;
            $bookingTransaction->exceeded_km_limit = $kmExceedPenalty;
            $bookingTransaction->additional_charges = $adminPenalty;
            $bookingTransaction->additional_charges_info = $adminPenaltyInfo;
            $bookingTransaction->save();

            $totalPenalty = $adminPenalty + $kmExceedPenalty + $lateReturnPenalty;
            $customerGst = $bookingTransaction->rentalBooking->customer->gst_number ?? '';
            $taxRate = $customerGst ? 0.12 : 0.05;
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

            $newVal = $bookingTransaction;
            $description = 'Penalties are changed for booking ID: '.$bookingTransaction->booking_id.' and Transaction ID: '.$bookingTransaction->id;
            logAdminActivities($description, $oldVal, $newVal);

            return $this->successResponse($bookingTransaction, 'Penalty updated Successfully');
        }
        return $this->errorResponse('Something went Wrong');
    }

    public function getCustomerCanceledRefund(Request $request){
        $page = $request->input('page');
        $pageSize = $request->input('page_size');
        $orderColumn = $request->order_column ?? '';
        $orderType = $request->order_type ?? '';
        $search = $request->search ?? '';
        $orderTypes = config('global_values.order_types');
        $orderTypes = implode(',', $orderTypes);
        $validator = Validator::make($request->all(), [
            'order_type' => 'nullable|in:'.$orderTypes,
            'cancel_refund_id' => 'nullable|exists:cancel_rental_bookings,cancel_id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $data = [];
        $cancelBookings = CancelRentalBooking::select('cancel_id', 'booking_id', 'hours_diffrence', 'refund_percent', 'refund_amount', 'refund_status')->with(['rentalBooking:booking_id,customer_id,vehicle_id', 'rentalBooking.customer:customer_id,firstname,lastname,email,mobile_number', 'rentalBooking.vehicle'])->with('rentalBooking.vehicle.model')->where('refund_amount', '>', 0);

        if($request->cancel_refund_id != ''){
            $cancelBookings = $cancelBookings->where('cancel_id', $request->cancel_refund_id);
        }

        if (!empty($request->cancel_refund_id)) {
            $cancelBookings = $cancelBookings->where('id', $request->cancel_refund_id)->first();
            return $cancelBookings ? $this->successResponse($cancelBookings, 'Cancel Booking details fetched successfully') : $this->errorResponse('Cancel Booking details not found');
        }
        
        if(isset($search) && $search != ''){
            $cancelBookings = $cancelBookings->where(function ($que) use ($search) {
                $que->where('booking_id', 'LIKE', "%{$search}%")
                    ->orWhere('refund_amount', 'LIKE', "%{$search}%")
                    ->orWhereHas('rentalBooking.customer', function ($q) use ($search) {
                        $q->whereRaw('CONCAT(firstname, " ", lastname) LIKE ?', ["%{$search}%"])
                            ->orWhere('email', 'LIKE', "%{$search}%");
                    })
                    ->orWhereHas('rentalBooking.vehicle.model', function ($q2) use ($search) {
                        $q2->join(
                            'vehicle_manufacturers',
                            'vehicle_models.manufacturer_id',
                            '=',
                            'vehicle_manufacturers.manufacturer_id'
                        )
                        ->whereRaw("LOWER(CONCAT(SUBSTRING_INDEX(vehicle_manufacturers.name, ' ', 1), ' ', vehicle_models.name)) LIKE LOWER(?)", "%{$search}%");
                    });
            
                $searchTrimmed = strtolower(trim($search));
                if ($searchTrimmed === 'not refunded') {
                    $que->orWhere('refund_status', 0);
                } elseif ($searchTrimmed === 'refunded in process') {
                    $que->orWhere('refund_status', 1);
                }
            });            
        }
        if($orderColumn != '' && $orderType != ''){
            $cancelBookings = $cancelBookings->orderBy($orderColumn, $orderType);
        }

        if ($page !== null && $pageSize !== null) {
            $cancelBookings = $cancelBookings->paginate($pageSize, ['*'], 'page', $page);
            $razorpayBalance = getRazorpayBalance();
            $cashfreeBalance = getCashfreeBalance();
            if(isset($cancelBookings) && is_countable($cancelBookings) && count($cancelBookings) > 0){
                $cancelBookings->each(function ($booking) {
                    $refundStatus = '';
                    if($booking->refund_status == 0){
                        $refundStatus = 'Not Refunded';
                    }elseif($booking->refund_status == 1){
                        $refundStatus = 'Refund in Process';
                    }
                    if($booking->rentalBooking){
                        $booking->rentalBooking->makeHidden('button_visiblity', 'status_map', 'start_images', 'end_images', 'invoice_pdf','summary_pdf', 'message_map', 'dl_status', 'govtid_status', 'allow_rating', 'rating_value', 'feedback_value', 'pay_now_status', 'admin_penalty_amount', 'price_summary');
                        $booking->rentalBooking->vehicle->makeHidden('year', 'description', 'availability','rental_price', 'extra_km_rate', 'extra_hour_rate', 'availability_calendar', 'commission_percent', 'publish', 'chassis_no', 'banner_image', 'banner_images', 'regular_images', 'rating','total_rating', 'trip_count', 'location');
                    }
                    $booking->refund_status = $refundStatus;
                });
            }
            $decodedCancelBookings = json_decode(json_encode($cancelBookings->getCollection()->values()), FALSE);
            return $this->successResponse([
                'cancelBookings' => $decodedCancelBookings,
                'cashfreeBalance' => $cashfreeBalance,
                'razorpayBalance' => $razorpayBalance,
                'pagination' => [
                    'total' => $cancelBookings->total(),
                    'per_page' => $cancelBookings->perPage(),
                    'current_page' => $cancelBookings->currentPage(),
                    'last_page' => $cancelBookings->lastPage(),
                    'from' => ($cancelBookings->currentPage() - 1) * $cancelBookings->perPage() + 1,
                    'to' => min($cancelBookings->currentPage() * $cancelBookings->perPage(), $cancelBookings->total()),
                ]], 'Cancel Bookings are fetched successfully');
        }else{
            $razorpayBalance = getRazorpayBalance();
            $cashfreeBalance = getCashfreeBalance();
            $cancelBookings = $cancelBookings->get();
            if(isset($cancelBookings) && is_countable($cancelBookings) && count($cancelBookings) > 0){
                $cancelBookings->each(function ($booking) {
                    $refundStatus = '';
                    if($booking->refund_status == 0){
                        $refundStatus = 'Not Refunded';
                    }elseif($booking->refund_status == 1){
                        $refundStatus = 'Refund in Process';
                    }
                    if($booking->rentalBooking){
                        $booking->rentalBooking->makeHidden('button_visiblity', 'status_map', 'start_images', 'end_images', 'invoice_pdf','summary_pdf', 'message_map', 'dl_status', 'govtid_status', 'allow_rating', 'rating_value', 'feedback_value', 'pay_now_status', 'admin_penalty_amount', 'price_summary');
                        $booking->rentalBooking->vehicle->makeHidden('year', 'description', 'availability','rental_price', 'extra_km_rate', 'extra_hour_rate', 'availability_calendar', 'commission_percent', 'publish', 'chassis_no', 'banner_image', 'banner_images', 'regular_images', 'rating','total_rating', 'trip_count', 'location');
                    }
                    $booking->refund_status = $refundStatus;
                });
            }
            $cancelBookings = [
                'cancelBookings' => $cancelBookings,
                'cashfreeBalance' => $cashfreeBalance,
                'razorpayBalance' => $razorpayBalance,
            ];
            if(isset($cancelBookings) && is_countable($cancelBookings) && count($cancelBookings) > 0){
                return $this->successResponse($cancelBookings, 'Cancel Bookings are fetched successfully');
            }else{
                return $this->errorResponse('Cancel Bookings are not found');
            }
        }
    }

    public function canceledRefundProcess(Request $request){
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|exists:rental_bookings,booking_id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $bookingId = $request->booking_id;
        $cancelBooking = CancelRentalBooking::where('booking_id', $bookingId)->first();
        $booking_details = AdminRentalBooking::where('booking_id', $bookingId)->first();
        if($cancelBooking != ''){
            $refundAmt = 0;
            $orderId = $sessionOrPaymentId = $pg = $payment = '';
            $calcDetails = BookingTransaction::where(['booking_id' => $bookingId])->get();
            if(is_countable($calcDetails) && count($calcDetails) > 0){
                foreach ($calcDetails as $k => $v) {
                    if(isset($v->type) && $v->type == 'new_booking'){
                        if(isset($v->razorpay_payment_id) && $v->razorpay_payment_id != NULL && isset($v->razorpay_order_id) && $v->razorpay_order_id != NULL){
                            $orderId = $v->razorpay_payment_id != '' ? $v->razorpay_payment_id : '';
                            $sessionOrPaymentId = $v->razorpay_order_id != '' ? $v->razorpay_order_id : '';
                            $pg = 'razorpay';
                        }elseif(isset($v->cashfree_order_id) && $v->cashfree_order_id != NULL && isset($v->cashfree_payment_session_id) && $v->cashfree_payment_session_id != NULL){
                            $orderId = $v->cashfree_order_id != '' ? $v->cashfree_order_id : '';
                            $sessionOrPaymentId = $v->cashfree_payment_session_id != '' ? $v->cashfree_payment_session_id : '';
                            $pg = 'cashfree';
                        }
                    }
                }
            }
            
            if(isset($cancelBooking->refund_amount) && $cancelBooking->refund_amount != ''){
                $refundAmt = (int)$cancelBooking->refund_amount;
            }
            
            if($refundAmt != 0){
                if ($pg != '' && $pg == 'razorpay') {
                    $payment = Payment::where(function ($query) use ($orderId, $sessionOrPaymentId) {
                        $query->where('razorpay_order_id', $orderId)
                              ->orWhere('razorpay_payment_id', $sessionOrPaymentId);
                    })->first();
                } elseif ($pg != '' && $pg == 'cashfree') {
                    $payment = Payment::where(function ($query) use ($orderId, $sessionOrPaymentId) {
                        $query->where('cashfree_order_id', $orderId)
                              ->orWhere('cashfree_payment_session_id', $sessionOrPaymentId);
                    })->first();
                }
                
                $paymentId = '';
                if($payment != ''){
                    $paymentId = $payment->payment_id;
                }

                $refund = Refund::where(['booking_id' => $bookingId, 'payment_id' => $paymentId, 'refund_amount' => $refundAmt, 'status' => 'processed'])->first();
                if($refund == ''){
                    $refund = new Refund();
                }
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
                
                    $cancelBooking = CancelRentalBooking::where('booking_id', $bookingId)->first();
                    $cancelBooking->refund_status = 1;
                    $cancelBooking->save();
                
                    return $this->successResponse($calculationDetails, 'Refunded Process is Initiated.');
                }else{
                    return $this->errorResponse('Something went wrong.');
                }
            }
            else{
                return $this->errorResponse('Booking Details are not found');
            }
        }else{
            return $this->errorResponse('Booking Details are not found');
        }
    }

    public function getBookingCalculationList(Request $request){
        $page = $request->input('page');
        $pageSize = $request->input('page_size');
        $orderColumn = $request->order_column ?? '';
        $orderType = $request->order_type ?? '';
        $orderTypes = config('global_values.order_types');
        $orderTypes = implode(',', $orderTypes);
        $search = $request->search ?? '';

        $validator = Validator::make($request->all(), [
            'from_date' => 'nullable',
            'to_date' => 'nullable|after_or_equal:from_date',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $rentalBooking = AdminRentalBooking::whereIn('rental_bookings.status', ['no show', 'completed','canceled'])->with('customer:customer_id,country_code,mobile_number,email,firstname,lastname,dob,gst_number,created_at,profile_picture_url')->with(['vehicle', 'payment'])
                ->where('sequence_no', '!=', 0)->whereHas('payments')->leftJoin('customers', 'customers.customer_id', '=', 'rental_bookings.customer_id');
        if($request->from_date != '' && $request->to_date != ''){
            $startDate = Carbon::parse($request->from_date)->format('Y-m-d');
            $endDate = Carbon::parse($request->to_date)->format('Y-m-d');
            $rentalBooking = $rentalBooking->join(DB::raw('(SELECT booking_id,
                            COALESCE(MAX(CASE WHEN type = "completion" THEN timestamp END), MAX(CASE WHEN type = "new_booking" THEN timestamp END)) AS effective_timestamp FROM booking_transactions GROUP BY booking_id) AS transaction_dates'),'rental_bookings.booking_id', '=', 'transaction_dates.booking_id')
            ->whereBetween('transaction_dates.effective_timestamp', [$startDate, $endDate]);
        }
        if(isset($search) && $search != ''){
            $rentalBooking = $rentalBooking->where(function ($query) use ($search) {
            $query->whereRaw('rental_bookings.booking_id LIKE LOWER(?)', ["%$search%"])
                ->orWhereRaw('rental_bookings.sequence_no LIKE LOWER(?)', ["%$search%"])
                ->orWhereRaw('LOWER(customers.firstname) LIKE LOWER(?)', ["%$search%"])
                ->orWhereRaw('LOWER(customers.lastname) LIKE LOWER(?)', ["%$search%"])
                ->orWhereRaw('CONCAT(customers.firstname, " ", customers.lastname) LIKE ?', ["%{$search}%"])
                ->orWhereRaw('CONCAT(customers.lastname, " ", customers.firstname) LIKE ?', ["%{$search}%"])
                ->orWhereRaw('LOWER(customers.gst_number) LIKE ?', ["%{$search}%"]);
                if(strtolower($search) == 'b2b'){
                    $query->orWhereNotNull('customers.gst_number');
                }elseif(strtolower($search) == 'b2c'){
                    $query->orWhereNull('customers.gst_number');
                }
            });
        }

        if($orderColumn != '' && $orderType != ''){
            if($orderColumn == 'booking_id'){
                $rentalBooking = $rentalBooking->orderBy('rental_bookings.booking_id', $orderType);   
            }else{
                $rentalBooking = $rentalBooking->orderBy($orderColumn, $orderType);
            }
       }else{
            $rentalBooking = $rentalBooking->orderBy('booking_id', 'desc');
       }

        if ($page !== null && $pageSize !== null) {
            $rentalBooking = $rentalBooking->paginate($pageSize, ['*'], 'page', $page);
            $rentalBooking->setCollection(processRentalBookings($rentalBooking->getCollection()));
            $decodedRentalBooking = json_decode(json_encode($rentalBooking->getCollection()->values()), FALSE);
            return $this->successResponse([
                'rental_bookings' => $decodedRentalBooking,
                'pagination' => [
                    'total' => $rentalBooking->total(),
                    'per_page' => $rentalBooking->perPage(),
                    'current_page' => $rentalBooking->currentPage(),
                    'last_page' => $rentalBooking->lastPage(),
                    'from' => ($rentalBooking->currentPage() - 1) * $rentalBooking->perPage() + 1,
                    'to' => min($rentalBooking->currentPage() * $rentalBooking->perPage(), $rentalBooking->total()),
                ]], 'Rental Bookings are get Successfully');
        } else {
            $rentalBookings = processRentalBookings($rentalBooking->get());
            $rentalBooking = [
                'rental_bookings' => $rentalBookings,
            ];
            if(isset($rentalBooking) && is_countable($rentalBooking) && count($rentalBooking) > 0){
                return $this->successResponse($rentalBooking, 'Rental Bookings are get Successfully');         
            }else{
                return $this->errorResponse('Rental Bookings are not Found');
            }
        }
    }

    public function exportBookingCalculationList(Request $request){
        $data = [];
        $orderColumn = $request->order_column ?? '';
        $orderType = $request->order_type ?? '';
        $search = $request->search ?? '';
        $orderTypes = config('global_values.order_types');
        $orderTypes = implode(',', $orderTypes);
        $validator = Validator::make($request->all(), [
            'from_date' => 'required',
            'to_date' => 'required|after_or_equal:from_date',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $rentalBooking = AdminRentalBooking::whereIn('status', ['no show', 'completed','canceled'])->with(['customer', 'vehicle', 'refund', 'payment'])
                ->where('sequence_no', '!=', 0)->whereHas('payments');
        if($request->from_date != '' && $request->to_date != ''){
            $startDate = Carbon::parse($request->from_date)->format('Y-m-d');
            $endDate = Carbon::parse($request->to_date)->format('Y-m-d');
            $rentalBooking = $rentalBooking->join(DB::raw('(SELECT booking_id,
                            COALESCE(MAX(CASE WHEN type = "completion" THEN timestamp END), MAX(CASE WHEN type = "new_booking" THEN timestamp END)) AS effective_timestamp FROM booking_transactions GROUP BY booking_id) AS transaction_dates'),'rental_bookings.booking_id', '=', 'transaction_dates.booking_id')
            ->whereBetween('transaction_dates.effective_timestamp', [$startDate, $endDate]);
        }
        $rentalBooking = $rentalBooking->get();
        $tripHours = 0;
        $multiplier = 0;
        $hours = 0;
        foreach ($rentalBooking as $key => $value) {
            $taxableAmount = 0;
            $finalAmount = 0;
            if((is_countable($value->price_summary) && count($value->price_summary) > 0)){
                $cDetails = [];
                $taxVal = $vehicleCommissionAmt = $vehicleCommissionTaxAmt = $vehicleComm = 0;
                $bTransaction = BookingTransaction::where('booking_id', $value->booking_id)->get();
                if(is_countable($bTransaction) && count($bTransaction) > 0){
                    foreach ($bTransaction as $k => $v) {
                        if($v->tax_amt && $v->tax_amt != '' && $v->paid == 1){
                            $taxVal += $v->tax_amt;
                            $taxVal -= $v->vehicle_commission_tax_amt;
                            $vehicleCommissionAmt += $v->vehicle_commission_amount;                
                            $vehicleCommissionTaxAmt += $v->vehicle_commission_tax_amt;  
                            if($v->type != 'penalty'){
                                $vehicleComm += $v->vehicle_commission_amount;  
                            } 
                        }
                    }
                }
                if($value->created_at == NULL){
                    $value->created_date = '-';
                }
                else{
                    $value->created_date = date('d/m/Y', strtotime($value->created_at));
                }
                $cFeesAmt = getConvenienceAmt($value->booking_id, 'amt');
                $cFeesGST = getConvenienceGst($value->booking_id, 'gst');
                $taxableAmt = getTaxableAmt($value->booking_id);
                $value->convenienceFeesAmount = $cFeesAmt;
                $value->convenienceFeesGST = $cFeesGST;
                $taxableAmt -= $vehicleComm;
                $value->taxableAmount = round($taxableAmt, 2);
                $value->vehicleCommissionAmt = round($vehicleCommissionAmt, 2);
                $value->vehicleCommissionTaxAmt = round($vehicleCommissionTaxAmt, 2);
                $value->finalAmt = round(($cFeesAmt + $cFeesGST + $taxableAmt), 2);
                $value->invoiceDate = getInvoiceDate($value->booking_id);
                $value->tax = $taxVal;
                $value->paymentMode = getPaymentMode($value->booking_id);
            }else{
                $value->cDetails = '';    
            }

            // Trip Amount Calculation
            $tripHours = isset($value->rental_duration_minutes) ? $value->rental_duration_minutes / 60 : 0;
            $minTripHoursRule = TripAmountCalculationRule::orderBy('hours')->first();
            if($minTripHoursRule != ''){
                if ($tripHours < $minTripHoursRule->hours) {
                    $tripHours = $minTripHoursRule->hours;
                }
            }
            $value->tripDurationInHours = $tripHours;
            $rules = TripAmountCalculationRule::orderBy('hours', 'desc')->get()->toArray();
            $multiplier = 1;
            $hours = isset($minTripHoursRule->hours) ? $minTripHoursRule->hours : 0;
            if(isset($rules) && is_countable($rules) && count($rules) > 0){
                foreach ($rules as $rule) {
                    if ($tripHours >= $rule['hours']) {
                        $multiplier = $rule['multiplier'];
                        $hours = $rule['hours'];
                        break;
                    }
                }
            }
            $value->multiplier = $multiplier;
            $value->hours = $hours;
        }

        $headers = [
            ['key' => 'invoice_no', 'name' => 'Invoice No.'],
            ['key' => 'booking_id', 'name' => 'Booking Id'],
            ['key' => 'invoice_date', 'name' => 'Invoice Date'],
            ['key' => 'payment_mode', 'name' => 'Payment Mode'],
            ['key' => 'party_name', 'name' => 'Party Name'],
            ['key' => 'gstn', 'name' => 'GSTN (If Have)'],
            ['key' => 'b2b_b2c', 'name' => 'B2B/B2C'],
            ['key' => 'gst_percent', 'name' => 'GST %'],
            ['key' => 'taxable_values', 'name' => 'Taxble Values'],
            ['key' => 'cgst', 'name' => 'CGST'],
            ['key' => 'sgst', 'name' => 'SGST'],
            ['key' => 'igst', 'name' => 'IGST (Out Of State'],
            ['key' => 'convenience_amount', 'name' => 'Convenience Fees Amount'],
            ['key' => 'convenience_fees_gst', 'name' => 'Convenience Fees GST'],
            ['key' => 'vehicle_commission_amt', 'name' => 'Vehicle Commission Amount'],
            ['key' => 'vehicle_commission_tax', 'name' => 'Vehicle Commission Tax'],
            ['key' => 'total_value', 'name' => 'Total Value'],
        ];

        $allData = [];
        if(isset($rentalBooking) && is_countable($rentalBooking) && count($rentalBooking) > 0){
            foreach($rentalBooking as $k => $v){
                $customerDetails = $customerGst = '';
                $gstPercent = 5;
                $b2bb2c = 'B2C';
                if($v->customer->firstname != null && $v->customer->lastname != null){
                    $customerDetails .= $v->customer->firstname.' '.$v->customer->lastname.'<br/>';
                }
                $tax = $v->tax ? $v->tax : 0;
                $lastAmt = $displayTax = $iGST = 0;
                if($v->customer->gst_number != null){
                    $customerGst = $v->customer->gst_number ?? '';    
                    $b2bb2c = "B2B";
                    $gstPercent = 12;
                }
                if($v->finalAmt){
                    $lastAmt = $v->finalAmt;
                }
                if($v->customer && $tax != 0){  
                    $gst = '';
                    $lastAmt = (float)$lastAmt + (float)$tax + (float)$v->vehicleCommissionTaxAmt + (float)$v->vehicleCommissionAmt; 
                    $lastAmt = round($lastAmt);
                    if($v->customer->gst_number){
                        $gst = str_starts_with($v->customer->gst_number, '24');
                    }
                    if($v->customer->gst_number == null){
                        $tax = number_format((float)$tax, 2, '.', '');
                        $gstTax = $tax / 2;
                        $displayTax = "<b>CGST</b> - ".number_format((float)$gstTax, 2, '.', '')." <br/><b>SGST</b> - ".number_format((float)$gstTax, 2, '.', '')."<br/><b>Total Tax</b> - ".number_format((float)$gstTax, 2, '.', '');         
                        $cGSTsGST = number_format((float)$gstTax, 2, '.', '');
                        $cGSTsGSTPercent = $gstPercent / 2;
                        $cGSTsGST = $cGSTsGST . "(".$cGSTsGSTPercent."%)";
                    } else if($gst && $tax != 0){
                        $tax = number_format((float)$tax, 2, '.', '');
                        $gstTax = $tax / 2;
                        $displayTax = "<b>CGST</b> - ".number_format((float)$gstTax, 2, '.', '')." <br/><b>SGST</b> - ".number_format((float)$gstTax, 2, '.', '')."<br/><b>Total Tax</b> - ".number_format((float)$gstTax, 2, '.', '');     
                        $cGSTsGST = parseFloat($gstTax).toFixed(2);  
                        $cGSTsGSTPercent = $gstPercent / 2;
                        $cGSTsGST = $cGSTsGST . "(".$cGSTsGSTPercent."%)";
                    }
                    else if(!$gst && $tax != 0){
                        $displayTax = "<b>IGST</b> - ".number_format((float)$gstTax, 2, '.', '');
                        $iGST = parseFloat($tax).toFixed(2);
                        $cGSTsGSTPercent = $gstPercent;
                        $iGST = $iGST . "(".$cGSTsGSTPercent."%)";
                    }
                }
                
                $allData[$k]['invoice_no'] = $v->sequence_no;
                $allData[$k]['booking_id'] = $v->booking_id ?? '-';
                $allData[$k]['invoice_date'] = $v->invoiceDate ?? '';
                $allData[$k]['payment_mode'] = $v->paymentMode;
                $allData[$k]['party_name'] = $customerDetails;
                $allData[$k]['gstn'] = $customerGst;
                $allData[$k]['b2b_b2c'] = $b2bb2c;
                $allData[$k]['gst_percent'] = $gstPercent;
                $allData[$k]['taxable_values'] = $v->taxableAmount ?? '';
                $allData[$k]['cgst'] = $cGSTsGST;
                $allData[$k]['sgst'] = $cGSTsGST;
                $allData[$k]['igst'] = $iGST;
                $allData[$k]['convenience_amount'] = $v->convenienceFeesAmount ?? '';
                $allData[$k]['convenience_fees_gst'] = $v->convenienceFeesGST;
                $allData[$k]['vehicle_commission_amt'] = $v->vehicleCommissionAmt;
                $allData[$k]['vehicle_commission_tax'] = $v->vehicleCommissionTaxAmt;
                $allData[$k]['total_value'] = $lastAmt;
            }
        }
        $allDetails['headers'] = $headers;
        $allDetails['data'] = $allData;
        
        return $this->successResponse($allDetails, 'Data get successfully');
    }

    public function getTripAmountCalculationList(Request $request){
        $page = $request->input('page');
        $pageSize = $request->input('page_size');
        $orderColumn = $request->order_column ?? '';
        $orderType = $request->order_type ?? '';
        $orderTypes = config('global_values.order_types');
        $orderTypes = implode(',', $orderTypes);
        $search = $request->search ?? '';
        $validator = Validator::make($request->all(), [
            'id' => 'nullable|exists:trip_amount_calculation_rules,id',
            'order_type' => 'nullable|in:'.$orderTypes,
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $tripAmtCalcRule = TripAmountCalculationRule::select('id', 'hours', 'multiplier');
        if(isset($request->id) && $request->id != NULL){
            $tripAmtCalcRule = $tripAmtCalcRule->where('id', $request->id)->first();
            return $tripAmtCalcRule ? $this->successResponse($tripAmtCalcRule, 'Trip Amount Calculation Rules get Successfully') : $this->errorResponse('Trip Amount Calculation Rules not Found');
        }
        if(isset($search) && $search != ''){
            $tripAmtCalcRule = $tripAmtCalcRule->where(function ($query) use ($search) {
                $query->whereRaw('LOWER(id) LIKE LOWER(?)', ["%$search%"])
                ->orWhereRaw('LOWER(hours) LIKE LOWER(?)', ["%$search%"])
                ->orWhereRaw('LOWER(multiplier) LIKE LOWER(?)', ["%$search%"]);
            });
        }
        if($orderColumn != '' && $orderType != ''){
            $tripAmtCalcRule = $tripAmtCalcRule->orderBy($orderColumn, $orderType);
        }

        if(isset($request->id) && $request->id != ''){
            $tripAmtCalcRule = $tripAmtCalcRule->where('id', $request->id)->first();
        }
        if ($page !== null && $pageSize !== null) {
            $tripAmtCalcRule = $tripAmtCalcRule->paginate($pageSize, ['*'], 'page', $page);
            $decodedTripCalc = json_decode(json_encode($tripAmtCalcRule->getCollection()->values()), FALSE);
            return $this->successResponse([
                'tripAmountCalculationRules' => $decodedTripCalc,
                'pagination' => [
                    'total' => $tripAmtCalcRule->total(),
                    'per_page' => $tripAmtCalcRule->perPage(),
                    'current_page' => $tripAmtCalcRule->currentPage(),
                    'last_page' => $tripAmtCalcRule->lastPage(),
                    'from' => ($tripAmtCalcRule->currentPage() - 1) * $tripAmtCalcRule->perPage() + 1,
                    'to' => min($tripAmtCalcRule->currentPage() * $tripAmtCalcRule->perPage(), $tripAmtCalcRule->total()),
            ]], 'Trip Amount Calculation Rules are get Successfully');
        } else{
            $tripAmtCalcRule = [
                'tripAmountCalculationRules' => $tripAmtCalcRule->get(),
            ];
            if(isset($tripAmtCalcRule) && is_countable($tripAmtCalcRule) && count($tripAmtCalcRule) > 0){
                return $this->successResponse($tripAmtCalcRule, 'Trip Amount Calculation Rules are get Successfully');         
            }else{
                return $this->errorResponse('Trip Amount Calculation Rules are not Found');
            }
        } 
    }

    public function createOrUpdateTripAmtCalc(Request $request){
        $validator = Validator::make($request->all(), [
            'hours' => 'required|numeric',
            'multiplier' => 'required|numeric',
            'id' => 'nullable|exists:trip_amount_calculation_rules,id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $oldVal = $newVal = [];
        if(isset($request->id) && $request->id != ''){
            $tripAmtCalc = TripAmountCalculationRule::where('id', $request->id)->first();
            if($tripAmtCalc != ''){
                $oldVal = clone $tripAmtCalc;
            }
        }else{
            $tripAmtCalc = new TripAmountCalculationRule();
        }

        $tripAmtCalc->hours = $request->hours ?? NULL;
        $tripAmtCalc->multiplier = $request->multiplier ?? NULL;
        $tripAmtCalc->save();
        if(isset($request->id) && $request->id != ''){
            $newVal = $tripAmtCalc;
            $differences = compareArray($oldVal, $newVal);
            if(isset($differences) && is_countable($differences) && count($differences) > 0){
                logAdminActivities('Trip Amount Calculation Updation', $oldVal, $newVal);
            }
        }else{
            logAdminActivities("Trip Amount Calculation Rule Creation", $tripAmtCalc);
        }
    
        return $this->successResponse($tripAmtCalc, 'Trip Amount Calculation Rule are set Successfully');
    }

    public function getRewards(Request $request){
        $validator = Validator::make($request->all(), [
            'customer_id' => 'nullable|exists:customers,customer_id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $page = $request->input('page');
        $pageSize = $request->input('page_size');
        $orderColumn = $request->order_column ?? '';
        $orderType = $request->order_type ?? '';
        $orderTypes = config('global_values.order_types');
        $orderTypes = implode(',', $orderTypes);
        $search = $request->search ?? '';
        $validator = Validator::make($request->all(), [
            'order_type' => 'nullable|in:'.$orderTypes,
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $customerReferralDetails = CustomerReferralDetails::with(['customerDetails' => function($q){
            $q->select('customer_id', 'firstname', 'lastname', 'email', 'mobile_number', 'account_holder_name', 'bank_name', 'branch_name', 'city', 'account_no', 'ifsc_code', 'nick_name');
        }])->with(['referredUser' => function($q){
           $q->select('customer_id', 'firstname', 'lastname', 'email', 'mobile_number', 'my_referral_code');
        }])->where('payable_amount', '>', 0);

        if(isset($request->customer_id) && $request->customer_id != ''){
            $customerReferralDetails = $customerReferralDetails->where('customer_id');
        }
        
        if(isset($search) && $search != ''){
            $customerReferralDetails = $customerReferralDetails->where(function ($query) use ($search) {
                $query->whereRaw('LOWER(used_referral_code) LIKE LOWER(?)', ["%$search%"])
                    ->orWhereRaw('LOWER(booking_id) LIKE LOWER(?)', ["%$search%"])
                    ->orWhereRaw('LOWER(reward_amount_or_percent) LIKE LOWER(?)', ["%$search%"])
                    ->orWhereRaw('LOWER(payable_amount) LIKE LOWER(?)', ["%$search%"]);

                $query->orWhereHas('customerDetails', function ($q) use ($search) {
                    $q->whereRaw('LOWER(firstname) LIKE LOWER(?)', ["%$search%"])
                        ->orWhereRaw('LOWER(lastname) LIKE LOWER(?)', ["%$search%"])
                        ->orWhereRaw('LOWER(email) LIKE LOWER(?)', ["%$search%"])
                        ->orWhereRaw('LOWER(mobile_number) LIKE LOWER(?)', ["%$search%"]);
                });

                $query->orWhereHas('referredUser', function ($q) use ($search) {
                    $q->whereRaw('LOWER(firstname) LIKE LOWER(?)', ["%$search%"])
                        ->orWhereRaw('LOWER(lastname) LIKE LOWER(?)', ["%$search%"])
                        ->orWhereRaw('LOWER(email) LIKE LOWER(?)', ["%$search%"])
                        ->orWhereRaw('LOWER(mobile_number) LIKE LOWER(?)', ["%$search%"]);
                });

                if(strtolower($search) == 'fixed'){
                    $query->orWhere('reward_type', 1);
                }elseif(strtolower($search) == 'percentage'){
                    $query->orWhere('reward_type', 2);
                }

                if(strtolower($search) == 'pending'){
                    $query->orWhere('is_paid', 1);
                }elseif(strtolower($search) == 'paid'){
                    $query->orWhere('is_paid', 2);
                }
            });
        }
        if($orderColumn != '' && $orderType != ''){
           $customerReferralDetails = $customerReferralDetails->orderBy($orderColumn, $orderType);
        }
        
        if ($page !== null && $pageSize !== null) {
            $customerReferralDetails = $customerReferralDetails->paginate($pageSize, ['*'], 'page', $page);
            if(isset($customerReferralDetails) && is_countable($customerReferralDetails) && count($customerReferralDetails) > 0){
                foreach($customerReferralDetails as $key => $value){
                    $rewardType = '';
                    if($value->reward_type == 1){
                        $rewardType = 'Fixed';
                    }elseif($value->reward_type == 2){
                        $rewardType = 'Percentage';
                    }
                    $value->reward_type = $rewardType;
                }
            }
            $decodedReferralDetails = json_decode(json_encode($customerReferralDetails->getCollection()->values()), FALSE);
            return $this->successResponse([
                'customer_referral_details' => $decodedReferralDetails,
                'pagination' => [
                    'total' => $customerReferralDetails->total(),
                    'per_page' => $customerReferralDetails->perPage(),
                    'current_page' => $customerReferralDetails->currentPage(),
                    'last_page' => $customerReferralDetails->lastPage(),
                    'from' => ($customerReferralDetails->currentPage() - 1) * $customerReferralDetails->perPage() + 1,
                    'to' => min($customerReferralDetails->currentPage() * $customerReferralDetails->perPage(), $customerReferralDetails->total()),
                ]], 'Customer Referral Details are get Successfully');
        }else{
            $customerReferralDetails = $customerReferralDetails->get();
            if(isset($customerReferralDetails) && is_countable($customerReferralDetails) && count($customerReferralDetails) > 0){
                foreach($customerReferralDetails as $key => $value){
                    $rewardType = '';
                    if($value->reward_type == 1){
                        $rewardType = 'Fixed';
                    }elseif($value->reward_type == 2){
                        $rewardType = 'Percentage';
                    }
                    $value->reward_type = $rewardType;
                }
            }
            $customerReferralDetails = [
                'customer_referral_details' => $customerReferralDetails,
            ];
            if(isset($customerReferralDetails) && is_countable($customerReferralDetails) && count($customerReferralDetails) > 0){
                return $this->successResponse($customerReferralDetails, 'Customer Referral Details are get Successfully');         
            }else{
                return $this->errorResponse('Customer Referral Details are not Found');
            }
        }
    }

    public function payRewards(Request $request){
        $validator = Validator::make($request->all(), [
            'reward_id' => 'nullable|exists:customer_referral_details,id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $rewardDetails = CustomerReferralDetails::where('id', $request->reward_id)->first(); 
        if($rewardDetails != ''){
            $rewardDetails->is_paid = 1;
            $rewardDetails->save();

            return $this->successResponse($rewardDetails, 'Rewards paid Successfully');         
        }else{
            return $this->errorResponse('Customer Rewards are not Found');
        }
    }

}