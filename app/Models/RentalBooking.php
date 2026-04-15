<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
// use function Termwind\style;
use Illuminate\Support\Facades\DB;
use Razorpay\Api\Api;
use App\Models\{Payment, BookingTransaction, AdminPenalty, CustomerReferralDetails, RentalBooking, Setting, PaymentReportHistory};
use App\Jobs\SendNotificationJob;

class RentalBooking extends Model
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
        // 'calculation_details',
        'start_otp',
        'end_otp',
        'start_kilometers',
        'end_datetime',
        // 'data_json',
        'tax_rate',
        'sequence_no',
        'is_end_by_admin',
        'created_at',
    ];

    protected $hidden = [
        'rental_duration',
        'payment',
        'data_json',
        'updated_at',
    ];


    // Define the status names and colors
    protected static $statusMap = [
        'completed' => '#008080',
        'failed' => '#FF0000',
        'canceled' => '#808080',
        'running' => '#0000FF',
        'late return' => '#FFA500',
        'damaged' => '#FF4500',
        'confirmed' => '#38A4A6',
        'no show' => '#FF6347',
        'refunded' => '#FF1493',
        'awaiting completion' => '#FF7F50',
        'pending' => '#808080',
    ];

    protected $appends = ['button_visiblity', 'status_map', 'start_images', 'end_images', 'invoice_pdf', 'admin_invoice_pdf', 'summary_pdf', 'admin_summary_pdf', 'message_map', 'dl_status', 'govtid_status', 'allow_rating', 'rating_value', 'feedback_value', 'pay_now_status', 'admin_penalty_amount', 'price_summary', 'admin_customer_aggrement'];

    public function bookingTransactions()
    {
        return $this->hasMany(BookingTransaction::class, 'booking_id', 'booking_id');
    }

    public function getStatusMapAttribute()
    {
        $data = [
            'name' => '',
            'color' => '',
        ];
        $statusName = strtolower($this->status);
        $data['color'] = static::$statusMap[$statusName] ?? '#000000';
        $data['name'] = ucwords($this->status);
        return $data;
    }

    public function processPaymentNew($payment)
    {
        // version types = new_booking, extension, completion
        // payment types = create_order, extend_order, penalty

        // Query the related booking transactions
        $bookingTransaction = BookingTransaction::where('booking_id', $this->booking_id)
            ->where('type', $payment->payment_type)
            ->where('razorpay_order_id', $payment->razorpay_order_id)
            ->first();

        if ($bookingTransaction && !$bookingTransaction->paid) {
            $bookingTransaction->paid = true;
            $bookingTransaction->razorpay_payment_id = $payment->razorpay_payment_id;

            if ($payment->payment_type == "new_booking") {
                $this->status = 'confirmed';

                // $lastSequence = RentalBooking::max('sequence_no');
                // $this->sequence_no = $lastSequence + 1;

                // Send Email and Push notifications to the User
                // try{
                //     generateCustomerPdf($this->customer_id, $this->booking_id);
                // }catch(Exception $e){}

                // $fileName = 'customer_agreements_'.$this->customerId.'_'.$this->bookingId.'.pdf';
                // $filePath = public_path().'/customer_aggrements/'.$fileName;
                $attachments = [];
                // if(file_exists($filePath)){ 
                //     $attachments[] = $filePath;
                // }
                SendNotificationJob::dispatch($this->customer_id, $this->booking_id, 'new_booking', $attachments)->onQueue('emails');
                // if(file_exists($filePath)){ 
                //     unlink($filePath);
                // }

                //Send Notification to the user after one minutes if he/she hasn't uploaded docs yet
                $govIdStatus = CustomerDocument::where('customer_id', $this->customer_id)->where('document_type', 'govtid')->first();
                $dlStatus = CustomerDocument::where('customer_id', $this->customer_id)->where('document_type', 'dl')->first();
                if ($govIdStatus == '' || $dlStatus == '') {
                    SendNotificationJob::dispatch($this->customer_id, $this->booking_id, 'doc_upload_reminder')->onQueue('emails')->delay(Carbon::now()->addMinute());
                }

            } elseif ($payment->payment_type == "extension") {
                $this->return_date = $bookingTransaction->end_date;
                $this->rental_duration_minutes += $bookingTransaction->trip_duration_minutes;
                $this->total_cost += $bookingTransaction->trip_amount;

                // Send Email and Push notifications to the User
                SendNotificationJob::dispatch($this->customer_id, $this->booking_id, 'extension')->onQueue('emails');

            } elseif ($payment->payment_type == "completion") {
                $this->status = 'completed';
                $lastSequence = RentalBooking::max('sequence_no');
                $this->sequence_no = $lastSequence + 1;

                // Send Email and Push notifications to the User
                SendNotificationJob::dispatch($this->customer_id, $this->booking_id, 'completion')->onQueue('emails');
            }

            // Save the updated booking transaction and rental booking
            $bookingTransaction->save();
            $this->save();
        }
    }

    public function processPayment($payment)
    {
        //version types = new_booking, extension, completion
        //payment types = create_order, extend_order, penalty
        // $data = $this->updatePaymentData($payment->razorpay_order_id, $payment->razorpay_payment_id, $payment->payment_type);

        //OLD CODE
        /*$calculationDetails = json_decode($this->calculation_details, true);
        if ($payment->payment_type == "new_booking") {
            foreach ($calculationDetails['versions'] as &$version) {
                if($version['type'] === 'new_booking') {
                    if($version['details']['order']['razorpay_order_id'] == $payment->razorpay_order_id) {
                        if (!$version['details']['order']['paid']) {
                            $version['details']['order']['paid'] = true;
                            $version['details']['order']['razorpay_payment_id'] = $payment->razorpay_payment_id;
                            $this->status = 'confirmed';    

                            $lastSequence = RentalBooking::max('sequence_no');
                            $this->sequence_no = $lastSequence + 1;

                            try{
                                generateCustomerPdf($this->customer_id, $this->booking_id);
                            }catch(Exception $e){}

                            //$filePath = public_path().'\test_attachment.pdf';
                            $fileName = 'customer_aggrements_'.$this->customerId.'_'.$this->bookingId.'.pdf';
                            $filePath = public_path().'/customer_aggrements/'.$fileName;
                            $attachments = [];
                            if(file_exists($filePath)){ 
                                $attachments[] = $filePath;
                            }
                            //Send Email and Push notifications to the User
                            SendNotificationJob::dispatch($this->customer_id, $this->booking_id, 'new_booking', $attachments)->onQueue('emails');
                            if(file_exists($filePath)){ 
                                unlink($filePath);
                            }
                            //Send Notification to the user after one minutes if he/she hasn't uploaded docs yet
                            $govIdStatus = CustomerDocument::where('customer_id', $this->customer_id)->where('document_type', 'govtid')->first();
                            $dlStatus = CustomerDocument::where('customer_id', $this->customer_id)->where('document_type', 'dl')->first();
                            if($govIdStatus == '' || $dlStatus == ''){
                                SendNotificationJob::dispatch($this->customer_id, $this->booking_id, 'doc_upload_reminder')->onQueue('emails')->delay(Carbon::now()->addMinute());    
                            }
                        }
                    }
                }
            }
        } elseif ($payment->payment_type == "extension") {
            foreach ($calculationDetails['versions'] as &$version) {
                if($version['type'] === 'extension') {
                    if($version['details']['order']['razorpay_order_id'] == $payment->razorpay_order_id) {
                        if (!$version['details']['order']['paid']) {
                            $version['details']['order']['paid'] = true;
                            $version['details']['order']['razorpay_payment_id'] = $payment->razorpay_payment_id;
                            $this->return_date = $version['details']['end_date'];
                            $this->rental_duration_minutes = $this->rental_duration_minutes + $version['details']['trip_duration_minutes'];
                            $this->total_cost = $this->total_cost + $version['details']['trip_amount'];
                            //Send Email and Push notifications to the User
                            SendNotificationJob::dispatch($this->customer_id, $this->booking_id, 'extension')->onQueue('emails');
                        }
                    }
                }
            }
        } elseif ($payment->payment_type == "completion") {
            foreach ($calculationDetails['versions'] as &$version) {
                if($version['type'] === 'completion') {
                    if($version['details']['order']['razorpay_order_id'] == $payment->razorpay_order_id) {
                        if (!$version['details']['order']['paid']) {
                            $version['details']['order']['paid'] = true;
                            $version['details']['order']['razorpay_payment_id'] = $payment->razorpay_payment_id;
                            $this->status = 'completed';            
                            //Send Email and Push notifications to the User
                            SendNotificationJob::dispatch($this->customer_id, $this->booking_id, 'completion')->onQueue('emails');                 
                        }
                    }
                }
            }
        }
        $this->calculation_details = json_encode($calculationDetails);
        $this->save();*/

        //NEW CODE
        $calculationDetails = BookingTransaction::where(['booking_id' => $this->booking_id])->get();
        if (is_countable($calculationDetails) && count($calculationDetails) > 0) {
            if ($payment->payment_type == "new_booking") {
                foreach ($calculationDetails as $version) {
                    if ($version->type === 'new_booking') {
                        if ($version->razorpay_order_id == $payment->razorpay_order_id) {
                            if ($version->paid != 1) {
                                $version->paid = 1;
                                $version->razorpay_payment_id = $payment->razorpay_payment_id;
                                $version->save();
                                $this->status = 'confirmed';
                                // $lastSequence = RentalBooking::max('sequence_no');
                                // $this->sequence_no = $lastSequence + 1;
                                // $this->save();
                                try {
                                    $customerReferralDetails = CustomerReferralDetails::where(['customer_id' => $this->customer_id, 'reward_type' => 2, 'is_paid' => 0])->whereNull('payable_amount')->first();
                                    if ($customerReferralDetails != '' && $version->final_amount > 0 && $customerReferralDetails->reward_amount_or_percent > 0) {
                                        $setting = Setting::select('id', 'reward_max_discount_amount')->first();
                                        $payAmt = 0;
                                        $percent = (float) $customerReferralDetails->reward_amount_or_percent;
                                        $amount = (float) $version->final_amount;
                                        if ($customerReferralDetails->reward_amount_or_percent > 0 && $setting != '' && $setting->reward_max_discount_amount > 0) {
                                            $payAmt = min(($amount * $percent) / 100, $setting->reward_max_discount_amount);
                                        }
                                        $payAmt = round($payAmt);
                                        if ($payAmt > 0) {
                                            $customerReferralDetails->payable_amount = $payAmt;
                                            $customerReferralDetails->booking_id = $this->booking_id;
                                            $customerReferralDetails->save();
                                        }
                                    }
                                } catch (Exception $e) {
                                }

                                try {
                                    generateCustomerPdf($this->customer_id, $this->booking_id);
                                } catch (Exception $e) {
                                }

                                // $fileName = 'customer_agreements_'.$this->customer_id.'_'.$this->booking_id.'.pdf';
                                // $filePath = public_path().'/customer_aggrements/'.$fileName;
                                $attachments = [];
                                // if(file_exists($filePath)){ 
                                //     $attachments[] = $filePath;
                                // }
                                //Send Email and Push notifications to the User
                                SendNotificationJob::dispatch($this->customer_id, $this->booking_id, 'new_booking', $attachments)->onQueue('emails');

                                //Send Notification to the user after one minutes if he/she hasn't uploaded docs yet
                                $govIdStatus = CustomerDocument::where('customer_id', $this->customer_id)->where('document_type', 'govtid')->first();
                                $dlStatus = CustomerDocument::where('customer_id', $this->customer_id)->where('document_type', 'dl')->first();
                                if ($govIdStatus == '' || $dlStatus == '') {
                                    SendNotificationJob::dispatch($this->customer_id, $this->booking_id, 'doc_upload_reminder')->onQueue('emails')->delay(Carbon::now()->addMinute());
                                }
                            }
                        }
                    }
                }
            } elseif ($payment->payment_type == "extension") {
                foreach ($calculationDetails as $version) {
                    if ($version->type === 'extension') {
                        if ($version->razorpay_order_id == $payment->razorpay_order_id) {
                            if ($version->paid != 1) {
                                $version->paid = 1;
                                $version->razorpay_payment_id = $payment->razorpay_payment_id;
                                $version->save();

                                $this->return_date = $version->end_date;
                                $this->rental_duration_minutes = $this->rental_duration_minutes + $version->trip_duration_minutes;
                                $this->total_cost += $version->trip_amount;
                                //Send Email and Push notifications to the User
                                SendNotificationJob::dispatch($this->customer_id, $this->booking_id, 'extension')->onQueue('emails');
                            }
                        }
                    }
                }
            } elseif ($payment->payment_type == "completion") {
                foreach ($calculationDetails as $version) {
                    if ($version->type === 'completion') {
                        if ($version->razorpay_order_id == $payment->razorpay_order_id) {
                            if ($version->paid != 1) {
                                $version->paid = 1;
                                $version->razorpay_payment_id = $payment->razorpay_payment_id;
                                $version->save();
                                $this->status = 'completed';
                                $lastSequence = RentalBooking::max('sequence_no');
                                $this->sequence_no = $lastSequence + 1;
                                $this->save();

                                $fileName = 'customer_agreements_' . $this->customer_id . '_' . $this->booking_id . '.pdf';
                                $filePath = public_path() . '/customer_aggrements/' . $fileName;
                                if (file_exists($filePath)) {
                                    unlink($filePath);
                                }

                                if ($version->additional_charges > 0) {
                                    $adminPenalty = AdminPenalty::where(['booking_id' => $this->booking_id, 'is_paid' => 0, 'amount' => $version->additional_charges])->first();
                                    $adminPenalty->is_paid = 1;
                                    $adminPenalty->save();
                                }

                                //Send Email and Push notifications to the User
                                SendNotificationJob::dispatch($this->customer_id, $this->booking_id, 'completion')->onQueue('emails');
                            }
                        }
                    }
                }
            } elseif ($payment->payment_type == "penalty") {
                $bookingTransaction = BookingTransaction::where(['booking_id' => $this->booking_id, 'order_type' => 'penalty', 'razorpay_order_id' => $payment->razorpay_order_id])->first();
                if ($bookingTransaction) {
                    $bookingTransaction->paid = 1;
                    $bookingTransaction->razorpay_payment_id = $payment->razorpay_payment_id ?? '';
                    $bookingTransaction->save();
                }
                SendNotificationJob::dispatch($this->customer_id, $this->booking_id, 'penalty')->onQueue('emails');
            }
            $this->save();
        }
    }

    public function processCashfreePayment($payment)
    {
        // OLD CODE
        /*$calculationDetails = json_decode($this->calculation_details, true);
        if ($payment->payment_type == "new_booking") {
            $bookingTransaction = BookingTransaction::where(['booking_id' => $this->booking_id, 'order_type' => 'new_booking', 'cashfree_order_id' => $payment->cashfree_order_id])->first();
            $bookingTransaction->paid = 1;
            $bookingTransaction->save();
            foreach ($calculationDetails['versions'] as &$version) {
                if($version['type'] === 'new_booking') {
                    if($version['details']['order']['cashfree_order_id'] == $payment->cashfree_order_id) {
                        if (!$version['details']['order']['paid']) {
                            $version['details']['order']['paid'] = true;
                            $this->status = 'confirmed';    

                            $lastSequence = RentalBooking::max('sequence_no');
                            $this->sequence_no = $lastSequence + 1;

                            try{
                                generateCustomerPdf($this->customer_id, $this->booking_id);
                            }catch(Exception $e){}

                            //$filePath = public_path().'\test_attachment.pdf';
                            $fileName = 'customer_aggrements_'.$this->customerId.'_'.$this->bookingId.'.pdf';
                            $filePath = public_path().'/customer_aggrements/'.$fileName;
                            $attachments = [];
                            if(file_exists($filePath)){ 
                                $attachments[] = $filePath;
                            }
                            //Send Email and Push notifications to the User
                            SendNotificationJob::dispatch($this->customer_id, $this->booking_id, 'new_booking', $attachments)->onQueue('emails');
                            if(file_exists($filePath)){ 
                                unlink($filePath);
                            }
                            //Send Notification to the user after one minutes if he/she hasn't uploaded docs yet
                            $govIdStatus = CustomerDocument::where('customer_id', $this->customer_id)->where('document_type', 'govtid')->first();
                            $dlStatus = CustomerDocument::where('customer_id', $this->customer_id)->where('document_type', 'dl')->first();
                            if($govIdStatus == '' || $dlStatus == ''){
                                SendNotificationJob::dispatch($this->customer_id, $this->booking_id, 'doc_upload_reminder')->onQueue('emails')->delay(Carbon::now()->addMinute());    
                            }
                        }
                    }
                }
            }
        } elseif ($payment->payment_type == "extension") {
            $bookingTransaction = BookingTransaction::where(['booking_id' => $this->booking_id, 'order_type' => 'extension', 'cashfree_order_id' => $payment->cashfree_order_id])->first();
            $bookingTransaction->paid = 1;
            $bookingTransaction->save();
            foreach ($calculationDetails['versions'] as &$version) {
                if($version['type'] === 'extension') {
                    if($version['details']['order']['cashfree_order_id'] == $payment->cashfree_order_id) {
                        if (!$version['details']['order']['paid']) {
                            $version['details']['order']['paid'] = true;
                            $this->return_date = $version['details']['end_date'];
                            $this->rental_duration_minutes = $this->rental_duration_minutes + $version['details']['trip_duration_minutes'];
                            $this->total_cost = $this->total_cost + $version['details']['trip_amount'];
                            //Send Email and Push notifications to the User
                            SendNotificationJob::dispatch($this->customer_id, $this->booking_id, 'extension')->onQueue('emails');
                        }
                    }
                }
            }
        } elseif ($payment->payment_type == "completion") {
            $bookingTransaction = BookingTransaction::where(['booking_id' => $this->booking_id, 'order_type' => 'completion', 'cashfree_order_id' => $payment->cashfree_order_id])->first();
            $bookingTransaction->paid = 1;
            $bookingTransaction->save();
            foreach ($calculationDetails['versions'] as &$version) {
                if($version['type'] === 'completion') {
                    if($version['details']['order']['cashfree_order_id'] == $payment->cashfree_order_id) {
                        if (!$version['details']['order']['paid']) {
                            $version['details']['order']['paid'] = true;
                            $this->status = 'completed';            
                            //Send Email and Push notifications to the User
                            SendNotificationJob::dispatch($this->customer_id, $this->booking_id, 'completion')->onQueue('emails');                 
                        }
                    }
                }
            }
        } elseif ($payment->payment_type == "penalty") {
            $bookingTransaction = BookingTransaction::where(['booking_id' => $this->booking_id, 'order_type' => 'penalty', 'cashfree_order_id' => $payment->cashfree_order_id])->first();
            $bookingTransaction->paid = 1;
            $bookingTransaction->save();
        }
        $this->calculation_details = json_encode($calculationDetails);
        $this->save();*/

        // NEW CODE
        $calculationDetails = BookingTransaction::where(['booking_id' => $this->booking_id])->get();
        if (is_countable($calculationDetails) && count($calculationDetails) > 0) {
            if ($payment->payment_type == "new_booking") {
                foreach ($calculationDetails as $version) {
                    if ($version->type === 'new_booking') {
                        if ($version->cashfree_order_id == $payment->cashfree_order_id) {
                            if ($version->paid != 1) {
                                $version->paid = 1;
                                $version->save();

                                $this->status = 'confirmed';
                                // $lastSequence = RentalBooking::max('sequence_no');
                                // $this->sequence_no = $lastSequence + 1;
                                // $this->save();

                                try {
                                    $customerReferralDetails = CustomerReferralDetails::where(['customer_id' => $this->customer_id, 'reward_type' => 2, 'is_paid' => 0])->whereNull('payable_amount')->first();
                                    if ($customerReferralDetails != '' && $version->final_amount > 0 && $customerReferralDetails->reward_amount_or_percent > 0) {
                                        $setting = Setting::select('id', 'reward_max_discount_amount')->first();
                                        $payAmt = 0;
                                        $percent = (float) $customerReferralDetails->reward_amount_or_percent;
                                        $amount = (float) $version->final_amount;
                                        if ($customerReferralDetails->reward_amount_or_percent > 0 && $setting != '' && $setting->reward_max_discount_amount > 0) {
                                            $payAmt = min(($amount * $percent) / 100, $setting->reward_max_discount_amount);
                                        }
                                        $payAmt = round($payAmt);
                                        if ($payAmt > 0) {
                                            $customerReferralDetails->payable_amount = $payAmt;
                                            $customerReferralDetails->booking_id = $this->booking_id;
                                            $customerReferralDetails->save();
                                        }
                                    }
                                } catch (Exception $e) {
                                }

                                // try{
                                //     generateCustomerPdf($this->customer_id, $this->booking_id);
                                // }catch(Exception $e){}
                                // $fileName = 'customer_agreements_'.$this->customer_id.'_'.$this->booking_id.'.pdf';
                                // $filePath = public_path().'/customer_aggrements/'.$fileName;
                                $attachments = [];
                                // if(file_exists($filePath)){ 
                                //     $attachments[] = $filePath;
                                // }
                                //Send Email and Push notifications to the User
                                SendNotificationJob::dispatch($this->customer_id, $this->booking_id, 'new_booking', $attachments)->onQueue('emails');

                                //Send Notification to the user after one minutes if he/she hasn't uploaded docs yet
                                $govIdStatus = CustomerDocument::where('customer_id', $this->customer_id)->where('document_type', 'govtid')->first();
                                $dlStatus = CustomerDocument::where('customer_id', $this->customer_id)->where('document_type', 'dl')->first();
                                if ($govIdStatus == '' || $dlStatus == '') {
                                    SendNotificationJob::dispatch($this->customer_id, $this->booking_id, 'doc_upload_reminder')->onQueue('emails')->delay(Carbon::now()->addMinute());
                                }
                            }
                        }
                    }
                }
            } elseif ($payment->payment_type == "extension") {
                foreach ($calculationDetails as $version) {
                    if ($version->type === 'extension') {
                        if ($version->cashfree_order_id == $payment->cashfree_order_id) {
                            if ($version->paid != 1) {
                                $version->paid = 1;
                                $version->save();
                                $this->return_date = $version->end_date;
                                $this->rental_duration_minutes = $this->rental_duration_minutes + $version->trip_duration_minutes;
                                $this->total_cost += $version->trip_amount;
                                //Send Email and Push notifications to the User
                                SendNotificationJob::dispatch($this->customer_id, $this->booking_id, 'extension')->onQueue('emails');
                            }
                        }
                    }
                }
            } elseif ($payment->payment_type == "completion") {
                foreach ($calculationDetails as $version) {
                    if ($version->type === 'completion') {
                        if ($version->cashfree_order_id == $payment->cashfree_order_id) {
                            if ($version->paid != 1) {
                                $version->paid = 1;
                                $version->save();
                                $this->status = 'completed';
                                $lastSequence = RentalBooking::max('sequence_no');
                                $this->sequence_no = $lastSequence + 1;
                                $this->save();
                                if ($version->additional_charges > 0) {
                                    $adminPenalty = AdminPenalty::where(['booking_id' => $this->booking_id, 'is_paid' => 0, 'amount' => $version->additional_charges])->first();
                                    $adminPenalty->is_paid = 1;
                                    $adminPenalty->save();
                                }
                                $fileName = 'customer_agreements_' . $this->customer_id . '_' . $this->booking_id . '.pdf';
                                $filePath = public_path() . '/customer_aggrements/' . $fileName;
                                if (file_exists($filePath)) {
                                    unlink($filePath);
                                }
                                //Send Email and Push notifications to the User
                                SendNotificationJob::dispatch($this->customer_id, $this->booking_id, 'completion')->onQueue('emails');
                            }
                        }
                    }
                }
            } elseif ($payment->payment_type == "penalty") {
                $bookingTransaction = BookingTransaction::where(['booking_id' => $this->booking_id, 'order_type' => 'penalty', 'cashfree_order_id' => $payment->cashfree_order_id])->first();
                $bookingTransaction->paid = 1;
                $bookingTransaction->save();

                SendNotificationJob::dispatch($this->customer_id, $this->booking_id, 'penalty')->onQueue('emails');
            }
            $this->save();
        }
    }

    public function processIciciPayment($payment)
    {
        $calculationDetails = BookingTransaction::where(['booking_id' => $this->booking_id])->get();
        if (is_countable($calculationDetails) && count($calculationDetails) > 0) {
            if ($payment->payment_type == "new_booking") {
                foreach ($calculationDetails as $version) {
                    if ($version->type === 'new_booking') {
                        if ($version->icici_merchant_txnNo == $payment->icici_merchant_txnNo && $version->icici_txnid == $payment->icici_txnid) {
                            if ($version->paid != 1) {
                                $version->paid = 1;
                                $version->save();
                                $this->status = 'confirmed';
                                try {
                                    $customerReferralDetails = CustomerReferralDetails::where(['customer_id' => $this->customer_id, 'reward_type' => 2, 'is_paid' => 0])->whereNull('payable_amount')->first();
                                    if ($customerReferralDetails != '' && $version->final_amount > 0 && $customerReferralDetails->reward_amount_or_percent > 0) {
                                        $setting = Setting::select('id', 'reward_max_discount_amount')->first();
                                        $payAmt = 0;
                                        $percent = (float) $customerReferralDetails->reward_amount_or_percent;
                                        $amount = (float) $version->final_amount;
                                        if ($customerReferralDetails->reward_amount_or_percent > 0 && $setting != '' && $setting->reward_max_discount_amount > 0) {
                                            $payAmt = min(($amount * $percent) / 100, $setting->reward_max_discount_amount);
                                        }
                                        $payAmt = round($payAmt);
                                        if ($payAmt > 0) {
                                            $customerReferralDetails->payable_amount = $payAmt;
                                            $customerReferralDetails->booking_id = $this->booking_id;
                                            $customerReferralDetails->save();
                                        }
                                    }
                                } catch (Exception $e) {
                                }
                                try {
                                    $customerReferralDetails = CustomerReferralDetails::where(['customer_id' => $this->customer_id, 'reward_type' => 2, 'is_paid' => 0])->whereNull('payable_amount')->first();
                                    if ($customerReferralDetails != '' && $version->final_amount > 0 && $customerReferralDetails->reward_amount_or_percent > 0) {
                                        $setting = Setting::select('id', 'reward_max_discount_amount')->first();
                                        $payAmt = 0;
                                        $percent = (float) $customerReferralDetails->reward_amount_or_percent;
                                        $amount = (float) $version->final_amount;
                                        if ($customerReferralDetails->reward_amount_or_percent > 0 && $setting != '' && $setting->reward_max_discount_amount > 0) {
                                            $payAmt = min(($amount * $percent) / 100, $setting->reward_max_discount_amount);
                                        }
                                        $payAmt = round($payAmt);
                                        if ($payAmt > 0) {
                                            $customerReferralDetails->payable_amount = $payAmt;
                                            $customerReferralDetails->booking_id = $this->booking_id;
                                            $customerReferralDetails->save();
                                        }
                                    }
                                } catch (Exception $e) {
                                }

                                // try{
                                //     generateCustomerPdf($this->customer_id, $this->booking_id);
                                // }catch(Exception $e){}
                                // $fileName = 'customer_agreements_'.$this->customer_id.'_'.$this->booking_id.'.pdf';
                                // $filePath = public_path().'/customer_aggrements/'.$fileName;
                                $attachments = [];
                                // if(file_exists($filePath)){ 
                                //     $attachments[] = $filePath;
                                // }
                                //Send Email and Push notifications to the User
                                SendNotificationJob::dispatch($this->customer_id, $this->booking_id, 'new_booking', $attachments)->onQueue('emails');

                                //Send Notification to the user after one minutes if he/she hasn't uploaded docs yet
                                $govIdStatus = CustomerDocument::where('customer_id', $this->customer_id)->where('document_type', 'govtid')->first();
                                $dlStatus = CustomerDocument::where('customer_id', $this->customer_id)->where('document_type', 'dl')->first();
                                if ($govIdStatus == '' || $dlStatus == '') {
                                    SendNotificationJob::dispatch($this->customer_id, $this->booking_id, 'doc_upload_reminder')->onQueue('emails')->delay(Carbon::now()->addMinute());
                                }
                            }
                        }
                    }
                }
            } elseif ($payment->payment_type == "extension") {
                foreach ($calculationDetails as $version) {
                    if ($version->type === 'extension') {
                        if ($version->icici_merchant_txnNo == $payment->icici_merchant_txnNo && $version->icici_txnid == $payment->icici_txnid) {
                            if ($version->paid != 1) {
                                $version->paid = 1;
                                $version->save();
                                $this->return_date = $version->end_date;
                                $this->rental_duration_minutes = $this->rental_duration_minutes + $version->trip_duration_minutes;
                                $this->total_cost += $version->trip_amount;
                                //Send Email and Push notifications to the User
                                SendNotificationJob::dispatch($this->customer_id, $this->booking_id, 'extension')->onQueue('emails');
                            }
                        }
                    }
                }
            } elseif ($payment->payment_type == "completion") {
                foreach ($calculationDetails as $version) {
                    if ($version->type === 'completion') {
                        if ($version->icici_merchant_txnNo == $payment->icici_merchant_txnNo && $version->icici_txnid == $payment->icici_txnid) {
                            if ($version->paid != 1) {
                                $version->paid = 1;
                                $version->save();
                                $this->status = 'completed';
                                $lastSequence = RentalBooking::max('sequence_no');
                                $this->sequence_no = $lastSequence + 1;
                                $this->save();
                                if ($version->additional_charges > 0) {
                                    $adminPenalty = AdminPenalty::where(['booking_id' => $this->booking_id, 'is_paid' => 0, 'amount' => $version->additional_charges])->first();
                                    $adminPenalty->is_paid = 1;
                                    $adminPenalty->save();
                                }
                                $fileName = 'customer_agreements_' . $this->customer_id . '_' . $this->booking_id . '.pdf';
                                $filePath = public_path() . '/customer_aggrements/' . $fileName;
                                if (file_exists($filePath)) {
                                    unlink($filePath);
                                }
                                //Send Email and Push notifications to the User
                                SendNotificationJob::dispatch($this->customer_id, $this->booking_id, 'completion')->onQueue('emails');
                            }
                        }
                    }
                }
            } elseif ($payment->payment_type == "penalty") {
                $bookingTransaction = BookingTransaction::where(['booking_id' => $this->booking_id, 'order_type' => 'penalty', 'icici_merchant_txnNo' => $payment->icici_merchant_txnNo])->first();
                $bookingTransaction->paid = 1;
                $bookingTransaction->save();

                SendNotificationJob::dispatch($this->customer_id, $this->booking_id, 'penalty')->onQueue('emails');
            }
            $this->save();
        }
    }

    public function getPriceSummaryAttribute()
    {
        // return $this->generatePriceSummaryFromCalculationDetails($this->calculation_details);
        return $this->generatePriceSummaryFromBookingTransactions();
    }

    public function generatePriceSummaryFromCalculationDetails($encoded)
    {

        /*$decodedValue = json_decode($encoded, true);
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
            }
            // Add the final_amount to the sum
            if ($version['type'] === 'extension') {
                if($details['order']['paid'] ?? false){
                    $paid_final_amount_sum += $final_amount;
                }
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
            if (!$transaction->paid && $transaction->type !== 'completion') {
                continue;
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
                        $kilometerLimit = calculateKmLimit($rentalDurationMinutes, $this->vehicle->model->category->vehicleType->name ?? '');
                        $kms_text = " ($kilometerLimit Kms)";
                        // $kms_text = "";
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
                    // $trip_amount *= 1.3;
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

    // public function getPayNowStatusAttribute(){
    //     $calculationDetails = json_decode($this->calculation_details, true);
    //     $isOrderPaid = $completionFound = $payNow = false;
    //     $adminPenalty = AdminPenalty::where('booking_id', $this->booking_id)->where('amount', '!=', 0)->where('is_paid', 0)->exists();    

    //     if(isset($calculationDetails) && isset($calculationDetails['versions'])){
    //         foreach ($calculationDetails['versions'] as $version) {
    //             if ($version['type'] === 'completion') {
    //                 $isOrderPaid = $version['details']['order']['paid'];
    //                 $completionFound = true;
    //                 break;
    //             }
    //         }
    //     }
    //     if ($completionFound) {
    //         $payNow = !$isOrderPaid;
    //     }
    //     if($adminPenalty){
    //         $payNow = true;   
    //     }

    //     return $payNow;
    // }

    public function getPayNowStatusAttribute()
    {
        /*$completionFound = */
        $payNow = false;

        // Check if there is a completion transaction that is not paid
        /*$completionTransaction = BookingTransaction::where('booking_id', $this->booking_id)
            ->where('type', 'completion')
            ->first();
        if ($completionTransaction) {
            $completionFound = true;
            $isOrderPaid = $completionTransaction->paid;
        }*/

        // Check if there is an unpaid admin penalty
        $adminPenalty = AdminPenalty::where('booking_id', $this->booking_id)
            ->where('amount', '!=', 0)
            ->where('is_paid', 0)
            ->exists();

        $bookingTransaction = BookingTransaction::where('booking_id', $this->booking_id)
            ->where('type', 'penalty')
            ->where('final_amount', '!=', 0)
            ->where('paid', 0)
            ->exists();

        /*if ($completionFound) {
            $payNow = !$isOrderPaid;
        }*/

        if ($adminPenalty || $bookingTransaction) {
            $payNow = true;
        }

        return $payNow;
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
        $kilometerLimit = calculateKmLimit($tripDurationHours, $this->vehicle->model->category->vehicleType->name ?? '');
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

    // public function generatePriceSummaryFromCalculationDetails($encoded) {

    //     $decodedValue = json_decode($encoded, true);
    //     if (empty($decodedValue) || !isset($decodedValue['versions']) || !is_array($decodedValue['versions'])) {
    //         return null;
    //     }

    //     $data = $decodedValue;
    //     $calculation_details = [];
    //     $paid_final_amount_sum = 0; // Initialize the sum for paid final_amount
    //     $completionAdded = false;
    //     $completionPaid = false;
    //     foreach ($data['versions'] as $version) {
    //         // Check if paid is true
    //         // if (isset($version['details']['order']['paid']) && $version['details']['order']['paid']) {
    //             // Extracting details from the JSON
    //             $trip_amount = number_format($version['details']['trip_amount'] ?? 0, 2);
    //             $convenience_fee = number_format($version['details']['convenience_fee'] ?? 0, 2);
    //             $tax_amt = number_format($version['details']['tax_amt'] ?? 0, 2);
    //             $total_amount = number_format($version['details']['total_amount'] ?? 0, 2);
    //             $refundable_deposit = number_format($version['details']['refundable_deposit'] ?? 0, 2);

    //             // Add the final_amount to the sum
    //             $paid_final_amount_sum += ($version['details']['final_amount'] ?? 0);
    //             if (isset($version['details']['order']['paid']) && $version['details']['order']['paid']) {

    //             } elseif ($version['type'] === 'completion') {

    //             } else {
    //                 continue;
    //             }

    //             // Creating the price summary array
    //             $price_summary = [];
    //             if ($version['type'] === 'new_booking') {
    //             } elseif ($version['type'] === 'extension') {
    //                 $price_summary[] = [
    //                     "key" => "Extension Booking",
    //                     "value" => "",
    //                     "color" => "#000000",
    //                     "style" => "bold"
    //                 ];
    //             } elseif ($version['type'] === 'completion') {
    //                 $price_summary[] = [
    //                     "key" => "Additional Charges",
    //                     "value" => "",
    //                     "color" => "#000000",
    //                     "style" => "bold"
    //                 ];
    //             }
    //             $price_summary = array_merge($price_summary, [
    //                 [
    //                     "key" => "Trip Amount",
    //                     "value" => "₹ {$trip_amount}",
    //                     "color" => "#000000",
    //                     "style" => "normal"
    //                 ],
    //                 [
    //                     "key" => "Convenience Fee",
    //                     "value" => "₹ {$convenience_fee}",
    //                     "color" => "#808080",
    //                     "style" => "normal"
    //                 ],
    //                 [
    //                     "key" => "Tax Amount",
    //                     "value" => "₹ {$tax_amt}",
    //                     "color" => "#808080",
    //                     "style" => "normal"
    //                 ],
    //                 [
    //                     "key" => "Total Price",
    //                     "value" => "₹ {$total_amount}",
    //                     "color" => "#000000",
    //                     "style" => "normal"
    //                 ]
    //             ]);

    //             // Include specific details for each type of order
    //             if ($version['type'] === 'new_booking') {
    //                 $price_summary[] = [
    //                     "key" => "Refundable Deposit",
    //                     "value" => "₹ {$refundable_deposit}",
    //                     "color" => "#D3D3D3",
    //                     "style" => "semibold"
    //                 ];
    //             } elseif ($version['type'] === 'completion') {
    //                 $completionAdded = true;
    //                 $order = $version['details']['order'];
    //                 $fromRefundableDeposit = $order['from_refundable_deposit'] ?? false;
    //                 $paid = $order['paid'] ?? false;
    //                 $completionPaid = $paid;
    //                 $lateReturn = number_format($version['details']['late_return'] ?? 0, 2);
    //                 $exceededKmPayAmount = number_format($version['details']['exceeded_km_limit'] ?? 0, 2);
    //                 $additionalCharges = number_format($version['details']['additional_charges'] ?? 0, 2);
    //                 $additionalChargesInfo = $version['details']['additional_charges_info'] ?? '';
    //                 $refundableDepositUsed = number_format($version['details']['refundable_deposit_used'] ?? 0, 2);
    //                 $refundableDeposit = number_format($version['details']['refundable_deposit'] ?? 0, 2);
    //                 $amountToPay = number_format($version['details']['amount_to_pay'] ?? 0, 2);


    //                 $price_summary[] = [
    //                     "key" => "Late Return",
    //                     "value" => "₹ {$lateReturn}",
    //                     "color" => "#808080",
    //                     "style" => "normal"
    //                 ];
    //                 if ($exceededKmPayAmount > 0) {
    //                     $pickupDateTime = Carbon::parse($this->pickup_date);
    //                     $returnDateTime = Carbon::parse($this->return_date);
    //                     $tripDurationHours = $returnDateTime->diffInHours($pickupDateTime);
    //                     $kilometerLimit = calculateKmLimit($tripDurationHours);

    //                     $kilometerDifference = $this->end_kilometers - $this->start_kilometers;
    //                     $extra = $kilometerDifference - $kilometerLimit;
    //                     $rate = ($this->vehicle->extra_km_rate ?? 0);
    //                     $exceededKilometerPenalty = max(0, ($kilometerDifference - $kilometerLimit) * $rate);
    //                     $key = "Extra Kms ".$extra." \nPer km rate ".$rate;
    //                     $price_summary[] = [
    //                         "key" => $key,
    //                         "value" => "₹ {$exceededKmPayAmount}",
    //                         "color" => "#000000",
    //                         "style" => "bold"
    //                     ];
    //                 }
    //                 $price_summary[] = [
    //                     "key" => "{$additionalChargesInfo}",
    //                     "value" => "₹ {$additionalCharges}",
    //                     "color" => "#000000",
    //                     "style" => "bold"
    //                 ];
    //                 if ($fromRefundableDeposit) {
    //                     $price_summary[] = [
    //                         "key" => "Refundable Deposit Used",
    //                         "value" => "₹ {$refundableDepositUsed}",
    //                         "color" => "#000000",
    //                         "style" => "bold"
    //                     ];
    //                     $price_summary[] = [
    //                         "key" => "Refundable Deposit Remains",
    //                         "value" => "₹ {$refundableDeposit}",
    //                         "color" => "#000000",
    //                         "style" => "bold"
    //                     ];
    //                 } else {
    //                     $price_summary[] = [
    //                         "key" => "Refundable Deposit Used",
    //                         "value" => "₹ {$refundableDepositUsed}",
    //                         "color" => "#000000",
    //                         "style" => "bold"
    //                     ];
    //                     $price_summary[] = [
    //                         "key" => "Refundable Deposit Remains",
    //                         "value" => "₹ {$refundableDeposit}",
    //                         "color" => "#000000",
    //                         "style" => "bold"
    //                     ];
    //                     if (!$paid) {
    //                         $price_summary[] = [
    //                             "key" => "Amount To Pay",
    //                             "value" => "₹ {$amountToPay}",
    //                             "color" => "#000000",
    //                             "style" => "bold"
    //                         ];    
    //                     }
    //                 }
    //             }

    //             // Merge price summary arrays
    //             $calculation_details = array_merge($calculation_details, $price_summary);
    //         // }
    //     }
    //     $final_amount = number_format($paid_final_amount_sum, 2);
    //     if ($completionAdded) {
    //         if ($completionPaid) {
    //             $calculation_details[] = [
    //                 "key" => "Final Amount",
    //                 "value" => "₹ {$final_amount}",
    //                 "color" => "#000000",
    //                 "style" => "bold"
    //             ];

    //         }
    //     } else {
    //         $calculation_details[] = [
    //             "key" => "Final Amount",
    //             "value" => "₹ {$final_amount}",
    //             "color" => "#000000",
    //             "style" => "bold"
    //         ];

    //     }
    //     // Outputting the calculation details as JSON
    //     // return ["final_amount" => $final_amount, "price_summary" => $calculation_details];
    //     return $calculation_details;
    // }

    private function isCompletionStatus($calcDetails)
    {
        if (isset($calcDetails['versions'])) {
            foreach ($calcDetails['versions'] as $version) {
                if ($version['type'] == 'completion') {
                    return false;
                }
            }
        }
        return true;
    }

    public function getMessageMapAttribute()
    {
        $data = [
            'message' => '',
            'color' => '',
        ];
        if ($this->status === 'confirmed') {
            $data['color'] = '#38a4a6';
        } elseif ($this->status === 'failed') {
            $data['color'] = '#dc3545';
        } elseif ($this->status === 'canceled') {
            $data['color'] = '#dc3545';
        } elseif ($this->status === 'running') {
            $data['color'] = '#38a4a6';
        } elseif ($this->status === 'late return') {
            $data['color'] = '#38a4a6';
        } elseif ($this->status === 'damaged') {
            $data['color'] = '#dc3545';
        } elseif ($this->status === 'no show') {
            $data['color'] = '#38a4a6';
        } elseif ($this->status === 'refunded') {
            $data['color'] = '#38a4a6';
        } elseif ($this->status === 'awaiting completion') {
            $data['color'] = '#38a4a6';
        } elseif ($this->status === 'completed') {
            $data['color'] = '#38a4a6';
        } elseif ($this->status === 'pending') {
            $data['color'] = '#38a4a6';
        } elseif ($this->status === 'penalty_paid') {
            $data['color'] = '#38a4a6';
        } else {
            $data['color'] = '#38a4a6';
        }

        if ($this->status === 'confirmed') {
            $govIdStatus = CustomerDocument::where('customer_id', $this->customer_id)->where('document_type', 'govtid')->first();
            $dlStatus = CustomerDocument::where('customer_id', $this->customer_id)->where('document_type', 'dl')->first();

            $hasApprovedGovtId = CustomerDocument::where('customer_id', $this->customer_id)
                ->where('is_approved', 'approved')
                ->where('document_type', 'govtid')
                ->exists();

            $hasApprovedDL = CustomerDocument::where('customer_id', $this->customer_id)
                ->where('is_approved', 'approved')
                ->where('document_type', 'dl')
                ->exists();

            if ($hasApprovedDL && $hasApprovedGovtId) {
                $data['status'] = '0';
                $data['message'] = "Your booking has been successfully confirmed Get ready to hit the road!";
            } elseif (($dlStatus == '' && $govIdStatus == '') || $dlStatus == '' || $govIdStatus == '') {
                $data['status'] = '1';
                $data['message'] = "Please upload Your Government Id or Driving Licence document/s";
            } elseif ($govIdStatus != '' && $dlStatus != '' && $govIdStatus->is_approved === 'approved' && $dlStatus->is_approved === 'approved') {
                // Both documents are approved
                $pickupDate = Carbon::parse($this->pickup_date);
                $currentDate = Carbon::now()->setTimezone('Asia/Kolkata');
                // Check if the current time is before 30 minutes of the pickup date
                $data['status'] = '0';
                $data['message'] = "Your booking has been successfully confirmed Get ready to hit the road!";
            } elseif ($govIdStatus != '' && $dlStatus != '' && $govIdStatus->is_approved === 'awaiting_approval' && $dlStatus->is_approved === 'awaiting_approval') {
                // Both documents are awaiting_approval
                $data['status'] = '0';
                $data['message'] = "Both Government ID and Driver's License documents are awaiting approval.";
            } elseif ($govIdStatus != '' && $dlStatus != '' && $govIdStatus->is_approved === 'approved' && $dlStatus->is_approved === 'awaiting_approval') {
                // Government ID approved, Driver's License awaiting_approval
                $data['message'] = "Government ID document has been approved, but Driver's License document is still awaiting approval.";
            } elseif ($govIdStatus != '' && $dlStatus != '' && $govIdStatus->is_approved === 'approved' && $dlStatus->is_approved === 'rejected') {
                // Government ID approved, Driver's License awaiting_approval
                $data['message'] = "Government ID document has been approved, but Driver's License document has been rejected, please re-upload.";
            } elseif ($govIdStatus != '' && $dlStatus != '' && $govIdStatus->is_approved === 'awaiting_approval' && $dlStatus->is_approved === 'approved') {
                // Government ID awaiting_approval, Driver's License approved
                $data['message'] = "Government ID document is still awaiting approval, but Driver's License document has been approved.";
            } elseif ($govIdStatus != '' && $dlStatus != '' && $govIdStatus->is_approved === 'rejected' && $dlStatus->is_approved === 'awaiting_approval') {
                // Government ID rejected, Driver's License awaiting_approval
                $data['status'] = '1';
                $data['message'] = "Government ID document has been rejected, please re-upload. Driver's License document is still awaiting approval.";
            } elseif ($govIdStatus != '' && $dlStatus != '' && $govIdStatus->is_approved === 'awaiting_approval' && $dlStatus->is_approved === 'rejected') {
                // Government ID awaiting_approval, Driver's License rejected
                $data['status'] = '1';
                $data['message'] = "Government ID document is still awaiting approval. Driver's License document has been rejected, please re-upload.";
            } elseif ($govIdStatus != '' && $dlStatus != '' && $govIdStatus->is_approved === 'rejected' && $dlStatus->is_approved === 'approved') {
                // Government ID awaiting_approval, Driver's License rejected
                $data['message'] = "Government ID document has been rejected, Driver's License document has been approved.";
            } elseif ($govIdStatus != '' && $dlStatus != '' && $govIdStatus->is_approved === 'rejected' && $dlStatus->is_approved === 'rejected') {
                // Both documents are rejected
                $data['status'] = '1';
                $data['message'] = "Both Government ID and Driver's License documents have been rejected, please re-upload.";
            } else {
                // Handle any other scenarios
                $data['message'] = "Invalid status.";
            }
        } elseif ($this->status === 'failed') {
            $data['message'] = 'We are sorry to inform you that your booking has failed. Please contact support for assistance.';
        } elseif ($this->status === 'canceled') {
            //$data['message'] = 'Your booking has been canceled. We hope to serve you better in the future. You will get refunded within 7 working days.*';
            $data['message'] = "Your Booking Has been Cancelled, We would Like you Serve you better Again for your Next Trip. We will revert you back soon for the Cancellations process as per Company's Terms & Conditions";
        } elseif ($this->status === 'running') {
            $data['message'] = 'Your booking is in running state. Enjoy your ride!';
        } elseif ($this->status === 'late return') {
            $data['message'] = 'We noticed your booking was returned late. Please remember to return the vehicle on time in the future.';
        } elseif ($this->status === 'damaged') {
            $data['message'] = 'We regret to inform you that the vehicle was returned damaged. Please contact support for further assistance.';
        } elseif ($this->status === 'no show') {
            $data['message'] = 'We regret to inform you that you did not show up for your booking. Please ensure to inform us if your plans change in the future.';
        } elseif ($this->status === 'refunded') {
            $data['message'] = 'Your booking has been refunded successfully. We apologize for any inconvenience caused.';
        } elseif ($this->status === 'awaiting completion') {
            $data['message'] = 'Your booking is awaiting completion. If you have any questions, feel free to contact us.';
        } elseif ($this->status === 'completed') {
            $data['message'] = 'Thank you for riding with us.';
        } elseif ($this->status === 'pending') {
            $data['message'] = "Your booking is currently payment pending approval. Please wait for confirmation.";
        } elseif ($this->status === 'penalty_paid') {
            $data['message'] = "Your penalty has been paid. Please complete your booking by verifying the end OTP.";
        } else {
            $data['message'] = 'Unknown status.';
        }
        return $data;
    }

    public function getButtonVisiblityAttribute()
    {
        $data = [
            'start_journey_button' => false,
            'upload_images_button' => false,
            'end_journey_button' => false,
            'cancel_booking' => false,
            'end_journey_otp_status' => false,
            'start_journey_images_upload_status' => false,
            'end_journey_images_upload_status' => false,
        ];
        $pickupDate = Carbon::parse($this->pickup_date);
        $currentDate = Carbon::now()->setTimezone('Asia/Kolkata');
        $returnDate = Carbon::parse($this->return_date);

        if ($this->status === 'confirmed') {
            if ($returnDate < $currentDate) {
                $data['cancel_booking'] = false;
            } else {
                $data['cancel_booking'] = true;
            }
        } else {
            $data['cancel_booking'] = false;
        }

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
                ->where('is_approved', 'approved')
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
                // if ($pickupDate->subMinutes(30) >= $currentDate && $currentDate < $returnDate) {
                //     $data['start_journey_button'] = true;    
                // } else {
                //     $data['start_journey_button'] = false;
                // }
            } else {
                $data['start_journey_button'] = false;
            }
        }


        if ($this->status === 'running') {
            // Check if 5 images are uploaded for this booking
            // $imageCount = RentalBookingImage::where('booking_id', $this->booking_id)
            //     ->where('image_type', 'start')
            //     ->count();
            //$data['upload_images_button'] = $imageCount < 5;
            //$data['end_journey_button'] = $imageCount >= 5;
            $data['end_journey_button'] = true;

            // Check if there is a completion transaction that is not paid
            /*$completionTransaction = BookingTransaction::where('booking_id', $this->booking_id)
                ->where('type', 'completion')
                ->first();
            if ($completionTransaction) {
                if($completionTransaction->paid != 1){
                    $data['end_journey_button'] = false;
                }
            }*/
            // Check if admin penalty is not paid
            $adminPenalty = AdminPenalty::where(['booking_id' => $this->booking_id, 'is_paid' => 0])->where('amount', '>', 0)->first();
            if ($adminPenalty != '') {
                $data['end_journey_button'] = false;
            }
        } else {
            //$data['upload_images_button'] = false;
            $data['end_journey_button'] = false;
        }

        if ($this->end_datetime == null) {
            $data['end_journey_otp_status'] = false;
        } else {
            $adminPenalty = AdminPenalty::where(['booking_id' => $this->booking_id, 'is_paid' => 0])->where('amount', '>', 0)->first();
            if ($adminPenalty != '') {
                $data['end_journey_otp_status'] = false;
            }

            $data['end_journey_otp_status'] = true;
        }

        $carHostVehicleStartJourneyImg = CarHostVehicleStartJourneyImage::where(['image_type' => 1, 'booking_id' => $this->booking_id])->exists();
        $carHostVehicleEndJourneyImg = CarHostVehicleStartJourneyImage::where(['image_type' => 2, 'booking_id' => $this->booking_id])->exists();
        $data['start_journey_images_upload_status'] = $carHostVehicleStartJourneyImg;
        $data['end_journey_images_upload_status'] = $carHostVehicleEndJourneyImg;

        return $data;
    }

    public function getAllowRatingAttribute()
    {
        if ($this->status === 'completed') {
            $checkReview = RentalReview::where('booking_id', $this->booking_id)->first();
            if ($checkReview != '') {
                $reviewDateTime = Carbon::parse($checkReview->created_at);
                $currentDateTime = Carbon::now()->setTimezone('Asia/Kolkata');
                $reviewDiffereceHours = $currentDateTime->diffInHours($reviewDateTime);
                if ($reviewDiffereceHours > 24) {
                    return false;
                }
            }
            return true;
        } else {
            return false;
        }
    }

    public function getRatingValueAttribute()
    {
        $checkReview = RentalReview::select('review_id', 'booking_id', 'rating')->where('booking_id', $this->booking_id)->first();
        if ($checkReview != '') {
            $ratingVal = isset($checkReview->rating) ? $checkReview->rating : 0;
            return $checkReview->rating;
        } else {
            return 0;
        }
    }

    public function getFeedbackValueAttribute()
    {
        $feedbackVal = '';
        $checkReview = RentalReview::select('review_id', 'booking_id', 'review_text')->where('booking_id', $this->booking_id)->first();
        if ($checkReview != '') {
            $feedbackVal = $checkReview->review_text ?? NULL;
        }

        return $feedbackVal;
    }

    public function getStartImagesAttribute()
    {
        $images = RentalBookingImage::where('booking_id', $this->booking_id)->where('image_type', 'start')->get();
        return $images;
    }

    public function getInvoicePdfAttribute()
    {
        if (strtolower($this->status) === 'completed') {
            return asset('api/admin/v1/booking-invoice/' . $this->booking_id);
        } else {
            return '';
        }
    }

    public function getAdminInvoicePdfAttribute()
    {
        if (strtolower($this->status) === 'completed') {
            //return asset('api/booking-invoice/' . $this->booking_id);
            return asset('api/admin/v1/booking-invoice/' . $this->booking_id);
        } else {
            return '';
        }
    }

    public function getDlStatusAttribute()
    {
        $hasApprovedDL = CustomerDocument::where('customer_id', $this->customer_id)
            ->where('is_approved', 'approved')
            ->where('document_type', 'dl')
            ->exists();

        return $hasApprovedDL;
    }

    public function getGovtidStatusAttribute()
    {
        $hasApprovedGovtId = CustomerDocument::where('customer_id', $this->customer_id)
            ->where('is_approved', 'approved')
            ->where('document_type', 'govtid')
            ->exists();

        return $hasApprovedGovtId;
    }

    public function getSummaryPdfAttribute()
    {
        if (strtolower($this->status) === 'completed') {
            return asset('api/rental-booking/summary/' . $this->booking_id);
        } else {
            return '';
        }
    }

    public function getAdminSummaryPdfAttribute()
    {
        //return asset('api/booking-summary/' . $this->booking_id.'/'.$this->customer_id);
        return asset('api/admin/v1/booking-summary/' . $this->booking_id . '/' . $this->customer_id);
    }

    public function getAdminCustomerAggrementAttribute()
    {
        //return asset('api/customer-aggrement/' . $this->booking_id);
        return asset('api/admin/v1/customer-aggrement/' . $this->booking_id);
    }

    public function getEndImagesAttribute()
    {
        $images = RentalBookingImage::where('booking_id', $this->booking_id)->where('image_type', 'end')->get();
        return $images;
    }

    // public function updatePaymentData($orderId, $paymentId, $paymentType = null) {
    //     $calculationDetailsJson = DB::table($this->table)->where($this->primaryKey, $this->booking_id)->value('calculation_details');
    //     $calculationDetails = json_decode($calculationDetailsJson, true);
    //     $versionToPass = null;
    //     if (isset($calculationDetails['versions']) && is_array($calculationDetails['versions'])) {
    //         $type = '';
    //         if($paymentType == 'new_booking'){
    //             $type = 'new_booking';
    //         }elseif($paymentType == 'extension'){
    //             $type = 'extension';
    //         }elseif($paymentType == 'completion'){
    //             $type = 'completion';
    //         }
    //         foreach($calculationDetails['versions'] as $index=>$version) {
    //             if($version['type'] == $type){
    //                 if (isset($version['details']['order']) && $version['details']['order']['razorpay_order_id'] === $orderId) {
    //                     $version['details']['order']['razorpay_payment_id'] = $paymentId;
    //                     $version['details']['order']['paid'] = true;
    //                     $versionToPass = $version;        
    //                     $calculationDetails['versions'][$index] = $version;
    //                     break;
    //                 }
    //             }
    //         }
    //         $updatedCalculationDetailsJson = json_encode($calculationDetails);
    //         DB::table($this->table)->where($this->primaryKey, $this->booking_id)->update(['calculation_details' => $updatedCalculationDetailsJson]);
    //     }
    //     return $versionToPass;
    // }

    // Define relationships
    public function payment()
    {
        return $this->belongsTo(Payment::class, 'booking_id', 'booking_id');
    }
    public function refund()
    {
        return $this->belongsTo(Refund::class, 'booking_id', 'booking_id');
    }
    public function rentalBookingImage()
    {
        return $this->belongsTo(RentalBookingImage::class, 'booking_id', 'booking_id');
    }
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id', 'vehicle_id');
    }

    /* public function fromBranch()
     {
         return $this->belongsTo(Branch::class, 'from_branch_id', 'branch_id');
     }*/

    /*public function toBranch()
    {
        return $this->belongsTo(Branch::class, 'to_branch_id', 'branch_id');
    }*/

    public function pickupLocation()
    {
        return $this->hasOne(CarEligibility::class, 'vehicle_id', 'vehicle_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function paymentReportHistory()
    {
        return $this->hasOne(PaymentReportHistory::class, 'booking_id', 'booking_id');
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

    public function computeRentalCostDetails($rentalPrice, $tripDurationMinutes, $unlimitedKms = false, $couponCode = null, $startDate = null, $endDate = null, $vehicleTypeId = null, $extend = false, $orderType = null, $vehicleCommissionPercent = 0, $taxRate = null, $vehicleId = null)
    {
        // Initialize variables
        $couponDiscount = 0;
        $cCode = '';
        $cId = null;
        $refundableDeposit = 0;
        $tripDurationHours = $tripDurationMinutes / 60;

        // Calculate trip amount
        $tripAmount = calculateTripAmount($rentalPrice, $tripDurationHours, $vehicleId);
        if ($unlimitedKms) {
            $tripAmount *= 1.3;
        }

        // Calculate coupon discount if not an extension
        //if (!$extend && $couponCode && $startDate && $endDate) {
        if ($couponCode && $startDate && $endDate) {
            $coupon = Coupon::where('code', $couponCode)
                ->where('valid_from', '<=', $startDate)
                ->where('valid_to', '>=', $endDate)
                ->where('is_deleted', 0)
                ->first();
            //if ($coupon && $coupon->is_active && now()->between($coupon->valid_from, $coupon->valid_to)) {
            if ($coupon && $coupon->is_active) { //UPDATED ON 6-8-25
                $cCode = isset($coupon->code) ? $coupon->code : '';
                $cId = $coupon->id;
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
        $tripAmountToPay = $tripAmount - $couponDiscount;
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
        $taxRate = $customerGst ? 0.18 : 0.05;*/
        $taxAmt = $tripAmountToPay * $taxRate;
        $taxAmt += $vehicleCommissionTaxAmt;
        $totalAmount = $tripAmountToPay + $convenienceFee + $taxAmt;
        $finalAmount = $totalAmount;
        // Adjust total amount for extension
        // Calculate refundable deposit if not an extension
        // if (!$extend) {
        //     $refundableDeposit = round($rentalPrice * 2.5 * 2); // Convert to single day price and take 2 days of advance
        //     //$refundableDeposit = 0; // Convert to single day price and take 2 days of advance
        //     //$finalAmount += $refundableDeposit;
        // }

        /*$finalAmount += round($vehicleCommissionTaxAmt);
        $taxAmt += round($vehicleCommissionTaxAmt);*/

        return [
            'start_date' => date('Y-m-d H:i:s', strtotime($startDate)),
            'end_date' => date('Y-m-d H:i:s', strtotime($endDate)),
            'unlimited_kms' => (int) $unlimitedKms,
            'rental_price' => (int) $rentalPrice,
            'trip_duration_minutes' => $tripDurationMinutes,
            'trip_amount' => $tripAmount,
            'tax_amt' => round($taxAmt, 2),
            'coupon_discount' => (int) $couponDiscount,
            'coupon_code' => $cCode,
            'coupon_code_id' => $cId,
            'trip_amount_to_pay' => $tripAmountToPay,
            'convenience_fee' => $convenienceFee,
            'total_amount' => round($totalAmount, 2),
            'refundable_deposit' => $extend ? 0 : $refundableDeposit,
            'final_amount' => round($finalAmount, 2),
            'order_type' => $orderType,
            'vehicle_commission_amt' => $vehicleCommissionAmt,
            'vehicle_commission_tax_amt' => round($vehicleCommissionTaxAmt, 2),
        ];
    }

    function convertToDouble($value)
    {
        $doubleValue = number_format($value, 2);
        return $doubleValue;
    }

    public function getAdminPenaltyAmountAttribute()
    {
        $adminPenalty = AdminPenalty::where(['booking_id' => $this->booking_id, 'is_paid' => 0])->where('amount', '!=', 0)->first();
        if ($adminPenalty != '') {
            return (int) $adminPenalty->amount;
        } else {
            return 0;
        }
    }
}
