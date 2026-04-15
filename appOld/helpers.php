<?php
use App\Models\{AdminActivityLog, CustomerDocument, RentalBooking, Setting, TripAmountCalculationRule, AdminRentalBooking, Payment, Customer, CarEligibility, BookingTransaction, Branch, Vehicle, Coupon, OfferDate, VehiclePriceDetail, VehicleModelPriceDetail, CarHostPickupLocation, CarHostPickupLocationTemp, CarHostVehicleFeatureTemp, CarHostVehicleImageTemp, VehicleModel, VehicleDocumentTemp, VehiclePriceDetailTemp};
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Exceptions\UnauthorizedException;
//use Google\Client as GoogleClient;
//use Google\Client;

function getIndianCurrency(float $number)
{
    if ($number < 0) {
        $number = abs($number);
    }
    $decimal = round($number - ($no = floor($number)), 2) * 100;
    $hundred = null;
    $digits_length = strlen($no);
    $i = 0;
    $str = array();
    $words = array(
        0 => '',
        1 => 'one',
        2 => 'two',
        3 => 'three',
        4 => 'four',
        5 => 'five',
        6 => 'six',
        7 => 'seven',
        8 => 'eight',
        9 => 'nine',
        10 => 'ten',
        11 => 'eleven',
        12 => 'twelve',
        13 => 'thirteen',
        14 => 'fourteen',
        15 => 'fifteen',
        16 => 'sixteen',
        17 => 'seventeen',
        18 => 'eighteen',
        19 => 'nineteen',
        20 => 'twenty',
        30 => 'thirty',
        40 => 'forty',
        50 => 'fifty',
        60 => 'sixty',
        70 => 'seventy',
        80 => 'eighty',
        90 => 'ninety'
    );
    $digits = array('', 'hundred', 'thousand', 'lakh', 'crore');
    while ($i < $digits_length) {
        $divider = ($i == 2) ? 10 : 100;
        $number = floor($no % $divider);
        $no = floor($no / $divider);
        $i += $divider == 10 ? 1 : 2;
        if ($number) {
            $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
            $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
            $str[] = ($number < 21) ? $words[$number] . ' ' . $digits[$counter] . $plural . ' ' . $hundred : $words[floor($number / 10) * 10] . ' ' . $words[$number % 10] . ' ' . $digits[$counter] . $plural . ' ' . $hundred;
        } else
            $str[] = null;
    }
    $Rupees = implode('', array_reverse($str));
    $paise = ($decimal > 0) ? "" . ucfirst($words[$decimal / 10] . " " . $words[$decimal % 10]) . ' Paise' : '';
    return ($Rupees ? $Rupees . 'Rupees ' : '') . $paise;
}

function logAdminActivity($activityDescription, $oldVal = NULL, $newVal = NULL, $oldImg = NULL, $adminId = NULL)
{ //adminId particulary used for Queue Job
    if (auth()->guard('admin_web')->check()) {
        $adminUserId = auth()->guard('admin_web')->user()->admin_id;
    } else {
        $adminUserId = $adminId;
    }

    $adminActivityLog = new AdminActivityLog();
    $adminActivityLog->admin_id = $adminUserId;
    $adminActivityLog->activity_description = isset($activityDescription) ? $activityDescription : NULL;
    $adminActivityLog->save();

    if ($oldVal != NULL) {
        if ($oldImg != NULL)
            $oldVal->icon = $oldImg;
        $adminActivityLog->old_value = json_encode($oldVal);
    }
    if ($newVal != NULL) {
        $adminActivityLog->new_value = json_encode($newVal);
    }
    $adminActivityLog->save();

}

function logAdminActivities($activityDescription, $oldVal = NULL, $newVal = NULL, $oldImg = NULL, $adminId = NULL)
{ //adminId particulary used for Queue Jobs
    if (auth()->guard('admin')->check()) {
        $adminUserId = auth()->guard('admin')->user()->admin_id;
    } else {
        $adminUserId = $adminId;
    }

    $adminActivityLog = new AdminActivityLog();
    $adminActivityLog->admin_id = $adminUserId;
    $adminActivityLog->activity_description = isset($activityDescription) ? $activityDescription : NULL;
    $adminActivityLog->save();

    if ($oldVal != NULL) {
        if ($oldImg != NULL)
            $oldVal->icon = $oldImg;
        $adminActivityLog->old_value = json_encode($oldVal);
    }
    if ($newVal != NULL) {
        $adminActivityLog->new_value = json_encode($newVal);
    }
    $adminActivityLog->save();

}

function compareArray($object1, $object2)
{
    // Convert objects to arrays
    $array1 = $object1->toArray();
    $array2 = $object2->toArray();

    // Find differences between arrays
    $differences = array_diff_assoc($array1, $array2);

    return $differences;
}


function sendPushNotification($deviceToken, $title = NULL, $content = NULL)
{
    $returnArr = [];
    //New Code
    $url = 'https://fcm.googleapis.com/v1/projects/velriders-8db39/messages:send'; //velriders-8db39 <- Project ID
    $accessToken = getDynamicAccessToken();
    if ($accessToken != '') {
        $jsonResponse = [
            "message" => [
                "token" => $deviceToken,
                "notification" => [
                    "title" => $title,
                    "body" => $content
                ],
                "data" => [
                    "title" => "Good :)",
                    "body" => "New Notification :)"
                ]
            ]
        ];
        $client = new Client();
        $response = $client->post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,

            ],
            'json' => $jsonResponse,
        ]);
        // Handle response
        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();
        $returnArr = [
            'status_code' => $statusCode,
            'response' => json_decode($body, true),
        ];
    } else {
        Log::info("Access Token for Push Notification is not found..");
    }
    return $returnArr;
}

function sendTopicPushNotification($subject, $content)
{
    $url = 'https://fcm.googleapis.com/v1/projects/velriders-8db39/messages:send';
    $accessToken = getDynamicAccessToken();
    if ($accessToken != '') {
        $jsonResponse = [
            "message" => [
                "topic" => "all_users",
                //"topic" => "all_test_users", // FOR TESTING PURPOSE ONLY
                "notification" => [
                    "title" => $subject,
                    "body" => $content
                ],
            ]
        ];
        $client = new Client();
        $response = $client->post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,
            ],
            'json' => $jsonResponse,
        ]);
        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();
        $returnArr = [
            'status_code' => $statusCode,
            'response' => json_decode($body, true),
        ];
    } else {
        Log::info("Access Token for Push Notification is not found..");
    }
    return $returnArr;
}

function getDynamicAccessToken()
{
    $accessToken = '';
    $jsonFile = config_path('velriders-8db39-5a56d176b2d7.json'); //Got from the service account
    //$client = new GoogleClient();
    $client = new \Google\Client();
    $client->setAuthConfig($jsonFile);
    $client->setScopes([
        'https://www.googleapis.com/auth/firebase.messaging',
    ]);
    $accessTokenResponse = $client->fetchAccessTokenWithAssertion();

    if ($accessTokenResponse && $accessTokenResponse['access_token']) {
        $accessToken = $accessTokenResponse['access_token'] ?? '';
    }

    return $accessToken;
}

function getRazorpayKey()
{
    $user = Auth::guard('api')->user();
    if ($user && $user->is_test_user != 1) {
        $rKey = get_env_variable('RAZORPAY_API_LIVE_KEY');
    } else {
        $rKey = get_env_variable('RAZORPAY_API_KEY');
    }
    return $rKey;
}

function getRazorpaySecret()
{
    $user = Auth::guard('api')->user();
    if ($user && $user->is_test_user != 1) {
        $rKey = get_env_variable('RAZORPAY_API_LIVE_SECRET');
    } else {
        $rKey = get_env_variable('RAZORPAY_API_SECRET');
    }

    return $rKey;
}


function getExtentionCalc($bookingId)
{ //This function is un-used
    $returnData['taxAmount'] = 0;
    $returnData['totalAmount'] = 0;
    $returnData['refundAmount'] = 0;
    $taxAmount = 0;
    $totalAmount = 0;
    $finalAmount = 0;
    $refundAmount = 0;
    $rentalBookingdata = DB::table('rental_bookings')->where('booking_id', $bookingId)->first();
    $calcDetails = BookingTransaction::where(['booking_id' => $bookingId])->get();
    if (is_countable($calcDetails) && count($calcDetails) > 0) {
        foreach ($calcDetails as $key => $value) {
            if (isset($value->type) && $value->type == 'new_booking') {
                $finalAmount += $value->final_amount;
                $refundAmount += $value->refundable_deposit;
            }
            if (isset($value->type) && $value->type == 'extension') {
                $taxAmount += $value->tax_amt;
                $totalAmount += $value->total_amount;
                $finalAmount += $value->final_amount;
                $refundAmount += $value->refundable_deposit;

                $returnData['taxAmount'] = $taxAmount;
                $returnData['totalAmount'] = $totalAmount;
                $returnData['refundAmount'] = $refundAmount;
            }
            $returnData['finalAmount'] = $finalAmount;
        }
    }

    return $returnData;
}

