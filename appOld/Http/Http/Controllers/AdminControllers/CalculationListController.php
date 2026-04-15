<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TripAmountCalculationRule;
use App\Models\AdminRentalBooking;
use App\Models\BookingTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use DB;
use Illuminate\Support\Facades\Storage;
use App\Jobs\SendNotificationJob;
class CalculationListController extends Controller
{
    public function getBookingCalculationList(){
        hasPermission('booking-calculation-list');
        return view('admin.booking-calculation');
    }

    public function getTripCalculationList(){
        hasPermission('trip-amount-calculation-list');
        return view('admin.tripamount-calculation-rules');
    }

    public function getTripCalculations(Request $request){
        if(isset($request->id) && $request->id != ''){  
            $tripAmountCalculationRule = TripAmountCalculationRule::where('id', $request->id)->first();
        }else{
            $tripAmountCalculationRule = TripAmountCalculationRule::get();
        }
        
        return $tripAmountCalculationRule;
    }

    public function updateTripCalculation(Request $request){
        $trip = TripAmountCalculationRule::find($request->id);
        $oldVal = clone $trip;

        $trip->hours = $request->hours;
        $trip->multiplier = $request->multiplier;
        $trip->save();

        $newVal = $trip;
        $differences = compareArray($oldVal, $newVal);
        if(isset($differences) && is_countable($differences) && count($differences) > 0){
            logAdminActivity('Trip Amount Calculation Updation', $oldVal, $newVal);
        }

        return response()->json([
            'data' => $trip,
            'status' => true,
            'message' => 'Trip Amount Calculation Updated Successfully.',
        ]);
    }

    public function createTripCalculation(Request $request){
        $trip = new TripAmountCalculationRule();
        $trip->hours = $request->hours;
        $trip->multiplier = $request->multiplier;
        $trip->save();
        logAdminActivity("Trip Amount Calculation Creation", $trip);

        return response()->json([
            'data' => $trip,
            'status' => true,
            'message' => 'Trip Amount Calculation Created Successfully.',
        ]);
    }

