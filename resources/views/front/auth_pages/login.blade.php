@extends('templates.auth')
@push('styles')
<style>
    #error_message {
        color: red;
    }
    #loader {
        position: fixed;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
        z-index: 1000;
    }
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
    <div class="login_form">
        <div class="container-fluid d-flex justify-content-center align-items-center"
            style="height: calc(100vh - 120px)">
            <div class="card d-flex flex-column justify-content-center align-items-center col-md-8 col-sm-6 col-lg-5">
                <div
                    class="card__header d-flex bg-white justify-content-center align-items-center box__shadow p-3 w-100">
                    <h5>Login / Signup</h5>
                </div>
                <div id="overlay">
                    <div class="cv-spinner">
                        <span class="spinner"></span>
                    </div>
                </div>
                <div id="error-container"></div>
                <form class="d-flex flex-column col-12 gap-4 p-4" id="login-form">
                    <div class="form-group">
                        <input type="text" id="phone_number" class="form-control"
                            placeholder="Enter the Phone Number" name="phone_number">
                    </div>
                    <div id="error_message"></div>
                    <button class="btn btn-primary py-3 d-flex justify-content-center gap-3 align-items-center"
                        id="get_otp_button" type="button">
                        GET OTP
                    </button>
                </form>
                <div class="third__party_login col-12 px-4 d-flex flex-column gap-3">
                    <button class="btn btn-dark py-3 d-flex justify-content-center gap-3 align-items-center"
                        style="background-color: #000000;">
                        <img src="{{asset('front_images/icons/apple-alt.svg')}}" alt="Apple"> Continue With Apple
                    </button>
                    <a href="{{url('auth/google')}}" class="btn btn-light py-3 d-flex justify-content-center gap-3 align-items-center">
                        <img src="{{asset('front_images/icons/google.svg')}}" alt="Google"> Continue With Google
                    </a>
                    <button class="btn btn-light py-3 d-flex justify-content-center gap-3 align-items-center"
                        style="background-color: #1877F2; color: white;">
                        <img src="{{asset('front_images/icons/facebook-alt.svg')}}" alt="Facebook" style="height: 25px;">
                        Continue With Facebook
                    </button>
                    <div class="terms-div d-flex flex-column align-items-center mt-2">
                        <p class="text-black">By Creating an account I agree to </p>
                        <a href="#" class="text-black mb-4">Terms of Services</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
    /*const input = document.querySelector("#mobile_code");
    window.intlTelInput(input, {
        utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@21.2.5/build/js/utils.js",
        initialCountry: "in",
        showSelectedDialCode: true,
    });*/
</script>
<script>
    $(document).ready(function() {
        $('#get_otp_button').click(function() {
            var phoneNumber = $('#phone_number').val().trim();
            var countryCode = '+91';
            if (phoneNumber !== '') {
                $('#loader').show();
                var URL = apiUrl+'send-otp';
                $.ajax({
                    url: URL,
                    type: 'POST',
                    contentType: 'application/x-www-form-urlencoded',
                    data: {
                        country_code: countryCode,
                        mobile_number: phoneNumber
                    },
                    success: function(response) {
                        $('#loader').hide(); // Hide loader
                        if (response.status === 'success') {
                            window.location = '{{ url('/verify-login') }}?phone=' + phoneNumber;
                        } else {
                            handleErrorMessage(response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#loader').hide(); // Hide loader
                        console.error('Failed to get OTP. Error: ' + error);
                    }
                });
            } else {
                $('#error_message').text('Please enter your phone number.');
            }
        });
    });

    function handleErrorMessage(message) {
        $('#error_message').text(message);
    }
</script>
@endsection