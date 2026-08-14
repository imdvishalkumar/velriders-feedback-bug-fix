<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{AppStatus, Setting, LoginToken, AdminActivityLog, OfferDate, Vehicle};
use App\Models\Coupon;
use Carbon\Carbon;

class SettingController extends Controller
{
    public function getSettings(Request $request){
        
        hasPermission('setting');
        $setting = Setting::first();

        $currentTime = Carbon::now();
        $checkSetting = Setting::whereTime('payment_gateway_alter_start_time', '<=', $currentTime)
            ->where('payment_gateway_alter_end_time', '>=', $currentTime)
            ->get();
        $offerDates = OfferDate::get();
        $vehicles = Vehicle::where(['availability' => 1, 'is_deleted' => 0])->get();
        return view('admin.settings', compact('setting', 'checkSetting', 'offerDates', 'vehicles'));
    }

    public function storeAppDetails(Request $request){
        $request->validate([
            'version' => 'required'
        ]);
        
        $appStatus = AppStatus::where('id', $request->aId)->first();    
        $oldVal = clone $appStatus;

        if(isset($request->version) && $appStatus != '' && ($appStatus->version != $request->version)){
            $loginToken = LoginToken::get();
            if(is_countable($loginToken) && count($loginToken) > 0){
                foreach ($loginToken as $key => $value) {
                    $value->delete();
                }
            }
        }
        
        //$appStatus->os_type = $request->input('os_type');
        $appStatus->version = $request->input('version') != '' ? $request->input('version') : NULL;
        $appStatus->maintenance = $request->input('maintenance') != '' ? $request->input('maintenance') : 0;
        $appStatus->alert_title = $request->input('alert_title') != '' ? $request->input('alert_title') : NULL;
        $appStatus->alert_message = $request->input('alert_message') != '' ? $request->input('alert_message') : NULL;
        $appStatus->save();

        $newVal = $appStatus;
        $differences = compareArray($oldVal, $newVal);
        if(isset($differences) && is_countable($differences) && count($differences) > 0){
            logAdminActivity('App Status Updation', $oldVal, $newVal);
        }

        return redirect()->route('admin.settings')->with('success', 'App Status Set successfully!');
    }

    public function storePaymentDetails(Request $request)
    {
        $setting = Setting::first();
        if($setting == '') {
            $setting = new Setting();
        }
        
        $setting->payment_gateway_alter_start_time = $request->startTime ?? NULL;
        $setting->payment_gateway_alter_end_time = $request->endTime ?? NULL;
        if($request->paymentGateway != ''){
            $setting->payment_gateway_type = $request->paymentGateway;
        }

        $setting->save();

        return response()->json("Payment Details are stored successfully");
    }

    public function getAppDetails()
    {
        $appStatusDetails = AppStatus::get();
        return response()->json([
            'status' => true,
            'data' => $appStatusDetails,
        ], 200);
    }

    public function getAppDetail(Request $request)
    {
        $appDetail = AppStatus::find($request->id);

        return response()->json([
            'data' => $appDetail,
            'status' => true,
        ]);
    }

    public function setShowAllFlag(Request $request){
        $setting = Setting::first();
        if($setting == ''){
            $setting = new Setting();
        }
        $message = '';
        if($request->checkStatus == 1){
            $setting->show_all_vehicle = 1;    
            $message = 'Show all Flag On Successfully';
        }else{
            $setting->show_all_vehicle = 0;
            $message = 'Show all Flag Off Successfully';
        }
        $setting->save();

        return response()->json($message);
    }

    public function setBookingGap(Request $request){
        $data['message'] = 'Something went Wrong';
        $data['status'] = false;
        if($request->bookingGapVal != '' && $request->bookingGapVal != 0){
            $setting = Setting::first();
            if($setting == ''){
                $setting = new Setting();
            }
            $setting->booking_gap = isset($request->bookingGapVal)?$request->bookingGapVal:NULL;    
            $setting->save();
            $data['message'] = 'Booking Gap minutes set Successfully';
            $data['status'] = true;
        }   
        else{
            $data['message'] = 'Please enter proper value for Booking Gap';
        }

        return response()->json($data);
    }

