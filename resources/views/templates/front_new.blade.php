<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{asset('css/front_css/main.css')}}">
    <title>Velriders - Home</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <style>
        .img-fluid-two {
            position: absolute;
            margin-top: 8%;
            margin-left: 15%;
        }

        .img-fluid-one {
            max-width: 100%;
            height: auto;
            margin-left: 68%;
            margin-bottom: -188px;
            margin-top: 17px;
        }

        .effect-1 {
            border: 0;
            padding: 7px 0;
            border-bottom: 1px solid #ccc;
            width: 100%;
        }

        .main-card {
            margin-bottom: 15%;
        }
    </style>

    @stack('styles')
</head>

<body>
    <header>
        <!-- navbar --->
        <nav class="navbar navbar-expand-lg bg-white py-3">
            <div class="container">
                <div class="d-flex flex-row gap-5 justify-content-center align-items-center">
                    <a data-bs-toggle="offcanvas" href="#offcanvasExample" aria-controls="offcanvasExample">
                        <img src="{{asset('front_images/icons/sidebar.svg')}}">
                    </a>
                    <a class="navbar-brand" href="#">
                        <img src="{{asset('front_images/logo-full.png')}}" alt="Velriders">
                    </a>
                </div>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse custom-nav gap-5" id="navbarNav">
                    <ul class="navbar-nav gap-lg-4">
                        <li class="nav-item">
                            <a class="nav-link active" href="#">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Fleet</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Offers</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">About</a>
                        </li>
                        <li class="nav-item fs-sm-5">
                            <a class="nav-link" href="#">Contact Us</a> 
                        </li>
                    </ul>
                    <div class="d-flex gap-3 ms-auto">
                        <div class="dropdown me-3">
                            <button class="btn btn-outline-primary dropdown-toggle" type="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="{{asset('front_images/icons/location.svg')}}"> Location
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#">Action</a></li>
                                <li><a class="dropdown-item" href="#">Another action</a></li>
                                <li><a class="dropdown-item" href="#">Something else here</a></li>
                            </ul>
                        </div>
                        <a href="/login" class="btn btn-primary">Login | Sign Up</a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- offcanvas or sidebar -->
        <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasExample"
            aria-labelledby="offcanvasExampleLabel">
            <div class="offcanvas-header mt-5">
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <br>
            <div class="offcanvas-body">
                <ul class="nav sidebar__nav flex-column mb-auto gap-3">
                    <li>
                        <a href="#"
                            class="dropdown-toggle-split nav-link sidebar__link d-flex justify-content-between align-items-center"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="d-flex justify-content-center align-items-center gap-4">
                                <div class="nav__icon d-flex justify-content-center align-items-center">
                                    <img src="{{asset('front_images/icons/setting-green.svg')}}" alt="">
                                </div>
                                <p class="text-dark">Settings</p>
                            </div>
                            <img src="{{asset('front_images/icons/arrow-left.svg')}}" alt="">
                        </a>
                        <ul class="dropdown-menu shadow" style="width: 100%;">
                            <li class="dropdown-item">
                                <a href="./edit-profile.html"
                                    class="nav-link sidebar__link d-flex justify-content-between align-items-center">
                                    <div class="d-flex justify-content-center align-items-center gap-4">
                                        <p class="text-dark">Edit Profile</p>
                                    </div>
                                </a>
                            </li>
                            <li class="dropdown-item">
                                <a href="./change-number.html"
                                    class="nav-link sidebar__link d-flex justify-content-between align-items-center">
                                    <div class="d-flex justify-content-center align-items-center gap-4">
                                        <p class="text-dark">Change Number</p>
                                    </div>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li>
                        <a href="./favorite-vehicle.html" class="nav-link sidebar__link d-flex justify-content-between align-items-center">
                            <div class="d-flex justify-content-center align-items-center gap-4">
                                <div class="nav__icon d-flex justify-content-center align-items-center">
                                    <img src="{{asset('front_images/icons/favorite-green.svg')}}" alt="">
                                </div>
                                <p class="text-dark">Favorite Vehicles </p>
                            </div>
                            <img src="{{asset('front_images/icons/arrow-left.svg')}}" alt="">
                        </a>
                    </li>

                    <li>
                        <a href="./my-booking.html" class="nav-link sidebar__link d-flex justify-content-between align-items-center">
                            <div class="d-flex justify-content-center align-items-center gap-4">
                                <div class="nav__icon d-flex justify-content-center align-items-center">
                                    <img src="{{asset('front_images/icons/booking-green.svg')}}" alt="">
                                </div>
                                <p class="text-dark">My Booking</p>
                            </div>
                            <img src="{{asset('front_images/icons/arrow-left.svg')}}" alt="">
                        </a>
                    </li>

                    <li>
                        <a href="#"
                            class="dropdown-toggle-split nav-link sidebar__link d-flex justify-content-between align-items-center"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="d-flex justify-content-center align-items-center gap-4">
                                <div class="nav__icon d-flex justify-content-center align-items-center">
                                    <img src="{{asset('front_images/icons/document-green.svg')}}" alt="">
                                </div>
                                <p class="text-dark">Documents</p>
                            </div>
                            <img src="{{asset('front_images/icons/arrow-left.svg')}}" alt="">
                        </a>
                        <ul class="dropdown-menu shadow" style="width: 100%;">
                            <li class="dropdown-item" data-bs-toggle="modal" data-bs-target="#govtId">
                                <button
                                    class="nav-link sidebar__link d-flex justify-content-between align-items-center">
                                    <div class="d-flex justify-content-center align-items-center gap-4">
                                        <p class="text-dark">Submit Goverment id</p>
                                    </div>
                                </button>
                            </li>
                            <li class="dropdown-item" data-bs-toggle="modal" data-bs-target="#dlc">
                                <a href="#"
                                    class="nav-link sidebar__link d-flex justify-content-between align-items-center">
                                    <div class="d-flex justify-content-center align-items-center gap-4">
                                        <p class="text-dark">Submit you Driving licence</p>
                                    </div>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li>
                        <a href="https://velriders.com/terms.html" target="_blank"
                            class="nav-link sidebar__link d-flex justify-content-between align-items-center">
                            <div class="d-flex justify-content-center align-items-center gap-4">
                                <div class="nav__icon d-flex justify-content-center align-items-center">
                                    <img src="{{asset('front_images/icons/terms-green.svg')}}" alt="">
                                </div>
                                <p class="text-dark">Terms & Conditions </p>
                            </div>
                            <img src="{{asset('front_images/icons/arrow-left.svg')}}" alt="">
                        </a>
                    </li>
                    
                    <li>
                        <a href="https://velriders.com/contact.html" target="_blank"
                            class="nav-link sidebar__link d-flex justify-content-between align-items-center">
                            <div class="d-flex justify-content-center align-items-center gap-4">
                                <div class="nav__icon d-flex justify-content-center align-items-center">
                                    <img src="{{asset('front_images/icons/help-green.svg')}}" alt="">
                                </div>
                                <p class="text-dark">Help & Support</p>
                            </div>
                            <img src="{{asset('front_images/icons/arrow-left.svg')}}" alt="">
                        </a>
                    </li>

                    <li>
                        <a href="https://velriders.com/privacy-policy.html" target="_blank"
                            class="nav-link sidebar__link d-flex justify-content-between align-items-center">
                            <div class="d-flex justify-content-center align-items-center gap-4">
                                <div class="nav__icon d-flex justify-content-center align-items-center">
                                    <img src="{{asset('front_images/icons/privacy-green.svg')}}" alt="">
                                </div>
                                <p class="text-dark">Privacy Policy</p>
                            </div>
                            <img src="{{asset('front_images/icons/arrow-left.svg')}}" alt="">
                        </a>
                    </li>

                    <li class="red-link" data-bs-toggle="modal" data-bs-target="#exampleModal">
                        <a href="#" class="nav-link sidebar__link d-flex justify-content-between align-items-center">
                            <div class="d-flex justify-content-center align-items-center gap-4">
                                <div
                                    class="nav__icon nav__icon-danger d-flex justify-content-center align-items-center">
                                    <img src="{{asset('front_images/icons/logout-green.svg')}}" alt="">
                                </div>
                                <p class="text-danger">Logout</p>
                            </div>
                            <img src="{{asset('front_images/icons/arrow-left-red.svg')}}" alt="">
                        </a>
                    </li>

                    <li class="red-link" data-bs-toggle="modal" data-bs-target="#exampleModal2">
                        <a href="#" class="nav-link sidebar__link d-flex justify-content-between align-items-center">
                            <div class="d-flex justify-content-center align-items-center gap-4">
                                <div
                                    class="nav__icon nav__icon-danger d-flex justify-content-center align-items-center">
                                    <img src="{{asset('front_images/icons/delete-red.svg')}}" alt="">
                                </div>
                                <p class="text-danger">DELETE ACCOUNT</p>
                            </div>
                            <img src="{{asset('front_images/icons/arrow-left-red.svg')}}" alt="">
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!--Logout Modal -->
        <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered custom__model">
                <div class="modal-content">
                    <div class="modal-body d-flex justify-content-center align-items-center flex-column gap-3 p-5">
                        <h5>Are you sure want to logout?</h5>
                        <div class="btns d-flex justify-content-center align-items-center gap-4">
                            <button data-bs-dismiss="modal" class="btn btn-outline-primary">
                                Cancle
                            </button>
                            <button class="btn btn-primary">
                                Logout
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!--Delete Account Modal -->
        <div class="modal fade" id="exampleModal2" tabindex="-1" aria-labelledby="exampleModalLabe2" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered custom__model">
                <div class="modal-content">
                    <div class="modal-body d-flex justify-content-center align-items-center flex-column gap-3 p-5">
                        <h5>Delete Account</h5>
                        <p>THIS ACTION CANNOT BE UNDONE. This <br>
                            will permanently delete your account <br>
                            and all of its data.</p>
                        <div class="btns d-flex justify-content-center align-items-center gap-4">
                            <button data-bs-dismiss="modal" class="btn btn-outline-primary">
                                Cancle
                            </button>
                            <button class="btn btn-primary">
                                Logout
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Licence Id -->
        <div class="modal fade" id="govtId" tabindex="-1" aria-labelledby="govtId" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered rounded"> <!-- style="max-width: 800px;" -->

                <div class="modal-content">
                    <div
                        class="container-fluid shadow d-flex bg-white justify-content-center align-items-center p-3 w-100 rounded">
                        <h5>Goverment Id</h5>
                    </div>
                    <div class="d-flex justify-content-center align-items-start flex-column px-5 mt-5 gap-4">
                        <div
                            class="bg-light d-flex justify-content-between align-items-center p-3 rounded container document-status">
                            <div class="left d-flex justify-content-center align-items-center gap-3">
                                <img src="{{asset('front_images/icons/check.svg')}}">
                                <p class="text-black" style="font-weight: 500;">Status</p>
                            </div>
                            <div class="right">
                                <button class="btn btn-sm btn-outline-primary px-2">
                                    Approved
                                </button>
                            </div>
                        </div>

                        <button data-bs-toggle="modal" data-bs-target="#rpd"
                            class="btn p-3 btn-primary d-flex justify-content-center align-items-center gap-2 container">
                            <p>
                                Review Prior Documents
                            </p>
                            <img src="{{asset('front_images/icons/arrow-right.svg')}}">
                        </button>

                        <p class="text-primary">Apply for Documents Verification</p>

                        <div>
                            <div
                                class="imgs-container d-flex justify-content-start gap-3 align-items-center flex-wrap mb-2">
                                <div class="card d-flex justify-content-center align-items-center"
                                    style="border: 2px solid black; padding: 2rem; border-radius: .8rem;">
                                    <img src="{{asset('front_images/icons/upload.svg')}}">
                                </div>
                            </div>

                            <p class="text-primary">Upload the front page of your document </p>
                            <div class="d-flex justify-content-start mt-2 align-items-center gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" name="vehicle_type" type="checkbox" id="car">
                                    <label class="form-check-label" for="car">
                                        Car
                                    </label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" name="vehicle_type" type="checkbox" id="bike">
                                    <label class="form-check-label" for="bike">
                                        Bike
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div style="width: 100%;">
                            <div class="form-group mb-3">
                                <input type="text" class="form-control mobile_number_ip"
                                    placeholder="Enter your licence expiry date" name="name" required>
                            </div>
                            <div class="form-group">
                                <input type="text" class="form-control mobile_number_ip"
                                    placeholder="Enter your license number" name="name" required>
                            </div>
                        </div>
                        <button
                            class="btn p-3 btn-primary d-flex justify-content-center align-items-center gap-2 container mb-5">
                            <p>
                                Upload
                            </p>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Govt Id -->
        <div class="modal fade" id="dlc" tabindex="-1" aria-labelledby="dlc" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered rounded"> <!-- style="max-width: 800px;" -->

                <div class="modal-content">
                    <div
                        class="container-fluid shadow d-flex bg-white justify-content-center align-items-center p-3 w-100 rounded">
                        <h5>Goverment Id</h5>
                    </div>
                    <div class="d-flex justify-content-center align-items-start flex-column px-5 mt-5 gap-4">
                        <div
                            class="bg-light d-flex justify-content-between align-items-center p-3 rounded container document-status">
                            <div class="left d-flex justify-content-center align-items-center gap-3">
                                <img src="{{asset('front_images/icons/check.svg')}}">
                                <p class="text-black" style="font-weight: 500;">Status</p>
                            </div>
                            <div class="right">
                                <button class="btn btn-sm btn-outline-primary px-2">
                                    Approved
                                </button>
                            </div>
                        </div>

                        <button data-bs-toggle="modal" data-bs-target="#rpd"
                            class="btn p-3 btn-primary d-flex justify-content-center align-items-center gap-2 container">
                            <p>
                                Review Prior Documents
                            </p>
                            <img src="{{asset('front_images/icons/arrow-right.svg')}}">
                        </button>

                        <p class="text-primary">Apply for Documents Verification</p>

                        <div>
                            <div
                                class="imgs-container d-flex justify-content-start gap-3 align-items-center flex-wrap mb-2">
                                <div class="card d-flex justify-content-center align-items-center"
                                    style="border: 2px solid black; padding: 2rem; border-radius: .8rem;">
                                    <img src="{{asset('front_images/icons/upload.svg')}}">
                                </div>
                            </div>

                            <p class="text-primary">If your document has two sides, start by uploading the front side,
                                then proceed to upload the second side.</p>
                        </div>
                        <div style="width: 100%;">
                            <div class="form-group mb-3">
                                <input type="text" class="form-control mobile_number_ip"
                                    placeholder="Enter your licence document number" name="name" required>
                            </div>
                            <div class="form-group">
                                <input type="text" class="form-control mobile_number_ip"
                                    placeholder="Enter your license number" name="name" required>
                            </div>
                        </div>
                        <button
                            class="btn p-3 btn-primary d-flex justify-content-center align-items-center gap-2 container mb-5">
                            <p>
                                Upload
                            </p>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Review prior -->
        <div class="modal fade" id="rpd" tabindex="-1" aria-labelledby="rpd" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered rounded" style="max-width: 600px;">
                <!-- style="max-width: 800px;" -->

                <div class="modal-content">
                    <div
                        class="container-fluid shadow d-flex bg-white justify-content-center align-items-center p-3 w-100 rounded">
                        <h5>Review prior documents</h5>
                    </div>
                    <div class="d-flex justify-content-center align-items-start flex-column px-5 mt-5 gap-4">
                        <div
                            class="bg-light d-flex flex-column gap-2 justify-content-between align-items-center container p-3 rounded">
                            <div class="d-flex justify-content-between align-items-start container">
                                <img src="{{asset('front_images/licence.png')}}" class="rounded" height="100px" width="150px"
                                    style="object-fit: cover;">
                                <div class="details">
                                    <p class="text-black">Driving Licence</p>
                                    <div class="d-flex justify-content-start mt-2 align-items-center gap-4">
                                        <div class="form-check">
                                            <input class="form-check-input" name="vehicle_type" type="checkbox"
                                                id="car">
                                            <label class="form-check-label" for="car">
                                                Car
                                            </label>
                                        </div>

                                        <div class="form-check">
                                            <input class="form-check-input" name="vehicle_type" type="checkbox"
                                                id="bike">
                                            <label class="form-check-label" for="bike">
                                                Bike
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <button
                                    class="btn btn-sm btn-outline-primary gap-2 d-flex justify-content-center align-items-center">
                                    <img src="{{asset('front_images/icons/check.svg')}}" height="20px">
                                    Approved
                                </button>
                            </div>
                        </div>

                        <div
                            class="bg-light d-flex flex-column gap-2 justify-content-between align-items-center container mb-5 p-3 rounded">
                            <div class="d-flex justify-content-between align-items-start container">
                                <img src="{{asset('front_images/licence.png')}}" class="rounded" height="100px" width="150px"
                                    style="object-fit: cover;">
                                <div class="details">
                                    <p class="text-black">Driving Licence</p>
                                    <div class="d-flex justify-content-start mt-2 align-items-center gap-4">
                                        <div class="form-check">
                                            <input class="form-check-input" name="vehicle_type" type="checkbox"
                                                id="car">
                                            <label class="form-check-label" for="car">
                                                Car
                                            </label>
                                        </div>

                                        <div class="form-check">
                                            <input class="form-check-input" name="vehicle_type" type="checkbox"
                                                id="bike">
                                            <label class="form-check-label" for="bike">
                                                Bike
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <button
                                    class="btn btn-sm btn-outline-danger gap-2 d-flex justify-content-center align-items-center">
                                    <img src="{{asset('front_images/icons/reject.svg')}}" height="20px">
                                    Rejected
                                </button>
                            </div>
                            <p class="text-danger">REJECTED: Invalid documents; application not accepted.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </header>