function getTaxPercent($mainAmt, $taxAmount, $tripAmountToPay = 0, $vehiclePercent = 0, $gstPercent = 0, $commissionTaxAmount = 0)
{
    $taxPercent = $vehicleCommissionAmt = $vehicleCommissionTaxAmt = 0;
    if ($mainAmt > 0 && $taxAmount > 0) {
        if ($gstPercent == 0) {
            $firstVal = $mainAmt * 0.12;
            $secondVal = $mainAmt * 0.05;
            if ($commissionTaxAmount == 0) {
                if ($vehiclePercent != 0) {
                    $vehicleCommissionAmt = ($tripAmountToPay * $vehiclePercent) / 100;
                    $vehicleCommissionAmt = round($vehicleCommissionAmt);
                    $vehicleCommissionTaxAmt = ($vehicleCommissionAmt * 18) / 100;
                }
                $firstVal += $vehicleCommissionTaxAmt;
                $secondVal += $vehicleCommissionTaxAmt;
            } else {
                $firstVal += $commissionTaxAmount;
                $secondVal += $commissionTaxAmount;
            }
            if (round($firstVal, 2) == $taxAmount)
                $taxPercent = 12;
            if (round($secondVal, 2) == $taxAmount)
                $taxPercent = 5;
        } else {
            if ($gstPercent == 0.05) {
                $taxPercent = 5;
            } elseif ($gstPercent == 0.12) {
                $taxPercent = 12;
            }
        }
    }
    return $taxPercent;
}

function getRazorpayBalance()
{
    $balance = '';
    $apiKey = get_env_variable('RAZORPAY_API_LIVE_KEY');
    $apiSecret = get_env_variable('RAZORPAY_API_LIVE_SECRET');
    $api = new Api($apiKey, $apiSecret);
    try {
        $response = $api->request->request('GET', 'balance');
        if ($response && ($response['balance'] != 0 || $response['balance'] != '')) {
            $balance = $response['balance'] / 100;
        }
    } catch (\Razorpay\Api\Errors\Error $e) {
    }

    return $balance;
}

function getCashfreeBalance()
{
    $balance = 0;
    $client = new Client();
    $authorizeApiUrl = "https://payout-api.cashfree.com/payout/v1/authorize";
    //$authorizeApiUrl = "https://payout-gamma.cashfree.com/payout/v1/authorize";
    $authorizeResponse = $client->request('POST', $authorizeApiUrl, [
        'headers' => [
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'X-Client-Id' => get_env_variable('CASHFREE_CLIENTID'),
            'X-Client-Secret' => get_env_variable('CASHFREE_CLIENTSECRET'),
            // 'X-Client-Id' => get_env_variable('CASHFREE_TEST_CLIENTID'),
            // 'X-Client-Secret' => get_env_variable('CASHFREE_TEST_CLIENTSECRET'),
        ],
        'http_errors' => true
    ]);
    $authorizeContentContent = json_decode($authorizeResponse->getBody()->getContents());
    $authorizeToken = $authorizeContentContent->data->token ?? '';
    if ($authorizeToken != '') {
        $apiUrl = 'https://payout-api.cashfree.com/payout/v1/getBalance';
        //$apiUrl = 'https://payout-gamma.cashfree.com/payout/v1/getBalance';
        $balanceResponse = $client->request('GET', $apiUrl, [
            'headers' => [
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'Authorization' => 'Bearer ' . $authorizeToken,
            ],
            'http_errors' => true
        ]);
        $balanceContent = json_decode($balanceResponse->getBody()->getContents());
        $balance = $balanceContent->data->availableBalance ?? 0;
    }

    return $balance;
}

function getMinuteDifference($paymentDate)
{

    //Payment creation and current date time duration greater than 20 min then make its status as failed
    $currentDateTime = Carbon::now()->setTimezone('Asia/Kolkata');
    $paymentDateTime = Carbon::parse($paymentDate);
    $minutesDiff = $currentDateTime->diffInMinutes($paymentDateTime);

    return $minutesDiff;
}

function getRoles()
{
    $roles = Role::select('id', 'name')->where('id', '!=', 1)->get();

    return $roles;
}

function hasPermission($permission)
{
    $permissions = is_array($permission)
        ? $permission
        : explode('|', $permission);
    if (!app('auth')->guard('admin_web')->user()->canany($permissions)) {
        throw UnauthorizedException::forPermissions($permissions);
    }
}

function validateRc($value)
{
    $uniqueId = substr(uniqid(), -10);
    $uniqueId = "velrider_rc_" . $uniqueId;
    $clientId = get_env_variable('CASHFREE_CLIENTID');
    $clientSecret = get_env_variable('CASHFREE_CLIENTSECRET');
    $apiUrl = config('global_values.cashfree_verification_live_url') . 'verification/vehicle-rc';
    // $apiUrl = config('global_values.cashfree_verification_test_url').'verification/vehicle-rc';
    // $clientId = get_env_variable('CASHFREE_TEST_CLIENTID');
    // $clientSecret = get_env_variable('CASHFREE_TEST_CLIENTSECRET');
    $rcResponseData = '';
    $client = new Client();
    try {
        $rcResponse = $client->request('POST', $apiUrl, [
            'headers' => [
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'x-client-id' => $clientId,
                'x-client-secret' => $clientSecret,
            ],
            'json' => [
                'verification_id' => $uniqueId,
                'vehicle_number' => $value,
            ],
            'http_errors' => false
        ]);
        $rcContent = $rcResponse->getBody()->getContents();
        $rcResponseData = json_decode($rcContent, true);
    } catch (\Exception $e) {
    }
    return $rcResponseData;

}

function getDistanceInKm($lat1, $lon1, $lat2, $lon2, $unit = 'K')
{
    $theta = $lon1 - $lon2;
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    $dist = acos($dist);
    $dist = rad2deg($dist);
    $miles = $dist * 60 * 1.1515;
    $unit = strtoupper($unit);

    if ($unit == "K") {
        return ($miles * 1.609344);
    } else {
        return $miles;
    }
}

function getRentalPrice($rentalPrice, $vehicleId = NULL)
{
    $updatedRentalPrice = $rentalPrice;
    // NEW CODE
    $vehicleOffer = OfferDate::first();
    if (isset($vehicleOffer) && $vehicleOffer != '') {
        $offerPercent = isset($vehicleOffer->vehicle_offer_price) ? $vehicleOffer->vehicle_offer_price : 0;
        $vehicleOfferStartDate = isset($vehicleOffer->vehicle_offer_start_date) ? $vehicleOffer->vehicle_offer_start_date : '';
        $vehicleOfferEndDate = isset($vehicleOffer->vehicle_offer_end_date) ? $vehicleOffer->vehicle_offer_end_date : '';
        if ($vehicleOfferStartDate != '' && $vehicleOfferEndDate != '' && $offerPercent < 100 && now()->between($vehicleOfferStartDate, $vehicleOfferEndDate)) {
            if ($offerPercent != 0) {
                if ($offerPercent > 0) {
                    $discountAmount = ($updatedRentalPrice * $offerPercent) / 100;
                    $updatedRentalPrice -= $discountAmount;
                } elseif ($offerPercent < 0) {
                    $offerPercent = abs($offerPercent);
                    $discountAmount = ($updatedRentalPrice * $offerPercent) / 100;
                    $updatedRentalPrice += $discountAmount;
                }
            }
        }
    }

    return $updatedRentalPrice;
}

function getHostAddedRentalPrice($vehicleId)
{
    $rentalPrice = 0;
    $checkVehiclePrice = VehiclePriceDetail::where('vehicle_id', $vehicleId)->get();
    if (isset($checkVehiclePrice) && is_countable($checkVehiclePrice) && count($checkVehiclePrice) > 0) {
        $rentalPrice = (float) $checkVehiclePrice[0]->rental_price;
    }

    return $rentalPrice;
}

function calculateKmLimit($tripDurationHours, $vehicleType = null, $vehicleId = null)
{
    // Auto-detect vehicle type from vehicleId if not provided
    if ($vehicleType === null && $vehicleId !== null) {
        try {
            $vehicle = Vehicle::where('vehicle_id', $vehicleId)->first();
            if ($vehicle) {
                $vehicleType = $vehicle->model->category->vehicleType->name ?? null;
            }
        } catch (\Exception $e) {
            \Log::error("calculateKmLimit: Error fetching vehicle type for vehicleId: " . $vehicleId . " - " . $e->getMessage());
        }
    }

    if ($tripDurationHours < 8) {
        $kmLimit = 50;
    } elseif ($tripDurationHours < 12) {
        $kmLimit = 100;
    } elseif ($tripDurationHours < 24) {
        $kmLimit = 220;
    } else {
        if ($tripDurationHours == 24) {
            $kmLimit = 300;
        } else {
            $kmLimit = intval((300 / 24) * $tripDurationHours);
        }
    }

    \Log::info("calculateKmLimit => Duration: {$tripDurationHours}hrs, VehicleType: {$vehicleType}, VehicleId: {$vehicleId}, BaseKmLimit: {$kmLimit}");

    // Return km limit divided by 3 for Bike and Scooter vehicle type
    if ($vehicleType !== null && (strtolower($vehicleType) === 'bike' || strtolower($vehicleType) === 'scooter')) {
        $reducedLimit = intval($kmLimit / 3);
        \Log::info("calculateKmLimit => Bike/Scooter detected, reduced KmLimit: {$reducedLimit}");
        return $reducedLimit;
    }

    return $kmLimit;
}

