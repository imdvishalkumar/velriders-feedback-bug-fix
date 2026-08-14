<?php

namespace App\Http\Controllers\FrontControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Exception;
use App\Models\Customer;

class SocialLoginController extends Controller
{
    public function signInwithGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /*public function callbackToGoogle()
    {
        try {
            $user = Socialite::driver('google')->user();
            $fullName = explode(" ", $user->name);
            $finduser = Customer::where('gauth_id', $user->id)->first();
            if($finduser){
                session()->put('loginUser', $finduser);
                session()->put('userToken', $returnResponse->data->authorisation->token);

                return redirect()->route('front.confirm-details')->with(['success' => "Your have logged in Successfully"]);    
            }else{
                $newUser = Customer::create([
                    'firstname' => isset($fullName[0])?$fullName[0]:NULL,
                    'lastname' => isset($fullName[1])?$fullName[1]:NULL,
                    'email' => $user->email,
                    'gauth_id'=> $user->id,
                    'gauth_type'=> 'google',
                ]);
                session()->put('loginUser', $finduser);
                session()->put('userToken', $returnResponse->data->authorisation->token);
     
                return redirect()->route('front.confirm-details')->with(['success' => "Your have logged in Successfully"]);
            }
     
        } catch (Exception $e) {}
    }*/
}
