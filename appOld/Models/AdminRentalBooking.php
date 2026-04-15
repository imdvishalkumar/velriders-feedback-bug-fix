<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AdminRentalBooking extends Model
{
    use HasFactory;

    protected $table = 'rental_bookings';
    protected $primaryKey = 'booking_id';

    protected $fillable = [
        'customer_id',
        'vehicle_id',
        'from_branch_id',
        'to_branch_id',
        'pickup_date',
        'return_date',
        'rental_duration_minutes',
        'unlimited_kms',
        'total_cost',
        'status',
        'rental_type',
        'penalty_details',
        //'calculation_details',
        'start_otp',
        'end_otp',
        'data_json',
        'rental_duration',
        'sequence_no',
        'tax_rate',
        'is_end_by_admin',
        'is_test_booking',
    ];

    protected $hidden = [
        'payment',
        //'created_at',
        'updated_at',
    ];

    protected $appends = ['start_images', 'end_images', 'price_summary', 'admin_button_visibility'];

    // Define relationships
    public function payment()
    {
        return $this->belongsTo(Payment::class, 'booking_id', 'booking_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'booking_id', 'booking_id');
    }

    public function refund()
    {
        return $this->belongsTo(Refund::class, 'booking_id', 'booking_id');
    }
    public function cancelRentalBooking()
    {
        return $this->hasOne(CancelRentalBooking::class, 'booking_id', 'booking_id');
    }
    public function rentalBookingImage()
    {
        return $this->belongsTo(RentalBookingImage::class, 'booking_id', 'booking_id');
    }
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id', 'vehicle_id');
    }

    public function fromBranch()
    {
        return $this->belongsTo(Branch::class, 'from_branch_id', 'branch_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function bookingTransactions()
    {
        return $this->hasMany(BookingTransaction::class, 'booking_id', 'booking_id');
    }

    public function adminPenalties()
    {
        return $this->hasMany(AdminPenalty::class, 'booking_id', 'booking_id')->where('is_paid', 0);
    }

    function convertToDouble($value)
    {
        $doubleValue = number_format($value, 2);
        return $doubleValue;
    }

    // Function to extract value from the formatted string (e.g., '₹ 100')
    private function extractValue($formattedValue)
    {
        return doubleval(str_replace('₹ ', '', $formattedValue));
    }

    public function getStartImagesAttribute()
    {
        $images = RentalBookingImage::where('booking_id', $this->booking_id)->where('image_type', 'start')->get();
        return $images;
    }

    public function getEndImagesAttribute()
    {
        $images = RentalBookingImage::where('booking_id', $this->booking_id)->where('image_type', 'end')->get();
        return $images;
    }

    public function getPriceSummaryAttribute()
    {
        // return $this->generatePriceSummaryFromCalculationDetails($this->calculation_details);
        return $this->generatePriceSummaryFromBookingTransactions();
    }

    public function generatePriceSummaryFromCalculationDetails($encoded)
    {
        /* $decodedValue = json_decode($encoded, true);
         if (empty($decodedValue) || !isset($decodedValue['versions']) || !is_array($decodedValue['versions'])) {
             return null;
         }

         $data = $decodedValue;
         $calculation_details = [];
         $paid_final_amount_sum = 0;
         $completionAdded = false;
         $completionPaid = false;
         $refundable_deposit_remains = 0;
         $fromRefundableDeposit = false;
         $refunded = false;
         $refundedAmount = 0;
         $newTax = 0;

         foreach ($data['versions'] as $version) {
             $details = $version['details'] ?? [];

             // Extract details with defaults if not set
             $trip_amount = number_format($details['trip_amount'] ?? 0, 2);
             $convenience_fee = number_format($details['convenience_fee'] ?? 0, 2);
             $tax_amt = number_format($details['tax_amt'] ?? 0, 2);
             $total_amount = number_format($details['total_amount'] ?? 0, 2);
             $refundable_deposit = number_format($details['refundable_deposit'] ?? 0, 2);
             $rD = $details['refundable_deposit'] ?? 0;
             $final_amount = $details['final_amount'] ?? 0;

             // Skip processing if conditions are not met
             if ((!$details['order']['paid']) && $version['type'] !== 'completion') {
                 continue;
             }

             $price_summary = [];

             // Determine the type and create initial price summary
             switch ($version['type']) {
                 case 'new_booking':
                     break;
                 case 'extension':
                     $price_summary[] = [
                         "key" => "Extension Booking",
                         "value" => "",
                         "color" => "#000000",
                         "style" => "bold"
                     ];
                     break;
                 case 'completion':
                     $completionAdded = true;
                     $completionPaid = $details['order']['paid'] ?? false;
                     $fromRefundableDeposit = $details['order']['from_refundable_deposit'] ?? false;
                     $price_summary[] = [
                         "key" => "Additional Charges",
                         "value" => "",
                         "color" => "#000000",
                         "style" => "bold"
                     ];
                     break;
             }
             if ($version['type'] != 'completion') {
                 $start_date = isset($details['start_date']) ? Carbon::parse($details['start_date'])->format('d-m-Y H:i') : '';
                 $end_date = isset($details['end_date']) ? Carbon::parse($details['end_date'])->format('d-m-Y H:i') : '';
                 $trip_amount_string = "Trip Amount";
                 $kms_text = "";
                 if ($version['type'] == 'new_booking') {
                     if ($this->unlimited_kms) {
                         $kms_text = " (Unlimited Kms)";
                     } else {
                         $kilometerLimit = calculateKmLimit($this->rental_duration_minutes / 60);
                         $kms_text = " ($kilometerLimit Kms)";
                     }
                 }
                 $trip_amount_string .= $kms_text . "\nFrom $start_date\nTo $end_date";
                 $this->addToSummary($price_summary, $trip_amount_string, $trip_amount, "#000000", "normal");
                 $this->addToSummary($price_summary, "Tax Amount", $tax_amt, "#808080", "normal");
                 $this->addToSummary($price_summary, "Convenience Fee", $convenience_fee, "#808080", "normal");
                 if ($version['type'] == 'new_booking') {
                     $coupon_code = $details['coupon_code'];
                     $coupon_discount = $details['coupon_discount'] ?? 0;
                     $this->addToSummary($price_summary, "Coupon '$coupon_code'", $coupon_discount, "#808080", "normal");
                 }
                 $this->addToSummary($price_summary, "Total Price", $total_amount, "#000000", "normal");
             }    

             // Specific details for new booking
             if ($version['type'] === 'new_booking') {
                 // Add the final_amount to the sum
                 $paid_final_amount_sum += $final_amount;
                 $this->addToSummary($price_summary, "Refundable Deposit", $refundable_deposit, "#D3D3D3", "semibold");
                 $refunded = $details['refund']['processed'] ?? false;
                 $refundedAmount = $details['refund']['amount'] ?? 0;

                 $newTax += (float)$tax_amt;

             }
             // Add the final_amount to the sum
             if ($version['type'] === 'extension') {
                 if($details['order']['paid'] ?? false){
                     $paid_final_amount_sum += $final_amount;
                 }

                 $newTax += (float)$tax_amt;
             }

             // Specific details for completion
             if ($version['type'] === 'completion') {
                 $order = $details['order'] ?? [];
                 $lateReturn = number_format($details['late_return'] ?? 0, 2);
                 $exceededKmPayAmount = number_format($details['exceeded_km_limit'] ?? 0, 2);
                 $exceededKmPayAmountDirect = $details['exceeded_km_limit'] ?? 0;
                 $additionalCharges = number_format($details['additional_charges'] ?? 0, 2);
                 $additionalChargesInfo = $details['additional_charges_info'] ?? 'Admin charges';
                 $refundableDepositUsed = number_format($details['refundable_deposit_used'] ?? 0, 2);
                 $amountToPay = number_format($details['amount_to_pay'] ?? 0, 2);
                 $refundable_deposit_remains = $refundable_deposit;
                 $this->addToSummary($price_summary, "Late Return", $lateReturn, "#808080", "normal");
                 $fa = (float)$details['amount_to_pay'] ?? 0;
                 $paid_final_amount_sum += $fa;

                 $newTax += (float)$tax_amt;

                 if ($exceededKmPayAmount > 0) {
                     $extraKmDetails = $this->getExceededKmDetails($exceededKmPayAmountDirect);
                     $this->addToSummary($price_summary, $extraKmDetails['key'], $exceededKmPayAmount, "#000000", "bold");
                 }

                 $this->addToSummary($price_summary, $additionalChargesInfo, $additionalCharges, "#000000", "bold");
                 //$this->addToSummary($price_summary, "Tax Amount", $tax_amt, "#808080", "normal");
                 $this->addToSummary($price_summary, "Tax Amount", $newTax, "#808080", "normal");
                 $this->addToSummary($price_summary, "Refundable Deposit Used", $refundableDepositUsed, "#000000", "bold");
                 $this->addToSummary($price_summary, "Refundable Deposit Remains", $refundable_deposit, "#000000", "bold");

                 if (!$completionPaid) {
                     $this->addToSummary($price_summary, "Amount To Pay", $amountToPay, "#000000", "bold");
                 }
             }

             $calculation_details = array_merge($calculation_details, $price_summary);
         }

         if($fromRefundableDeposit) {
             if($paid_final_amount_sum > $rD){
                 $paid_final_amount_sum = floatval($paid_final_amount_sum) - floatval($rD);    
             }
         }
         if ($refunded && ($refundedAmount > 0)) {
             $this->addToSummary($calculation_details, "Refunded", number_format($refundedAmount ?? 0, 2), "#000000", "bold");
         }

         $final_amount = number_format($paid_final_amount_sum, 2);

         if ($completionAdded && $completionPaid || !$completionAdded) {
             if ($completionAdded && $completionPaid) {
                 $this->addToSummary($calculation_details, "Final Amount", $final_amount, "#000000", "bold");
             } else {
                 $this->addToSummary($calculation_details, "Final Amount", $final_amount, "#000000", "bold");
             }
         }

         return $calculation_details;*/
    }

    public function generatePriceSummaryFromBookingTransactions()
    {
        $bookingTransactions = BookingTransaction::where('booking_id', $this->booking_id)->get();
        if ($bookingTransactions->isEmpty()) {
            return null;
        }
        $calculation_details = [];
        $paid_final_amount_sum = 0;
        $completionAdded = false;
        $completionPaid = false;
        $refundable_deposit_remains = 0;
        $fromRefundableDeposit = false;
        $refunded = false;
        $refundedAmount = 0;

        foreach ($bookingTransactions as $transaction) {
            // Extract details with defaults if not set
            $trip_amount = number_format($transaction->trip_amount ?? 0, 2);
            $convenience_fee = number_format($transaction->convenience_fee ?? 0, 2);
            $tax_amt = number_format($transaction->tax_amt ?? 0, 2);
            $total_amount = number_format($transaction->total_amount ?? 0, 2);
            $refundable_deposit = number_format($transaction->refundable_deposit ?? 0, 2);
            $rD = $transaction->refundable_deposit ?? 0;
            $final_amount = $transaction->final_amount ?? 0;

            // Skip processing if conditions are not met
            if ((!$transaction->paid) /*&& $transaction->type !== 'completion'*/) {
                //continue;
            }

            $price_summary = [];
            // Determine the type and create initial price summary


            switch ($transaction->type) {
                case 'new_booking':
                    break;
                case 'extension':
                    $price_summary[] = [
                        "key" => "Extension Booking",
                        "value" => "",
                        "color" => "#000000",
                        "style" => "bold"
                    ];
                    break;
                case 'completion':
                    $completionAdded = true;
                    $completionPaid = $transaction->paid ?? false;
                    $fromRefundableDeposit = $transaction->from_refundable_deposit ?? false;
                    $price_summary[] = [
                        "key" => "Additional Charges",
                        "value" => "",
                        "color" => "#000000",
                        "style" => "bold"
                    ];
                    break;
            }

            if ($transaction->type != 'completion') {
                $start_date = isset($transaction->start_date) ? Carbon::parse($transaction->start_date)->format('d-m-Y H:i') : '';
                $end_date = isset($transaction->end_date) ? Carbon::parse($transaction->end_date)->format('d-m-Y H:i') : '';
                $trip_amount_string = "Trip Amount";
                $kms_text = "";
                if ($transaction->type == 'new_booking') {
                    if ($this->unlimited_kms) {
                        $kms_text = " (Unlimited Kms)";
                    } else {
                        $rentalDurationMinutes = round($this->rental_duration_minutes / 60);
                        $kilometerLimit = calculateKmLimit($rentalDurationMinutes, null, $this->vehicle_id);
                        $kms_text = " ($kilometerLimit Kms)";
                    }
                }
                $trip_amount_string .= $kms_text . "\nFrom $start_date\nTo $end_date";
                $tripDurationHours = $transaction->trip_duration_minutes / 60;
                if (!isset($trip_amount) && $trip_amount == '') {
                    $trip_amount = calculateTripAmount($transaction->rentalBooking->vehicle->rental_price, $tripDurationHours, $transaction->rentalBooking->vehicle_id);
                }
                $trip_amount = str_replace(',', '', $trip_amount);
                $trip_amount = round((float) $trip_amount, 2);
                if ($transaction->unlimited_kms == 1) {
                    //$trip_amount *= 1.3;
                }
                $trip_amount = number_format($trip_amount);
                $this->addToSummary($price_summary, $trip_amount_string, $trip_amount, "#000000", "normal");
                $this->addToSummary($price_summary, "Tax Amount", $tax_amt, "#808080", "normal");
                $this->addToSummary($price_summary, "Convenience Fee", $convenience_fee, "#808080", "normal");
                if ($transaction->type == 'new_booking') {
                    $coupon_code = $transaction->coupon_code;
                    $coupon_discount = $transaction->coupon_discount ?? 0;
                    $this->addToSummary($price_summary, "Coupon '$coupon_code'", $coupon_discount, "#808080", "normal");
                }
                if ($transaction->type == 'extension') {
                    $coupon_code = $transaction->coupon_code;
                    $coupon_discount = $transaction->coupon_discount ?? 0;
                    $this->addToSummary($price_summary, "Coupon '$coupon_code'", $coupon_discount, "#808080", "normal");
                }
                if (strtolower($transaction->type) !== 'penalty') {
                    $this->addToSummary($price_summary, "Total Price", $total_amount, "#000000", "normal");
                }
            }

            // Specific details for new booking
            if ($transaction->type === 'new_booking') {
                // Add the final_amount to the sum
                if ($transaction->paid) {
                    $paid_final_amount_sum += $final_amount;
                }
                $this->addToSummary($price_summary, "Refundable Deposit", $refundable_deposit, "#D3D3D3", "semibold");
                $refunded = $transaction->refund_processed ?? false;
                $refundedAmount = $transaction->refund_amount ?? 0;
            }
            // Add the final_amount to the sum
            if ($transaction->type === 'extension') {
                if ($transaction->paid) {
                    $paid_final_amount_sum += $final_amount;
                }
            }
            // Specific details for completion
            if ($transaction->type === 'completion') {
                $lateReturn = number_format($transaction->late_return ?? 0, 2);
                $exceededKmPayAmount = number_format($transaction->exceeded_km_limit ?? 0, 2);
                $exceededKmPayAmountDirect = $transaction->exceeded_km_limit ?? 0;
                $additionalCharges = number_format($transaction->additional_charges ?? 0, 2);
                $additionalChargesInfo = $transaction->additional_charges_info ?? 'Admin charges';
                $refundableDepositUsed = number_format($transaction->refundable_deposit_used ?? 0, 2);
                $amountToPay = number_format($transaction->amount_to_pay ?? 0, 2);
                $refundable_deposit_remains = $refundable_deposit;
                $this->addToSummary($price_summary, "Late Return", $lateReturn, "#808080", "normal");
                $fa = (float) $transaction->amount_to_pay ?? 0;
                if ($transaction->paid) {
                    $paid_final_amount_sum += $fa;
                }
                if ($exceededKmPayAmount > 0) {
                    $extraKmDetails = $this->getExceededKmDetails($exceededKmPayAmountDirect);
                    $this->addToSummary($price_summary, $extraKmDetails['key'], $exceededKmPayAmount, "#000000", "bold");
                }
                $this->addToSummary($price_summary, $additionalChargesInfo, $additionalCharges, "#000000", "bold");
                $this->addToSummary($price_summary, "Tax Amount", $tax_amt, "#808080", "normal");
                $this->addToSummary($price_summary, "Refundable Deposit Used", $refundableDepositUsed, "#000000", "bold");
                $this->addToSummary($price_summary, "Refundable Deposit Remains", $refundable_deposit, "#000000", "bold");
                if (!$completionPaid) {
                    $this->addToSummary($price_summary, "Amount To Pay", $amountToPay, "#000000", "bold");
                }
            }
            if ($transaction->type === 'penalty') {
                $this->addToSummary($price_summary, "Admin Penalty", $transaction->total_amount, "#000000", "normal");
                if ($transaction->paid) {
                    $paid_final_amount_sum += $transaction->total_amount;
                }
            }
            $calculation_details = array_merge($calculation_details, $price_summary);
        }


        if ($fromRefundableDeposit) {
            if ($paid_final_amount_sum > $rD) {
                $paid_final_amount_sum = floatval($paid_final_amount_sum) - floatval($rD);
            }
        }
        if ($refunded && ($refundedAmount > 0)) {
            $this->addToSummary($calculation_details, "Refunded", number_format($refundedAmount ?? 0, 2), "#000000", "bold");
        }
        $final_amount = number_format($paid_final_amount_sum, 2);
        if ($completionAdded && $completionPaid || !$completionAdded) {
            if ($completionAdded && $completionPaid) {
                $this->addToSummary($calculation_details, "Final Amount", $final_amount, "#000000", "bold");
            } else {
                $this->addToSummary($calculation_details, "Final Amount", $final_amount, "#000000", "bold");
            }
        }

        return $calculation_details;
    }

    public function calculateKmLimit($tripDurationHours)
    {
        if ($tripDurationHours < 8) { // 4hours
            return 50;
        } elseif ($tripDurationHours < 12) {
            return 100;
        } elseif ($tripDurationHours < 24) {
            return 220;
        } else {
            if ($tripDurationHours == 24) {
                return 300;
            } else {
                return intval((300 / 24) * $tripDurationHours);
            }
        }
    }

    private function addToSummary(&$summary, $key, $value, $color, $style)
    {
        //if ($value != 0) {
        if ($value >= 0) {
            $summary[] = [
                "key" => $key,
                "value" => "₹ {$value}",
                "color" => $color,
                "style" => $style
            ];
        }
    }

    // Assuming this method exists and calculates the required exceeded kilometer details
    private function getExceededKmDetails($exceededKmPayAmount)
    {
        // OLD CODE
        // $kilometerLimit = calculateKmLimit(round($this->rental_duration_minutes / 60));
        // $kilometerDifference = $this->end_kilometers - $this->start_kilometers;
        // $extra = $kilometerDifference - $kilometerLimit;
        // $rate = 0;
        // if($extra > 0){
        //     $rate = floatval($exceededKmPayAmount) / floatval($extra);
        // }
        // //$rate = round($rate,2);
        // $rate = round($rate);
        // return [
        //     "key" => "Extra Kms {$extra} \nPer km rate {$rate}"
        // ];

        // NEW CODE
        $pickupDateTime = Carbon::parse($this->pickup_date);
        $returnDateTime = Carbon::parse($this->return_date);
        $tripDurationHours = $returnDateTime->diffInHours($pickupDateTime);
        $exceededKilometerPenalty = 0;
        $kilometerLimit = calculateKmLimit($tripDurationHours, null, $this->vehicle_id);
        $kilometerDifference = $this->end_kilometers - $this->start_kilometers;
        $extra = $kilometerDifference - $kilometerLimit;
        $rate = 0;
        if ($extra > 0) {
            $rate = floatval($exceededKmPayAmount) / floatval($extra);
        }
        //$rate = round($rate,2);
        $rate = round($rate);
        return [
            "key" => "Extra Kms {$extra} \nPer km rate {$rate}"
        ];
    }

    //THIS FUNCTION PARAMETERS ARE DIFFERENCT THAN RENTALBOOKING MODEL
    public function computeRentalCostDetails($rentalPrice, $tripDurationMinutes, $unlimitedKms = false, $couponCode = null, $startDate = null, $endDate = null, $vehicleTypeId = null, $extend = false, $orderType = null, $customerId = NULL, $paymentMode = NULL, $refNumber = NULL, $vehicleCommissionPercent = 0, $taxRate = null, $tripAmt = 0, $unlimitedStatus = 0, $vehicleId = null, $unlimitedKmStatus = 1, $finalAmt = 0, $finalAmtStatus = 0)
    {
        // Initialize variables
        $couponDiscount = 0;
        $cCode = '';
        $refundableDeposit = 0;
        $tripDurationHours = $tripDurationMinutes / 60;

        $tripAmount = 0;
        if ($finalAmtStatus == 1) {
            // Backward calculation from Final Amount
            $convenienceFee = 0;
            if (!$extend) {
                $convenienceFee = 99;
                if ($vehicleTypeId) {
                    $vehicleType = VehicleType::where('type_id', $vehicleTypeId)->first();
                    if ($vehicleType) {
                        $convenienceFee = $vehicleType->convenience_fees ?? 99;
                    }
                }
            }

            // K = taxRate + (vehicleCommissionPercent / 100) * 0.18
            $K = (float) $taxRate + ($vehicleCommissionPercent / 100) * 0.18;
            $tripAmountToPay = ($finalAmt - $convenienceFee) / (1 + $K);

            // Now we need TripAmount from tripAmountToPay considering Coupon
            if ($couponCode && $startDate && $endDate) {
                $coupon = Coupon::where('code', $couponCode)->where('is_active', 1)->first();
                if ($coupon) {
                    if ($coupon->type === 'percentage') {
                        $p = $coupon->percentage_discount / 100;
                        if (($tripAmountToPay / (1 - $p)) * $p > $coupon->max_discount_amount) {
                            $tripAmount = $tripAmountToPay + $coupon->max_discount_amount;
                        } else {
                            $tripAmount = $tripAmountToPay / (1 - $p);
                        }
                    } elseif ($coupon->type === 'fixed') {
                        $tripAmount = $tripAmountToPay + $coupon->fixed_discount_amount;
                    }
                } else {
                    $tripAmount = $tripAmountToPay;
                }
            } else {
                $tripAmount = $tripAmountToPay;
            }
            $tripAmount = round($tripAmount, 2);
        } else {
            if ($tripAmt == null) {
                $tripAmount = calculateTripAmount($rentalPrice, $tripDurationHours, $vehicleId);
            } else {
                $tripAmount = $tripAmt;
            }

            // Calculate trip amount
            if ($unlimitedKms) {
                if ($orderType == 'extension') {
                    if ($unlimitedKmStatus == 0) {
                        $tripAmount *= 1.3;
                    }
                } else {
                    $tripAmount *= 1.3;
                }
            }
        }

        //Re-calculate everything normally now that we have $tripAmount
        if ($couponCode && $startDate && $endDate) {
            $coupon = Coupon::where('code', $couponCode)
                ->where('valid_from', '<=', $startDate)
                ->where('valid_to', '>=', $endDate)
                ->first();

            //if ($coupon && $coupon->is_active && now()->between($coupon->valid_from, $coupon->valid_to)) {
            if ($coupon && $coupon->is_active) { //UPDATED ON 6-8-25
                $cCode = isset($coupon->code) ? $coupon->code : '';
                if ($coupon->type === 'percentage') {
                    $couponDiscount = min($tripAmount * ($coupon->percentage_discount / 100), $coupon->max_discount_amount);
                } elseif ($coupon->type === 'fixed') {
                    $couponDiscount = min($coupon->fixed_discount_amount, $tripAmount);
                }
            }
        }

        // Calculate convenience fee
        $convenienceFee = 0;
        if (!$extend) {
            $convenienceFee = 99; // Default convenience fee
            if ($vehicleTypeId) {
                $vehicleType = VehicleType::where('type_id', $vehicleTypeId)->first();
                if ($vehicleType) {
                    $convenienceFee = $vehicleType->convenience_fees ?? 99;
                }
            }
        }

        // Calculate total amount
        $tripAmountToPay = (float) $tripAmount - $couponDiscount;
        $vehicleCommissionTaxAmt = $vehicleCommissionAmt = 0;
        if ($vehicleCommissionPercent > 0) {
            $vehicleCommissionAmt = ($tripAmountToPay * $vehicleCommissionPercent) / 100;
            $vehicleCommissionAmt = round($vehicleCommissionAmt);
            //$tripAmount -= $vehicleCommissionAmt;
            $vehicleCommissionTaxAmt = ($vehicleCommissionAmt * 18) / 100;
        }
        /*$customerGst = '';
        if($customerId != NULL){
            $user = Customer::where('customer_id', $customerId)->first();
            $customerGst = $user->gst_number ?? '';    
        }
        $taxRate = $customerGst ? 0.12 : 0.05;*/
        $taxAmt = $tripAmountToPay * $taxRate;
        $taxAmt += $vehicleCommissionTaxAmt;
        if ($tripAmt != null && $tripAmt == 0) {
            $convenienceFee = 0;
        }
        $totalAmount = $tripAmountToPay + $convenienceFee + $taxAmt;
        $finalAmount = $totalAmount;
        // Adjust total amount for extension
        // Calculate refundable deposit if not an extension
        if (!$extend) {
            //$refundableDeposit = round($rentalPrice * 2.5 * 2, 2); // Convert to single day price and take 2 days of advance
            //$refundableDeposit = 0;
            // $finalAmount += $refundableDeposit;
        }
        return array_merge(
            [
                'start_date' => date('Y-m-d H:i:s', strtotime($startDate)),
                'end_date' => date('Y-m-d H:i:s', strtotime($endDate)),
                'unlimited_kms' => (int) $unlimitedKms,
                'rental_price' => (int) $rentalPrice,
                'trip_duration_minutes' => $tripDurationMinutes,
                'trip_amount' => round((float) $tripAmount, 2),
                'tax_amt' => round($taxAmt, 2),
                'coupon_discount' => (int) $couponDiscount,
                'coupon_code' => $cCode,
                'trip_amount_to_pay' => round($tripAmountToPay, 2),
                'convenience_fee' => $convenienceFee,
                'total_amount' => round($totalAmount, 2),
                'refundable_deposit' => $extend ? 0 : $refundableDeposit,
                'final_amount' => round($finalAmount, 2),
                'order_type' => $orderType,
                'vehicle_commission_amt' => $vehicleCommissionAmt,
                'vehicle_commission_tax_amt' => round($vehicleCommissionTaxAmt),
            ],
            $paymentMode != NULL ? ['payment_mode' => $paymentMode] : [],
            $refNumber != NULL ? ['reference_number' => $refNumber] : []
        );

    }

    public function generatePriceSummary($data)
    {
        // Extracting values from the $data array with default values
        $trip_amount = number_format($data['trip_amount'] ?? 0, 2);
        $coupon_discount = number_format($data['coupon_discount'] ?? 0, 2);
        $coupon_code = $data['coupon_code'] ?? '';
        $tax_amt = number_format($data['tax_amt'] ?? 0, 2);
        $convenience_fee = number_format($data['convenience_fee'] ?? 0, 2);
        $total_amount = number_format($data['total_amount'] ?? 0, 2);
        $refundable_deposit = number_format($data['refundable_deposit'] ?? 0, 2);
        $final_amount = number_format($data['final_amount'] ?? 0, 2);

        // Creating the price summary array
        $price_summary = [];

        // Add entries to price_summary if not empty or zero
        if (!empty($trip_amount) && $trip_amount != '0.00') {
            $price_summary[] = [
                "key" => "Trip Amount",
                "value" => "₹ " . $trip_amount,
                "color" => "#000000",
                "style" => "normal"
            ];
        }
        if (!empty($coupon_discount) && $coupon_discount != '0.00') {
            $price_summary[] = [
                "key" => "Coupon Discount",
                "value" => "₹ " . $coupon_discount,
                "color" => "#000000",
                "style" => "normal"
            ];
        }
        if (!empty($coupon_code)) {
            $price_summary[] = [
                "key" => "Coupon Code",
                "value" => $coupon_code,
                "color" => "#000000",
                "style" => "normal"
            ];
        }
        if (!empty($tax_amt) && $tax_amt != '0.00') {
            $price_summary[] = [
                "key" => "Tax Amount",
                "value" => "₹ " . $tax_amt,
                "color" => "#000000",
                "style" => "normal"
            ];
        }
        if (!empty($convenience_fee) && $convenience_fee != '0.00') {
            $price_summary[] = [
                "key" => "Convenience Fee",
                "value" => "₹ " . $convenience_fee,
                "color" => "#000000",
                "style" => "normal"
            ];
        }
        if (!empty($total_amount) && $total_amount != '0.00') {
            $price_summary[] = [
                "key" => "Total Amount",
                "value" => "₹ " . $total_amount,
                "color" => "#000000",
                "style" => "normal"
            ];
        }
        if (!empty($refundable_deposit) && $refundable_deposit != '0.00') {
            $price_summary[] = [
                "key" => "Refundable Deposit",
                "value" => "₹ " . $refundable_deposit,
                "color" => "#000000",
                "style" => "normal"
            ];
        }
        if (!empty($final_amount) && $final_amount != '0.00') {
            $price_summary[] = [
                "key" => "Final Amount",
                "value" => "₹ " . $final_amount,
                "color" => "#000000",
                "style" => "normal"
            ];
        }

        // Return the final_amount as a string and the price summary array
        return ["final_amount" => strval($total_amount), "price_summary" => $price_summary];
    }

    public function getAdminButtonVisibilityAttribute()
    {
        $data = [
            'start_journey_button' => false,
            'end_journey_button' => false,
        ];
        $pickupDate = Carbon::parse($this->pickup_date);
        $currentDate = Carbon::now()->setTimezone('Asia/Kolkata');
        $returnDate = Carbon::parse($this->return_date);

        if ($this->status !== 'confirmed') {
            $data['start_journey_button'] = false;
        } else {
            $hasApprovedGovtId = CustomerDocument::where('customer_id', $this->customer_id)
                ->where('is_approved', 'approved')
                ->where('document_type', 'govtid')
                ->where('is_blocked', 0)
                ->exists();
            $hasApprovedDL = CustomerDocument::where('customer_id', $this->customer_id)
                ->where('is_approved', 'approved')
                ->where('document_type', 'dl')
                ->where('is_blocked', 0)
                ->exists();
            $checkCust = Customer::select('customer_id', 'is_blocked')->where('customer_id', $this->customer_id)->first();
            if ($checkCust && $checkCust->is_blocked == 0 && $hasApprovedDL && $hasApprovedGovtId) {
                $adjustedPickupDate = $pickupDate->copy()->subMinutes(30);
                $adjustedReturnDate = $returnDate->copy();
                if ($currentDate >= $adjustedPickupDate && $currentDate <= $adjustedReturnDate) {
                    $data['start_journey_button'] = true;
                } else {
                    $data['start_journey_button'] = false;
                }
            } else {
                $data['start_journey_button'] = false;
            }
        }

        if ($this->status === 'running') {
            // Check if 5 images are uploaded for this booking
            $imageCount = RentalBookingImage::where('booking_id', $this->booking_id)
                ->where('image_type', 'start')
                ->count();
            $data['end_journey_button'] = $imageCount >= 5;

            // Check if admin penalty is not paid
            /*$adminPenalty = AdminPenalty::where(['booking_id' => $this->booking_id, 'is_paid' => 0])->where('amount', '>', 0)->first();
            if ($adminPenalty != '') {
                $data['end_journey_button'] = false;
            }*/
        } else {
            $data['end_journey_button'] = false;
        }
        return $data;
    }

}
