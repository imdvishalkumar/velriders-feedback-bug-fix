<?php

namespace App\Http\Controllers\AdminApis;

use App\Http\Controllers\Controller; 
use Illuminate\Http\Request;
use App\Models\{Customer, Coupon};
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Http;

class CustomerDataController extends Controller
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

    public function getCustomers(Request $request){
        $page = $request->input('page');
        $pageSize = $request->input('page_size');
        $orderColumn = $request->order_column ?? '';
        $orderType = $request->order_type ?? '';
        $search = $request->search ?? '';
        $orderTypes = config('global_values.order_types');
        $orderTypes = implode(',', $orderTypes);
        $validator = Validator::make($request->all(), [
            'customer_id' => 'nullable|exists:customers,customer_id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $customers = Customer::select('customer_id', 'country_code', 'mobile_number', 'email', 'firstname', 'lastname', 'dob', 'profile_picture_url', 'billing_address', 'shipping_address','device_token', 'device_id', 'is_deleted', 'is_blocked')->where('is_deleted', 0);
        if (!empty($request->customer_id)) {
            $customers = $customers->where('customer_id', $request->customer_id)->first();
            $customerStatus = $this->getCustomerStatus($customers);
            $customers->status = $customerStatus;
            return $customers ? $this->successResponse($customers, 'Customer details fetched successfully') : $this->errorResponse('Customer user not found');
        }
        
        if(isset($search) && $search != ''){
            $checkCust = Customer::where('customer_id', (int)$search)->exists();
            if($checkCust){
                $customers = $customers->where('customer_id', $search);
            }
            else{
                $customers = $customers->where(function ($query) use ($search) {
                $query->whereRaw('LOWER(country_code) LIKE LOWER(?)', ["%$search%"])
                      ->orWhereRaw('LOWER(mobile_number) LIKE LOWER(?)', ["%$search%"])
                      ->orWhereRaw('LOWER(email) LIKE LOWER(?)', ["%$search%"])
                      ->orWhereRaw('LOWER(firstname) LIKE LOWER(?)', ["%$search%"])
                      ->orWhereRaw('LOWER(lastname) LIKE LOWER(?)', ["%$search%"])
                      ->orWhereRaw('CONCAT(firstname, " ", lastname) LIKE ?', ["%{$search}%"])
                      ->orWhereRaw('CONCAT(lastname, " ", firstname) LIKE ?', ["%{$search}%"])
                      ->orWhereRaw('LOWER(billing_address) LIKE LOWER(?)', ["%$search%"])
                      ->orWhereRaw('LOWER(device_token) LIKE LOWER(?)', ["%$search%"])
                      ->orWhereRaw('LOWER(device_id) LIKE LOWER(?)', ["%$search%"]);

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
            $customers = $customers->orderBy($orderColumn, $orderType);
        }
        if ($page !== null && $pageSize !== null) {
            $customers = $customers->paginate($pageSize, ['*'], 'page', $page);

            if(isset($customers) && is_countable($customers) && count($customers) > 0){
                foreach($customers as $k => $v){
                    $customerStatus = $this->getCustomerStatus($v);
                    $v->status = $customerStatus;
                }
            }
            
            $decodedAdmins = json_decode(json_encode($customers->getCollection()->values()), FALSE);
    
            return $this->successResponse([
                'customers' => $decodedAdmins,
                'pagination' => [
                    'total' => $customers->total(),
                    'per_page' => $customers->perPage(),
                    'current_page' => $customers->currentPage(),
                    'last_page' => $customers->lastPage(),
                    'from' => ($customers->currentPage() - 1) * $customers->perPage() + 1,
                    'to' => min($customers->currentPage() * $customers->perPage(), $customers->total()),
                ]], 'Customers fetched successfully');
        }else{
            $customers = $customers->get();
            if(isset($customers) && is_countable($customers) && count($customers) > 0){
                foreach($customers as $k => $v){
                    $customerStatus = $this->getCustomerStatus($v);
                    $v->status = $customerStatus;
                }
            }
            $customers = [
                'customers' => $customers,
            ];
            if(isset($customers) && is_countable($customers) && count($customers) > 0){
                return $this->successResponse($customers, 'Customers fetched successfully');
            }else{
                return $this->errorResponse('Customers users not found');
            }
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

    public function updateCustomer(Request $request){
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,customer_id',
            'mobile_number' => [
                'nullable',
                'string',
                'regex:/^\+?[1-9]\d{9,14}$/',
                'digits_between:8,15'
            ],
            'email' => [
                'nullable',
                'email',
                'email:rfc,dns',
                'max:255',
                function ($attribute, $value, $fail) {
                    // Check if '@' exists in the value before exploding
                    if (!str_contains($value, '@')) {
                        $fail('The :attribute must be a valid email address.');
                        return;
                    }
                    // Split the email into local part (before @) and domain part (after @)
                    [$localPart, $domain] = explode('@', $value, 2); // Limit to 2 parts to avoid errors
                    // Allow only a-z, 0-9, ., _, - in the local part
                    if (!preg_match('/^[a-z0-9._-]+$/', $localPart)) {
                        $fail('The :attribute can only contain lowercase letters, numbers, dots (.), underscores (_), and dashes (-).');
                    }
                    // Ensure the local part contains at least one letter (a-z)
                    if (!preg_match('/[a-z]/', $localPart)) {
                        $fail('The :attribute must contain at least one letter.');
                    }
                }
            ],
            'country_code' => ['nullable', 'string', 'regex:/^\+[1-9]\d{1,3}$/'],
            'dob' => 'nullable|date',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        if(isset($request->customer_id) && $request->customer_id != ''){
            $customer = Customer::where('customer_id', $request->customer_id)->first();
            $oldVal = clone $customer;
            if($customer != ''){
                $customer->mobile_number = $request->mobile_number;
                $customer->email = $request->email;
                $customer->country_code = $request->country_code;
                $customer->dob = date('Y-m-d', strtotime($request->dob));
                $customer->billing_address = $request->billing_address;
                $customer->shipping_address = $request->shipping_address;
                $customer->save();
                $newVal = $customer;

                $array1 = $oldVal->toArray();
                $array2 = $newVal->toArray();
                unset($array1['documents']);
                unset($array2['documents']);
                // Find differences between arrays
                $differences = array_diff_assoc($array1, $array2);
                if(isset($differences) && is_countable($differences) && count($differences) > 0){
                    logAdminActivities('Customer Updation', $oldVal, $newVal);
                }

                return $this->successResponse($customer, 'Customer details are updated Successfully');
            }else{
                return $this->errorResponse('Customer not Found');
            }
        }else{
            return $this->errorResponse('Customer not Found');
        }
    }

    public function deleteCustomer(Request $request){
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,customer_id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $customerId = $request->customer_id;
        $customer = Customer::where('customer_id',  $customerId)->first();
        if ($customer == '') {
            return $this->errorResponse('Customer not Found');
        }
        $customer->is_deleted = 1;
        $customer->save();
        logAdminActivities("Customer Deletion", $customer);

        return $this->successResponse($customer, 'Customer deleted successfully');
    }

    public function resendMail(Request $request) {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,customer_id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $customerId = $request->customer_id;
        $customerDetails = Customer::select('customer_id', 'firstname', 'lastname', 'email', 'is_deleted', 'is_blocked')->where(['customer_id' => $customerId, 'is_deleted' => 0, 'is_blocked' => 0])->first();
        if($customerDetails != '' && $customerDetails->email != ''){
            $to = $customerDetails->email;
            $subject = "Email Verification";
            $from = config('global_values.mail_from');
            $customerId = Crypt::encrypt($customerDetails->customer_id);
            $name = $customerDetails->firstname ?? '';
            $name .= ' '.$customerDetails->lastname ?? '';
            $email = Crypt::encrypt($to);
            $app = Crypt::encrypt('v_main');
            if(isset($to) && $to != ''){
                try{
                    // Send Verification mail to Customer
                    Mail::send('emails.front.email_verification', ['customer_id' => $customerId, 'name' => $name, 'email' => $email, 'app' => $app], function ($m) use ($subject, $to, $from) {
                        $m->from($from)->to($to)->subject($subject);
                    });
                } catch (\Exception $e) {} 
                return $this->successResponse($customerDetails, 'Customer mail send successfully');
            }else {
                return $this->errorResponse('Customer email not Found');
            }
        } else {
            return $this->errorResponse('Customer not Found');
        }
    }

    public function blockUnblockUser(Request $request){
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,customer_id',
            'status' => 'required|in:1,0',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $custId = $request->customer_id;
        $customer = Customer::find($custId);
        $customer->is_blocked =  $request->status;
        $customer->save();

        if($request->status == 1){
            logAdminActivities("Customer Block Activity", $customer);
            return $this->successResponse($customer, 'Customer blocked successfully');
        }
        else{
            logAdminActivities("Customer Un-Block Activity", $customer);
            return $this->successResponse($customer, 'Customer un-blocked successfully');
        }   
    }

    public function getCoupons(Request $request){
        $page = $request->input('page');
        $pageSize = $request->input('page_size');
        $orderColumn = $request->order_column ?? '';
        $orderType = $request->order_type ?? '';
        $search = $request->search ?? '';
        $orderTypes = config('global_values.order_types');
        $orderTypes = implode(',', $orderTypes);
        $validator = Validator::make($request->all(), [
            'id' => 'nullable|exists:coupons,id',
            'order_type' => 'nullable|in:'.$orderTypes,
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $coupons = Coupon::where('is_deleted', 0);
        if (!empty($request->id)) {
            $coupon = $coupons->where('id', $request->id)->first();
            $coupon->discount_type = $coupon->type;
            $coupon->makeHidden('type');
            return $coupon ? $this->successResponse($coupon, 'Coupon details fetched successfully') : $this->errorResponse('Coupon not found');
        }
        
        if(isset($search) && $search != ''){
            $coupons = $coupons->where(function ($query) use ($search) {
                $query->whereRaw('LOWER(code) LIKE LOWER(?)', ["%$search%"])
                    ->orWhereRaw('LOWER(type) LIKE LOWER(?)', ["%$search%"])
                    ->orWhereRaw('LOWER(percentage_discount) LIKE LOWER(?)', ["%$search%"])
                    ->orWhereRaw('LOWER(max_discount_amount) LIKE LOWER(?)', ["%$search%"]);
            });
        }

        if($orderColumn != '' && $orderType != ''){
            $coupons = $coupons->orderBy($orderColumn, $orderType);
        }
        if ($page !== null && $pageSize !== null) {
            $coupons = $coupons->paginate($pageSize, ['*'], 'page', $page);
            if(isset($coupons) && is_countable($coupons) && count($coupons) > 0){
                foreach($coupons as $key => $val){
                    $val->discount_type = $val->type;
                    $val->makeHidden('type');
                }
            }
            $decodedcoupons = json_decode(json_encode($coupons->getCollection()->values()), FALSE);

            return $this->successResponse([
                'coupons' => $decodedcoupons,
                'pagination' => [
                    'total' => $coupons->total(),
                    'per_page' => $coupons->perPage(),
                    'current_page' => $coupons->currentPage(),
                    'last_page' => $coupons->lastPage(),
                    'from' => ($coupons->currentPage() - 1) * $coupons->perPage() + 1,
                    'to' => min($coupons->currentPage() * $coupons->perPage(), $coupons->total()),
                ]], 'Coupons fetched successfully');
        }else{
            $coupons = $coupons->get();
            if(isset($coupons) && is_countable($coupons) && count($coupons) > 0){
                foreach($coupons as $key => $val){
                    $val->discount_type = $val->type;
                    $val->makeHidden('type');
                }
            }
            $coupons = [
                'coupons' => $coupons,
            ];
            if(isset($coupons) && is_countable($coupons) && count($coupons) > 0){
                return $this->successResponse($coupons, 'Coupons fetched successfully');
            }else{
                return $this->errorResponse('Coupons are not found');
            }
        }
    }

    public function setCouponStatus(Request $request){
        $validator = Validator::make($request->all(), [
            'status_type' => 'required|in:active,show,delete',
            'coupon_id' => 'required|exists:coupons,id',
            'status' => 'required|in:0,1'
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        if(isset($request->status_type) && $request->status_type != ''){
            $coupon = Coupon::where('id', $request->coupon_id)->first();
            if($coupon != ''){
                $msg = '';
                if($request->status_type == 'active'){
                    if($request->status == 1){
                        $coupon->is_active = 1;
                        $msg = "Coupon Activated Successfully";
                        $coupon->save();
                    }elseif($request->status == 0){
                        $coupon->is_active = 0;
                        $msg = "Coupon In-Activated Successfully";
                        $coupon->save();
                    }
                    logAdminActivities($msg, $coupon);
                    return $this->successResponse($coupon, $msg);
                }elseif($request->status_type == 'show'){   
                    if($request->status == 1){
                        $coupon->is_show = 1;
                        $msg = "Coupon Show Successfully";
                        $coupon->save();
                    }elseif($request->status == 0){
                        $coupon->is_show = 0;
                        $msg = "Coupon Not Show Successfully";
                        $coupon->save();
                    }
                    logAdminActivities($msg, $coupon);
                    return $this->successResponse($coupon, $msg);
                }elseif($request->status_type == 'delete'){
                    $coupon->is_deleted = 1;
                    $coupon->save();
                    logAdminActivities("Coupon Code Deletion", $coupon);
                    return $this->successResponse($coupon, "Coupon deleted Successfully");
                }    
            }else{
                return $this->errorResponse('Coupon not found');    
            }
        }else{
            return $this->errorResponse('Invalid status type');
        }
    }

    public function createOrUpdateCoupon(Request $request){
        $now = Carbon::now()->setTimezone('Asia/Kolkata');
        $startDate = $request->filled('valid_from') ? Carbon::parse($request->valid_from)->format('Y-m-d H:i:s'): null;
        $endDate = $request->filled('valid_to')? Carbon::parse($request->valid_to)->format('Y-m-d H:i:s'): null;
        $validator = Validator::make(array_merge($request->all(), [
            'valid_from' => $startDate,
            'valid_to' => $endDate,
        ]), [
            'coupon_id' => [
                'nullable',
                Rule::exists('coupons', 'id')->where(function ($query) {
                    $query->where('is_deleted', 0);
                }),
            ],
            'code' => [
                'required',
                function ($attribute, $value, $fail) use ($request) {
                    if (!$request->filled('coupon_id')) {
                        $exists = DB::table('coupons')->where('code', $value)->exists();
                        if ($exists) {
                            $fail('The coupon code has already been taken.');
                        }
                    }
                },
            ],
            'discount_type' => 'required|in:percentage,fixed',
            'percentage_discount' => 'required_if:discount_type,percentage|numeric|max:100',
            'max_discount_amount' => 'required_if:discount_type,percentage|numeric',
            'fixed_discount_amount' => 'required_if:discount_type,fixed|numeric',
            'customer_id' => 'nullable|exists:customers,customer_id',
            'valid_from' => 'required|after:' . $now,
            'valid_to' => 'required|after:valid_from',
            'single_use_per_customer' => 'required|in:0,1',
            'one_time_use_among_all' => 'required|in:0,1',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $oldVal = $newVal = '';
        if(isset($request->coupon_id) && $request->coupon_id != ''){
            $coupon = Coupon::where('id', $request->coupon_id)->first();
            $oldVal = clone $coupon;
        }else{
            $coupon = new Coupon();
        }
        $coupon->code = isset($request->code) ? $request->code :'';
        $coupon->type = isset($request->discount_type) ? $request->discount_type : '';
        $coupon->percentage_discount = isset($request->percentage_discount) ? $request->percentage_discount : 0;
        $coupon->max_discount_amount = isset($request->max_discount_amount) ? $request->max_discount_amount : 0;
        $coupon->fixed_discount_amount = isset($request->fixed_discount_amount) ? $request->fixed_discount_amount : 0;
        $coupon->customer_id = isset($request->customer_id) ? $request->customer_id : 0;
        $coupon->valid_from = $startDate;
        $coupon->valid_to = $endDate;
        if(!isset($request->coupon_id) && $request->coupon_id == ''){
            $coupon->is_active = 1;
            $coupon->is_show = 0;
        }
        $coupon->single_use_per_customer = isset($request->single_use_per_customer)?$request->single_use_per_customer:0;
        $coupon->one_time_use_among_all = isset($request->one_time_use_among_all)?$request->one_time_use_among_all:0;
        $coupon->save();
            
        if(isset($request->coupon_id) && $request->coupon_id != ''){
            $newVal = $coupon;
            $differences = compareArray($oldVal, $newVal);
            if(isset($differences) && is_countable($differences) && count($differences) > 0){
                logAdminActivities('Coupon Code Updation', $oldVal, $newVal);
            }
            return $this->successResponse($coupon, 'Coupon Updated successfully!');
        }else{
            logAdminActivities("Coupon Code Creation", $coupon);
            return $this->successResponse($coupon, 'Coupon Created successfully!');
        }
    }

    public function deleteCoupon(Request $request){
        $validator = Validator::make($request->all(), [
            'coupon_id' => 'required|exists:coupons,id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $coupon = Coupon::where('id', $request->coupon_id)->first();
        if ($coupon) {
            $coupon->is_deleted = 1;
            $coupon->is_show = 0;
            $coupon->save();

            logAdminActivities("Coupon Deleted Successfully", $coupon);
            return $this->successResponse(null, 'Coupon deleted successfully');
        } else {
            return $this->errorResponse('Coupon not found');
        }
    }

}