function calculateTripAmount($rentalPrice, $tripHours, $vehicleId = NULL)
{
    $checkVehiclePrice = VehiclePriceDetail::where('vehicle_id', $vehicleId)->where('is_show', 1)->get();
    if (isset($checkVehiclePrice) && is_countable($checkVehiclePrice) && count($checkVehiclePrice) > 0) {
        $rentalPrice = (float) $checkVehiclePrice[0]->rental_price;
        $minTripHoursRule = VehiclePriceDetail::where('vehicle_id', $vehicleId)->where('rate', '!=', 0)->where('is_show', 1)->orderBy('hours')->first();
        if ($tripHours < $minTripHoursRule->hours) {
            $tripHours = $minTripHoursRule->hours;
        }
        $rules = VehiclePriceDetail::where('vehicle_id', $vehicleId)->where('rate', '!=', 0)->where('is_show', 1)->orderBy('hours', 'desc')->get()->toArray();
        $multiplier = 1;
        $hours = $minTripHoursRule->hours;
        foreach ($rules as $rule) {
            if ($tripHours >= $rule['hours']) {
                $multiplier = $rule['multiplier'];
                $hours = $rule['hours'];
                break;
            }
        }
        $finalAmount = (($multiplier * $rentalPrice) / $hours) * $tripHours;
        $finalAmount = round($finalAmount, 2);
    } else {
        $rentalPrice = (float) $rentalPrice;
        $minTripHoursRule = TripAmountCalculationRule::orderBy('hours')->first();
        if ($tripHours < $minTripHoursRule->hours) {
            $tripHours = $minTripHoursRule->hours;
        }
        $rules = TripAmountCalculationRule::orderBy('hours', 'desc')->get()->toArray();
        $multiplier = 1;
        $hours = $minTripHoursRule->hours;
        foreach ($rules as $rule) {
            if ($tripHours >= $rule['hours']) {
                $multiplier = $rule['multiplier'];
                $hours = $rule['hours'];
                break;
            }
        }
        $finalAmount = (($multiplier * $rentalPrice) / $hours) * $tripHours;
        $finalAmount = round($finalAmount, 2);
    }

    return $finalAmount;
}

function compareVersions($version1, $version2)
{
    $v1Parts = explode('.', $version1);
    $v2Parts = explode('.', $version2);

    $length = max(count($v1Parts), count($v2Parts));

    for ($i = 0; $i < $length; $i++) {
        $v1 = isset($v1Parts[$i]) ? (int) $v1Parts[$i] : 0;
        $v2 = isset($v2Parts[$i]) ? (int) $v2Parts[$i] : 0;

        if ($v1 > $v2) {
            return 1;
        } else if ($v1 < $v2) {
            return -1;
        }
    }

    return 0;
}

function getTaxableAmt($bookingId)
{
    $taxableAmt = 0;
    $rBooking = RentalBooking::where('booking_id', $bookingId)->first();
    $calculationDetails = BookingTransaction::where(['booking_id' => $bookingId])->get();
    if (is_countable($calculationDetails) && count($calculationDetails) > 0) {
        foreach ($calculationDetails as $key => $value) {
            if ($value->type == 'new_booking') {
                if ($value->paid) {
                    $taxableAmt += $value->trip_amount_to_pay ?? 0;
                }
            } elseif ($value->type == 'extension') {
                if ($value->paid) {
                    $taxableAmt += $value->trip_amount_to_pay ?? 0;
                }
            } elseif ($value->type == 'completion') {
                if ($value->paid) {
                    $taxableAmt += $value->amount_to_pay ?? 0;
                    $taxableAmt += $value->refundable_deposit_used ?? 0;
                    $taxableAmt -= $value->tax_amt;
                }
            } elseif ($value->type == 'penalty') {
                if ($value->paid) {
                    $taxableAmt += $value->total_amount ?? 0;
                }
            }
        }
    }

    $taxableAmt = round($taxableAmt, 2);

    return $taxableAmt;
}

function getConvenienceAmt($bookingId, $type)
{

    $cAmount = getConvenienceDetails($bookingId, $type);

    return $cAmount;
}

function getConvenienceDetails($bookingId, $type)
{
    $convenienceFee = $newConvenienceFees = $gstAmt = 0;
    $rBooking = RentalBooking::where('booking_id', $bookingId)->first();
    $calculationDetails = BookingTransaction::where(['booking_id' => $bookingId])->get();
    if (is_countable($calculationDetails) && count($calculationDetails) > 0) {
        foreach ($calculationDetails as $key => $value) {
            if ($value->type == 'new_booking') {
                if ($value->paid) {
                    $convenienceFee += $value->convenience_fee ?? 0;
                }
            } elseif ($value->type == 'extension') {
                if ($value->paid) {
                    $convenienceFee += $value->convenience_fee ?? 0;
                }
            }
        }
    }
    $newConvenienceFees = $convenienceFee / (1 + (18 / 100));
    $newConvenienceFees = round($newConvenienceFees, 2);
    $gstAmt = $convenienceFee - $newConvenienceFees;
    $gstAmt = round($gstAmt, 2);

    if ($type == 'amt') {
        return $newConvenienceFees;
    } elseif ($type == 'gst') {
        return $gstAmt;
    }
}

function getConvenienceGst($bookingId, $type)
{
    $convenienceGst = getConvenienceDetails($bookingId, $type);

    return $convenienceGst;
}

function getInvoiceDate($bookingId)
{
    $invoiceDateTime = '';
    $rBooking = RentalBooking::where('booking_id', $bookingId)->first();
    $calculationDetails = BookingTransaction::where(['booking_id' => $bookingId])->get();
    if (is_countable($calculationDetails) && count($calculationDetails) > 0) {
        foreach ($calculationDetails as $key => $value) {
            if ($value->type == 'completion') {
                if ($value->paid) {
                    $invoiceDateTime = $value->timestamp ?? '';
                }
            }
        }
        if ($invoiceDateTime == '') {
            foreach ($calculationDetails as $key => $value) {
                if ($value->type == 'new_booking') {
                    if ($value->paid) {
                        $invoiceDateTime = $value->timestamp ?? '';
                    }
                }
            }
        }
    }

    $invoiceDateTime = $invoiceDateTime != '' ? date('d-m-Y', strtotime($invoiceDateTime)) : '';
    return $invoiceDateTime;
}

function getPaymentMode($bookingId)
{
    $paymentMode = '';
    $rBooking = AdminRentalBooking::with('payments')->where('booking_id', $bookingId)->first();
    if ($rBooking != '' && is_countable($rBooking->payments) && count($rBooking->payments) > 0) {
        $payment = Payment::where('booking_id', $bookingId)->where('payment_type', 'new_booking')->first();
        if ($payment) {
            $paymentMode = $payment->payment_mode;
        }
    }
    return $paymentMode;
}

function generateCustomerPdf($customerId, $bookingId)
{
    $customer = Customer::where('customer_id', $customerId)->first();
    $name = '';
    $ownerName = 'Shailesh Car & Bikes Pvt. Ltd.';
    $name .= $customer->firstname ?? '';
    $name .= ' ' . $customer->lastname ?? '';
    $bookingId = $bookingId;
    $vehicleRegistrationNo = '-';
    $bookingStartDate = '';
    $booking = RentalBooking::select('booking_id', 'vehicle_id', 'start_datetime', 'pickup_date')->where('booking_id', $bookingId)->first();
    if ($booking) {
        $vehicle = Vehicle::where('vehicle_id', $booking->vehicle_id)->first();
        $vehicleRegistrationNo = $vehicle->license_plate ?? '-';
        $carEligibility = CarEligibility::with('carHost')->where('vehicle_id', $booking->vehicle_id)->first();
        if ($carEligibility && $carEligibility->carHost) {
            $ownerName .= $carEligibility->carHost->firstname ?? '';
            $ownerName .= ' ' . $carEligibility->carHost->lastname ?? '';
        }
        $bookingStartDate = $booking->start_datetime ? date('d-m-Y H:i', strtotime($booking->start_datetime)) : date('d-m-Y H:i', strtotime($booking->pickup_date));
    }
    $fileName = 'customer_agreements_' . $customerId . '_' . $bookingId . '.pdf';
    $path = public_path() . '/customer_aggrements/';
    $fullPath = $path . $fileName;
    if (file_exists($fullPath)) {
        unlink($fullPath); // delete existing file
    }
    $pdf = PDF::loadView('customer_aggrement', compact('name', 'bookingId', 'ownerName', 'bookingStartDate', 'vehicleRegistrationNo' /*, 'vehicleChassisNo'*/));
    $pdf->save($path . $fileName);
    //return $pdf->stream('customer_aggrement.pdf');
}

function generateRandomString($length = 10)
{
    return substr(str_shuffle(str_repeat($x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length / strlen($x)))), 1, $length);
}

if (!function_exists('get_env_variable')) {
    function get_env_variable($key, $default = null)
    {
        $variable = DB::table('env_variables')->where('key', $key)->first();
        return $variable ? $variable->value : $default;
    }
}

