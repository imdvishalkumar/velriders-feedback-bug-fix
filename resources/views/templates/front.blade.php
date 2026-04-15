<!DOCTYPE html>
<html lang="en">
<head>
     <title>Vel Riders | Car & Bike Rental</title>
     <meta charset="UTF-8">
     <meta http-equiv="X-UA-Compatible" content="IE=Edge">
     <meta name="description" content="">
     <meta name="keywords" content="">
     <meta name="author" content="">
     <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
     <link rel="stylesheet" href="{{asset('css/bootstrap.min.css')}}">
     <link rel="stylesheet" href="{{asset('css/font-awesome.min.css')}}">
     <link rel="stylesheet" href="{{asset('css/owl.carousel.css')}}">
     <link rel="stylesheet" href="{{asset('css/owl.theme.default.min.css')}}">

     <!-- MAIN CSS -->
     <link rel="stylesheet" href="{{asset('css/style.css')}}">

     <style>
     .error {
          color:red;
     }
     </style>
</head>

<body id="top" data-spy="scroll" data-target=".navbar-collapse" data-offset="50">
     <!-- PRE LOADER -->
     <section class="preloader">
          <div class="spinner">
               <span class="spinner-rotate"></span>
          </div>
     </section>

     <!-- MENU -->
     <section class="navbar custom-navbar navbar-fixed-top" role="navigation">
          <div class="container">
               <div class="navbar-header">
                    <button class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                         <span class="icon icon-bar"></span>
                         <span class="icon icon-bar"></span>
                         <span class="icon icon-bar"></span>
                    </button>

                    <!-- lOGO TEXT HERE -->
                    <a href="{{route('front.home')}}"><img src="{{asset('images/mask.svg')}}" alt="images" width="200" height="70"></a>
               </div>

               <!-- MENU LINKS -->
               <div class="collapse navbar-collapse">
                    <ul class="nav navbar-nav navbar-nav-first">
                         <li class="@if(Route::is('front.home')){{'active'}}@else{{''}}@endif"><a href="{{route('front.home')}}">Home</a></li>
                         <li class="@if(Route::is('front.about-us')){{'active'}}@else{{''}}@endif"><a href="{{route('front.about-us')}}">About Us</a></li>
                         <li class="@if(Route::is('front.terms-condition')){{'active'}}@else{{''}}@endif"><a href="{{route('front.terms-condition')}}">Terms and Conditions</a></li>
                         <li class="dropdown @if(Route::is('front.privacy-policy') || Route::is('front.refund-policy') || Route::is('front.pricing-policy')){{'active'}}@else{{''}}@endif">
                              <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button"
                                   aria-haspopup="true" aria-expanded="false">Policies<span class="caret"></span></a>
                              <ul class="dropdown-menu">
                                   <li class="@if(Route::is('front.privacy-policy')){{'active'}}@else{{''}}@endif"><a href="{{route('front.privacy-policy')}}">Privacy Policy</a></li>
                                   <li class="@if(Route::is('front.refund-policy')){{'active'}}@else{{''}}@endif"><a href="{{route('front.refund-policy')}}">Refunds / Cancellations policy</a></li>
                                   <li class="@if(Route::is('front.pricing-policy')){{'active'}}@else{{''}}@endif"><a href="{{route('front.pricing-policy')}}">Pricing Policy</a></li>
                              </ul>
                         </li>
                         <li class="@if(Route::is('front.contact-us')){{'active'}}@else{{''}}@endif"><a href="{{route('front.contact-us')}}">Contact Us</a></li>
                    </ul>
               </div>

          </div>
     </section>

     @yield('content')

     <!-- FOOTER -->
     <footer id="footer">
          <div class="container">
               <div class="row">

                    <div class="col-md-4 col-sm-6">
                         <div class="footer-info">
                              <div class="section-title">
                                   <h2>Headquarter</h2>
                              </div>
                              <address>
                                   <p>Shop No. 232, Someshwar Complex, BRTS STOP, Satellite Rd, opp. Iscon Emporio, nr. Star Bazaar, Satellite, Ahmedabad, Gujarat 380015</p>
                              </address>

                              <ul class="social-icon">
                                   <li><a href="https://www.facebook.com/shaileshcarbike?mibextid=ZbWKwL" class="fa fa-facebook-square" attr="facebook icon"></a></li>
                                   <li><a href="https://g.co/kgs/qLxRQYq" class="google"><i class="fa fa-google"></i></a></li>
                                   <li><a href="https://www.instagram.com/velriders?igsh=eWhmc2R3cnloZzYy" class="fa fa-instagram"></a></li>
                              </ul>

                              <div class="copyright-text">
                                   <p>Copyright &copy; 2024 VEL RIDERS</p>
                              </div>
                         </div>
                    </div>

                    <div class="col-md-4 col-sm-6">
                         <div class="footer-info">
                              <div class="section-title">
                                   <h2>Contact Info</h2>
                              </div>
                              <address>
                                   <p>099092 27077</p>
                                   <p><a href="mailto:info@velriders.com">info@velriders.com</a></p>
                              </address>

                              <div class="footer_menu">
                                   <h2>Quick Links</h2>
                                   <ul>
                                        <li><a href="index.html">Home</a></li>
                                        <li><a href="about-us.html">About Us</a></li>
                                        <li><a href="terms.html">Terms & Conditions</a></li>
                                        <li><a href="contact.html">Contact Us</a></li>
                                   </ul>
                              </div>
                         </div>
                    </div>

                    <div class="col-md-4 col-sm-12">
                         <div class="footer-info newsletter-form">
                              <div class="section-title">
                                   <h2>Newsletter Signup</h2>
                              </div>
                              <div>
                                   <div class="form-group">
                                        <form action="#" method="get">
                                             <input type="email" class="form-control" placeholder="Enter your email"
                                                  name="email" id="email" required>
                                             <input type="submit" class="form-control" name="submit" id="form-submit"
                                                  value="Send me">
                                        </form>
                                        <span><sup>*</sup> Please note - we do not spam your email.</span>
                                   </div>
                              </div>
                         </div>
                    </div>

               </div>
          </div>
     </footer>

     <script>
          AOS.init();
          setTimeout(function() {$('#success-message').fadeOut('slow');}, 4000);
          setTimeout(function() {$('#error-message').fadeOut('slow');}, 4000);
     </script>
     <!-- SCRIPTS -->
     <script src="{{asset('all_js/jquery.js')}}"></script>
     <script src="{{asset('all_js/bootstrap.min.js')}}"></script>
     <script src="{{asset('all_js/owl.carousel.min.js')}}"></script>
     <script src="{{asset('all_js/smoothscroll.js')}}"></script>
     <script src="{{asset('all_js/custom.js')}}"></script>
     <script src="{{ asset('plugins/jquery/jquery.validate.min.js') }}"></script>
     <script src="{{ asset('plugins/jquery/additional-methods.min.js') }}"></script>
     <script src="{{ asset('all_js/front_js/common.js') }}"></script>
</body>

</html>