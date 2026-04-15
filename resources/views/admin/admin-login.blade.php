<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Velriders - Admin login</title>

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
        }
    </style>
</head>

<body class="hold-transition login-page">
    <div class="login-box">
        <!-- /.login-logo -->
        <div class="card card-outline card-primary">
            <div class="card-header text-center">
                <a href="/" class="h1"><b>Vel</b>Riders</a>
            </div>
            <div class="card-body">
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
                <p class="login-box-msg">Sign in to start your session</p>
                <form action="{{ route('admin.post-login') }}" method="POST" id="login-form">
                    @csrf
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-user"></span>
                            </div>
                        </div>
                    </div>
                    <div id="username_error"></div>
                    <div class="input-group mb-3">
                        <input type="password" class="form-control" name="password" id="password"
                            placeholder="Password" required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span id="togglePassword" class="fas fa-eye"
                                    onclick="togglePasswordVisibility()"></span>
                            </div>
                        </div>
                    </div>
                    <div id="password_error"></div>
                    <div class="row">
                        <div class="col-8">
                        </div>
                        <!-- /.col -->
                        <div class="col-4">
                            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
                        </div>
                        <!-- /.col -->
                    </div>
                </form>

            </div>
            <!-- /.card-body -->
        </div>
        <!-- /.card -->
    </div>
    <!-- /.login-box -->

    <!-- jQuery -->
    <script src="{{ asset('plugins/jquery/jquery.min.js') }}"></script>
    <script src = "https://cdn.jsdelivr.net/npm/jquery-validation@1.19.2/dist/jquery.validate.min.js"> </script>
    <!-- Bootstrap 4 -->
    <script src="{{ asset('plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <!-- AdminLTE App -->
    <script src="{{ asset('dist/js/adminlte.min.js') }}"></script>
    <!-- Notyf -->
    <script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>

    <script>
       $(function() {
            setTimeout(function() {
                $(".alert-danger").fadeOut(500);  
            }, 5000); 
        });
        const toast = new Notyf({
            position: {
                x: 'center',
                y: 'top',
            }
        });
        jQuery.validator.addMethod("nospace", function(value, element, params) 
        {
          var length = $.trim(value).length;
             if(length == 0)
             {
               return false;
             }
             else
             {
                return true;
             }
        }, jQuery.validator.format("only space not allowed"));

        $( "#login-form" ).validate({
          rules: {        
              username: {  required: true, nospace :true, maxlength:20},
              password: { required: true, nospace :true, minlength:4, maxlength:15 },     
          },
          messages:{
              username: { required: "Please enter Username" },
              password: { required: "Please enter Password" },     
          },
          highlight: function( element, errorClass, validClass ) {              
            if ( element.type === "text" ) {
                $(element).removeClass(errorClass).removeClass(validClass);
            }
          },
          errorPlacement: function (error, element) {
            if (element.attr("id") == "username") {
                error.appendTo("#username_error");
            } else if (element.attr("id") == "password") {
                error.appendTo("#password_error");
            } else {
                error.insertAfter(element);
            }
        }
      });
        $(document).ready(function() {

            /*$('#login').submit(function(e) {
                e.preventDefault();
                var username = $('input[name="username"]').val();
                var password = $('input[name="password"]').val();

               {{-- $.ajax({
                    url: '{{ route('admin.login') }}',
                    type: 'POST',
                    data: {
                        username: username,
                        password: password,
                        _token: '{{ csrf_token() }}',
                    },
                    success: function(response) {
                        if (response.status) {
                            window.location.href = '{{ route('admin.dashboard') }}';
                        } else {
                            toast.error(response.message);
                        }
                    },
                    error: function(response) {
                        console.log(response);
                        toast.error('Something went wrong');
                    }
                });--}}
            });*/
            $('#togglePassword').on('click', function() {
                var passwordField = $('#password');
                var passwordFieldType = passwordField.attr('type');

                if (passwordFieldType === 'password') {
                    passwordField.attr('type', 'text');
                    $(this).removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    passwordField.attr('type', 'password');
                    $(this).removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });

        });
    </script>
</body>

</html>
