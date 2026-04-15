<?php

namespace App\Http\Controllers\AdminControllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use Illuminate\Support\Facades\Auth;

use App\Jobs\SendNotificationJob;
use App\Models\Payment;
use GuzzleHttp\Client;
use Razorpay\Api\Api;
use Carbon\Carbon;

class AdminController extends Controller
{

    public function getAdminDashboard(){

        return view('admin.dashboard');
    }

    public function getLogin(Request $request)
    {
        return view('admin.admin-login');
    }

    // LARAVEL LOGIN
    public function postLogin(Request $request){
        $this->validate($request, [
            'username' => 'required',
            'password' => 'required',
        ]);
        
        if (auth()->guard('admin_web')->attempt(['username' => $request->input('username'), 'password' => $request->input('password')]))
        {
            $admin = auth()->guard('admin_web')->user();
            
            return redirect()->route('admin.dashboard')->with('success','You are Login successfully!!');
            
        } else {
            return back()->with('error','your Username OR Password are wrong.');
        }
    }

    // public function postLogin(Request $request){
    //     $this->validate($request, [
    //         'username' => 'required',
    //         'password' => 'required',
    //     ]);
        
    //     if (auth()->guard('admin')->attempt(['username' => $request->input('username'), 'password' => $request->input('password')]))
    //     {
    //         $admin = auth()->guard('admin')->user();

    //         return $this->successResponse('You are Login successfully!!');
    //         //return redirect()->route('admin.dashboard')->with('success','You are Login successfully!!');
            
    //     } else {
    //         return $this->errorResponse('Your Username OR Password are wrong.');
    //         //return back()->with('error','your Username OR Password are wrong.');
    //     }
    // }

    /*public function login(Request $request){

        $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        $username = $request->input('username');

        $admin = AdminUser::where('username', $username)
            ->first();

        if ($admin && Auth::guard('admin')->attempt($request->only('username', 'password'))) {
            return response()->json([
                'status' => true,
                'message' => 'Login successful'
            ], 200);
        }else{
            return response()->json([
                'status' => false,
                'message' => 'Invalid username or password'
            ], 200);
        }
    }*/

    public function getAdmins(){
        hasPermission('admins');

        //$admins = AdminUser::whereNotIn('admin_id', [1])->get();
        $admins = AdminUser::select('admin_users.admin_id as id', 'admin_users.username', 'roles.name as rolename', 'admin_users.created_at')->where('is_deleted', 0)->leftJoin('roles', 'roles.id', '=', 'admin_users.role')->whereNotIn('admin_id', [1])->get();
      
        return response()->json([
            'status' => true,
            'message' => 'Admins fetched successfully',
            'data' => $admins
        ], 200);
    }

    public function getAdmin(Request $request){
        $admin = AdminUser::find($request->input('id'));
        return response()->json([
            'status' => true,
            'message' => 'Admin fetched successfully',
            'data' => $admin
        ], 200);
    }

    public function createAdmin(Request $request){
        $request->validate([
            'username' => 'required',
            'password' => 'required',
            'role' => 'required',
        ]);

        $admin = new AdminUser();
        $admin->username = $request->input('username');
        $admin->password = bcrypt($request->input('password'));
        $admin->role = $request->input('role');
        $admin->save();

        if(isset($request->role) && $request->role != ''){
            if($request->role == 2){
                $permission_moduleids = config('global_values.manager_permissions');
                $admin->syncPermissions($permission_moduleids);     
            }elseif($request->role == 3){
                $permission_moduleids = config('global_values.accountant_permissions');
                $admin->syncPermissions($permission_moduleids);     
            }elseif($request->role == 4){
                $permission_moduleids = config('global_values.admin');
                $admin->syncPermissions($permission_moduleids);     
            }
        }

        //Assign Sub Admin Permission
        /*$permission_moduleids = config('global_values.subadmin_permissions');
        $admin->syncPermissions($permission_moduleids);     */

        return response()->json([
            'status' => true,
            'message' => 'Admin created successfully'
        ], 200);
    }

