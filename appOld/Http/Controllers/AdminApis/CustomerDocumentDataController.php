<?php

namespace App\Http\Controllers\AdminApis;

use App\Http\Controllers\Controller; 
use Illuminate\Http\Request;
use App\Models\{Customer, CustomerDocument, RejectionReason};
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;

class CustomerDocumentDataController extends Controller
{
    protected $cashfreeClientId;
    protected $cashfreeClientSecret;
    protected $cashfreeAadharApiUrl;
    protected $cashfreeAadharVerifyApiUrl;
    protected $cashfreePassportApiUrl;
    protected $cashfreeElectionApiUrl;

    public function __construct()
    {
        $this->cashfreeClientId = get_env_variable('CASHFREE_CLIENTID');
        $this->cashfreeClientSecret = get_env_variable('CASHFREE_CLIENTSECRET');
        $this->cashfreeDlApiUrl = config('global_values.cashfree_verification_live_url') . 'verification/driving-license';
        $this->cashfreeAadharApiUrl = config('global_values.cashfree_verification_live_url') . 'verification/offline-aadhaar/otp';
        $this->cashfreeAadharVerifyApiUrl = config('global_values.cashfree_verification_live_url') . 'verification/offline-aadhaar/verify';
        $this->cashfreePassportApiUrl = config('global_values.cashfree_verification_live_url') . 'verification/passport';
        $this->cashfreeElectionApiUrl = config('global_values.cashfree_verification_live_url') . 'verification/voter-id';

        // $this->cashfreeClientId = get_env_variable('CASHFREE_TEST_CLIENTID');
        // $this->cashfreeClientSecret = get_env_variable('CASHFREE_TEST_CLIENTSECRET');
        // $this->cashfreeDlApiUrl = config('global_values.cashfree_verification_test_url').'verification/driving-license';
        // $this->cashfreeAadharApiUrl = config('global_values.cashfree_verification_test_url').'verification/offline-aadhaar/otp';
        // $this->cashfreeAadharVerifyApiUrl = config('global_values.cashfree_verification_test_url').'verification/offline-aadhaar/verify';
        // $this->cashfreePassportApiUrl = config('global_values.cashfree_verification_test_url').'verification/passport';
        // $this->cashfreeElectionApiUrl = config('global_values.cashfree_verification_test_url').'verification/voter-id';
    }