function getLocationDetails($vehicleId)
{
    $location = null;
    $vehicle = Vehicle::where('vehicle_id', $vehicleId)->first();
    if ($vehicle != '') {
        if ($vehicle->branch_id != NULL || $vehicle->branch_id != '') {
            $branch = Branch::where('branch_id', $vehicle->branch_id)->first();
            if ($branch != '') {
                $location['id'] = $branch->branch_id ?? '';
                $location['from'] = 1;
            }
        } else {
            $carEligibility = CarEligibility::where('vehicle_id', $vehicleId)->with('vehiclePickupLocation')->first();
            if ($carEligibility != '' && $carEligibility->vehiclePickupLocation) {
                $location['id'] = $carEligibility->car_host_pickup_location_id ?? '';
                $location['from'] = 2;
            }
        }
    }

    return $location;
}

function generateReferralCode($customerId, $customerName)
{
    $uniqueId = substr(uniqid(), -4);
    $code = $customerName . $customerId . $uniqueId;
    //$code = 'Vel_'.$customerId.'_'.$uniqueId;

    return $code;
}

function getAvailCoupons($startDate, $endDate, $customerId)
{
    $coupons = '';
    $sDate = date('Y-m-d H:i:s', strtotime($startDate));
    $eDate = date('Y-m-d H:i:s', strtotime($endDate));
    if ($sDate != NULL && $eDate != NULL) {
        $coupons = Coupon::select('id', 'code', 'type', 'is_show', 'valid_from', 'valid_to', 'percentage_discount', 'fixed_discount_amount', 'single_use_per_customer', 'one_time_use_among_all')
            ->where(['is_show' => 1, 'is_active' => 1, 'is_deleted' => 0])
            ->where('valid_from', '<=', $sDate)->where('valid_to', '>=', $eDate)
            ->get();
        $coupons = $coupons->filter(function ($item) {
            if (!now()->between($item->valid_from, $item->valid_to)) {
                return false;
            } else {
                return true;
            }
        })->values();
        $coupons = $coupons->filter(function ($item) use ($customerId) {
            $couponDiscount = 0;
            $rentalBooking = [];
            $singleUse = isset($item->single_use_per_customer) ? $item->single_use_per_customer : 0;
            $oneTimeUse = isset($item->one_time_use_among_all) ? $item->one_time_use_among_all : 0;
            $rentalBooking = '';
            if ($oneTimeUse == 1 || $singleUse == 1) {
                $couponCode = $item->code ?? '';
                $couponId = $item->id;
                if ($oneTimeUse == 1) {
                    $rentalBooking = RentalBooking::whereIn('status', ['confirmed', 'running', 'completed'])->get();
                }
                if ($singleUse == 1) {
                    if ($customerId != '') {
                        $rentalBooking = RentalBooking::whereIn('status', ['confirmed', 'running', 'completed'])->where('customer_id', $customerId)->get();
                    }
                }
                if ($couponCode != '' && is_countable($rentalBooking) && count($rentalBooking) > 0) {
                    foreach ($rentalBooking as $key => $value) {
                        //Check in booking_transaction table
                        $bookingTransaction = BookingTransaction::where('booking_id', $value->booking_id)->get();
                        if (is_countable($bookingTransaction) && count($bookingTransaction) > 0) {
                            foreach ($bookingTransaction as $k => $v) {
                                if ($v->coupon_code != '' && strtolower($v->coupon_code) == strtolower($couponCode) && $v->coupon_code_id != '' && $v->coupon_code_id == $couponId && $v->paid == 1) {
                                    $couponDiscount = 2;
                                    break;
                                }
                            }
                        }
                        if ($couponDiscount == 2) {
                            break;
                        }
                    }
                }
            }
            if ($couponDiscount == 2) { //2 means invalid
                return false;
            }
            return true;
        })->values();

        if (is_countable($coupons) && count($coupons) > 0) {
            foreach ($coupons as $key => $value) {
                $value->percentage_discount = (int) $value->percentage_discount;
                if (($value->percentage_discount == null || $value->percentage_discount == '' || $value->percentage_discount == 0) && isset($value->fixed_discount_amount)) {
                    $value->coupon_title = 'Fixed ' . (string) $value->fixed_discount_amount . ' Rs.';
                } elseif ($value->percentage_discount != null || $value->percentage_discount != '' || $value->percentage_discount != 0) {
                    $value->coupon_title = 'FLAT ' . (string) $value->percentage_discount . '% OFF';
                }
            }
        }
    }

    return $coupons;

}

//function getTransactionTaxable($bookingId, $type){
function getTransactionTaxable($transactionId)
{
    $taxableAmt = 0;
    //$calculationDetails = BookingTransaction::where(['booking_id' => $bookingId])->get();
    $calculationDetails = BookingTransaction::where(['id' => $transactionId])->first();
    //if(is_countable($calculationDetails) && count($calculationDetails) > 0){
    if ($calculationDetails != '') {
        //foreach($calculationDetails as $key => $value){
        if (strtolower($calculationDetails->type) == 'new_booking') {
            //if($value->paid){
            $taxableAmt += $calculationDetails->trip_amount_to_pay ?? 0;
            //}
        } elseif (strtolower($calculationDetails->type) == 'extension') {
            //if($value->paid){
            $taxableAmt += $calculationDetails->trip_amount_to_pay ?? 0;
            //}
        } elseif (strtolower($calculationDetails->type) == 'completion') {
            //if($value->paid){
            $taxableAmt += $calculationDetails->amount_to_pay ?? 0;
            $taxableAmt += $calculationDetails->refundable_deposit_used ?? 0;
            $taxableAmt -= $calculationDetails->tax_amt;
            //}
        } elseif (strtolower($calculationDetails->type) == 'penalty') {
            //if($value->paid){
            $taxableAmt += $calculationDetails->total_amount ?? 0;
            //}
        }
        //}
    }
    $taxableAmt = round($taxableAmt, 2);

    return $taxableAmt;
}

function getTransactionConvenienceFees($bookingId, $type)
{
    $convenienceFee = $newConvenienceFees = $gstAmt = $cFees = 0;
    $rBooking = RentalBooking::where('booking_id', $bookingId)->first();
    $calculationDetails = BookingTransaction::where(['booking_id' => $bookingId])->get();
    if (is_countable($calculationDetails) && count($calculationDetails) > 0) {
        foreach ($calculationDetails as $key => $value) {
            if ($type == 'new_booking') {
                // if($value->paid){
                $convenienceFee += $value->convenience_fee ?? 0;
                // }
            } elseif ($type == 'extension') {
                // if($value->paid){
                $convenienceFee += $value->convenience_fee ?? 0;
                // }
            }
        }
    }
    $newConvenienceFees = $convenienceFee / (1 + (18 / 100));
    $newConvenienceFees = round($newConvenienceFees, 2);
    $gstAmt = $convenienceFee - $newConvenienceFees;
    $gstAmt = round($gstAmt, 2);

    $cFees = $newConvenienceFees + $gstAmt;

    return $cFees;
}

function usedPaymentGateway($bokingId, $type, $razorpayId = NULL, $cashfreeId = NULL)
{
    $data['payment_gateway'] = NULL;
    $data['payment_gateway_charges'] = 0;

    $payment = Payment::select('booking_id', 'payment_type', 'razorpay_order_id', 'cashFree_order_id', 'payment_gateway_used', 'payment_gateway_charges')->where(['booking_id' => $bokingId, 'payment_type' => $type]);
    if ($razorpayId != NULL) {
        $payment = $payment->where('razorpay_order_id', $razorpayId);
    } elseif ($cashfreeId != NULL) {
        $payment = $payment->where('cashfree_order_id', $cashfreeId);
    }
    $payment = $payment->first();

    if ($payment != '') {
        $data['payment_gateway'] = strtoupper($payment->payment_gateway_used) ?? NULL;
        $data['payment_gateway_charges'] = $payment->payment_gateway_charges ?? 0;
    }

    return $data;
}

function getCancelDetails($bookingId)
{
    $refundPercent = $refundAmount = $diffInHours = 0;
    $details['refundPercent'] = 0;
    $details['refundAmount'] = 0;
    $details['diffInHours'] = 0;
    $rentalBooking = AdminRentalBooking::select('booking_id', 'pickup_date', 'total_cost', 'status')
        ->where('status', 'confirmed')
        ->where('pickup_date', '>', now()->format('Y-m-d H:i'))
        ->where('booking_id', $bookingId)
        ->first();
    if ($rentalBooking != '') {
        if ($rentalBooking->status == 'canceled') {
            $data['message'] = 'This booking is already Canceled';
            return response()->json($data);
        }
        $pickupDateTime = $rentalBooking->pickup_date;
        $finalPaidAmount = $rentalBooking->total_cost;
        $currentDateTime = Carbon::now()->setTimezone('Asia/Kolkata');
        $pickupDateTime = Carbon::parse($pickupDateTime);
        $diffInHours = $pickupDateTime->diffInHours($currentDateTime);
        if ($diffInHours > 48) {
            $refundPercent = 100;
        } elseif ($diffInHours > 24 && $diffInHours < 48) {
            $refundPercent = 50;
        } elseif ($diffInHours < 24) {
            $refundPercent = 0;
        }
        if ($refundPercent == 100) {
            $refundAmount = $finalPaidAmount;
        } elseif ($refundPercent == 50) {
            $refundAmount = $finalPaidAmount / 2;
        }
        $details['refundPercent'] = $refundPercent;
        $details['refundAmount'] = $refundAmount;
        $details['diffInHours'] = $diffInHours;

        return $details;
    }
}