    public function updateAdmin(Request $request){
        $request->validate([
            'id' => 'required',
            'username' => 'required',
            'role' => 'required',
        ]);

        $admin = AdminUser::find($request->input('id'));
        $admin->username = $request->input('username');
        $admin->role = $request->input('role');
        $admin->save();

        if(isset($request->role) && $request->role != ''){
            if($request->role == 2){
                $permission_moduleids = config('global_values.manager_permissions');
                $admin->syncPermissions($permission_moduleids);     
            }elseif($request->role == 3){
                $permission_moduleids = config('global_values.accountant_permissions');
                $admin->syncPermissions($permission_moduleids);     
            }elseif($request->role == 4){
                $permission_moduleids = config('global_values.admin');
                $admin->syncPermissions($permission_moduleids);     
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Admin updated successfully'
        ], 200);
    }

    public function deleteAdmin(Request $request){
        $admin = AdminUser::find($request->input('id'));
        if(isset($admin->role) && $admin->role != ''){
            //Remove this particular admin user's permissions
            if($admin->role == 2){
                $permission_moduleids = config('global_values.manager_permissions');
                $admin->revokePermissionTo($permission_moduleids);   
            }elseif($admin->role == 3){
                $permission_moduleids = config('global_values.accountant_permissions');
                $admin->revokePermissionTo($permission_moduleids);
            }elseif($request->role == 4){
                $permission_moduleids = config('global_values.admin');
                $admin->syncPermissions($permission_moduleids);     
            }
        }
        $admin->is_deleted = 1;
        $admin->save();
        //$admin->delete();
        return response()->json([
            'status' => true,
            'message' => 'Admin deleted successfully'
        ], 200);
    }

    public function getAdminList(){
        hasPermission('admins');
        return view('admin.users');
    }

    public function testCode(Request $request){
        // TEST ICICI PHICOMMERCE PAYMENT GATEWAY
        $merchantTrnNum = random_int(100, 100000);
        $txnDate = Carbon::now()->format('YmdHis');
        $txnDate = (string)$txnDate;
        $bookingId = 100;
        $params = [
            "merchantId"       => "T_03338",
            "merchantTxnNo"    => $merchantTrnNum,
            "amount"           => "300.00",
            "currencyCode"     => "356",
            "payType"          => "0",
            "customerEmailID"  => "testicicipg@yopmail.com",
            "transactionType"  => "SALE",
            "txnDate"          => $txnDate,
            "returnURL"        => url('test1'),
            "customerMobileNo" => "917498791441",
            "addlParam1"       => $bookingId,
        ];
        // Step 1: Sort params by key name in ascending order
        ksort($params);
        // Step 2: Concatenate parameter values (ignore null/empty values)
        $concatenated = "";
        foreach ($params as $key => $value) {
            if ($value !== null && $value !== "") {
                $concatenated .= $value;
            }
        }
        // 🔑 Replace this with the actual key shared by PhiCommerce
        $secretKey = 'abc'; //"YOUR_SECRET_KEY_HERE";
        // Step 3: Generate HMAC SHA256 hash
        $hash = hash_hmac("sha256", $concatenated, $secretKey);
        // Step 4: Convert to lowercase HEX (hash_hmac already returns lowercase)
        $secureHash = strtolower($hash);
        $newKey   = "secureHash";
        $newValue = $secureHash;
        // Appended secureHash after returnURL
        $pos = array_search("returnURL", array_keys($params)) + 1; // position after returnURL
        $params = array_slice($params, 0, $pos, true) + [$newKey => $newValue] + array_slice($params, $pos, null, true);
        //\Log::info("REQ 11 - " . $params);
        // CALL SALES API
        $client = new Client();    
        $response = $client->post('https://qa.phicommerce.com/pg/api/v2/initiateSale', [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json' => $params,
        ]);
        $body = $response->getBody()->getContents();
        //\Log::info("RES 11 - " . $body);
        $body = json_decode($body, true);
        $tranCtx = $body['tranCtx'] ?? '';
        $redirectUri = $body['redirectURI'] ?? '';
        if(isset($tranCtx) && $tranCtx != '' && isset($redirectUri) && $redirectUri != ''){
            $redirectUrl = $redirectUri.'?tranCtx='.$tranCtx; 
        }else{
            $redirectUrl = 'https://test.velriders.com/test1';
        }
        return redirect()->to($redirectUrl);
    }

