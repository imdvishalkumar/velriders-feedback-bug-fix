<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Customer, CustomerDeviceToken};
use Illuminate\Support\Facades\Log;
use App\Jobs\SendAdminEmailNotification;

class NotificationController extends Controller
{
    public function showForm()
    { 
        hasPermission('send-mobile-notification');
       $customers = Customer::where(['is_deleted' => 0, 'is_blocked' => 0, 'is_test_user' => 0])
                    ->whereHas('customerDeviceToken', function ($query) {
                        $query->where('is_deleted', 0);
                    })
                    //->where('device_token', '!=', '')
                    ->where('mobile_number', '!=', '')
                    ->get();
        return view('admin.notifications.index' , compact('customers'));
        // $customers = Customer::where(['is_deleted' => 0, 'is_blocked' => 0, 'is_test_user' => 0])
        //             ->whereHas('customerDeviceToken', function ($query) {
        //                 $query->where('is_deleted', 0);
        //             })
        //             ->where('mobile_number', '!=', '')
        //             ->where('mobile_number', '!=', '8090100711')
        //             ->get();
        // $additionalCustomer = Customer::where('mobile_number', '8090100711')->first();
        // if ($additionalCustomer && !$customers->contains('customer_id', $additionalCustomer->customer_id)) {
        //     $customers->push($additionalCustomer);
        // }
        // return view('admin.notifications.index' , compact('customers'));
    }

    public function sendPushNotifications(Request $request){
        $showStatus = 0;
        $showAllStatus = 0;
        if($request->selectall && $request->showguest){
            $showStatus = 1;
        }
        if($request->selectall){
            $showAllStatus = 1;
        }
        
        // $request->validate([
        //     'to' => 'required',
        //     'title' => 'required',
        //     'content' => 'required',
        // ]);
        // Get form data
        
        $to = $request->to;
        $title = $request->input('title');
        $content = $request->input('content');
        $env = config('global_values.environment');
        if(isset($to) && is_countable($to) && $to[0] == 0){
            $to = 0;
        }
        if($env != '' && $env == 'live'){
            try{
                $adminId = auth()->guard('admin_web')->user()->admin_id ? auth()->guard('admin_web')->user()->admin_id : '';
                SendAdminEmailNotification::dispatch($to, $title, $content,'push_notification', $adminId, $showStatus, $showAllStatus)->onQueue('emails');
            } catch (\Exception $e) {}
            return redirect()->back()->with('success', 'Notification has been sent successfully!');
        }else{
            return redirect()->back()->with('error', 'You can not send notification on staging Environment');
        }
    }

}
