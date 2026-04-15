<?php

namespace App\Http\Controllers\FrontControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use App\Models\Customer;
use App\Models\CarHost;
use Carbon\Carbon;

class LoginController extends Controller
{
    public function Login()
    {
        return view('front.auth_pages.login');
    }

    public function LoginPost(Request $request)
    {
        $phone = isset($request->phone)?$request->phone:'';
        return view('front.auth_pages.verify_otp', compact('phone'));
    }

    public function verifyLoginOtp(Request $request){
        $enteredOtp = $request->otp_input1.$request->otp_input2.$request->otp_input3.$request->otp_input4;
        $country_code = '+91';
        $mobile_number = $request->mobile_no;
        $otp = $enteredOtp;
        $data = [
            'country_code' => $country_code,
            'mobile_number' => $mobile_number,
            'otp' => $otp,
        ];
        $url = config('global_values.api_url').'verify-otp';
        $client = new \GuzzleHttp\Client();
        $response = $client->request('POST', $url, [
            'form_params' => $data
        ]);
        $returnResponse = $response->getBody()->getContents();
        $returnResponse = json_decode($returnResponse);
        $message = $returnResponse->message;
        if($returnResponse->status == 'success'){
            session()->put('loginUser', $returnResponse->data->user);
            session()->put('userToken', $returnResponse->data->authorisation->token);

            return redirect()->route('front.confirm-details')->with(['success' => "Your Mobile # is verified Successfully"]);    
        }else{
            return redirect()->back()->with('error', $message);    
        }
    }

    public function getConfirmDetails(Request $request)
    {
        return view('front.auth_pages.confirm-details');
    }

    public function storeConfirmDetails(Request $request){
        $url = config('global_values.api_url').'update-profile';
        $token = '';
        if(session()->has('userToken')){
            $token = session()->get('userToken');    
        }
        $data = [
            'firstname' => $request->first_name,
            'lastname' => $request->last_name,
            'email' => $request->email,
            'dob' => $request->dob,
        ];

        $client = new \GuzzleHttp\Client();
        $response = $client->post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ],
            'form_params' => $data,
        ]);
        $returnResponse = $response->getBody()->getContents();
        $returnResponse = json_decode($returnResponse);
        $message = $returnResponse->message;

        if($returnResponse->status == 'success'){
            session()->put('loginUser', $returnResponse->data->user);
            return redirect()->route('front.confirm-details')->with(['success' => $message]);    
        }else{
            return redirect()->back()->with('error', $message);    
        }
    }

    public function verifyCustomerEmail(Request $request, $customer_id, $email, $app){
        $customerId = Crypt::decrypt($customer_id);
        $emailId = Crypt::decrypt($email);
        $app = Crypt::decrypt($app);
        if(isset($app) && $app == 'v_host'){
            $customer = CarHost::where(['id' => $customerId, 'email' => $emailId])->first();
        }else{
            $customer = Customer::where(['customer_id' => $customerId, 'email' => $emailId])->first();
        }
        
        if($customer != '' && $customer->email_verified_at == null){
            $customer->email_verified_at = date('Y-m-d H:i:s');
            $customer->save();
            return redirect()->route('front.verify-email-success', 'success');   
        }else{
            return redirect()->route('front.verify-email-success', 'fail');
        }
    }

    public function verifyEmailSuccess($status){
        $message = '';
        if($status == 'success'){
            $message = "Your Email is Verified Successfully....";
        }else{
            $message = "Link is Expire OR Your Email is Already Verified OR Something went wrong";
        }

        return view('verify-email-success', compact('message'));
    }

}
