@extends('templates.auth')

@push('styles')
<style>
    #error_message {
        color: red;
    }
    #overlay {
        position: fixed;
        top: 0;
        z-index: 100;
        width: 100%;
        height: 100%;
        display: none;
        background: rgba(0, 0, 0, 0.6);
    }
    .cv-spinner {
        height: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .spinner {
        width: 40px;
        height: 40px;
        border: 4px #ddd solid;
        border-top: 4px #2e93e6 solid;
        border-radius: 50%;
        animation: sp-anime 0.8s infinite linear;
    }
    @keyframes sp-anime {
        100% {
            transform: rotate(360deg);
        }
    }
    .is-hide {
        display: none;
    }
</style>
@endpush

@section('content')
    <div class="container-fluid d-flex justify-content-center align-items-center" style="height: calc(100vh - 120px)">
        <div class="card d-flex flex-column justify-content-center align-items-center col-md-8 col-sm-6 col-lg-5">
            <div class="card__header d-flex bg-white justify-content-center align-items-center box__shadow p-3 w-100">
                <h5>Login with OTP</h5>
            </div>
            <div id="overlay">
                <div class="cv-spinner">
                    <span class="spinner"></span>
                </div>
            </div>
            <div id="error-container">
                @if (session('success'))
                    <div id="success-message" class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @elseif(session('error'))
                    <div id="error-message" class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif
            </div>
            <form id="verify-otp-form" class="p-5 d-flex justify-content-center align-items-center flex-column gap-3" method="POST" action="{{route('front.verify-login-otp')}}">
                @csrf
                <div class="d-flex justify-content-center align-items-center flex-column">
                    <h3>OTP VERIFICATION</h3>
                    <p class="text-secondary text-center">We will send you one time <br> password in {{$phone}}</p>
                </div>
                <input type="hidden" id="mobile_no" name="mobile_no" value="{{$phone}}">
                <div id="otp" class="inputs d-flex col-5 flex-row justify-content-center">
                    <input class="m-2 text-center form-control rounded opt-box validateotp" name="otp_input1" id="otp_input_1"
                        type="text" maxlength="1" autocomplete="off">
                    <input class="m-2 text-center form-control rounded opt-box validateotp" name="otp_input2" id="otp_input_2"
                        type="text" maxlength="1" autocomplete="off">
                    <input class="m-2 text-center form-control rounded opt-box validateotp" name="otp_input3" id="otp_input_3"
                        type="text" maxlength="1" autocomplete="off">
                    <input class="m-2 text-center form-control rounded opt-box validateotp" name="otp_input4" id="otp_input_4"
                        type="text" maxlength="1" autocomplete="off">
                        <div id="otp-error"></div>
                </div>
                
                <div class="d-flex justify-content-center align-items-center flex-column">
                    <p class="text-secondary">Didn't get OTP yet?</p>
                    <a href="#" style="text-decoration: none;">
                        <h6 style="font-size: .9rem;">Send Again</h6>
                    </a>
                </div>
                <button class="btn btn-primary col-10" id="verify_otp_button" type="submit">Verify</button>
            </form>
        </div>
    </div>
@endsection

@section('script')
<script>
    $('#verify-otp-form').validate({ 
           rules: {
              otp_input1: {required: true},
              otp_input2: {required: true},
              otp_input3: {required: true},
              otp_input4: {required: true},
           },
           messages :{
                otp_input1 : { required : 'Enter' },
                otp_input2 : { required : 'Enter' },
                otp_input3 : { required : 'Enter' },
                otp_input4 : { required : 'Enter' },
            },
            highlight: function (element) {
                if ($(element).is('select') || $(element).is('input')) {
                    $(element).parent('.select-wrap').addClass('error');
                } else {
                    $(element).addClass('error');
                }
            },
            errorPlacement: function (error, element) {
                if (element.attr("name") == "otp_input1") {
                    error.appendTo("#otp-error");
                }if (element.attr("name") == "otp_input2") {
                    error.appendTo("#otp-error");
                }if (element.attr("name") == "otp_input3") {
                    error.appendTo("#otp-error");
                }if (element.attr("name") == "otp_input4") {
                    error.appendTo("#otp-error");
                } else {
                    error.insertAfter(element);
                }
            } 
        });

    $(document).ready(function() {
        
    });

    function handleErrorMessage(message) {
        $('#error_message').text(message);
    }
   /* jQuery(function($) {
        $(document).ajaxSend(function() {
            $("#overlay").fadeIn(300);
        });

        $('#button').click(function() {
            $.ajax({
                type: 'GET',
                success: function(data) {
                    console.log(data);
                }
            }).done(function() {
                setTimeout(function() {
                    $("#overlay").fadeOut(300);
                }, 500);
            });
        });
    });*/
</script>
@endsection