<div class="container-fluid row mx-auto mt-3">
    @yield('content')

    <div class="facny-footer">
        <div
            class="d-flex flex-column flex-md-row justify-content-md-around align-items-center bg-light container email__subscription__container">
            <div class="mb-3 mb-md-0">
                <img src="{{asset('front_images/illustration/email-subscription.svg')}}" alt="" class="img-fluid">
            </div>
            <div class="content d-flex flex-column gap-3">
                <div>
                    <h6 class="text-primary">Do you want to stay updated on</h6>
                    <h2 class="text-primary">exclusive email offers?</h2>
                </div>
                <form class="card-body" action="{{ route('front.subscribe-form') }}" id="subscribe-form" method="POST">
                @csrf
                    <div style="border-radius: 2rem;"
                        class="email__subscription__div bg-white p-2 d-flex flex-column flex-md-row align-items-md-center gap-3">
                        <div class="form-group flex-grow-1">
                            <input type="email" class="form-control rounded-full email__subscription__input"
                                id="subscribe_email" name="subscribe_email" aria-describedby="emailHelp" placeholder="Enter email">
                            @if($errors->has('subscribe_email'))
                                <div class="error">{{ $errors->first('subscribe_email') }}</div>
                            @endif
                        </div>
                        <button class="btn btn-primary" type="submit">Subscribe</button>
                    </div>
                </form>

            </div>
        </div>
        <div class="footer mt-5">
            <footer class="bg-primary d-flex flex-column flex-md-row justify-content-around align-items-start p-3">
                <div class="footer__brand col-md-3 mb-4 mb-md-0">
                    <img src="{{asset('front_images/logo-alt.png')}}" alt="Velriders" class="mb-3">
                    <p class="text-white">We provide car and bike rentals for individuals and businesses, offering a
                        range of
                        vehicles from compact cars to luxury SUVs and bikes. Our rental process is efficient,
                        user-friendly,
                        and backed by excellent customer service.</p>
                </div>

                <div class="footer__links col-md-3 align-items-start text-white mb-4 mb-md-0">
                    <h6>Helpful Links</h6>
                    <br>
                    <ul class="nav flex-column gap-3 list-unstyled">
                        <li><a href="#" class="nav-link text-white p-0">Home</a></li>
                        <li><a href="#" class="nav-link text-white p-0">Features</a></li>
                        <li><a href="#" class="nav-link text-white p-0">Pricing</a></li>
                        <li><a href="#" class="nav-link text-white p-0">FAQs</a></li>
                        <li><a href="#" class="nav-link text-white p-0">About</a></li>
                    </ul>
                </div>

                <div class="footer__links col-md-3 text-white">
                    <h6>Get in Touch</h6>
                    <br>
                    <ul class="nav flex-column align-items-start gap-2 list-unstyled">
                        <li>
                            <a href="#" class="d-flex align-items-start gap-2 nav-link text-white p-0">
                                <img src="{{asset('front_images/icons/location-alt.svg')}}">
                                <p class="mb-0">SHOP NO.232, Someshwar Complex, BRTS STOP, Satellite Rd, opp. Iscon
                                    Emporio, nr. Star Bazaar, Satellite, Ahmedabad, Gujarat 380015</p>
                            </a>
                        </li>
                        <li>
                            <a href="mailto:info@velriders.com" class="d-flex align-items-start gap-2 nav-link text-white p-0">
                                <img src="{{asset('front_images/icons/email-alt.svg')}}">
                                <p class="mb-0">info@velriders.com</p>
                            </a>
                        </li>
                        <li>
                            <a href="tel:8238224282" class="d-flex align-items-start gap-2 nav-link text-white p-0">
                                <img src="{{asset('front_images/icons/phone-alt.svg')}}">
                                <p class="mb-0">082382 24282</p>
                            </a>
                        </li>
                    </ul>
                </div>
            </footer>
            <div class="container d-flex justify-content-between align-items-center gap-3"
                style="border-top: .5px solid white; flex-wrap: wrap; margin-top: 1rem; margin-bottom: 1rem;">
                <div class="social-icons d-flex flex-row gap-3 mt-3" style="flex-wrap: wrap;">
                    <a class="btn btn-social" style="width: 50px;">
                        <img src="{{asset('front_images/icons/facebook-alt.svg')}}" style="width: 10px;" alt="Instagram">
                    </a>
                    <a class="btn btn-social">
                        <img src="{{asset('front_images/icons/twitter-alt.svg')}}" alt="Instagram">
                    </a>
                    <a class="btn btn-social">
                        <img src="{{asset('front_images/icons/linkedin-alt.svg')}}" alt="Instagram">
                    </a>
                    <a class="btn btn-social">
                        <img src="{{asset('front_images/icons/instagram-alt.svg')}}" alt="Instagram">
                    </a>
                </div>
                <div class="app_links gap-3 d-flex align-items-center justify-content-center">
                    <a href="#">
                        <img src="{{asset('front_images/icons/play-store.svg')}}" alt="Play Store">
                    </a>
                    <a href="#">
                        <img src="{{asset('front_images/icons/app-store.svg')}}" alt="Play Store">
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="{{ asset('plugins/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('plugins/jquery/jquery.validate.min.js') }}"></script>
<script src="{{ asset('plugins/jquery/additional-methods.min.js') }}"></script>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
        integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous">
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"
        integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous">
</script>
<script>
    setTimeout(function() {$('#success-message').fadeOut('slow');}, 4000);
    setTimeout(function() {$('#error-message').fadeOut('slow');}, 4000);
</script>
@stack('script')
</body>

</html>