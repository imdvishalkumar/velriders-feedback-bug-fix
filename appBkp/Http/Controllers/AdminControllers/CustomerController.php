<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Crypt;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        hasPermission('customers');
        if($request->ajax()){
            $customers = Customer::where('is_deleted', 0)->get(); // Fetch all customers   
            return $customers;
        }

        return view('admin.customer.index');
    }

    public function edit($id)
    {
        $customer = Customer::find($id);
        return response()->json($customer);
    }

    public function update(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'mobile_number' => 'required',
            'country_code' => 'required',
        ]);
        $dob = NULL;
        if(isset($request->dob)){
            $dob = date('Y-m-d', strtotime($request->dob));
        }
     
        $customer = Customer::find($request->customer_id);
        $oldVal = clone $customer;

        $customer->email = $request->email;
        $customer->mobile_number = $request->mobile_number;
        $customer->country_code = $request->country_code;
        $customer->dob = $dob;
        $customer->billing_address = $request->billing_address;
        $customer->shipping_address = $request->shipping_address;
        $customer->save();
        $newVal = $customer;

        $array1 = $oldVal->toArray();
        $array2 = $newVal->toArray();
        unset($array1['documents']);
        unset($array2['documents']);
        // Find differences between arrays
        $differences = array_diff_assoc($array1, $array2);
        
        if(isset($differences) && is_countable($differences) && count($differences) > 0){
            logAdminActivity('Customer Updation', $oldVal, $newVal);
        }

        return response()->json(['message' => 'Customer updated successfully']);
    }

    public function deleteCustomer(Request $request)
    {
        $customerId = $request->customerId;
        $customer = Customer::find($customerId);
        if (!$customer) {
            return response()->json(['error' => 'customer not found'], 404);
        }
        //$customer->delete();
        $customer->is_deleted = 1;
        $customer->save();
        logAdminActivity("Customer Deletion", $customer);

        return response()->json(['message' => 'Customer deleted successfully', 'status' => true]);
    }

    public function blockCustomer(Request $request)
    {   
        $custId = $request->custId;
        $customer = Customer::find($custId);
        $customer->is_blocked =  $request->status == 'blocked' ? 1 : 0;
        $customer->save();

        if($request->status == 'blocked'){
            logAdminActivity("Customer Block Activity", $customer);
        }
        else{
            logAdminActivity("Customer Un-Block Activity", $customer);
        }
    }

    public function customerSendMail(Request $request, $customerId){
        $status = false;
        $customerDetails = Customer::select('customer_id', 'email', 'is_deleted', 'is_blocked')->where(['customer_id' => $customerId, 'is_deleted' => 0, 'is_blocked' => 0])->first();
        if($customerDetails != '' && $customerDetails->email != ''){
            //Send mail to admin
            $to = $customerDetails->email;
            $subject = "Email Verification";
            $from = config('global_values.mail_from');
            $customerId = Crypt::encrypt($customerDetails->customer_id);
            $name = $customerDetails->firstname ?? '';
            $name .= ' '.$customerDetails->lastname ?? '';
            $email = Crypt::encrypt($to);
            $app = Crypt::encrypt('v_main');
            if(isset($to) && $to != ''){
                try{
                    // Send Verification mail to Customer
                    Mail::send('emails.front.email_verification', ['customer_id' => $customerId, 'name' => $name, 'email' => $email, 'app' => $app], function ($m) use ($subject, $to, $from) {
                        $m->from($from)->to($to)->subject($subject);
                    });
                } catch (\Exception $e) {} 
            }
            $status = true;
        }

        return response()->json($status);
    }

}
