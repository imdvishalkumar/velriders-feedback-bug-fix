<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Models\{Customer, CustomerDeviceToken};
use Illuminate\Support\Facades\Log;
use App\Models\NotificationLog;

class SendAdminEmailNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;
    protected $customers;
    protected $subject;
    protected $content;
    protected $notificationType;
    protected $adminId;
    protected $showStatus;
    protected $showAllStatus;

    /**
     * Create a new job instance.
     */
    public function __construct($customers, $subject, $content, $notificationType, $adminId, $showStatus = 0, $showAllStatus = 0)
    {
        $this->customers = $customers;
        $this->subject = $subject;
        $this->content = $content;
        $this->notificationType = $notificationType;
        $this->adminId = $adminId;
        $this->showStatus = $showStatus;
        $this->showAllStatus = $showAllStatus;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //php artisan queue:work --queue=emails <- Command to run Queue Job
        if(isset($this->customers) && is_countable($this->customers) && count($this->customers) > 0){
            $sentStatus = false;
            foreach($this->customers as $key => $val){
                //Send Admin Emails
                if($this->notificationType == 'email'){
                    try{
                        $customer = Customer::select('customer_id', 'mobile_number', 'device_token', 'updated_at', 'email')->where('email', $val)->latest('updated_at')->first();
                        $from = 'support@velriders.com';
                        // Send email to multiple recipients
                        $mailContent = $this->content;
                        $mailSubject = $this->subject;
                        Mail::send('emails.sendemail', ['subject' => $this->subject , 'content' => $this->content, 'to' => $val], function ($m) use ($mailContent, $mailSubject, $val, $from) {
                            $m->from($from);
                            $m->to($val)->subject($this->subject);
                        });
                        if($this->showAllStatus != 1){
                            $notificationLog = new NotificationLog();
                            $notificationLog->customer_id = $customer ? $customer->customer_id : NULL;
                            $notificationLog->type = 1; // 1 Means Email
                            $notificationLog->status = 1;
                            $notificationLog->event_type = $this->subject;
                            $notificationLog->message_text = $this->content;
                            $notificationLog->is_show = 0;
                            $notificationLog->save();
                        }
                    } catch (\Exception $e) {} 
                }
                //Send Admin Mobile push Notifications
                if($this->notificationType == 'push_notification'){
                  // try{    
                        $customer = Customer::select('customer_id', 'mobile_number', 'device_token', 'updated_at')->where('mobile_number', $val)->latest('updated_at')->first();
                        if($customer != ''){
                            $customerDeviceToken = CustomerDeviceToken::where('customer_id', $customer->customer_id)->where('device_token', '!=', '')->where('is_deleted', 0)->where('is_error', 0)->get();
                            if(isset($customerDeviceToken) && is_countable($customerDeviceToken) && count($customerDeviceToken) > 0){
                                if($this->showAllStatus != 1){
                                    foreach($customerDeviceToken as $key => $value){
                                        try{
                                            $notificationResponse = sendPushNotification($value->device_token, $this->subject, $this->content);
                                            if(isset($notificationResponse['status_code']) && $notificationResponse['status_code'] == 200){
                                                $sentStatus = true;
                                            }else{
                                                Log::error('Something went wrong.. Notification not sent to this number - '. $customer->mobile_number.'and its response - '.json_encode($notificationResponse));
                                            } 
                                        } catch (\Exception $e) {
                                            $deviceToken = CustomerDeviceToken::where('customer_id', $customer->customer_id)->where('device_token', $value->device_token)->first();
                                            if($deviceToken != ''){
                                                $deviceToken->is_error = 1;
                                                $deviceToken->error_log = json_encode($e->getMessage());
                                                $deviceToken->save();
                                            }
                                            continue;
                                        }  
                                    }
                                    $notificationLog = new NotificationLog();
                                    $notificationLog->customer_id = $customer->customer_id;
                                    $notificationLog->type = 2; // 2 Means Push notification
                                    $notificationLog->status = 1;
                                    $notificationLog->event_type = $this->subject;
                                    $notificationLog->message_text = $this->content;
                                    $notificationLog->is_show = $this->showStatus;
                                    $notificationLog->save();
                                }
                            }
                        }
                        // OLD CODE
                        // if($customer != '' && (isset($customer->device_token) || $customer->device_token != NULL || $customer->device_token != '')){
                        //     $notificationResponse = sendPushNotification($customer->device_token, $this->subject, $this->content);
                        //     if(isset($notificationResponse['status_code']) && $notificationResponse['status_code'] == 200){

                        //         $notificationLog = new NotificationLog();
                        //         $notificationLog->customer_id = $customer->customer_id;
                        //         $notificationLog->type = 2; // 2 Means Push notification
                        //         $notificationLog->status = 1;
                        //         $notificationLog->event_type = $this->subject;
                        //         $notificationLog->message_text = $this->content;
                        //         $notificationLog->is_show = $this->showStatus;
                        //         $notificationLog->save();

                        //     }else{
                        //         Log::error('Something went wrong.. Notification not sent to this number - '. $customer->mobile_number.'and its response - '.json_encode($notificationResponse));
                        //     }                       
                        // }
                   // } catch (\Exception $e) {} 
                }
            }
        }else if($this->customers == 0 && $this->notificationType == 'push_notification' && $this->showAllStatus == 1){ // Notifications to all users
            try{
                $notificationResponse = sendTopicPushNotification($this->subject, $this->content);
                if(isset($notificationResponse['status_code']) && $notificationResponse['status_code'] == 200){
                    $sentStatus = true;
                }else{
                    Log::error('Something went wrong.. Notification not sent and its response - '.json_encode($notificationResponse));
                } 
            } catch (\Exception $e) {}  
            $notificationLog = new NotificationLog();
            $notificationLog->customer_id = NULL;
            $notificationLog->type = 2; //1 means Email & 2 means Push Notification
            $notificationLog->status = 1;
            $notificationLog->event_type = $this->subject;
            $notificationLog->message_text = $this->content;
            $notificationLog->is_show = 1;
            $notificationLog->save();
        }else if($this->customers == 0 && $this->notificationType == 'email' && $this->showAllStatus == 1){ // Email to all users
            $customers = Customer::select('email')->whereNotNull('email')->whereNotNull('email_verified_at')->where(['is_deleted' => 0, 'is_blocked' => 0])->get();
            $from = 'support@velriders.com';
            // Send email to multiple recipients
            $mailContent = $this->content;
            $mailSubject = $this->subject;
            try{
                if(isset($customers) && is_countable($customers) && count($customers) > 0){
                    foreach($customers as $key => $val){
                        $email = $val->email;
                        Mail::send('emails.sendemail', ['subject' => $this->subject , 'content' => $this->content, 'to' => $email], function ($m) use ($mailContent, $mailSubject, $email, $from) {
                            $m->from($from);
                            $m->to($email)->subject($this->subject);
                        });
                    }
                }
            } catch (\Exception $e) {} 

            $notificationLog = new NotificationLog();
            $notificationLog->customer_id = NULL;
            $notificationLog->type = 1; //1 means Email & 2 means Push Notification
            $notificationLog->status = 1;
            $notificationLog->event_type = $this->subject;
            $notificationLog->message_text = $this->content;
            $notificationLog->is_show = 0;
            $notificationLog->save();
        }
    }
}