    public function testCode1(Request $request){
        // Array
        //     (
        //         [secureHash] => e52924fc881f6fc62aba21cbda50fd75d92e770a58d1daf9f6289b1d56c7d4b8
        //         [amount] => 300.00
        //         [respDescription] => Transaction successful
        //         [paymentMode] => NB
        //         [customerEmailID] => testicicipg@yopmail.com
        //         [responseCode] => 0000
        //         [customerMobileNo] => 917498791441
        //         [paymentSubInstType] => Phicom Test bank
        //         [merchantId] => T_03338
        //         [paymentID] => 56875698848
        //         [merchantTxnNo] => 62035
        //         [paymentDateTime] => 20250825132300
        //         [txnID] => 7700201624524
        //     )
        //\Log::info("RES 2 - " . json_encode($request->all()));

        $merchantId = $request->merchantId ?? '';
        $merchantTxnNo = $request->merchantTxnNo ?? '';
        $amount = $request->amount ?? '';
        $paymentID = $request->paymentID ?? '';
        $txnID = $request->txnID ?? '';
        $params = [
            "merchantID"       => $merchantId,
            "merchantTxnNo"    => $merchantTxnNo,
            "originalTxnNo"    => $merchantTxnNo,
            "transactionType"  => "STATUS",
            "amount"           => $amount,
        ];
        // Step 1: Sort params by key name in ascending order
        ksort($params);
        // Step 2: Concatenate parameter values (ignore null/empty values)
        $concatenated = "";
        foreach ($params as $key => $value) {
            if ($value !== null && $value !== "") {
                $concatenated .= $value;
            }
        }
        // 🔑 Replace this with the actual key shared by PhiCommerce
        $secretKey = 'abc'; //"YOUR_SECRET_KEY_HERE";
        // Step 3: Generate HMAC SHA256 hash
        $hash = hash_hmac("sha256", $concatenated, $secretKey);        
        // Step 4: Convert to lowercase HEX (hash_hmac already returns lowercase)
        $secureHash = strtolower($hash);
        // CALL STATUS CHECK API
        $client = new Client(); 
        $response = $client->post('https://qa.phicommerce.com/pg/api/command', [
            'multipart' => [
                [
                    'name'     => 'merchantID',
                    'contents' => $merchantId
                ],
                [
                    'name'     => 'merchantTxnNo',
                    'contents' => $merchantTxnNo
                ],
                [
                    'name'     => 'originalTxnNo',
                    'contents' => $merchantTxnNo
                ],
                [
                    'name'     => 'transactionType',
                    'contents' => 'STATUS'
                ],
                [
                    'name'     => 'secureHash',
                    'contents' => $secureHash
                ],
                [
                    'name'     => 'amount',
                    'contents' => $amount
                ],
            ],
        ]);
        // RESPONSE
        //      {
        //     "txnRespDescription": "Transaction successful",
        //     "secureHash": "282a6dfd8a64e88506027dfbe41ac074997dc239fe97c5301a86aeeee8aa0270",
        //     "amount": "300.00",
        //     "txnResponseCode": "0000",
        //     "txnAuthID": "85599466969",
        //     "respDescription": "Request processed successfully",
        //     "paymentMode": "NB",
        //     "customerEmailID": "testicicipg@yopmail.com",
        //     "responseCode": "000",
        //     "customerMobileNo": "917498791441",
        //     "txnStatus": "SUC",
        //     "paymentSubInstType": "Phicom Test bank",
        //     "merchantId": "T_03338",
        //     "merchantTxnNo": "43152",
        //     "paymentDateTime": "20250825154200",
        //     "txnID": "7700201624615"
        // }
        // REQ – Request received and in process
        // SUC – Transaction Successful
        // REJ – Transaction Rejected
        // ERR – Error in transaction process
        $response = $response->getBody();
        $response = json_decode($response);
        if($response->txnResponseCode == "0000" && $response->txnStatus == 'SUC'){
            
            return $this->successResponse(null, 'Payment completed successfully');
        }else{
            return $this->errorResponse('Something went wrong');
        }
    }