function checkAvailabilityDates($availabilityCalendar, $startDate, $endDate)
{
    $displayMsg = '';
    $unavailabilityCalendar = json_decode($availabilityCalendar, true);
    if (is_countable($unavailabilityCalendar) && count($unavailabilityCalendar) > 0) {
        foreach ($unavailabilityCalendar as $period) {
            if (isset($period['start_date']) && isset($period['end_date'])) {
                $periodStartDate = normalizeDateTime($period['start_date']);
                $periodEndDate = normalizeDateTime($period['end_date']);
                // Check if the requested period overlaps with any unavailable period
                if (
                    ($startDate->between($periodStartDate, $periodEndDate) ||
                        $endDate->between($periodStartDate, $periodEndDate) ||
                        ($startDate <= $periodStartDate && $endDate >= $periodEndDate))
                ) {
                    //$displayMsg = 'The vehicle is unavailable from ' . $startDate->format('d-m-Y H:i:s') . ' to ' . $endDate->format('d-m-Y H:i:s') . '.';
                    $displayMsg = 'The vehicle is unavailable from ' . $periodStartDate . ' to ' . $periodEndDate . '.';
                    return $displayMsg;
                }
            }
        }
    }
}

function checkedBookedVehicele($vehicleId, $startDate, $endDate, $bookingGap, $bookingId = NULL, $isTestUser = 0)
{
    $checkedBookedVehicleMsg = '';
    $existingBookings = RentalBooking::where('vehicle_id', $vehicleId)
        ->whereIn('status', ['running', 'confirmed']);

    if ($bookingId != NULL) {
        $existingBookings = $existingBookings->where('booking_id', '!=', $bookingId);
    }

    // If the current user is a normal user (isTestUser = 0), exclude test bookings (is_test_booking = 1)
    // Test users can see all bookings, normal users ignore test bookings
    if ($isTestUser == 0) {
        $existingBookings = $existingBookings->where(function ($query) {
            $query->where('is_test_booking', '!=', '1')
                ->orWhereNull('is_test_booking');
        });
    }

    $existingBookings = $existingBookings->where(function ($query) use ($startDate, $endDate) {
        $query->whereBetween('pickup_date', [$startDate, $endDate])
            ->orWhereBetween('return_date', [$startDate, $endDate])
            ->orWhere(function ($query) use ($startDate, $endDate) {
                $query->where('pickup_date', '<', $startDate)
                    ->where('return_date', '>', $endDate);
            });
    })
        ->get();
    if ($existingBookings->isNotEmpty()) {
        $bookingPeriods = $existingBookings->map(function ($booking) use ($bookingGap) {
            return Carbon::parse($booking->pickup_date)->subMinutes($bookingGap)->format('d-m-Y H:i') . ' to ' . Carbon::parse($booking->return_date)->addMinutes($bookingGap)->format('d-m-Y H:i');
        })->implode(', ');

        // Suggest available periods (simplified logic for example purposes)
        $latestReturnDate = $existingBookings->max('return_date');
        $availableFrom = Carbon::parse($latestReturnDate)->addMinute($bookingGap)->format('d-m-Y H:i');
        $checkedBookedVehicleMsg = "The vehicle is already booked for the following periods: $bookingPeriods. You can book from $availableFrom onwards.";
    }

    return $checkedBookedVehicleMsg;
}

function checkedUserBookedVehicle($cId, $bookingGap, $startDate, $endDate)
{
    $existingBookingMsg = '';
    if ($cId != '') {
        $intervalExpression = "INTERVAL {$bookingGap} MINUTE";
        $existingBookingCustomer = RentalBooking::where('customer_id', $cId)
            ->whereIn('status', ['running', 'confirmed'])
            ->where(function ($query) use ($startDate, $endDate, $intervalExpression) {
                $query->whereBetween(DB::raw("DATE_SUB(pickup_date, {$intervalExpression})"), [$startDate, $endDate])
                    ->orWhereBetween(DB::raw("DATE_ADD(return_date, {$intervalExpression})"), [$startDate, $endDate])
                    ->orWhere(function ($query) use ($startDate, $endDate, $intervalExpression) {
                        $query->where(DB::raw("DATE_SUB(pickup_date, {$intervalExpression})"), '<', $startDate)
                            ->where(DB::raw("DATE_ADD(return_date, {$intervalExpression})"), '>', $endDate);
                    });
            })
            ->exists();
        if ($existingBookingCustomer) {
            $existingBookingMsg = 'You have already booked another Vehicle for this specified time period.';
        }
    }

    return $existingBookingMsg;
}

function checkVehicleStatus($vehicleId, $bookingId, $startDate, $endDate)
{
    $checkVehicleMsg = '';
    $existingBooking = RentalBooking::where('vehicle_id', $vehicleId)->whereIn('status', ['running', 'confirmed'])
        ->where('booking_id', '!=', $bookingId)
        ->where(function ($query) use ($startDate, $endDate) {
            $query->whereBetween('pickup_date', [$startDate, $endDate])
                ->orWhereBetween('return_date', [$startDate, $endDate]);
        })
        ->exists();
    if ($existingBooking) {
        $checkVehicleMsg = 'The vehicle is already booked for the specified time period.';
    }

    return $checkVehicleMsg;
}

function checkRateWIthModelRate($hour, $rate, $vehicleModelId)
{
    $data['status'] = false;
    $data['message'] = '';
    $minPrice = $maxPrice = 0;
    if ($vehicleModelId != '') {
        if ($hour != '' && $rate != '' && $rate != 0) {
            $minPrice = VehicleModelPriceDetail::select('rate')->where(['vehicle_model_id' => $vehicleModelId, 'type' => 1, 'hours' => $hour])->first();
            $maxPrice = VehicleModelPriceDetail::select('rate')->where(['vehicle_model_id' => $vehicleModelId, 'type' => 2, 'hours' => $hour])->first();
            if ($minPrice != '' && $maxPrice != '') {
                $minPrice = (float) $minPrice->rate;
                $maxPrice = (float) $maxPrice->rate;
                if ($rate >= $minPrice && $rate <= $maxPrice) {
                    $data['status'] = true;
                } else {
                    $duration = $hour <= 24 ? $hour . ' Hours' : ($hour / 24) . ' Days';
                    $data['status'] = false;
                    $data['message'] = $duration . " Rate must be between " . $minPrice . " and " . $maxPrice;
                }
            } else {
                $data['status'] = true;
            }
        } else {
            $data['status'] = true;
        }
    } else {
        $data['status'] = true;
    }

    return $data;
}

function getRateWithModelRate($hour, $vehicleModelId)
{
    $data['minPrice'] = 0;
    $data['maxPrice'] = 0;
    $minPrice = $maxPrice = 0;
    if ($vehicleModelId != '' && $hour != '') {
        $minPrice = VehicleModelPriceDetail::select('rate')->where(['vehicle_model_id' => $vehicleModelId, 'type' => 1, 'hours' => $hour])->first();
        $maxPrice = VehicleModelPriceDetail::select('rate')->where(['vehicle_model_id' => $vehicleModelId, 'type' => 2, 'hours' => $hour])->first();
        if ($minPrice != '' && $maxPrice != '') {
            $minPrice = (float) $minPrice->rate;
            $maxPrice = (float) $maxPrice->rate;
            $data['minPrice'] = $minPrice;
            $data['maxPrice'] = $maxPrice;
        }
    }

    return $data;
}

function getModelKmDetail($vehicleModelId)
{
    $minKmLimit = $maxKmLimit = $middleKmLimit = 0;
    $vehicleModel = VehicleModel::where('model_id', $vehicleModelId)->first();
    if ($vehicleModel) {
        $minKmLimit = $vehicleModel->min_km_limit ?? 0;
        $maxKmLimit = $vehicleModel->max_km_limit ?? 0;
    }
    $data['min_km_limit'] = $minKmLimit;
    $data['max_km_limit'] = $maxKmLimit;
    if ($maxKmLimit > $minKmLimit) {
        $middleKmLimit = $maxKmLimit - $minKmLimit;
        $middleKmLimit = $middleKmLimit / 2;
    }
    $middleKmLimit = $minKmLimit + $middleKmLimit;
    $data['middle_km_limit'] = $middleKmLimit;

    return $data;
}

function cleanNameParts($name)
{
    // Normalize name: Convert to lowercase and remove extra spaces
    $name = strtolower(trim($name));
    // Common suffixes to be removed
    $commonSuffixes = ['bhai', 'sinh', 'singh', 'kumar', 'lal', 'das', 'dev', 'rao', 'chand', 'prasad', 'reddy', 'varma', 'nath'];
    // Split the name into words
    $parts = preg_split('/\s+/', $name);
    // Process each part to remove suffixes
    foreach ($parts as &$part) {
        foreach ($commonSuffixes as $suffix) {
            if (str_ends_with($part, $suffix)) {
                $part = substr($part, 0, -strlen($suffix)); // Remove the suffix
            }
        }
    }
    // Remove empty values and return cleaned parts
    return array_values(array_filter(array_map('trim', $parts)));
}

