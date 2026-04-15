<?php

namespace App\Http\Controllers\FrontAppApis\V1;

use App\Http\Controllers\Controller; 
use App\Models\{Customer, CustomerDocument, LoginToken, AppStatus, UserDevice, RentalBooking, NotificationLog, AdminPenalty, BookingTransaction, CustomerReferralDetails, Setting, ContactUs, Policy, City, CustomerDeviceToken};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use App\Services\SmsService;
use DB;
use Laravel\Socialite\Facades\Socialite;
use Exception;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Crypt;
use JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use GuzzleHttp\Client;
use Razorpay\Api\Api;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Http;

class CustomerController extends Controller
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->middleware('auth:api')->except(['appStatus', 'settings', 'sendOTP', 'verifyOTPAndGenerateToken', 'unauthenticated', 'staticMessages', 'storeGuestUser', 'getGuestNotifications', 'testApis', 'getAllPolicies', 'storeContactDetails', 'storeUserSubscribe', 'getCityDetails']);
        $this->smsService = $smsService;
    }

    public function sendOTP(Request $request)
    {
        $otpVia = config('global_values.otp_via');
        $otpVia = implode(',', $otpVia);
        // Validate the incoming request & return validation errors if validation fails
        $validator = Validator::make($request->all(), [
            'otp_via' => 'required|in:'.$otpVia,
            'country_code' => ['nullable', 'string', 'regex:/^\+[1-9]\d{1,3}$/', 'required_if:otp_via,sms'],
            //'mobile_number' => ['required', 'string', 'regex:/^\+?[1-9]\d{9,14}$/'],
            'mobile_number' => [
                'nullable',
                'string',
                'regex:/^\+?[1-9]\d{9,14}$/',
                'required_if:otp_via,sms',
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
                },
                'required_if:otp_via,email'
            ],
            'referral_code' => ['nullable','max:50', Rule::exists('customers', 'my_referral_code')->where(function ($query) { $query->where('is_deleted', 0);})]
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $registerVia = NULL;

        if(config('global_values.environment') == 'live'){
            if ($request->otp_via == 'email' && $request->email != '') {
                $checkUser = Customer::where('email', $request->email)->first();
                if($checkUser == ''){
                    $registerVia = 2;
                }
                $user = Customer::firstOrCreate(['email' => $request->email]);
                if($user != '' && $registerVia != NULL){
                    $user->registered_via = $registerVia;
                }
                $otp = $this->generateAndSendEmailOTP($request->email);
                if ($otp === null) { 
                    return $this->errorResponse('OTP already sent within 1 Minute.');
                }
                if ($otp && isset($otp['status']) && $otp['status'] == false) {
                    $errorMessage = $otp['message'] ?? 'Something went Wrong';
                    return $this->errorResponse($errorMessage);
                } else{
                    $user->save();
                    if(isset($request->referral_code) && $request->referral_code != ''){
                        // CHECK IF ENTERED REFERRAL CODE IS VALID OR NOT AND IS NOT USER'S OWN REFERRAL CODE
                        $checkReferralCode = Customer::where('my_referral_code', $request->referral_code)->where('customer_id', '!=', $user->customer_id)->where('is_deleted', 0)->first();
                        // CHECK IF USER HAS ALREADY USED ANY REFERRAL CODE ON HIS/HER DELETED ACOOUNT OR NOT
                        $checkUsedReferralCode = Customer::where('mobile_number', $user->mobile_number)->where('used_referral_code', '!=', '')->get();
                        if($checkReferralCode == ''){
                            return $this->errorResponse('Referral Code is Invalid');
                        }
                        if(count($checkUsedReferralCode) > 0){
                            return $this->errorResponse('You have already used any referral code on your deleted account');    
                        }
                    }
                    return $this->successResponse(['otp' => '', 'reuse_with_old_email_message' => "<span style='color: blue;'>If you wish to reuse the app, please log in with an old email, otherwise, create a new account</span>"], 'OTP sent for login.');    
                }
            } else if ($request->otp_via == 'sms' && $request->mobile_number != '') {
                $checkUser = Customer::where('mobile_number', $request->mobile_number)->first();
                if($checkUser == ''){
                    $registerVia = 1;
                }
                $user = Customer::firstOrCreate(['mobile_number' => $request->mobile_number , 'country_code' => $request->country_code]);
                if($user != '' && $registerVia != NULL){
                    $user->registered_via = $registerVia;
                }
                $otp = $this->generateAndSendOTP($request->mobile_number);
                if ($otp === null) { 
                    return $this->errorResponse('OTP already sent within 1 Minute.');
                }
                if ($otp && isset($otp['status']) && $otp['status'] !== 200) {
                    $errorMessage = $otp['message'] ?? 'Something went Wrong';
                    return $this->errorResponse($errorMessage);
                } else{
                    $user->save();
                    if(isset($request->referral_code) && $request->referral_code != ''){
                        // CHECK IF ENTERED REFERRAL CODE IS VALID OR NOT AND IS NOT USER'S OWN REFERRAL CODE
                        $checkReferralCode = Customer::where('my_referral_code', $request->referral_code)->where('customer_id', '!=', $user->customer_id)->where('is_deleted', 0)->first();
                        // CHECK IF USER HAS ALREADY USED ANY REFERRAL CODE ON HIS/HER DELETED ACOOUNT OR NOT
                        $checkUsedReferralCode = Customer::where('mobile_number', $user->mobile_number)->where('used_referral_code', '!=', '')->get();
                        if($checkReferralCode == ''){
                            return $this->errorResponse('Referral Code is Invalid');
                        }
                        if(count($checkUsedReferralCode) > 0){
                            return $this->errorResponse('You have already used any referral code on your deleted account');    
                        }
                    }
                    return $this->successResponse(['otp' => '', 'reuse_with_old_number_message' => "<span style='color: blue;'>If you wish to reuse the app, please log in with an old phone number, otherwise, create a new account</span>"], 'OTP sent for login.');    
                }
            }
        }else{
            $otp = '0000';
            if ($request->otp_via == 'email') {
                $checkUser = Customer::where('email', $request->email)->first();
                if($checkUser == ''){
                    $registerVia = 2;
                }
                $user = Customer::firstOrCreate(['email' => $request->email]);
                if($user != '' && $registerVia != NULL){
                    $user->registered_via = $registerVia;
                }
                if(isset($request->referral_code) && $request->referral_code != ''){
                    // CHECK IF ENTERED REFERRAL CODE IS VALID OR NOT AND IS NOT USER'S OWN REFERRAL CODE
                    $checkReferralCode = Customer::where('my_referral_code', $request->referral_code)->where('customer_id', '!=', $user->customer_id)->where('is_deleted', 0)->first();
                    // CHECK IF USER HAS ALREADY USED ANY REFERRAL CODE ON HIS/HER DELETED ACOOUNT OR NOT
                    $checkUsedReferralCode = Customer::where('mobile_number', $user->mobile_number)->where('used_referral_code', '!=', '')->get();
                    if($checkReferralCode == ''){
                        return $this->errorResponse('Referral Code is Invalid');
                    }
                    if(count($checkUsedReferralCode) > 0){
                        return $this->errorResponse('You have already used any referral code on your deleted account');    
                    }
                }
                Cache::put('otp_' . $request->email, strval($otp), 60 * 5);
                Cache::put('last_otp_sent_' . $request->email, now(), 30);

                return $this->successResponse(['otp' => $otp, 'reuse_with_old_email_message' => "<span style='color: blue;'>If you wish to reuse the app, please log in with an old email, otherwise, create a new account</span>"], 'OTP sent for login.');    
            } elseif($request->otp_via == 'sms'){
                $checkUser = Customer::where('mobile_number', $request->mobile_number)->first();
                if($checkUser == ''){
                    $registerVia = 1;
                }
                $user = Customer::firstOrCreate(['mobile_number' => $request->mobile_number , 'country_code' => $request->country_code]);
                if($user != '' && $registerVia != NULL){
                    $user->registered_via = $registerVia;
                }
                $user->save();
                if(isset($request->referral_code) && $request->referral_code != ''){
                    // CHECK IF ENTERED REFERRAL CODE IS VALID OR NOT AND IS NOT USER'S OWN REFERRAL CODE
                    $checkReferralCode = Customer::where('my_referral_code', $request->referral_code)->where('customer_id', '!=', $user->customer_id)->where('is_deleted', 0)->first();
                    // CHECK IF USER HAS ALREADY USED ANY REFERRAL CODE ON HIS/HER DELETED ACOOUNT OR NOT
                    $checkUsedReferralCode = Customer::where('mobile_number', $user->mobile_number)->where('used_referral_code', '!=', '')->get();
                    if($checkReferralCode == ''){
                        return $this->errorResponse('Referral Code is Invalid');
                    }
                    if(count($checkUsedReferralCode) > 0){
                        return $this->errorResponse('You have already used any referral code on your deleted account');    
                    }
                }
                Cache::put('otp_' . $request->mobile_number, strval($otp), 60 * 5);
                Cache::put('last_otp_sent_' . $request->mobile_number, now(), 30);

                return $this->successResponse(['otp' => $otp, 'reuse_with_old_number_message' => "<span style='color: blue;'>If you wish to reuse the app, please log in with an old phone number, otherwise, create a new account</span>"], 'OTP sent for login.');
            }
        }
    }

    public function verifyOTPAndGenerateToken(Request $request)
    {
        $otpVia = config('global_values.otp_via');
        $otpVia = implode(',', $otpVia);
        // Validate the incoming request & return validation errors if validation fails
        $validator = Validator::make($request->all(), [
            'otp_via' => 'required|in:'.$otpVia,
            //'mobile_number' => ['required', 'string', 'regex:/^\+?[1-9]\d{9,14}$/'],
            'country_code' => ['nullable', 'string', 'regex:/^\+[1-9]\d{1,3}$/', 'required_if:otp_via,sms'],
            'mobile_number' => [
                'nullable',
                'string',
                'regex:/^\+?[1-9]\d{9,14}$/',
                'required_if:otp_via,sms',
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
                },
                'required_if:otp_via,email' // Email is required if otp_via is 'email'
            ],
            'otp' => 'required|string',
            'device_info' => 'required|json',
            //'referral_code' => 'nullable|max:50|exists:customers,my_referral_code',
            'referral_code' => ['nullable','max:50', Rule::exists('customers', 'my_referral_code')->where(function ($query) { $query->where('is_deleted', 0);}),
            ],

        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        if($request->otp_via == 'email'){
            $otp = Cache::get('otp_' . $request->email);
            if (!$otp || $otp !== $request->otp) {
                return $this->errorResponse('Invalid OTP');
            }
            $user = Customer::where('email', $request->email)->latest()->first();
            if (!$user) {
                return $this->errorResponse('Customer not found');
            }
            $user->email_verified_at = date('Y-m-d H:i:s');
        }else if($request->otp_via == 'sms'){
            $otp = Cache::get('otp_' . $request->mobile_number);
            if (!$otp || $otp !== $request->otp) {
                return $this->errorResponse('Invalid OTP');
            }
            $user = Customer::where('mobile_number', $request->mobile_number)->latest()->first();
            if (!$user) {
                return $this->errorResponse('Customer not found');
            }
            $user->mobileno_verified_at = date('Y-m-d H:i:s');
        }
        $user->save();
        $customerDeviveToken = CustomerDeviceToken::where('customer_id', $user->customer_id)->where(['device_token' => $request->firebase_token, 'is_error' => 0])->first();
        if($customerDeviveToken == ''){
            $customerDeviveToken = new CustomerDeviceToken();
            $customerDeviveToken->customer_id = $user->customer_id;
        }
        $customerDeviveToken->device_token = $request->firebase_token;
        $customerDeviveToken->is_deleted = 0;
        $customerDeviveToken->save();

        // SUBSCRIBE TOKEN TO TOPIC
        $tokens = [$request->firebase_token];
        $tokenSubscription = subscribeToTopic($tokens, "all_users");
        if(isset($tokenSubscription['results'][0]['error']) && $tokenSubscription['results'][0]['error'] != ''){}
        else{
            $customerDeviveToken->is_subscribed = 1;
            $customerDeviveToken->save();
        }
        // $user->device_token = $request->firebase_token; //$request['firebase_token']; // Replace with the correct device token
        // $user->save();
        
        $token = Auth::guard('api')->login($user);

        $loginToken = new LoginToken();
        $loginToken->app = 1;
        $loginToken->customer_id = $user->customer_id;
        $loginToken->token = $token;
        $loginToken->save();

        if(isset($request->device_info) && $request->device_info != ''){
            $userDevice = new UserDevice();
            $userDevice->app = 1; 
            $userDevice->customer_id = $user->customer_id;
            $userDevice->device_info = json_encode($request->device_info);
            $userDevice->save();
        }

        if(config('global_values.environment') != '' && config('global_values.environment') == 'live' && $user->email_verified_at == null){
            //Send Mail to Customer
            $user->email_verified_at = null;
            $user->save();

            $to = isset($request->email)?$request->email:'';
            $subject = "Email Verification";
            $from = config('global_values.mail_from');
            $customerId = Crypt::encrypt($user->customer_id);
            $name = $user->firstname ?? '';
            $name .= ' '.$user->lastname ?? '';
            $email = Crypt::encrypt($to);
            $app = Crypt::encrypt('v_main');
            if(isset($to) && $to != ''){
                try{
                    // Send Verification mail to Customer
                    Mail::send('emails.front.email_verification', ['customer_id' => $customerId, 'name' => $name, 'email' => $email, 'app' => $app], function ($m) use ($subject, $to, $from) {
                        $m->from($from)->to($to)->subject($subject);
                    });
                } catch (\Exception $e) {} 
            }
            
        }

        // REFERRAL CODE
        if($user->my_referral_code == '' || $user->my_referral_code == null){
            $customerName = trim($user->firstname);
            $customerName = strtoupper(substr($customerName, 0, 7));
            $userReferralCode = generateReferralCode($user->customer_id, $customerName);
            $user->my_referral_code = $userReferralCode;
            $user->save();
        }
        if($user->my_referral_code != $request->referral_code && $request->referral_code != NULL){
            if($user->used_referral_code == '' || $user->used_referral_code == NULL || !isset($user->used_referral_code)){
                // CHECK IF ENTERED REFERRAL CODE IS VALID OR NOT AND IS NOT USER'S OWN REFERRAL CODE
                $checkReferralCode = Customer::where('my_referral_code', $request->referral_code)->where('customer_id', '!=', $user->customer_id)->where('is_deleted', 0)->first();
                // CHECK IF USER HAS ALREADY USED ANY REFERRAL CODE ON HIS/HER DELETED ACOOUNT OR NOT
                $checkUsedReferralCode = Customer::where('mobile_number', $user->mobile_number)->where('used_referral_code', '!=', '')->get();
                if($checkReferralCode){
                    if(count($checkUsedReferralCode) == 0){
                        $user->used_referral_code = $request->referral_code ?? '';
                        $user->save();
                        if($user->used_referral_code != ''){
                            $setting = Setting::select('reward_type', 'reward_val')->first();
                            $checkCustomerReferral = CustomerReferralDetails::where(['customer_id' => $user->customer_id, 'used_referral_code' => $user->used_referral_code])->exists();
                            if($checkCustomerReferral){
                                return $this->errorResponse('Referral Code already added');
                            }
                            $customerReferralDetails = new CustomerReferralDetails();
                            $customerReferralDetails->customer_id = $user->customer_id;
                            $customerReferralDetails->used_referral_code = trim($user->used_referral_code);
                            if($setting != ''){
                                $customerReferralDetails->reward_type = $setting->reward_type ?? '';
                                $customerReferralDetails->reward_amount_or_percent = $setting->reward_val ?? '';
                                // if($customerReferralDetails->reward_type == 1){ // 1 means Fixed 2 means percent
                                //     $customerReferralDetails->payable_Amount = $setting->reward_val;
                                // }
                            }
                            $customerReferralDetails->save();
                        }
                    }else{
                        return $this->errorResponse('You have already used any referral code on your deleted account');    
                    }
                } else {
                    return $this->errorResponse('Referral Code is Invalid');
                }
            }
        } else if($request->referral_code != NULL && $user->my_referral_code == $request->referral_code) {
            return $this->errorResponse('You cannot use your own referral code');
        }

        $user->delete_account_message = "<span style='color: red;'>THIS ACTION CANNOT BE UNDONE. This will permanently delete your account and all of its data.</span>";
        $user->email_verification_message = "<span style='color: blue;'>An email will be sent to verify your email!</span>";

        return $this->successResponse([
            'user' => $user,
            'authorisation' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ]);
    }

    public function verifyOldNumberAndOTPAndGenerateToken(Request $request)
    {
        $otpVia = config('global_values.otp_via');
        $otpVia = implode(',', $otpVia);
        $validator = Validator::make($request->all(), [
            'otp_via' => 'required|in:'.$otpVia,
            // 'mobile_number' => ['required', 'string', 'regex:/^\+?[1-9]\d{9,14}$/'],
            // 'country_code' => ['required', 'string', 'regex:/^\+[1-9]\d{1,3}$/'],
            'country_code' => ['nullable', 'string', 'regex:/^\+[1-9]\d{1,3}$/', 'required_if:otp_via,sms'],
            'mobile_number' => [
                'nullable',
                'string',
                'regex:/^\+?[1-9]\d{9,14}$/',
                'required_if:otp_via,sms',
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
                },
                'required_if:otp_via,email' // Email is required if otp_via is 'email'
            ],
            'otp' => 'required|string',
            'login_with_old_account' => 'required', // Add validation for login_with_old_acc
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        
        $user = '';
        if($request->otp_via == 'sms'){
            $otp = Cache::get('otp_' . $request->mobile_number);
            if (!$otp || $otp !== $request->otp) {
                return $this->errorResponse('Invalid OTP');
            }
            $user = Customer::where('mobile_number', $request->mobile_number)->orderBy('customer_id' , 'desc')->first();
        } else if($request->otp_via == 'email'){
            $otp = Cache::get('otp_' . $request->email);
            if (!$otp || $otp !== $request->otp) {
                return $this->errorResponse('Invalid OTP');
            }
            $user = Customer::where('email', $request->email)->orderBy('customer_id' , 'desc')->first();
        }

        if ($request->login_with_old_account && $request->login_with_old_account == 1 && $user != '') {
            // If user exists, update is_deleted field to 0 if login_with_old_acc is true
            $user->is_deleted = 0;
            //$user->device_token = $request->firebase_token;
            $user->save();

            $customerDeviveToken = CustomerDeviceToken::where('customer_id', $user->customer_id)->where(['device_token' => $request->firebase_token, 'is_error' => 0])->first();
            if($customerDeviveToken == ''){
                $customerDeviveToken = new CustomerDeviceToken();
                $customerDeviveToken->customer_id = $user->customer_id;
            }
            $customerDeviveToken->is_deleted = 0;
            $customerDeviveToken->device_token = $request->firebase_token;
            $customerDeviveToken->save();
            
            // SUBSCRIBE TOKEN TO TOPIC
            $tokens = [$request->firebase_token];
            $tokenSubscription = subscribeToTopic($tokens, "all_users");
            if(isset($tokenSubscription['results'][0]['error']) && $tokenSubscription['results'][0]['error'] != ''){}
            else{
                $customerDeviveToken->is_subscribed = 1;
                $customerDeviveToken->save();
            }

            // Login to the old account
            $token = Auth::guard('api')->login($user);
            $loginToken = new LoginToken();
            $loginToken->app = 1;
            $loginToken->customer_id = $user->customer_id;
            $loginToken->token = $token;
            $loginToken->save();
            return $this->successResponse([
                'user' => $user,
                'authorisation' => [
                    'token' => $token,
                    'type' => 'bearer',
                ]
            ], 'You are logged in to your existing account');
        } else {
            // If user does not exist, create a new account
            $user = new Customer();
            //$user->device_token = $request->firebase_token;
            $user->mobile_number = $request->mobile_number;
            $user->email = $request->email ?? NULL;
            $user->country_code = $request->country_code;
            $user->save();

            $customerDeviveToken = CustomerDeviceToken::where('customer_id', $user->customer_id)->where(['device_token' => $request->firebase_token, 'is_error' => 0])->first();
            if($customerDeviveToken == ''){
                $customerDeviveToken = new CustomerDeviceToken();
                $customerDeviveToken->customer_id = $user->customer_id;
            }
            $customerDeviveToken->is_deleted = 0;
            $customerDeviveToken->device_token = $request->firebase_token;
            $customerDeviveToken->save();

            // SUBSCRIBE TOKEN TO TOPIC
            $tokens = [$request->firebase_token];
            $tokenSubscription = subscribeToTopic($tokens, "all_users");
            if(isset($tokenSubscription['results'][0]['error']) && $tokenSubscription['results'][0]['error'] != ''){}
            else{
                $customerDeviveToken->is_subscribed = 1;
                $customerDeviveToken->save();
            }
    
            $token = Auth::guard('api')->login($user);
            $loginToken = new LoginToken();
            $loginToken->app = 1;
            $loginToken->customer_id = $user->customer_id;
            $loginToken->token = $token;
            $loginToken->save();

            // REFERRAL CODE GENERATION FOR NEW CREATED USER
            if($user->my_referral_code == '' || $user->my_referral_code == null) {
                $customerName = trim($user->firstname);
                $customerName = strtoupper(substr($customerName, 0, 7));
                $userReferralCode = generateReferralCode($user->customer_id, $customerName);
                $user->my_referral_code = $userReferralCode;
                $user->save();
            }

            return $this->successResponse([
                'user' => $user,
                'authorisation' => [
                    'token' => $token,
                    'type' => 'bearer',
                ]
            ], 'A new account has been created for you');
        }
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
        //$otp = strval(mt_rand(1000, 9999));
        if(isset($mobileNumber) && $mobileNumber == '9999999999'){
            $otp = "0000";
        }else{
            $otp = strval(mt_rand(1000, 9999));
            $checkresponse =  $this->smsService->sendOTP($mobileNumber,$otp);
            // Check the response status and handle errors
            if($checkresponse && isset($checkresponse['status']) && $checkresponse['status'] != 200){
                $checkResponse['message'] = $checkResponse['message'] ?? 'An error occurred while sending OTP.';
                return $checkresponse; 
            }
        }
       
        // Cache the OTP and timestamp
        Cache::put('otp_' . $mobileNumber, strval($otp), 60 * 5);
        // Store the timestamp of the OTP sent
        Cache::put('last_otp_sent_' . $mobileNumber, now(), 30);

        return $otp; 
    }

    private function generateAndSendEmailOTP($email)
    {
        $lastOTPSentTime = Cache::get('last_otp_sent_' . $email);
        if ($lastOTPSentTime && now()->diffInSeconds($lastOTPSentTime) < 30) {
            return;
        }
        // Generate OTP
        $otp = strval(mt_rand(1000, 9999));
        $to = $email;
        $subject = "OTP for Email Verification";
        $from = config('global_values.mail_from');
        if(isset($to) && $to != ''){
            try{
                Mail::send('emails.email_otp', ['otp' => $otp], function ($m) use ($subject, $to, $from) {
                    $m->from($from)->to($to)->subject($subject);
                });
            } catch (\Exception $e) {} 
        }else{
            $checkResponse['status'] = false;
            $checkResponse['message'] = 'Email Not Found';
            return $checkresponse; 
        }

        // Cache the OTP and timestamp
        Cache::put('otp_' . $email, strval($otp), 60 * 5);
        // Store the timestamp of the OTP sent
        Cache::put('last_otp_sent_' . $email, now(), 30);

        return $otp; 
    }

    public function getProfile(Request $request)
    {
        $user = Auth::guard('api')->user();
        $user = Customer::where('customer_id', $user->customer_id)
                ->where('is_deleted', 0)
                ->orderBy('customer_id', 'desc')
                ->first();
        $amountPayable = 0;
        $emailVerificationStatus = $payStatus = $completionFound = false;
        $emailVerificationTitle = "<span style='color: blue;'>Email Verification Warning</span>";
        $emailVerificationMessage = "<span style='color: blue;'>Please verify your email!</span>";
        $paymentTitle = "";
        $paymentWarning = "";

        if($user){
            $userBookings = RentalBooking::where('customer_id', $user->customer_id)
                ->whereIn('status', ['running', 'completed'])
                ->get();
            
            $amountPayable = 0;
            if ($userBookings->isNotEmpty()) {
                foreach($userBookings as $booking) {
                    // Query for the 'completion' type transactions that are not paid
                    $unpaidCompletionTransaction = BookingTransaction::where('booking_id', $booking->booking_id)
                        ->where('type', 'completion')
                        ->where('paid', false)
                        ->first();
                    if($unpaidCompletionTransaction != ''){
                        $amountToPay = $unpaidCompletionTransaction->amount_to_pay ?? 0;
                        if ($amountToPay > 0) {
                            $amountPayable += $amountToPay;
                        }
                    }
                }
                foreach($userBookings as $booking) {
                    $unpaidCompletionTransactions = BookingTransaction::where('booking_id', $booking->booking_id)
                            ->where('type', 'completion')
                            ->where('paid', false)
                            ->first();
                    if($unpaidCompletionTransactions != ''){
                        $amountToPay = $transaction->amount_to_pay ?? 0;
                        if ($amountToPay > 0) {
                            $completionFound = true;
                            break;
                        }
                    }
                }
                foreach ($userBookings as $key => $booking) {
                    $adminPenalty = AdminPenalty::where('booking_id', $booking->booking_id)
                        ->where('amount', '!=', 0)
                        ->where('is_paid', 0)
                        ->exists();
                    if ($adminPenalty) {
                        $payStatus = true;
                        break;
                    }
                }
            }
        }

        if($completionFound == true && $payStatus == false){
            $payStatus = true;
        }     
        if($payStatus == true){
            $paymentTitle = "<span style='color: blue;'>Payment pending title</span>";
            $paymentWarning = "<span style='color: blue;'>Payment pending message!</span>";
        }   
        if($user){
            if($user->email_verified_at != null){
                $emailVerificationStatus = true;
                $emailVerificationMessage = "";
                $emailVerificationTitle = "";
            }
            $user->email_verified = $emailVerificationStatus;
            $user->warning_title = $emailVerificationTitle;
            $user->warning_message = $emailVerificationMessage;
            $user->is_payment_pending = $payStatus;
            $user->payment_pending_title = $paymentTitle;
            $user->payment_pending_message = $paymentWarning;
            $user->email_verification_message = "<span style='color: blue;'>An email will be sent to verify your email!</span>";
            $user->delete_account_message = "<span style='color: red;'>THIS ACTION CANNOT BE UNDONE. This will permanently delete your account and all of its data.</span>";
            $user->payable_amount_info = "Your remianing amount to pay is - Rs.".$amountPayable;
            $setting = Setting::select('reward_type', 'reward_val', 'reward_html')->first();

            $checkBooking = RentalBooking::where('customer_id', $user->customer_id)->whereNotIn('status', ['pending', 'no show', 'canceled', 'failed'])->exists();
            $user->has_booking = $checkBooking;

            if($setting != ''){
                $user->reward_type = $setting->reward_type ?? '';
                $user->reward_val = $setting->reward_val ?? '';
                if($user->reward_type == 1){
                    $user->reward_display_val = '₹ '.$setting->reward_val ?? '';
                }
                elseif($user->reward_type == 2){
                    $user->reward_display_val = $setting->reward_val.' %' ?? '';
                }
                $user->reward_html = stripslashes($setting->reward_html) ?? '';
            }

            return $this->successResponse(['user' => $user]);
        }else{
            return $this->errorResponse("User not found");
        }
    }

    public function updateMobileNumber(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile_number' => ['required', 'string', 'regex:/^\+?[1-9]\d{9,14}$/'],
            'country_code' => ['required', 'string', 'regex:/^\+[1-9]\d{1,3}$/'],
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $checkexistMobile = Customer::select('customer_id')->where('mobile_number', $request->mobile_number)->where('is_deleted', 0)->first();
        if ($checkexistMobile != null) {
            return $this->errorResponse('Mobile number already exist.');
        } else {
            $otp = $this->generateAndSendOTP($request->mobile_number);
            if ($otp === null) {
                return $this->errorResponse('OTP already sent within 1 Minute.');
            }    

            if($otp && isset($otp['status']) && $otp['status'] != 200){
                $errorMessage = $otp['message'] ?? 'Something went Wrong';
                return $this->errorResponse($errorMessage);
            }else{
                return $this->successResponse(['otp' => null], 'OTP sent for mobile number update.');
            }
        }
    }
    
    public function updateMobileNumberVerifyOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile_number' => ['required', 'string', 'regex:/^\+?[1-9]\d{9,14}$/'],
            'country_code' => ['required', 'string', 'regex:/^\+[1-9]\d{1,3}$/'],
            'otp' => 'required|string',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        // Retrieve OTP from cache & Verify
        $otp = Cache::get('otp_' . $request->mobile_number);
        if (!$otp || $otp !== $request->otp) {
            return $this->errorResponse('Invalid OTP');
        }

        // Update the mobile number
        $user = Auth::guard('api')->user();
        $user = Customer::select('customer_id', 'mobile_number')->where('customer_id', $user->customer_id)->first();
        $user->mobile_number = $request->mobile_number;
        $user->mobileno_verified_at = date('Y-m-d H:i:s');
        $user->save();

        // Clear OTP from cache after verification
        Cache::forget('otp_' . $request->mobile_number);

        return $this->successResponse(null, 'Mobile number updated successfully');
    }

    public function updateEmailAddress(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => [
                'required',
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
                },
            ],
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $user = Customer::select('customer_id')->where('email', $request->email)->first();
        if ($user != null) {
            return $this->errorResponse('Email already exist.');
        } else {
            // if(config('global_values.environment') != '' && config('global_values.environment') == 'live'){
            //     //Send Mail to Customer
            //     $user = Auth::guard('api')->user(); 
            //     $user->email_verified_at = null;
            //     $user->email = $request->email;
            //     $user->save();
    
            //     $to = isset($request->email)?$request->email:'';
            //     $subject = "Email Verification";
            //     $from = config('global_values.mail_from');
            //     $customerId = Crypt::encrypt($user->customer_id);
            //     $name = $user->firstname ?? '';
            //     $name .= ' '.$user->lastname ?? '';
            //     $email = Crypt::encrypt($to);
            //     $app = Crypt::encrypt('v_main');
            //     if(isset($to) && $to != ''){
            //        //try{
            //             // Send Verification mail to Customer
            //             Mail::send('emails.front.verify_email', ['customer_id' => $customerId, 'name' => $name, 'email' => $email, 'app' => $app], function ($m) use ($subject, $to, $from) {
            //                 $m->from($from)->to($to)->subject($subject);
            //             });
            //         //} catch (\Exception $e) {} 
            //     }
            // }

            // return $this->successResponse($user, 'Email Sent Successfully');

            $otp = $this->generateAndSendEmailOTP($request->email);
            if ($otp === null) {
                return $this->errorResponse('OTP already sent within 1 Minute.');
            }    
            if($otp && isset($otp['status']) && $otp['status'] != 200){
                $errorMessage = $otp['message'] ?? 'Something went Wrong';
                return $this->errorResponse($errorMessage);
            }else{
                return $this->successResponse(['otp' => null], 'OTP sent for Email update.');
            }
        }
    }

    public function updateEmailAddressVerifyOTP(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => [
                'required',
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
                },
            ],
            'otp' => 'required|string',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        // // Retrieve OTP from cache & Verify
        $otp = Cache::get('otp_' . $request->email);
        if (!$otp || $otp !== $request->otp) {
            return $this->errorResponse('Invalid OTP');
        }

        // Update the Email Address
        $user = Auth::guard('api')->user();
        $user = Customer::select('customer_id', 'email', 'firstname', 'lastname')->where('customer_id', $user->customer_id)->first();
        $user->email = $request->email;
        $user->email_verified_at = date('Y-m-d H:i:s');
        $user->save();

        // SEND AGREEMENT TO THE CUSTOMER IF HE/SHE NOT RECEIVED WHILE BOOKING CONFIRM DUE TO EMAIL IS NOT VERIFIED
        // if(config('global_values.environment') != '' && config('global_values.environment') == 'live'){
        //     $getLatestBooking = RentalBooking::where('customer_id', $user->customer_id)->orderBy('booking_id', 'desc')->first();
        //     if($getLatestBooking && $getLatestBooking->is_aggrement_send == 0){
        //         try{
        //             generateCustomerPdf($user->customer_id, $getLatestBooking->booking_id);
        //         }catch(Exception $e){}
        //         $fileName = 'customer_agreements_'.$user->customer_id.'_'.$getLatestBooking->booking_id.'.pdf';
        //         $filePath = public_path().'/customer_aggrements/'.$fileName;
        //         $attachments = [];
        //         if(file_exists($filePath)){ 
        //             $attachments[] = $filePath;
        //         }
        //         $attach = $attachments;
        //         $to = $user->email;
        //         $from = config('global_values.mail_from');
        //         $userName = $user->firstname.' '.$user->firstname;
        //         $templateFile = 'emails.front.customer_agreement';
        //         Mail::send($templateFile, ['to' => $to, 'name' => $userName], function ($m) use ($from, $to, $attach) {
        //             $m->from($from);
        //             $m->to($to)->subject("Velrider Customer Agreement");
        //             if (count($attach) > 0) {
        //                 foreach ($attach as $attachment) {
        //                     $m->attach($attachment);
        //                 }
        //             }
        //         });
        //         if(file_exists($filePath)){ 
        //             unlink($filePath);
        //         }
        //         $getLatestBooking->is_aggrement_send = 1;
        //         $getLatestBooking->save();
        //     }
        // }

        $checkBooking = RentalBooking::where('customer_id', $user->customer_id)->whereNotIn('status', ['pending', 'no show', 'canceled', 'failed'])->exists();
        $user->has_booking = $checkBooking;
        $dlStatus = $govtStatus = false;
        if(strtolower($user->documents['dl']) == 'approved'){
            $dlStatus = true;
        }
        if(strtolower($user->documents['govtid']) == 'approved'){
            $govtStatus = true;
        }
        $user->dl_status = $dlStatus;
        $user->govt_status = $govtStatus;

        // Clear OTP from cache after verification
        Cache::forget('otp_' . $request->email);
        return $this->successResponse($user, 'Email updated successfully');
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::guard('api')->user();  
        $mailStatus = false;
        $customerId = $user->customer_id;
        $validationRules = [
            'firstname' => 'string|max:255',
            'lastname' => 'string|max:255',
            //'email' => 'email:rfc,dns|max:255|unique:customers,email,' . $customerId . ',id,is_deleted,0',
            // 'email' => [
            //     'email:rfc,dns',
            //     'max:255',
            //     Rule::unique('customers', 'email')
            //     ->where(function ($query) {
            //         $query->where('is_deleted', 0);
            //     })
            //     ->ignore($customerId, 'customer_id'),
            // ],
            'dob' => 'date',
            //'mobile_number' => 'numeric|digits_between:8,15|unique:customers,mobile_number,' . $customerId . ',customer_id,is_deleted,0',
            'mobile_number' => [
                'numeric',
                'digits_between:8,15',
                Rule::unique('customers', 'mobile_number')
                ->where(function ($query) {
                    $query->where('is_deleted', 0);
                })
                ->ignore($customerId, 'customer_id'),
            ],
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4096',
            'billing_address' => 'nullable|string|max:255',
            'shipping_address' => 'nullable|string|max:255',
            'business_name' => 'nullable|string|max:255',
            'gst_number' => 'nullable|string|max:255',
            //'referral_code' => 'nullable|max:50|exists:customers,my_referral_code',
            //'is_email_updated' => 'required',
        ];
        $validator = Validator::make($request->all(), $validationRules);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $user = Customer::where('customer_id', $user->customer_id)
                ->where('is_deleted', 0)
                ->orderBy('customer_id', 'desc')
                ->first();
        if (!$user) {
            return $this->errorResponse('Customer not found');
        }

        // if($user->my_referral_code == '' || $user->my_referral_code == null){
        //     $customerName = trim($user->firstname);
        //     $customerName = strtoupper(substr($customerName, 0, 7));
        //     $userReferralCode = generateReferralCode($user->customer_id, $customerName);
        //     $user->my_referral_code = $userReferralCode;
        //     $user->save();
        // }
        // if($user->my_referral_code != $request->referral_code && $request->referral_code != NULL){
        //     if($user->used_referral_code == '' || $user->used_referral_code == NULL || !isset($user->used_referral_code)){
        //         $checkReferralCode = Customer::where('my_referral_code', $request->referral_code)->where('customer_id', '!=', $user->customer_id)->where('is_deleted', 0)->first();
        //         $checkUsedReferralCode = Customer::where('mobile_number', $user->mobile_number)->where('used_referral_code', '!=', NULL)->get();
        //         if($checkReferralCode != '' && count($checkUsedReferralCode) == 0){
        //             $user->used_referral_code = $request->referral_code ?? '';
        //             $user->save();
        //             if($user->used_referral_code != ''){
        //                 $setting = Setting::select('reward_type', 'reward_val')->first();
        //                 $customerReferralDetails = new CustomerReferralDetails();
        //                 $customerReferralDetails->customer_id = $user->customer_id;
        //                 $customerReferralDetails->used_referral_code = trim($user->used_referral_code);
        //                 if($setting != ''){
        //                     $customerReferralDetails->reward_type = $setting->reward_type ?? '';
        //                     $customerReferralDetails->reward_amount_or_percent = $setting->reward_val ?? '';
        //                     // if($customerReferralDetails->reward_type == 1){ // 1 means Fixed 2 means percent
        //                     //     $customerReferralDetails->payable_Amount = $setting->reward_val;
        //                     // }
        //                 }
        //                 $customerReferralDetails->save();
        //             }
        //         } else {
        //             return $this->errorResponse('Referral Code is Invalid');
        //         }
        //     }
        // }

        if(($request->firstname != '' && $user->firstname != $request->firstname) || ($request->lastname != '' && $user->lastname != $request->lastname)){
            if($user->documents && strtolower($user->documents['dl']) == 'approved' && strtolower($user->documents['govtid']) == 'approved'){
                return $this->errorResponse('You can not update your First name & Last name as your Documents are approved');
            }
        }

        if (isset($request->email) && $request->email != '' && $request->email != $user->email) {
            $mailStatus = true;
        }
        $user->fill($request->except('profile_picture', 'dob'));
        if ($request->hasFile('profile_picture')) {
            $file = $request->file('profile_picture');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('images/profile_pictures'), $filename);
            $user->profile_picture_url = $filename;
        }
        if(isset($request->dob) && $request->dob != ''){
            $dob = date('Y-m-d', strtotime($request->dob));
            $user->dob = $dob;
        }
        $user->save();
      
        if(config('global_values.environment') != '' && config('global_values.environment') == 'live' && $mailStatus == true && $request->is_email_updated == true){
            //Send Mail to Customer
            $user->email_verified_at = null;
            $user->save();

            $to = isset($request->email)?$request->email:'';
            $subject = "Email Verification";
            $from = config('global_values.mail_from');
            $customerId = Crypt::encrypt($user->customer_id);
            $name = $user->firstname ?? '';
            $name .= ' '.$user->lastname ?? '';
            $email = Crypt::encrypt($to);
            $app = Crypt::encrypt('v_main');
            if(isset($to) && $to != ''){
               try{
                    // Send Verification mail to Customer
                    Mail::send('emails.front.email_verification', ['customer_id' => $customerId, 'name' => $name, 'email' => $email, 'app' => $app], function ($m) use ($subject, $to, $from) {
                        $m->from($from)->to($to)->subject($subject);
                    });
                } catch (\Exception $e) {} 
            }
            
        }
            
        $user->delete_account_message = "<span style='color: red;'>THIS ACTION CANNOT BE UNDONE. This will permanently delete your account and all of its data.</span>";
        $user->email_verification_message = "<span style='color: blue;'>An email will be sent to verify your email!</span>";
            
        return $this->successResponse(['user' => $user], 'Profile updated successfully');
    }

    public function logout(Request $request)
    {
        // $validator = Validator::make($request->all(), [
        //     'device_token' => 'required',
        // ]);
        // if ($validator->fails()) {
        //     return $this->validationErrorResponse($validator);
        // }
        $user = Auth::guard('api')->user();
        $customerDeviveToken = CustomerDeviceToken::where('customer_id', $user->customer_id)->where('device_token', $request->device_token)->first();
        if($customerDeviveToken != ''){
            $customerDeviveToken->is_deleted = 1;
            $customerDeviveToken->save();
        }
        // $user->device_token = '';
        // $user->save();

        // UNSUBSCRIBE TOKEN FROM THE TOPIC
        $tokens = [$request->device_token];
        $tokenUnSubscription = unsubscribeToTopic($tokens, "all_users");
        if(isset($tokenUnSubscription['results'][0]['error']) && $tokenUnSubscription['results'][0]['error'] != ''){}
        else{
            if($customerDeviveToken != ''){
                $customerDeviveToken->is_subscribed = 0;
                $customerDeviveToken->save();
            }
        }
        Auth::guard('api')->logout();
        return $this->successResponse(null, 'Successfully logged out');
    }

    public function unauthenticated()
    {
        return $this->errorResponse('unauthenticated');
    }

    public function appStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            //'os_type' => 'required|in:1,2', 
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
      
        $appStatus = AppStatus::select('version', 'maintenance', 'alert_title', 'alert_message');
        if(strtolower($request->os_type) == 'android')
            $appStatus = $appStatus->where('os_type', 1);
        elseif(strtolower($request->os_type) == 'ios')
            $appStatus = $appStatus->where('os_type', 2);
        $appStatus = $appStatus->first();
        
        return $this->successResponse($appStatus);

        /*return $this->successResponse([
            'version' => "1.0.1",
            'maintenance' => false,
            'alert_title' => 'Good to go',
            'alert_message' => 'Good to go',
        ]);*/
    }

    public function settings()
    {
        $statuses = config('global_values.booking_statuses');
        
        // Add 'All' status at the beginning
        // array_unshift($statuses, ['id' => 'all', 'status' => 'All']);

        return $this->successResponse([
            'help_support' => "+919909927077",
            'booking_help_support' => "+919909927077",
            'terms_condition' => "https://velriders.com/terms-condition",
            'privacy_policy' => 'https://velriders.com/privacy-policy',
            'refund_policy' => 'https://velriders.com/refund-policy',
            'pricing_policy' => 'https://velriders.com/pricing-policy',
            'document_type' => config('global_values.govid_types'),
            'booking_statuses' => $statuses,
        ]);
    }

    public function deleteAccount(Request $request)
    {
        $user = Auth::guard('api')->user();
        $user = Customer::where('customer_id', $user->customer_id)
                ->where('is_deleted', 0)
                ->orderBy('customer_id', 'desc')
                ->first();
        if($user != ''){  
            $user->is_deleted = 1;
            //$user->device_token = '';
            $user->save();
            $customerDeviveToken = CustomerDeviceToken::where('customer_id', $user->customer_id)->get();
            if(is_countable($customerDeviveToken) && count($customerDeviveToken) > 0){
                foreach($customerDeviveToken as $key => $val){
                    $val->is_deleted = 1;
                    $val->save();

                    // UNSUBSCRIBE TOKEN FROM THE TOPIC
                    $tokens = [$val->device_token];
                    $tokenUnSubscription = unsubscribeToTopic($tokens, "all_users");
                    if(isset($tokenUnSubscription['results'][0]['error']) && $tokenUnSubscription['results'][0]['error'] != ''){}
                    else{
                        $val->is_subscribed = 0;
                        $val->save();
                    }
                }
            }
        
            $loginToken = LoginToken::where('customer_id', $user->customer_id)->where('app', 1)->get();
            if(is_countable($loginToken) && count($loginToken) > 0){
                foreach($loginToken as $key => $val){
                    if(isset($val->token) && $val->token != ''){
                        try {
                            JWTAuth::setToken($val->token)->invalidate();
                            $val->delete();
                        } catch (TokenExpiredException $e) {
                            // Token has already expired, handle accordingly
                            \Log::info("Token already expired for customer_id: {$user->customer_id}, token: {$val->token}");
                        } catch (JWTException $e) {
                            // Handle other JWT exceptions
                            \Log::error("JWT Exception for customer_id: {$user->customer_id}, error: " . $e->getMessage());
                        } catch (Exception $e) {
                            // Handle other potential exceptions
                            \Log::error("An error occurred while invalidating the token: " . $e->getMessage());
                        }
                    }
                }
            }
            
            Auth::guard('api')->logout(); // Log out the user after deleting the account
            return $this->successResponse(null, 'Account Deleted Successfully.');

        }else{
            return $this->errorResponse('Something went Wrong');
        }
    }

    public function refresh()
    {
        $user = Auth::guard('api')->user();
        $token = Auth::guard('api')->refresh();
        return $this->successResponse([
            'user' => $user,
            'authorisation' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ]);
    }
    
    public function storeGuestUser(Request $request){
        $validator = Validator::make($request->all(), [
            'device_token' => ['required'],
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        // $customer = Customer::where(['device_token' => $request->device_token, 'is_guest_user' => 1])->first();
        // if($customer == ''){
        //     $customer = new Customer();
        // }
        // $customer->device_token = $request->device_token;
        // $customer->is_guest_user = 1;
        // $customer->save();

        $customerDeviveToken = CustomerDeviceToken::where('device_token', $request->device_token)->latest('updated_at')->first();
        if($customerDeviveToken == ''){
            $customer = new Customer();
            $customer->is_guest_user = 1;
            $customer->save();

            $customerDeviveToken = new CustomerDeviceToken();
            $customerDeviveToken->customer_id = $customer->customer_id;
            $customerDeviveToken->device_token = $request->device_token;
            $customerDeviveToken->save();
        }else{
            $customer = Customer::where('customer_id', $customerDeviveToken->customer_id)->where('is_deleted', 0)->latest('updated_at')->first();
            if($customer == ''){
                $customer = new Customer();
            }
            $customer->is_guest_user = 1;
            $customer->save();
        }

        return $this->successResponse(null, 'Guest user details are stored successfully');
    }

    public function getGuestNotifications(Request $request){
        $validator = Validator::make($request->all(), [
            'device_token' => ['required'],
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $deviceToken = $request->device_token ?? '';
        // $notificationLog = NotificationLog::select('id', 'customer_id', 'message_text', 'event_type', 'created_at')->where(['type' => 2, 'status' => 1, 'is_show' => 1])
        //     ->whereHas('customer', function ($query) use ($deviceToken){
        //         $query->whereHas('userLocationDetails', function($q) use ($deviceToken){
        //             $q->where('device_token', $deviceToken);
        //         });
        //     });
        $notificationLog = NotificationLog::select('id', 'customer_id', 'message_text', 'event_type', 'created_at')->where(['type' => 2, 'status' => 1, 'is_show' => 1])
                            ->whereHas('customer', function ($query) use ($deviceToken) {
                                $query->where('is_guest_user', 1);
                                $query->whereHas('customerDeviceToken', function ($subQuery) use ($deviceToken) {
                                    $subQuery->where('device_token', $deviceToken);
                                });
                            });

        $page = $request->input('page');
        $pageSize = $request->input('page_size');
        if ($page !== null && $pageSize !== null) {
            $notificationLog = $notificationLog->paginate($pageSize, ['*'], 'page', $page);
        }else{
            $notificationLog = $notificationLog->get();
        }

        if(is_countable($notificationLog) && count($notificationLog) > 0 ){
            foreach ($notificationLog as $key => $value) {
                if($value->event_type == 'new_booking')
                    $value->color_code = '#b6c3e9';
                elseif($value->event_type == 'extension')
                    $value->color_code = '#b6e9b8';
                elseif($value->event_type == 'completion')
                    $value->color_code = '#b0d3b5';
                $value->event_type = ucwords(str_replace('_', ' ', $value->event_type));
            }
            if ($page !== null && $pageSize !== null) {
                $notificationLogArr = json_decode(json_encode($notificationLog->getCollection()->values()), FALSE);
                return $this->successResponse($notificationLogArr, 'Notifications are get successfully.');
            }else{
                //return $this->successResponse($notificationLog,'Notifications are get successfully.');
                $notificationLogArr = json_decode(json_encode($notificationLog->values()), FALSE);
                return $this->successResponse($notificationLogArr,'Notifications are get successfully.');
            }
        }else{
            return $this->errorResponse('Notifications are not Found');
        }
       
    }

    public function testApis(Request $request){
        //$tokenSubscription = subscribeToTopic($tokens, "all_users");

        // $tokenArr = $tokenIds = $errorTokenArr = $errorTokenIds = [];
        // $customerDeviveToken = CustomerDeviceToken::where(['is_deleted' => 0, 'is_subscribed' => 0, 'is_error' => 0])->get();
        // if(is_countable($customerDeviveToken) && count($customerDeviveToken) > 0){
        //     foreach($customerDeviveToken as $key => $val){
        //         $tokens = [$val->device_token];                
        //         $tokenSubscription = subscribeToTopic($tokens, "all_users");
        //         if(isset($tokenSubscription['results'][0]['error']) && $tokenSubscription['results'][0]['error'] != ''){
        //             $errorTokenArr[] = $val->device_token;
        //             $errorTokenIds[] = $val->id;
        //             $val->is_error = 1;
        //             $val->error_log = json_encode($tokenSubscription['results'][0]['error']);
        //             $val->save();
        //         }
        //         else{
        //             $tokenArr[] = $val->device_token;
        //             $tokenIds[] = $val->id;
        //             $val->is_subscribed = 1;
        //             $val->save();
        //         }
        //     }
        //     \Log::info("errorTokenArr - " . json_encode($errorTokenArr));
        //     \Log::info("errorTokenIds - " . json_encode($errorTokenIds));
        //     \Log::info("tokenArr - " . json_encode($tokenArr));
        //     \Log::info("tokenIds - " . json_encode($tokenIds));
        //     print_r($tokenIds); die;
        // }
        // die;

        // SUBSCRIBE ALL EXISTING DEVICE TOKENS TO TOPIC
        // $tokenArr = $tokenIds = [];
        // $customerDeviveToken = CustomerDeviceToken::where(['is_deleted' => 0, 'is_subscribed' => 0, 'is_error' => 0])->get();
        // if(is_countable($customerDeviveToken) && count($customerDeviveToken) > 0){
        //     foreach($customerDeviveToken as $key => $val){
        //         $tokens = [$val->device_token];
        //         $tokenSubscription = subscribeToTopic($tokens, "all_users");
        //         if(isset($tokenSubscription['results'][0]['error']) && $tokenSubscription['results'][0]['error'] != ''){}
        //         else{
        //             $tokenArr[] = $val->device_token;
        //             $tokenIds[] = $val->id;
        //             $val->is_subscribed = 1;
        //             $val->save();
        //         }
        //     }
        //     print_r($tokenIds); die;
        // }
        // die;

        // UPDATE DL NAME TO CUSTOMER TABLE
        // $cD = CustomerDocument::where('cashfree_api_response', '!=', '')->where('document_type', 'dl')->get();
        // foreach($cD as $k => $v){
        //     $cashfreeRes = json_decode($v->cashfree_api_response);
        //     if(isset($cashfreeRes->details_of_driving_licence) && $cashfreeRes->details_of_driving_licence != ''){
        //         $name = explode(' ', $cashfreeRes->details_of_driving_licence->name);
        //         if(count($name) > 0){
        //             $customer = Customer::where('customer_id', $v->customer_id)->first();
        //             \Log::info('CUSTOMER - '. json_encode($customer));
        //             $firstName = $lastName =  '';
        //             if($customer != ''){
        //                 $lastName = array_shift($name);
        //                 $firstName = implode(' ', $name);
        //                 if($firstName != '' && $lastName != '' && strlen($firstName) > 1 && strlen($lastName) > 1){
        //                     $customer->firstname = ucwords(strtolower(trim($firstName)));
        //                     $customer->lastname = ucwords(strtolower(trim($lastName)));
        //                     $customer->save();
        //                 }
        //             }
        //         }
        //     }
        // }

        // NEW CODE TO COMPARE AADHAR AND DL NAME
        // $aadharName = strtolower($request->aadharName);
        // $dlName = strtolower($request->dlName);
        // // Clean and process both names
        // $parts1 = cleanNameParts($aadharName);
        // $parts2 = cleanNameParts($dlName);
        // if (count($parts1) < 2 || count($parts2) < 2) {
        //     return 0; // Ensure both names have at least first and last name
        // }
        // // Extract first and last names
        // $firstName1 = $parts1[0];
        // $lastName1 = end($parts1);
        // $firstName2 = $parts2[0];
        // $lastName2 = end($parts2);
        // // Check if names match directly OR if first & last name are swapped, allowing small typos
        // if((isSimilar($firstName1, $firstName2) && isSimilar($lastName1, $lastName2)) || (isSimilar($firstName1, $lastName2) && isSimilar($lastName1, $firstName2))){
        //     return 1;
        // }
        // return 0;

        // MULTIPLE NOTIFICATION USING TOKEN 
        // SUBSCRIBE TOKEN TO TOPIC
        // $tokens = ["eVo56cgwSJC43PkvwpJjbf:APA91bHUhCwfpanJqTDMB-s5Ex7diks3icwlIcmSgzsutXrGfODcd8-0uQl6boM1LSWUJ2FDj4vSYqktXRHPrrTDnKWg5tbOKsGfjjjh20GET8gNmPhaxeY"];
        // $subscribeToTopic($tokens, "all_users");

        // SEND NOTIFICATIONS ON TOPIC
        // $title = 'TEST TiTlE';
        // $content = 'TEST CoNtEnT';
        // $sendNotificationUsingTopic($title, $content);

        // GET DYNAMIC ACCESS TOKEN
        // $accessToken = $accessToken = getDynamicAccessToken();
        // print_r($accessToken); die;

        // UNSUBSCRIBE TOKEN FROM THE TOPIC
        // $tokens = ["eVo56cgwSJC43PkvwpJjbf:APA91bHUhCwfpanJqTDMB-s5Ex7diks3icwlIcmSgzsutXrGfODcd8-0uQl6boM1LSWUJ2FDj4vSYqktXRHPrrTDnKWg5tbOKsGfjjjh20GET8gNmPhaxeY"];
        // $unsubscribeToTopic($tokens, "all_users");
    }

    public function getAllPolicies(Request $request){
        $policies = Policy::get();
        if(is_countable($policies) && count($policies) > 0){
            foreach($policies as $key => $val){
                return $this->successResponse($policies, 'Policies are get successfully.');
            }
        } else {
            return $this->errorResponse('Policies are not Found');
        }   
    }

    public function storeContactDetails(Request $request){
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email',
            'mobile_no' => 'required|numeric|digits_between:8,15',
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
                'unique:contact_us',
                function ($attribute, $value, $fail) {
                    // Split the email into local part (before @) and domain part (after @)
                    [$localPart, $domain] = explode('@', $value);
                    // Allow only a-z, 0-9, ., _, - in the local part
                    if (!preg_match('/^[a-z0-9._-]+$/', $localPart)) {
                        $fail('The :attribute can only contain lowercase letters, numbers, dots (.), underscores (_), and dashes (-).');
                    }
                    // Ensure the local part contains at least one letter (a-z)
                    if (!preg_match('/[a-z]/', $localPart)) {
                        $fail('The :attribute must contain at least one letter.');
                    }
                },
            ],
            'message_text' => 'required|max:300',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $contact = ContactUs::create($request->all());
        $env = config('global_values.environment');
        if($env != '' && $env == 'live'){
            if($contact){
                //Send mail to admin
                $to = config('global_values.mail_to');
                $from = config('global_values.mail_from');
                $data = [
                    'first_name' => $contact->first_name,
                    'last_name' => $contact->last_name,
                    'email' => $contact->email,
                    'mobile_no' => $contact->mobile_no,
                    'message_text' => isset($contact->message_text)?$contact->message_text:'',
                ];
                try{
                    Mail::send('emails.front.contact-us', $data, function ($message) use ($to, $from) {
                        $message->from($from, 'Velriders');
                        $message->subject("You have received New Contact Inquiry");
                        $message->to($to);
                    });
                }catch(\Exception $e){}
            }
            return $this->successResponse($contact, 'Contact Data saved Successfully');
        }else{
            return $this->errorResponse('You can not send mail on Staging Env.');
        }
    }

    public function storeUserSubscribe(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
                function ($attribute, $value, $fail) {
                    // Split the email into local part (before @) and domain part (after @)
                    [$localPart, $domain] = explode('@', $value);
                    // Allow only a-z, 0-9, ., _, - in the local part
                    if (!preg_match('/^[a-z0-9._-]+$/', $localPart)) {
                        $fail('The :attribute can only contain lowercase letters, numbers, dots (.), underscores (_), and dashes (-).');
                    }
                    // Ensure the local part contains at least one letter (a-z)
                    if (!preg_match('/[a-z]/', $localPart)) {
                        $fail('The :attribute must contain at least one letter.');
                    }
                },
            ]
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $to = config('global_values.mail_to');
        $from = config('global_values.mail_from');
        $data = [
            'email' => $request->email,
        ];
        try{
            Mail::send('emails.front.user-subscribed', $data, function ($message) use ($to, $from) {
                $message->from($from, 'Velriders'); 
                $message->subject("New user has Subscribed");
                $message->to($to);
            });
        }catch(\Exception $e){}

        return $this->successResponse('You have subscribed Successfully');
    }

    public function getCityDetails(Request $request){
        $validator = Validator::make($request->all(), [
            'city_id' => 'required|exists:cities,id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $city = City::where(['is_deleted' => 0, 'id' => $request->city_id])->with(['branch' => function ($query) {
            $query->select('branch_id', 'city_id' ,'name', 'address', 'phone', 'email', 'is_head_branch')->where('is_deleted', 0)->where('is_head_branch', 1);
        }])->first();
        
        if($city != ''){
            return $this->successResponse($city, 'Data get Successfully');
        }else{
            return $this->errorResponse('Data not Found');
        }
    }

}