    public function setVehicleOfferPrice(Request $request){
        $data['message'] = 'Something went Wrong';
        $data['status'] = false;
        if($request->vehicleOfferPrice != '' && $request->offerStartDate != '' && $request->offerEndDate != ''){
            $offerStartDate = Carbon::parse($request->offerStartDate);
            $offerEndDate = Carbon::parse($request->offerEndDate);

            if($offerStartDate >= $offerEndDate){
                $data['message'] = 'Offer End date must be Greater than Offer Start Date';
                $data['status'] = false;
                return response()->json($data);
            }

            $setting = Setting::first();
            if($setting == ''){
                $setting = new Setting();
            }
            /*$setting->vehicle_offer_price = isset($request->vehicleOfferPrice)?$request->vehicleOfferPrice:NULL;    
            $setting->vehicle_offer_start_date = isset($request->offerStartDate)?date('Y-m-d H:i', strtotime($request->offerStartDate)):NULL;    
            $setting->vehicle_offer_end_date = isset($request->offerEndDate)?date('Y-m-d H:i', strtotime($request->offerEndDate)):NULL; */   
            $setting->save();
            $data['message'] = 'Vehicle Offer Details are set Successfully';
            $data['status'] = true;
        }   
        else{
            $data['message'] = 'Vehicle Start Date, Vehicle End Date & Vehicle Offer Price all are required';
        }

        return response()->json($data);
    }

    public function storeReferEarnDetails(Request $request){
        $data['message'] = 'Something went Wrong';
        $data['status'] = false;

        if($request->rewardType != '' && $request->rewardVal != ''){

            $setting = Setting::first();
            if($setting == ''){
                $setting = new Setting();
            }
            $setting->reward_type = $request->rewardType ?? '';    
            $setting->reward_val = $request->rewardVal ?? '';    
            $setting->reward_html = $request->rewardHtml ?? '';
            $setting->reward_max_discount_amount = $request->maxDiscountAmount ?? 0;
            $setting->save();

            $data['message'] = 'Refer & Earn Details are set Successfully';
            $data['status'] = true;
        }   
        else{
            $data['message'] = 'Reward Type & Reward Value are required';
        }

        return response()->json($data);
    }

    public function getActivityLog(Request $request){
       $adminActivityLog = AdminActivityLog::with(['adminDetails' => function($q) {
            $q->select('admin_id', 'username', 'role', 'created_at', 'updated_at');
        }])->orderBy('log_id', 'DESC')->paginate(10);

        return view('admin.activity-log', compact('adminActivityLog'));
    }

    public function getLogDetails(Request $request){
        $displayVal = '';
        if(isset($request->logId)){
            $logDetails = AdminActivityLog::select('log_id', 'old_value', 'new_value')->where('log_id', $request->logId)->first();
            if($logDetails != '' && $request->valType == 'old'){
                $displayVal = $logDetails->old_value ? json_decode($logDetails->old_value) : '';
                return response()->json($displayVal);
            }else if($logDetails != '' && $request->valType == 'new'){
                $displayVal = $logDetails->new_value ? json_decode($logDetails->new_value) : '';
                return response()->json($displayVal);
            }
        }else{
            return response()->json($displayVal);
        }
    }

    public function storeOfferDates(Request $request){
        if (isset($request->vehicle_exist) && is_countable($request->vehicle_exist) && count(array_filter($request->vehicle_exist)) > 0) {
            $offerDetails = OfferDate::truncate();
            foreach ($request->vehicle_exist as $key => $value) {
                $offerDate = new OfferDate();
                $offerDate->vehicle_id = $value;
                $offerDate->vehicle_offer_start_date = $request->offer_start_date_exist[$key] ? date('Y-m-d H:i', strtotime($request->offer_start_date_exist[$key])) : NULL ;
                $offerDate->vehicle_offer_end_date = $request->offer_end_date_exist[$key] ? date('Y-m-d H:i', strtotime($request->offer_end_date_exist[$key])) : NULL;
                $offerDate->vehicle_offer_price = $request->offer_price_exist[$key] ?? NULL;
                $offerDate->save();
            }
        }else{
            $offerDetails = OfferDate::truncate();
        }
        if(isset($request->vehicle) && is_countable($request->vehicle) && count($request->vehicle) > 0){
            foreach ($request->vehicle as $key => $value) {
                $offerDate = new OfferDate();
                $offerDate->vehicle_id = $value;
                $offerDate->vehicle_offer_start_date = $request->offer_start_date[$key] ? date('Y-m-d H:i', strtotime($request->offer_start_date[$key])) : NULL;
                $offerDate->vehicle_offer_end_date = $request->offer_end_date[$key] ? date('Y-m-d H:i', strtotime($request->offer_end_date[$key])) : NULL;
                $offerDate->vehicle_offer_price = $request->offer_price[$key] ?? NULL;
                $offerDate->save();
            }
        }

       return redirect()->back()->with('success', 'Offer Dates set Successfully');
    }

}
