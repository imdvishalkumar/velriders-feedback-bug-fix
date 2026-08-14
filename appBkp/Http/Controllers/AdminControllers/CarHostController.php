<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{CarHost, CarHostBank};
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class CarHostController extends Controller
{
    public function getCarHostList(Request $request){
        hasPermission('car-host-management');
        return view('admin.carhost.index');
    }

    public function getAllCarHost(Request $request){
        hasPermission('car-host-management');
        $carHosts = CarHost::select('id', 'country_code', 'mobile_number', 'email', 'firstname', 'lastname', 'pan_number', 'dob', 'profile_picture_url', 'created_at')->where('is_deleted', 0)->get();
        
        return $carHosts;
    }

    public function getCarHostCreate(Request $request){
        hasPermission('car-host-management');
        return view("admin.carhost.create");
    }

    public function storeCarHost(Request $request){
        hasPermission('car-host-management');
        $validator = Validator::make($request->all(), [
            'firstname' => 'required',
            'lastname' => 'required',
            'email' => 'email:rfc,dns|max:255',
            'dob' => 'required|date|before:'.Carbon::now()->setTimezone('Asia/Kolkata')->toDateTimeString(),
            'mobile_number' => ['numeric', 'digits_between:8,15', Rule::unique('car_hosts', 'mobile_number')->where(function ($query) {
                $query->where('is_deleted', 0);
            })],
            'profile_pic' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:4096',
            'account_holder_name' => 'nullable|regex:/^[a-zA-Z\s\.\-\']+$/',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        $carHost = new CarHost();
        $carHost->firstname = $request->firstname ?? '';
        $carHost->lastname = $request->lastname ?? '';
        $carHost->email = $request->email ?? '';
        $carHost->dob = $request->dob ? date('Y-m-d', strtotime($request->dob)) : '';
        $carHost->mobile_number = $request->mobile_number ?? '';
        $carHost->pan_number = $request->pan_number ?? '';
        $carHost->gst_number = $request->gst_number ?? '';
        $carHost->business_name = $request->business_name ?? '';
        $carHost->save();

        $carHostBank = new CarHostBank();
        $carHostBank->car_hosts_id = $carHost->id;    
        $carHostBank->account_holder_name = $request->account_holder_name;
        $carHostBank->bank_name = $request->bank_name;
        $carHostBank->branch_name = $request->branch_name;
        $carHostBank->city = $request->city;
        $carHostBank->account_no = $request->account_number;
        $carHostBank->ifsc_code = $request->ifsc_code;
        $carHostBank->nick_name = isset($request->nick_name)?$request->nick_name:NULL;
        $carHostBank->is_primary = 1;
        $carHostBank->save();

        if ($request->hasFile('profile_pic')) {
            $file = $request->file('profile_pic');
            $filename = 'Carhost_userprofile_'.$carHost->id.'_'.time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('images/profile_pictures'), $filename);
            $carHost->profile_picture_url = $filename;
            $carHost->save();
        }

        return redirect()->route('admin.carhost-mgt');
    }

    public function getCarHostEdit(Request $request, $hostId)
    {
        hasPermission('car-host-management');
        $carHost = CarHost::select('id', 'country_code', 'mobile_number', 'email', 'firstname', 'lastname', 'pan_number', 'dob', 'profile_picture_url', 'created_at', 'gst_number', 'business_name')->where('id', $hostId)->first();

        $carHostBank = CarHostBank::where('car_hosts_id', $hostId)->where('is_primary', 1)->first();
        return view("admin.carhost.edit", compact('carHost', 'carHostBank'));
    }

    public function getCarHostUpdate(Request $request){
        hasPermission('car-host-management');
        $carHost = CarHost::where('id', $request->carHostId)->first();
        $validator = Validator::make($request->all(), [
            'firstname' => 'required',
            'lastname' => 'required',
            'dob' => 'required|date|before:'.Carbon::now()->setTimezone('Asia/Kolkata')->toDateTimeString(),
            'email' => ['email:rfc,dns', 'max:255', Rule::unique('car_hosts', 'email')->ignore($request->carHostId, 'id')->where(function ($query) {
                $query->where('is_deleted', 0);
            })],
            'mobile_number' => ['numeric', 'digits_between:8,15', Rule::unique('car_hosts', 'mobile_number')->ignore($request->carHostId, 'id')->where(function ($query) {
                $query->where('is_deleted', 0);
            })],
            'profile_pic' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4096',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        
        $carHost->firstname = $request->firstname ?? '';
        $carHost->lastname = $request->lastname ?? '';
        $carHost->email = $request->email ?? '';
        $carHost->dob = $request->dob ? date('Y-m-d', strtotime($request->dob)) : '';
        $carHost->mobile_number = $request->mobile_number ?? '';
        $carHost->pan_number = $request->pan_number ?? '';
        $carHost->gst_number = $request->gst_number ?? '';
        $carHost->business_name = $request->business_name ?? '';
        $carHost->save();
        if ($request->hasFile('profile_pic')) {
            $file = $request->file('profile_pic');
            $filename = 'Carhost_userprofile_'.$carHost->id.'_'.time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('images/profile_pictures'), $filename);
            $carHost->profile_picture_url = $filename;
            $carHost->save();
        }

        return redirect()->route('admin.carhost-mgt');
    }
}
