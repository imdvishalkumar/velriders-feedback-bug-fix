@extends('emails.email')

@section('content')

<p style="font-size: 14px; font-weight: 600; color: #808080; margin: 0 0 14px;">Hello Admin,</p>
    <table class="info-card" width="100%" cellpadding="10" cellspacing="0" border="0" style="padding: 25px; background-color: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); margin-bottom: 30px;">
        <tr style="border-bottom: 1px solid #f0f0f0">
            <td width="18" style="display: block; height: 18px; margin-right: 4px; line-height: 18px; text-align: start;">
                <img src="{{asset('images/email_images/icon/User.png')}}" alt="User Icon" width="18" height="18" style="margin-right: 4px; object-fit: contain; display: block;" />
            </td>
            <td style="height: 18px; line-height: 18px; text-align: start; font-size: 12px; font-weight: 600; color: #38a4a6; min-width: 95px; margin-right: 15px;">Name</td>
            <td style="font-size: 10px; color: #000000; font-weight: 500">@isset($first_name){{ $first_name }}@endisset @isset($last_name){{ $last_name }}@endisset</td>
        </tr>
        <tr style="height: 10px"><td colspan="3"></td></tr>
        <tr style="border-bottom: 1px solid #f0f0f0">
            <td width="18" style="display: block; height: 18px; margin-right: 4px; line-height: 18px; text-align: start;">
                <img src="{{asset('images/email_images/icon/Mail.png')}}" alt="Mail Icon" width="18" height="18" style="margin-right: 4px; object-fit: contain; display: block;" />
            </td>
            <td style="height: 18px; line-height: 18px; text-align: start; font-size: 12px; font-weight: 600; color: #38a4a6; min-width: 95px; margin-right: 15px;">Email</td>
            <td style="font-size: 10px; color: #000000; font-weight: 500">@isset($email){{ $email }}@endisset</td>
        </tr>
        <tr style="height: 10px"><td colspan="3"></td></tr>
        <tr style="border-bottom: 1px solid #f0f0f0">
            <td width="18" style="display: block; height: 18px; margin-right: 4px; line-height: 18px; text-align: start;">
                <img src="{{asset('images/email_images/icon/Mobile.png')}}" alt="Mobile Icon" width="18" height="18" style="object-fit: contain; display: block" />
            </td>
            <td style="height: 18px; line-height: 18px; text-align: start; font-size: 12px; font-weight: 600; color: #38a4a6; min-width: 95px; margin-right: 15px;">Mobile</td>
            <td style="font-size: 10px; color: #000000; font-weight: 500">@isset($mobile_no){{ $mobile_no }}@endisset</td>
        </tr>
        <tr style="height: 10px"><td colspan="3"></td></tr>
        <tr>
            <td width="18" style="height: 18px; margin-right: 4px; line-height: 18px;">
                <img src="{{asset('images/email_images/icon/Meassage.png')}}" alt="Message Icon" width="18" height="18" style="margin-right: 4px; display: block; object-fit: contain;" />
            </td>
            <td style="height: 18px; line-height: 18px; text-align: start; font-size: 12px; font-weight: 600; color: #38a4a6; min-width: 95px; margin-right: 15px;">Message Text</td>
            <td style="font-size: 10px; color: #000000; font-weight: 500">@isset($message_text){{ $message_text }}@endisset</td>
        </tr>
    </table>
@endsection
