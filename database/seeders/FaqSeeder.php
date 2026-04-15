<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Faq;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'question' => 'How to book your ride?',
                'answer' => 'Select your city, preferred travel date & time, choose a vehicle, select kilometer type (limited/unlimited), apply a coupon code (if applicable), choose a payment method (GPay, Debit Card, Credit Card, EMI), and make the payment to confirm your booking.',
            ],
            [
                'question' => 'How to upload documents?',
                'answer' => 'Upload the front & back pictures of your valid driving license and a government-issued ID (Aadhar Card, Voter ID, or Passport). Enter the correct details and wait for approval. Once approved, confirm your email to complete the process.',
            ],
            [
                'question' => 'Is long-term booking possible?',
                'answer' => 'Yes, you can book a vehicle for a minimum of 4 hours and extend it as per your requirement.',
            ],
            [
                'question' => 'How can I start my journey?',
                'answer' => 'Reach the vehicle location, inspect it inside and out, enter the Start OTP, upload at least 5 pictures covering the vehicle’s interior, exterior, and odometer, enter the kilometer reading, and you are ready to drive.',
            ],
            [
                'question' => 'How to extend the booking?',
                'answer' => 'You can extend your booking 10 minutes before it ends via the app under the "My Booking" → "Running" page by selecting the extension time & date and paying the extra amount.',
            ],
            [
                'question' => 'What happens if I cancel my booking?',
                'answer' => 'You can cancel your booking if needed. For further details, refer to our cancellation policy.',
            ],
            [
                'question' => 'When will my journey end?',
                'answer' => 'Return the vehicle 10 minutes before the booking period ends. Check for belongings, inspect for damages, ensure it is clean, upload final pictures, enter the End OTP, pay any dues, and complete the booking.',
            ],
            [
                'question' => 'Is there a speed limit?',
                'answer' => 'Yes, the speed limit is governed by local traffic laws and our terms and conditions.',
            ],
            [
                'question' => 'Can I extend, cancel, or modify the booking?',
                'answer' => 'Yes, you can manage your booking through the app or contact customer support for assistance.',
            ],
            [
                'question' => 'What are the booking criteria and required documents?',
                'answer' => 'You need a valid driver\'s license and a government-issued ID to book a vehicle.',
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::create($faq);
        }
    }
}