    public function test(){

        /*$cClientId = get_env_variable('CASHFREE_PAYMENT_LIVE_CLIENTID');
        $cSecretId = get_env_variable('CASHFREE_PAYMENT_LIVE_CLIENTSECRET');
        $cUrl = "https://api.cashfree.com/pg/orders";
        $cashfreeApiVersion = '2023-08-01';*/

        /*select * from `payments` where `status` = 'captured' and `payment_id` <= 2000 and 
        `payment_id` >= 1994 and `payment_gateway_used` != '' and `payment_env`
         is not null and `payment_gateway_used` = 'cashfree';*/

        /*$payments = Payment::where('status', 'captured')->where('payment_id','<=', 1959)->where('payment_id', '>=', 1869)->where('payment_gateway_used', '!=', '')->where('payment_env', '!=', NULL)->where('payment_gateway_used', 'cashfree')->get();
        //print_r($payments); die;
        foreach ($payments as $key => $value) {
            if($value->payment_gateway_used == 'cashfree'){
                $client = new Client();
                    $response = $client->request('GET', $cUrl . '/' . $value->cashfree_order_id, [
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
                        $cashfreeCharges = 0;
                        //try {
                            //Get Transaction Chargers
                            $url = $cUrl."/".$value->cashfree_order_id."/settlements";
                            $cashfreeSettlementRes = $client->request('GET', $url, [
                                'headers' => [
                                    'x-client-id' => $cClientId,
                                    'x-client-secret' => $cSecretId,
                                    'x-api-version' => $cashfreeApiVersion,
                                    'Content-Type' => 'application/json',
                                ],
                            ]);
                            $responseBody = json_decode($cashfreeSettlementRes->getBody()->getContents(), true);
                            Log::info("RESPONSE - ". json_encode($responseBody));
                            $cashfreeFees = $responseBody['service_charge'] ?? 0;
                            $cashfreeTax = $responseBody['service_tax'] ?? 0;
                            $cashfreeCharges = $cashfreeFees + $cashfreeTax;
                            //$cashfreeCharges = round($cashfreeCharges);
                            
                        //}catch(Exception $e){}
                        $value->payment_gateway_charges = $cashfreeCharges;
                        $value->status = 'captured';
                        $value->save();
                    } 
                }
            }elseif ($value->payment_gateway_used == 'razorpay') {
                    $rKey = get_env_variable('RAZORPAY_API_LIVE_KEY');
                    $rSecret = get_env_variable('RAZORPAY_API_LIVE_SECRET');
                    $api = new Api($rKey, $rSecret);
                    $payment = Payment::where('razorpay_order_id', $value->razorpay_order_id)->first();
                    if (!$payment) {
                        return $this->errorResponse('You have passed an invalid Order Id');
                    }
                    $orderStatus = [];
                    try {
                        $orderStatus = $api->order->fetch($value->razorpay_order_id)->payments();
                    } catch (\Razorpay\Api\Errors\Error $e) {
                        
                    }

                    if(is_countable($orderStatus['items']) && count($orderStatus['items']) > 0){
                        $items = $orderStatus->toArray();  // Convert the collection to an array
                        $items = $items['items'] ?? [];  
                        //$items = $orderStatus['items'] ?? [];
                        $capturedOrAuthorized = array_filter($items, function($v) {
                            return !empty($v['status']) && in_array($v['status'], ['captured', 'authorized']) && !empty($v['id']);
                        });
                        if (!empty($capturedOrAuthorized)) {
                            $razorpayFees = 0;
                            $razorpayTax = 0;
                            $paymentData = reset($capturedOrAuthorized);
                            $razorpayFees = $paymentData['fee'] ?? 0;
                            $razorpayTax = $paymentData['tax'] ?? 0;
                            $razorpayCharges = $razorpayFees + $razorpayTax;
                            $razorpayCharges = (int)round($razorpayCharges);
                            $payment->update([
                                'status' => 'captured',
                                'razorpay_payment_id' => $paymentData['id'],
                                'payment_gateway_charges' => $razorpayCharges,
                            ]);
                        } 
                    }
                }
        }
        die('done');*/
        //$filePath = public_path().'\test_attachment.pdf';
        //$attachments = [];
        //if (file_exists($filePath)) $attachments[] = $filePath;
        //SendNotificationJob::dispatch(1, 76, 'new_booking')->onQueue('emails');
    }

}
