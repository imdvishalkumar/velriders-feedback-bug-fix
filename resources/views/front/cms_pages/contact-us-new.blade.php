@extends('templates.front_new')
@section('content')
    <div class="container mt-5">
        <div class="row d-flex justify-content-center">
            <div class="col-sm-4 text-center">
                <h3 class="text-primary fw-bold">Contact Us</h3>
                <h6 class="text-center text-secondary p-2">Any question or remarks? Just write us a message!</h6>
            </div>
        </div>
    </div>

    <div class="main-card">
        <div class="container-fluid p-0">
            <div class="row d-flex justify-content-center">
                <div class="col-md-9">
                    <div class="card shadow rounded-3 border border-0" style="background-color: #38A4A5;">
                        <div class="row">
                            <div class="col-md-4">
                                <h4 class="text-white p-5">Contact Information</h4>
                                <div class="contact_us p-5">
                                    <ul class="nav flex-column align-items-start gap-2 list-unstyled">
                                        <li>
                                            <a href="tel:8238224282" class="d-flex align-items-start gap-2 nav-link text-white p-0">
                                                <img src="{{asset('front_images/icons/phone-alt.svg')}}" alt="Phone icon"
                                                    class="me-2">
                                                <p class="mb-0">
                                                    08238224282
                                                </p>
                                            </a>
                                        </li>
                                        <br>
                                        <li>
                                            <a href="mailto:info@velriders.com" class="d-flex align-items-start gap-2 nav-link text-white p-0">
                                                <img src="{{asset('front_images/icons/email-alt.svg')}}" alt="Email icon"
                                                    class="me-2">
                                                <p class="mb-0">info@velriders.com</p>
                                            </a>
                                        </li>
                                        <br>
                                        <li>
                                            <a href="#"
                                                class="d-flex align-items-start gap-2 nav-link text-white p-0">
                                                <img src="{{asset('front_images/icons/location-alt.svg')}}" alt="Location icon"
                                                    class="me-2">
                                                <p class="mb-0">SHOP NO.232, Someshwar Complex, BRTS STOP, Satellite
                                                    Rd,
                                                    opp. Iscon Emporio, nr. Star Bazaar, Satellite, Ahmedabad, Gujarat
                                                    380015</p>
                                            </a>
                                        </li>
                                    </ul>
                                    <img src="{{asset('front_images/models/round.svg')}}" class="img-fluid-one" alt="...">
                                    <img src="{{asset('front_images/models/round_two.svg')}}" class="img-fluid-two"
                                        alt="...">
                                </div>
                            </div>
                            <div class="col-md-8 d-flex">
                                <div class="card shadow p-5 border border-0 w-100">
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
                                    <form class="card-body" action="{{ route('front.store-contact_us') }}" id="contact-form" method="POST">
                                    @csrf
                                    <div class="form_contact_us">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label for="firstName" class="form-label">First Name</label>
                                                <input type="text" class="form-control effect-1" id="firstName" name="first_name" autocomplete="off">
                                                @if($errors->has('first_name'))
                                                    <div class="error">{{ $errors->first('first_name') }}</div>
                                                @endif
                                            </div>
                                            <div class=" col-md-6">
                                                <label for="lastName" class="form-label">Last Name</label>
                                                <input type="text" class="form-control effect-1" id="lastName" name="last_name" autocomplete="off">
                                                @if($errors->has('last_name'))
                                                    <div class="error">{{ $errors->first('last_name') }}</div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-md-6">
                                                <label for="email" class="form-label">Email</label>
                                                <input type="email" class="form-control effect-1" id="email" name="email" autocomplete="off">
                                                @if($errors->has('email'))
                                                    <div class="error">{{ $errors->first('email') }}</div>
                                                @endif
                                            </div>
                                            <div class="col-md-6">
                                                <label for="phoneNumber" class="form-label">Phone Number</label>
                                                <input type="number" class="form-control effect-1" id="phoneNumber" name="mobile_no" autocomplete="off">
                                                @if($errors->has('mobile_no'))
                                                    <div class="error">{{ $errors->first('mobile_no') }}</div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-md-12">
                                                <label for="message" class="form-label">Message</label>
                                                <textarea class="form-control effect-1" name="message_text" rows="3" autocomplete="off"></textarea>
                                                @if($errors->has('message_text'))
                                                    <div class="error">{{ $errors->first('message_text') }}</div>
                                                @endif
                                                <div class="d-flex justify-content-end mt-3">
                                                    <button type="submit" class="btn btn-primary py-2 p-2"
                                                        style="font-weight: 500; font-size: 1rem; width: 25%;">Send Message</button>
                                                </div>
                                                <div class="d-flex justify-content-end">
                                                    <img src="{{asset('front_images/models/white_arrow.svg')}}"
                                                        class="img-fluid">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')

<script src="{{ asset('all_js/front_js/common.js') }}"></script>

@endpush