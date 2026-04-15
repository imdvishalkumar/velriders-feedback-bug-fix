<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\{Coupon, RentalBooking, BookingTransaction};
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CheckCoupon implements ValidationRule
{
    public function passes($attribute, $value)
    {
        // Your validation logic goes here
        $startDate = Carbon::parse(request()->get('start_date'));
        $endDate = Carbon::parse(request()->get('end_date'));
        $sDate = date('Y-m-d H:i:s', strtotime($startDate)); 
        $eDate = date('Y-m-d H:i:s', strtotime($endDate));
        $couponDiscount = 0;
        if ($value != null && $sDate != NULL && $eDate != NULL){
            $coupon = Coupon::where('code', $value)
                ->where('valid_from', '<=', $sDate)->where('valid_to', '>=', $eDate)->where(['is_deleted' => 0, 'is_active' => 1])->first();
            if ($coupon /* && $coupon->is_active && now()->between($coupon->valid_from, $coupon->valid_to)*/) {
                $couponDiscount = 1; //1 means valid
            }
            
            $rentalBooking = [];
            //Below code check Single Use and One Time Use coupon condition
            $singleUse = isset($coupon->single_use_per_customer)?$coupon->single_use_per_customer:0;
            $oneTimeUse = isset($coupon->one_time_use_among_all)?$coupon->one_time_use_among_all:0;
            if($oneTimeUse == 1 || $singleUse == 1){
                $couponCode = $coupon->code ?? '';    
                $couponId = $coupon->id;
                if($oneTimeUse == 1){
                    $rentalBooking = RentalBooking::whereIn('status', ['confirmed', 'running', 'completed'])->get();
                }
                if($singleUse == 1){
                    $cId = '';
                    if(Auth::guard('api')->check()){
                        $cId = Auth::guard('api')->user()->customer_id;
                    }
                    if($cId != ''){
                        $rentalBooking = RentalBooking::whereIn('status', ['confirmed', 'running', 'completed'])->where('customer_id', $cId)->get();
                    }
                }
                if($couponCode != '' && is_countable($rentalBooking) && count($rentalBooking) > 0){
                    foreach ($rentalBooking as $key => $value) {
                        //Check in json
                        /*$calculationDetails = json_decode($value->calculation_details);
                        if(isset($calculationDetails->versions)){
                            foreach ($calculationDetails->versions as &$version) {
                                if ($version->type == 'new_booking') {
                                    if($version->details->coupon_code != '' && $version->details->coupon_code == $couponCode && $version->details->coupon_code_id != '' && $version->details->coupon_code_id == $couponId && $version->details->order->paid == true){
                                        $couponDiscount = 2;
                                        break;
                                    }
                                }
                                if ($version->type == 'extension') {
                                    if($version->details->coupon_code != '' && $version->details->coupon_code == $couponCode && $version->details->coupon_code_id != '' && $version->details->coupon_code_id == $couponId && $version->details->order->paid == true){
                                        $couponDiscount = 2;
                                        break;
                                    }
                                }
                            }
                        }*/
                            
                        //Check in booking_transaction table
                        $bookingTransaction = BookingTransaction::where('booking_id', $value->booking_id)->get();
                        if(is_countable($bookingTransaction) && count($bookingTransaction) > 0){
                            foreach ($bookingTransaction as $k => $v) {
                                if($v->coupon_code != '' && strtolower($v->coupon_code) == strtolower($couponCode) && $v->coupon_code_id != '' && $v->coupon_code_id == $couponId && $v->paid == 1){
                                    $couponDiscount = 2;
                                    break;
                                }
                            }
                        }
                        
                        if($couponDiscount == 2){
                            break;
                        }
                    }
                }

            }
        }
        if ($couponDiscount == 0 || $couponDiscount == 2) { //2 and 0 means invalid
            return false;
        }

        return true;
    }

    public function message()
    {
        // Custom error message
        return 'The selected coupon code is invalid';
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->passes($attribute, $value)) {
            $fail($this->message());
        }
    }
}
