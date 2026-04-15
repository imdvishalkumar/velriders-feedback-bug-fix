@extends('templates.front')
@section('content')
<section>
     <div class="container">
          <div class="text-center">
               <h1>Contact Us</h1>
          </div>
     </div>

     <div class="container mt-5">
          <div class="row justify-content-center">
               <div class="col-md-12">
                    <div class="card shadow p-5 border border-0 w-100" style="background-color: #29ca8e;">
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
                         <div class="form_contact_us" style="margin: 50px;">
                              <div class="row mt-3">
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
                              <div class="row mt-3" style="padding-left: 20px;">
                                  <div class="col-md-12">
                                      <label for="message" class="form-label">Message</label>
                                      <textarea class="form-control effect-1" name="message_text" rows="3" autocomplete="off"></textarea>
                                      @if($errors->has('message_text'))
                                          <div class="error">{{ $errors->first('message_text') }}</div>
                                      @endif
                                      <div class="d-flex justify-content-end mt-3">
                                          <button type="submit" class="btn btn-primary py-2 p-2" style="background: #252020;">Send Message</button>
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
          <div class="row">
               <div class="col-md-6 col-sm-12" data-aos="fade-up" data-aos-delay="100">
                    <div class="branch-address text-center">
                         <h3>Ahmedabad</h3>
                         <p>Shop No. 232, Someshwar Complex, Satellite Road, Opposite Iscon Emporio, Near Star Bazaar, BRTS Stop, Satellite, Ahmedabad, Gujarat 380015</p>
                         <p>Opening Hours: 24/7</p>
                         <p>Contact: +91 9909927077</p>
                    </div>
               </div>
               <div class="col-md-6 col-sm-12" data-aos="fade-up" data-aos-delay="200">
                    <div class="branch-address text-center">
                         <h3>Jamnagar</h3>
                         <p>Shop No. 5, Dwarkesh Complex, Below Hotel Shivhari, Near Samarpan Over Bridge, Jamnagar, Gujarat 361006</p>
                         <p>Opening Hours: 24/7</p>
                         <p>Contact: +91 9909227077</p>
                    </div>
               </div>
          </div>
     </div>

</section>  

@endsection



