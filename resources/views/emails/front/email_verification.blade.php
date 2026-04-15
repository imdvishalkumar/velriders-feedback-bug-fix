@extends('emails.email')

@section('content')     
<p style="font-size: 14px; font-weight: 600; color: #808080; margin: 0 0 14px;">Hello {{$name}},</p>
<p style="font-size: 12px; line-height: 20px; color: #808080; margin: 0 0 30px;">Please click on below button to verify your Email - </p>
<a href="{{route('front.verify-customer-email', ['customer_id' => $customer_id, 'email' => $email, 'app' => $app])}}" style="background-color: #38a4a6;border: none;color: white;padding: 15px 32px;text-align: center;text-decoration: none;display: inline-block;font-size: 16px;margin: 4px 2px;cursor: pointer;">Click this button to Verify your Email</a><br/>
@endsection