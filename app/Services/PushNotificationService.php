<?php

namespace App\Services;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;

class PushNotificationService {
    

    public function store($sendNotificationCustomer)
    {   
        $url = 'https://fcm.googleapis.com/fcm/send';
        $jsonResponse = [
            "to" => $sendNotificationCustomer->device_token,
            "notification" => [
                "body" => "Rental booking created successfully",
                "content_available" => true,
                "priority" => "high",
                "title" => "Order Booking"
            ],
            "data" => [
                "title" => "Good :)",
                "priority" => "high",
                "content_available" => true,
                "body" => "New Announcement assignedsdnf :)"
            ]
        ];

        $client = new Client();
        $response = $client->post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer AAAAlEvQ2Jo:APA91bFVeZnEjp9sBXIywk0ag11S5AYXzqX-VtNSO0cnKgJUBn_ZGIpUhha-BnPl3FinuoZ-_m-p58JJ2jXh8mtQbt9zmkElP6W3uchrdYFdZqaNSkun2ILlJ5HBOiqL30eJ7trIQMR0',
            ],
            'json' => $jsonResponse,
        ]);

        // Handle response
        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();

        // Return response
        return response()->json([
            'status_code' => $statusCode,
            'response' => json_decode($body, true),
        ]);

    }

}
