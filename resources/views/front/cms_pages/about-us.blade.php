@extends('templates.front')
@section('content')

 <section>
      <div class="container">
           <div class="text-center">
                <h1>About Us</h1>
                <h4>Shailesh Car & Bike Pvt. Ltd.</h4>
           </div>
      </div>
 </section>

<!-- Our Commitment Section -->
<section id="commitment" class="section-bg">
    <div class="container">
        <div class="section-header">
            <h2>Our Commitment</h2>
            <p>Providing seamless and reliable transportation solutions</p>
        </div>
        <div class="row text-center">
            <div class="col-lg-4">
                <div class="icon-box">
                    <i class="fa fa-handshake-o" style="font-size:48px;"></i>
                    <h4>Reliability</h4>
                    <p>Dependable services for every journey.</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="icon-box">
                    <i class="fa fa-car" style="font-size:48px;"></i>
                    <h4>Diverse Fleet</h4>
                    <p>Wide range of well-maintained vehicles.</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="icon-box">
                    <i class="fa fa-users" style="font-size:48px;"></i>
                    <h4>Customer Satisfaction</h4>
                    <p>Prioritizing your comfort and convenience.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Explore Our Fleet -->
<section id="fleet">
    <div class="container">
        <div class="section-header">
            <h2>Explore Our Fleet</h2>
            <p>Choose from a wide range of well-maintained vehicles</p>
        </div>
        <div class="row">
            <!-- Example of a car category -->
            <div class="col-lg-4 col-md-6">
                <div class="fleet-item">
                    <img src="{{asset('images/compact-car.jpg')}}" alt="Compact Car" style="max-width: 100%; height: auto; display: block; margin: 0 auto;" class="img-fluid">
                    <center><h3>Compact Cars</h3></center>
                </div>
            </div>
          <div class="col-lg-4 col-md-6">
              <div class="fleet-item">
                  <img src="{{asset('images/mahindra-thar-kappel.png')}}" alt="High-Performance SUV" style="max-width: 100%; height: auto; display: block; margin: 0 auto;" class="img-fluid">
                    <center><h3>High-Performance SUVs</h3></center>
              </div>
          </div>
            <!-- Example of a high-performance bike category -->
            <div class="col-lg-4 col-md-6">
                <div class="fleet-item">
                    <img src="{{asset('images/bmw-bike.webp')}}" alt="Bike" class="img-fluid" style="max-width: 100%; height: auto; display: block; margin: 0 auto;" class="img-fluid">
                    <center><h3>High-Performance Bikes</h3></center>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Customer Testimonials -->
<section id="testimonials" class="section-bg">
    <div class="container">
        <div class="section-header">
            <h2>Customer Testimonials</h2>
            <p>Hear from our satisfied customers</p>
        </div>
        <div class="row">
            <div class="col-lg-6">
                <div class="testimonial-item">
                    <h3>Sunil Jagnani</h3>
                    <h4>Entrepreneur</h4>
                    <p>
                        <i class="fa fa-quote-left"></i>
                        Renting a car for my business trips has never been easier. The service is excellent!
                        <i class="fa fa-quote-right"></i>
                    </p>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="testimonial-item">
                    <h3>Yash Sheladia</h3>
                    <h4>Store Owner</h4>
                    <p>
                        <i class="fa fa-quote-left"></i>
                        A wide range of bikes to choose from, and each one is in top-notch condition!
                        <i class="fa fa-quote-right"></i>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Our Story -->
<section id="story">
    <div class="container">
        <div class="section-header">
            <h2>Our Story</h2>
            <p>The journey of Shailesh Car & Bike Pvt. Ltd.</p>
        </div>
        <!-- Example entry in the timeline -->
        <div class="timeline">
            <div class="timeline-item">
                <h4>2011 - The Beginning</h4>
                <p>Our journey started with a small fleet and a big dream.</p>
            </div>
            <div class="timeline-item">
                <h4>2018 - Expansion</h4>
                <p>We expanded our fleet and introduced luxury vehicles and high-performance bikes.</p>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section id="cta" style="background: url({{asset('images/cta-bg.jpg')}}) center center no-repeat; background-size: cover;">
    <div class="container">
        <h3>Ready to start your journey?</h3>
        <p>Contact us today to book your ride!</p>
        <a class="cta-btn" href="contact.html">Contact Us</a>
    </div>
</section>

@endsection
