<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Coupon;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CouponController extends Controller
{
    public function getAllCouponList(){
        hasPermission('coupon-codes');
        return view('admin.coupon.coupons');
    }

    public function getAllCoupons()
    {
        $coupons = Coupon::where('is_deleted', 0)->get()->map(function ($coupon){
            $coupon->valid_from_formatted = Carbon::parse($coupon->valid_from)->format('d-m-Y g:i A');
            $coupon->valid_to_formatted = Carbon::parse($coupon->valid_to)->format('d-m-Y g:i A');
            return $coupon;    
        });

        return $coupons;
    }

    public function createCoupon(Request $request)
    {       
        hasPermission('coupon-codes');
        $customer = Customer::all();
        return view('admin.coupon.create' , compact('customer'));
    }

    public function store(Request $request)
    {
        $startDate = isset($request->valid_from) ? date('Y-m-d H:i:s', strtotime($request->valid_from)) :'';
        $endDate = isset($request->valid_to) ? date('Y-m-d H:i:s', strtotime($request->valid_to)) :'';
        $coupon = new Coupon;
        $coupon->code = isset($request->code) ? $request->code :'';
        $coupon->type = isset($request->discount_type) ? $request->discount_type : '';
        $coupon->customer_id = isset($request->customerId) ? $request->customerId : '0';
        $coupon->percentage_discount = isset($request->percentage_discount) ? $request->percentage_discount : '0';
        $coupon->max_discount_amount = isset($request->max_discount_amount) ? $request->max_discount_amount : '0';
        $coupon->fixed_discount_amount = isset($request->fixed_discount_amount) ? $request->fixed_discount_amount : '0';
        $coupon->valid_from = $startDate;
        $coupon->valid_to = $endDate;
        $coupon->is_active = 1;
        $coupon->single_use_per_customer = isset($request->single_use_per_customer)?$request->single_use_per_customer:0;
        $coupon->one_time_use_among_all = isset($request->one_time_use_among_all)?$request->one_time_use_among_all:0;
        $coupon->is_show = 0;
        $coupon->save();
        logAdminActivity("Coupon Code Creation", $coupon);

        return redirect('/admin/coupons')->with('success', 'Coupon Created successfully!');

    }

    public function editCoupon($id)
    {   
        hasPermission('coupon-codes');
        $coupon = Coupon::find($id);
        return view('admin.coupon.edit' , compact('coupon'));
    }

    public function updateCoupon(Request $request, $id) 
    {
        $coupon = Coupon::findOrFail($id);
        $oldVal = clone $coupon;
        $coupon->code = $request->input('code', $coupon->code);
        $coupon->type = $request->input('discount_type', $coupon->type);
        $coupon->is_active = $request->is_active;
        $coupon->percentage_discount = $request->input('percentage_discount', $coupon->percentage_discount);
        $coupon->max_discount_amount = $request->input('max_discount_amount', $coupon->max_discount_amount);
        $coupon->fixed_discount_amount = $request->input('fixed_discount_amount', $coupon->fixed_discount_amount);
        $coupon->valid_from = date('Y-m-d H:i:s', strtotime($request->valid_from));
        $coupon->valid_to = date('Y-m-d H:i:s', strtotime($request->valid_to));
        $coupon->is_active = 1;
        $coupon->single_use_per_customer = isset($request->single_use_per_customer)?$request->single_use_per_customer:0;
        $coupon->one_time_use_among_all = isset($request->one_time_use_among_all)?$request->one_time_use_among_all:0;
        $coupon->save();

        $newVal = $coupon;
        $differences = compareArray($oldVal, $newVal);
        if(isset($differences) && is_countable($differences) && count($differences) > 0){
            logAdminActivity('Coupon Code Updation', $oldVal, $newVal);
        }

        return redirect('/admin/coupons')->with('success', 'Coupon Updated successfully!');
    }

    public function destroyCoupon(Request $request)
    {   
        $coupon = Coupon::where('id', $request->id)->first();
        $coupon->is_deleted = 1;
        $coupon->save();
        logAdminActivity("Coupon Code Deletion", $coupon);

        return response()->json(['message' => 'Coupons Deleted Successfully', 'status' => true]);
    }

    public function toggleCoupon(Request $request)
    {   
        $coupon = Coupon::where('id', $request->id)->first();
        $status = '';
        if($request->checkStatus == 'checked'){
            $coupon->is_active = 1;
            $status = "Coupon Activated";
            $coupon->save();
        }elseif($request->checkStatus == 'unchecked'){
            $coupon->is_active = 0;
            $status = "Coupon In-Activated";
            $coupon->save();
        }
        logAdminActivity($status, $coupon);

        return response()->json(['message' => 'Coupons code status changed Successfully', 'status' => true]);
    }

    public function toggleShowCoupon(Request $request){
        $coupon = Coupon::where('id', $request->id)->first();
        $status = '';
        if($request->checkStatus == 'checked'){
            $coupon->is_show = 1;
            $status = "Coupon Show";
            $coupon->save();
        }elseif($request->checkStatus == 'unchecked'){
            $coupon->is_show = 0;
            $status = "Coupon Not Show";
            $coupon->save();
        }
        logAdminActivity($status, $coupon);

        return response()->json(['message' => 'Coupons code Is Show status changed Successfully', 'status' => true]);
    }

    public function checkCouponCode(Request $request){
        $coupon = Coupon::where('code',  $request->value)->where('is_deleted', 0);
        if (isset($request->id) && $request->id != '') {
           $coupon = $coupon->where('id','!=',$request->id);
        }
        $couponCount = $coupon->where('is_deleted',0)->count();        
        if ($couponCount > 0) {
            return false;
        }
        return true;        
    }

    public function validateToDate(Request $request){
        $validFrom = Carbon::parse($request->validFrom);
        $validTo = Carbon::parse($request->validTo);
        if($validFrom >= $validTo){
           return false;
        }

        return true;
    }
}