    public function getCustomerDocuments(Request $request){
        $page = $request->input('page');
        $pageSize = $request->input('page_size');
        $orderColumn = $request->order_column ?? '';
        $orderType = $request->order_type ?? '';
        $search = $request->search ?? '';
        $orderTypes = config('global_values.order_types');
        $orderTypes = implode(',', $orderTypes);
        $validator = Validator::make($request->all(), [
            'customer_id' => 'nullable|exists:customers,customer_id',
            'order_type' => 'nullable|in:'.$orderTypes,
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        // NEW CODE
        $customerDocs = CustomerDocument::select(
            'customer_documents.*',
            'customers.customer_id as customerid',
            'customers.firstname',
            'customers.lastname',
            'customers.email',
            'customers.country_code',
            'customers.mobile_number',
            'customers.profile_picture_url',
            'customer_documents.govtid_type',
            'customer_documents.dob',
            'customer_documents.rejection_message_id',
        )->leftJoin('customers', 'customers.customer_id', '=', 'customer_documents.customer_id')->with('customer')->with("approvedBy:admin_id,username")->with('rejectionReason:id,reason');

        if (!empty($request->customer_id)) {
            $customerDocs = $customerDocs->where('customers.customer_id', $request->customer_id);
        }
        if(isset($search) && $search != ''){
            $customerDocs = $customerDocs->where(function ($query) use ($search) {
                $query->whereRaw('LOWER(customers.firstname) LIKE LOWER(?)', ["%$search%"])
                      ->orWhereRaw('LOWER(customers.lastname) LIKE LOWER(?)', ["%$search%"])
                      ->orWhereRaw('LOWER(customers.email) LIKE LOWER(?)', ["%$search%"])
                      ->orWhereRaw('LOWER(customers.mobile_number) LIKE LOWER(?)', ["%$search%"]);
            });
        }
        if($orderColumn != '' && $orderType != ''){
            $customerDocs = $customerDocs->orderBy($orderColumn, $orderType);
        }else{
            $customerDocs = $customerDocs->orderBy('created_at', 'DESC');
        }

        $customerDocuments = [];
        if ($page !== null && $pageSize !== null) {
            $custDocs = $customerDocs->paginate($pageSize, ['*'], 'page', $page);
            $decodedCustDocs = json_decode(json_encode($custDocs->getCollection()->values()), FALSE);
            foreach ($decodedCustDocs as $key => $value) {
                if ($value->document_type === 'dl')
                    $value->document_type = 'Driving License';
                elseif($value->document_type === 'govtid')
                    $value->document_type = 'Government ID';
                
                if ($value->vehicle_type == 'car')
                    $value->vehicle_type = 'Car';
                elseif ($value->vehicle_type == 'bike')
                    $value->vehicle_type = 'Bike';
                elseif ($value->vehicle_type == 'car/bike')
                    $value->vehicle_type = 'Car & Bike';
                else
                    $value->vehicle_type = 'Unknown';

                if ($value->is_approved === 'awaiting_approval')
                    $value->is_approved = 'Awaiting Approval';
                elseif($value->is_approved === 'approved')
                    $value->is_approved = 'Approved';
                elseif($value->is_approved === 'rejected')
                    $value->is_approved = 'Rejected';
            
                $customerDocuments[$key]['customer_details'] = [
                    'customer_id' => $value->customer ? $value->customer->customer_id : '',
                    'firstname' => $value->customer ? $value->customer->firstname : '',
                    'lastname' => $value->customer ? $value->customer->lastname : '',
                    'email' => $value->customer ? $value->customer->email : '',
                    'country_code' => $value->customer ? $value->customer->country_code : '',
                    'mobile_number' => $value->customer ? $value->customer->mobile_number : '',
                    'profile_picture_url' => $value->customer ? $value->customer->profile_picture_url : '',
                ];
                $rejectMessage = $value->custom_rejection_message;
                if(isset($value->rejection_message_id)){
                   $rejectMessage = $value->rejection_reason->reason ?? '';
                }

                $customerDocuments[$key]['document_id'] = $value->document_id;
                $customerDocuments[$key]['document_type'] = $value->document_type;
                $customerDocuments[$key]['document_number'] = $value->id_number;
                $customerDocuments[$key]['document_front_image'] = $value->document_image_url;
                $customerDocuments[$key]['document_back_image'] = $value->document_back_image_url;
                $customerDocuments[$key]['expire_date'] = $value->expiry_date;
                $customerDocuments[$key]['approved_by'] = $value->approved_by;
                $customerDocuments[$key]['reject_message'] = $rejectMessage;
                $customerDocuments[$key]['vehicle_type'] = $value->vehicle_type;
                $customerDocuments[$key]['status'] = $value->is_approved;
                $customerDocuments[$key]['is_blocked'] = $value->is_blocked;
                $customerDocuments[$key]['govtid_type'] = $value->govtid_type;
                $customerDocuments[$key]['dob'] = $value->dob;
            }
            return $this->successResponse([
                'customerDocs' => $customerDocuments,
                'pagination' => [
                    'total' => $custDocs->total(),
                    'per_page' => $custDocs->perPage(),
                    'current_page' => $custDocs->currentPage(),
                    'last_page' => $custDocs->lastPage(),
                    'from' => ($custDocs->currentPage() - 1) * $custDocs->perPage() + 1,
                    'to' => min($custDocs->currentPage() * $custDocs->perPage(), $custDocs->total()),
                ]], 'Customer documents fetched successfully');
        }else{
            $customerDocs = $customerDocs->get();
            if(isset($customerDocs) && is_countable($customerDocs) && count($customerDocs) > 0){
                foreach ($customerDocs as $key => $value) {
                    if ($value->document_type === 'dl')
                        $value->document_type = 'Driving License';
                    elseif($value->document_type === 'govtid')
                        $value->document_type = 'Government ID';
                    
                    if ($value->vehicle_type == 'car')
                        $value->vehicle_type = 'Car';
                    elseif ($value->vehicle_type == 'bike')
                        $value->vehicle_type = 'Bike';
                    elseif ($value->vehicle_type == 'car/bike')
                        $value->vehicle_type = 'Car & Bike';
                    else
                        $value->vehicle_type = 'Unknown';

                    if ($value->is_approved === 'awaiting_approval')
                        $value->is_approved = 'Awaiting Approval';
                    elseif($value->is_approved === 'approved')
                        $value->is_approved = 'Approved';
                    elseif($value->is_approved === 'rejected')
                        $value->is_approved = 'Rejected';
                
                    $customerDocuments[$key]['customer_details'] = [
                        'customer_id' => $value->customer->customer_id,
                        'firstname' => $value->customer->firstname,
                        'lastname' => $value->customer->lastname,
                        'email' => $value->customer->email,
                        'country_code' => $value->customer->country_code,
                        'mobile_number' => $value->customer->mobile_number,
                    ];
                    $rejectMessage = $value->custom_rejection_message;
                    if(isset($value->rejection_message_id)){
                        $rejectMessage = $value->rejection_reason->reason ?? '';
                    }
                    $customerDocuments[$key]['document_id'] = $value->document_id;
                    $customerDocuments[$key]['document_type'] = $value->document_type;
                    $customerDocuments[$key]['document_number'] = $value->id_number;
                    $customerDocuments[$key]['document_front_image'] = $value->document_image_url;
                    $customerDocuments[$key]['document_back_image'] = $value->document_back_image_url;
                    $customerDocuments[$key]['expire_date'] = $value->expiry_date;
                    $customerDocuments[$key]['approved_by'] = $value->approved_by;
                    $customerDocuments[$key]['reject_message'] = $rejectMessage;
                    $customerDocuments[$key]['vehicle_type'] = $value->vehicle_type;
                    $customerDocuments[$key]['status'] = $value->is_approved;
                    $customerDocuments[$key]['is_blocked'] = $value->is_blocked;
                    $customerDocuments[$key]['govtid_type'] = $value->govtid_type;
                    $customerDocuments[$key]['dob'] = $value->dob;
                }
            }
        }

        $custDocs = [
            'customerDocs' => $customerDocuments,
        ];
        if(isset($custDocs) && is_countable($custDocs) && count($custDocs) > 0){
            return $this->successResponse($custDocs, 'Customer documents fetched successfully');
        }else{
            return $this->errorResponse('Customer documents are not found');
        }
    }

    public function getRejectReasons(Request $request){
        $rejectionReasons = RejectionReason::select('id', 'reason')->get();
        if(isset($rejectionReasons) && is_countable($rejectionReasons) && count($rejectionReasons) > 0){
            return $this->successResponse($rejectionReasons, 'Reject Reasons are get successfully');
        }else{
            return $this->errorResponse('Reject reasons are not Found');
        }
    }

    public function approveRejectBlockDocument(Request $request){
        $govtTypes = [];
        $govtType = NULL;
        if (is_countable(config('global_values.govid_types')) && count(config('global_values.govid_types')) > 0) {
            foreach (config('global_values.govid_types') as $key => $value) {
                if (isset($value['id']))
                    $govtTypes[] = $value['id'];
            }
        }
        $govtTypes = implode(',', $govtTypes);
        $validator = Validator::make($request->all(), [
            'document_id' => 'required|exists:customer_documents,document_id',
            'doc_type' => 'required|in:dl,govtid',
            'action' => 'required|in:approve,reject,block',
            'approve_via' => 'required|in:manually,cashfree',
            'govtid_type' => 'nullable',
        ]);
        $validator->sometimes('vehicle_type', 'required|in:car,bike,car/bike', function ($input) {
            return $input->doc_type === 'dl' && $input->action == 'approve';
        });
        $validator->sometimes('rejection_type', 'required|in:custom,template', function ($input) {
            return $input->action == 'reject';
        });
        $validator->sometimes('reject_message', 'required|max:500', function ($input) {
            return $input->action == 'reject' && $input->rejection_type == 'custom';
        });
        $validator->sometimes('reject_template_id', 'required|exists:rejection_messages,id', function ($input) {
            return $input->action == 'reject' && $input->rejection_type == 'template';
        });
        $validator->sometimes('status', 'required|in:0,1', function ($input) {
            return $input->action == 'block';
        });
        $validator->sometimes('approve_via', 'required', function ($input) {
            return $input->action == 'approve';
        });
        $validator->sometimes('govtid_type', 'required|in:' . $govtTypes, function ($input) {
            return $input->doc_type === 'govtid';
        });
        $validator->sometimes('dob', 'required', function ($input) {
            if ($input->approve_via == 'cashfree' &&  $input->doc_type === 'dl' || ($input->doc_type === 'govtid' && $input->govtid_type == 'passport'))
                return true;
            else
                return false;
        });

        $documentId = $request->document_id;
        $document = CustomerDocument::where(['document_id' => $documentId, 'document_type' => $request->doc_type])->first();
        if($document == ''){
            return $this->errorResponse('Document not Found');
        }
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        if($request->action == 'approve'){ //APPROVE 
            $doc = DB::table('customer_documents')->select('id_number', 'is_approved', 'rejection_message_id', 'custom_rejection_message', 'approved_by', 'vehicle_type')->where('document_id', $documentId)->first();
            $oldVal = clone $doc;
            if(strtolower($request->approve_via == 'manually')){
                $updateFields = [
                    'is_approved' => 'approved',
                    'rejection_message_id' => null,
                    'custom_rejection_message' => null,
                    'approved_by' => auth()->guard('admin')->user()->admin_id,
                    'is_approved_datetime' => date('Y-m-d H:i:s'),
                ];
                // Only include vehicle_type in the update if it's not null
                if ($request->vehicle_type != null) {
                    $updateFields['vehicle_type'] = $request->vehicle_type;
                }
                $newVal = $updateFields;
                $document->update($updateFields);
                $oldVal->document_id = $documentId;
                $newVal['document_id'] = $documentId;
                logAdminActivities('Customer Document Approval', $oldVal, $newVal);

                return $this->successResponse($document, 'Document Appoved Successfully');
            }elseif(strtolower($request->approve_via == 'cashfree')){
                $dob = date('Y-m-d', strtotime($request->dob));
                $glVerificationStatus = $dlVerificationStatus = false;
                if($request->doc_type == 'dl'){
                    $responseJson = NULL;
                    $client = new Client();
                    $dlNumber = str_replace(' ', '',$doc->id_number);
                    $uniqueDlId = substr(uniqid(), -10);
                    $uniqueDlId = "velrider_dl" . $uniqueDlId;
                    $verificationId = $uniqueDlId;
                    $apiUrl = $this->cashfreeDlApiUrl;
                    $dlResponse = $client->request('POST', $apiUrl, [
                        'headers' => [
                            'accept' => 'application/json',
                            'content-type' => 'application/json',
                            'x-client-id' => $this->cashfreeClientId,
                            'x-client-secret' => $this->cashfreeClientSecret,
                        ],
                        'json' => [
                            'verification_id' => $verificationId,
                            'dl_number' => $dlNumber,
                            'dob' => $dob,
                        ],
                        'http_errors' => false
                    ]);
                    $dlContent = $dlResponse->getBody()->getContents();
                    $dlResponseData = json_decode($dlContent, true);
                    if ($dlResponseData != '' && isset($dlResponseData['status']) && $dlResponseData['status'] != '' && strtolower($dlResponseData['status']) == 'valid') {
                        $responseJson = json_encode($dlResponseData);
                    if ($dlResponseData != '') {
                        $dlResName = $dlResponseData['details_of_driving_licence']['name'] ?? '';
                        $dlProfileLink = $dlResponseData['details_of_driving_licence']['photo'] ?? '';
                        $dlDob = $dlResponseData['dob'] ?? '';
                        $dlAddress = $dlResponseData['details_of_driving_licence']['address'] ?? '';
                    }
                    $customer = Customer::where('customer_id', $document->customer_id)->first();
                    $checkGovtId = CustomerDocument::where('customer_id', $document->customer_id)
                    ->where('document_type', 'govtid')
                    ->where('is_approved', 'approved')->first();
                    if ($checkGovtId == '') {
                        return $this->errorResponse('Verify your Government ID First');
                    } else {
                        $cashfreeRes = $checkGovtId->cashfree_api_response ? json_decode($checkGovtId->cashfree_api_response) : '';
                        if ($cashfreeRes != '') {
                            $aadharResName = $cashfreeRes->name ?? '';
                        }
                    }
                    if ($aadharResName != '' && $dlResName != '') {
                        $result = checkNameMatch($aadharResName, $dlResName);
                        if ($result == 1) {
                            $dlVerificationStatus = true;
                        }else{
                            return $this->errorResponse("You cannot upload anyone else's dl");
                        }
                    }
                    } elseif ($dlResponseData != '' && isset($dlResponseData['status']) && $dlResponseData['status'] != '' && strtolower($dlResponseData['status']) == 'invalid') {
                        return $this->errorResponse('Driving License is Invalid');
                    } else if ($dlResponseData != '' && isset($dlResponseData['type']) && $dlResponseData['type'] != '' && strtolower($dlResponseData['type']) == 'validation_error' && isset($dlResponseData['code']) && $dlResponseData['code'] != '' && strtolower($dlResponseData['code']) == 'driving_license_value_invalid') {
                        return $this->errorResponse('Driving License is Invalid');
                    } else if ($dlResponseData != '' && isset($dlResponseData['type']) && $dlResponseData['type'] != '' && strtolower($dlResponseData['type']) == 'validation_error' && isset($dlResponseData['code']) && $dlResponseData['code'] != '' && strtolower($dlResponseData['code']) == 'dob_value_invalid') {
                        return $this->errorResponse('DOB is Invalid');
                    } else if ($dlResponseData != '' && isset($dlResponseData['type']) && $dlResponseData['type'] != '' && strtolower($dlResponseData['type']) == 'validation_error' && isset($dlResponseData['code']) && $dlResponseData['code'] != '' && strtolower($dlResponseData['code']) == 'dob_missing') {
                        return $this->errorResponse('DOB is Missing');
                    } else if ($dlResponseData != '' && isset($dlResponseData['type']) && $dlResponseData['type'] != '' && strtolower($dlResponseData['type']) == 'validation_error' && isset($dlResponseData['code']) && $dlResponseData['code'] != '' && strtolower($dlResponseData['code']) == 'verification_id_missing') {
                        return $this->errorResponse('Verification Id is Missing');
                    } else if (
                    $dlResponseData != '' && isset($dlResponseData['type']) && $dlResponseData['type'] != '' && strtolower($dlResponseData['type']) == 'validation_error' && isset($dlResponseData['code']) && $dlResponseData['code'] != '' &&
                    (strtolower($dlResponseData['code']) == 'verification_id_length_exceeded') ||
                    (strtolower($dlResponseData['code']) == 'verification_id_value_invalid') ||
                    (strtolower($dlResponseData['code']) == 'verification_id_already_exists') ||
                    (strtolower($dlResponseData['code']) == 'verification_failed')
                    ) {
                        return $this->errorResponse('Verification Id is Invalid');
                    } else if (
                        $dlResponseData != '' && isset($dlResponseData['type']) && $dlResponseData['type'] != '' && strtolower($dlResponseData['type']) == 'validation_error' && isset($dlResponseData['code']) && $dlResponseData['code'] != '' &&
                        (strtolower($dlResponseData['code']) == 'x-client-id_missing') ||
                        (strtolower($dlResponseData['code']) == 'x-client-secret_value_invalid') ||
                        (strtolower($dlResponseData['code']) == 'invalid_parameters') ||
                        (strtolower($dlResponseData['code']) == 'authentication_failed') ||
                        (strtolower($dlResponseData['code']) == 'ip_validation_failed')
                    ) {
                        return $this->errorResponse('Server Error');
                    } else if ($dlResponseData != '' && isset($dlResponseData['type']) && $dlResponseData['type'] != '' && strtolower($dlResponseData['type']) == 'validation_error' && isset($dlResponseData['code']) && $dlResponseData['code'] != '' && strtolower($dlResponseData['code']) == 'insufficient_balance') {
                        return $this->errorResponse('Verification id Invalid');
                    }
                    if ($dlVerificationStatus == true) {
                        if($dlResName != ''){
                            $dlParts = explode(' ', $dlResName);
                            $dlFirstName = $dlParts[0] ?? '';
                            $dlLastName = end($dlParts);
                            $customer->firstname = $dlFirstName;
                            $customer->lastname = $dlLastName;
                            $customer->dob = $dlDob ?? NULL;
                            $customer->billing_address = $dlAddress ?? NULL;
                            $customer->save();
                        }
                        if($dlProfileLink != ''){
                            $response = Http::get($dlProfileLink);
                            if ($response->successful()) {
                                $fileName = "DL".time() . '.png';
                                $filePath = public_path('images/profile_pictures/' . $fileName);
                                if (!File::exists(public_path('images/profile_pictures/'))) {
                                    File::makeDirectory(public_path('images/profile_pictures/'), 0755, true);
                                }
                                file_put_contents($filePath, $response->body());

                                $customer->profile_picture_url = $fileName;
                                $customer->save();
                            }
                        }
                        $document->is_approved = 'approved';
                        $document->approved_by = auth()->guard('admin')->user()->admin_id;
                        $document->is_approved_datetime = date('Y-m-d H:i:s');
                        $document->cashfree_api_response = $responseJson;
                        $document->dob = isset($dob) && $dob != '' ? date('Y-m-d', strtotime($dob)) : NULL;
                        $document->save();
                        return $this->successResponse($document, 'Document updated Successfully');
                    }else{
                        return $this->errorResponse('Something went Wrong');
                    }
                }elseif($request->doc_type == 'govtid'){
                    $client = new Client();
                    $idNumber = $doc->id_number;
                    if($request->govtid_type == 'aadhar'){
                        $govtType = 'aadhar';
                        if (strlen($idNumber) != 12) {
                            return $this->errorResponse('Aadhar Number should contain 12 digits');
                        }
                        try {
                            $aadharResponse = $client->request('POST', $this->cashfreeAadharApiUrl, [
                                'body' => json_encode([
                                    'aadhaar_number' => $idNumber,
                                ]),
                                'headers' => [
                                    'accept' => 'application/json',
                                    'content-type' => 'application/json',
                                    'x-client-id' => $this->cashfreeClientId,
                                    'x-client-secret' => $this->cashfreeClientSecret,
                                ],
                                'http_errors' => false
                            ]);
                            $statusCode = $aadharResponse->getStatusCode();
                            $content = $aadharResponse->getBody()->getContents();
                            $content = json_decode($content);
                            $refId = '';
                            if ($content != '' && isset($content->status) && $content->status != '' && strtolower($content->status) == 'success') {
                                $refId = isset($content->ref_id) ? $content->ref_id : '';
                                return $this->successResponse(['ref_id' => $refId, 'govtid' => $idNumber, 'documentId' => $documentId, 'document_upload_message' => "<span style='font-style: italic;'>Document is submitted for approval.</span>"], 'OTP Sent Successfully on your registered Mobile Number');
                            } else if ($content != '' && isset($content->status) && $content->status != '' && strtolower($content->status) == 'invalid') {
                                return $this->errorResponse('Invalid Aadhaar Card');
                            } else if (isset($content->code) && (strtolower($content->code) == 'aadhaar_invalid' || strtolower($content->code) == 'aadhaar_empty') && strtolower($content->type) == 'validation_error') {
                                return $this->errorResponse("Please enter valid Aadhaar number");
                            } else if (isset($content->code) && (strtolower($content->code) == 'x-client-id_missing' || strtolower($content->code) == 'x-client-secret_value_invalid' || strtolower($content->code) == 'authentication_failed' || strtolower($content->code) == 'ip_validation_failed' || strtolower($content->code) == 'verification_pending' || strtolower($content->code) == 'insufficient_balance' || strtolower($content->code) == 'verification_failed' || strtolower($content->code) == 'api_error') && (strtolower($content->type) == 'validation_error' || strtolower($content->type) == 'authentication_error' || strtolower($content->type) == 'internal_error')) {
                                return $this->errorResponse("Something went Wrong.. Server side issue contact admin");
                                //$glIssueStatus = true;
                            }
                        } catch (ClientException $e) {
                            //return $this->errorResponse('Govt Id is Invalid');
                            $response = $e->getResponse();
                            $statusCode = $response->getStatusCode();
                            $responseBody = $response->getBody()->getContents();
                            $errorData = json_decode($responseBody, true);

                            // Check if decoding was successful and the expected data exists
                            if (json_last_error() === JSON_ERROR_NONE && isset($errorData['type'], $errorData['code'], $errorData['message'])) {
                                $errorMessage = "{$errorData['message']}";
                            } else {
                                // Fallback error message if response is not as expected
                                $errorMessage = "An unexpected error occurred. Status Code: {$statusCode}";
                            }
                            return $this->errorResponse($errorMessage);
                        }
                    }elseif($request->govtid_type == 'passport'){
                        $govtType = 'passport';
                        $responseJson = NULL;
                        if (strlen($idNumber) != 15) {
                            return $this->errorResponse('Passport Number should contain 15 characters');
                        }
                        try {
                            $prefix = 'velpassport_';
                            $verificationId = uniqid($prefix, true); //Uninque ID   
                            $dob = $request->dob ? date('Y-m-d', strtotime($request->dob)) : '';
                            $voterIdResponse = $client->request('POST', $this->cashfreePassportApiUrl, [
                                'headers' => [
                                    'accept' => 'application/json',
                                    'content-type' => 'application/json',
                                    'x-client-id' => $this->cashfreeClientId,
                                    'x-client-secret' => $this->cashfreeClientSecret,
                                ],
                                'json' => [
                                    'verification_id' => $verificationId,
                                    'file_number' => $idNumber,
                                    'dob' => $dob,
                                ],
                                'http_errors' => false
                            ]);
                            $passportContent = $voterIdResponse->getBody()->getContents();
                            $passportResponseData = json_decode($passportContent, true);
                            if (isset($passportResponseData['status']) && strtolower($passportResponseData['status']) == 'valid') {
                                $responseJson = json_encode($passportResponseData);
                                $glVerificationStatus = true;
                            } else if (isset($passportResponseData['status']) && strtolower($passportResponseData['status']) == 'invalid') {
                                return $this->errorResponse('Passport Number is Invalid');
                            } else if (isset($passportResponseData['code']) && strtolower($passportResponseData['code']) == 'verification_failed' && strtolower($passportResponseData['type']) == 'internal_error') { //For error 500, 502 
                                return $this->errorResponse("An unexpected error occurred while verifying your Govt Id. Please recheck and confirm that you have entered valid id or not");
                            } else if (isset($passportResponseData['code']) && (strtolower($passportResponseData['code']) == 'ip_validation_failed' || strtolower($passportResponseData['code']) == 'authentication_failed') && strtolower($passportResponseData['type']) == 'authentication_error') { //401, 403 IP whitelist error OR Client Id and Client Secret issue
                                //return $this->errorResponse("Something went Wrong.. Server side issue contact admin");
                                //$glIssueStatus = true;
                            } else if (isset($passportResponseData['code']) && (strtolower($passportResponseData['code']) == 'insufficient_balance' || strtolower($passportResponseData['code']) == 'verification_id_already_exists' || strtolower($passportResponseData['code']) == 'x-client-secret_value_invalid' || strtolower($passportResponseData['code']) == 'x-client-secret_value_invalid' || strtolower($passportResponseData['code']) == 'dob_value_invalid' || strtolower($passportResponseData['code']) == 'dob_missing' || strtolower($passportResponseData['code']) == 'file_number_missing' || strtolower($passportResponseData['code']) == 'verification_id_value_invalid' || strtolower($passportResponseData['code']) == 'verification_id_missing') && strtolower($passportResponseData['type']) == 'validation_error') { //422,409,400
                                //return $this->errorResponse("Something went Wrong");
                                // $glIssueStatus = true;
                            }
                        } catch (ClientException $e) {
                            return $this->errorResponse("Something went Wrong..Please try with corrent Government ID number");
                        }
                    }elseif($request->govtid_type == 'election'){
                        $govtType = 'election';
                        //Voter ID/Election Card verification
                        $responseJson = NULL;
                        if (strlen($idNumber) != 10) {
                            return $this->errorResponse('Voter Id/Election Id Number should contain 10-digit alpha-numeric');
                        }
                        try {
                            $prefix = 'velvoterid_';
                            $verificationId = uniqid($prefix, true); //Uninque ID  
                            $voterIdResponse = $client->request('POST', $this->cashfreeElectionApiUrl, [
                                'headers' => [
                                    'accept' => 'application/json',
                                    'content-type' => 'application/json',
                                    'x-client-id' => $this->cashfreeClientId,
                                    'x-client-secret' => $this->cashfreeClientSecret,
                                ],
                                'json' => [
                                    'verification_id' => $verificationId,
                                    'epic_number' => $idNumber
                                ],
                                'http_errors' => false
                            ]);
                            $voterIdContent = $voterIdResponse->getBody()->getContents();
                            $voterIdResponseData = json_decode($voterIdContent, true);
                            if (isset($voterIdResponseData['status']) && strtolower($voterIdResponseData['status']) == 'valid') { //200 Valid
                                $responseJson = json_encode($voterIdResponseData);
                                $glVerificationStatus = true;
                            } else if (isset($voterIdResponseData['status']) && strtolower($voterIdResponseData['status']) == 'invalid') { //200 Invalid
                                return $this->errorResponse('Voter Id/Election Id Number is Invalid');
                            } else if (isset($voterIdResponseData['code']) && (strtolower($voterIdResponseData['code']) == 'verification_failed' || strtolower($voterIdResponseData['code']) == 'epic_number_value_invalid') && (strtolower($voterIdResponseData['type']) == 'internal_error' || strtolower($voterIdResponseData['type']) == 'validation_error')) { //For error 500, 502 
                                return $this->errorResponse("An unexpected error occurred while verifying your Govt Id. Please recheck and confirm that you have entered valid id or not");
                            } else if (isset($voterIdResponseData['code']) && (strtolower($voterIdResponseData['code']) == 'ip_validation_failed' || strtolower($voterIdResponseData['code']) == 'authentication_failed') && strtolower($voterIdResponseData['type']) == 'authentication_error') { //401, 403 IP whitelist error OR Client Id and Client Secret issue
                                //return $this->errorResponse("Something went Wrong.. Server side issue contact admin");
                                // $glIssueStatus = true;
                            } else if (
                                isset($voterIdResponseData['code']) &&
                                (strtolower($voterIdResponseData['code']) == 'insufficient_balance' ||
                                    strtolower($voterIdResponseData['code']) == 'verification_id_already_exists' ||
                                    strtolower($voterIdResponseData['code']) == 'x-client-secret_value_invalid' ||
                                    strtolower($voterIdResponseData['code']) == 'x-client-secret_value_invalid' ||
                                    strtolower($voterIdResponseData['code']) == 'verification_id_value_invalid' ||
                                    strtolower($voterIdResponseData['code']) == 'verification_id_missing') && strtolower($voterIdResponseData['type']) == 'validation_error'
                            ) { //422,409,400
                                //return $this->errorResponse("Something went Wrong");
                                //$glIssueStatus = true;
                            }
                        } catch (Exception $e) {
                            Log::error("Unexpected error occurred", ['exception' => $e]);
                            return $this->errorResponse("An unexpected error occurred: " . $e->getMessage());
                        }
                    }

                    if ($glVerificationStatus = true && $request->doc_type == 'govtid') {
                        if (isset($request->govtid_type) && $request->govtid_type == 'aadhar') {
                            $document->is_approved = 'awaiting_approval';
                        } else {
                            $document->is_approved = 'approved';
                        }
                        $document->approved_by = auth()->guard('admin')->user()->admin_id;
                        $document->is_approved_datetime = date('Y-m-d H:i:s');
                        $document->cashfree_api_response = $responseJson;
                        $document->govtid_type = $request->govtid_type;
                        $document->dob = isset($dob) && $dob != '' ? date('Y-m-d', strtotime($dob)) : NULL;
                        $document->save();

                        return $this->successResponse($document, "Document verified Successfully");
                    }else{
                        return $this->errorResponse("Something went Wrong");
                    }
                }
            }
        }elseif($request->action == 'reject') { // REJECT
            $doc = DB::table('customer_documents')->select('is_approved', 'rejection_message_id', 'custom_rejection_message', 'approved_by')->where('document_id',$documentId)->first();
            $oldVal = clone $doc;
            $updateFields = [
                'is_approved' => 'rejected',
                'approved_by' => auth()->guard('admin')->user()->admin_id,
                'is_approved_datetime' => date('Y-m-d H:i:s'),
            ];
            if(isset($request->rejection_type) && $request->rejection_type == 'custom'){
                $updateFields['custom_rejection_message'] = $request->reject_message;
            }elseif(isset($request->rejection_type) && $request->rejection_type == 'template'){
                $updateFields['rejection_message_id'] = $request->reject_template_id;
            }
            $newVal = $updateFields;
            $document->update($updateFields);
            $oldVal->document_id = $documentId;
            $newVal['document_id'] = $documentId;
            logAdminActivities('Customer Document Rejection', $oldVal, $newVal);

            return $this->successResponse($document, 'Document Rejected Successfully');
        }elseif($request->action == 'block'){ //BLOCK / UN-BLOCK
            $document->is_blocked =  $request->status;
            $document->save();
            if($request->status == 1){
                logAdminActivities("Customer Document Block Activity", $document);
                return $this->successResponse($document, 'Document Blocked Successfully'); 
            }
            else{
                logAdminActivities("Customer Document Un-Block Activity", $document);
                return $this->successResponse($document, 'Document Un-blocked Successfully');
            }
        }else{
            return $this->errorResponse('Invaid Action');
        }
    }

    public function verifyGovtIdDocument(Request $request){
        $validator = Validator::make($request->all(), [
            'id_number' => [
                'required',
                'string',
                // Rule::unique('customer_documents')->where(function ($query) {
                //     return $query->where(function ($query) {
                //         $query->where('is_approved', 'approved')
                //             ->orWhere('is_blocked', 1);
                //     });
                // }),
            ],
            'ref_id' => 'required',
            'otp' => 'required',
            'customer_id' => 'required|exists:customers,customer_id',
            'document_id' => 'required|exists:customer_documents,document_id',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        // Retrieve the authenticated user
        // Check if there's already a document of the same type awaiting approval or approved
        $existingDocument = CustomerDocument::where('customer_id', $request->customer_id)
            ->where('document_type', 'govtid')
            ->whereIn('is_approved', ['awaiting_approval', 'approved'])
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [Carbon::now(), Carbon::now()->addMonth()])
            ->first();
            
        if ($existingDocument) {
            if ($existingDocument->is_approved === 'awaiting_approval') {
                return $this->errorResponse('There is already a document of this type awaiting approval.');
            } elseif ($existingDocument->is_approved === 'approved') {
                return $this->errorResponse('A document of this type is already approved.');
            }
        }
        $document = CustomerDocument::where('document_id', $request->document_id)->first();
        $oldVal = $newVal = '';
        $oldVal = clone $document;
        //Aadhar number Verification
        if (isset($request->ref_id) && $request->ref_id != '' && isset($request->otp) && $request->otp != '' && isset($request->id_number) && $request->id_number != '') {
            $aadharNumber = isset($request->id_number) ? $request->id_number : '';
            if ($aadharNumber != '') {
                try {
                    $client = new Client();
                    $aadharResponse = $client->request('POST', $this->cashfreeAadharVerifyApiUrl, [
                        'body' => json_encode([
                            'otp' => $request->otp ?? '',
                            'ref_id' => $request->ref_id ?? '',
                        ]),
                        'headers' => [
                            'accept' => 'application/json',
                            'content-type' => 'application/json',
                            'x-client-id' => $this->cashfreeClientId,
                            'x-client-secret' => $this->cashfreeClientSecret,
                        ],
                    ]);
                    $statusCode = $aadharResponse->getStatusCode();
                    $aadharContent = $aadharResponse->getBody()->getContents();
                    $content = json_decode($aadharContent);
                    if ($content != '' && isset($content->status) && $content->status != '' && strtolower($content->status) == 'valid') {
                        $aadharResponseJson = json_encode($content);
                        $document->customer_id = $request->customer_id;
                        $document->document_type = 'govtid';
                        $document->id_number = $request->id_number;
                        $document->vehicle_type = $request->vehicle_type;
                        $document->cashfree_api_response = $aadharResponseJson;
                        $document->is_approved = 'approved';
                        $document->approved_by = auth()->guard('admin')->user()->admin_id;;
                        $document->is_approved_datetime = date('Y-m-d H:i:s');
                        $document->govtid_type = 'aadhar';
                        $document->save();

                        $newVal = $document;
                        logAdminActivities('Verify Government ID Document', $oldVal, $newVal);
                        return $this->successResponse($document, 'Document uploaded successfully.');
                    } else {
                        return $this->errorResponse('Govt Id is Invalid');
                    }
                } catch (\Exception $e) {
                    return $this->errorResponse('Govt Id is Invalid');
                }
            }
        } else {
            return $this->errorResponse('Ref Id OR OTP OR Govt Id is Missing');
        }
    }

    public function addDocument(Request $request){
        $govtTypes = [];
        $govtType = NULL;
        if (is_countable(config('global_values.govid_types')) && count(config('global_values.govid_types')) > 0) {
            foreach (config('global_values.govid_types') as $key => $value) {
                if (isset($value['id']))
                    $govtTypes[] = $value['id'];
            }
        }
        $govtTypes = implode(',', $govtTypes);
        $validator = Validator::make($request->all(), [
            'doc_type' => 'required|in:dl,govtid',
            'customer_id' => 'required|exists:customers,customer_id',
            'doc_number' => [
                'required',
            ],
            'doc_front_img' => 'required|image|mimetypes:image/heic,image/heif,image/jpeg,image/png,image/jpg,image/bmp,image/gif,image/svg,image/webp|max:20480',
            'doc_back_img' => 'required|image|mimetypes:image/heic,image/heif,image/jpeg,image/png,image/jpg,image/bmp,image/gif,image/svg,image/webp|max:20480',
            'approve_via' => 'required|in:manually,cashfree',
            'govt_type' => 'nullable',
        ],[
            'doc_type.required' => 'Please select Document Type',
            'customer_id.required' => 'Please select Customer',
            'doc_number.required' => 'Please enter Document Number',
            'doc_front_img.required' => 'Please select Document Front Image',
            'doc_back_img.required' => 'Please select Document Back Image',
        ]);
        $validator->sometimes('vehicle_type', 'required|in:car,bike,car/bike', function ($input) {
            return $input->doc_type === 'dl';
        });
        $validator->sometimes('dob', 'required', function ($input) {
            if ($input->approve_via == 'cashfree' && $input->doc_type === 'dl' || ($input->doc_type === 'govtid' && $input->govt_type == 'passport'))
                return true;
            else
                return false;
        });
        $validator->sometimes('govt_type', 'required|in:' . $govtTypes, function ($input) {
            return $input->doc_type === 'govtid';
        });
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        $oldVal = $newVal = '';
        $customerDocument = CustomerDocument::where(['customer_id' => $request->customer_id, 'id_number' => $request->doc_number, 'document_type' => $request->doc_type])->first();
        if($customerDocument == ''){
            $customerDocument = new CustomerDocument();            
        }
        $oldVal = clone $customerDocument;
        $frontImageUrl = $backImageUrl = null;
        if ($request->hasFile('doc_front_img')) {
            $documentImage = $request->file('doc_front_img');
            if($request->doc_type == 'govtid' && $request->customer_id != ''){
                $frontImageUrl = 'govt_'.$request->customer_id.'_'.time() . '_front.' . $documentImage->getClientOriginalExtension();
            }elseif($request->doc_type == 'dl' && $request->customer_id != ''){
                $frontImageUrl = 'dl'.$request->customer_id.'_'.time() . '_front.' . $documentImage->getClientOriginalExtension();
            }
            $documentImage->move(public_path('images/customer_documents'), $frontImageUrl);
            $customerDocument->document_image_url = $frontImageUrl;
        }
        if ($request->hasFile('doc_back_img')) {
            $documentBackImage = $request->file('doc_back_img');
            if($request->doc_type == 'govtid' && $request->customer_id != ''){
                $backImageUrl = 'govt_'.$request->customer_id.'_'.time() . '_back.' . $documentBackImage->getClientOriginalExtension();
            }elseif($request->doc_type == 'dl' && $request->customer_id != ''){
                $backImageUrl = 'dl'.$request->customer_id.'_'.time() . '_back.' . $documentBackImage->getClientOriginalExtension();
            }
            $documentBackImage->move(public_path('images/customer_documents'), $backImageUrl);
            $customerDocument->document_back_image_url = $backImageUrl;
        }

        $customerDocument->customer_id = $request->customer_id;
        if(isset($request->approve_via) && $request->approve_via == 'manually'){
            $customerDocument->document_type = $request->doc_type;
            $customerDocument->id_number = $request->doc_number;
            $customerDocument->is_approved = 1;
            $customerDocument->is_approved_datetime = date('Y-m-d H:i:s');
            $customerDocument->approved_by = auth()->guard('admin')->user()->admin_id;
            if($request->doc_type == 'dl' && $request->customer_id != ''){
                $customerDocument->vehicle_type = $request->vehicle_type;
            }
            $customerDocument->save();
            $newVal = $customerDocument;
            logAdminActivities('Customer Document DRIVING LICENCE uploaded Manually', $oldVal, $newVal);
        }elseif(isset($request->approve_via) && $request->approve_via == 'cashfree'){
            $dob = date('Y-m-d', strtotime($request->dob));
            $glVerificationStatus = $dlVerificationStatus = false;
            $responseJson = NULL;
            if($request->doc_type == 'dl'){
                $client = new Client();
                $dlNumber = str_replace(' ', '',$request->doc_number);
                $uniqueDlId = substr(uniqid(), -10);
                $uniqueDlId = "velrider_dl" . $uniqueDlId;
                $verificationId = $uniqueDlId;
                $apiUrl = $this->cashfreeDlApiUrl;
                $dlResponse = $client->request('POST', $apiUrl, [
                    'headers' => [
                        'accept' => 'application/json',
                        'content-type' => 'application/json',
                        'x-client-id' => $this->cashfreeClientId,
                        'x-client-secret' => $this->cashfreeClientSecret,
                    ],
                    'json' => [
                        'verification_id' => $verificationId,
                        'dl_number' => $dlNumber,
                        'dob' => $dob,
                    ],
                    'http_errors' => false
                ]);
                $dlContent = $dlResponse->getBody()->getContents();
                $dlResponseData = json_decode($dlContent, true);
                if ($dlResponseData != '' && isset($dlResponseData['status']) && $dlResponseData['status'] != '' && strtolower($dlResponseData['status']) == 'valid') {
                    $responseJson = json_encode($dlResponseData);
                    if ($dlResponseData != '') {
                        $dlResName = $dlResponseData['details_of_driving_licence']['name'] ?? '';
                        $dlProfileLink = $dlResponseData['details_of_driving_licence']['photo'] ?? '';
                        $dlDob = $dlResponseData['dob'] ?? '';
                        $dlAddress = $dlResponseData['details_of_driving_licence']['address'] ?? '';
                    }
                    $customer = Customer::where('customer_id', $request->customer_id)->first();
                    $checkGovtId = CustomerDocument::where('customer_id', $request->customer_id)->where('document_type', 'govtid')->where('is_approved', 'approved')->first();
                    if ($checkGovtId == '') {
                        return $this->errorResponse('Verify your Government ID First');
                    } else {
                        $cashfreeRes = $checkGovtId->cashfree_api_response ? json_decode($checkGovtId->cashfree_api_response) : '';
                        if ($cashfreeRes != '') {
                            $aadharResName = $cashfreeRes->name ?? '';
                        }
                    }
                    if ($aadharResName != '' && $dlResName != '') {
                        $result = checkNameMatch($aadharResName, $dlResName);
                        if ($result == 1) {
                            $dlVerificationStatus = true;
                        }else{
                            return $this->errorResponse("You cannot upload anyone else's dl");
                        }
                    }elseif ($dlResponseData != '' && isset($dlResponseData['status']) && $dlResponseData['status'] != '' && strtolower($dlResponseData['status']) == 'invalid') {
                        return $this->errorResponse('Driving License is Invalid');
                    } else if ($dlResponseData != '' && isset($dlResponseData['type']) && $dlResponseData['type'] != '' && strtolower($dlResponseData['type']) == 'validation_error' && isset($dlResponseData['code']) && $dlResponseData['code'] != '' && strtolower($dlResponseData['code']) == 'driving_license_value_invalid') {
                        return $this->errorResponse('Driving License is Invalid');
                    } else if ($dlResponseData != '' && isset($dlResponseData['type']) && $dlResponseData['type'] != '' && strtolower($dlResponseData['type']) == 'validation_error' && isset($dlResponseData['code']) && $dlResponseData['code'] != '' && strtolower($dlResponseData['code']) == 'dob_value_invalid') {
                        return $this->errorResponse('DOB is Invalid');
                    } else if ($dlResponseData != '' && isset($dlResponseData['type']) && $dlResponseData['type'] != '' && strtolower($dlResponseData['type']) == 'validation_error' && isset($dlResponseData['code']) && $dlResponseData['code'] != '' && strtolower($dlResponseData['code']) == 'dob_missing') {
                        return $this->errorResponse('DOB is Missing');
                    } else if ($dlResponseData != '' && isset($dlResponseData['type']) && $dlResponseData['type'] != '' && strtolower($dlResponseData['type']) == 'validation_error' && isset($dlResponseData['code']) && $dlResponseData['code'] != '' && strtolower($dlResponseData['code']) == 'verification_id_missing') {
                        return $this->errorResponse('Verification Id is Missing');
                    } else if (
                        $dlResponseData != '' && isset($dlResponseData['type']) && $dlResponseData['type'] != '' && strtolower($dlResponseData['type']) == 'validation_error' && isset($dlResponseData['code']) && $dlResponseData['code'] != '' &&
                        (strtolower($dlResponseData['code']) == 'verification_id_length_exceeded') ||
                        (strtolower($dlResponseData['code']) == 'verification_id_value_invalid') ||
                        (strtolower($dlResponseData['code']) == 'verification_id_already_exists') ||
                        (strtolower($dlResponseData['code']) == 'verification_failed')
                    ) {
                        return $this->errorResponse('Verification Id is Invalid');
                    } else if (
                        $dlResponseData != '' && isset($dlResponseData['type']) && $dlResponseData['type'] != '' && strtolower($dlResponseData['type']) == 'validation_error' && isset($dlResponseData['code']) && $dlResponseData['code'] != '' &&
                        (strtolower($dlResponseData['code']) == 'x-client-id_missing') ||
                        (strtolower($dlResponseData['code']) == 'x-client-secret_value_invalid') ||
                        (strtolower($dlResponseData['code']) == 'invalid_parameters') ||
                        (strtolower($dlResponseData['code']) == 'authentication_failed') ||
                        (strtolower($dlResponseData['code']) == 'ip_validation_failed')
                    ) {
                        return $this->errorResponse('Server Error');
                    } else if ($dlResponseData != '' && isset($dlResponseData['type']) && $dlResponseData['type'] != '' && strtolower($dlResponseData['type']) == 'validation_error' && isset($dlResponseData['code']) && $dlResponseData['code'] != '' && strtolower($dlResponseData['code']) == 'insufficient_balance') {
                        return $this->errorResponse('Verification id Invalid');
                    }
                }
                if ($dlVerificationStatus == true) {
                    if($dlResName != ''){
                        $dlParts = explode(' ', $dlResName);
                        $dlFirstName = $dlParts[0] ?? '';
                        $dlLastName = end($dlParts);
                        $customer->firstname = $dlFirstName;
                        $customer->lastname = $dlLastName;
                        $customer->dob = $dlDob ?? NULL;
                        $customer->billing_address = $dlAddress ?? NULL;
                        $customer->save();
                    }
                    if($dlProfileLink != ''){
                        $response = Http::get($dlProfileLink);
                        if ($response->successful()) {
                            $fileName = "DL".time() . '.png';
                            $filePath = public_path('images/profile_pictures/' . $fileName);
                            if (!File::exists(public_path('images/profile_pictures/'))) {
                                File::makeDirectory(public_path('images/profile_pictures/'), 0755, true);
                            }
                            file_put_contents($filePath, $response->body());

                            $customer->profile_picture_url = $fileName;
                            $customer->save();
                        }
                    }
                    $customerDocument->is_approved = 'approved';
                    $customerDocument->approved_by = auth()->guard('admin')->user()->admin_id;
                    $customerDocument->is_approved_datetime = date('Y-m-d H:i:s');
                    $customerDocument->cashfree_api_response = $responseJson;
                    $customerDocument->dob = isset($dob) && $dob != '' ? date('Y-m-d', strtotime($dob)) : NULL;
                    $customerDocument->save();

                    $newVal = $customerDocument;
                    logAdminActivities('Customer Document DRIVING LICENCE uploaded via Cashfree', $oldVal, $newVal);
                    return $this->successResponse($customerDocument, 'Document updated Successfully');
                }else{
                    return $this->errorResponse('Something went Wrong');
                }
            }elseif($request->doc_type == 'govtid'){
                $refId = '';
                $aadharStatus = false;
                $client = new Client();
                $idNumber = $request->doc_number;
                if($request->govt_type == 'aadhar'){
                    $govtType = 'aadhar';
                    if (strlen($idNumber) != 12) {
                        return $this->errorResponse('Aadhar Number should contain 12 digits');
                    }
                    try {
                        $aadharResponse = $client->request('POST', $this->cashfreeAadharApiUrl, [
                            'body' => json_encode([
                                'aadhaar_number' => $idNumber,
                            ]),
                            'headers' => [
                                'accept' => 'application/json',
                                'content-type' => 'application/json',
                                'x-client-id' => $this->cashfreeClientId,
                                'x-client-secret' => $this->cashfreeClientSecret,
                            ],
                            'http_errors' => false
                        ]);
                        $statusCode = $aadharResponse->getStatusCode();
                        $content = $aadharResponse->getBody()->getContents();
                        $content = json_decode($content);
                        if (isset($content->status) && strtolower($content->status) == 'success') {
                            $refId = isset($content->ref_id) ? $content->ref_id : '';
                            $aadharStatus = true;
                            //return $this->successResponse(['ref_id' => $refId, 'govtid' => $idNumber, 'documentId' => $customerDocument->document_id, 'document_upload_message' => "<span style='font-style: italic;'>Document is submitted for approval.</span>"], 'OTP Sent Successfully on your registered Mobile Number');
                        } else if ($content != '' && isset($content->status) && $content->status != '' && strtolower($content->status) == 'invalid') {
                            return $this->errorResponse('Invalid Aadhaar Card');
                        } else if (isset($content->code) && (strtolower($content->code) == 'aadhaar_invalid' || strtolower($content->code) == 'aadhaar_empty') && strtolower($content->type) == 'validation_error') {
                            return $this->errorResponse("Please enter valid Aadhaar number");
                        } else if (isset($content->code) && (strtolower($content->code) == 'x-client-id_missing' || strtolower($content->code) == 'x-client-secret_value_invalid' || strtolower($content->code) == 'authentication_failed' || strtolower($content->code) == 'ip_validation_failed' || strtolower($content->code) == 'verification_pending' || strtolower($content->code) == 'insufficient_balance' || strtolower($content->code) == 'verification_failed' || strtolower($content->code) == 'api_error') && (strtolower($content->type) == 'validation_error' || strtolower($content->type) == 'authentication_error' || strtolower($content->type) == 'internal_error')) {
                            return $this->errorResponse("Something went Wrong.. Server side issue contact admin");
                            //$glIssueStatus = true;
                        }
                    } catch (ClientException $e) {
                        //return $this->errorResponse('Govt Id is Invalid');
                        $response = $e->getResponse();
                        $statusCode = $response->getStatusCode();
                        $responseBody = $response->getBody()->getContents();
                        $errorData = json_decode($responseBody, true);
                        // Check if decoding was successful and the expected data exists
                        if (json_last_error() === JSON_ERROR_NONE && isset($errorData['type'], $errorData['code'], $errorData['message'])) {
                            $errorMessage = "{$errorData['message']}";
                        } else {
                            // Fallback error message if response is not as expected
                            $errorMessage = "An unexpected error occurred. Status Code: {$statusCode}";
                        }
                        return $this->errorResponse($errorMessage);
                    }
                }elseif($request->govt_type == 'passport'){
                    $govtType = 'passport';
                    $responseJson = NULL;
                    if (strlen($idNumber) != 15) {
                        return $this->errorResponse('Passport Number should contain 15 characters');
                    }
                    try {
                        $prefix = 'velpassport_';
                        $verificationId = uniqid($prefix, true); //Uninque ID   
                        $dob = $request->dob ? date('Y-m-d', strtotime($request->dob)) : '';
                        $voterIdResponse = $client->request('POST', $this->cashfreePassportApiUrl, [
                            'headers' => [
                                'accept' => 'application/json',
                                'content-type' => 'application/json',
                                'x-client-id' => $this->cashfreeClientId,
                                'x-client-secret' => $this->cashfreeClientSecret,
                            ],
                            'json' => [
                                'verification_id' => $verificationId,
                                'file_number' => $idNumber,
                                'dob' => $dob,
                            ],
                            'http_errors' => false
                        ]);
                        $passportContent = $voterIdResponse->getBody()->getContents();
                        $passportResponseData = json_decode($passportContent, true);
                        if (isset($passportResponseData['status']) && strtolower($passportResponseData['status']) == 'valid') {
                            $responseJson = json_encode($passportResponseData);
                            $glVerificationStatus = true;
                        } else if (isset($passportResponseData['status']) && strtolower($passportResponseData['status']) == 'invalid') {
                            return $this->errorResponse('Passport Number is Invalid');
                        } else if (isset($passportResponseData['code']) && strtolower($passportResponseData['code']) == 'verification_failed' && strtolower($passportResponseData['type']) == 'internal_error') { //For error 500, 502 
                            return $this->errorResponse("An unexpected error occurred while verifying your Govt Id. Please recheck and confirm that you have entered valid id or not");
                        } else if (isset($passportResponseData['code']) && (strtolower($passportResponseData['code']) == 'ip_validation_failed' || strtolower($passportResponseData['code']) == 'authentication_failed') && strtolower($passportResponseData['type']) == 'authentication_error') { //401, 403 IP whitelist error OR Client Id and Client Secret issue
                            //return $this->errorResponse("Something went Wrong.. Server side issue contact admin");
                            //$glIssueStatus = true;
                        } else if (isset($passportResponseData['code']) && (strtolower($passportResponseData['code']) == 'insufficient_balance' || strtolower($passportResponseData['code']) == 'verification_id_already_exists' || strtolower($passportResponseData['code']) == 'x-client-secret_value_invalid' || strtolower($passportResponseData['code']) == 'x-client-secret_value_invalid' || strtolower($passportResponseData['code']) == 'dob_value_invalid' || strtolower($passportResponseData['code']) == 'dob_missing' || strtolower($passportResponseData['code']) == 'file_number_missing' || strtolower($passportResponseData['code']) == 'verification_id_value_invalid' || strtolower($passportResponseData['code']) == 'verification_id_missing') && strtolower($passportResponseData['type']) == 'validation_error') { //422,409,400
                            //return $this->errorResponse("Something went Wrong");
                            // $glIssueStatus = true;
                        }
                    } catch (ClientException $e) {
                        return $this->errorResponse("Something went Wrong..Please try with corrent Government ID number");
                    }
                }elseif($request->govt_type == 'election'){
                    $govtType = 'election';
                    //Voter ID/Election Card verification
                    $responseJson = NULL;
                    if (strlen($idNumber) != 10) {
                        return $this->errorResponse('Voter Id/Election Id Number should contain 10-digit alpha-numeric');
                    }
                    try {
                        $prefix = 'velvoterid_';
                        $verificationId = uniqid($prefix, true); //Uninque ID  
                        $voterIdResponse = $client->request('POST', $this->cashfreeElectionApiUrl, [
                            'headers' => [
                                'accept' => 'application/json',
                                'content-type' => 'application/json',
                                'x-client-id' => $this->cashfreeClientId,
                                'x-client-secret' => $this->cashfreeClientSecret,
                            ],
                            'json' => [
                                'verification_id' => $verificationId,
                                'epic_number' => $idNumber
                            ],
                            'http_errors' => false
                        ]);
                        $voterIdContent = $voterIdResponse->getBody()->getContents();
                        $voterIdResponseData = json_decode($voterIdContent, true);
                        if (isset($voterIdResponseData['status']) && strtolower($voterIdResponseData['status']) == 'valid') { //200 Valid
                            $responseJson = json_encode($voterIdResponseData);
                            $glVerificationStatus = true;
                        } else if (isset($voterIdResponseData['status']) && strtolower($voterIdResponseData['status']) == 'invalid') { //200 Invalid
                            return $this->errorResponse('Voter Id/Election Id Number is Invalid');
                        } else if (isset($voterIdResponseData['code']) && (strtolower($voterIdResponseData['code']) == 'verification_failed' || strtolower($voterIdResponseData['code']) == 'epic_number_value_invalid') && (strtolower($voterIdResponseData['type']) == 'internal_error' || strtolower($voterIdResponseData['type']) == 'validation_error')) { //For error 500, 502 
                            return $this->errorResponse("An unexpected error occurred while verifying your Govt Id. Please recheck and confirm that you have entered valid id or not");
                        } else if (isset($voterIdResponseData['code']) && (strtolower($voterIdResponseData['code']) == 'ip_validation_failed' || strtolower($voterIdResponseData['code']) == 'authentication_failed') && strtolower($voterIdResponseData['type']) == 'authentication_error') { //401, 403 IP whitelist error OR Client Id and Client Secret issue
                            //return $this->errorResponse("Something went Wrong.. Server side issue contact admin");
                            // $glIssueStatus = true;
                        } else if (
                            isset($voterIdResponseData['code']) &&
                            (strtolower($voterIdResponseData['code']) == 'insufficient_balance' ||
                                strtolower($voterIdResponseData['code']) == 'verification_id_already_exists' ||
                                strtolower($voterIdResponseData['code']) == 'x-client-secret_value_invalid' ||
                                strtolower($voterIdResponseData['code']) == 'x-client-secret_value_invalid' ||
                                strtolower($voterIdResponseData['code']) == 'verification_id_value_invalid' ||
                                strtolower($voterIdResponseData['code']) == 'verification_id_missing') && strtolower($voterIdResponseData['type']) == 'validation_error'
                        ) { //422,409,400
                            //return $this->errorResponse("Something went Wrong");
                            //$glIssueStatus = true;
                        }
                    } catch (Exception $e) {
                        Log::error("Unexpected error occurred", ['exception' => $e]);
                        return $this->errorResponse("An unexpected error occurred: " . $e->getMessage());
                    }
                }

                if ($glVerificationStatus = true && $request->doc_type == 'govtid') {
                    if (isset($request->govt_type) && $request->govt_type == 'aadhar') {
                        $customerDocument->is_approved = 'awaiting_approval';
                    } else {
                        $customerDocument->is_approved = 'approved';
                    }
                    $customerDocument->id_number = $request->doc_number;
                    $customerDocument->approved_by = auth()->guard('admin')->user()->admin_id;
                    $customerDocument->is_approved_datetime = date('Y-m-d H:i:s');
                    $customerDocument->cashfree_api_response = $responseJson;
                    $customerDocument->govtid_type = $request->govt_type;
                    $customerDocument->dob = isset($dob) && $dob != '' ? date('Y-m-d', strtotime($dob)) : NULL;
                    $customerDocument->save();

                    if($aadharStatus == true){
                        return $this->successResponse(['ref_id' => $refId, 'govtid' => $idNumber, 'documentId' => $customerDocument->document_id, 'document_upload_message' => "<span style='font-style: italic;'>Document is submitted for approval.</span>"], 'OTP Sent Successfully on your registered Mobile Number');
                    }
                    $newVal = $customerDocument;
                    logAdminActivities('Customer Government ID uploaded via Cashfree', $oldVal, $newVal);
                    return $this->successResponse($customerDocument, "Document verified Successfully");
                }else{
                    return $this->errorResponse("Something went Wrong");
                }
            }
        }
        return $this->successResponse($customerDocument, 'Document details are stored successfully!');
    }
    
    public function getGovTypes(Request $request){
        $govtId = config('global_values.govt_types');
        if(isset($govtId) && is_countable($govtId) && count($govtId) > 0){
            $formattedGovtId = [];
            foreach ($govtId as $key => $value) {
                $formattedGovtId[] = [
                    'label' => $value,
                    'value' => $key
                ];
            }
            return $this->successResponse($formattedGovtId, 'Government Types get successfully');
        }else{
            return $this->errorResponse('Government Types are not found');
        }
    }

    public function getDropdownCustomers(Request $request){
        $customers = Customer::select('customer_id', 'firstname', 'lastname', 'email', 'mobile_number', 'profile_picture_url')->where(['is_deleted' => 0, 'is_blocked' => 0, 'is_test_user' => 0])->get();
        if(isset($customers) && is_countable($customers) && count($customers) > 0){
            $customers->each(function ($customer) {
                $customer->makeHidden(['documents']);
            });
            return $this->successResponse($customers, 'Customers get successfully');
        }else{
            return $this->errorResponse('Cutomers are not found');
        }
    }

}