<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Jobs\SendAdminEmailNotification;

class EmailController extends Controller
{   
    public function showForm()
    {   
        hasPermission('send-emails');
        $customers = Customer::where(['is_deleted' => 0, 'is_blocked' => 0])->where('email', '!=', '')->get();
        return view('admin.email.emails' , compact('customers'));
    }

    public function filterData(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $customers = Customer::where(['is_deleted' => 0, 'is_blocked' => 0])->whereBetween('created_at', [$startDate, $endDate]);
        if($request->call_from == 1){
            $customers = $customers->where('email', '!=', '');
        }elseif($request->call_from == 2){
            $customers = $customers->where('mobile_number', '!=', '')->where('device_token', '!=', '');
        }
        $customers = $customers->get();
        if(is_countable($customers) && count($customers) > 0){
            foreach ($customers as $key => $value) {
                $fullName = '';
                if($value->firstname != '' || $value->firstname != null){
                    $fullName .= $value->firstname;
                }elseif($value->lastname != '' || $value->lastname != null){
                    $fullName .= ' '.$value->lastname;
                }
                $value->full_name = $fullName;
            }
        }

        return $customers;

    }

    public function sendEmail(Request $request)
    {
        // Validate the form data
        $request->validate([
            'to' => 'required',
            'subject' => 'required',
            'content' => 'required',
        ]);

        // Get form data
        $to = $request->to;
        $subject = $request->input('subject');
        $content = $request->input('content');
        $env = config('global_values.environment');
        if($env != '' && $env == 'live'){
            try{
                $adminId = auth()->guard('admin_web')->user()->admin_id ? auth()->guard('admin_web')->user()->admin_id : '';
                SendAdminEmailNotification::dispatch($to, $subject, $content,'email', $adminId)->onQueue('emails');
            } catch (\Exception $e) {}
            return redirect()->back()->with('success', 'Email has been sent successfully!');
        }else{
            return redirect()->back()->with('error', "You can't send mail on Staging Env.");    
        }
    }
}
