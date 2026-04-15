<?php

namespace App\Http\Controllers\FrontControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Policy;
use App\Models\Customer;
use App\Models\ContactUs;
use App\Models\CarHost;
use App\Services\SmsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;

class CmsController extends Controller
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function home()
    {
        return view('front.cms_pages.home');
    }

    public function aboutUs()
    {
        return view('front.cms_pages.about-us');
    }

    public function contactUs()
    {
        return view('front.cms_pages.contact-us');
    }

    public function storeContactUs(Request $request){
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email',
            'mobile_no' => 'required|numeric|digits:10',
            'message_text' => 'required|max:300',
            ], [
              'first_name.required' => 'Please enter First Name',
              'last_name.required' => 'Please enter Last Name',
              'email.required' => 'Please enter Email',
              'mobile_no.required' => 'Please enter Mobile No.',
              'message_text.required' => 'Please enter Message Text',
          ]);

        // Check validation failure
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator); 
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
            return redirect()->back()->with('success', 'Contact Data saved Successfully');
        }else{
            return redirect()->back()->with('error', 'You can not send mail on Staging Env.');
        }
    }

    public function aboutUsNew()
    {
        return view('front.cms_pages.about-us-new');
    }
    
    public function contactUsNew()
    {
        return view('front.cms_pages.contact-us-new');
    }

    public function termsCondition()
    {
        $termsCondition = $this->getCmsDetail('terms_condition');
        return view('front.cms_pages.terms-condition', compact('termsCondition'));
    }
    
    public function privacyPolicy()
    {
        $privacyPolicy = $this->getCmsDetail('privacy_policy');
        return view('front.cms_pages.privacy-policy', compact('privacyPolicy'));
    }

    public function refundPolicy()
    {
        $refundPolicy = $this->getCmsDetail('refund_policy');
        return view('front.cms_pages.refund-policy', compact('refundPolicy'));
    }

    public function pricingPolicy()
    {
        $pricingPolicy = $this->getCmsDetail('pricing_policy');
        return view('front.cms_pages.pricing-policy', compact('pricingPolicy'));
    }

    public function getCmsDetail($pageType){
        $policyDetails = Policy::select('policy_type', 'title', 'content')->where('policy_type', $pageType)->first();

        return $policyDetails;
    }

    public function deleteAccountThroughWeb(Request $request){

        return view('delete-account-through-web');
    }

    public function sendOtp(Request $request){
        $mobileNo = isset($request->mobileNo)?$request->mobileNo:'';
        $customer = Customer::where('mobile_number', $request->mobileNo)->first();
        $data['status'] = false;
        $data['message'] = 'Something went Wrong';
        if($customer != '' && isset($customer->mobile_number) && $customer->mobile_number != ''){
            if($customer->is_deleted != 1){
                //Send OTP
                $lastOTPSentTime = Cache::get('last_otp_sent_' . $mobileNo);
                if ($lastOTPSentTime && now()->diffInSeconds($lastOTPSentTime) < 30) {
                    $data['message'] = 'OTP already sent within 1 Minute';
                }
                $otp = strval(mt_rand(1000, 9999));
                $env = config('global_values.environment');
                if($env != '' && $env == 'live'){
                    $checkresponse =  $this->smsService->sendOTP($mobileNo,$otp);
                    Cache::put('otp_' . $mobileNo, strval($otp), 60 * 5);
                    // Store the timestamp of the OTP sent
                    Cache::put('last_otp_sent_' . $mobileNo, now(), 30);
                    $data['status'] = true;
                    $data['message'] = 'OTP Send Successfully on your specified Mobile Number';
                }else{
                    $data['message'] = "You can not send OTP on Staging Env.";
                }
            }else{
                 $data['message'] = "Specified Customer already deleted";
            }
        }else{
            $data['message'] = "Mobile No. doesn't exist";
        }

        return response()->json($data);
    }

    public function verifySendOtp(Request $request){
        $data['status'] = false;
        $data['message'] = 'Something went Wrong';
        $otp = Cache::get('otp_' . $request->mobileNo);
        $customer = Customer::where('mobile_number', $request->mobileNo)->first();
        if($customer != ''){
            if (!$otp || $otp !== $request->otp) {
                $data['message'] = 'Invalid OTP';
            }else{
                $data['status'] = true;
                $customer->is_deleted = 1;
                $customer->save();
                $data['message'] = "You have successfully verified your Mobile verification and specified customer is deleted..";
            }    
            
        }else{
            $data['message'] = "Customer doesn't exist";
        }

        return response()->json($data);
    }

    public function deleteHostAccountThroughWeb(Request $request){

        return view('delete-host-account-through-web');
    }

    public function sendHostOtp(Request $request){
        $mobileNo = isset($request->mobileNo)?$request->mobileNo:'';
        $customer = CarHost::where('mobile_number', $request->mobileNo)->first();
        $data['status'] = false;
        $data['message'] = 'Something went Wrong';
        if($customer != '' && isset($customer->mobile_number) && $customer->mobile_number != ''){
            if($customer->is_deleted != 1){
                //Send OTP
                $lastOTPSentTime = Cache::get('last_otp_sent_' . $mobileNo);
                if ($lastOTPSentTime && now()->diffInSeconds($lastOTPSentTime) < 30) {
                    $data['message'] = 'OTP already sent within 1 Minute';
                }
                $otp = strval(mt_rand(1000, 9999));
                $env = config('global_values.environment');
                if($env != '' && $env == 'live'){
                    $checkresponse =  $this->smsService->sendOTP($mobileNo,$otp);
                    Cache::put('otp_' . $mobileNo, strval($otp), 60 * 5);
                    // Store the timestamp of the OTP sent
                    Cache::put('last_otp_sent_' . $mobileNo, now(), 30);
                    $data['status'] = true;
                    $data['message'] = 'OTP Send Successfully on your specified Mobile Number';
                }else{
                    $data['message'] = "You can not send OTP on Staging Env.";
                }
            }else{
                 $data['message'] = "Specified Customer already deleted";
            }
        }else{
            $data['message'] = "Mobile No. doesn't exist";
        }

        return response()->json($data);
    }

    public function verifyHostSendOtp(Request $request){
        $data['status'] = false;
        $data['message'] = 'Something went Wrong';
        $otp = Cache::get('otp_' . $request->mobileNo);
        $customer = CarHost::where('mobile_number', $request->mobileNo)->first();
        if($customer != ''){
            if (!$otp || $otp !== $request->otp) {
                $data['message'] = 'Invalid OTP';
            }else{
                $data['status'] = true;
                $customer->is_deleted = 1;
                $customer->save();
                $data['message'] = "You have successfully verified your Mobile verification and specified Host is deleted..";
            }    
            
        }else{
            $data['message'] = "Host doesn't exist";
        }

        return response()->json($data);
    }
    
    public function subscribeForm(Request $request){
        $validator = Validator::make($request->all(), [
            'subscribe_email' => 'required',
            ], [
              'subscribe_email.required' => 'Please enter First Name',
        ]);
        // Check validation failure
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator); 
        }

        $env = config('global_values.environment');
        if($env != '' && $env == 'live'){
            if(isset($request->subscribe_email) && $request->subscribe_email != ''){
                //Send mail to admin
                $to = config('global_values.mail_to');
                $from = config('global_values.mail_from');
                $data = [
                    'subscribe_email' => $request->subscribe_email,
                ];
                try{
                    Mail::send('emails.front.subscribe-email', $data, function ($message) use ($to, $from) {
                        $message->from($from, 'Velriders');
                        $message->subject("New user has send Subscribe Mail");
                        $message->to($to);
                    });
                }catch(\Exception $e){}
            }
            return redirect()->back()->with('success', 'Your subscribe mail send Successfully');
        }else{
            return redirect()->back()->with('error', 'You can not send mail on Staging Env.');
        }
    }

}
