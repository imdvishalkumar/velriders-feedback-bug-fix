@extends('templates.front')
@section('content')

     @if(isset($privacyPolicy) && $privacyPolicy != '')
      <section>
          <div class="container">
               <div class="text-center">
                    <h1>
                         @if(isset($privacyPolicy->title))
                              {{$privacyPolicy->title}}
                         @else
                              Privacy Policy
                         @endif
                    </h1>
               </div>
          </div>
     </section>
     <section class="section-background">
          <div class="container">
               @if(isset($privacyPolicy->content) && $privacyPolicy->content != '')
                    {!! $privacyPolicy->content !!}
               @else
                   <h2>1. Overview</h2>
                   <p>At Shailesh Car & Bike Pvt Ltd, we are committed to protecting your privacy and ensuring the security of your personal information. This privacy policy outlines how we collect, use, disclose, and protect your data when you use our vehicle rental services.</p>
                   <h2>2. Information We Collect</h2>
                   <p>We may collect personal information, including but not limited to:</p>
                   <ul>
                       <li>Contact details (name, address, email, phone number)</li>
                       <li>Driver's license information</li>
                       <li>Payment information</li>
                       <li>Vehicle usage and location data</li>
                       <li>Customer feedback</li>
                   </ul>
                   <h2>3. How We Use Your Information</h2>
                   <p>We use your information for the following purposes:</p>
                   <ul>
                       <li>Processing and confirming reservations</li>
                       <li>Providing rental services and support</li>
                       <li>Verifying your identity and driver's eligibility</li>
                       <li>Communicating with you about reservations, promotions, and updates</li>
                       <li>Improving our services and customer experience</li>
                   </ul>
                   <h2>4. Data Security</h2>
                   <p>We employ industry-standard security measures to protect your data from unauthorized access, disclosure, alteration, and destruction.</p>
                   <h2>5. Information Sharing</h2>
                   <p>We may share your information with:</p>
                   <ul>
                       <li>Third-party service providers for operational purposes</li>
                       <li>Authorities if required by law or to protect our rights</li>
                       <li>Affiliated companies for internal business purposes</li>
                   </ul>
                   <h2>6. Your Choices</h2>
                   <p>You have the right to:</p>
                   <ul>
                       <li>Access, correct, or delete your personal information</li>
                       <li>Opt-out of marketing communications</li>
                   </ul>
                   <h2>7. Cookies and Tracking Technologies</h2>
                   <p>We use cookies and similar technologies to enhance your browsing experience and collect usage data. You can manage your preferences through your browser settings.</p>
                   <h2>8. Changes to this Privacy Policy</h2>
                   <p>We may update this policy to reflect changes in our practices. Please review it periodically.</p>
                   <h2>9. Contact Us</h2>
                   <p>For any questions or concerns about this privacy policy, contact us at contact: <a href="mailto:info@velriders.com">info@velriders.com</a></p>
                   <p>Effective Date: 01-01-2024</p>
               @endif
          </div>
      </section>
     @else
     <section>
          <div class="container">
               <div class="text-center">
                    <h1>Privacy Policy</h1>
               </div>
          </div>
     </section>
     <section class="section-background">
          <div class="container">
              <h2>1. Overview</h2>
              <p>At Shailesh Car & Bike Pvt Ltd, we are committed to protecting your privacy and ensuring the security of your personal information. This privacy policy outlines how we collect, use, disclose, and protect your data when you use our vehicle rental services.</p>\
              <h2>2. Information We Collect</h2>
              <p>We may collect personal information, including but not limited to:</p>
              <ul>
                  <li>Contact details (name, address, email, phone number)</li>
                  <li>Driver's license information</li>
                  <li>Payment information</li>
                  <li>Vehicle usage and location data</li>
                  <li>Customer feedback</li>
              </ul>
              <h2>3. How We Use Your Information</h2>
              <p>We use your information for the following purposes:</p>
              <ul>
                  <li>Processing and confirming reservations</li>
                  <li>Providing rental services and support</li>
                  <li>Verifying your identity and driver's eligibility</li>
                  <li>Communicating with you about reservations, promotions, and updates</li>
                  <li>Improving our services and customer experience</li>
              </ul>
              <h2>4. Data Security</h2>
              <p>We employ industry-standard security measures to protect your data from unauthorized access, disclosure, alteration, and destruction.</p>
              <h2>5. Information Sharing</h2>
              <p>We may share your information with:</p>
              <ul>
                  <li>Third-party service providers for operational purposes</li>
                  <li>Authorities if required by law or to protect our rights</li>
                  <li>Affiliated companies for internal business purposes</li>
              </ul>
              <h2>6. Your Choices</h2>
              <p>You have the right to:</p>
              <ul>
                  <li>Access, correct, or delete your personal information</li>
                  <li>Opt-out of marketing communications</li>
              </ul>
              <h2>7. Cookies and Tracking Technologies</h2>
              <p>We use cookies and similar technologies to enhance your browsing experience and collect usage data. You can manage your preferences through your browser settings.</p>
              <h2>8. Changes to this Privacy Policy</h2>
              <p>We may update this policy to reflect changes in our practices. Please review it periodically.</p>
              <h2>9. Contact Us</h2>
              <p>For any questions or concerns about this privacy policy, contact us at contact: <a href="mailto:info@velriders.com">info@velriders.com</a></p>
              <p>Effective Date: 01-01-2024</p>
          </div>
      </section>

      @endif
  
@endsection