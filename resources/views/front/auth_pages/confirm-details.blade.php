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
        border-top: 4px #38A4A6 solid;
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
                <h5>Confirm Information</h5>
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
            <form class="d-flex flex-column col-12 gap-4 p-4" id="login-form" method="POST" action="{{route('front.store-confirm-details')}}">
                @csrf
                <div class="form-group">
                    <input type="text" id="first_name" name="first_name" class="form-control"
                        placeholder="First Name" @if(isset(session()->get('loginUser')->firstname))value="{{session()->get('loginUser')->firstname}}"@else value=""@endif>
                </div>

                <div class="form-group">
                    <input type="text" class="form-control" placeholder="Last name" name="last_name"
                        id="last_name" @if(isset(session()->get('loginUser')->lastname))value="{{session()->get('loginUser')->lastname}}"@else value=""@endif>
                </div>
                <div class="form-group">
                    <input type="text" id="mobile_code" class="form-control" placeholder="Enter the phone number"
                        name="mobile_code" @if(isset(session()->get('loginUser')->mobile_number))value="{{session()->get('loginUser')->mobile_number}}"@else value=""@endif readonly>
                </div>
                <div class="form-group">
                    <input type="text" class="form-control" placeholder="Email Address" name="email"
                        id="email" @if(isset(session()->get('loginUser')->email))value="{{session()->get('loginUser')->email}}"@else value=""@endif>
                </div>
                <div class="form-group">
                    <input type="text" id="dob" name="dob" class="form-control"
                        placeholder="Date of Birth" autocomplete="off" @if(isset(session()->get('loginUser')->dob))value="{{session()->get('loginUser')->dob}}"@else value=""@endif>
                </div>
                <button type="submit" id="confirm_number_button"
                    class="btn btn-primary py-3 d-flex justify-content-center gap-3 align-items-center">
                    NEXT
                </button>
            </form>
        </div>
    </div>
@endsection
@section('script')
    <script>
        $('#login-form').validate({ 
           rules: {
              first_name: {required: true},
              last_name: {required: true},
              mobile_code: {required: true},
              email: {required: true, email: true},
              dob: {required: true},
           },
           messages :{
                first_name : { required : 'Please enter First Name' },
                last_name : { required : 'Please enter Last Name' },
                mobile_code : { required : 'Please enter Mobile Number' },
                email : { required : 'Please enter Email' },
                dob : { required : 'Please select Date of Birth' },
            },
            highlight: function (element) {
                //console.log(element, element.type, element.tagName)
                if ($(element).is('select') || $(element).is('input')) {
                    $(element).parent('.select-wrap').addClass('error');
                } else {
                    $(element).addClass('error');
                }
            },
        });

        const input = document.querySelector("#mobile_code");
        window.intlTelInput(input, {
            utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@21.2.5/build/js/utils.js",
            initialCountry: "in",
            showSelectedDialCode: true,
        });

         jQuery(function($) {
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
        });

        $( "#dob" ).datepicker({  
            maxDate: 0,
           dateFormat: 'dd-mm-yy'
        });


    </script>

    <script>
        $(document).ready(function() {
            /*$('#confirm_number_button').click(function() {
                var phoneNumber = '1212121212';
                var countryCode = '+91';
                var otp = '0000';
                var firebase_token = 'jni.nhu.inhik';
                if (otp !== '') {
                    $('#loader').show();
                    $.ajax({
                        url: 'https://velriders.com/api/get-profile',
                        type: 'GET',
                        contentType: 'application/x-www-form-urlencoded',
                        data: {
                            otp: otp,
                            country_code: countryCode,
                            mobile_number: phoneNumber,
                            firebase_token: firebase_token
                        },
                        headers: {
                            'Authorization': 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczovL3ZlbHJpZGVycy5jb20vYXBpL3ZlcmlmeS1vdHAiLCJpYXQiOjE3MTQzOTAzMzAsImV4cCI6MTcxNjk4MjMzMCwibmJmIjoxNzE0MzkwMzMwLCJqdGkiOiIyZmFranlyQlpqMmNDRjFhIiwic3ViIjoiNiIsInBydiI6IjFkMGEwMjBhY2Y1YzRiNmM0OTc5ODlkZjFhYmYwZmJkNGU4YzhkNjMiLCJtb2JpbGVfbnVtYmVyIjoiOTYzODg2MzY3NCJ9.g3rSt-blhVIgoenAcpmPnaekFX23k26vEk8v51j5bP4' // Replace with your actual access token
                        },
                        success: function(response) {
                            $('#loader').hide();
                            if (response.status === 'success') {
                                window.location = '{{ url('/welcome') }}'
                            } else {
                                handleErrorMessage(response.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            $('#loader').hide();
                            handleErrorMessage('Failed to verify OTP. Please try again.');
                            console.error('Failed to verify OTP. Error:',
                                error);
                            console.error('XHR Status:', status);
                            console.error('XHR Response:', xhr
                                .responseText);
                        }
                    });
                } else {
                    $('#error_message').text('Please enter the OTP.');
                }
            });*/
        });

        function handleErrorMessage(message) {
            $('#error_message').text(message);
        }
    </script>
@endsection