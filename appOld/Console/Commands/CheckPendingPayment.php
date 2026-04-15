<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{RentalBooking, Payment, AdminPenalty};
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;
use Exception;
use GuzzleHttp\Client;

class CheckPendingPayment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:pending-payment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This Command will check if last 12 hours Pending Payments are paid or not based on that update tables';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $currentDateTime = Carbon::now()->setTimezone('Asia/Kolkata');
        $oneHourAgo = $currentDateTime->subHour();

        RentalBooking::where('return_date', '<', $oneHourAgo)
        ->where('status', 'confirmed')
        ->update(['status' => 'no show']);

        //NEW CODE
        $cutoffTime = Carbon::now()->subHour(12);
        $allPayments = Payment::where(['status' => 'pending', 'payment_env' => 'live'])->where('created_at', '>=', $cutoffTime)->get();
        if(is_countable($allPayments) && count($allPayments) > 0){
            foreach($allPayments as $key => $value){
                $paymentGateway = $value->payment_gateway_used;
                if(strtolower($paymentGateway) == 'cashfree'){
                    $cClientId = $cSecretId = $cUrl = '';
                    $orderId = $value->cashfree_order_id;
                    $cClientId = get_env_variable('CASHFREE_PAYMENT_LIVE_CLIENTID');
                    $cSecretId = get_env_variable('CASHFREE_PAYMENT_LIVE_CLIENTSECRET');
                    $cUrl = "https://api.cashfree.com/pg/orders";
                    $cashfreeApiVersion = '2023-08-01';
                    try {
                        $client = new Client();
                        $response = $client->request('GET', $cUrl . '/' . $orderId, [
                            'headers' => [
                                'Accept' => 'application/json',
                                'Content-Type' => 'application/json',
                                'x-api-version' => $cashfreeApiVersion,
                                'x-client-id' => $cClientId,
                                'x-client-secret' => $cSecretId,
                            ],
                        ]);
                        $body = $response->getBody()->getContents();
                        $responseData = json_decode($body, true);
                        if ($responseData && isset($responseData['order_amount'])) {
                            if($value->amount == $responseData['order_amount'] && strtolower($responseData['order_status']) == 'paid'){
                                $value->status = 'captured';
                                $value->save();
                                $rentalBooking = RentalBooking::where('booking_id', $value->booking_id)->first();
                                $rentalBooking->processCashfreePayment($value);
                                if(strtolower($value->payment_gateway_used) == 'cashfree' && $responseData['order_id'] != ''){
                                    $adminPenalty = AdminPenalty::where('is_paid', 0)->where('booking_id', $value->booking_id)->where('cashfree_order_id', $responseData['order_id'])->first();
                                    if($adminPenalty != ''){
                                        $adminPenalty->is_paid = 1;
                                        $adminPenalty->save();    
                                    }
                                }
                               // Log::error("Cashfree Order is paid successfully - ". $value->payment_id);
                            } else {
                               // Log::error("Your cashfree order is in Active Mode not yet completed - ". $value->payment_id);
                            }
                        }
                    } catch(Exception $e) { Log::error($e->getMessage()); } 
                }elseif(strtolower($paymentGateway) == 'razorpay'){
                    $rKey = get_env_variable('RAZORPAY_API_LIVE_KEY');
                    $rSecret = get_env_variable('RAZORPAY_API_LIVE_SECRET');
                    $api = new Api($rKey, $rSecret);
                    $orderId = $value->razorpay_order_id;
                    $orderStatus = [];
                    try {
                        $orderStatus = $api->order->fetch($orderId)->payments();
                    } catch (\Razorpay\Api\Errors\Error $e) {
                        Log::error($e->getMessage());
                    }
                    if(is_countable($orderStatus['items']) && count($orderStatus['items']) > 0){
                        $items = $orderStatus->toArray();  // Convert the collection to an array
                        $items = $items['items'] ?? [];  
                        $capturedOrAuthorized = array_filter($items, function($v) {
                            return !empty($v['status']) && in_array($v['status'], ['captured', 'authorized']) && !empty($v['id']);
                        });
                        if (!empty($capturedOrAuthorized)) {
                            $paymentData = reset($capturedOrAuthorized);
                            $value->status = 'captured';
                            $value->razorpay_payment_id = $paymentData['id'];
                            $value->save();
                            // $payment->update([
                            //     'status' => 'captured',
                            //     'razorpay_payment_id' => $paymentData['id']
                            // ]);
                            //$rentalBooking = RentalBooking::where('booking_id', $payment->booking_id)->first();
                            $rentalBooking = RentalBooking::where('booking_id', $value->booking_id)->first();
                            //$rentalBooking->processPayment($payment);
                            $rentalBooking->processPayment($value);

                            // if(strtolower($payment->payment_gateway_used) == 'razorpay' && $orderId != ''){
                            //     $adminPenalty = AdminPenalty::where('is_paid', 0)->where('booking_id', $payment->booking_id)->where('razorpay_order_id', $orderId)->first();
                            if(strtolower($value->payment_gateway_used) == 'razorpay' && $orderId != ''){
                                $adminPenalty = AdminPenalty::where('is_paid', 0)->where('booking_id', $value->booking_id)->where('razorpay_order_id', $orderId)->first();
                                if($adminPenalty != ''){
                                    $adminPenalty->is_paid = 1;
                                    $adminPenalty->save();    
                                }
                            }
                           // Log::error("Razorpay Order is paid successfully - ". $value->payment_id);
                        } else {
                           // Log::error("Your razorpay order not yet completed - ". $value->payment_id);
                        }
                    }
                }
            }
        }
            
        /*------------------------------*/
        // $rKey = getRazorpayKey();
        // $rSecret = getRazorpaySecret();
        /*$rKey = get_env_variable('RAZORPAY_API_KEY');
        $rSecret = get_env_variable('RAZORPAY_API_SECRET');
        $api = new Api($rKey, $rSecret);
        $cutoffTime = Carbon::now()->subHour(24);
        $pendingPayments = Payment::where('status', '!=', 'captured')
            ->where('status', '!=', 'failed')
            ->where('created_at', '>=', $cutoffTime)
            ->where('payment_gateway_used', '!=', 'cashfree')
            ->orderBy('payment_id', 'desc')
            ->get();
        if(is_countable($pendingPayments) && count($pendingPayments) > 0){
            foreach ($pendingPayments as $payment) {
                $rentalBooking = RentalBooking::where('booking_id', $payment->booking_id)->first();
                $orderId = $payment->razorpay_order_id;
                if (empty($orderId)) {
                    continue; // Skip this payment if no order ID
                }
                try {
                    $orderStatus = $api->order->fetch($orderId)->payments();
                } catch (\Razorpay\Api\Errors\Error $e) {
                    Log::error($e->getMessage());
                    continue;
                }
                if(is_countable($orderStatus['items']) && count($orderStatus['items']) > 0){
                    $items = $orderStatus->toArray();  // Convert the collection to an array
                    $items = $items['items'] ?? [];  
                    //$items = $orderStatus['items'] ?? [];
                    $capturedOrAuthorized = array_filter($items, function($v) {
                        return !empty($v['status']) && in_array($v['status'], ['captured', 'authorized']) && !empty($v['id']);
                    });
                    if (!empty($capturedOrAuthorized)) {
                        $paymentData = reset($capturedOrAuthorized);
                        $payment->update([
                            'status' => 'captured',
                            'razorpay_payment_id' => $paymentData['id']
                        ]);
                        $rentalBooking->processPayment($payment);
                    } else {
                        $minutesDiff = getMinuteDifference($payment->created_at);
                        if ($minutesDiff > 30) {
                            if ($payment->status == 'pending') {
                                $payment->status = 'failed';
                                $payment->save();
                            }
                            if ($rentalBooking->status == 'pending') {
                                $rentalBooking->status = 'failed';
                                $rentalBooking->save();
                            }
                        }
                    }
                }
                // Handle cases where there are no items in the order status
                if (isset($orderStatus['count']) && $orderStatus['count'] == 0) {
                    $minutesDiff = getMinuteDifference($payment->created_at);
                    if ($minutesDiff > 60) {
                        if ($payment->status == 'pending') {
                            $payment->status = 'failed';
                            $payment->save();
                        }
                        if ($rentalBooking->status == 'pending') {
                            $rentalBooking->status = 'failed';
                            $rentalBooking->save();
                        }
                    }
                }
            }
        }*/
    }
}
