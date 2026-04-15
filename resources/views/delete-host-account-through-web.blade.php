<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <title>Velriders - Delete Customer</title>

    <link rel="shortcut icon" href="{{ asset('images/logo.png') }}" type="image/x-icon">

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome-free/css/all.min.css') }}">
    <!-- icheck bootstrap -->
    <link rel="stylesheet" href="{{ asset('plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('dist/css/adminlte.min.css') }}">
    <!-- Notyf -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">
    <style>
        .error{
            color:red;
            font-size: 15px;
        }
    </style>
</head>

<body class="hold-transition login-page">
    <div class="login-box" id="mobileCard">
        <div class="card card-outline card-primary">
            <div class="card-header text-center">
                <a href="javascript:void(0);" class="h1"><b>Vel</b>Riders</a>
            </div>
            <div class="card-body">
                <p class="login-box-msg">Delete Host User</p>

                <form method="post" id="mobile-form">
                    @csrf
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="mobile" name="mobile" placeholder="Enter Mobile No." required autocomplete="off">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-user"></span>
                            </div>
                        </div>
                    </div>
                    <span id="mobile-error"></span>
                    <div class="row">
                        <div class="col-4"></div>
                        <div class="col-4">
                            <button type="submit" class="btn btn-primary btn-block">Submit</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="login-box" id="otpCard">
        <div class="card card-outline card-primary">
            <div class="card-header text-center">
                <a href="javascript:void(0);" class="h1"><b>Vel</b>Riders</a>
            </div>
            <div class="card-body">
                <p class="login-box-msg">Host OTP Verification</p>

                <form method="post" id="otp-form">
                    @csrf
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="otp" name="otp" placeholder="Enter OTP" required autocomplete="off">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-user"></span>
                            </div>
                        </div>
                    </div>
                    <span id="otp-error"></span>
                    <div class="row">
                        <div class="col-4"> </div>
                        <div class="col-4">
                            <button type="submit" class="btn btn-primary btn-block">Verify</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="{{ asset('plugins/jquery/jquery.min.js') }}"></script>
    <!-- Bootstrap 4 -->
    <script src="{{ asset('plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <!-- AdminLTE App -->
    <script src="{{ asset('dist/js/adminlte.min.js') }}"></script>
    <!-- Notyf -->
    <script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>
    <script src="{{ asset('plugins/jquery/jquery.validate.min.js') }}"></script>

    <script>
        const Toast = new Notyf({
            position: {
                x: 'center',
                y: 'top',
            }
        });
        $(document).ready(function() {
            $('#otpCard').hide();
            $("#mobile-form").submit(function(e) {
                e.preventDefault();
                }).validate({
                    rules: {
                        mobile: {required: true, number:true, maxlength:10},
                    },
                    messages :{
                        mobile : { required : 'Please enter Mobile No.' },
                    },
                    highlight: function (element) {
                        if ($(element).is('select') || $(element).is('input')) {
                            $(element).parent('.select-wrap').addClass('error');
                        } else {
                            $(element).addClass('error');
                        }
                    },
                    errorPlacement: function (error, element) {
                        if (element.attr("name") == "mobile") {
                            error.appendTo("#mobile-error");
                        } else {
                            error.insertAfter(element);
                        }
                    },
                    submitHandler: function (form) {
                        var mobileNo = $('#mobile').val();
                        $.ajax({
                            url: "{{'send-host-otp'}}",
                            type: 'POST',
                            data: {
                                "_token":  $('meta[name="csrf-token"]').attr('content'), 
                                mobileNo : mobileNo,
                            },
                            success: function(response) {
                                if(response.status){
                                    Toast.open({
                                        type: 'success',
                                        message: response.message,
                                        background: 'green',
                                        duration: 3000,
                                    });
                                    $('#mobileCard').hide();
                                    $('#otpCard').show();
                                }else{
                                    $('#mobile').val('');
                                    Toast.open({
                                        type: 'error',
                                        message: response.message,
                                        background: 'red',
                                        duration: 3000,
                                    });
                                }
                            }
                        });
                    }
                });

            $("#otp-form").submit(function(e) {
                e.preventDefault();
                }).validate({
                    rules: {
                        otp: {required: true, number:true, maxlength:4},
                    },
                    messages :{
                        otp : { required : 'Please enter OTP' },
                    },
                    highlight: function (element) {
                        if ($(element).is('select') || $(element).is('input')) {
                            $(element).parent('.select-wrap').addClass('error');
                        } else {
                            $(element).addClass('error');
                        }
                    },
                    errorPlacement: function (error, element) {
                        if (element.attr("name") == "otp") {
                            error.appendTo("#otp-error");
                        } else {
                            error.insertAfter(element);
                        }
                    },
                    submitHandler: function (form) {
                        var mobileNo = $('#mobile').val();
                        var otp = $('#otp').val();
                        $.ajax({
                            url: "{{'verify-host-send-otp'}}",
                            type: 'POST',
                            data: {
                                "_token":  $('meta[name="csrf-token"]').attr('content'), 
                                'mobileNo' : mobileNo,
                                'otp' : otp
                            },
                            success: function(response) {
                                if(response.status){
                                    Toast.open({
                                        type: 'success',
                                        message: response.message,
                                        background: 'green',
                                        duration: 4000,
                                    });
                                    setTimeout(function() {
                                        location.reload();
                                    }, 4000);                              
                                }else{
                                    Toast.open({
                                        type: 'error',
                                        message: response.message,
                                        background: 'red',
                                        duration: 3000,
                                    });
                                }
                               console.log(response);
                            }
                        });
                    }
                });

        });
    </script>
</body>

</html>
