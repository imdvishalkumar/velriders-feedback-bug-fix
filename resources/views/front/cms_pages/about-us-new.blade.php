@extends('templates.front_new')
@section('content')
    <div class="container mt-5">
        <div class="row d-flex justify-content-center">
            <div class="col-sm-2 text-center">
                <h3 class="text-primary fw-bold">About</h3>
                <h6 class="text-center text-secondary p-2">Who We Are</h6>
            </div>
        </div>
    </div>

    <div class="main-card">
        <div class="row d-flex justify-content-center">
            <div class="col-md-9">
                <div class="card shadow rounded-3 border border-0">
                    <div class="row">
                        <div class="col-md-4 d-flex justify-content-center">
                            <img src="{{asset('front_images/models/car_group.svg')}}" class="img-fluid" alt="car_group">
                        </div>
                        <div class="col-md-8">
                            <div class="card shadow p-4 border border-0" style="background-color: #38A4A5;">
                                <label class="form-check-label text-white">Welcome to VelRiders - Your Premier Car
                                    and Bike Rental Hub!
                                    Since
                                    2017, we've been revolutionizing travel with a tech-driven platform, offering
                                    200+
                                    vehicles in various cities. Based in Jamnagar, our team of 60+ professionals
                                    ensures
                                    a
                                    top-notch user experience.</label>
                                <br>
                                <p class="text-decoration-underline fw-bold text-white">About VelRiders:</p>
                                <div class="row"></div>
                                <label class="form-check-label text-white">Empowering Hosts and Guests: Join our
                                    community for seamless
                                    car
                                    and bike sharing.</label>
                                <label class="form-check-label text-white">Local Exploration Made Easy: Whether
                                    local or a visitor,
                                    VelRiders is your go-to for convenient and comfortable city exploration.</label>
                                <label class="form-check-label text-white">A Vehicle for Every Occasion: Choose from
                                    our diverse fleet for
                                    short city trips or scenic drives.</label>
                                <br>
                                <p class="text-decoration-underline fw-bold text-white">Renting Made Easy in 3 Steps:</p>
                                <label class="form-check-label text-white">📅 Choose your travel date and
                                    time.</label>
                                <label class="form-check-label text-white">🚗 Select from our extensive
                                    range.</label>
                                <label class="form-check-label text-white">📲 Book and ride away with
                                    VelRiders!</label>
                                <br>
                                <p class="text-decoration-underline fw-bold text-white">VelRiders:</p>
                                <p class="fst-italic text-white">Your Perfect Ride Awaits! 🌟 Book now for a memorable journey!
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection