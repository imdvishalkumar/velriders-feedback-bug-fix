<?php

namespace App\Http\Controllers\FrontAppApis\V1;

use App\Http\Controllers\Controller; 
use App\Models\{CustomerDocument, Customer, RentalBooking, Setting};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;

class CustomerDocumentController extends Controller
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

    public function uploadDocument(Request $request)
    {
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
            'document_type' => ['required', 'string', Rule::in(['govtid', 'dl'])], // Ensure document_type is either "govtid" or "dl"
            'id_number' => [
                'required',
                'string',
                Rule::unique('customer_documents')->where(function ($query) {
                    return $query->where(function ($query) {
                        $query->where('is_approved', 'approved')
                            ->orWhere('is_blocked', 1);
                    });
                }),
            ],
            'front_image' => 'required|mimetypes:image/heic,image/heif,image/jpeg,image/png,image/jpg,image/bmp,image/gif,image/svg,image/webp|max:10000',
        ], [
            'dob.required' => "The date of birth is required when using a driver\'s license or a passport.",
        ]);
        $validator->sometimes('govtid_type', 'required|in:' . $govtTypes, function ($input) {
            return $input->document_type === 'govtid';
        });
        $validator->sometimes('back_image', 'required|mimetypes:image/heic,image/heif,image/jpeg,image/png,image/jpg,image/bmp,image/gif,image/svg,image/webp|max:10000', function ($input) {
            if ($input->document_type === 'dl' || ($input->document_type === 'govtid' && $input->govtid_type != 'passport'))
                return true;
            else
                return false;
        });
        $validator->sometimes('dob', 'required', function ($input) {
            if ($input->document_type === 'dl' || ($input->document_type === 'govtid' && $input->govtid_type == 'passport'))
                return true;
            else
                return false;
        });
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        // Retrieve the authenticated user
        $user = Auth::guard('api')->user();
        $customerId = $user->customer_id;

        $checkBooking = RentalBooking::where('customer_id', $customerId)->whereNotIn('status', ['pending', 'no show', 'canceled', 'failed'])->exists();
        if(!$checkBooking) {
            return $this->errorResponse('You cannot upload documents without an active booking.');
        }

        // Check if there's already a document of the same type awaiting approval or approved
        $documentTypes = ['dl', 'govtid'];
        if (in_array($request->document_type, $documentTypes)) {
            $query = CustomerDocument::where('customer_id', $customerId)
                ->where('document_type', $request->document_type)
                ->whereIn('is_approved', ['awaiting_approval', 'approved']);
            $existingDocument = $query->first();
            if ($existingDocument) {
                //NEW
                if ($existingDocument->is_approved === 'approved') {
                    return $this->errorResponse('A document of this type is already approved.');
                }
                // OLD
                // if ($existingDocument->is_approved === 'awaiting_approval') {
                //     return $this->errorResponse('There is already a document of this type awaiting approval.');
                // } elseif ($existingDocument->is_approved === 'approved') {
                //     return $this->errorResponse('A document of this type is already approved.');
                // }
            }
        }

        $responseJson = NULL;
        $glVerificationStatus = $dlVerificationStatus = false;
        $docVerificationStatus = config('global_values.doc_verification_status');
        $aadharResName = $dlResName = $dlProfileLink = '';
        $dlDob = $dlAddress = NULL;
        $checkDocVerificationCnt = Customer::where('customer_id', $customerId)->where('is_deleted', 0)->latest('created_at')->first();
        $setting = Setting::select('id', 'cust_doc_verif_limits')->first();
        //DL VERIFICATION
        if (isset($request->document_type) && $request->document_type == 'dl' && $docVerificationStatus != '' && strtolower($docVerificationStatus) == 'yes') {
            if ($checkDocVerificationCnt != '' && $setting != '' && $checkDocVerificationCnt->dl_doc_verification_cnt >= $setting->cust_doc_verif_limits) {
                $message = "You can not verify your Driving Licence more than " . $setting->cust_doc_verif_limits . " times";
                return $this->errorResponse($message);
            }
            $responseJson = NULL;
            $client = new Client();
            $dlNumber = str_replace(' ', '',$request->id_number);
            $dob = $request->dob;
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
                $dlVerificationStatus = true;
                // $checkGovtId = CustomerDocument::where('customer_id', $customerId)
                //     ->where('document_type', 'govtid')
                //     ->where('is_approved', 'approved')->first();
                // if ($checkGovtId == '') {
                //     return $this->errorResponse('Verify your Government ID First');
                // } else {
                //     $cashfreeRes = $checkGovtId->cashfree_api_response ? json_decode($checkGovtId->cashfree_api_response) : '';
                //     if ($cashfreeRes != '') {
                //         $aadharResName = $cashfreeRes->name ?? '';
                //     }
                // }
                // if ($aadharResName != '' && $dlResName != '') {
                //     $result = checkNameMatch($aadharResName, $dlResName);
                //     if ($result == 1) {
                //         $dlVerificationStatus = true;
                //     }else{
                //         return $this->errorResponse("You cannot upload anyone else's dl");
                //     }
                // }
            } elseif ($dlResponseData != '' && isset($dlResponseData['status']) && $dlResponseData['status'] != '' && strtolower($dlResponseData['status']) == 'invalid') {
                return $this->errorResponse('Driving License is Invalid');
            } else if ($dlResponseData != '' && isset($dlResponseData['type']) && $dlResponseData['type'] != '' && strtolower($dlResponseData['type']) == 'validation_error' && isset($dlResponseData['code']) && $dlResponseData['code'] != '' && (strtolower($dlResponseData['code']) == 'driving_license_value_invalid' || strtolower($dlResponseData['code']) == 'dl_number_value_invalid')) {
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
            } else if($dlResponseData != '' && isset($dlResponseData['type']) && $dlResponseData['type'] != '' && strtolower($dlResponseData['type']) == 'validation_error' && isset($dlResponseData['code']) && $dlResponseData['code'] != '' && strtolower($dlResponseData['code']) == 'invalid_parameters'){
                if($dlResponseData['message']){
                    return $this->errorResponse($dlResponseData['message']);
                }else{
                    return $this->errorResponse('Invalid Details');
                }
            }else if (
                $dlResponseData != '' && isset($dlResponseData['type']) && $dlResponseData['type'] != '' && strtolower($dlResponseData['type']) == 'validation_error' && isset($dlResponseData['code']) && $dlResponseData['code'] != '' &&
                (strtolower($dlResponseData['code']) == 'x-client-id_missing') ||
                (strtolower($dlResponseData['code']) == 'x-client-secret_value_invalid') ||
                (strtolower($dlResponseData['code']) == 'authentication_failed') ||
                (strtolower($dlResponseData['code']) == 'ip_validation_failed')
            ) {
                return $this->errorResponse('Server Error');
            } else if ($dlResponseData != '' && isset($dlResponseData['type']) && $dlResponseData['type'] != '' && strtolower($dlResponseData['type']) == 'validation_error' && isset($dlResponseData['code']) && $dlResponseData['code'] != '' && strtolower($dlResponseData['code']) == 'insufficient_balance') {
                return $this->errorResponse('Verification id Invalid');
            } else {
                return $this->errorResponse('Something went wrong');
            }
        }

        // GOVT VERIFICATION
        if (isset($request->document_type) && $request->document_type == 'govtid' && $docVerificationStatus != '' && strtolower($docVerificationStatus) == 'yes') {
            $checkDl = CustomerDocument::where('customer_id', $customerId)->where('document_type', 'dl')->where('is_approved', 'approved')->first();
            if ($checkDl == '') {
                return $this->errorResponse('Verify your Driving License First');
            }else{
                $dlResName = $checkDl->cashfree_api_response ? json_decode($checkDl->cashfree_api_response)->details_of_driving_licence->name ?? '' : '';
            }
            if ($checkDocVerificationCnt != '' && $setting != '' && $checkDocVerificationCnt->govt_doc_verification_cnt >= $setting->cust_doc_verif_limits) {
                $message = "You can not verify your Government ID more than " . $setting->cust_doc_verif_limits . " times";
                return $this->errorResponse($message);
            }
            $idNumber = isset($request->id_number) ? $request->id_number : '';
            if ($idNumber != '') {
                $client = new Client();
                if (isset($request->govtid_type) && $request->govtid_type == 'aadhar') { //Aadhar Verification - OTP Generation (First Step)    
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
                            return $this->successResponse(['ref_id' => $refId, 'govtid' => $idNumber, 'doc_verification_status' => $docVerificationStatus, 'document_upload_message' => "<span style='font-style: italic;'>Document is submitted for approval.</span>"], 'OTP Sent Successfully on your registered Mobile Number');
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
                } elseif (isset($request->govtid_type) && $request->govtid_type == 'passport') { //Passport Verification
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
                            $aadharResName = $passportResponseData['name'] ?? '';
                            $responseJson = json_encode($passportResponseData);
                            //$glVerificationStatus = true;
                            if($dlResName != '' && $aadharResName != ''){
                                $result = checkNameMatch($aadharResName, $dlResName);
                                if ($result == 1) {
                                    $glVerificationStatus = true;
                                }else{
                                    return $this->errorResponse("You cannot upload anyone else's id");
                                }
                            }
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
                } elseif (isset($request->govtid_type) && $request->govtid_type == 'election') {
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
                            //$glVerificationStatus = true;
                            $aadharResName = $voterIdResponseData['name'] ?? '';
                            if($dlResName != '' && $aadharResName != ''){
                                $result = checkNameMatch($aadharResName, $dlResName);
                                if ($result == 1) {
                                    $glVerificationStatus = true;
                                }else{
                                    return $this->errorResponse("You cannot upload anyone else's id");
                                }
                            }
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
            } else {
                return $this->errorResponse('ID Number Missing');
            }
        }

        $frontImageUrl = null;
        if ($request->hasFile('front_image')) {
            $documentImage = $request->file('front_image');
            $frontImageUrl = time() . '_front.' . $documentImage->getClientOriginalExtension();
            $documentImage->move(public_path('images/customer_documents'), $frontImageUrl);
        }
        $backImageUrl = null;
        if ($request->hasFile('back_image')) {
            $documentBackImage = $request->file('back_image');
            $backImageUrl = time() . '_back.' . $documentBackImage->getClientOriginalExtension();
            $documentBackImage->move(public_path('images/customer_documents'), $backImageUrl);
        }

        // Use Carbon to parse the expiry_date and convert it to MySQL date format
        $expiryDate = $request->expiry_date != NULL ? date('Y-m-d', strtotime($request->expiry_date)) : null;

        // Create a new CustomerDocument instance
        $document = new CustomerDocument();
        $document->customer_id = $customerId;
        $document->document_type = $request->document_type;
        $document->id_number = $request->id_number;
        $document->expiry_date = isset($expiryDate) ? $expiryDate : null;
        $document->document_image_url = $frontImageUrl;
        $document->document_back_image_url = $backImageUrl;
        $document->vehicle_type = $request->vehicle_type;
        $document->cashfree_api_response = $responseJson;
        $document->govtid_type = $request->govtid_type != '' ? $request->govtid_type : $govtType;
        $document->save();

        if ($docVerificationStatus != '' && strtolower($docVerificationStatus) == 'yes') {
            $customer = Customer::where('customer_id', $customerId)->first();
            if ($dlVerificationStatus == true && $request->document_type == 'dl') {
                if($dlResName != ''){
                    $dlParts = explode(' ', $dlResName);
                    $dlFirstName = $dlParts[0] ?? '';
                    $dlLastName = end($dlParts);
                    $customer->firstname = $dlFirstName;
                    $customer->lastname = $dlLastName;
                    $customer->dob = $dlDob ?? NULL;
                    $customer->billing_address = $dlAddress ?? NULL;
                    $customer->dl_doc_verification_cnt += 1;
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
                $document->approved_by = null;
                $document->is_approved_datetime = date('Y-m-d H:i:s');
                $document->save();
            }
            if ($glVerificationStatus = true && $request->document_type == 'govtid') {
                if (isset($request->govtid_type) && $request->govtid_type == 'aadhar') {
                    $document->is_approved = 'awaiting_approval';
                } else {
                    $document->is_approved = 'approved';
                    $customer->govt_doc_verification_cnt += 1;
                    $customer->save();
                }
                $document->approved_by = null;
                $document->is_approved_datetime = date('Y-m-d H:i:s');
                $document->save();
            }
        }

        return $this->successResponse(['document_upload_message' => "<span style='font-style: italic;'>Document is submitted for approval.</span>", 'doc_verification_status' => $docVerificationStatus], 'Document uploaded successfully.');
    }

    protected function checkNameMatch($aadharName, $dlName)
    { 
        $aadharName = strtolower($aadharName);
        $dlName = strtolower($dlName);
        // OLD CODE
        // $dlParts = explode(' ', $dlName);
        // $dlFirstName = $dlParts[0] ?? '';
        // $dlLastName = end($dlParts);
        // // Check if both first and last names are present in Aadhar card name
        // if (str_contains($aadharName, $dlFirstName) && str_contains($aadharName, $dlLastName)) {
        //     return 1; // Both names are found
        // } else {
        //     return 0; // Names not found
        // }

        // NEW CODE
        // Clean and process both names
        /*$parts1 = cleanNameParts($aadharName);
        $parts2 = cleanNameParts($dlName);
        if (count($parts1) < 2 || count($parts2) < 2) {
            return 0; // Ensure both names have at least first and last name
        }*/
        // Extract first and last names
        /*$firstName1 = $parts1[0];
        $lastName1 = end($parts1);
        $firstName2 = $parts2[0];
        $lastName2 = end($parts2);
        // Check if names match directly OR if first & last name are swapped, allowing small typos
        if((isSimilar($firstName1, $firstName2) && isSimilar($lastName1, $lastName2)) || (isSimilar($firstName1, $lastName2) && isSimilar($lastName1, $firstName2))){
            return 1;
        }
        return 0;*/

        // NEW CODE 1
        $aadharNameCheck = strtolower(trim($aadharName));
        $aadharNameCheck = preg_split('/\s+/', $aadharNameCheck);
        $dlNameCheck = strtolower(trim($dlName));
        $dlNameCheck = preg_split('/\s+/', $dlNameCheck);
        if (count($aadharNameCheck) < 2 || count($dlNameCheck) < 2) {
            return 0; // Ensure both names have at least first and last name
        }

        // Clean and process both names
        $parts1 = cleanNameParts($aadharName);
        $parts2 = cleanNameParts($dlName);
        if (count($parts1) < 2 || count($parts2) < 2) {
            return 0; // Ensure both names have at least first and last name
        }
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
        if($middleNameStatus == true){
            if (
                (isSimilar($firstName1, $firstName2) && isSimilar($lastName1, $lastName2)) || 
                (isSimilar($firstName1, $lastName2) && isSimilar($lastName1, $firstName2)) ||
                (isSimilar($middleNames1, $firstName2) || isSimilar($middleNames1, $lastName2)) || 
                (isSimilar($middleNames2, $firstName1) || isSimilar($middleNames2, $lastName1))
            ){
                return 1;
            }
            return 0;
        }else{
            if (
                (isSimilar($firstName1, $firstName2) && isSimilar($lastName1, $lastName2)) || (isSimilar($firstName1, $lastName2) && isSimilar($lastName1, $firstName2))
                //(isSimilar($firstName1, $firstName2) && isSimilar($lastName1, $firstName2)) || (isSimilar($firstName1, $lastName2) && isSimilar($lastName1, $firstName2))
            ){
                return 1;
            }
            return 0;
        }
    }

    public function verifyGovtIdDocument(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_number' => [
                'required',
                'string',
                Rule::unique('customer_documents')->where(function ($query) {
                    return $query->where(function ($query) {
                        $query->where('is_approved', 'approved')
                            ->orWhere('is_blocked', 1);
                    });
                }),
            ],
            'ref_id' => 'required',
            'otp' => 'required',
            'front_image' => 'required|mimetypes:image/heic,image/heif,image/jpeg,image/png,image/jpg,image/bmp,image/gif,image/svg,image/webp|max:10000',
            'back_image' => 'required|mimetypes:image/heic,image/heif,image/jpeg,image/png,image/jpg,image/bmp,image/gif,image/svg,image/webp|max:10000',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        // Retrieve the authenticated user
        $user = Auth::guard('api')->user();

        // Check if there's already a document of the same type awaiting approval or approved
        $existingDocument = CustomerDocument::where('customer_id', $user->customer_id)
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
                        $frontImageUrl = null;
                        $checkDl = CustomerDocument::where('customer_id', $user->customer_id)->where('document_type', 'dl')->where('is_approved', 'approved')->first();
                        $dlResName = $checkDl->cashfree_api_response ? json_decode($checkDl->cashfree_api_response)->details_of_driving_licence->name ?? '' : '';
                        $aadharResName = $content->name ?? '';
                        if($dlResName != '' && $aadharResName != ''){
                            $result = checkNameMatch($aadharResName, $dlResName);
                            if ($result == 1) {
                                if ($request->hasFile('front_image')) {
                                    $documentImage = $request->file('front_image');
                                    $frontImageUrl = time() . '_front.' . $documentImage->getClientOriginalExtension();
                                    $documentImage->move(public_path('images/customer_documents'), $frontImageUrl);
                                }
                                $backImageUrl = null;
                                if ($request->hasFile('back_image')) {
                                    $documentBackImage = $request->file('back_image');
                                    $backImageUrl = time() . '_back.' . $documentBackImage->getClientOriginalExtension();
                                    $documentBackImage->move(public_path('images/customer_documents'), $backImageUrl);
                                }
                                // Use Carbon to parse the expiry_date and convert it to MySQL date format
                                if ($request->expiry_date != null) {
                                    //$expiryDate = Carbon::parse($request->expiry_date)->format('Y-m-d');
                                    $expiryDate = date('Y-m-d', strtotime($request->expiry_date));
                                }
                                // $expiryDate = null;
                                // Create a new CustomerDocument instance
                                $document = new CustomerDocument();
                                $document->customer_id = $user->customer_id;
                                $document->document_type = 'govtid';
                                $document->id_number = $request->id_number;
                                $document->expiry_date = isset($expiryDate) ? $expiryDate : null;
                                $document->document_image_url = $frontImageUrl;
                                $document->document_back_image_url = $backImageUrl;
                                $document->vehicle_type = $request->vehicle_type;
                                $document->cashfree_api_response = $aadharResponseJson;
                                $document->is_approved = 'approved';
                                $document->approved_by = null;
                                $document->is_approved_datetime = date('Y-m-d H:i:s');
                                $document->govtid_type = 'aadhar';
                                $document->save();

                                $customer = Customer::where('customer_id', $user->customer_id)->first();
                                $customer->govt_doc_verification_cnt += 1;
                                $customer->save();
                                
                                $document->doc_status = $customer->documents;

                                return $this->successResponse($document, 'Document uploaded successfully.');

                            }else{
                                return $this->errorResponse("You cannot upload anyone else's id");
                            }
                        }else{
                            return $this->errorResponse("Something went Wrong");
                        }
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

    public function checkApprovalStatus($documentType)
    {
        if (!in_array($documentType, ['govtid', 'dl'])) {
            return $this->errorResponse('Invalid Document type.');
        }

        $user = Auth::guard('api')->user();
        $documents = CustomerDocument::where('customer_id', $user->customer_id)->where('document_type', $documentType)->latest()->get();
        $document = $documents->where('document_type', $documentType)->first();
        $allowUpload = true;

        // Check if any document is in 'awaiting approval' status
        if (
            $documents->contains(function ($doc) {
                return $doc->is_approved == 'awaiting approval';
            })
        ) {
            $allowUpload = false;
        }

        if (!$document) {
            $document = new CustomerDocument();
        }

        return $this->successResponse([
            'allow_upload' => $allowUpload,
            'status' => $document ? $document->getDocumentStatus() : null,
            'documents' => $documents
        ]);

    }
}