function isSimilar($name1, $name2, $threshold = 2)
{
    return levenshtein($name1, $name2) <= $threshold; // Allow minor typos (threshold = 2 edits)
}

function subscribeToTopic($deviceTokens, $topic)
{
    $url = "https://iid.googleapis.com/iid/v1:batchAdd";
    $accessToken = getDynamicAccessToken();
    //try{
    if ($accessToken != '') {
        $jsonResponse = [
            "to" => "/topics/" . $topic,
            "registration_tokens" => $deviceTokens // Array of device tokens
        ];
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
            'access_token_auth' => 'true',
        ];
        $client = new Client();
        $response = $client->post($url, [
            'headers' => $headers,
            'json' => $jsonResponse,
            'http_errors' => false
        ]);
        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();
        $responseBody = json_decode($body, true);

        return $responseBody;
    }
    //}catch (\Exception $e) {} 
}

function unsubscribeToTopic($deviceTokens, $topic)
{
    $url = "https://iid.googleapis.com/iid/v1:batchRemove";
    $accessToken = getDynamicAccessToken();
    try {
        if ($accessToken != '') {
            $jsonResponse = [
                "to" => "/topics/" . $topic,
                "registration_tokens" => $deviceTokens // Array of device tokens
            ];
            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,
                'access_token_auth' => 'true',
            ];
            $client = new Client();
            $response = $client->post($url, [
                'headers' => $headers,
                'json' => $jsonResponse,
                'http_errors' => false
            ]);
            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();
            $responseBody = json_decode($body, true);

            return $responseBody;
        }
    } catch (\Exception $e) {
    }
}

function getMaxRate($modelId, $hours, $type)
{
    $getMaxPrice = VehicleModelPriceDetail::select('id', 'rate')->where(['vehicle_model_id' => $modelId, 'hours' => $hours, 'type' => $type])->first();
    if ($getMaxPrice != '') {
        $getMaxPrice = $getMaxPrice->rate;
    } else {
        $getMaxPrice = 0;
    }
    return $getMaxPrice;
}

function getMiddlePrice($minPrice, $maxPrice)
{
    $differenceAmt = $middleVal = 0;
    if ($maxPrice > $minPrice) {
        $differenceAmt = $maxPrice - $minPrice;
        $differenceAmt = $differenceAmt / 2;
    }

    $middleVal = $minPrice + $differenceAmt;

    return $middleVal;
}

function processRentalBookings($bookings)
{
    $minTripHoursRule = TripAmountCalculationRule::orderBy('hours')->first();
    $rules = TripAmountCalculationRule::orderBy('hours', 'desc')->get()->toArray();
    foreach ($bookings as $value) {
        $b2bb2c = 'B2C';
        $gstPercent = 5;
        $cGSTsGST = $cGSTsGSTPercent = '';
        $iGST = 0;
        $value->created_date = $value->created_at ? date('d/m/Y', strtotime($value->created_at)) : '-';
        if ($value->customer->gst_number != null) {
            $b2bb2c = "B2B";
            $gstPercent = 12;
        }
        $value->b2bb2c = $b2bb2c;
        $value->gstpercent = $gstPercent;

        if (is_countable($value->price_summary) && count($value->price_summary) > 0) {
            $taxVal = $vehicleCommissionAmt = $vehicleCommissionTaxAmt = $vehicleComm = 0;
            $bTransactions = BookingTransaction::where('booking_id', $value->booking_id)->get();

            foreach ($bTransactions as $v) {
                if ($v->tax_amt && $v->paid == 1) {
                    $taxVal += $v->tax_amt - $v->vehicle_commission_tax_amt;
                    $vehicleCommissionAmt += $v->vehicle_commission_amount;
                    $vehicleCommissionTaxAmt += $v->vehicle_commission_tax_amt;
                    if ($v->type !== 'penalty') {
                        $vehicleComm += $v->vehicle_commission_amount;
                    }
                }
            }

            $cFeesAmt = getConvenienceAmt($value->booking_id, 'amt');
            $cFeesGST = getConvenienceGst($value->booking_id, 'gst');
            $taxableAmt = getTaxableAmt($value->booking_id) - $vehicleComm;

            $value->convenienceFeesAmount = $cFeesAmt;
            $value->convenienceFeesGST = $cFeesGST;
            $value->taxableAmount = round($taxableAmt, 2);
            $value->vehicleCommissionAmt = round($vehicleCommissionAmt, 2);
            $value->vehicleCommissionTaxAmt = round($vehicleCommissionTaxAmt, 2);
            $value->finalAmt = round(($cFeesAmt + $cFeesGST + $taxableAmt), 2);
            $value->invoiceDate = getInvoiceDate($value->booking_id);
            $value->tax = $taxVal;
            $value->paymentMode = getPaymentMode($value->booking_id);
        } else {
            $value->cDetails = '';
        }
        // Trip Amount Calculation
        $tripHours = $value->rental_duration_minutes ? $value->rental_duration_minutes / 60 : 0;
        if ($minTripHoursRule && $tripHours < $minTripHoursRule->hours) {
            $tripHours = $minTripHoursRule->hours;
        }

        $multiplier = 1;
        $hours = $minTripHoursRule->hours ?? 0;
        foreach ($rules as $rule) {
            if ($tripHours >= $rule['hours']) {
                $multiplier = $rule['multiplier'];
                $hours = $rule['hours'];
                break;
            }
        }

        $value->tripDurationInHours = $tripHours;
        $value->multiplier = $multiplier;
        $value->hours = $hours;

        $lastAmt = 0;
        $lastAmt = $value->finalAmt;
        //TAXES
        if ($value->customer && $value->tax != 0) {
            $gst = '';
            $lastAmt = (float) $lastAmt + (float) $value->tax + (float) $value->vehicleCommissionTaxAmt + (float) $value->vehicleCommissionAmt;
            $lastAmt = round($lastAmt);
            if ($value->customer->gst_number) {
                $gst = str_starts_with($value->customer->gst_number, '24');
            }
            if ($value->customer->gst_number == null || $gst && $value->tax != 0) {
                $tax = (float) $value->tax;
                $tax = round($tax, 2);
                $gstTax = $tax / 2;
                $cGSTsGST = (float) $gstTax;
                $cGSTsGST = round($gstTax, 2);
                $cGSTsGSTPercent = $gstPercent / 2;
                $cGSTsGST = $cGSTsGST . "(" . $cGSTsGSTPercent . "%)";
            } else if (!$gst && $value->tax != 0) {
                $iGST = (float) $value->tax;
                $iGST = round($iGST, 2);
                $cGSTsGSTPercent = $gstPercent;
                $iGST = $iGST . "(" . $cGSTsGSTPercent . "%)";
            }
        }

        $value->cgst_sgst = $cGSTsGST;
        $value->cgst_sgst_percent = $cGSTsGSTPercent;
        $value->iGST = $iGST;
        $value->total_value = $lastAmt;
        $finalPaidAmt = getPaidFinalAmtSum($value->booking_id);
        $value->total_cost = $finalPaidAmt;

        $value->makeHidden([
            'initial_vehicle_id',
            'location_id',
            'location_from',
            'amount_paid',
            'rental_type',
            'penalty_details',
            'calculation_details',
            'data_json',
            'start_datetime',
            'end_datetime',
            'start_images',
            'end_images',
            'price_summary',
            'admin_button_visibility',
            'refund'
        ]);
        if ($value->customer) {
            $value->customer->makeHidden([
                'profile_picture_url',
                'billing_address',
                'business_name',
                'shipping_address',
                'is_deleted',
                'is_blocked',
                'device_token',
                'device_id',
                'gauth_id',
                'gauth_type',
                'email_verified_at',
                'is_test_user',
                'is_guest_user',
                'my_referral_code',
                'used_referral_code',
                'account_holder_name',
                'bank_name',
                'branch_name',
                'city',
                'account_no',
                'ifsc_code',
                'nick_name',
                'registered_via',
                'documents',
                'passbook_image_url'
            ]);
        }
        if ($value->vehicle) {
            $value->vehicle->makeHidden([
                'availability',
                'extra_km_rate',
                'extra_hour_rate',
                'availability_calendar',
                'publish',
                'chassis_no',
                'cutout_image',
                'banner_image',
                'banner_images',
                'regular_images',
                'rating',
                'total_rating',
                'trip_count',
                'location'
            ]);
        }

    }

    return $bookings;
}

