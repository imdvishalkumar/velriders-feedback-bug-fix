@extends('emails.email')

@section('content')   

<p style="font-size: 14px; font-weight: 600; color: #808080; margin: 0 0 14px;">Hello Admin, New user has subscribed with below Email -</p>
<p style="font-size: 12px; line-height: 20px; color: #808080; margin: 0 0 30px;">@isset($email){{ $email }}@endisset</p>  
  
@endsection