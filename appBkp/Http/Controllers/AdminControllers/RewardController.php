<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{CustomerReferralDetails,Customer};

class RewardController extends Controller
{
    public function rewardList(Request $request){
        hasPermission('reward-list');
        return view('admin.reward-list');
    }

    public function getAllRewards(Request $request){
        hasPermission('reward-list');
        $customerReferralDetails = CustomerReferralDetails::with(['customerDetails' => function($q){
            $q->select('customer_id', 'firstname', 'lastname', 'email', 'mobile_number');
        }])->with(['referredUser' => function($q){
           $q->select('customer_id', 'firstname', 'lastname', 'email', 'mobile_number', 'my_referral_code');
        }])->where('payable_amount', '>', 0)->get();
        
        return response()->json($customerReferralDetails);
    }

    public function storePayStatus(Request $request){
        hasPermission('reward-list');
        $rewardId = $request->rewardId ?? '';
        $data['status'] = false;
        $data['message'] = 'Something went Wrong';
        if($rewardId != ''){
            $rewardDetails = CustomerReferralDetails::where('id', $rewardId)->first(); 
            if($rewardDetails != ''){
                $rewardDetails->is_paid = 1;
                $rewardDetails->save();
                $data['status'] = true;
                $data['message'] = 'Payment Status updated Successfully';
            }else{
                $data['message'] = 'Rewars details are not found';
            }
        }else{
            $data['message'] = 'Rewars details are not found';
        }

        return response()->json($data);
    }

    public function getBankDetails(Request $request){
        hasPermission('reward-list');
        $referralCode = $request->referralCode ?? '';
        $data['status'] = false;
        $data['message'] = 'Something went Wrong';
        $data['details'] = '';
        if($referralCode != ''){
            $customer = Customer::where('my_referral_code', $referralCode)->first();
            if($customer != ''){
                $data['details'] = $customer;
                $data['status'] = true;
            }else{
                $data['message'] = 'Bank details are not found';
            }
        }else{
            $data['message'] = 'Bank details are not found';
        }

        return response()->json($data);
    }

}
