@extends('templates.front')
@section('content')
     @if(isset($termsCondition) && $termsCondition != '')
     <section>
          <div class="container">
               <div class="text-center">
                    <h1>
                         @if(isset($termsCondition->title))
                              {{$termsCondition->title}}
                         @else
                              Terms & Conditions
                         @endif
                    </h1><br>
                    <p class="lead">
                         Read Terms & Conditions
                    </p>
               </div>
          </div>
     </section>
     <section class="section-background">
          <div class="container">
               <div class="about-info">
                    <div class="container mt-5 mb-5">
                         @if(isset($termsCondition->content) && $termsCondition->content != '')
                              {!! $termsCondition->content !!}
                         @else
                         <p> Welcome to Shailesh Car & Bike PVT LTD AKA VELRIDERS located at www.velriders.com (the
                              “Site”) and the mobile application (the “App”).
                              The Site and App(each the “Platform”) are owned and operated by Shailesh Car & Bike PVT
                              LTD India, a company incorporated under the Companies Act 1956, having its registered
                              office at Ground Floor, Shop No-5, Dwarkesh Complex, Near Samarpan Over Bridge, Below Shiv
                              Hari Hotel,Jamnagar,Gujarat,361008.(also referred to as “Shailesh Car & Bike PVT
                              LTD”,“we,” “us,” or “our’ ‘All access and use of the Platform and the services thereon are
                              governed by our general Platform terms,(the “General Terms''), privacy policy available at
                              website.</p>
                         <p>These Terms of Service, including specific terms and conditions applicable to the Hosts and
                              Guests and Add-on Services (this “Agreement”/ “Host T&C”) read together with the Privacy
                              Policy, Fee Policy and other applicable policies (“Governing Policies”), collectively
                              create the legally binding terms and conditions on which Shailesh Car & Bike PVT LTD
                              offers to you or the entity you represent (“you”, “User” or “your”) the Shailesh Car And
                              Bike PVT LTD Host Services (defined below), including your access and use of Shailesh Car
                              And Bike PVT LTD Host Services.</p>
                         <p>Please read each of Governing Policies carefully to ensure that you understand each
                              provision and before using or registering on the website or accessing any material,
                              information or availing services through the Platform. If you do not agree to any of its
                              terms, please do not use the Platform or avail any services through the Platform. The
                              Governing Policies take effect when you click an “I Agree” button or checkbox presented
                              with these terms or, if earlier, when you use any of the services offered on the Platform
                              (the “Effective Date”). To serve you better, our Platform is continuously evolving, and we
                              may change or discontinue all or any part of the Platform, at any time and without notice,
                              at our sole discretion.</p>
                         <h3><strong>PRIVACY PRACTICES</strong></h3>
                         <p>portance of safeguarding your personal information and we have formulated a Privacy Policy
                              shaileshcar&bike@gmail.com to ensure that your personal information is sufficiently
                              protected. We encourage you to read it to better understand how you can update and manage
                              your information on the Platform.</p>
                         <h3><strong>AMENDMENTS/MODIFICATIONS</strong></h3>
                         <p>Shailesh Car & Bike PVT LTD reserves the right to change the particulars contained in the
                              Agreement from time to time and at any time. If Shailesh Car & Bike PVT LTD decides to
                              make changes to the Agreement, it will post the new version on the website and update the
                              date specified above or communicate the same to you by</p>
                         <p>other means. Any change or modification to the Agreement will be effective immediately from
                              the date of upload of the Agreement on the Platform. It is pertinent that you review the
                              Agreement whenever we modify them and keep yourself updated about the latest terms of
                              Agreement because if you continue to use the Shailesh Car & Bike PVT LTD Host Services
                              after we have posted a modified Agreement, you are indicating to us that you agree to be
                              bound by the modified Agreement. If you don’t agree to be bound by the modified terms of
                              the Agreement, then you may not use the Shailesh Car & Bike PVT LTD Host Services
                              anymore</p>
                         <p>Shailesh Car & Bike PVT LTD HOST SERVICES</p>
                         <p>Shailesh Car & Bike PVT LTD Host Services is a marketplace feature of the Platform more
                              particularly described below. That helps owners of vehicles (“Hosts”/ “Lessors”)connect
                              with users in temporary need of a vehicle on leasehold basis (“Guest”) for their personal
                              use(“Shailesh Car & Bike PVT LTD Host Services”).Shailesh Car & Bike PVT LTD does not
                              itself lease or deal with such vehicles in any manner whatsoever and only provides a
                              service connecting the Hosts to the Guests so they may enter into a Lease Agreement
                              (defined below).You understand and agree that Shailesh Car & Bike PVT LTD is not a party
                              to the Lease Agreement entered into between you as the Host of the vehicle or you as the
                              Guest of the vehicle, nor is Shailesh Car & Bike PVT LTD a transportation service,
                              agent, or insurer. Shailesh Car & Bike PVT LTD has no control over the conduct of the
                              Users of the Shailesh Car & Bike PVT LTD Host Services and disclaims all liability in
                              this regard.</p>
                         <p>Shailesh Car & Bike PVT LTD Host Services aims to establish and provide a robust
                              marketplace of reliable Hosts and Guests. Although Shailesh Car & Bike PVT LTD Host
                              Services provides support for the transaction between Hosts and Guests, we do guarantee
                              the quality or safety of the vehicles listed on the Platform.</p>
                         <h3><strong>SERVICES INFORMATION</strong></h3>
                         <p>Shailesh Car & Bike PVT LTD Host Services comprises of (a) the marketplace features
                              enables Hosts and Guests satisfying the applicable eligibility criteria listed below to
                              connect with one another for leasing of vehicle for personal use; and (b)
                              support/facilitation services for leasing including, among others, assistance with
                              execution of the lease agreement, payment facilitation, vehicle cleaning/sanitization,
                              vehicle delivery, on-road assistance, prospective Guest diligence and vehicle
                              usage/location tracking (“Add-on Services”);and (iii) web widgets, feeds, mobile device
                              software applications, applications for third-party web sites and services, and any other
                              mobile or online services and/or applications owned, controlled, or offered by Shailesh
                              Car & Bike PVT LTD. Shailesh Car & Bike PVT LTD attempts to be as accurate as possible
                              in the description of the Shailesh Car & Bike PVT LTD Host Services. However, Shailesh
                              Car & Bike PVT LTD does not warrant that the Shailesh Car & Bike PVT LTD Host
                              Services, information or other content of the Platform is accurate, complete,</p>
                         <p>reliable, current or error-free. The Platform may contain typographical errors or
                              inaccuracies and may not be complete or current</p>
                         <p>Shailesh Car & Bike PVT LTD reserves the right to correct, change or update information,
                              errors, inaccuracies, subjective conclusions, interpretations, views, opinions or even
                              human error, or omissions at any time (including after an order has been submitted)
                              without prior notice. Please note that such errors, inaccuracies, or omissions may also
                              relate to availability and Shailesh Car & Bike PVT LTD Host Services. The user of the
                              Shailesh Car & Bike PVT LTD Host Services shall not hold Shailesh Car & Bike PVT LTD
                              liable for any loss or damage relating to the same.</p>
                         <p>USE Of Shailesh Car & Bike PVT LTD HOST SERVICES</p>
                         <p>While you may use some sections/features of the Platform without registering with us, to
                              access the Shailesh Car & Bike PVT LTD Host Services you will be required to register
                              and create an account with us. Thereafter, only the Hosts and Guests satisfying the
                              applicable eligibility criteria (listed below) will be able to use the services subject to
                              the terms and conditions of this Agreement.</p>
                         <h3><strong>ELIGIBILITY</strong></h3>
                         <p>The Shailesh Car & Bike PVT LTD Host Services are intended solely for users who are 18
                              years or older and satisfy user specific criteria below. Any use of the Shailesh Car And
                              Bike PVT LTD Host Services by anyone</p>
                         <p>that does not meet these requirements is expressly prohibited. Host/Vehicle Eligibility
                              Criteria</p>
                         <p>The Host must have a valid passport, Aadhar number and/or other form of government issued
                              identification document.</p>
                         <p>The vehicle(s) proposed to be listed must be an eligible non-transport or private personal
                              use vehicle registered solely in your name. At the time of listing the vehicle(s) being
                              listed should also not have any pending insurance claims and/or other on-going
                              litigations, legal claims or any other claims that may arise in tort or law.</p>
                         <p>Your vehicle must be less than 10 years old and should meet all legal requirements of the
                              state of its registration and usage</p>
                         <p>Your vehicle must be clean, well maintained and have the basic accessories, including safety
                              device as per our maintenance, component and safety standards/equipment specifications
                              attached hereto as Annexure I.</p>
                         <p>You must abide by our exclusivity policy, which mandates that vehicles you list on Platform
                              must be</p>
                         <p>exclusively shared on the Platform and can’t appear on another car sharing/leasing platform.
                         </p>
                         <p>Your vehicle must meet our minimum insurance requirements of having Third Party
                              Comprehensive Insurance as is mandated under Motor Vehicle Act, 1988</p>
                         <p>Your vehicle must have fewer than 70000 kilometers and have never been declared a total loss
                         </p>
                         <p>You must have fitment of the In-Vehicle Devices in your vehicle to ensure safety and
                              tracking of the vehicle.</p>
                         <p>Guest Eligibility Criteria</p>
                         <p>The Guest must have a valid driving license issued by appropriate authority under Government
                              of India. The Guest must have valid passport, Aadhar number and/or other form of
                              government issued identification document</p>
                         <p>The Guest must have no recent vehicle accidents in the last year, major traffic violations
                              in the last 1 year, more than 2 recent moving violations and history of non-payment of
                              failure to pay</p>
                         <p>The Guest must have a clean criminal record, including but not limited to no felony(s), no
                         </p>
                         <p>violent crime(s), theft(s) or offense related to prohibited substance(s).</p>
                         <h3><strong>REGISTERING AND CREATING YOUR ACCOUNT</strong></h3>
                         <p>To access and use the Shailesh Car & Bike PVT LTD Host Services, you shall have to open an
                              account on the Platform with a valid email address by providing certain complete and
                              accurate information and documentation including but not limited to your name, date of
                              birth, an email address and password, and other identifying information as may be
                              necessary to open the account on the Platform. Each user may open and maintain only one
                              account on the Platform</p>
                         <p>Please see below an indicative list of documents that you will be required to submit as part
                              of the registration process on the Platform. Shailesh Car & Bike PVT LTD may on a need
                              basis request submission of additional documents as well, as it may deem necessary for
                              facilitation of Shailesh Car & Bike PVT LTD Host Services.</p>
                         <p>For Hosts</p>
                         <p>Registration Certificate.</p>
                         <p>Pollution Under Check Certificate.</p>
                         <p>Car Insurance.</p>
                         <p>Current Address Proof. (Rent Agreement/Company Allotment Letter etc.)</p>
                         <p>Valid Government ID Card (Aadhar, Voter’s ID, Passport etc.)</p>
                         <p>PAN Card For Guest: Valid Driver’s License.</p>
                         <p>Valid Government ID Card (Aadhar, Voter’s ID, Passport etc.) Canceled Cheque in name of the
                              Host 4.Current Address Proof. (Rent Agreement/Company Allotment Letter etc.)</p>
                         <p>Once you have created an account with us, you are responsible for maintaining the
                              confidentiality of your username, password, and other information used to register and
                              sign into our Platform, and you are fully responsible for all activities that occur under
                              this username and password. Please immediately notify us of any unauthorized use of your
                              account or any other breach of security by contacting us at If you interact with us or
                              with third-party service providers, you agree that all information that you provide will
                              be accurate, complete, and current. You acknowledge that the information you provide, in
                              any manner whatsoever, are not confidential or proprietary and does not infringe any
                              rights of a third party.</p>
                         <p>By registering on the Platform, each applicant i.e. The Host and the Guest authorizes
                              Shailesh Car & Bike PVT</p>
                         <p>LTD and Shailesh Car & Bike PVT LTD reserves the right, in its sole discretion, to verify
                              the documents submitted by such applicants through the Platform. Shailesh Car & Bike PVT
                              LTD may in its sole discretion use third-party services to verify the information you
                              provide to us and to obtain additional related information and corrections where
                              applicable, and you hereby authorize Shailesh Car & Bike PVT LTD to request, receive,
                              use, and store such information in accordance with our Privacy Policy. Further, Shailesh
                              Car & Bike PVT LTD reserves the right, at its sole discretion, to suspend or terminate
                              the Shailesh Car & Bike PVT LTD Services to any of the registered users while their
                              account is still active for any reason whatsoever. Shailesh Car & Bike PVT LTD may
                              provide any information necessary to the Hosts, insurance companies, or law enforcement
                              authorities to assist in the filing of a stolen car claim, insurance claim, vehicle
                              repossession, or legal action.</p>
                         <p>EACH HOST AND GUEST ACKNOWLEDGES AND AGREES THAT NEITHER Shailesh Car & Bike PVT LTD NOR
                              ANY OF ITS AFFILIATES WILL HAVE ANY LIABILITY TOWARDS ANY: (1) USER FOR ANY UNAUTHORIZED
                              TRANSACTION MADE USING ANY USERNAME OR PASSWORD; (2) PERSONAL BELONGINGS WHICH IS CLAIMED
                              BY GUEST TO BE LOST OR STOLEN ONCE THE BOOKING PERIOD ENDS; AND (3) THE UNAUTHORIZED USE
                              OF YOUR USERNAME AND PASSWORD FOR YOUR PLATFORM ACCOUNT COULD CAUSE YOU TO INCUR LIABILITY
                              TO BOTH Shailesh Car & Bike PVT LTD AND OTHER USERS.</p>
                         <h3><strong>ONBOARDING VEHICLE & LISTING BY THE HOST</strong></h3>
                         <p>Once the user account is created, Hosts can onboard and list their vehicle(s) on the
                              Platform for leasing</p>
                         <p>by following the single steps available on the platform</p>
                         <p>Host can hide its vehicle from platform on its requirement if booking for the same is not
                              confirmed.</p>
                         <p>Listing can be created from the Platform at least 1 hour in advance. Host shall ensure the
                              availability of the vehicle at the Designated Location for bookings during a Listing. Each
                              Listing Period shall be for a minimum of 4 hours and a maximum period of 6 months.</p>
                         <p>Cancellation/Rescheduling of a Listing: Host will not have right to cancel or reschedule the
                              booking. Except in accidental case submitted on the platform with proof. Charges, as
                              stipulated in the Fee Policy shall be applicable on cancellation or rescheduling a Listing
                              under certain conditions. However, in case where there are multiple cancellations in Guest
                              booking/s due to Host/a misdemeanor or unwarranted cancellations by the Host himself,
                              Shailesh Car & Bike PVT LTD at its sole discretion, shall have the right to terminate
                              Host from its platform and delist any/all vehicles listed on the Platform by such Host.
                              Designated Location: The vehicle shall be parked at Host’s own location. Host shall ensure
                              that the vehicle is parked in a clean, safe and clearly identifiable location (a
                              “Designated Location”). Host shall have the Designated Locations within the city limits.
                              Host shall provide Shailesh Car & Bike PVT LTD detailed directions to the Designated
                              Location(s) for ensuring that Guests are able to find and access the vehicle. If a
                              Designated Location has restricted access, Host shall ensure that Guests are able to
                              access the location for a booking to make the pickup process seamless.</p>
                         <p>For the use of the listing service, you shall allow the personal/representatives of Shailesh
                              Car & Bike PVT LTD to visit your premise for assessment of your vehicle and installing
                              the In-Vehicle Device in your vehicle to ensure its complete safety. Upon installation /
                              fitment of the In-vehicle Device the vehicle will be returned to the location designated
                              by you. You hereby unconditionally agree not to tamper or remove such In-Vehicle Devices.
                              You further agree and acknowledge that such installed In-vehicle Devices may require minor
                              modification from time to time and you shall provide full access of the vehicle to
                              Shailesh</p>
                         <p>Car & Bike PVT LTD or any other party appointed by Shailesh Car & Bike PVT LTD for the
                              purpose of modification of such devices. In case you remove or otherwise tamper the
                              In-vehicle Devices, you shall be liable to pay Shailesh Car & Bike PVT LTD the actual
                              cost of such In-vehicle Device. Shailesh Car & Bike PVT LTD further reserves the right
                              to deduct the foregoing amount from amount to be paid by Shailesh Car & Bike PVT LTD to
                              you. Both Host and Guest acknowledge and accept that Shailesh Car & Bike PVT LTD shall
                              not be liable for any consequential damages arising due to such unauthorized removal
                              and/or tampering of In-vehicle Device by either of the parties For the purpose of this
                              Agreement, “In-Vehicle Devices” means and includes the various devices selected by
                              Shailesh Car & Bike PVT LTD to be installed in the vehicle for the security, safety,
                              tracking and health monitoring of the vehicle Host hereby expressly consent to any
                              consequential loss and warranty loss such as OEM “Original equipment Manufacturer”
                              warranty that you may suffer, as a result of fitment of the In-vehicle Device in the
                              vehicle. Notwithstanding the foregoing Shailesh Car & Bike PVT LTD will not provide any
                              compensation upon termination of this Agreement or your account for any other reason
                              whatsoever. You will not fit any other devices in the vehicle other than the In-Vehicle
                              Devices, whether for customer privacy, GPS or otherwise. Upon termination of this
                              Agreement for any reason whatsoever, Shailesh Car & Bike PVT LTD will be authorized to
                              remove</p>
                         <p>In-Vehicle Device installed in the vehicle and any failure to do so due to a reason
                              attributable to you, will result in a penalty on you as per the Fee Schedule.</p>
                         <p>Further, you acknowledge and accept that Shailesh Car & Bike PVT LTD collects GPS and
                              driver behavior related data through the In-Vehicle Devices and that the same will be
                              collected even when you are using it for your personal use due to fitment of In-Vehicle
                              Device in your Vehicle. You hereby agree and expressly consent that Shailesh Car & Bike
                              PVT LTD shall be allowed to collect such aforementioned data until removal of the
                              In-Vehicle Device from the Vehicle.</p>
                         <p>Once the vehicle onboarding process is complete the Vehicle will be listed on the Platform.
                              Your Host listing page will also include information such as your city and area detail
                              where the vehicle is located, your listing description, your public profile photo, your
                              responsiveness in replying to Guests’ queries, and any additional information you share
                              with other users via the Platform.</p>
                         <p>By listing a vehicle, Hosts are agreeing to (i) provide true and accurate information and
                              are representing that the information that they are providing is accurate; (ii) that the
                              photos, contained in the listing are actual photos of the vehicle being advertised, and
                              that they are not misrepresenting their vehicle in any way; (iii) maintain only one active
                              listing, per vehicle, at a time; (e) truthfully represent any claims or allegations of
                              damage; and (f) work in good faith to resolve any disagreement with Shailesh Car & Bike
                              PVT LTD and the Guests.</p>
                         <h3><strong>ONLINE BOOKING</strong></h3>
                         <p>Once your account is created on the Platform, the Guest will receive confirmation of
                              successful creation of Guest account from Shailesh Car & Bike PVT LTD. Thereafter, the
                              verified Guests can view the vehicles listed on the Platform and send a booking request
                              for your vehicle via the Platform</p>
                         <p>The Guest will be able to (i) book the trip to start at any time of the day subject to
                              availability; and (ii) choose a start time of the trip from the next hour from the time of
                              the booking.</p>
                         <p>Upon receipt of booking request in relation to a vehicle, Shailesh Car & Bike PVT LTD
                              shall confirm such booking and communicate details of the final booking with the Host and
                              the Guest through an email, text message or message via the Platform confirming such
                              booking By accepting these terms relating to the online booking process, the parties
                              hereby acknowledge and agree that (i) each of the Host and Guest accept the conditions for
                              listing the vehicle on the Shailesh Car & Bike PVT LTD Platform and use of Shailesh Car
                              And Bike PVT LTD Services. (ii) Shailesh Car & Bike PVT LTD is merely a facilitator and
                              any arrangements entered into between Host and Guest through this Platform or otherwise is
                              solely at their own risk and expense.</p>
                         <h3><strong>VEHICLE OWNERSHIP</strong></h3>
                         <p>The parties, specifically the Guests understand that this Agreement only grant
                              rental/usufructuary/</p>
                         <p>limited rights of use over the vehicle, and all along the absolute and unencumbered
                              ownership of the vehicle for all intent and purposes, including for regulatory requirement
                              under the applicable laws in India, will remain with the Host. This Agreement will cover
                              all terms of listing and availing of Shailesh Car & Bike PVT LTD Host Services and the
                              Lease Agreement (as defined under) shall cover the terms of the subsequent booking as
                              agreed between the Host and the Guest, including Damage Protection Fee (defined below),
                              liability for violations, theft/accident, confiscation of vehicle, insurance, issues
                              related to the use of the vehicles, and so on. It is hereby clarified, and the Host and
                              the Guest acknowledge that Shailesh Car & Bike PVT LTD is not the owner of the vehicles
                              listed on its Platform and is merely a facilitator as provided under this Agreement.</p>
                         <h3><strong>LEASE OF VEHICLE</strong></h3>
                         <p>Upon acceptance of the booking by the Host, the Host and Guest will be required to duly
                              enter into a standard lease agreement (“Lease Agreement”) to formally execute the terms
                              and conditions and commercials for such booking to ensure compliance with the requirements
                              of applicable law. Shailesh Car & Bike PVT LTD shall assist both the Host and the Guest
                              with the electronic execution and record keeping as a part of its Shailesh Car & Bike
                              PVT LTD Host Services.The Guest understands and accepts that the trip cannot start unless
                              the Lease Agreement is duly executed over our Platform.</p>
                         <p>The Host hereby acknowledges and agrees that by accepting the terms of this Host T&C, all
                              Lease</p>
                         <p>Agreements that are executed over the Platform with any Guest for the Host’s vehicle bear
                              the Host’s express consent and such Lease Agreement shall constitute a binding agreement
                              between the Host and the Guest. The Host also acknowledges and agrees that he/she is
                              cognizant of the terms of all such lease agreements and the corresponding booking details
                              that have been executed over the Shailesh Car & Bike PVT LTD Platform for the particular
                              trip. The Host shall receive a copy of the executed Lease Agreement through email along
                              with the booking details soon after the same has been executed by Guest upon the Platform.
                         </p>
                         <p>By utilizing a separate Lease Agreement or otherwise displaying terms relating to the lease
                              as part of the online booking process, the parties hereby acknowledge and agree that (i)
                              such separate Lease Agreement is directly between the Guest and the Host; (ii) the
                              Shailesh Car & Bike PVT LTD is not party to such separate Lease Agreement, (iii) Lease
                              Agreement executed, is solely at the parties’ own risk and expense, (iv) nothing contained
                              in the Lease Agreement,</p>
                         <p>on the Platform or this Agreement is a substitute for the advice of a legal counsel and (v)
                              the parties have been hereby advised to obtain local legal counsel to prepare, review and
                              revise as necessary the Lease Agreement to ensure compliance with applicable laws. If
                              there is any conflict between the terms of a separate Lease Agreement and this Agreement,
                              the terms of this Agreement shall prevail.</p>
                         <h3><strong>OFFLINE ARRANGEMENTS</strong></h3>
                         <p>Any instances where the Host and the Guest enter into a lease, rental or similar/analogous
                              arrangement involving the hiring/sharing/renting of the listed vehicle (by whatever name
                              called) with an intention to circumvent the Platform, while using, attempting or intending
                              to wrongly benefit from Shailesh Car & Bike PVT LTD Host Services or any other services
                              on the Platform, including without limitation, the additional insurance coverage (herein
                              any such arrangement to be referred as (“Offline Arrangements”) shall be contravention of
                              this Agreement. Please note that such Offline Arrangements are not permitted for vehicle/s
                              listed on the Platform. If any such offer to lease a listed vehicle outside the Platform,
                              is made to/by either Parties (Host or the Guest), the same should be reported to Shailesh
                              Car & Bike PVT LTD immediately. If you fail to follow these requirements, you may be
                              subject to a range of actions, including limits on your access to Shailesh Car & Bike
                              PVT LTD Host Services and other services, restrictions on listings, suspension of your
                              account, application of Facilitation Fees, and recovery of our expenses in policy
                              monitoring and enforcement. Furthermore, Offline Arrangements are explicitly excluded from
                              any Shailesh Car & Bike PVT</p>
                         <p>LTD offered insurance coverage or claims and Shailesh Car & Bike PVT LTD shall in no case
                              be held liable for any damages (direct or indirect), consequential losses, loss of
                              profit/business as faced by Host or the Guest entering into such an arrangement.</p>
                         <h3><strong>VEHICLE DELIVERY</strong></h3>
                         <p>Soon after the boking of vehicle is confirmed the Host shall:</p>
                         <p>have the vehicle is cleaned, sanitized and kept ready for delivery (including servicing and
                              routine maintenance) as per our maintenance, component and safety standards/equipment
                              specifications in Annexure I or opt for Shailesh Car & Bike PVT LTD’s Add-on Service in
                              this regard, details and terms available on the website</p>
                         <p>keep the vehicle Key, copies of documentation of the Vehicle, including the registration
                              certificate, Vehicle Insurance policy, Pollution Under Control (PUC) Certificate and other
                              mandatory documents, if any, prescribed by the relevant authorities under Applicable Laws
                              (the “Vehicle Documentation”) ready for delivery.</p>
                         <p>ensure that the vehicle is delivered Guest at the Designated Location and at the specified
                              time.</p>
                         <p>The Guest must be present in-person to take or receive the delivery of the vehicle. The
                              Guest must examine the vehicle before accepting its delivery and shall be deemed to have
                              satisfied himself as to its condition and suitability for his/her purpose, and its
                              compliance with any prescribed safety standards. After the delivery, any fault in the car
                              shall be dealt with in accordance with the terms of the Lease Agreement.</p>
                         <p>Cancellation of Booking / Reduction of Booking Period: If the Guest wishes to cancel a
                              booking or reduce the booking period for which the vehicle has been reserved, Guest must
                              do so in advance, in pursuance of the Fee Policy. Furthermore, if the Guest refuses and/or
                              is unable/unwilling for any reason to accept delivery of the vehicle, the booking shall be
                              automatically canceled and the any Lease Rental paid in advance shall stand forfeited to
                              compensate the Host for the costs, charges, expenses, losses incurred by the Host arising
                              out of such an action of the Guest, in pursuance of the Fee Policy. In case of any loss
                              suffered by the Guest due to non-delivery, delay in delivery, failure in delivery, the
                              Guest will not hold Shailesh Car & Bike PVT LTD responsible for such loss.</p>
                         <h3><strong>VEHICLE USAGE TERMS</strong></h3>
                         <p>The vehicle shall be driven only by the Guest and used in a prudent and careful manner
                              solely for Guest's personal use within the territory specified in the</p>
                         <p>Lease Agreement (“Permitted Territory”), in strict compliance with the requirements of the
                              applicable Laws of India and the conditions of the Lease Agreement (the “Permitted Use”).
                         </p>
                         <p>Other than the Permitted Use, all other uses of the vehicle including the usages as listed
                              in the Lease Agreement (by the Guest and/or any other person(s) directly or indirectly
                              acting through, authorized by or on behalf of the Guest), are strictly prohibited (the
                              “Prohibited Uses”) and shall result in immediate termination of the Lease and Shailesh Car
                              And Bike PVT LTD Host Services without any notice to the Guest. The Prohibited Uses shall
                              more particularly be described in the Lease Agreement between the Host and the Guest.
                              Notwithstanding anything contrary to the above, Guest shall, at all times be liable to
                              compensate Host during the Booking Period for any/all deliberate damages caused to the
                              vehicle by Guests an/or any of his/her co-driver or any other person who was permitted to
                              drive the vehicle by the Guest.</p>
                         <h3><strong>AGREED MILEAGE</strong></h3>
                         <p>Agreed mileage of a vehicle for the booking period shall be as specified in the booking
                              details on the Platform (“Agreed Mileage”) and in case the actual use of the vehicle
                              varies from the Agreed Mileage, charges towards the difference be paid the Guest to the
                              platform as per our Fee Policy at the time of expiry of booking period.</p>
                         <p>FACILITATION FEE, DAMAGE PROTECTION FEE, FIXED PAYOUT AND LEASE RENTAL Facilitation Fee:</p>
                         <p>Shailesh Car & Bike PVT LTD shall be entitled to charge the Host a fee in lieu of
                              provision of Shailesh Car & Bike PVT LTD Host Services (“Facilitation Fee”). This
                              Facilitation Fee shall be calculated as a certain percentage (more particularly described
                              in Fee policy) of the Rental. The Facilitation Fee shall be deducted from the Lease Rental
                              at the time of pay-out to Host. Platform Fee: Shailesh Car & Bike PVT LTD shall be
                              entitled to charge the Host a fee of INR 500 per month in lieu of the safety and
                              operational expense of Host’s car (“Host Platform Fee”). The Platform Fee shall be
                              deducted from the Lease Rental at the time of pay-out to Host. b. Further, at the time of
                              booking Guest shall pay a fee of INR 99 per booking (“Guest Platform Fee”) in lieu of the
                              services provided to the Guest on Shailesh Car & Bike PVT LTD Platform. The Platform
                              Fees shall be payable by Guest in addition to the Damage Protection Fee payable at the
                              time of booking a vehicle. Damage Protection Fee: At the time of booking a vehicle, the
                              Guest shall have to pay upfront a fee for insuring the vehicle at the time of the trip and
                              (“Damage Protection Fee”). Shailesh Car & Bike PVT LTD shall facilitate such protection
                              plans from time to time on payment of such Damage Protection Fee. Pay-out to the Host: For
                              the first 3 months from onboarding of the vehicle (“Initial Pay-out period”), the Host
                              shall be eligible to a pay-out solely on basis of the period for which the Host has listed
                              the vehicle on the Platform This pay-out shall be calculated as a fixed amount on an
                              hourly basis, shall vary as per</p>
                         <p>the vehicle type and is calculated as per the parameters under the Fee Policy. After the
                              Initial Pay-out Period, this model will be suspended, and the Host shall be paid on the
                              basis of the Lease Rental as paid by the Guest post deduction of the Shailesh Car & Bike
                              PVT LTD Facilitation Fee as applicable. Lease Rental For Guest: The Guest shall be liable
                              to pay a fee (“Lease Rental”) for leasing the vehicle and it shall be inclusive of the
                              applicable taxes (if any) in force. The same is dynamic and subject to vehicle type,
                              booking distance and dates, location etc., and shall be payable as per the terms and
                              timelines mentioned in the Fee Policy. All such payments shall be made by the Guest over
                              the Shailesh Car & Bike PVT LTD Platform and payment to Shailesh Car & Bike PVT LTD
                              shall be considered the same as payment made directly to the Hosts by the Guests. Other
                              payments, refunds, and penalties: In addition to the above Lease Rental and the Damage
                              Protection Fee, the Guest shall also be liable for the following as described in the Fee
                              Policy: Default interest and reminder fee for late payments. Add-on Charges (if availed)
                              for services like home delivery facility or addition of a co-driver for the trip. Charges
                              for loss of keys, documents, unpaid tolls, traffic violation penalties. Cost for any
                              damages which may include both cost of repair as well as insurance cover as per the
                              standard rates in the Fee Policy</p>
                         <p>The Guest acknowledges and agrees that he/she shall be liable to pay such charges on
                              occurrence of any of the above-mentioned event/s and hereby authorizes Shailesh Car And
                              Bike PVT LTD to set off any amounts as may be due from Shailesh Car & Bike PVT LTD to
                              the Guest against any amounts that may be payable by the Guest under this Agreement, as
                              the case may be.</p>
                         <p>Guest also acknowledges and agrees that Shailesh Car & Bike PVT LTD shall have the right
                              to prohibit the Guest from making a subsequent booking on the Platform until all
                              outstanding fees in the Guest's account have been paid in full.</p>
                         <p>The Guests also understand and agree that Shailesh Car & Bike PVT LTD may charge
                              additional fees for failed payments, returned/canceled checks. The Guest will be
                              responsible to reimburse us for all costs of collection, including collection agency fees,
                              third party fees, and legal fees, and costs.</p>
                         <p>If you are a Host, you understand, acknowledge, and agree that Shailesh Car & Bike PVT LTD
                              may set the booking/reservation fee for your vehicle as per the Fee Policy. Shailesh Car
                              And Bike PVT LTD will adjudicate the booking/reservation fee on your behalf, which means
                              processing the Guest's [credit/debit card], retaining the Facilitation Fees and other
                              add-on services fee, if any, commission and remitting such funds to you as provided in
                              this section.</p>
                         <p>Shailesh Car & Bike PVT LTD reserves the right to withhold payment or charge back to your
                              account any amounts otherwise due to us under this Agreement, in the event of any account
                              information is lacking or mismatched or in the event of where there has been any breach of
                              this Agreement by you, pending Shailesh Car & Bike PVT LTD’s reasonable investigation of
                              such breach.</p>
                         <p>To ensure proper payment, both Guest and the Host are solely responsible for providing and
                              maintaining accurate contact and payment information associated with your account, which
                              includes, without limitation, applicable tax information and Shailesh Car & Bike PVT LTD
                              shall in no case be held liable on account of any error in payments due to information
                              wrongly provided by you.</p>
                         <p>If you dispute any payment made hereunder, you must notify Shailesh Car & Bike PVT LTD in
                              writing within 3 days of any such payment; failure to notify Shailesh Car & Bike PVT LTD
                              shall result in the waiver by you of any claim relating to any such disputed payment.
                              Payment shall be calculated solely based on records maintained by Shailesh Car & Bike
                              PVT LTD.</p>
                         <p>In the event of a conflict between this Clause and terms of the Fee Policy, the terms set
                              forth in the Fee Policy shall prevail.</p>
                         <h3><strong>HOST’S OBLIGATIONS</strong></h3>
                         <p>In connection with use of or access to the Shailesh Car & Bike PVT LTD Host Services the
                              Host shall not, and hereby agrees that it will not, nor advocate, encourage, request, or
                              assist any third party in activity or otherwise, to harm or threaten to harm users of our
                              community, including but not limited to, (i) "stalking" or harassing any other Guest or
                              Host of Shailesh Car & Bike PVT LTD community or user of the Platform (ii) collecting or
                              storing any personally identifiable information about any other member or associate of
                              Shailesh Car & Bike PVT LTD community, other than as specifically agreed / allowed
                              herein (iii) engaging in physically or verbally abusive or threatening conduct; or (iv)
                              using our Services to transmit, distribute, post, or submit any information concerning any
                              other person or entity, including without limitation, photographs of others without their
                              permission, personal contact information, or credit, debit, calling card, or account
                              numbers.</p>
                         <p>The Host is also bound to maintain car conditions and ensure continuity of his listings for
                              agreed upon periods on our Platform. In this regard, the Host is additionally governed by
                              Host Strike Policy, the failure to comply with which may lead to delisting of Host vehicle
                              from the Shailesh Car & Bike PVT LTD Host program.</p>
                         <p>Host further agrees and acknowledges that in case of any concerns including but not limited
                              to the damages caused to the vehicle during the booking period shall only be raised by
                              raising his/her concern via authorized ticket support process. If the Host refuses or
                              denies to follow the due redressal mechanism continuously, Shailesh Car & Bike PVT LTD
                              shall at its sole discretion have the right to terminate such Host from the Platform.
                              Further Shailesh Car & Bike PVT LTD shall not be liable to entertain or make good for
                              any such damage or other claims unless the same is duly routed through the authorized
                              ticket support process.</p>
                         <p>Checklists help us ensure that all information regarding the vehicle, the trip and customer
                              experience are captured so we can serve the Hosts and Guests better. Accordingly, Host
                              shall be responsible for filling:</p>
                         <p>“Car Ready Checklist” within 24 hours of listing start time. If the Host fails to fill it
                              within mentioned timelines, then the listing gets canceled automatically. “Booking End
                              Checklist” within 2 hours of the booking end time or the start till of the next booking.
                              If the Host fails to fill the checklist within the above stipulated timelines, then the
                              last available information with Shailesh Car & Bike PVT LTD (for e.g. from the Guest
                              checklist) shall be deemed as final for the closure of the booking</p>
                         <p>Guests OBLIGATIONS</p>
                         <p>Both parties shall be responsible to ensure compliance with the provisions of the Lease
                              Agreement at times during the Lease Term and until the return of the vehicle to the Host
                              in good working condition. In addition to other obligations and covenants under the Lease
                              Agreement, as regards the use of the Vehicle during the aforesaid period the Guest shall:
                         </p>
                         <p>at his/her expense maintain the cleanliness, condition, and appearance of the vehicle in as
                              good an operating condition as it was on the commencement date of the Lease Term. use the
                              Vehicle only for the Permitted Use in conformity with the Host’s manual instructions
                              provided as part of Vehicle Documentation, applying the same degree of care when using the
                              vehicle as would not drive vehicle roughly and strictly refrain from Prohibited Use of
                              Vehicle and other requirements as laid down more particularly in the Lease Agreement under
                              the Section “Terms of Vehicle Usage” ensure the safekeeping and presence of the Vehicle
                              Documentation in the vehicle. If these documents are lost or stolen, the Guest will be
                              charged the cost of obtaining duplicates and be remitted to the Host along with all other
                              charges for damages and Lease Rental as payable to the Host</p>
                         <h3><strong>ACCIDENT,THEFT,TRAFFICVIOLATION AND CONFISCATION</strong></h3>
                         <p>All instances of accident, damage, theft, traffic violations and confiscation of or
                              involving the vehicle during the Lease Term shall be handled by the parties in accordance
                              with the provisions of the Lease Agreement, including alleged damage or other issues. The
                         </p>
                         <p>Hosts and the Guests further agree to honestly represent any claims or allegations of damage
                              and to work in good faith with each other to resolve any disagreement in keeping with the
                              terms of the Lease Agreement.</p>
                         <h3><strong>INSURANCE & DAMAGE PROTECTION</strong></h3>
                         <p>The Host shall maintain a minimum of third-party comprehensive insurance as mandated by
                              Motor Vehicles Act, 1988 for the vehicle with an insurance company of its choice (“Vehicle
                              Insurance”). The Guest shall be responsible for payment of all expenses associated with
                              any risks and ensuing damage to the vehicle including without limitation theft, partial or
                              total destruction etc. In doing so, the Guest shall be required to avail trip protection
                              plans/insurance through the Platform and shall be required to avail so at requisite fee
                              (Damage Protection Fee) over and above the Lease Rental. Guest acknowledges and agrees to
                              abide by the terms and conditions pertaining to the trip protection plan/insurance,
                              including without limitation its coverage, exclusions and process of invocation.</p>
                         <p>Shailesh Car & Bike PVT LTD shall assist the Host in filing and administering such claims
                              for damages, theft or loss of vehicle. Platform shall also assist the Guest in
                              administration of claims with the Host.</p>
                         <p>Both Host and the Guest acknowledge and agree that the information gathered through the
                              Booking Start/Pick-up Checklist and the Booking End/Drop Checklist is crucial to the
                              Damage Protection process. Should the Host or the Guest fail to fill in these</p>
                         <p>checklists, no claims of damage/repair etc. shall be entertained or administered in absence
                              of relevant proof collected through these checklists. The Guest shall not be allowed to
                              contest claims from the Host/claim refunds and the Host shall not be allowed to raise
                              claims in absence of such fully filled in checklists. In events of technical issues
                              preventing the filling of the checklist, the Host/Guest should immediately contact
                              customer support for resolution.</p>
                         <p>The Host understands and undertakes that he/she shall not act in a manner contrary or
                              prejudicial to the Platform or the Guest and extend his/her full cooperation and
                              participation at the time of any such claim being invoked under the trip protection
                              plan/insurance.</p>
                         <p>The Host also understands and agrees that in the event that the Host refuses, interferes,
                              prevents the administration of the claim in any manner or repossesses the vehicle which is
                              undergoing any maintenance/repair due to invocation of insurance, he/she shall forfeit any
                              rights to claim damages from the Guest/ insurance company as the case maybe. Neither
                              Shailesh Car & Bike PVT LTD nor the Guest will be liable to make good any damages in
                              such a situation and shall stand discharged of all liabilities therein.</p>
                         <p>The Guest shall not do or omit to do or be done or permit or suffer any act which might or
                              could prejudicially vitiate or affect any such damage protection plan and shall at all
                              times extend full cooperation so that the claims can be effectively administered.</p>
                         <p>The Host also understands and agrees that for the events including but not limited to the
                              below listed, the vehicle shall not be protected under any trip protection plan/insurance.
                              if: - The damage occurs when the vehicle is in possession of Host and/or occurs due to
                              deliberate/negligent acts of the Host itself. - Any damage arising due to normal wear and
                              tear of the vehicle or depreciation in quality or value of the vehicle as such including
                              but not limited to self-heating, electrical arcing or leakage etc. - Any specific
                              exclusions as may be listed by the insurance company in such a trip protection
                              plan/insurance.</p>
                         <p>In case of total loss of vehicle, the Host understands and agrees to bind themselves to the
                              depreciation level as prescribed under law or as prescribed by the relevant insurance
                              company in line with market practice.</p>
                         <p>The Guest also understands and agrees that certain damages/incidents as listed below are not
                              covered under such trip protection plans and the Guest will fully and personally be held
                              liable for all costs and damages.</p>
                         <p>The following shall not be covered under trip protection plan/insurance:</p>
                         <p>Any deliberate act of damaging the vehicle by the Guest or any of his/her co-driver Any
                              damage to the vehicle due to negligence or rash driving on part of the Guest.</p>
                         <p>The Guest was tested with alcohol in blood or breath or used drugs and or other stimulants
                              prohibited by the law The Guest used the vehicle in a manner that is in contravention of
                              law or the traffic regulations (over speeding, driving in restricted areas or any other
                              illegal usage for racing/commercial usage etc.).</p>
                         <p>In the event of any damage, theft, or destruction of the Vehicle during the Guest shall
                              promptly inform the Platform and render all documentation and information including but
                              not limited to information about the accident, assistance in filing of FIR or other
                              relevant details as maybe necessary to invoke a claim with the company providing the trip
                              protection plan/insurance with the assistance from the Platform.</p>
                         <p>Accordingly, the Guest shall pay to the Host, the amount of loss and/or damage not paid
                              under the trip protection plan/insurance and be liable for the following: In case of
                              Damage:</p>
                         <p>The difference, if any, between the actual amount incurred in repairing the damage to the
                              vehicle and the amounts recovered/to be recovered under the Vehicle Insurance.</p>
                         <p>In case of theft/total loss of the Vehicle:</p>
                         <p>The shortfall between the claim amount received under the trip protection plan/insurance,
                              and the book value of the vehicle at that time of its theft/total loss.</p>
                         <p>If usage of vehicle at the time of its theft/total loss exceeds the Agreed Mileage (defined
                              below), charge of the excess mileage incurred as per the rate specified in Fee Policy. For
                              Retired Vehicles, damage protection compensation is not applicable and hence no payout
                              shall be made for theft/ total loss of such Retired Vehicles. other cost/expense incurred
                              by the Host for/in respect of assessment loss suffered by the vehicle and possibility of
                              its restoration. other charges, if any, remaining unpaid by the Guest under the Lease
                              Agreement.</p>
                         <p>Notwithstanding any such additional trip protection plan/insurance availed, under no
                              circumstances shall Shailesh Car & Bike PVT LTD be held liable towards the parties or a
                              third party for any loss or damage that may be suffered by the parties or a third party,
                              whether or not the same may be attributed to parties.</p>
                         <h3><strong>VEHICLE RETURN / REPOSSESSION</strong></h3>
                         <p>Upon the expiry of the Lease Term or earlier termination of the Lease Agreement (except
                              termination on account of theft or total destruction/loss of the vehicle), Guest must at
                              his/her own cost return the vehicle in the almost the same order and condition, as the
                              Vehicle was at the time of commencement of the Lease Term, except normal wear and tear,
                              with Vehicle Documentation, vehicle’s key, key fob, in-vehicle devices and other starting
                              device in its designated position in the vehicle to the Specified Location within the
                              period specified in the Lease Agreement. The Guest is mandatorily required to fill up the
                              Booking</p>
                         <p>End/Drop Checklist for recording the car condition at the end of the trip. This will be
                              followed by filling of a similar Booking End/Drop Checklist by the Host as and when the
                              Host is returned the vehicle by the Guest. If, however, in case: The Guest returns the
                              vehicle at a place other than the Designated Location; the Guest will be charged the cost
                              of transportation of the vehicle from such place to the Designated Location. The Guest
                              does not return the Vehicle within the specified period, Guest will be charged late return
                              penalty specified in our Fees Policy till such time as the vehicle is returned to the Host
                              and also the costs, expenses, charges etc. incurred by the Host for repossession of the
                              vehicle. Damage caused to the returning vehicle, other than excepted wear and tear, the
                              Guest will be charged penalty for such damages at the rate specified in our Fees Policy
                              and approximate costs, expenses, charges for restoration of the vehicle to its original
                              condition. Any item provided with the vehicle is lost, including without limitation its
                              key, key fob, in-vehicle devices, other starting device to the vehicle or any component(s)
                              of the vehicle, Vehicle Documentation is missing, the Guest will be charged with (a) Lease
                              Rental (prorated on hourly basis) until the missing item is returned safely to the Host;
                              and (b) an inconvenience fee if the lost items are not returned and need to be replaced.
                              The actual usage of the vehicle by the Guest exceeds the Agreed Mileage, the Guest shall
                              pay the excess mileage charge as per the rate specified in our Fees Policy.</p>
                         <p>All such disputes shall be administered only by means of the information gathered through
                              Booking Start/Pickup Checklist and the Booking End/Drop Checklist as duly filled in by
                              both Host and the Guest. The Guest should ensure that these checklists are duly filled in
                              to avoid any hassles and additional penalties for damages caused.</p>
                         <h3><strong>WARRANTIES OF THE PARTIES</strong></h3>
                         <p>Hosts’ Warranties:</p>
                         <p>Each Host represents and warrants to Shailesh Car & Bike PVT LTD that:</p>
                         <p>Host is the sole legal, beneficial and registered owner of the vehicle(s) listed on the
                              Platform. The vehicle you offer for listing on the Platform is in sound and safe condition
                              and free of any known faults or defects that would affect its safe operation under normal
                              use and meets the vehicle eligibility criteria mentioned in this Agreement. Host has the
                              full legal right, capacity, power and authority to enter into and execute the Lease
                              Agreement, Agreement and General Policies, be contractually bound by and comply with all
                              rights and obligations contracted under each of these documents. There is no action,
                              investigation or other proceedings of any nature whatsoever, by any governmental authority
                              or third party against the Host, which would restrain, prohibit or otherwise challenge the
                              Lease, any listing of the vehicle on the Platform, Host’s posts on Platform and/or or a
                         </p>
                         <p>Guest's use of vehicle pursuant to the Lease Agreement.</p>
                         <p>Guests’ Warranties:</p>
                         <p>Each Guest represents and warrants that:</p>
                         <p>The Guest is above the legal driving age requirement and has a valid driving license for the
                              use and operation of the vehicle in accordance with requirements of applicable laws. The
                              Guest has the full legal right, capacity, power, and authority to enter into and execute
                              the Lease Agreement, this Agreement and the General Policies and be contractually bound by
                              and comply with all rights and obligations contracted under each of these documents. There
                              is no action, investigation, or other proceedings of any nature whatsoever, by any
                              governmental authority or third party against the Guest, which would restrain, prohibit,
                              or otherwise challenge the transaction as contemplated by the Lease Agreement.</p>
                         <p>WARRANTIES OF Shailesh Car & Bike PVT LTD</p>
                         <p>The Platform and Shailesh Car & Bike PVT LTD Host Services are provided to you “AS IS”. We
                              make no representations regarding the use of or the result of the use/depiction of the
                              contents on the Platform in terms of their correctness, accuracy, reliability, or
                              otherwise. Shailesh Car & Bike PVT LTD shall not be liable for any loss suffered in any
                              manner by the user as a result of depending directly or indirectly on the depiction of the
                              content on the Platform.</p>
                         <p>You acknowledge that the Platform is provided only on the basis set out in the General
                              Policies. Your uninterrupted access or use of the Platform and Shailesh Car & Bike PVT
                              LTD Host Services on this basis may be prevented by certain factors outside our reasonable
                              control including, without limitation, the unavailability, inoperability or interruption
                              of the internet or other telecommunications services or as a result of any maintenance or
                              other service work carried out on the Platform.</p>
                         <p>Shailesh Car & Bike PVT LTD shall have the right, at any time, to change or discontinue
                              any aspect or feature of the Platform, including, but not limited to, content, hours of
                              availability and equipment needed for access or use. Further, the Platform may discontinue
                              disseminating any portion of information or category of information. Shailesh Car & Bike
                              PVT LTD does not accept any responsibility and will not be liable for any loss or damage
                              whatsoever arising out of or in connection with any ability/inability to access or to use
                              the Platform.</p>
                         <p>The postings on the Platform or on social networking sites, including the Platform’s
                              Facebook page, or any information provided over chat or emails exchanged with Shailesh Car
                              And Bike PVT LTD, its employees or representatives (collectively referred to as
                              “Information”) which are in furtherance of any communication made by the user with
                              Shailesh Car & Bike PVT LTD, its employees or representatives is based on the background
                              provided by the user. While Shailesh Car & Bike PVT LTD takes reasonable care to ensure
                              that the Information is accurate, Shailesh Car & Bike</p>
                         <p>PVT LTD makes no representation and takes no responsibility for the accuracy, completeness,
                              appropriateness, or usefulness of the Information. In the event any user relies on the
                              Information provided by Shailesh Car & Bike PVT LTD or its representatives/ employees,
                              he/she may do so at its own risk. Under no circumstances will Shailesh Car & Bike PVT
                              LTD, its employees, representatives or affiliates be liable for the Information or the
                              consequences of relying on such Information.</p>
                         <h3><strong>USERS’ INDEMNITIES</strong></h3>
                         <p>During the subsistence of the Lease Agreement and/or this Agreement, both parties i.e., the
                              Hosts and the Guests shall at all times, indemnify, defend, hold harmless and keep
                              indemnified, Shailesh Car & Bike PVT LTD, its parent and affiliates and their respective
                              directors, officers, employees, shareholders, agents, attorneys, assigns and
                              successors-in-interest (“Shailesh Car & Bike PVT LTD Group”) against all losses,
                              liabilities, damages, injuries, claims, demands, costs, attorney fees and other expenses
                              arising out of or attributable to: any losses, costs, charges or expenses (including
                              between attorney and Guest and costs of litigation) or outgoings which Shailesh Car And
                              Bike PVT LTD shall certify as sustained or suffered or incurred by Shailesh Car & Bike
                              PVT LTD or any member of Shailesh Car & Bike PVT LTD Group as a consequence of
                              occurrence of default under the Lease Agreement, this Agreement and/or the General
                              Policies any loss, cost, charge, claim, damage, expense or liability that Shailesh Car And
                              Bike PVT LTD or any</p>
                         <p>member of Shailesh Car & Bike PVT LTD Group may suffer as a result of any representation
                              or warranty made by the parties in connection with the Lease Agreement, this Agreement
                              and/or the General Policies Agreement being found to be materially incorrect or
                              misleading. any losses, claims, damages, expenses, liability for any death, injury or
                              damage to any person or property that Shailesh Car & Bike PVT LTD or any member of
                              Shailesh Car & Bike PVT LTD Group may suffer/ incur arising directly or indirectly from
                              the listed vehicle or its use under the Lease Agreement, whether caused willfully/ or the
                              result of rash and negligent driving or any malicious act. any claim for breach of
                              intellectual property rights arising in connection with the Shailesh Car & Bike PVT LTD
                              Host Services and/or any other services provided by Shailesh Car & Bike PVT LTD or any
                              member of Shailesh Car & Bike PVT LTD Group. liability and costs incurred by Shailesh
                              Car & Bike PVT LTD group in connection with any claim arising out of your use of the
                              platform or otherwise relating to the business we conduct on the platform (including,
                              without limitation, any potential or actual communication, transaction or dispute between
                              you and any other user or third party), any content posted by you or on your behalf or
                              posted by other users of your account to the website, any use of any tool or service
                              provided by a third party provider, any use of a tool or service offered by us that
                              interacts with a third party website, including without limitation any social media site
                              or any breach by you of these terms or the representations, warranties and</p>
                         <p>covenants made by you herein, including without limitation legal fees and costs.</p>
                         <p>Each of the above indemnity is a separate and independent obligation and continues after
                              termination of this Agreement. The users also covenant to cooperate as fully as reasonably
                              required in the defense of any claim. Further, Shailesh Car & Bike PVT LTD hereby
                              reserves the right, at our own expense, to assume the exclusive defense and control of any
                              matter otherwise subject to indemnification by you and you shall not in any event settle
                              any matter without our written consent.</p>
                         <p>TERMINATION OF THIS AGREEMENT / Shailesh Car & Bike PVT LTD HOST SERVICES OR THE LEASE
                              AGREEMENT</p>
                         <p>This Agreement shall continue to apply and shall remain valid till the time the concerned
                              party continues to use Shailesh Car & Bike PVT LTD Services through its Platform or is
                              terminated by either you or Shailesh Car & Bike PVT LTD (“Term”).</p>
                         <p>If You want to terminate this Agreement, you may do so by (I) not accessing the Platform or
                              the Shailesh Car & Bike PVT LTD Services; or (ii) closing Your account on the Platform
                              for all of the listings or bookings of vehicles, as applicable, where such option is
                              available to You, as the case may be; or (iii) discontinuing any further use of the
                              Platform. Any such termination shall not cancel your obligation to pay for the Shailesh
                              Car & Bike PVT LTD Services and/or any other services already obtained from us and/ the
                              Platform or affect any liability that may have arisen under the Governing Policies.</p>
                         <p>Additionally, Shailesh Car & Bike PVT LTD shall have the sole discretion to suspend or
                              terminate this Agreement and discontinue Shailesh Car & Bike PVT LTD Services and/or
                              services provided by us (through the Platform or otherwise) by providing 30 (thirty) days’
                              prior notice to you. However, we may, at any time, with or without notice, suspend or
                              terminate this Agreement and Shailesh Car & Bike PVT LTD Services if: We are required to
                              do so by law (for example, where the provision of the Shailesh Car & Bike PVT LTD
                              Services to you is, or becomes, unlawful), or upon request by any law enforcement or other
                              government agencies. The provision of the Shailesh Car & Bike PVT LTD Services to you by
                              Shailesh Car & Bike PVT LTD is, in our sole discretion, no longer commercially viable to
                              us. The User fails to make any of the payments or part thereof or any other payment
                              required to be made to Shailesh Car & Bike PVT LTD hereunder and/or in respect of the
                              Shailesh Car & Bike PVT LTD Services, or any other service provided by Shailesh Car And
                              Bike PVT LTD when due and such failure continues for a period of 15 (fifteen) calendar
                              days after the due date of such payment. The User fails to perform or observe any other
                              covenant, conditions or agreement to be performed or observed by it under any of the
                              Governing Policies or in any other document furnished to Shailesh Car & Bike PVT LTD in
                              connection herewith. Termination of the listing or the booking on account of any
                              wrongdoing of either party and/or</p>
                         <p>violation of any terms, conditions and obligations of this Agreement and/or the Governing
                              Policies. The vehicle is being used for a Prohibited Use, as determined by us in our sole
                              discretion. Shailesh Car & Bike PVT LTD has elected to discontinue, with or without
                              reason, access to the Platform and/or the Shailesh Car & Bike PVT LTD Services (or any
                              part thereof). In the event Shailesh Car & Bike PVT LTD faces any unexpected technical
                              issues or problems that prevent the Platform, the Shailesh Car & Bike PVT LTD Services,
                              and/or any other services provided by Shailesh Car & Bike PVT LTD from working. Any
                              other similar unforeseen circumstances.</p>
                         <p>Termination of Lease Agreement by the Host/Guest:</p>
                         <p>Both the Host and the Guest may terminate the Lease Agreement as per the terms of the Lease
                              Agreement</p>
                         <p>Effects of Termination: In case of termination of this Agreement or completion of a booking,
                              in accordance with the terms hereunder and the Governing Policies:</p>
                         <p>the Guest shall promptly and without delay return the vehicle to the Host, as per the
                              vehicle return / Repossession terms mentioned herein. the Guest shall pay, the outstanding
                              Lease Rental (together with all late payment/charges thereon) and other unpaid
                              sums/charges/costs payable by the Guest under the Agreement and Governing Policies. The
                              Host shall pay any outstanding amounts due payable by the Host under the Agreement and
                              Governing Policies.</p>
                         <p>The Host shall upon termination make its vehicle available to Shailesh Car & Bike PVT LTD
                              for removal of the In-Vehicle Device. Upon the return of the vehicle, the Guest shall be
                              repaid the advance Lease Rental if any, paid by the Guest for the unexpired period of the
                              booking period to the Guest subject to adjustment against other outstanding payable of the
                              Guest for the booking made by him/her; Upon any termination of this Agreement either you
                              or Shailesh Car & Bike PVT LTD, you must promptly destroy all materials downloaded or
                              otherwise obtained from the Platform, as well as all copies of such materials, whether
                              made under the Governing Policies or otherwise.</p>
                         <h3><strong>RECOMMENDATION OF PLATFORM</strong></h3>
                         <p>Any recommendation made to you on the Platform during the course of your use of the Platform
                              is purely for informational purposes and for your convenience and does not amount to
                              endorsement of the Shailesh Car & Bike PVT LTD Host Services by Shailesh Car & Bike
                              PVT LTD or any of its associates in any manner.</p>
                         <h3><strong>USER CONTENT</strong></h3>
                         <p>The information, photo, image, chat communication, text, software, data, music, sound,
                              graphics, messages, videos or other materials transmitted, uploaded, posted, emailed or
                              otherwise made available to us (“User Content”), are entirely your responsibility and we
                              will not be held responsible, in any manner whatsoever, in connection to the User Content.
                              You</p>
                         <p>agree to not encourage or assist or engage others as well as yourself in transmitting,
                              hosting, displaying, uploading, modifying, publishing transmitting, updating or sharing
                              any information that:</p>
                         <p>belongs to another person and to which the user does not have any right to; is grossly
                              harmful, harassing, blasphemous defamatory, obscene, pornographic, pedophilic, libelous,
                              invasive of another’s privacy, hateful, or racially, ethnically objectionable,
                              disparaging, relating or encouraging money laundering or gambling, or otherwise unlawful
                              in any manner whatever; harms minors in any way; infringes any patent, trademark,
                              copyright or other proprietary rights; violates any law for the time being in force;
                              deceives or misleads the addressee about the origin of such messages or communicates any
                              information which is grossly offensive or menacing in nature; impersonate another person;
                              contains software viruses or any other computer cod, files or programs designed to
                              interrupt, destroy or limit the functionality of any computer resource; and/or threatens
                              the unity, integrity, defense, security or sovereignty of India, friendly relations with
                              foreign states, or public order or causes incitement to the commission of any cognizable
                              offense or prevents investigation of any offense or is insulting any other nation.</p>
                         <p>Shailesh Car & Bike PVT LTD shall in no way be held responsible for examining or
                              evaluating User Content, nor does it assume any responsibility or liability for the User
                              Content. Shailesh Car & Bike PVT LTD does not endorse or control the User Content
                              transmitted or posted on the Platform by you and therefore, accuracy, integrity or quality
                              of User Content is not guaranteed by Shailesh Car & Bike PVT LTD. You understand that by
                              using the Platform, you may be exposed to User Content that is offensive, indecent or
                              objectionable to you. Under no circumstances will Shailesh Car & Bike PVT LTD be liable
                              in any way for any User Content, including without limitation, for any errors or omissions
                              in any User Content, or for any loss or damage of any kind incurred by you as a result of
                              the use of any User Content transmitted, uploaded, posted, e-mailed or otherwise made
                              available via the Platform. You hereby waive all rights to any claims against Shailesh Car
                              And Bike PVT LTD for any alleged or actual infringements of any proprietary rights, rights
                              of privacy and publicity, moral rights, and rights of attribution in connection with User
                              Content.</p>
                         <p>You hereby acknowledge that Shailesh Car & Bike PVT LTD has the right (but not the
                              obligation) in its sole discretion to refuse to post or remove any User Content and
                              further reserves the right to change, condense, or delete any User Content. Without
                              limiting the generality of the foregoing or any other provision of these Terms and
                              Conditions, Shailesh Car & Bike PVT LTD has the right to remove any User Content that
                              violates these Terms and Conditions or is otherwise objectionable and further reserves the
                              right to refuse service and/or terminate accounts without prior notice</p>
                         <p>for any users who violate these Terms and Conditions or infringe the rights of others.</p>
                         <p>If you wish to delete your User Content on our Platform, please contact us by email at and
                              request you to include the following personal information in your deletion request: first
                              name, last name, user name/screen name (if applicable), email address associated with our
                              Platform, your reason for deleting the posting, and date(s) of posting(s) you wish to
                              delete (if you have it). We may not be able to process your deletion request if you are
                              unable to provide such information to us. Please allow up to 30 business days to process
                              your deletion request.</p>
                         <h3><strong>INTELLECTUAL PROPERTY RIGHTS</strong></h3>
                         <p>The “Shailesh Car & Bike PVT LTD” name and logo and all related product and service names,
                              design marks and slogans are the trademarks, logos or service marks (hereinafter referred
                              to as “Marks”) of Shailesh Car & Bike PVT LTD India Private Limited. All other Marks
                              provided on the Platform are the property of their respective companies. No trademark or
                              service mark license is granted in connection with the materials contained on this
                              Platform. Access to the Platform does not authorize anyone to use any Marks in any manner.
                              Marks displayed on the Platform, whether registered or unregistered, of Shailesh Car And
                              Bike PVT LTD or others, are the intellectual property of their respective owners, and
                              Shailesh Car & Bike PVT LTD shall not be held liable in any manner whatsoever for any
                              unlawful, unauthorized use of the Marks.</p>
                         <p>Shailesh Car & Bike PVT LTD and its suppliers and licensors expressly reserve all the
                              intellectual property rights in all text, programs, products, processes, technology,
                              content, software and other materials, which appear on the Platform, including its looks
                              and feel. The compilation (meaning the collection, arrangement and assembly) of the
                              content on the Platform is the exclusive property of Shailesh Car & Bike PVT LTD and are
                              protected by the Indian copyright laws and International treaties. Consequently, the
                              materials on the Platform shall not be copied, reproduced, duplicated, republished,
                              downloaded, posted, transmitted, distributed or modified in whole or in part or in any
                              other form whatsoever, except for your personal, non-commercial use only. No right, title
                              or interest in any downloaded materials or software is transferred to you as a result of
                              any such downloading or copying, reproducing, duplicating, republishing, posting,
                              transmitting, distributing or modifying.</p>
                         <p>All materials, including images, text, illustrations, designs, icons, photographs, programs,
                              music clips, downloads, video clips and written and other materials that are part of the
                              Platform (collectively, the “Contents”) are intended solely for personal, non-commercial
                              use. You may download or copy the Contents and other downloadable materials displayed on
                              the Platform for your personal use only. We also grant you a limited, revocable,
                              non-transferable, and non-exclusive license to create a hyperlink to the homepage of the
                              Platform for personal, non-commercial use only. Any other use, including the reproduction,
                              modification, distribution, transmission, re-publication, display, or performance, of the
                              Contents on the</p>
                         <p>Platform is strictly prohibited. Unless Shailesh Car & Bike PVT LTD explicitly provides to
                              the contrary, all Contents are copyrighted, trademarked, trade dressed and/or other
                              intellectual property owned, controlled or licensed by Shailesh Car & Bike PVT LTD, any
                              of its affiliates or by third parties who have licensed their materials to Shailesh Car
                              And Bike PVT LTD and are protected by Indian copyright laws and international treaties.
                         </p>
                         <h3><strong>DISCLAIMEROF WARRANTY AND LIMITATION OF LIABILITY</strong></h3>
                         <p>PLEASE NOTE THAT Shailesh Car & Bike PVT LTD HOST SERVICES ARE INTENDED TO BE USED TO
                              FACILITATE THE LEASING OF VEHICLE BY THE HOST AND TO THE Guest. Shailesh Car & Bike PVT
                              LTD CANNOT AND DOES NOT CONTROL THE CONTENT IN ANY LISTINGS AND THE CONDITION, LEGALITY OR
                              SUITABILITY OF ANY VEHICLE LISTED ON THE PLATFORM. Shailesh Car & Bike PVT LTD IS NOT
                              RESPONSIBLE FOR, AND DISCLAIMS ANY AND ALL LIABILITY RELATED TO, ANY AND ALL LISTINGS AND
                              VEHICLE. ANY LEASING OF THE LISTED VEHICLE UNDER THE LEASE AGREEMENT OR OTHERWISE WILL BE
                              DONE ENTIRELY AT THE GUEST’S AND HOST’S OWN RISK.FURTHER Shailesh Car & Bike PVT LTD
                              SHALL NOT BE LIABLE TOWARDS THE LOSSES, DAMAGES, COSTS INCURRED BY THE HOST OR THE GUEST
                              IN ABSENCE OF THE DULY FILLED IN BOOKING START/PICKUP CHECKLIST AND THE BOOKING END/DROP
                              CHECKLIST. Shailesh Car & Bike PVT LTD SHALL ALSO NOT BE RESPONSIBLE FOR ANY TOTAL
                              LOSS/THEFT CLAIMS UNDER DAMAGE PROTECTION PLAN FOR RETIRED VEHICLES.</p>
                         <h3><strong>THE PLATFORM IS PRESENTED “AS IS”. NEITHER WE NOR OUR HOLDING, SUBSIDIARIES,
                                   AFFILIATES, PARTNERS OR</strong></h3>
                         <h3><strong>LICENSORS MAKE ANY REPRESENTATIONS OR WARRANTIES OF ANY KIND WHATSOEVER, EXPRESS OR
                                   IMPLIED, IN CONNECTION WITH THESE TERMS AND CONDITIONS OR THE PLATFORM OR ANY OF THE
                                   CONTENT, EXCEPT TO THE EXTENT SUCH REPRESENTATIONS AND WARRANTIES ARE NOT LEGALLY
                                   EXCLUDABLE.</strong></h3>
                         <p>YOU AGREE THAT, TO THE FULLEST EXTENT PERMITTED BY APPLICABLE LAW, NEITHER WE NOR OUR
                              HOLDING, SUBSIDIARIES, AFFILIATES, PARTNERS, OR LICENSORS WILL BE RESPONSIBLE OR LIABLE
                              (WHETHER IN CONTRACT, TORT (INCLUDING NEGLIGENCE) OR OTHERWISE) UNDER ANY CIRCUMSTANCES
                              FOR ANY (a) INTERRUPTION OF BUSINESS; (b) ACCESS DELAYS OR ACCESS INTERRUPTIONS TO THE
                              PLATFORM; (c) DATA NON-DELIVERY, LOSS, THEFT, MISDELIVERY, CORRUPTION, DESTRUCTION OR
                              OTHER MODIFICATION; (d) LOSS OR DAMAGES OF ANY SORT INCURRED AS A RESULT OF DEALINGS WITH
                              OR THE PRESENCE OF OFF-WEBSITE LINKS ON THE PLATFORM; (e) VIRUSES, SYSTEM FAILURES OR
                              MALFUNCTIONS WHICH MAY OCCUR IN CONNECTION WITH YOUR USE OF THE PLATFORM, INCLUDING DURING
                              HYPERLINK TO OR FROM THIRD PARTY WEBSITES; (f) ANY INACCURACIES OR OMISSIONS IN CONTENT;
                              OR (g) EVENTS BEYOND THE REASONABLE CONTROL OF Shailesh Car & Bike PVT LTD. WE MAKE NO
                              REPRESENTATIONS OR WARRANTIES THAT DEFECTS OR ERRORS WILL BE CORRECTED.</p>
                         <h3><strong>FURTHER, TO THE FULLEST EXTENT PERMITTED BY LAW, NEITHER WE NOR OUR SUBSIDIARIES,
                                   AFFILIATES, PARTNERS, OR LICENSORS WILL BE LIABLE FOR ANY INDIRECT, SPECIAL,
                                   PUNITIVE, INCIDENTAL, OR CONSEQUENTIAL DAMAGES OF ANY KIND (INCLUDING LOST PROFITS)
                                   RELATED TO THE PLATFORM OR YOUR USE THEREOF REGARDLESS OF THE FORM OF ACTION WHETHER
                                   IN CONTRACT, TORT (INCLUDING NEGLIGENCE) OR OTHERWISE, EVEN IF WE HAVE BEEN ADVISED
                                   OF THE</strong></h3>
                         <h3><strong>POSSIBILITY OF SUCH DAMAGES AND IN NO EVENT SHALL OUR MAXIMUM AGGREGATE LIABILITY
                                   EXCEED RUPEES 50,000/-.</strong></h3>
                         <p>Shailesh Car & Bike PVT LTD MAKES NO CLAIM WITH RESPECT TO THE EFFICACY OF THE METHODOLOGY
                              AND THE OUTCOME OF THE PRODUCTS AND SERVICES MAY VARY FROM USER TO USER. THE USER USES THE
                              PRODUCT AND SERVICES AT THEIR OWN RISK.</p>
                         <h3><strong>YOU AGREE THAT NO CLAIMS OR ACTION ARISING OUT OF, OR RELATED TO, THE USE OF THE
                                   PLATFORM OR THESE TERMS AND CONDITIONS MAY BE BROUGHT BY YOU MORE THAN ONE (1) YEAR
                                   AFTER THE CAUSE OF ACTION RELATING TO SUCH CLAIM OR ACTION AROSE. IF YOU HAVE A
                                   DISPUTE WITH US OR ARE DISSATISFIED WITH THE PLATFORM, TERMINATION OF YOUR USE OF THE
                                   PLATFORM IS YOUR SOLE REMEDY. WE HAVE NO OTHER OBLIGATION, LIABILITY, OR
                                   RESPONSIBILITY TO YOU.</strong></h3>
                         <p>THIS LIMITATION OF LIABILITY REFLECTS AN INFORMED, VOLUNTARY ALLOCATION BETWEEN Shailesh Car
                              And Bike PVT LTD AND THE USERS OF THE RISKS (KNOWN AND UNKNOWN) THAT MAY EXIST IN
                              CONNECTION WITH THIS AGREEMENT AND/OR Shailesh Car & Bike PVT LTD HOST SERVICES AND/OR
                              ADD-ON SERVICES AND/OR ANY OTHER SERVICES PROVIDED BY Shailesh Car & Bike PVT LTD
                              THROUGH THE PLATFORM OR OTHERWISE. THE TERMS OF THIS CLAUSE SHALL SURVIVE ANY TERMINATION
                              OR EXPIRATION OF THIS AGREEMENT.</p>
                         <h3><strong>LINKS AND THIRD-PARTY SITES</strong></h3>
                         <p>References on the Platform to any names, marks, products or services of third parties or
                              hypertext links to third party sites or information are provided solely as a convenience
                              to you. This does not in any way constitute or imply Shailesh Car & Bike PVT LTD</p>
                         <p>endorsement, sponsorship or recommendation of the third party, information, product or
                              service or any association and relationship between Shailesh Car & Bike PVT LTD and
                              those third parties.</p>
                         <p>Shailesh Car & Bike PVT LTD is not responsible for the content of any third-party websites
                              and does not make any representations regarding the content or accuracy of material on
                              such sites. If you decide to link to any such third-party websites, you do so entirely at
                              your own risk. Shailesh Car & Bike PVT LTD does not assume any responsibility for
                              examining or evaluating the offerings of the off-websites pages or any other websites
                              linked from the Platform. We shall not be responsible for the actions, content, products,
                              or services of such pages and websites, including, without limitation, their privacy
                              policies and terms and conditions. You should carefully review the terms and conditions
                              and privacy policies of all off-website pages and other websites that you visit via the
                              Platform.</p>
                         <h3><strong>GOVERNING LAW AND JURISDICTION</strong></h3>
                         <p>This Agreement shall be construed in accordance with the applicable laws of India. For
                              proceedings arising therein the Courts at Jamnagar (Gujarat) shall have exclusive
                              jurisdiction.</p>
                         <p>Any dispute or difference either in interpretation or otherwise, of this Agreement and/or
                              the General Policies, shall be referred to an independent arbitrator who will be appointed
                              by Shailesh Car & Bike PVT LTD and his decision shall be final and binding on the
                              parties hereto. The above arbitration</p>
                         <p>shall be in accordance with the Arbitration and Conciliation Act, 1996 as amended from time
                              to time. The seat of arbitration shall be held in Jamnagar (Gujarat).</p>
                         <p>Without any prejudice to particulars listed in clause above, Shailesh Car & Bike PVT LTD
                              shall have the right to seek and obtain any injunctive, provisional or interim relief from
                              any court of competent jurisdiction to protect its Marks or other</p>
                         <h3><strong>PLATFORM SECURITY</strong></h3>
                         <p>You are prohibited from violating or attempting to violate the security of the Platform,
                              including, without limitation,</p>
                         <p>accessing data not intended for you or logging onto a server or an account which you are not
                              authorized to access; attempting to probe, scan or test the vulnerability of a system or
                              network or to breach security or authentication measures without proper authorization;
                              attempting to interfere with service to any other user, host or network, including,
                              without limitation, via means of submitting a virus to the Site, overloading, “flooding,”
                              “spamming,” “mail bombing” or “crashing;” sending unsolicited email, including promotions
                              and/or advertising of products or services; or forging any header or any part of the
                              header information in any email or newsgroup posting.</p>
                         <p>Violations of system or network security may result in civil or criminal liability</p>
                         <p>Shailesh Car & Bike PVT LTD is entitled to investigate occurrences that may involve such
                              violations and may involve, and cooperate with, law enforcement authorities in prosecuting
                              users who are involved in such violations. You agree not to use any device, software or
                              routine to interfere or attempt to interfere with the proper working of the Platform or
                              any activity being conducted on the Platform. You agree, further, not to use or attempt to
                              use any engine, software, tool, agent or other device or mechanism (including without
                              limitation browsers, spiders, robots, avatars or intelligent agents) to navigate or search
                              this Site other than the search engine and search agents available from Shailesh Car And
                              Bike PVT LTD on the Platform and other than generally available third party web browsers
                              (e.g., Netscape Navigator, Microsoft Explorer)</p>
                         <h3><strong>SEVERABILITY</strong></h3>
                         <p>If any part of this Agreement is determined to be invalid or unenforceable pursuant to
                              applicable law including or be so held by any applicable arbitral award or court decision,
                              but not limited to, the warranty disclaimers and liability limitations set forth above,
                              then such unenforceability or invalidity shall not render the Agreement unenforceable or
                              invalid as a whole but invalid or unenforceable provision will be deemed to be superseded
                              by a valid, enforceable provision that most closely matches the intent of the original
                              provision and the remainder of the Agreement shall continue to be in effect.</p>
                         <h3><strong>ENTIRE AGREEMENT</strong></h3>
                         <p>Unless otherwise specified herein, the General Policies constitutes the entire agreement
                              between you and Shailesh Car & Bike PVT LTD with respect to the Platform and the
                              Shailesh Car & Bike PVT LTD Host Services and it supersedes all prior or contemporaneous
                              communications and proposals, whether electronic, oral or written.</p>
                         <h3><strong>CORRESPONDENCE ADDRESS/ NOTICES</strong></h3>
                         <p>Unless specifically provided otherwise, any notice or demands required to be given herein
                              shall be given to the parties hereto in writing and by either Registered Post Acknowledged
                              Due, e-mail or by hand delivery at the addresses as mentioned below:</p>
                         <p>Shailesh Car & Bike PVT LTD India Shop no.5 Dwarkesh Complex, below Shivhari hotel, near
                              smarpan overbridge, Jamnagar 361006 Email: info@velriders Contact No: 9909927077
                              Communication generated by Shailesh Car & Bike PVT LTD on the users’ mobile number will
                              be deemed adequate service of notice / electronic record to the maximum extent permitted
                              under any applicable law.</p>
                         <h3><strong>WAIVER</strong></h3>
                         <p>Our failure to require your performance of any provision hereof shall not affect our full
                              right to require such performance at any time thereafter, nor shall our waiver of a breach
                              of any provision hereof be taken or held to be a waiver of the provision itself.</p>
                         <h3><strong>ASSIGNMENT</strong></h3>
                         <p>The users shall not be entitled to assign (in whole or in part) this Agreement or any of
                              their rights or obligations hereunder, without prior written consent of Shailesh Car And
                              Bike PVT LTD, which consent may be given at Shailesh Car & Bike PVT LTD’s own
                              discretion. Shailesh Car & Bike PVT LTD shall have the right to assign (in whole or in
                              part) this Agreement, or obligations of Shailesh Car & Bike PVT LTD. In such an event,
                              the users shall perform their respective obligations under or pursuant to this Agreement
                              qua such assignee.</p>
                         <h3><strong>FORCE MAJEURE</strong></h3>
                         <p>This Agreement and its performance by Shailesh Car & Bike PVT LTD or the users shall be
                              subject to force majeure. If performance of any service or obligation under the terms and
                              conditions of the General Policies, including this Agreement or other third parties in
                              fulfillment of transaction (for e.g. home deliveries of vehicles, payment gateways etc.)
                              are, prevented, restricted, delayed or interfered with by reason of labor disputes,
                              strikes, acts of God, floods, lightning, severe weather, shortages of materials,
                              rationing, utility or communication failures,</p>
                         <p>earthquakes, war, revolution, acts of terrorism, civil commotion, acts of public enemies,
                              blockade, pandemic, epidemic, lockdown, embargo or any law, order, proclamation,
                              regulation, ordinance, demand or requirement having legal effect of any government or any
                              judicial authority or representative of any such government, or any other act whatsoever,
                              whether similar or dissimilar to those referred to in this Clause, which are beyond the
                              reasonable commercial control of Shailesh Car & Bike PVT LTD or its third parties
                              performing such services as sub-contractor to Shailesh Car & Bike PVT LTD and could not
                              have been prevented by reasonable precautions (each, a "Force Majeure Event"), then
                              Shailesh Car & Bike PVT LTD shall be excused from such performance to the extent of and
                              during the period of such Force Majeure Event. For the avoidance of doubt, a Force Majeure
                              Event shall exclude any event that a party could reasonably have prevented by testing,
                              work-around, or other exercise of diligence. If the period of non-performance exceeds 60
                              days from the receipt of written notice of the Force Majeure Event, either Shailesh Car
                              And Bike PVT LTD or the user may by giving written notice terminate the Agreement</p>
                         <h3><strong>GENERAL</strong></h3>
                         <p>Nothing contained in this Agreement and/or General Policies shall be construed as creating
                              any agency, partnership, or other form of joint enterprise between Shailesh Car & Bike
                              PVT LTD and the users</p>
                         <p>If you have any questions regarding this Agreement, please email us at <a href="mailto:info@velriders.com">info@velriders.com</a></p>
                         @endif
                    </div>
               </div>
          </div>
     </section>
     @else
     <section>
          <div class="container">
               <div class="text-center">
                    <h1>Terms & Conditions</h1><br>
                    <p class="lead">Read Terms & Conditions</p>
               </div>
          </div>
     </section>
     <section class="section-background">
          <div class="container">
               <div class="about-info">
                    <div class="container mt-5 mb-5">
                         <p> Welcome to Shailesh Car & Bike PVT LTD AKA VELRIDERS located at www.velriders.com (the
                              “Site”) and the mobile application (the “App”).
                              The Site and App(each the “Platform”) are owned and operated by Shailesh Car & Bike PVT
                              LTD India, a company incorporated under the Companies Act 1956, having its registered
                              office at Ground Floor, Shop No-5, Dwarkesh Complex, Near Samarpan Over Bridge, Below Shiv
                              Hari Hotel,Jamnagar,Gujarat,361008.(also referred to as “Shailesh Car & Bike PVT
                              LTD”,“we,” “us,” or “our’ ‘All access and use of the Platform and the services thereon are
                              governed by our general Platform terms,(the “General Terms''), privacy policy available at
                              website.</p>
                         <p>These Terms of Service, including specific terms and conditions applicable to the Hosts and
                              Guests and Add-on Services (this “Agreement”/ “Host T&C”) read together with the Privacy
                              Policy, Fee Policy and other applicable policies (“Governing Policies”), collectively
                              create the legally binding terms and conditions on which Shailesh Car & Bike PVT LTD
                              offers to you or the entity you represent (“you”, “User” or “your”) the Shailesh Car And
                              Bike PVT LTD Host Services (defined below), including your access and use of Shailesh Car
                              And Bike PVT LTD Host Services.</p>
                         <p>Please read each of Governing Policies carefully to ensure that you understand each
                              provision and before using or registering on the website or accessing any material,
                              information or availing services through the Platform. If you do not agree to any of its
                              terms, please do not use the Platform or avail any services through the Platform. The
                              Governing Policies take effect when you click an “I Agree” button or checkbox presented
                              with these terms or, if earlier, when you use any of the services offered on the Platform
                              (the “Effective Date”). To serve you better, our Platform is continuously evolving, and we
                              may change or discontinue all or any part of the Platform, at any time and without notice,
                              at our sole discretion.</p>
                         <h3><strong>PRIVACY PRACTICES</strong></h3>
                         <p>portance of safeguarding your personal information and we have formulated a Privacy Policy
                              shaileshcar&bike@gmail.com to ensure that your personal information is sufficiently
                              protected. We encourage you to read it to better understand how you can update and manage
                              your information on the Platform.</p>
                         <h3><strong>AMENDMENTS/MODIFICATIONS</strong></h3>
                         <p>Shailesh Car & Bike PVT LTD reserves the right to change the particulars contained in the
                              Agreement from time to time and at any time. If Shailesh Car & Bike PVT LTD decides to
                              make changes to the Agreement, it will post the new version on the website and update the
                              date specified above or communicate the same to you by</p>
                         <p>other means. Any change or modification to the Agreement will be effective immediately from
                              the date of upload of the Agreement on the Platform. It is pertinent that you review the
                              Agreement whenever we modify them and keep yourself updated about the latest terms of
                              Agreement because if you continue to use the Shailesh Car & Bike PVT LTD Host Services
                              after we have posted a modified Agreement, you are indicating to us that you agree to be
                              bound by the modified Agreement. If you don’t agree to be bound by the modified terms of
                              the Agreement, then you may not use the Shailesh Car & Bike PVT LTD Host Services
                              anymore</p>
                         <p>Shailesh Car & Bike PVT LTD HOST SERVICES</p>
                         <p>Shailesh Car & Bike PVT LTD Host Services is a marketplace feature of the Platform more
                              particularly described below. That helps owners of vehicles (“Hosts”/ “Lessors”)connect
                              with users in temporary need of a vehicle on leasehold basis (“Guest”) for their personal
                              use(“Shailesh Car & Bike PVT LTD Host Services”).Shailesh Car & Bike PVT LTD does not
                              itself lease or deal with such vehicles in any manner whatsoever and only provides a
                              service connecting the Hosts to the Guests so they may enter into a Lease Agreement
                              (defined below).You understand and agree that Shailesh Car & Bike PVT LTD is not a party
                              to the Lease Agreement entered into between you as the Host of the vehicle or you as the
                              Guest of the vehicle, nor is Shailesh Car & Bike PVT LTD a transportation service,
                              agent, or insurer. Shailesh Car & Bike PVT LTD has no control over the conduct of the
                              Users of the Shailesh Car & Bike PVT LTD Host Services and disclaims all liability in
                              this regard.</p>
                         <p>Shailesh Car & Bike PVT LTD Host Services aims to establish and provide a robust
                              marketplace of reliable Hosts and Guests. Although Shailesh Car & Bike PVT LTD Host
                              Services provides support for the transaction between Hosts and Guests, we do guarantee
                              the quality or safety of the vehicles listed on the Platform.</p>
                         <h3><strong>SERVICES INFORMATION</strong></h3>
                         <p>Shailesh Car & Bike PVT LTD Host Services comprises of (a) the marketplace features
                              enables Hosts and Guests satisfying the applicable eligibility criteria listed below to
                              connect with one another for leasing of vehicle for personal use; and (b)
                              support/facilitation services for leasing including, among others, assistance with
                              execution of the lease agreement, payment facilitation, vehicle cleaning/sanitization,
                              vehicle delivery, on-road assistance, prospective Guest diligence and vehicle
                              usage/location tracking (“Add-on Services”);and (iii) web widgets, feeds, mobile device
                              software applications, applications for third-party web sites and services, and any other
                              mobile or online services and/or applications owned, controlled, or offered by Shailesh
                              Car & Bike PVT LTD. Shailesh Car & Bike PVT LTD attempts to be as accurate as possible
                              in the description of the Shailesh Car & Bike PVT LTD Host Services. However, Shailesh
                              Car & Bike PVT LTD does not warrant that the Shailesh Car & Bike PVT LTD Host
                              Services, information or other content of the Platform is accurate, complete,</p>
                         <p>reliable, current or error-free. The Platform may contain typographical errors or
                              inaccuracies and may not be complete or current</p>
                         <p>Shailesh Car & Bike PVT LTD reserves the right to correct, change or update information,
                              errors, inaccuracies, subjective conclusions, interpretations, views, opinions or even
                              human error, or omissions at any time (including after an order has been submitted)
                              without prior notice. Please note that such errors, inaccuracies, or omissions may also
                              relate to availability and Shailesh Car & Bike PVT LTD Host Services. The user of the
                              Shailesh Car & Bike PVT LTD Host Services shall not hold Shailesh Car & Bike PVT LTD
                              liable for any loss or damage relating to the same.</p>
                         <p>USE Of Shailesh Car & Bike PVT LTD HOST SERVICES</p>
                         <p>While you may use some sections/features of the Platform without registering with us, to
                              access the Shailesh Car & Bike PVT LTD Host Services you will be required to register
                              and create an account with us. Thereafter, only the Hosts and Guests satisfying the
                              applicable eligibility criteria (listed below) will be able to use the services subject to
                              the terms and conditions of this Agreement.</p>
                         <h3><strong>ELIGIBILITY</strong></h3>
                         <p>The Shailesh Car & Bike PVT LTD Host Services are intended solely for users who are 18
                              years or older and satisfy user specific criteria below. Any use of the Shailesh Car And
                              Bike PVT LTD Host Services by anyone</p>
                         <p>that does not meet these requirements is expressly prohibited. Host/Vehicle Eligibility
                              Criteria</p>
                         <p>The Host must have a valid passport, Aadhar number and/or other form of government issued
                              identification document.</p>
                         <p>The vehicle(s) proposed to be listed must be an eligible non-transport or private personal
                              use vehicle registered solely in your name. At the time of listing the vehicle(s) being
                              listed should also not have any pending insurance claims and/or other on-going
                              litigations, legal claims or any other claims that may arise in tort or law.</p>
                         <p>Your vehicle must be less than 10 years old and should meet all legal requirements of the
                              state of its registration and usage</p>
                         <p>Your vehicle must be clean, well maintained and have the basic accessories, including safety
                              device as per our maintenance, component and safety standards/equipment specifications
                              attached hereto as Annexure I.</p>
                         <p>You must abide by our exclusivity policy, which mandates that vehicles you list on Platform
                              must be</p>
                         <p>exclusively shared on the Platform and can’t appear on another car sharing/leasing platform.
                         </p>
                         <p>Your vehicle must meet our minimum insurance requirements of having Third Party
                              Comprehensive Insurance as is mandated under Motor Vehicle Act, 1988</p>
                         <p>Your vehicle must have fewer than 70000 kilometers and have never been declared a total loss
                         </p>
                         <p>You must have fitment of the In-Vehicle Devices in your vehicle to ensure safety and
                              tracking of the vehicle.</p>
                         <p>Guest Eligibility Criteria</p>
                         <p>The Guest must have a valid driving license issued by appropriate authority under Government
                              of India. The Guest must have valid passport, Aadhar number and/or other form of
                              government issued identification document</p>
                         <p>The Guest must have no recent vehicle accidents in the last year, major traffic violations
                              in the last 1 year, more than 2 recent moving violations and history of non-payment of
                              failure to pay</p>
                         <p>The Guest must have a clean criminal record, including but not limited to no felony(s), no
                         </p>
                         <p>violent crime(s), theft(s) or offense related to prohibited substance(s).</p>
                         <h3><strong>REGISTERING AND CREATING YOUR ACCOUNT</strong></h3>
                         <p>To access and use the Shailesh Car & Bike PVT LTD Host Services, you shall have to open an
                              account on the Platform with a valid email address by providing certain complete and
                              accurate information and documentation including but not limited to your name, date of
                              birth, an email address and password, and other identifying information as may be
                              necessary to open the account on the Platform. Each user may open and maintain only one
                              account on the Platform</p>
                         <p>Please see below an indicative list of documents that you will be required to submit as part
                              of the registration process on the Platform. Shailesh Car & Bike PVT LTD may on a need
                              basis request submission of additional documents as well, as it may deem necessary for
                              facilitation of Shailesh Car & Bike PVT LTD Host Services.</p>
                         <p>For Hosts</p>
                         <p>Registration Certificate.</p>
                         <p>Pollution Under Check Certificate.</p>
                         <p>Car Insurance.</p>
                         <p>Current Address Proof. (Rent Agreement/Company Allotment Letter etc.)</p>
                         <p>Valid Government ID Card (Aadhar, Voter’s ID, Passport etc.)</p>
                         <p>PAN Card For Guest: Valid Driver’s License.</p>
                         <p>Valid Government ID Card (Aadhar, Voter’s ID, Passport etc.) Canceled Cheque in name of the
                              Host 4.Current Address Proof. (Rent Agreement/Company Allotment Letter etc.)</p>
                         <p>Once you have created an account with us, you are responsible for maintaining the
                              confidentiality of your username, password, and other information used to register and
                              sign into our Platform, and you are fully responsible for all activities that occur under
                              this username and password. Please immediately notify us of any unauthorized use of your
                              account or any other breach of security by contacting us at If you interact with us or
                              with third-party service providers, you agree that all information that you provide will
                              be accurate, complete, and current. You acknowledge that the information you provide, in
                              any manner whatsoever, are not confidential or proprietary and does not infringe any
                              rights of a third party.</p>
                         <p>By registering on the Platform, each applicant i.e. The Host and the Guest authorizes
                              Shailesh Car & Bike PVT</p>
                         <p>LTD and Shailesh Car & Bike PVT LTD reserves the right, in its sole discretion, to verify
                              the documents submitted by such applicants through the Platform. Shailesh Car & Bike PVT
                              LTD may in its sole discretion use third-party services to verify the information you
                              provide to us and to obtain additional related information and corrections where
                              applicable, and you hereby authorize Shailesh Car & Bike PVT LTD to request, receive,
                              use, and store such information in accordance with our Privacy Policy. Further, Shailesh
                              Car & Bike PVT LTD reserves the right, at its sole discretion, to suspend or terminate
                              the Shailesh Car & Bike PVT LTD Services to any of the registered users while their
                              account is still active for any reason whatsoever. Shailesh Car & Bike PVT LTD may
                              provide any information necessary to the Hosts, insurance companies, or law enforcement
                              authorities to assist in the filing of a stolen car claim, insurance claim, vehicle
                              repossession, or legal action.</p>
                         <p>EACH HOST AND GUEST ACKNOWLEDGES AND AGREES THAT NEITHER Shailesh Car & Bike PVT LTD NOR
                              ANY OF ITS AFFILIATES WILL HAVE ANY LIABILITY TOWARDS ANY: (1) USER FOR ANY UNAUTHORIZED
                              TRANSACTION MADE USING ANY USERNAME OR PASSWORD; (2) PERSONAL BELONGINGS WHICH IS CLAIMED
                              BY GUEST TO BE LOST OR STOLEN ONCE THE BOOKING PERIOD ENDS; AND (3) THE UNAUTHORIZED USE
                              OF YOUR USERNAME AND PASSWORD FOR YOUR PLATFORM ACCOUNT COULD CAUSE YOU TO INCUR LIABILITY
                              TO BOTH Shailesh Car & Bike PVT LTD AND OTHER USERS.</p>
                         <h3><strong>ONBOARDING VEHICLE & LISTING BY THE HOST</strong></h3>
                         <p>Once the user account is created, Hosts can onboard and list their vehicle(s) on the
                              Platform for leasing</p>
                         <p>by following the single steps available on the platform</p>
                         <p>Host can hide its vehicle from platform on its requirement if booking for the same is not
                              confirmed.</p>
                         <p>Listing can be created from the Platform at least 1 hour in advance. Host shall ensure the
                              availability of the vehicle at the Designated Location for bookings during a Listing. Each
                              Listing Period shall be for a minimum of 4 hours and a maximum period of 6 months.</p>
                         <p>Cancellation/Rescheduling of a Listing: Host will not have right to cancel or reschedule the
                              booking. Except in accidental case submitted on the platform with proof. Charges, as
                              stipulated in the Fee Policy shall be applicable on cancellation or rescheduling a Listing
                              under certain conditions. However, in case where there are multiple cancellations in Guest
                              booking/s due to Host/a misdemeanor or unwarranted cancellations by the Host himself,
                              Shailesh Car & Bike PVT LTD at its sole discretion, shall have the right to terminate
                              Host from its platform and delist any/all vehicles listed on the Platform by such Host.
                              Designated Location: The vehicle shall be parked at Host’s own location. Host shall ensure
                              that the vehicle is parked in a clean, safe and clearly identifiable location (a
                              “Designated Location”). Host shall have the Designated Locations within the city limits.
                              Host shall provide Shailesh Car & Bike PVT LTD detailed directions to the Designated
                              Location(s) for ensuring that Guests are able to find and access the vehicle. If a
                              Designated Location has restricted access, Host shall ensure that Guests are able to
                              access the location for a booking to make the pickup process seamless.</p>
                         <p>For the use of the listing service, you shall allow the personal/representatives of Shailesh
                              Car & Bike PVT LTD to visit your premise for assessment of your vehicle and installing
                              the In-Vehicle Device in your vehicle to ensure its complete safety. Upon installation /
                              fitment of the In-vehicle Device the vehicle will be returned to the location designated
                              by you. You hereby unconditionally agree not to tamper or remove such In-Vehicle Devices.
                              You further agree and acknowledge that such installed In-vehicle Devices may require minor
                              modification from time to time and you shall provide full access of the vehicle to
                              Shailesh</p>
                         <p>Car & Bike PVT LTD or any other party appointed by Shailesh Car & Bike PVT LTD for the
                              purpose of modification of such devices. In case you remove or otherwise tamper the
                              In-vehicle Devices, you shall be liable to pay Shailesh Car & Bike PVT LTD the actual
                              cost of such In-vehicle Device. Shailesh Car & Bike PVT LTD further reserves the right
                              to deduct the foregoing amount from amount to be paid by Shailesh Car & Bike PVT LTD to
                              you. Both Host and Guest acknowledge and accept that Shailesh Car & Bike PVT LTD shall
                              not be liable for any consequential damages arising due to such unauthorized removal
                              and/or tampering of In-vehicle Device by either of the parties For the purpose of this
                              Agreement, “In-Vehicle Devices” means and includes the various devices selected by
                              Shailesh Car & Bike PVT LTD to be installed in the vehicle for the security, safety,
                              tracking and health monitoring of the vehicle Host hereby expressly consent to any
                              consequential loss and warranty loss such as OEM “Original equipment Manufacturer”
                              warranty that you may suffer, as a result of fitment of the In-vehicle Device in the
                              vehicle. Notwithstanding the foregoing Shailesh Car & Bike PVT LTD will not provide any
                              compensation upon termination of this Agreement or your account for any other reason
                              whatsoever. You will not fit any other devices in the vehicle other than the In-Vehicle
                              Devices, whether for customer privacy, GPS or otherwise. Upon termination of this
                              Agreement for any reason whatsoever, Shailesh Car & Bike PVT LTD will be authorized to
                              remove</p>
                         <p>In-Vehicle Device installed in the vehicle and any failure to do so due to a reason
                              attributable to you, will result in a penalty on you as per the Fee Schedule.</p>
                         <p>Further, you acknowledge and accept that Shailesh Car & Bike PVT LTD collects GPS and
                              driver behavior related data through the In-Vehicle Devices and that the same will be
                              collected even when you are using it for your personal use due to fitment of In-Vehicle
                              Device in your Vehicle. You hereby agree and expressly consent that Shailesh Car & Bike
                              PVT LTD shall be allowed to collect such aforementioned data until removal of the
                              In-Vehicle Device from the Vehicle.</p>
                         <p>Once the vehicle onboarding process is complete the Vehicle will be listed on the Platform.
                              Your Host listing page will also include information such as your city and area detail
                              where the vehicle is located, your listing description, your public profile photo, your
                              responsiveness in replying to Guests’ queries, and any additional information you share
                              with other users via the Platform.</p>
                         <p>By listing a vehicle, Hosts are agreeing to (i) provide true and accurate information and
                              are representing that the information that they are providing is accurate; (ii) that the
                              photos, contained in the listing are actual photos of the vehicle being advertised, and
                              that they are not misrepresenting their vehicle in any way; (iii) maintain only one active
                              listing, per vehicle, at a time; (e) truthfully represent any claims or allegations of
                              damage; and (f) work in good faith to resolve any disagreement with Shailesh Car & Bike
                              PVT LTD and the Guests.</p>
                         <h3><strong>ONLINE BOOKING</strong></h3>
                         <p>Once your account is created on the Platform, the Guest will receive confirmation of
                              successful creation of Guest account from Shailesh Car & Bike PVT LTD. Thereafter, the
                              verified Guests can view the vehicles listed on the Platform and send a booking request
                              for your vehicle via the Platform</p>
                         <p>The Guest will be able to (i) book the trip to start at any time of the day subject to
                              availability; and (ii) choose a start time of the trip from the next hour from the time of
                              the booking.</p>
                         <p>Upon receipt of booking request in relation to a vehicle, Shailesh Car & Bike PVT LTD
                              shall confirm such booking and communicate details of the final booking with the Host and
                              the Guest through an email, text message or message via the Platform confirming such
                              booking By accepting these terms relating to the online booking process, the parties
                              hereby acknowledge and agree that (i) each of the Host and Guest accept the conditions for
                              listing the vehicle on the Shailesh Car & Bike PVT LTD Platform and use of Shailesh Car
                              And Bike PVT LTD Services. (ii) Shailesh Car & Bike PVT LTD is merely a facilitator and
                              any arrangements entered into between Host and Guest through this Platform or otherwise is
                              solely at their own risk and expense.</p>
                         <h3><strong>VEHICLE OWNERSHIP</strong></h3>
                         <p>The parties, specifically the Guests understand that this Agreement only grant
                              rental/usufructuary/</p>
                         <p>limited rights of use over the vehicle, and all along the absolute and unencumbered
                              ownership of the vehicle for all intent and purposes, including for regulatory requirement
                              under the applicable laws in India, will remain with the Host. This Agreement will cover
                              all terms of listing and availing of Shailesh Car & Bike PVT LTD Host Services and the
                              Lease Agreement (as defined under) shall cover the terms of the subsequent booking as
                              agreed between the Host and the Guest, including Damage Protection Fee (defined below),
                              liability for violations, theft/accident, confiscation of vehicle, insurance, issues
                              related to the use of the vehicles, and so on. It is hereby clarified, and the Host and
                              the Guest acknowledge that Shailesh Car & Bike PVT LTD is not the owner of the vehicles
                              listed on its Platform and is merely a facilitator as provided under this Agreement.</p>
                         <h3><strong>LEASE OF VEHICLE</strong></h3>
                         <p>Upon acceptance of the booking by the Host, the Host and Guest will be required to duly
                              enter into a standard lease agreement (“Lease Agreement”) to formally execute the terms
                              and conditions and commercials for such booking to ensure compliance with the requirements
                              of applicable law. Shailesh Car & Bike PVT LTD shall assist both the Host and the Guest
                              with the electronic execution and record keeping as a part of its Shailesh Car & Bike
                              PVT LTD Host Services.The Guest understands and accepts that the trip cannot start unless
                              the Lease Agreement is duly executed over our Platform.</p>
                         <p>The Host hereby acknowledges and agrees that by accepting the terms of this Host T&C, all
                              Lease</p>
                         <p>Agreements that are executed over the Platform with any Guest for the Host’s vehicle bear
                              the Host’s express consent and such Lease Agreement shall constitute a binding agreement
                              between the Host and the Guest. The Host also acknowledges and agrees that he/she is
                              cognizant of the terms of all such lease agreements and the corresponding booking details
                              that have been executed over the Shailesh Car & Bike PVT LTD Platform for the particular
                              trip. The Host shall receive a copy of the executed Lease Agreement through email along
                              with the booking details soon after the same has been executed by Guest upon the Platform.
                         </p>
                         <p>By utilizing a separate Lease Agreement or otherwise displaying terms relating to the lease
                              as part of the online booking process, the parties hereby acknowledge and agree that (i)
                              such separate Lease Agreement is directly between the Guest and the Host; (ii) the
                              Shailesh Car & Bike PVT LTD is not party to such separate Lease Agreement, (iii) Lease
                              Agreement executed, is solely at the parties’ own risk and expense, (iv) nothing contained
                              in the Lease Agreement,</p>
                         <p>on the Platform or this Agreement is a substitute for the advice of a legal counsel and (v)
                              the parties have been hereby advised to obtain local legal counsel to prepare, review and
                              revise as necessary the Lease Agreement to ensure compliance with applicable laws. If
                              there is any conflict between the terms of a separate Lease Agreement and this Agreement,
                              the terms of this Agreement shall prevail.</p>
                         <h3><strong>OFFLINE ARRANGEMENTS</strong></h3>
                         <p>Any instances where the Host and the Guest enter into a lease, rental or similar/analogous
                              arrangement involving the hiring/sharing/renting of the listed vehicle (by whatever name
                              called) with an intention to circumvent the Platform, while using, attempting or intending
                              to wrongly benefit from Shailesh Car & Bike PVT LTD Host Services or any other services
                              on the Platform, including without limitation, the additional insurance coverage (herein
                              any such arrangement to be referred as (“Offline Arrangements”) shall be contravention of
                              this Agreement. Please note that such Offline Arrangements are not permitted for vehicle/s
                              listed on the Platform. If any such offer to lease a listed vehicle outside the Platform,
                              is made to/by either Parties (Host or the Guest), the same should be reported to Shailesh
                              Car & Bike PVT LTD immediately. If you fail to follow these requirements, you may be
                              subject to a range of actions, including limits on your access to Shailesh Car & Bike
                              PVT LTD Host Services and other services, restrictions on listings, suspension of your
                              account, application of Facilitation Fees, and recovery of our expenses in policy
                              monitoring and enforcement. Furthermore, Offline Arrangements are explicitly excluded from
                              any Shailesh Car & Bike PVT</p>
                         <p>LTD offered insurance coverage or claims and Shailesh Car & Bike PVT LTD shall in no case
                              be held liable for any damages (direct or indirect), consequential losses, loss of
                              profit/business as faced by Host or the Guest entering into such an arrangement.</p>
                         <h3><strong>VEHICLE DELIVERY</strong></h3>
                         <p>Soon after the boking of vehicle is confirmed the Host shall:</p>
                         <p>have the vehicle is cleaned, sanitized and kept ready for delivery (including servicing and
                              routine maintenance) as per our maintenance, component and safety standards/equipment
                              specifications in Annexure I or opt for Shailesh Car & Bike PVT LTD’s Add-on Service in
                              this regard, details and terms available on the website</p>
                         <p>keep the vehicle Key, copies of documentation of the Vehicle, including the registration
                              certificate, Vehicle Insurance policy, Pollution Under Control (PUC) Certificate and other
                              mandatory documents, if any, prescribed by the relevant authorities under Applicable Laws
                              (the “Vehicle Documentation”) ready for delivery.</p>
                         <p>ensure that the vehicle is delivered Guest at the Designated Location and at the specified
                              time.</p>
                         <p>The Guest must be present in-person to take or receive the delivery of the vehicle. The
                              Guest must examine the vehicle before accepting its delivery and shall be deemed to have
                              satisfied himself as to its condition and suitability for his/her purpose, and its
                              compliance with any prescribed safety standards. After the delivery, any fault in the car
                              shall be dealt with in accordance with the terms of the Lease Agreement.</p>
                         <p>Cancellation of Booking / Reduction of Booking Period: If the Guest wishes to cancel a
                              booking or reduce the booking period for which the vehicle has been reserved, Guest must
                              do so in advance, in pursuance of the Fee Policy. Furthermore, if the Guest refuses and/or
                              is unable/unwilling for any reason to accept delivery of the vehicle, the booking shall be
                              automatically canceled and the any Lease Rental paid in advance shall stand forfeited to
                              compensate the Host for the costs, charges, expenses, losses incurred by the Host arising
                              out of such an action of the Guest, in pursuance of the Fee Policy. In case of any loss
                              suffered by the Guest due to non-delivery, delay in delivery, failure in delivery, the
                              Guest will not hold Shailesh Car & Bike PVT LTD responsible for such loss.</p>
                         <h3><strong>VEHICLE USAGE TERMS</strong></h3>
                         <p>The vehicle shall be driven only by the Guest and used in a prudent and careful manner
                              solely for Guest's personal use within the territory specified in the</p>
                         <p>Lease Agreement (“Permitted Territory”), in strict compliance with the requirements of the
                              applicable Laws of India and the conditions of the Lease Agreement (the “Permitted Use”).
                         </p>
                         <p>Other than the Permitted Use, all other uses of the vehicle including the usages as listed
                              in the Lease Agreement (by the Guest and/or any other person(s) directly or indirectly
                              acting through, authorized by or on behalf of the Guest), are strictly prohibited (the
                              “Prohibited Uses”) and shall result in immediate termination of the Lease and Shailesh Car
                              And Bike PVT LTD Host Services without any notice to the Guest. The Prohibited Uses shall
                              more particularly be described in the Lease Agreement between the Host and the Guest.
                              Notwithstanding anything contrary to the above, Guest shall, at all times be liable to
                              compensate Host during the Booking Period for any/all deliberate damages caused to the
                              vehicle by Guests an/or any of his/her co-driver or any other person who was permitted to
                              drive the vehicle by the Guest.</p>
                         <h3><strong>AGREED MILEAGE</strong></h3>
                         <p>Agreed mileage of a vehicle for the booking period shall be as specified in the booking
                              details on the Platform (“Agreed Mileage”) and in case the actual use of the vehicle
                              varies from the Agreed Mileage, charges towards the difference be paid the Guest to the
                              platform as per our Fee Policy at the time of expiry of booking period.</p>
                         <p>FACILITATION FEE, DAMAGE PROTECTION FEE, FIXED PAYOUT AND LEASE RENTAL Facilitation Fee:</p>
                         <p>Shailesh Car & Bike PVT LTD shall be entitled to charge the Host a fee in lieu of
                              provision of Shailesh Car & Bike PVT LTD Host Services (“Facilitation Fee”). This
                              Facilitation Fee shall be calculated as a certain percentage (more particularly described
                              in Fee policy) of the Rental. The Facilitation Fee shall be deducted from the Lease Rental
                              at the time of pay-out to Host. Platform Fee: Shailesh Car & Bike PVT LTD shall be
                              entitled to charge the Host a fee of INR 500 per month in lieu of the safety and
                              operational expense of Host’s car (“Host Platform Fee”). The Platform Fee shall be
                              deducted from the Lease Rental at the time of pay-out to Host. b. Further, at the time of
                              booking Guest shall pay a fee of INR 99 per booking (“Guest Platform Fee”) in lieu of the
                              services provided to the Guest on Shailesh Car & Bike PVT LTD Platform. The Platform
                              Fees shall be payable by Guest in addition to the Damage Protection Fee payable at the
                              time of booking a vehicle. Damage Protection Fee: At the time of booking a vehicle, the
                              Guest shall have to pay upfront a fee for insuring the vehicle at the time of the trip and
                              (“Damage Protection Fee”). Shailesh Car & Bike PVT LTD shall facilitate such protection
                              plans from time to time on payment of such Damage Protection Fee. Pay-out to the Host: For
                              the first 3 months from onboarding of the vehicle (“Initial Pay-out period”), the Host
                              shall be eligible to a pay-out solely on basis of the period for which the Host has listed
                              the vehicle on the Platform This pay-out shall be calculated as a fixed amount on an
                              hourly basis, shall vary as per</p>
                         <p>the vehicle type and is calculated as per the parameters under the Fee Policy. After the
                              Initial Pay-out Period, this model will be suspended, and the Host shall be paid on the
                              basis of the Lease Rental as paid by the Guest post deduction of the Shailesh Car & Bike
                              PVT LTD Facilitation Fee as applicable. Lease Rental For Guest: The Guest shall be liable
                              to pay a fee (“Lease Rental”) for leasing the vehicle and it shall be inclusive of the
                              applicable taxes (if any) in force. The same is dynamic and subject to vehicle type,
                              booking distance and dates, location etc., and shall be payable as per the terms and
                              timelines mentioned in the Fee Policy. All such payments shall be made by the Guest over
                              the Shailesh Car & Bike PVT LTD Platform and payment to Shailesh Car & Bike PVT LTD
                              shall be considered the same as payment made directly to the Hosts by the Guests. Other
                              payments, refunds, and penalties: In addition to the above Lease Rental and the Damage
                              Protection Fee, the Guest shall also be liable for the following as described in the Fee
                              Policy: Default interest and reminder fee for late payments. Add-on Charges (if availed)
                              for services like home delivery facility or addition of a co-driver for the trip. Charges
                              for loss of keys, documents, unpaid tolls, traffic violation penalties. Cost for any
                              damages which may include both cost of repair as well as insurance cover as per the
                              standard rates in the Fee Policy</p>
                         <p>The Guest acknowledges and agrees that he/she shall be liable to pay such charges on
                              occurrence of any of the above-mentioned event/s and hereby authorizes Shailesh Car And
                              Bike PVT LTD to set off any amounts as may be due from Shailesh Car & Bike PVT LTD to
                              the Guest against any amounts that may be payable by the Guest under this Agreement, as
                              the case may be.</p>
                         <p>Guest also acknowledges and agrees that Shailesh Car & Bike PVT LTD shall have the right
                              to prohibit the Guest from making a subsequent booking on the Platform until all
                              outstanding fees in the Guest's account have been paid in full.</p>
                         <p>The Guests also understand and agree that Shailesh Car & Bike PVT LTD may charge
                              additional fees for failed payments, returned/canceled checks. The Guest will be
                              responsible to reimburse us for all costs of collection, including collection agency fees,
                              third party fees, and legal fees, and costs.</p>
                         <p>If you are a Host, you understand, acknowledge, and agree that Shailesh Car & Bike PVT LTD
                              may set the booking/reservation fee for your vehicle as per the Fee Policy. Shailesh Car
                              And Bike PVT LTD will adjudicate the booking/reservation fee on your behalf, which means
                              processing the Guest's [credit/debit card], retaining the Facilitation Fees and other
                              add-on services fee, if any, commission and remitting such funds to you as provided in
                              this section.</p>
                         <p>Shailesh Car & Bike PVT LTD reserves the right to withhold payment or charge back to your
                              account any amounts otherwise due to us under this Agreement, in the event of any account
                              information is lacking or mismatched or in the event of where there has been any breach of
                              this Agreement by you, pending Shailesh Car & Bike PVT LTD’s reasonable investigation of
                              such breach.</p>
                         <p>To ensure proper payment, both Guest and the Host are solely responsible for providing and
                              maintaining accurate contact and payment information associated with your account, which
                              includes, without limitation, applicable tax information and Shailesh Car & Bike PVT LTD
                              shall in no case be held liable on account of any error in payments due to information
                              wrongly provided by you.</p>
                         <p>If you dispute any payment made hereunder, you must notify Shailesh Car & Bike PVT LTD in
                              writing within 3 days of any such payment; failure to notify Shailesh Car & Bike PVT LTD
                              shall result in the waiver by you of any claim relating to any such disputed payment.
                              Payment shall be calculated solely based on records maintained by Shailesh Car & Bike
                              PVT LTD.</p>
                         <p>In the event of a conflict between this Clause and terms of the Fee Policy, the terms set
                              forth in the Fee Policy shall prevail.</p>
                         <h3><strong>HOST’S OBLIGATIONS</strong></h3>
                         <p>In connection with use of or access to the Shailesh Car & Bike PVT LTD Host Services the
                              Host shall not, and hereby agrees that it will not, nor advocate, encourage, request, or
                              assist any third party in activity or otherwise, to harm or threaten to harm users of our
                              community, including but not limited to, (i) "stalking" or harassing any other Guest or
                              Host of Shailesh Car & Bike PVT LTD community or user of the Platform (ii) collecting or
                              storing any personally identifiable information about any other member or associate of
                              Shailesh Car & Bike PVT LTD community, other than as specifically agreed / allowed
                              herein (iii) engaging in physically or verbally abusive or threatening conduct; or (iv)
                              using our Services to transmit, distribute, post, or submit any information concerning any
                              other person or entity, including without limitation, photographs of others without their
                              permission, personal contact information, or credit, debit, calling card, or account
                              numbers.</p>
                         <p>The Host is also bound to maintain car conditions and ensure continuity of his listings for
                              agreed upon periods on our Platform. In this regard, the Host is additionally governed by
                              Host Strike Policy, the failure to comply with which may lead to delisting of Host vehicle
                              from the Shailesh Car & Bike PVT LTD Host program.</p>
                         <p>Host further agrees and acknowledges that in case of any concerns including but not limited
                              to the damages caused to the vehicle during the booking period shall only be raised by
                              raising his/her concern via authorized ticket support process. If the Host refuses or
                              denies to follow the due redressal mechanism continuously, Shailesh Car & Bike PVT LTD
                              shall at its sole discretion have the right to terminate such Host from the Platform.
                              Further Shailesh Car & Bike PVT LTD shall not be liable to entertain or make good for
                              any such damage or other claims unless the same is duly routed through the authorized
                              ticket support process.</p>
                         <p>Checklists help us ensure that all information regarding the vehicle, the trip and customer
                              experience are captured so we can serve the Hosts and Guests better. Accordingly, Host
                              shall be responsible for filling:</p>
                         <p>“Car Ready Checklist” within 24 hours of listing start time. If the Host fails to fill it
                              within mentioned timelines, then the listing gets canceled automatically. “Booking End
                              Checklist” within 2 hours of the booking end time or the start till of the next booking.
                              If the Host fails to fill the checklist within the above stipulated timelines, then the
                              last available information with Shailesh Car & Bike PVT LTD (for e.g. from the Guest
                              checklist) shall be deemed as final for the closure of the booking</p>
                         <p>Guests OBLIGATIONS</p>
                         <p>Both parties shall be responsible to ensure compliance with the provisions of the Lease
                              Agreement at times during the Lease Term and until the return of the vehicle to the Host
                              in good working condition. In addition to other obligations and covenants under the Lease
                              Agreement, as regards the use of the Vehicle during the aforesaid period the Guest shall:
                         </p>
                         <p>at his/her expense maintain the cleanliness, condition, and appearance of the vehicle in as
                              good an operating condition as it was on the commencement date of the Lease Term. use the
                              Vehicle only for the Permitted Use in conformity with the Host’s manual instructions
                              provided as part of Vehicle Documentation, applying the same degree of care when using the
                              vehicle as would not drive vehicle roughly and strictly refrain from Prohibited Use of
                              Vehicle and other requirements as laid down more particularly in the Lease Agreement under
                              the Section “Terms of Vehicle Usage” ensure the safekeeping and presence of the Vehicle
                              Documentation in the vehicle. If these documents are lost or stolen, the Guest will be
                              charged the cost of obtaining duplicates and be remitted to the Host along with all other
                              charges for damages and Lease Rental as payable to the Host</p>
                         <h3><strong>ACCIDENT,THEFT,TRAFFICVIOLATION AND CONFISCATION</strong></h3>
                         <p>All instances of accident, damage, theft, traffic violations and confiscation of or
                              involving the vehicle during the Lease Term shall be handled by the parties in accordance
                              with the provisions of the Lease Agreement, including alleged damage or other issues. The
                         </p>
                         <p>Hosts and the Guests further agree to honestly represent any claims or allegations of damage
                              and to work in good faith with each other to resolve any disagreement in keeping with the
                              terms of the Lease Agreement.</p>
                         <h3><strong>INSURANCE & DAMAGE PROTECTION</strong></h3>
                         <p>The Host shall maintain a minimum of third-party comprehensive insurance as mandated by
                              Motor Vehicles Act, 1988 for the vehicle with an insurance company of its choice (“Vehicle
                              Insurance”). The Guest shall be responsible for payment of all expenses associated with
                              any risks and ensuing damage to the vehicle including without limitation theft, partial or
                              total destruction etc. In doing so, the Guest shall be required to avail trip protection
                              plans/insurance through the Platform and shall be required to avail so at requisite fee
                              (Damage Protection Fee) over and above the Lease Rental. Guest acknowledges and agrees to
                              abide by the terms and conditions pertaining to the trip protection plan/insurance,
                              including without limitation its coverage, exclusions and process of invocation.</p>
                         <p>Shailesh Car & Bike PVT LTD shall assist the Host in filing and administering such claims
                              for damages, theft or loss of vehicle. Platform shall also assist the Guest in
                              administration of claims with the Host.</p>
                         <p>Both Host and the Guest acknowledge and agree that the information gathered through the
                              Booking Start/Pick-up Checklist and the Booking End/Drop Checklist is crucial to the
                              Damage Protection process. Should the Host or the Guest fail to fill in these</p>
                         <p>checklists, no claims of damage/repair etc. shall be entertained or administered in absence
                              of relevant proof collected through these checklists. The Guest shall not be allowed to
                              contest claims from the Host/claim refunds and the Host shall not be allowed to raise
                              claims in absence of such fully filled in checklists. In events of technical issues
                              preventing the filling of the checklist, the Host/Guest should immediately contact
                              customer support for resolution.</p>
                         <p>The Host understands and undertakes that he/she shall not act in a manner contrary or
                              prejudicial to the Platform or the Guest and extend his/her full cooperation and
                              participation at the time of any such claim being invoked under the trip protection
                              plan/insurance.</p>
                         <p>The Host also understands and agrees that in the event that the Host refuses, interferes,
                              prevents the administration of the claim in any manner or repossesses the vehicle which is
                              undergoing any maintenance/repair due to invocation of insurance, he/she shall forfeit any
                              rights to claim damages from the Guest/ insurance company as the case maybe. Neither
                              Shailesh Car & Bike PVT LTD nor the Guest will be liable to make good any damages in
                              such a situation and shall stand discharged of all liabilities therein.</p>
                         <p>The Guest shall not do or omit to do or be done or permit or suffer any act which might or
                              could prejudicially vitiate or affect any such damage protection plan and shall at all
                              times extend full cooperation so that the claims can be effectively administered.</p>
                         <p>The Host also understands and agrees that for the events including but not limited to the
                              below listed, the vehicle shall not be protected under any trip protection plan/insurance.
                              if: - The damage occurs when the vehicle is in possession of Host and/or occurs due to
                              deliberate/negligent acts of the Host itself. - Any damage arising due to normal wear and
                              tear of the vehicle or depreciation in quality or value of the vehicle as such including
                              but not limited to self-heating, electrical arcing or leakage etc. - Any specific
                              exclusions as may be listed by the insurance company in such a trip protection
                              plan/insurance.</p>
                         <p>In case of total loss of vehicle, the Host understands and agrees to bind themselves to the
                              depreciation level as prescribed under law or as prescribed by the relevant insurance
                              company in line with market practice.</p>
                         <p>The Guest also understands and agrees that certain damages/incidents as listed below are not
                              covered under such trip protection plans and the Guest will fully and personally be held
                              liable for all costs and damages.</p>
                         <p>The following shall not be covered under trip protection plan/insurance:</p>
                         <p>Any deliberate act of damaging the vehicle by the Guest or any of his/her co-driver Any
                              damage to the vehicle due to negligence or rash driving on part of the Guest.</p>
                         <p>The Guest was tested with alcohol in blood or breath or used drugs and or other stimulants
                              prohibited by the law The Guest used the vehicle in a manner that is in contravention of
                              law or the traffic regulations (over speeding, driving in restricted areas or any other
                              illegal usage for racing/commercial usage etc.).</p>
                         <p>In the event of any damage, theft, or destruction of the Vehicle during the Guest shall
                              promptly inform the Platform and render all documentation and information including but
                              not limited to information about the accident, assistance in filing of FIR or other
                              relevant details as maybe necessary to invoke a claim with the company providing the trip
                              protection plan/insurance with the assistance from the Platform.</p>
                         <p>Accordingly, the Guest shall pay to the Host, the amount of loss and/or damage not paid
                              under the trip protection plan/insurance and be liable for the following: In case of
                              Damage:</p>
                         <p>The difference, if any, between the actual amount incurred in repairing the damage to the
                              vehicle and the amounts recovered/to be recovered under the Vehicle Insurance.</p>
                         <p>In case of theft/total loss of the Vehicle:</p>
                         <p>The shortfall between the claim amount received under the trip protection plan/insurance,
                              and the book value of the vehicle at that time of its theft/total loss.</p>
                         <p>If usage of vehicle at the time of its theft/total loss exceeds the Agreed Mileage (defined
                              below), charge of the excess mileage incurred as per the rate specified in Fee Policy. For
                              Retired Vehicles, damage protection compensation is not applicable and hence no payout
                              shall be made for theft/ total loss of such Retired Vehicles. other cost/expense incurred
                              by the Host for/in respect of assessment loss suffered by the vehicle and possibility of
                              its restoration. other charges, if any, remaining unpaid by the Guest under the Lease
                              Agreement.</p>
                         <p>Notwithstanding any such additional trip protection plan/insurance availed, under no
                              circumstances shall Shailesh Car & Bike PVT LTD be held liable towards the parties or a
                              third party for any loss or damage that may be suffered by the parties or a third party,
                              whether or not the same may be attributed to parties.</p>
                         <h3><strong>VEHICLE RETURN / REPOSSESSION</strong></h3>
                         <p>Upon the expiry of the Lease Term or earlier termination of the Lease Agreement (except
                              termination on account of theft or total destruction/loss of the vehicle), Guest must at
                              his/her own cost return the vehicle in the almost the same order and condition, as the
                              Vehicle was at the time of commencement of the Lease Term, except normal wear and tear,
                              with Vehicle Documentation, vehicle’s key, key fob, in-vehicle devices and other starting
                              device in its designated position in the vehicle to the Specified Location within the
                              period specified in the Lease Agreement. The Guest is mandatorily required to fill up the
                              Booking</p>
                         <p>End/Drop Checklist for recording the car condition at the end of the trip. This will be
                              followed by filling of a similar Booking End/Drop Checklist by the Host as and when the
                              Host is returned the vehicle by the Guest. If, however, in case: The Guest returns the
                              vehicle at a place other than the Designated Location; the Guest will be charged the cost
                              of transportation of the vehicle from such place to the Designated Location. The Guest
                              does not return the Vehicle within the specified period, Guest will be charged late return
                              penalty specified in our Fees Policy till such time as the vehicle is returned to the Host
                              and also the costs, expenses, charges etc. incurred by the Host for repossession of the
                              vehicle. Damage caused to the returning vehicle, other than excepted wear and tear, the
                              Guest will be charged penalty for such damages at the rate specified in our Fees Policy
                              and approximate costs, expenses, charges for restoration of the vehicle to its original
                              condition. Any item provided with the vehicle is lost, including without limitation its
                              key, key fob, in-vehicle devices, other starting device to the vehicle or any component(s)
                              of the vehicle, Vehicle Documentation is missing, the Guest will be charged with (a) Lease
                              Rental (prorated on hourly basis) until the missing item is returned safely to the Host;
                              and (b) an inconvenience fee if the lost items are not returned and need to be replaced.
                              The actual usage of the vehicle by the Guest exceeds the Agreed Mileage, the Guest shall
                              pay the excess mileage charge as per the rate specified in our Fees Policy.</p>
                         <p>All such disputes shall be administered only by means of the information gathered through
                              Booking Start/Pickup Checklist and the Booking End/Drop Checklist as duly filled in by
                              both Host and the Guest. The Guest should ensure that these checklists are duly filled in
                              to avoid any hassles and additional penalties for damages caused.</p>
                         <h3><strong>WARRANTIES OF THE PARTIES</strong></h3>
                         <p>Hosts’ Warranties:</p>
                         <p>Each Host represents and warrants to Shailesh Car & Bike PVT LTD that:</p>
                         <p>Host is the sole legal, beneficial and registered owner of the vehicle(s) listed on the
                              Platform. The vehicle you offer for listing on the Platform is in sound and safe condition
                              and free of any known faults or defects that would affect its safe operation under normal
                              use and meets the vehicle eligibility criteria mentioned in this Agreement. Host has the
                              full legal right, capacity, power and authority to enter into and execute the Lease
                              Agreement, Agreement and General Policies, be contractually bound by and comply with all
                              rights and obligations contracted under each of these documents. There is no action,
                              investigation or other proceedings of any nature whatsoever, by any governmental authority
                              or third party against the Host, which would restrain, prohibit or otherwise challenge the
                              Lease, any listing of the vehicle on the Platform, Host’s posts on Platform and/or or a
                         </p>
                         <p>Guest's use of vehicle pursuant to the Lease Agreement.</p>
                         <p>Guests’ Warranties:</p>
                         <p>Each Guest represents and warrants that:</p>
                         <p>The Guest is above the legal driving age requirement and has a valid driving license for the
                              use and operation of the vehicle in accordance with requirements of applicable laws. The
                              Guest has the full legal right, capacity, power, and authority to enter into and execute
                              the Lease Agreement, this Agreement and the General Policies and be contractually bound by
                              and comply with all rights and obligations contracted under each of these documents. There
                              is no action, investigation, or other proceedings of any nature whatsoever, by any
                              governmental authority or third party against the Guest, which would restrain, prohibit,
                              or otherwise challenge the transaction as contemplated by the Lease Agreement.</p>
                         <p>WARRANTIES OF Shailesh Car & Bike PVT LTD</p>
                         <p>The Platform and Shailesh Car & Bike PVT LTD Host Services are provided to you “AS IS”. We
                              make no representations regarding the use of or the result of the use/depiction of the
                              contents on the Platform in terms of their correctness, accuracy, reliability, or
                              otherwise. Shailesh Car & Bike PVT LTD shall not be liable for any loss suffered in any
                              manner by the user as a result of depending directly or indirectly on the depiction of the
                              content on the Platform.</p>
                         <p>You acknowledge that the Platform is provided only on the basis set out in the General
                              Policies. Your uninterrupted access or use of the Platform and Shailesh Car & Bike PVT
                              LTD Host Services on this basis may be prevented by certain factors outside our reasonable
                              control including, without limitation, the unavailability, inoperability or interruption
                              of the internet or other telecommunications services or as a result of any maintenance or
                              other service work carried out on the Platform.</p>
                         <p>Shailesh Car & Bike PVT LTD shall have the right, at any time, to change or discontinue
                              any aspect or feature of the Platform, including, but not limited to, content, hours of
                              availability and equipment needed for access or use. Further, the Platform may discontinue
                              disseminating any portion of information or category of information. Shailesh Car & Bike
                              PVT LTD does not accept any responsibility and will not be liable for any loss or damage
                              whatsoever arising out of or in connection with any ability/inability to access or to use
                              the Platform.</p>
                         <p>The postings on the Platform or on social networking sites, including the Platform’s
                              Facebook page, or any information provided over chat or emails exchanged with Shailesh Car
                              And Bike PVT LTD, its employees or representatives (collectively referred to as
                              “Information”) which are in furtherance of any communication made by the user with
                              Shailesh Car & Bike PVT LTD, its employees or representatives is based on the background
                              provided by the user. While Shailesh Car & Bike PVT LTD takes reasonable care to ensure
                              that the Information is accurate, Shailesh Car & Bike</p>
                         <p>PVT LTD makes no representation and takes no responsibility for the accuracy, completeness,
                              appropriateness, or usefulness of the Information. In the event any user relies on the
                              Information provided by Shailesh Car & Bike PVT LTD or its representatives/ employees,
                              he/she may do so at its own risk. Under no circumstances will Shailesh Car & Bike PVT
                              LTD, its employees, representatives or affiliates be liable for the Information or the
                              consequences of relying on such Information.</p>
                         <h3><strong>USERS’ INDEMNITIES</strong></h3>
                         <p>During the subsistence of the Lease Agreement and/or this Agreement, both parties i.e., the
                              Hosts and the Guests shall at all times, indemnify, defend, hold harmless and keep
                              indemnified, Shailesh Car & Bike PVT LTD, its parent and affiliates and their respective
                              directors, officers, employees, shareholders, agents, attorneys, assigns and
                              successors-in-interest (“Shailesh Car & Bike PVT LTD Group”) against all losses,
                              liabilities, damages, injuries, claims, demands, costs, attorney fees and other expenses
                              arising out of or attributable to: any losses, costs, charges or expenses (including
                              between attorney and Guest and costs of litigation) or outgoings which Shailesh Car And
                              Bike PVT LTD shall certify as sustained or suffered or incurred by Shailesh Car & Bike
                              PVT LTD or any member of Shailesh Car & Bike PVT LTD Group as a consequence of
                              occurrence of default under the Lease Agreement, this Agreement and/or the General
                              Policies any loss, cost, charge, claim, damage, expense or liability that Shailesh Car And
                              Bike PVT LTD or any</p>
                         <p>member of Shailesh Car & Bike PVT LTD Group may suffer as a result of any representation
                              or warranty made by the parties in connection with the Lease Agreement, this Agreement
                              and/or the General Policies Agreement being found to be materially incorrect or
                              misleading. any losses, claims, damages, expenses, liability for any death, injury or
                              damage to any person or property that Shailesh Car & Bike PVT LTD or any member of
                              Shailesh Car & Bike PVT LTD Group may suffer/ incur arising directly or indirectly from
                              the listed vehicle or its use under the Lease Agreement, whether caused willfully/ or the
                              result of rash and negligent driving or any malicious act. any claim for breach of
                              intellectual property rights arising in connection with the Shailesh Car & Bike PVT LTD
                              Host Services and/or any other services provided by Shailesh Car & Bike PVT LTD or any
                              member of Shailesh Car & Bike PVT LTD Group. liability and costs incurred by Shailesh
                              Car & Bike PVT LTD group in connection with any claim arising out of your use of the
                              platform or otherwise relating to the business we conduct on the platform (including,
                              without limitation, any potential or actual communication, transaction or dispute between
                              you and any other user or third party), any content posted by you or on your behalf or
                              posted by other users of your account to the website, any use of any tool or service
                              provided by a third party provider, any use of a tool or service offered by us that
                              interacts with a third party website, including without limitation any social media site
                              or any breach by you of these terms or the representations, warranties and</p>
                         <p>covenants made by you herein, including without limitation legal fees and costs.</p>
                         <p>Each of the above indemnity is a separate and independent obligation and continues after
                              termination of this Agreement. The users also covenant to cooperate as fully as reasonably
                              required in the defense of any claim. Further, Shailesh Car & Bike PVT LTD hereby
                              reserves the right, at our own expense, to assume the exclusive defense and control of any
                              matter otherwise subject to indemnification by you and you shall not in any event settle
                              any matter without our written consent.</p>
                         <p>TERMINATION OF THIS AGREEMENT / Shailesh Car & Bike PVT LTD HOST SERVICES OR THE LEASE
                              AGREEMENT</p>
                         <p>This Agreement shall continue to apply and shall remain valid till the time the concerned
                              party continues to use Shailesh Car & Bike PVT LTD Services through its Platform or is
                              terminated by either you or Shailesh Car & Bike PVT LTD (“Term”).</p>
                         <p>If You want to terminate this Agreement, you may do so by (I) not accessing the Platform or
                              the Shailesh Car & Bike PVT LTD Services; or (ii) closing Your account on the Platform
                              for all of the listings or bookings of vehicles, as applicable, where such option is
                              available to You, as the case may be; or (iii) discontinuing any further use of the
                              Platform. Any such termination shall not cancel your obligation to pay for the Shailesh
                              Car & Bike PVT LTD Services and/or any other services already obtained from us and/ the
                              Platform or affect any liability that may have arisen under the Governing Policies.</p>
                         <p>Additionally, Shailesh Car & Bike PVT LTD shall have the sole discretion to suspend or
                              terminate this Agreement and discontinue Shailesh Car & Bike PVT LTD Services and/or
                              services provided by us (through the Platform or otherwise) by providing 30 (thirty) days’
                              prior notice to you. However, we may, at any time, with or without notice, suspend or
                              terminate this Agreement and Shailesh Car & Bike PVT LTD Services if: We are required to
                              do so by law (for example, where the provision of the Shailesh Car & Bike PVT LTD
                              Services to you is, or becomes, unlawful), or upon request by any law enforcement or other
                              government agencies. The provision of the Shailesh Car & Bike PVT LTD Services to you by
                              Shailesh Car & Bike PVT LTD is, in our sole discretion, no longer commercially viable to
                              us. The User fails to make any of the payments or part thereof or any other payment
                              required to be made to Shailesh Car & Bike PVT LTD hereunder and/or in respect of the
                              Shailesh Car & Bike PVT LTD Services, or any other service provided by Shailesh Car And
                              Bike PVT LTD when due and such failure continues for a period of 15 (fifteen) calendar
                              days after the due date of such payment. The User fails to perform or observe any other
                              covenant, conditions or agreement to be performed or observed by it under any of the
                              Governing Policies or in any other document furnished to Shailesh Car & Bike PVT LTD in
                              connection herewith. Termination of the listing or the booking on account of any
                              wrongdoing of either party and/or</p>
                         <p>violation of any terms, conditions and obligations of this Agreement and/or the Governing
                              Policies. The vehicle is being used for a Prohibited Use, as determined by us in our sole
                              discretion. Shailesh Car & Bike PVT LTD has elected to discontinue, with or without
                              reason, access to the Platform and/or the Shailesh Car & Bike PVT LTD Services (or any
                              part thereof). In the event Shailesh Car & Bike PVT LTD faces any unexpected technical
                              issues or problems that prevent the Platform, the Shailesh Car & Bike PVT LTD Services,
                              and/or any other services provided by Shailesh Car & Bike PVT LTD from working. Any
                              other similar unforeseen circumstances.</p>
                         <p>Termination of Lease Agreement by the Host/Guest:</p>
                         <p>Both the Host and the Guest may terminate the Lease Agreement as per the terms of the Lease
                              Agreement</p>
                         <p>Effects of Termination: In case of termination of this Agreement or completion of a booking,
                              in accordance with the terms hereunder and the Governing Policies:</p>
                         <p>the Guest shall promptly and without delay return the vehicle to the Host, as per the
                              vehicle return / Repossession terms mentioned herein. the Guest shall pay, the outstanding
                              Lease Rental (together with all late payment/charges thereon) and other unpaid
                              sums/charges/costs payable by the Guest under the Agreement and Governing Policies. The
                              Host shall pay any outstanding amounts due payable by the Host under the Agreement and
                              Governing Policies.</p>
                         <p>The Host shall upon termination make its vehicle available to Shailesh Car & Bike PVT LTD
                              for removal of the In-Vehicle Device. Upon the return of the vehicle, the Guest shall be
                              repaid the advance Lease Rental if any, paid by the Guest for the unexpired period of the
                              booking period to the Guest subject to adjustment against other outstanding payable of the
                              Guest for the booking made by him/her; Upon any termination of this Agreement either you
                              or Shailesh Car & Bike PVT LTD, you must promptly destroy all materials downloaded or
                              otherwise obtained from the Platform, as well as all copies of such materials, whether
                              made under the Governing Policies or otherwise.</p>
                         <h3><strong>RECOMMENDATION OF PLATFORM</strong></h3>
                         <p>Any recommendation made to you on the Platform during the course of your use of the Platform
                              is purely for informational purposes and for your convenience and does not amount to
                              endorsement of the Shailesh Car & Bike PVT LTD Host Services by Shailesh Car & Bike
                              PVT LTD or any of its associates in any manner.</p>
                         <h3><strong>USER CONTENT</strong></h3>
                         <p>The information, photo, image, chat communication, text, software, data, music, sound,
                              graphics, messages, videos or other materials transmitted, uploaded, posted, emailed or
                              otherwise made available to us (“User Content”), are entirely your responsibility and we
                              will not be held responsible, in any manner whatsoever, in connection to the User Content.
                              You</p>
                         <p>agree to not encourage or assist or engage others as well as yourself in transmitting,
                              hosting, displaying, uploading, modifying, publishing transmitting, updating or sharing
                              any information that:</p>
                         <p>belongs to another person and to which the user does not have any right to; is grossly
                              harmful, harassing, blasphemous defamatory, obscene, pornographic, pedophilic, libelous,
                              invasive of another’s privacy, hateful, or racially, ethnically objectionable,
                              disparaging, relating or encouraging money laundering or gambling, or otherwise unlawful
                              in any manner whatever; harms minors in any way; infringes any patent, trademark,
                              copyright or other proprietary rights; violates any law for the time being in force;
                              deceives or misleads the addressee about the origin of such messages or communicates any
                              information which is grossly offensive or menacing in nature; impersonate another person;
                              contains software viruses or any other computer cod, files or programs designed to
                              interrupt, destroy or limit the functionality of any computer resource; and/or threatens
                              the unity, integrity, defense, security or sovereignty of India, friendly relations with
                              foreign states, or public order or causes incitement to the commission of any cognizable
                              offense or prevents investigation of any offense or is insulting any other nation.</p>
                         <p>Shailesh Car & Bike PVT LTD shall in no way be held responsible for examining or
                              evaluating User Content, nor does it assume any responsibility or liability for the User
                              Content. Shailesh Car & Bike PVT LTD does not endorse or control the User Content
                              transmitted or posted on the Platform by you and therefore, accuracy, integrity or quality
                              of User Content is not guaranteed by Shailesh Car & Bike PVT LTD. You understand that by
                              using the Platform, you may be exposed to User Content that is offensive, indecent or
                              objectionable to you. Under no circumstances will Shailesh Car & Bike PVT LTD be liable
                              in any way for any User Content, including without limitation, for any errors or omissions
                              in any User Content, or for any loss or damage of any kind incurred by you as a result of
                              the use of any User Content transmitted, uploaded, posted, e-mailed or otherwise made
                              available via the Platform. You hereby waive all rights to any claims against Shailesh Car
                              And Bike PVT LTD for any alleged or actual infringements of any proprietary rights, rights
                              of privacy and publicity, moral rights, and rights of attribution in connection with User
                              Content.</p>
                         <p>You hereby acknowledge that Shailesh Car & Bike PVT LTD has the right (but not the
                              obligation) in its sole discretion to refuse to post or remove any User Content and
                              further reserves the right to change, condense, or delete any User Content. Without
                              limiting the generality of the foregoing or any other provision of these Terms and
                              Conditions, Shailesh Car & Bike PVT LTD has the right to remove any User Content that
                              violates these Terms and Conditions or is otherwise objectionable and further reserves the
                              right to refuse service and/or terminate accounts without prior notice</p>
                         <p>for any users who violate these Terms and Conditions or infringe the rights of others.</p>
                         <p>If you wish to delete your User Content on our Platform, please contact us by email at and
                              request you to include the following personal information in your deletion request: first
                              name, last name, user name/screen name (if applicable), email address associated with our
                              Platform, your reason for deleting the posting, and date(s) of posting(s) you wish to
                              delete (if you have it). We may not be able to process your deletion request if you are
                              unable to provide such information to us. Please allow up to 30 business days to process
                              your deletion request.</p>
                         <h3><strong>INTELLECTUAL PROPERTY RIGHTS</strong></h3>
                         <p>The “Shailesh Car & Bike PVT LTD” name and logo and all related product and service names,
                              design marks and slogans are the trademarks, logos or service marks (hereinafter referred
                              to as “Marks”) of Shailesh Car & Bike PVT LTD India Private Limited. All other Marks
                              provided on the Platform are the property of their respective companies. No trademark or
                              service mark license is granted in connection with the materials contained on this
                              Platform. Access to the Platform does not authorize anyone to use any Marks in any manner.
                              Marks displayed on the Platform, whether registered or unregistered, of Shailesh Car And
                              Bike PVT LTD or others, are the intellectual property of their respective owners, and
                              Shailesh Car & Bike PVT LTD shall not be held liable in any manner whatsoever for any
                              unlawful, unauthorized use of the Marks.</p>
                         <p>Shailesh Car & Bike PVT LTD and its suppliers and licensors expressly reserve all the
                              intellectual property rights in all text, programs, products, processes, technology,
                              content, software and other materials, which appear on the Platform, including its looks
                              and feel. The compilation (meaning the collection, arrangement and assembly) of the
                              content on the Platform is the exclusive property of Shailesh Car & Bike PVT LTD and are
                              protected by the Indian copyright laws and International treaties. Consequently, the
                              materials on the Platform shall not be copied, reproduced, duplicated, republished,
                              downloaded, posted, transmitted, distributed or modified in whole or in part or in any
                              other form whatsoever, except for your personal, non-commercial use only. No right, title
                              or interest in any downloaded materials or software is transferred to you as a result of
                              any such downloading or copying, reproducing, duplicating, republishing, posting,
                              transmitting, distributing or modifying.</p>
                         <p>All materials, including images, text, illustrations, designs, icons, photographs, programs,
                              music clips, downloads, video clips and written and other materials that are part of the
                              Platform (collectively, the “Contents”) are intended solely for personal, non-commercial
                              use. You may download or copy the Contents and other downloadable materials displayed on
                              the Platform for your personal use only. We also grant you a limited, revocable,
                              non-transferable, and non-exclusive license to create a hyperlink to the homepage of the
                              Platform for personal, non-commercial use only. Any other use, including the reproduction,
                              modification, distribution, transmission, re-publication, display, or performance, of the
                              Contents on the</p>
                         <p>Platform is strictly prohibited. Unless Shailesh Car & Bike PVT LTD explicitly provides to
                              the contrary, all Contents are copyrighted, trademarked, trade dressed and/or other
                              intellectual property owned, controlled or licensed by Shailesh Car & Bike PVT LTD, any
                              of its affiliates or by third parties who have licensed their materials to Shailesh Car
                              And Bike PVT LTD and are protected by Indian copyright laws and international treaties.
                         </p>
                         <h3><strong>DISCLAIMEROF WARRANTY AND LIMITATION OF LIABILITY</strong></h3>
                         <p>PLEASE NOTE THAT Shailesh Car & Bike PVT LTD HOST SERVICES ARE INTENDED TO BE USED TO
                              FACILITATE THE LEASING OF VEHICLE BY THE HOST AND TO THE Guest. Shailesh Car & Bike PVT
                              LTD CANNOT AND DOES NOT CONTROL THE CONTENT IN ANY LISTINGS AND THE CONDITION, LEGALITY OR
                              SUITABILITY OF ANY VEHICLE LISTED ON THE PLATFORM. Shailesh Car & Bike PVT LTD IS NOT
                              RESPONSIBLE FOR, AND DISCLAIMS ANY AND ALL LIABILITY RELATED TO, ANY AND ALL LISTINGS AND
                              VEHICLE. ANY LEASING OF THE LISTED VEHICLE UNDER THE LEASE AGREEMENT OR OTHERWISE WILL BE
                              DONE ENTIRELY AT THE GUEST’S AND HOST’S OWN RISK.FURTHER Shailesh Car & Bike PVT LTD
                              SHALL NOT BE LIABLE TOWARDS THE LOSSES, DAMAGES, COSTS INCURRED BY THE HOST OR THE GUEST
                              IN ABSENCE OF THE DULY FILLED IN BOOKING START/PICKUP CHECKLIST AND THE BOOKING END/DROP
                              CHECKLIST. Shailesh Car & Bike PVT LTD SHALL ALSO NOT BE RESPONSIBLE FOR ANY TOTAL
                              LOSS/THEFT CLAIMS UNDER DAMAGE PROTECTION PLAN FOR RETIRED VEHICLES.</p>
                         <h3><strong>THE PLATFORM IS PRESENTED “AS IS”. NEITHER WE NOR OUR HOLDING, SUBSIDIARIES,
                                   AFFILIATES, PARTNERS OR</strong></h3>
                         <h3><strong>LICENSORS MAKE ANY REPRESENTATIONS OR WARRANTIES OF ANY KIND WHATSOEVER, EXPRESS OR
                                   IMPLIED, IN CONNECTION WITH THESE TERMS AND CONDITIONS OR THE PLATFORM OR ANY OF THE
                                   CONTENT, EXCEPT TO THE EXTENT SUCH REPRESENTATIONS AND WARRANTIES ARE NOT LEGALLY
                                   EXCLUDABLE.</strong></h3>
                         <p>YOU AGREE THAT, TO THE FULLEST EXTENT PERMITTED BY APPLICABLE LAW, NEITHER WE NOR OUR
                              HOLDING, SUBSIDIARIES, AFFILIATES, PARTNERS, OR LICENSORS WILL BE RESPONSIBLE OR LIABLE
                              (WHETHER IN CONTRACT, TORT (INCLUDING NEGLIGENCE) OR OTHERWISE) UNDER ANY CIRCUMSTANCES
                              FOR ANY (a) INTERRUPTION OF BUSINESS; (b) ACCESS DELAYS OR ACCESS INTERRUPTIONS TO THE
                              PLATFORM; (c) DATA NON-DELIVERY, LOSS, THEFT, MISDELIVERY, CORRUPTION, DESTRUCTION OR
                              OTHER MODIFICATION; (d) LOSS OR DAMAGES OF ANY SORT INCURRED AS A RESULT OF DEALINGS WITH
                              OR THE PRESENCE OF OFF-WEBSITE LINKS ON THE PLATFORM; (e) VIRUSES, SYSTEM FAILURES OR
                              MALFUNCTIONS WHICH MAY OCCUR IN CONNECTION WITH YOUR USE OF THE PLATFORM, INCLUDING DURING
                              HYPERLINK TO OR FROM THIRD PARTY WEBSITES; (f) ANY INACCURACIES OR OMISSIONS IN CONTENT;
                              OR (g) EVENTS BEYOND THE REASONABLE CONTROL OF Shailesh Car & Bike PVT LTD. WE MAKE NO
                              REPRESENTATIONS OR WARRANTIES THAT DEFECTS OR ERRORS WILL BE CORRECTED.</p>
                         <h3><strong>FURTHER, TO THE FULLEST EXTENT PERMITTED BY LAW, NEITHER WE NOR OUR SUBSIDIARIES,
                                   AFFILIATES, PARTNERS, OR LICENSORS WILL BE LIABLE FOR ANY INDIRECT, SPECIAL,
                                   PUNITIVE, INCIDENTAL, OR CONSEQUENTIAL DAMAGES OF ANY KIND (INCLUDING LOST PROFITS)
                                   RELATED TO THE PLATFORM OR YOUR USE THEREOF REGARDLESS OF THE FORM OF ACTION WHETHER
                                   IN CONTRACT, TORT (INCLUDING NEGLIGENCE) OR OTHERWISE, EVEN IF WE HAVE BEEN ADVISED
                                   OF THE</strong></h3>
                         <h3><strong>POSSIBILITY OF SUCH DAMAGES AND IN NO EVENT SHALL OUR MAXIMUM AGGREGATE LIABILITY
                                   EXCEED RUPEES 50,000/-.</strong></h3>
                         <p>Shailesh Car & Bike PVT LTD MAKES NO CLAIM WITH RESPECT TO THE EFFICACY OF THE METHODOLOGY
                              AND THE OUTCOME OF THE PRODUCTS AND SERVICES MAY VARY FROM USER TO USER. THE USER USES THE
                              PRODUCT AND SERVICES AT THEIR OWN RISK.</p>
                         <h3><strong>YOU AGREE THAT NO CLAIMS OR ACTION ARISING OUT OF, OR RELATED TO, THE USE OF THE
                                   PLATFORM OR THESE TERMS AND CONDITIONS MAY BE BROUGHT BY YOU MORE THAN ONE (1) YEAR
                                   AFTER THE CAUSE OF ACTION RELATING TO SUCH CLAIM OR ACTION AROSE. IF YOU HAVE A
                                   DISPUTE WITH US OR ARE DISSATISFIED WITH THE PLATFORM, TERMINATION OF YOUR USE OF THE
                                   PLATFORM IS YOUR SOLE REMEDY. WE HAVE NO OTHER OBLIGATION, LIABILITY, OR
                                   RESPONSIBILITY TO YOU.</strong></h3>
                         <p>THIS LIMITATION OF LIABILITY REFLECTS AN INFORMED, VOLUNTARY ALLOCATION BETWEEN Shailesh Car
                              And Bike PVT LTD AND THE USERS OF THE RISKS (KNOWN AND UNKNOWN) THAT MAY EXIST IN
                              CONNECTION WITH THIS AGREEMENT AND/OR Shailesh Car & Bike PVT LTD HOST SERVICES AND/OR
                              ADD-ON SERVICES AND/OR ANY OTHER SERVICES PROVIDED BY Shailesh Car & Bike PVT LTD
                              THROUGH THE PLATFORM OR OTHERWISE. THE TERMS OF THIS CLAUSE SHALL SURVIVE ANY TERMINATION
                              OR EXPIRATION OF THIS AGREEMENT.</p>
                         <h3><strong>LINKS AND THIRD-PARTY SITES</strong></h3>
                         <p>References on the Platform to any names, marks, products or services of third parties or
                              hypertext links to third party sites or information are provided solely as a convenience
                              to you. This does not in any way constitute or imply Shailesh Car & Bike PVT LTD</p>
                         <p>endorsement, sponsorship or recommendation of the third party, information, product or
                              service or any association and relationship between Shailesh Car & Bike PVT LTD and
                              those third parties.</p>
                         <p>Shailesh Car & Bike PVT LTD is not responsible for the content of any third-party websites
                              and does not make any representations regarding the content or accuracy of material on
                              such sites. If you decide to link to any such third-party websites, you do so entirely at
                              your own risk. Shailesh Car & Bike PVT LTD does not assume any responsibility for
                              examining or evaluating the offerings of the off-websites pages or any other websites
                              linked from the Platform. We shall not be responsible for the actions, content, products,
                              or services of such pages and websites, including, without limitation, their privacy
                              policies and terms and conditions. You should carefully review the terms and conditions
                              and privacy policies of all off-website pages and other websites that you visit via the
                              Platform.</p>
                         <h3><strong>GOVERNING LAW AND JURISDICTION</strong></h3>
                         <p>This Agreement shall be construed in accordance with the applicable laws of India. For
                              proceedings arising therein the Courts at Jamnagar (Gujarat) shall have exclusive
                              jurisdiction.</p>
                         <p>Any dispute or difference either in interpretation or otherwise, of this Agreement and/or
                              the General Policies, shall be referred to an independent arbitrator who will be appointed
                              by Shailesh Car & Bike PVT LTD and his decision shall be final and binding on the
                              parties hereto. The above arbitration</p>
                         <p>shall be in accordance with the Arbitration and Conciliation Act, 1996 as amended from time
                              to time. The seat of arbitration shall be held in Jamnagar (Gujarat).</p>
                         <p>Without any prejudice to particulars listed in clause above, Shailesh Car & Bike PVT LTD
                              shall have the right to seek and obtain any injunctive, provisional or interim relief from
                              any court of competent jurisdiction to protect its Marks or other</p>
                         <h3><strong>PLATFORM SECURITY</strong></h3>
                         <p>You are prohibited from violating or attempting to violate the security of the Platform,
                              including, without limitation,</p>
                         <p>accessing data not intended for you or logging onto a server or an account which you are not
                              authorized to access; attempting to probe, scan or test the vulnerability of a system or
                              network or to breach security or authentication measures without proper authorization;
                              attempting to interfere with service to any other user, host or network, including,
                              without limitation, via means of submitting a virus to the Site, overloading, “flooding,”
                              “spamming,” “mail bombing” or “crashing;” sending unsolicited email, including promotions
                              and/or advertising of products or services; or forging any header or any part of the
                              header information in any email or newsgroup posting.</p>
                         <p>Violations of system or network security may result in civil or criminal liability</p>
                         <p>Shailesh Car & Bike PVT LTD is entitled to investigate occurrences that may involve such
                              violations and may involve, and cooperate with, law enforcement authorities in prosecuting
                              users who are involved in such violations. You agree not to use any device, software or
                              routine to interfere or attempt to interfere with the proper working of the Platform or
                              any activity being conducted on the Platform. You agree, further, not to use or attempt to
                              use any engine, software, tool, agent or other device or mechanism (including without
                              limitation browsers, spiders, robots, avatars or intelligent agents) to navigate or search
                              this Site other than the search engine and search agents available from Shailesh Car And
                              Bike PVT LTD on the Platform and other than generally available third party web browsers
                              (e.g., Netscape Navigator, Microsoft Explorer)</p>
                         <h3><strong>SEVERABILITY</strong></h3>
                         <p>If any part of this Agreement is determined to be invalid or unenforceable pursuant to
                              applicable law including or be so held by any applicable arbitral award or court decision,
                              but not limited to, the warranty disclaimers and liability limitations set forth above,
                              then such unenforceability or invalidity shall not render the Agreement unenforceable or
                              invalid as a whole but invalid or unenforceable provision will be deemed to be superseded
                              by a valid, enforceable provision that most closely matches the intent of the original
                              provision and the remainder of the Agreement shall continue to be in effect.</p>
                         <h3><strong>ENTIRE AGREEMENT</strong></h3>
                         <p>Unless otherwise specified herein, the General Policies constitutes the entire agreement
                              between you and Shailesh Car & Bike PVT LTD with respect to the Platform and the
                              Shailesh Car & Bike PVT LTD Host Services and it supersedes all prior or contemporaneous
                              communications and proposals, whether electronic, oral or written.</p>
                         <h3><strong>CORRESPONDENCE ADDRESS/ NOTICES</strong></h3>
                         <p>Unless specifically provided otherwise, any notice or demands required to be given herein
                              shall be given to the parties hereto in writing and by either Registered Post Acknowledged
                              Due, e-mail or by hand delivery at the addresses as mentioned below:</p>
                         <p>Shailesh Car & Bike PVT LTD India Shop no.5 Dwarkesh Complex, below Shivhari hotel, near
                              smarpan overbridge, Jamnagar 361006 Email: info@velriders Contact No: 9909927077
                              Communication generated by Shailesh Car & Bike PVT LTD on the users’ mobile number will
                              be deemed adequate service of notice / electronic record to the maximum extent permitted
                              under any applicable law.</p>
                         <h3><strong>WAIVER</strong></h3>
                         <p>Our failure to require your performance of any provision hereof shall not affect our full
                              right to require such performance at any time thereafter, nor shall our waiver of a breach
                              of any provision hereof be taken or held to be a waiver of the provision itself.</p>
                         <h3><strong>ASSIGNMENT</strong></h3>
                         <p>The users shall not be entitled to assign (in whole or in part) this Agreement or any of
                              their rights or obligations hereunder, without prior written consent of Shailesh Car And
                              Bike PVT LTD, which consent may be given at Shailesh Car & Bike PVT LTD’s own
                              discretion. Shailesh Car & Bike PVT LTD shall have the right to assign (in whole or in
                              part) this Agreement, or obligations of Shailesh Car & Bike PVT LTD. In such an event,
                              the users shall perform their respective obligations under or pursuant to this Agreement
                              qua such assignee.</p>
                         <h3><strong>FORCE MAJEURE</strong></h3>
                         <p>This Agreement and its performance by Shailesh Car & Bike PVT LTD or the users shall be
                              subject to force majeure. If performance of any service or obligation under the terms and
                              conditions of the General Policies, including this Agreement or other third parties in
                              fulfillment of transaction (for e.g. home deliveries of vehicles, payment gateways etc.)
                              are, prevented, restricted, delayed or interfered with by reason of labor disputes,
                              strikes, acts of God, floods, lightning, severe weather, shortages of materials,
                              rationing, utility or communication failures,</p>
                         <p>earthquakes, war, revolution, acts of terrorism, civil commotion, acts of public enemies,
                              blockade, pandemic, epidemic, lockdown, embargo or any law, order, proclamation,
                              regulation, ordinance, demand or requirement having legal effect of any government or any
                              judicial authority or representative of any such government, or any other act whatsoever,
                              whether similar or dissimilar to those referred to in this Clause, which are beyond the
                              reasonable commercial control of Shailesh Car & Bike PVT LTD or its third parties
                              performing such services as sub-contractor to Shailesh Car & Bike PVT LTD and could not
                              have been prevented by reasonable precautions (each, a "Force Majeure Event"), then
                              Shailesh Car & Bike PVT LTD shall be excused from such performance to the extent of and
                              during the period of such Force Majeure Event. For the avoidance of doubt, a Force Majeure
                              Event shall exclude any event that a party could reasonably have prevented by testing,
                              work-around, or other exercise of diligence. If the period of non-performance exceeds 60
                              days from the receipt of written notice of the Force Majeure Event, either Shailesh Car
                              And Bike PVT LTD or the user may by giving written notice terminate the Agreement</p>
                         <h3><strong>GENERAL</strong></h3>
                         <p>Nothing contained in this Agreement and/or General Policies shall be construed as creating
                              any agency, partnership, or other form of joint enterprise between Shailesh Car & Bike
                              PVT LTD and the users</p>
                         <p>If you have any questions regarding this Agreement, please email us at <a
                                   href="mailto:info@velriders.com">info@velriders.com</a></p>
                    </div>
               </div>
          </div>
     </section>
     @endif
@endsection