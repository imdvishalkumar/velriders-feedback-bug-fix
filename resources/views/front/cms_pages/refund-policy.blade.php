@extends('templates.front')
@section('content')
     @if(isset($refundPolicy) && $refundPolicy != '')
      <section>
          <div class="container">
               <div class="text-center">
                    <h1>
                         @if(isset($refundPolicy->title))
                              {{$refundPolicy->title}}
                         @else
                              Cancellation and Refund Policy
                         @endif
                    </h1>
               </div>
          </div>
     </section>
     <section class="section-background">
          <div class="container">
               @if(isset($refundPolicy->content) && $refundPolicy->content != '')
                    {!! $refundPolicy->content !!}
               @else
               <div class="about-info">
                    <div class="container mt-5 mb-5">
                         <h3>Cancellation Window</h3>
                         <ul>
                              <li><strong>(a)</strong> Cancellations made 24 hours before the scheduled pickup time are
                                   eligible for no refund.</li>
                              <li><strong>(b)</strong> Cancellations made between 24 hours to 72 hours before the
                                   scheduled pickup are eligible for a 50% refund.</li>
                              <li><strong>(c)</strong> Cancellations made 72 hours before the scheduled pickup are
                                   eligible for a 95% refund, with a deduction of a 5% platform fee.</li>
                         </ul>
                         <h3>No-Show</h3>
                         <p>Failure to pick up the vehicle at the scheduled booking time without prior cancellation or
                              delay notice may result in a no-show fee.</p>
                         <h3>Refund Process</h3>
                         <p>Refunds for eligible cancellations will be processed within 24 hours to 7 working days using
                              the original payment method.</p>
                         <h3>Modification</h3>
                         <p>Changes to reservations can be made based on availability, and associated cost adjustments
                              will be communicated.</p>
                         <h3>Force Majeure</h3>
                         <p>In cases of unforeseen circumstances, special consideration may be given to cancellations.
                              The refund process may be delayed in such circumstances.</p>
                         <p>For detailed information, please refer to our Terms and Conditions or feel free to contact
                              our support center for more clear views. We value your understanding and look forward to
                              serving you at Shailesh Car & Bike PVT. LTD. Vehicle Rental Service.</p>
                    </div>
               </div>
               @endif
          </div>
      </section>
     @else
     <section>
          <div class="container">
               <div class="text-center">
                    <h1>Cancellation and Refund Policy</h1>
               </div>
          </div>
     </section>
     <section class="section-background">
          <div class="container">
               <div class="about-info">
                    <div class="container mt-5 mb-5">
                         <h3>Cancellation Window</h3>
                         <ul>
                              <li><strong>(a)</strong> Cancellations made 24 hours before the scheduled pickup time are
                                   eligible for no refund.</li>
                              <li><strong>(b)</strong> Cancellations made between 24 hours to 72 hours before the
                                   scheduled pickup are eligible for a 50% refund.</li>
                              <li><strong>(c)</strong> Cancellations made 72 hours before the scheduled pickup are
                                   eligible for a 95% refund, with a deduction of a 5% platform fee.</li>
                         </ul>
                         <h3>No-Show</h3>
                         <p>Failure to pick up the vehicle at the scheduled booking time without prior cancellation or
                              delay notice may result in a no-show fee.</p>
                         <h3>Refund Process</h3>
                         <p>Refunds for eligible cancellations will be processed within 24 hours to 7 working days using
                              the original payment method.</p>
                         <h3>Modification</h3>
                         <p>Changes to reservations can be made based on availability, and associated cost adjustments
                              will be communicated.</p>
                         <h3>Force Majeure</h3>
                         <p>In cases of unforeseen circumstances, special consideration may be given to cancellations.
                              The refund process may be delayed in such circumstances.</p>
                         <p>For detailed information, please refer to our Terms and Conditions or feel free to contact
                              our support center for more clear views. We value your understanding and look forward to
                              serving you at Shailesh Car & Bike PVT. LTD. Vehicle Rental Service.</p>
                    </div>
               </div>
          </div>
     </section>
     @endif
@endsection