    public function getBookings(Request $request){
        $rentalBooking = AdminRentalBooking::whereIn('status', ['no show', 'completed','canceled'])->with(['customer', 'vehicle', 'refund', 'payment'])
                ->where('sequence_no', '!=', 0)      
                ->whereHas('payments');
                // ->whereHas('payments', function ($query) {
                //     $query->whereNull('payment_mode');
                // });
        if($request->from_date != '' && $request->to_date != ''){
            $startDate = Carbon::parse($request->from_date)->format('Y-m-d');
            $endDate = Carbon::parse($request->to_date)->format('Y-m-d');
            $rentalBooking = $rentalBooking->join(DB::raw('(SELECT booking_id,
                            COALESCE(MAX(CASE WHEN type = "completion" THEN timestamp END), MAX(CASE WHEN type = "new_booking" THEN timestamp END)) AS effective_timestamp FROM booking_transactions GROUP BY booking_id) AS transaction_dates'),'rental_bookings.booking_id', '=', 'transaction_dates.booking_id')
            ->whereBetween('transaction_dates.effective_timestamp', [$startDate, $endDate]);
        }
        /*if($request->from_date != '' && $request->to_date != ''){
            $rentalBooking = $rentalBooking->whereBetween('return_date', [$request->from_date, $request->to_date]);
        }*/
        /*select * from `rental_bookings` inner join (SELECT booking_id,  COALESCE(MAX(CASE WHEN type = "completion" THEN timestamp END), MAX(CASE WHEN type = "new_booking" THEN timestamp END)) AS effective_timestamp FROM booking_transactions GROUP BY booking_id) AS transaction_dates on `rental_bookings`.`booking_id` = `transaction_dates`.`booking_id` where `transaction_dates`.`effective_timestamp` between ? and ?
        echo "<pre>"; print_r($rentalBooking->toSql()); die;*/
        $rentalBooking = $rentalBooking->get();

        // $rentalBooking = AdminRentalBooking::/*whereBetween('return_date', [$startDate, $endDate])->*/whereIn('status', ['no show', 'completed','canceled'])->with(['customer', 'vehicle', 'refund', 'payment'])->/*where('booking_id', 607)->*/where('sequence_no', '!=', 0)->whereHas('payments', function ($query) {
        //             $query->whereNull('payment_mode');
        //         })->get()->map(function ($vehicle) {
        //     $vehicle->pickup_date_formatted = Carbon::parse($vehicle->pickup_date)->format('d-m-Y g:i A');
        //     $vehicle->return_date_formatted = Carbon::parse($vehicle->return_date)->format('d-m-Y g:i A');
        //     return $vehicle;
        // });
        
        $tripHours = 0;
        $multiplier = 0;
        $hours = 0;

        foreach ($rentalBooking as $key => $value) {
            $taxableAmount = 0;
            $finalAmount = 0;
            if((is_countable($value->price_summary) && count($value->price_summary) > 0)){
                $cDetails = [];
                $taxVal = $vehicleCommissionAmt = $vehicleCommissionTaxAmt = $vehicleComm = 0;
                $bTransaction = BookingTransaction::where('booking_id', $value->booking_id)->get();
                if(is_countable($bTransaction) && count($bTransaction) > 0){
                    foreach ($bTransaction as $k => $v) {
                        if($v->tax_amt && $v->tax_amt != '' && $v->paid == 1){
                            $taxVal += $v->tax_amt;
                            $taxVal -= $v->vehicle_commission_tax_amt;
                            $vehicleCommissionAmt += $v->vehicle_commission_amount;                
                            $vehicleCommissionTaxAmt += $v->vehicle_commission_tax_amt;  
                            if($v->type != 'penalty'){
                                $vehicleComm += $v->vehicle_commission_amount;  
                            } 
                        }
                    }
                }
                if($value->created_at == NULL){
                    $value->created_date = '-';
                }
                else{
                    $value->created_date = date('d/m/Y', strtotime($value->created_at));
                }
                $cFeesAmt = getConvenienceAmt($value->booking_id, 'amt');
                $cFeesGST = getConvenienceGst($value->booking_id, 'gst');
                $taxableAmt = getTaxableAmt($value->booking_id);

                $value->convenienceFeesAmount = $cFeesAmt;
                $value->convenienceFeesGST = $cFeesGST;

                $taxableAmt -= $vehicleComm;

                $value->taxableAmount = round($taxableAmt, 2);
                $value->vehicleCommissionAmt = round($vehicleCommissionAmt, 2);
                $value->vehicleCommissionTaxAmt = round($vehicleCommissionTaxAmt, 2);
                $value->finalAmt = round(($cFeesAmt + $cFeesGST + $taxableAmt), 2);
                $value->invoiceDate = getInvoiceDate($value->booking_id);

                $value->tax = $taxVal;
                $value->paymentMode = getPaymentMode($value->booking_id);
            }else{
                $value->cDetails = '';    
            }

            // Trip Amount Calculation
            $tripHours = isset($value->rental_duration_minutes) ? $value->rental_duration_minutes / 60 : 0;
            $minTripHoursRule = TripAmountCalculationRule::orderBy('hours')->first();
            if($minTripHoursRule != ''){
                if ($tripHours < $minTripHoursRule->hours) {
                    $tripHours = $minTripHoursRule->hours;
                }
            }
            $value->tripDurationInHours = $tripHours;
            $rules = TripAmountCalculationRule::orderBy('hours', 'desc')->get()->toArray();
            $multiplier = 1;
            $hours = isset($minTripHoursRule->hours) ? $minTripHoursRule->hours : 0;
            if(isset($rules) && is_countable($rules) && count($rules) > 0){
                foreach ($rules as $rule) {
                    if ($tripHours >= $rule['hours']) {
                        $multiplier = $rule['multiplier'];
                        $hours = $rule['hours'];
                        break;
                    }
                }
            }
            $value->multiplier = $multiplier;
            $value->hours = $hours;
        }
        return response()->json($rentalBooking);
    }

}