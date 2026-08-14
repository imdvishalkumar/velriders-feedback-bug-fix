<?php

namespace App\Services;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;

class SmsService {
    
    protected $apiKey;

    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }

    public function sendOTP($mobileNumber,$otp)
    {   
        $client = new Client();

        $url = 'https://www.fast2sms.com/dev/bulkV2';
        $headers = [
            'Authorization' => $this->apiKey,
            'Content-Type' => 'application/json',
        ]; 
        $data = [
            'route' => 'dlt',
            'sender_id' => 'VELRDR',
            'message' => '168052',
            'variables_values' => $otp,
            'flash' => '0',
            'numbers' => $mobileNumber,
        ];
                
        try {
            $response = $client->request('POST', $url, [
                'headers' => $headers,
                'json' => $data
            ]);
            // Handle successful response here if needed
            return $response->getBody()->getContents();
        } catch (ClientException $e) {
            $response = $e->getResponse();
            $statusCode = $response->getStatusCode();
            $responseBodyAsString = $response->getBody()->getContents();
            $responseBody = json_decode($responseBodyAsString, true);

            if(isset($responseBody) && isset($responseBody['message'])){
                $details['status'] = $statusCode;
                $details['message'] = $responseBody['message'];
                return $details;
            }

            // Log the error
            Log::error("Fast2SMS API Error - Status Code: $statusCode, Response: $responseBodyAsString");
    
            // Return a response indicating the error
           // return "Fast2SMS API Error - Status Code: $statusCode, Response: $responseBodyAsString";
        } catch (\Exception $e) {
            // Handle other exceptions
            Log::error("An unexpected error occurred: " . $e->getMessage());
            //return "An unexpected error occurred. Please try again later.";
        }

    }

}
