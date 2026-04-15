@extends('templates.front')
@section('content')
     @if(isset($pricingPolicy) && $pricingPolicy != '')
      <section>
          <div class="container">
               <div class="text-center">
                    <h1>
                         @if(isset($pricingPolicy->title))
                              {{$pricingPolicy->title}}
                         @else
                              Pricing Policy
                         @endif
                    </h1>
               </div>
          </div>
     </section>
     <section class="section-background">
          <div class="container">
               @if(isset($pricingPolicy->content) && $pricingPolicy->content != '')
                    {!! $pricingPolicy->content !!}
               @else
               <div class="about-info">
                    <div class="container mt-5 mb-5">
                         <ul>
                              <li><strong>Base Rates:</strong> Our pricing is determined by vehicle type, ranging from compact cars to spacious SUVs, with competitive base rates for each category, starting from ₹1,500 onwards.</li>
                              <li><strong>Rental Duration:</strong> Longer rental durations may qualify for discounted daily rates, encouraging extended use of vehicles.</li>
                              <li><strong>Dynamic Pricing:</strong> We adjust prices based on demand, festival, season, functional purpose, like marriage, celebration, engagement, etc., ensuring fair rates during peak and off-peak periods.</li>
                              <li><strong>Transparent Fees:</strong> Our pricing includes all standard fees upfront. Any additional charges, such as fuel or optional services, are clearly communicated before confirmation.</li>
                              <li><strong>Promotions and Discounts:</strong> Periodic promotions and loyalty programs may offer special discounts to enhance affordability for our valued customers.</li>
                              <li><strong>Flexible Options:</strong> Choose from various rental plans, including hourly, weekly, daily, and monthly options, providing flexibility to meet diverse travel needs and customer convenience.</li>
                          </ul>
                          <p>At Shailesh Car & Bike PVT. LTD., we are dedicated to providing excellent value while maintaining transparency in our pricing structure. We value your money and help you in providing the best quality rental service. We aim at customer satisfaction service & continuing in healthy, happy satisfied customer relation with our organisation, where a trip ends creating sweet memories with our travel service.</p>
                    </div>
               </div>
               @endif
          </div>
      </section>
     @else
     <section>
          <div class="container">
               <div class="text-center">
                    <h1>Pricing Policy</h1>
               </div>
          </div>
     </section>
     <section class="section-background">
          <div class="container">
               <div class="about-info">
                    <div class="container mt-5 mb-5">
                         <ul>
                              <li><strong>Base Rates:</strong> Our pricing is determined by vehicle type, ranging from compact cars to spacious SUVs, with competitive base rates for each category, starting from ₹1,500 onwards.</li>
                              <li><strong>Rental Duration:</strong> Longer rental durations may qualify for discounted daily rates, encouraging extended use of vehicles.</li>
                              <li><strong>Dynamic Pricing:</strong> We adjust prices based on demand, festival, season, functional purpose, like marriage, celebration, engagement, etc., ensuring fair rates during peak and off-peak periods.</li>
                              <li><strong>Transparent Fees:</strong> Our pricing includes all standard fees upfront. Any additional charges, such as fuel or optional services, are clearly communicated before confirmation.</li>
                              <li><strong>Promotions and Discounts:</strong> Periodic promotions and loyalty programs may offer special discounts to enhance affordability for our valued customers.</li>
                              <li><strong>Flexible Options:</strong> Choose from various rental plans, including hourly, weekly, daily, and monthly options, providing flexibility to meet diverse travel needs and customer convenience.</li>
                          </ul>
                          <p>At Shailesh Car & Bike PVT. LTD., we are dedicated to providing excellent value while maintaining transparency in our pricing structure. We value your money and help you in providing the best quality rental service. We aim at customer satisfaction service & continuing in healthy, happy satisfied customer relation with our organisation, where a trip ends creating sweet memories with our travel service.</p>
                    </div>
               </div>
          </div>
     </section>
       @endif
@endsection