function getStartOtpText($bookingId)
{
    $startOtpText = '';
    $startJourneyOtpStatus = $vehicleTypeStatus = false;
    $booking = AdminRentalBooking::where('booking_id', $bookingId)->first();
    $checkVehicleType = CustomerDocument::where(['customer_id' => $booking->customer_id, 'document_type' => 'dl', 'is_approved' => 'approved'])->first();
    $vehicleType = $checkVehicleType->vehicle_type ?? '';
    $vehicleType = explode('/', $vehicleType);
    $selectedVehicleType = $booking->vehicle->model->category->vehicleType->name;
    $selectedVehicleType = strtolower($selectedVehicleType);
    if (in_array($selectedVehicleType, $vehicleType)) {
        $vehicleTypeStatus = true;
    }

    if ($booking != '') {
        $currentDate = Carbon::now()->setTimezone('Asia/Kolkata');
        $pickupDate = Carbon::parse($booking->pickup_date);
        $returnDate = Carbon::parse($booking->return_date);
        $adjustedPickupDate = $pickupDate->copy()->subMinutes(30);
        $adjustedReturnDate = $returnDate->copy();
        if ($currentDate >= $adjustedPickupDate && $currentDate <= $adjustedReturnDate) {
            $startJourneyOtpStatus = true;
        }
        if (strtolower($booking->status) != 'confirmed') {
            $startOtpText = "Start OTP can't generate because booking status is not in 'Confirmed' state";
        }
        // elseif($booking->customer->email_verified_at == NULL){
        //     $startOtpText = "Customer's Email is not Verified";
        // }
        elseif (strtolower($booking->customer->documents['dl']) != 'approved') {
            $startOtpText = "Customer's DL is not verified";
        } elseif (strtolower($booking->customer->documents['govtid']) != 'approved') {
            $startOtpText = "Customer's GOVT ID is not verified";
        } elseif (!$startJourneyOtpStatus) {
            $startOtpText = "Start OTP cannot be generated because the difference between the current time and the customer's booking pickup time is more than 30 minutes.";
        } elseif (!$vehicleTypeStatus) {
            $startOtpText = "Vehicle type is not matching with the customer's DL vehicle type.";
        }
    }
    return $startOtpText;
}

function getEndOtpText($bookingId)
{
    $endOtpText = '';
    $booking = AdminRentalBooking::select('booking_id', 'status', 'customer_id')->where('booking_id', $bookingId)->first();
    if ($booking != '') {
        // Check if previous booking has any penalties are remaining to paid or not
        $duePenalties = false;
        $getBooking = AdminRentalBooking::where('customer_id', $booking->customer_id)->get();
        if (isset($getBooking) && is_countable($getBooking) && count($getBooking) > 0) {
            foreach ($getBooking as $key => $val) {
                $checkOtherBookingsDuePenalties = BookingTransaction::where(['booking_id' => $val->booking_id, 'type' => 'penalty', 'paid' => 0])
                    ->where('total_amount', '>', 0)
                    ->where('final_amount', '>', 0)
                    ->exists();
                if ($checkOtherBookingsDuePenalties) {
                    $duePenalties = true;
                    break;
                }
            }
        }
        if (strtolower($booking->status) != 'running') {
            $endOtpText = "End OTP can't be generate because the booking status is not in 'Running' state";
        } elseif ($duePenalties) {
            $endOtpText = "End OTP can't be generate because Customer has unpaid penalty dues.";
        }
    }
    return $endOtpText;
}

function getOtpText($bookingId)
{
    $otpText = '';
    $startJourneyOtpStatus = $vehicleTypeStatus = false;
    $booking = AdminRentalBooking::where('booking_id', $bookingId)->first();
    $checkVehicleType = CustomerDocument::where(['customer_id' => $booking->customer_id, 'document_type' => 'dl', 'is_approved' => 'approved'])->first();
    $vehicleType = $checkVehicleType->vehicle_type ?? '';
    $vehicleType = explode('/', $vehicleType);
    $selectedVehicleType = $booking->vehicle->model->category->vehicleType->name;
    $selectedVehicleType = strtolower($selectedVehicleType);
    if (in_array($selectedVehicleType, $vehicleType)) {
        $vehicleTypeStatus = true;
    }
    $currentDate = Carbon::now()->setTimezone('Asia/Kolkata');
    $pickupDate = Carbon::parse($booking->pickup_date);
    $returnDate = Carbon::parse($booking->return_date);
    $adjustedPickupDate = $pickupDate->copy()->subMinutes(30);
    $adjustedReturnDate = $returnDate->copy();
    if ($currentDate >= $adjustedPickupDate && $currentDate <= $adjustedReturnDate) {
        $startJourneyOtpStatus = true;
    }

    // Check if previous booking has any penalties are remaining to paid or not
    $duePenalties = false;
    $getBooking = AdminRentalBooking::where('customer_id', $booking->customer_id)->get();
    if (isset($getBooking) && is_countable($getBooking) && count($getBooking) > 0) {
        foreach ($getBooking as $key => $val) {
            $checkOtherBookingsDuePenalties = BookingTransaction::where(['booking_id' => $val->booking_id, 'type' => 'penalty', 'paid' => 0])
                ->where('total_amount', '>', 0)
                ->where('final_amount', '>', 0)
                ->exists();
            if ($checkOtherBookingsDuePenalties) {
                $duePenalties = true;
                break;
            }
        }
    }

    //if(strtolower($booking->status) != 'confirmed'){ // START OTP TEXT
    if (strtolower($booking->status) == 'pending' || strtolower($booking->status) == 'no show' || strtolower($booking->status) == 'canceled' || strtolower($booking->status) == 'failed') { // START OTP TEXT
        $otpText = "Start OTP can't generate because booking status is not in 'Confirmed' state";
    }
    // elseif(strtolower($booking->status) == 'confirmed' && $booking->customer->email_verified_at == NULL){
    //     $otpText = "Customer's Email is not Verified";
    // }
    elseif (strtolower($booking->status) == 'confirmed' && strtolower($booking->customer->documents['dl']) != 'approved') {
        $otpText = "Customer's DL is not verified";
    } elseif (strtolower($booking->status) == 'confirmed' && strtolower($booking->customer->documents['govtid']) != 'approved') {
        $otpText = "Customer's GOVT ID is not verified";
    } elseif (strtolower($booking->status) == 'confirmed' && !$startJourneyOtpStatus) {
        $otpText = "Start OTP cannot be generated because the difference between the current time and the customer's booking pickup time is more than 30 minutes.";
    } elseif (strtolower($booking->status) == 'confirmed' && !$vehicleTypeStatus) {
        $otpText = "Vehicle type is not matching with the customer's DL vehicle type.";
    }
    //if(strtolower($booking->status) != 'running'){ // END OTP TEXT
    elseif (strtolower($booking->status) == 'confirmed') {
        $otpText = "End OTP can't be generate because the booking status is not in 'Running' state";
    } elseif (strtolower($booking->status) == 'running' && $duePenalties) {
        $otpText = "End OTP can't be generate because Customer has unpaid penalty dues.";
    } elseif (strtolower($booking->status) == 'completed') {
        $otpText = "End OTP can't be generate because booking is completed.";
    }

    return $otpText;
}

function isHostNewUpdatedChanges($hostId)
{
    $newLocationChanges = $newImageChanges = $newFeatureChanges = false;
    $data = [];
    $carhostLocationIds = CarHostPickupLocation::where('car_hosts_id', $hostId)->pluck('id')->toArray();
    $carhostVehicleIds = CarEligibility::where('car_hosts_id', $hostId)->pluck('vehicle_id')->toArray();
    if (isset($carhostLocationIds) && is_countable($carhostLocationIds) && count($carhostLocationIds) > 0) {
        $carHostPickupLocationTempExists = CarHostPickupLocationTemp::whereIn('car_host_pickup_locations_id', $carhostLocationIds)->exists();
        if ($carHostPickupLocationTempExists) {
            $newLocationChanges = true;
        }
    }
    if (isset($carhostVehicleIds) && is_countable($carhostVehicleIds) && count($carhostVehicleIds) > 0) {
        $carHostVehicleFeatureTempExists = CarHostVehicleFeatureTemp::whereIn('vehicles_id', $carhostVehicleIds)->exists();
        if ($carHostVehicleFeatureTempExists) {
            $newFeatureChanges = true;
        }
        $carHostVehicleImageTempExists = CarHostVehicleImageTemp::whereIn('vehicles_id', $carhostVehicleIds)->exists();
        if ($carHostVehicleImageTempExists) {
            $newImageChanges = true;
        }
    }

    $data['newLocationChanges'] = $newLocationChanges;
    $data['newImageChanges'] = $newImageChanges;
    $data['newFeatureChanges'] = $newFeatureChanges;

    return $data;
}

function checkNameMatch($aadharName, $dlName)
{
    $aadharName = strtolower($aadharName);
    $dlName = strtolower($dlName);

    // NEW CODE 1
    $aadharNameCheck = strtolower(trim($aadharName));
    $aadharNameCheck = preg_split('/\s+/', $aadharNameCheck);
    $dlNameCheck = strtolower(trim($dlName));
    $dlNameCheck = preg_split('/\s+/', $dlNameCheck);
    // if (count($aadharNameCheck) < 2 || count($dlNameCheck) < 2) {
    //     return 0; // Ensure both names have at least first and last name
    // }
    // Clean and process both names
    $parts1 = cleanNameParts($aadharName);
    $parts2 = cleanNameParts($dlName);
    // if (count($parts1) < 2 || count($parts2) < 2) {
    //     return 0; // Ensure both names have at least first and last name
    // }
    // Extract first and last names
    $firstName1 = $parts1[0];
    $lastName1 = end($parts1);
    $firstName2 = $parts2[0];
    $lastName2 = end($parts2);
    // Extract middle names
    $middleNames1 = array_slice($parts1, 1, -1);
    $middleNames2 = array_slice($parts2, 1, -1);
    $middleNameStatus = false;
    if (!empty($middleNames1) || !empty($middleNames2)) {
        $middleNames1 = $middleNames1[0] ?? '';
        $middleNames2 = $middleNames2[0] ?? '';
        $middleNameStatus = true;
    }
    if ($middleNameStatus == true) {
        if (
            (isSimilar($firstName1, $firstName2) && isSimilar($lastName1, $lastName2)) ||
            (isSimilar($firstName1, $lastName2) && isSimilar($lastName1, $firstName2)) ||
            (isSimilar($middleNames1, $firstName2) || isSimilar($middleNames1, $lastName2)) ||
            (isSimilar($middleNames2, $firstName1) || isSimilar($middleNames2, $lastName1))
        ) {
            return 1;
        }
        return 0;
    } else {
        if (
            (isSimilar($firstName1, $firstName2) && isSimilar($lastName1, $lastName2)) || (isSimilar($firstName1, $lastName2) && isSimilar($lastName1, $firstName2))
        ) {
            return 1;
        }
        return 0;
    }
}

function normalizeDateTime($dateStr)
{
    // Remove redundant AM/PM if 24-hour format is used
    if (preg_match('/\b(0?[1-9]|1[0-2]):[0-5][0-9]\s?(AM|PM)/i', $dateStr)) {
        // It's 12-hour format with AM/PM
        return Carbon::createFromFormat('d-m-Y h:i A', $dateStr);
    } elseif (preg_match('/\b([01]?[0-9]|2[0-3]):[0-5][0-9]\s?(AM|PM)/i', $dateStr)) {
        // Mixed 24-hour + AM/PM — remove AM/PM
        $dateStr = preg_replace('/\s?(AM|PM)/i', '', $dateStr);
        return Carbon::createFromFormat('d-m-Y H:i', $dateStr);
    } else {
        // Assume pure 24-hour format
        return Carbon::createFromFormat('d-m-Y H:i', $dateStr);
    }
}

function checkVehicleInNightTime($vehicle, $startDate, $endDate)
{
    $allowToBook = true;
    if ($vehicle && $vehicle->vehicle_created_by == 2) { // If created by host
        if ($vehicle->vehicleEligibility && $vehicle->vehicleEligibility->night_time == 1) {
            // Check if either time is between 12:00 AM and 6:00 AM
            $startOfRange = Carbon::createFromTime(0, 0);
            $endOfRange = Carbon::createFromTime(6, 0);
            // Set same date as startDate
            $startRangeStart = $startOfRange->copy()->setDate($startDate->year, $startDate->month, $startDate->day);
            $startRangeEnd = $endOfRange->copy()->setDate($startDate->year, $startDate->month, $startDate->day);
            // Set same date as endDate
            $endRangeStart = $startOfRange->copy()->setDate($endDate->year, $endDate->month, $endDate->day);
            $endRangeEnd = $endOfRange->copy()->setDate($endDate->year, $endDate->month, $endDate->day);

            if ($startDate->between($startRangeStart, $startRangeEnd, true) || $endDate->between($endRangeStart, $endRangeEnd, true)) {
                $allowToBook = false;
            }
        }
    }
    return $allowToBook;
}

// function checkVehicleInNightTime($vehicle, $startDate, $endDate){
//     $allowToBook = true;
//     if ($vehicle && $vehicle->vehicle_created_by == 2) { // If created by host
//         if ($vehicle->vehicleEligibility && $vehicle->vehicleEligibility->night_time == 1) {
//             // Iterate each day between start and end to check
//             $currentDate = $startDate->copy()->startOfDay();
//             $lastDate = $endDate->copy()->startOfDay();
//             while ($currentDate->lte($lastDate)) {
//                 $nightStart = $currentDate->copy()->setTime(0, 0); // 12:00 AM
//                 $nightEnd = $currentDate->copy()->setTime(6, 0);   // 06:00 AM
//                 // If booking overlaps night window for this day
//                 if ($startDate->lt($nightEnd) && $endDate->gt($nightStart)) {
//                     $allowToBook = false;
//                     break;
//                 }
//                 $currentDate->addDay();
//             }
//         }
//     }
//     return $allowToBook;
// }

function getModelDepositDetail($vehicleModelId)
{
    $minDepositLimit = $maxDepositLimit = $middleDepositLimit = 0;
    $vehicleModel = VehicleModel::where('model_id', $vehicleModelId)->first();
    if ($vehicleModel) {
        $minDepositLimit = $vehicleModel->min_deposit_amount ?? 0;
        $maxDepositLimit = $vehicleModel->max_deposit_amount ?? 0;
    }
    $data['min_deposit_limit'] = $minDepositLimit;
    $data['max_deposit_limit'] = $maxDepositLimit;
    if ($maxDepositLimit > $minDepositLimit) {
        $middleDepositLimit = $maxDepositLimit - $minDepositLimit;
        $middleDepositLimit = $middleDepositLimit / 2;
    }
    $middleDepositLimit = $minDepositLimit + $middleDepositLimit;
    $data['middle_deposit_limit'] = $middleDepositLimit;

    return $data;
}

function getVehicleDetailStatuses($vehicleId)
{
    $isLocationChanges = $isImageChanges = $isFeatureChanges = $isVehicleDetailChanges = $isRcDetails = $isInsuranceDetails = $isPucDetails = $isPriceUpdated = false;
    $data = [];
    $hostVehicle = CarEligibility::where('vehicle_id', $vehicleId)->first();
    if (isset($hostVehicle) && $hostVehicle != '') {
        $hostPickupLocationTempExists = CarHostPickupLocationTemp::where('car_hosts_id', $hostVehicle->car_hosts_id)->exists();
        if ($hostPickupLocationTempExists) {
            $isLocationChanges = true;
        }
    }

    $hostVehicleFeatureTempExists = CarHostVehicleFeatureTemp::where('vehicles_id', $vehicleId)->exists();
    if ($hostVehicleFeatureTempExists) {
        $isFeatureChanges = true;
    }
    $hostVehicleImageTempExists = CarHostVehicleImageTemp::where('vehicles_id', $vehicleId)->exists();
    if ($hostVehicleImageTempExists) {
        $isImageChanges = true;
    }
    $hostVehicleDetailsUpdated = Vehicle::where('vehicle_id', $vehicleId)->where('is_host_updated', 1)->exists();
    if ($hostVehicleDetailsUpdated) {
        $isVehicleDetailChanges = true;
    }
    $isRcDetailsUpdated = VehicleDocumentTemp::where('vehicle_id', $vehicleId)->where('document_type', 'rc_doc')->exists();
    if ($isRcDetailsUpdated) {
        $isRcDetails = true;
    }
    $isInsuranceDetailsUpdated = VehicleDocumentTemp::where('vehicle_id', $vehicleId)->where('document_type', 'insurance_doc')->exists();
    if ($isInsuranceDetailsUpdated) {
        $isInsuranceDetails = true;
    }
    $isPucDetailsUpdated = VehicleDocumentTemp::where('vehicle_id', $vehicleId)->where('document_type', 'puc_doc')->exists();
    if ($isPucDetailsUpdated) {
        $isPucDetails = true;
    }
    $isVehiclePricesUpdated = VehiclePriceDetailTemp::where('vehicle_id', $vehicleId)->exists();
    if ($isVehiclePricesUpdated) {
        $isPriceUpdated = true;
    }

    $data['isLocationChanges'] = $isLocationChanges;
    $data['isImageChanges'] = $isImageChanges;
    $data['isFeatureChanges'] = $isFeatureChanges;
    $data['isVehicleDetailChanges'] = $isVehicleDetailChanges;
    $data['isRcDetails'] = $isRcDetails;
    $data['isInsuranceDetails'] = $isInsuranceDetails;
    $data['isPucDetails'] = $isPucDetails;
    $data['isPriceUpdated'] = $isPriceUpdated;

    return $data;
}

function getPaidFinalAmtSum($bookingId)
{
    $paidFinalAmountSum = 0;
    $transactions = BookingTransaction::where(['booking_id' => $bookingId])->get();
    if (isset($transactions) && is_countable($transactions) && count($transactions) > 0) {
        foreach ($transactions as $transaction) {
            if ($transaction->type === 'new_booking' && $transaction->paid) {
                $paidFinalAmountSum += $transaction->final_amount;
            }
            if ($transaction->type === 'extension' && $transaction->paid) {
                $paidFinalAmountSum += $transaction->final_amount;
            }
            if ($transaction->type === 'completion' && $transaction->paid) {
                $paidFinalAmountSum += $transaction->amount_to_pay;
            }
            if ($transaction->type === 'penalty' && $transaction->paid) {
                $paidFinalAmountSum += $transaction->total_amount;
            }
        }

        $paidFinalAmountSum = number_format($paidFinalAmountSum, 2);
    }

    return $paidFinalAmountSum;
}