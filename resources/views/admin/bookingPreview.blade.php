@extends('templates.admin')

@section('page-title', 'Rental Booking Details')

@section('content')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rental Booking Details</title>
    <a href="/admin/bookings" class="btn btn-primary mb-3">Back</a>

    @haspermission('booking-history-indetails', 'admin_web')
    <div class="row mb-3">
        <div class="col-md-3">
            <input type="hidden" id="bookingId" value="{{$rentalBookingDetails['booking_id']}}">
            <select id="vehicle" name="vehicle" class="form-control">
                <option value="">-- Update Vehicle --</option>
                @if(is_countable($vehicles) && count($vehicles) > 0)
                    @foreach($vehicles as $key => $val)
                        <option value="{{$val->vehicle_id}}">{{$val->vehicle_id}} - {{$val->vehicle_name}} - {{$val->license_plate}} @if($val->is_deleted == 1)({{'DELETED'}})@else {{''}}@endif</option>
                    @endforeach
                @endif
            </select>
        </div>
        <div class="col-md-2"><button type="button" class="btn btn-primary" id="updateVehicle">Update Vehicle</button></div>
    </div>
    @endhaspermission
    
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

    @haspermission('booking-history-indetails', 'admin_web')
    <div class="card p-3">
        <h4><b>Booking Operations</b></h4><hr/>
        <div class="row mt-3">
            <!-- START JOURNEY -->
            <div class="col-md-3">
                @if($rentalBookingDetails->admin_button_visibility['start_journey_button'] == 1 && $returnDate != '' && $currentDate < $returnDate)
                    <button type="button" class="btn btn-info" data-toggle="modal" data-target="#startJourneyModal">Start Journey</button>
                @elseif(strtolower($rentalBookingDetails->status) == 'running')
                    <button type="button" class="btn btn-success">Journey Started</button>
                @else
                    <button type="button" class="btn btn-secondary" id="startJourneyForcefullyBtn">You can't Start the Journey</button> 
                    @if(strtolower($rentalBookingDetails->status) != 'completed')
                        <label id="startJourneyForcefullyLbl" title="You can start the journey only if this user's Government ID & Driver's License (DL) documents are approved, AND the booking pickup time is within 30 minutes of the current time AND Customer email must be verified"><i class="fa fa-info-circle fa-2x"></i></label>
                    @endif
                @endif

                @if(strtolower($rentalBookingDetails->status) != 'running' && strtolower($rentalBookingDetails->status) != 'completed' && strtolower($rentalBookingDetails->status) != 'pending')
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" value="" id="allowStartJourney">
                    <label class="form-check-label" for="allowStartJourney">
                        <b class="text-danger">Forcefully allow Start Journey</b>
                    </label>
                </div>
                @endif
            </div>
            <!-- EXTEND BOOKING -->
            <div class="col-md-3">
            @if($rentalBookingDetails->admin_button_visibility['end_journey_button'] == 1)
                <button type="button" class="btn btn-info" data-toggle="modal" data-target="#extendBookingModal">Extend Booking</button>
            @else
                <button type="button" class="btn btn-secondary" id="forcefullyExtensionBtn">You can't able to Extend Booking this booking </button> 
                @if(strtolower($rentalBookingDetails->status) != 'completed')
                    <label title="You can extend the booking only if the user has started their journey and uploaded up to 5 images of the journey's start AND Return date must be greater than current date" id="forcefullyExtensionLbl"><i class="fa fa-info-circle fa-2x"></i></label>
                @endif
            @endif

            @if($rentalBookingDetails->admin_button_visibility['end_journey_button'] != 1 && strtolower($rentalBookingDetails->status) != 'completed' && strtolower($rentalBookingDetails->status) != 'confirmed' && strtolower($rentalBookingDetails->status) != 'pending')
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" value="" id="allowExtension">
                    <label class="form-check-label" for="allowExtension">
                        <b class="text-danger">Forcefully allow Extension</b>
                    </label>
                </div>
            @endif
            </div>
            <!-- END JOURNEY -->
            <div class="col-md-3">
                @if($rentalBookingDetails->admin_button_visibility['end_journey_button'] == 1)
                    <button type="button" class="btn btn-info" data-toggle="modal" data-target="#endJourneyModal">End Journey</button>
                @elseif(strtolower($rentalBookingDetails->status) == 'completed')
                    <button type="button" class="btn btn-success">Journey Ended</button>
                @else
                    <button type="button" class="btn btn-secondary" id="forcefullyEndJourneyBtn">You can't End this Journey </button>
                    @if(strtolower($rentalBookingDetails->status) != 'completed')
                        <label title="You can End this Journey only if the user has started their journey and uploaded up to 5 images of the journey's start" id="forcefullyEndJourneyLbl"><i class="fa fa-info-circle fa-2x"></i></label>
                    @endif
                @endif

                @if($rentalBookingDetails->admin_button_visibility['end_journey_button'] != 1 && strtolower($rentalBookingDetails->status) != 'completed' && strtolower($rentalBookingDetails->status) != 'confirmed' && strtolower($rentalBookingDetails->status) != 'pending')
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" value="" id="allowEndJourney">
                    <label class="form-check-label" for="allowEndJourney">
                        <b class="text-danger">Forcefully allow End Journey</b>
                    </label>
                </div>
                @endif
            </div>
            <!-- CANCEL BOOKING -->
            <div class="col-md-3">
                @if(strtolower($rentalBookingDetails['status']) == 'confirmed' && $rentalBookingDetails['pickup_date'] > now()->format('Y-m-d H:i'))
                    <button type="button" class="btn btn-info cancelModal">Cancel Booking</button>
                @elseif(strtolower($rentalBookingDetails['status']) == 'canceled')
                    <button type="button" class="btn btn-secondary">This booking is Canceled </button>
                @else
                    <button type="button" class="btn btn-secondary" id="forcefullyCancelBtn" data-id="{{$rentalBookingDetails['booking_id']}}">You can't Cancel this Booking </button>
                    <label title="You can Cancel this booking if Booking status is Confirmed and Pickup date is greater than the Current Date" id="forcefullyCancelLbl"><i class="fa fa-info-circle fa-2x"></i></label>
                @endif
                @if(strtolower($rentalBookingDetails['status']) != 'pending' && strtolower($rentalBookingDetails['status']) != 'no show' && strtolower($rentalBookingDetails['status']) != 'completed' && strtolower($rentalBookingDetails['status']) != 'failed' && strtolower($rentalBookingDetails['status']) != 'canceled')
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" value="" id="allowCancelBooking">
                    <label class="form-check-label" for="allowCancelBooking">
                        <b class="text-danger">Forcefully allow Cancel Booking</b>
                    </label>
                </div>
                @endif
            </div>
        </div>
        <hr/>
        @php
            generateCustomerPdf($rentalBookingDetails->customer_id, $rentalBookingDetails->booking_id);
            $fileName = 'customer_agreements_'.$rentalBookingDetails->customer_id.'_'.$rentalBookingDetails->booking_id.'.pdf';
            $filePath = public_path('customer_aggrements/' . $fileName);
            $fileUrl = asset('customer_aggrements/' . $fileName);
        @endphp
        @if(file_exists($filePath))
            <h5>
                <a href="{{ $fileUrl }}" download class="font-weight-bold" target="_blank">Download Customer Agreement PDF</a>
            </h5>
        @else
            <h5 class="text-danger">
                PDF NOT FOUND
            </h5>
        @endif
    </div>
    @endhaspermission
</head>
<style>
    .card-header{
        background-color: #e4e6e7;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #444;
        line-height: 20px !important;
    }
</style>
<body>
    <div>
        <div class="card">
            <div class="card-header">
                <strong>Booking ID:</strong> {{ $rentalBookingDetails['booking_id'] }} <br>
                <strong>Booking Status:</strong> <span style="margin-left: 10px;">{{ $rentalBookingDetails['status'] }}</span><br>
                <strong>Booking Pickup Date:</strong> {{ $rentalBookingDetails['pickup_date'] }} <br>
                <strong>Booking Return Date:</strong> {{ $rentalBookingDetails['return_date'] }} <br>
                <strong>Booking Actual Pickup Date:</strong> {{ $rentalBookingDetails['start_datetime'] }} <br>
                <strong>Booking Actual Return Date:</strong> {{ $rentalBookingDetails['end_datetime'] }} <br>
                <strong>Rental Duration:</strong> {{ $rentalBookingDetails['rental_duration_minutes'] }} minutes <br>
                <strong>Total Price:</strong> ${{ $rentalBookingDetails['total_cost'] }} <br>

                <strong>Unlimited Kilometers:</strong> {{ $rentalBookingDetails->unlimited_kms ?? 0 }} <br>
                <strong>Vehicle City:</strong> 
                @if($rentalBookingDetails->vehicle->location)
                    {{ $rentalBookingDetails->vehicle->location['name'] ?? '' }} 
                @endif
                <br>
                <strong>Booking Creation Date:</strong> {{ date('d-m-Y H:i', strtotime($rentalBookingDetails->created_at)) }} <br>
                @if($rentalBookingDetails->penalty_details)
                    @php $penalty = json_decode($rentalBookingDetails->penalty_details); @endphp
                    <strong>Penalty Details:</strong> {{ $penalty->penalty_details ?? '-'}} <br>
                @endif                
                <strong>Start Journey Button:</strong> {{ $rentalBookingDetails['start_journey_button'] }} <br>
                <strong>End Journey Button:</strong> {{ $rentalBookingDetails['end_journey_button'] }} <br>
                {{--<strong>Upload Images:</strong> {{ $rentalBookingDetails['upload_images'] }} <br>
                @if(strtolower($rentalBookingDetails->status) == 'completed')
                    <strong>
                        @php 
                            $rStatus = 'Refund'; 
                            $buttonStatus = 'btn btn-primary refund-payment';
                        @endphp
                        @if($rentalBookingDetails->cDetails)
                            @if(isset($rentalBookingDetails->cDetails['Refundable Deposit']) && $rentalBookingDetails->cDetails['Refundable Deposit'] == 0)
                                $buttonStatus = 'btn btn-secondary';
                                $rStatus = 'Refunded';    
                            @endif
                        @endif
                        <a class="{{$buttonStatus}}" id="refundBtn_{{$rentalBookingDetails->booking_id}}" data-id="{{$rentalBookingDetails->booking_id}}" href="javascript:void(0);">{{$rStatus}}</a>
                        <a class="btn btn-primary" id="processBtn_{{$rentalBookingDetails->booking_id}}" href="javascript:void(0);" style="display: none;">Processing...</a>
                    </strong><br/>
                @endif --}}  
                <hr/>
                @if(isset($rentalBookingDetails->customer))
                <h3><strong>Customer Details</strong></h3>
                    <strong>Name: </strong> @isset($rentalBookingDetails->customer->firstname){{ $rentalBookingDetails->customer->firstname }}@endisset @isset($rentalBookingDetails->customer->lastname){{ $rentalBookingDetails->customer->lastname }}@endisset<br>
                    <strong>Email: </strong> @isset($rentalBookingDetails->customer->email){{ $rentalBookingDetails->customer->email }}@endisset <br>
                    <strong>Mobile Number: </strong> @isset($rentalBookingDetails->customer->mobile_number){{ $rentalBookingDetails->customer->mobile_number }}@endisset <br>
                    <strong>D.O.B.: </strong> @isset($rentalBookingDetails->customer->dob){{ date('d-m-Y', strtotime($rentalBookingDetails->customer->dob))}}@endisset <br>
                    <strong>Billing Address: </strong> @isset($rentalBookingDetails->customer->billing_address){{ $rentalBookingDetails->customer->billing_address }}@endisset<br>
                    <strong>Shipping Address: </strong> @isset($rentalBookingDetails->customer->shipping_address){{ $rentalBookingDetails->customer->shipping_address }}@endisset <br>
                    <strong class="text-danger">Email Verification Status: </strong> 
                    <span class="text-danger">
                    @if(isset($rentalBookingDetails->customer->email_verified_at) && $rentalBookingDetails->customer->email_verified_at != '')
                        {{'Verified'}}
                    @else
                        {{'Not Verified'}}
                    @endisset 
                    </span>
                <hr/>
                @endif
                @if(isset($rentalBookingDetails->vehicle))
                <h3><strong>Vehicle Details</strong></h3>
                    <strong>Vehicle Id: </strong> @isset($rentalBookingDetails->vehicle->vehicle_id){{ $rentalBookingDetails->vehicle->vehicle_id }}@endisset <br>
                    <strong>Color: </strong> @isset($rentalBookingDetails->vehicle->color){{ $rentalBookingDetails->vehicle->color }}@endisset <br>
                    <strong>Rental Price: </strong>
                    @isset($rentalBookingDetails->vehicle->rental_price)
                        @php
                            $checkOffer = \App\Models\OfferDate::where('vehicle_id', $rentalBookingDetails->vehicle->vehicle_id)->get();
                            $rentalPrice = $rentalBookingDetails->vehicle->rental_price;
                            if(is_countable($checkOffer) && count($checkOffer) > 0){
                                $rentalPrice = getRentalPrice($rentalBookingDetails->vehicle->rental_price, $rentalBookingDetails->vehicle->vehicle_id);
                            }
                        @endphp
                        {{$rentalPrice}}
                    @endisset 
                    <br/>
                    <strong>Branch: </strong> @isset($rentalBookingDetails->vehicle->branch->name){{ $rentalBookingDetails->vehicle->branch->name }}@endisset<br>
                    <strong>Type: </strong>  @isset($rentalBookingDetails->vehicle->model->category->vehicleType->name){{ $rentalBookingDetails->vehicle->model->category->vehicleType->name }}@endisset<br>
                    <strong>Manufacturer: </strong> @isset($rentalBookingDetails->vehicle->model->manufacturer->name){{ $rentalBookingDetails->vehicle->model->manufacturer->name }}@endisset<br>
                    <strong>Model: </strong> @isset($rentalBookingDetails->vehicle->model->name){{ $rentalBookingDetails->vehicle->model->name }}@endisset<br>
                    <strong>Category: </strong> @isset($rentalBookingDetails->vehicle->model->category->name){{ $rentalBookingDetails->vehicle->model->category->name }}@endisset<br>
                    <strong>License Plate: </strong> @isset($rentalBookingDetails->vehicle->license_plate){{ $rentalBookingDetails->vehicle->license_plate }}@endisset<br>
                    <strong>Extra KM. Rate: </strong> @isset($rentalBookingDetails->vehicle->extra_km_rate){{ $rentalBookingDetails->vehicle->extra_km_rate }}@endisset<br>
                    <strong>Extra Hour Rate: </strong> @isset($rentalBookingDetails->vehicle->extra_hour_rate){{ $rentalBookingDetails->vehicle->extra_hour_rate }}@endisset<br>
                    <strong>Description: </strong> @isset($rentalBookingDetails->vehicle->description){{ $rentalBookingDetails->vehicle->description }}@endisset<br>
                <hr/>
                @endif
                <h3><strong>Calculation Details</strong></h3>
                    <th>
                        @php $finalPrice = 0; $updatedKey = ''; @endphp
                        @if(is_countable($rentalBookingDetails->price_summary) && count($rentalBookingDetails->price_summary) > 0)
                            @foreach ($rentalBookingDetails->price_summary as $key => $item)
                                @php
                                    if(strtolower($item['key']) == 'final amount'){
                                        $cleanedPrice = str_replace(['₹', ','], '', $item['value']);
                                        $finalPrice += $cleanedPrice;

                                    }
                                    if(strtolower($item['key']) == 'refundable deposit used'){
                                        $cleanedPrice = str_replace(['₹', ','], '', $item['value']);
                                        $finalPrice += $cleanedPrice;
                                    }

                                    if($key == 0){
                                        $position = strpos($item['key'], "Amount");
                                        if ($position !== false) {
                                            $position += strlen("Amount");
                                            $firstPart = 'Trip Amount';
                                            $secondPart = substr($item['key'], $position);
                                            $secondPart = str_replace('From', '<br/>From', $secondPart);
                                            $updatedKey = $firstPart.'<br/>'.$secondPart;
                                        }
                                    }
                                    else{
                                        $updatedKey = $item['key'];
                                    }
                                @endphp
                                @if(strtolower($item['key']) == 'final amount')
                                    <strong>Final Price: </strong>₹ {{round($finalPrice)}} <br/>
                               @else
                                    <strong>{!! $updatedKey !!}: </strong> {{ $item['value'] }} <br/>
                               @endif
                            @endforeach
                        @endif
                        
                        {{-- @foreach ($rentalBookingDetails['calculation_details']['price_summary'] as $item)
                            <li><strong>{{ $item['key'] }}:</strong> ${{ $item['value'] }}, <strong>Color:</strong> {{ $item['color'] }}, <strong>Style:</strong> {{ $item['style'] }}</li>
                        @endforeach --}}
                    </th>
                <hr>
                <strong>Invoice Pdf:</strong> 
                <span style="margin-left: 10px;">
                    @if(strtolower($rentalBookingDetails->status) == 'completed')
                        <a href="{{ route('admin.rental-booking.invoice', ['customer_id' => $rentalBookingDetails->customer_id, 'booking_id' => $rentalBookingDetails->booking_id]) }}" download><i class="fa fa-download" aria-hidden="true"></i></a>
                    @else
                        <a href="javascript:void(0);">You can download after Status becomes Completed</a>
                    @endif
                </span><br>
                <strong>Summary Pdf:</strong> 
                <span style="margin-left: 10px;">
                    <a href="{{ route('admin.rental-booking.summary', ['customer_id' => $rentalBookingDetails->customer_id, 'booking_id' => $rentalBookingDetails->booking_id]) }}" download><i class="fa fa-download" aria-hidden="true"></i></a>
                </span><br/>
                <strong>Rental Type:</strong> <span style="margin-left: 10px;">{{ $rentalBookingDetails['rental_type'] }}</span><br>
                <strong>Start Kilometers:</strong> <span style="margin-left: 10px;">{{ $rentalBookingDetails['start_kilometers'] }}</span><br>
                <strong>End Kilometers:</strong> <span style="margin-left: 10px;">{{ $rentalBookingDetails['end_kilometers'] }}</span>
            </div>
        </div>

        @if(isset($rentalBookingDetails->payments) && is_countable($rentalBookingDetails->payments) && count($rentalBookingDetails->payments) > 0)
        <div class="card">
            <input type="hidden" value="{{$rentalBookingDetails->booking_id}}" id="bId">
            <div class="card-header">
                <h3>Payment History</h3>
                <a href="javascript:void(0);" class="btn btn-success" id="removeHistory">Remove Razorpay History Table</a>
            </div>
           <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Payment Id</th>
                            <th>Razorpay Order Id</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment Date</th>
                            <th>View History <br/> From Razorpay</th>
                        </tr>
                    </thead>
                    <tbody class="table-data">
                        @foreach($rentalBookingDetails->payments as $val)
                            <tr>
                                <td>{{$val->payment_id ?? ''}}</td>
                                <td>{{$val->razorpay_order_id ?? ''}}</td>
                                <td>{{$val->amount ?? ''}}</td>
                                <td>{{strtoupper($val->status) ?? ''}}</td>
                                <td>@isset($val->created_at){{date('d-m-Y', strtotime($val->created_at))}}@endisset</td>
                                <td><a href="javascript:void(0);" class="viewRazorpayHistory" id="rHistory_{{$val->payment_id}}" data-id="{{$val->payment_id}}">Show</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                 <table class="table table-bordered table-dark table-striped mt-3" id="razorpayTable" hidden>
                    <thead>
                        <tr><th colspan="4">Showing For Payment Id - <span id="bookingIdText"></span></th></tr>
                        <tr>
                            <th>Razorpay Order Id</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment Date</th>
                        </tr>
                    </thead>
                    <tbody class="table-data" id="historyData">
                       
                    </tbody>
                </table>
            </div>
        </div>
        
        @endif

        @if($rentalBookingDetails['start_images']->isNotEmpty())
            <div class="card">
                <div class="card-header">
                    <h3>Start Journey Images</h3>
                    <div class="row">
                        @foreach ($rentalBookingDetails['start_images'] as $image)
                            <div class="col-lg-3">
                                <img src="{{ $image['image_url'] }}" alt="Start Image" height="210" width="310">
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @if($rentalBookingDetails['end_images']->isNotEmpty())
            <div class="card">
                <div class="card-header">
                    <h3>End Journey Images</h3>
                    <div class="row">
                        @foreach ($rentalBookingDetails['end_images'] as $image)
                            <div class="col-lg-3">
                                <img src="{{ $image['image_url'] }}" alt="End Image" height="210" width="310">
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>

<!-- EXTEND MODAL -->
<div class="modal fade modal-ld" id="extendBookingModal" tabindex="-1" role="dialog" aria-labelledby="extendBookingModalTitle" aria-hidden="true" data-keyboard="false" data-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="extendBookingModalLongTitle">Extend Booking</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <input type="hidden" value="{{$rentalBookingDetails->return_date}}" id="extend_return_date">
        <input type="hidden" value="{{$rentalBookingDetails->booking_id}}" id="extend_booking_id">
        <div class="col-md-4 mb-3">
            <label for="forceExtension">Allow Forcefully Extension without checking any condition</label>
            <input type="checkbox" id="forceExtension" name="forceExtension" class="form-control">
        </div>
        <div class="col-md-12 mb-3">
            <label id="">Existing Return Date Time</label>
            <input type="text" value="{{date('d-m-Y H:i', strtotime($rentalBookingDetails->return_date))}}" class="form-control" readonly />
        </div>
        <div class="col-md-12">
            <label id="">New Date To Extend</label> <span class="text-danger">*</span>
            <div class="input-group date" data-target-input="nearest">
                <input type="text" id="extend_to_date_time" class="form-control datetimepicker-input" data-target="#extend_to_date_time" placeholder="Select To Date" onchange="disaplyExtendSummary(0)"/ autocomplete="off">
                <div class="input-group-append" data-target="#extend_to_date_time" data-toggle="datetimepicker">
                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-12 mt-3" id="couponField">
            <label for="extend_coupon_code">Apply Coupon Code <a class="text-info" data-toggle="modal" data-target="#viewCoupons">View Available Coupons</a></label>
            <input type="text" id="extend_coupon_code" class="form-control" placeholder="Enter Coupon Code">
            <!-- <button type="button" class="btn btn-primary mt-3">Apply</button> -->
        </div>
        <div class="col-md-12 mt-3" id="extendPriceSummaryView">
            <hr/>
            <h5><b>Price Summary</b></h5>
            -------------------------------------------
            <div class="row">
                <div class="col-md-6">Trip Amount - 
                    <b>
                        <a href="javascript:void(0);"><label id="extend_trip_amt_lbl"></label></a>
                        <input type="text" name="extend_trip_amt_txt" value="0" id="extend_trip_amt_txt" style="width: 100px; margin-left: 10px;margin:3px">&nbsp;
                        <span id="extend_trip_amt_span"><i class="fa fa-check fa-lg text-success"></i></span>
                    </b>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    Tax Amount - <b><label id="extend_tax_amt"></label></b>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    Convenience Fee - <b><label id="extend_convenience_fee"></label></b>
                </div>
            </div>
            <div class="row" id="coupon">
                <div class="col-md-6">
                    Coupon Discount - <b><label id="extend_coupon_discount"></label></b>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    Total Amount - <b><label id="extend_total_amt"></label></b>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    Refundable Amount - <b><label id="extend_refundable_amt"></label></b>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    Final Amount - <b><label id="extend_final_amt"></label></b>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    Warning - <b><label id="extend_warning_text"></label></b>
                </div>
            </div>
        </div>

      </div>
      <div class="modal-footer">
        <!-- <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button> -->
        <button type="button" class="btn btn-primary extendBooking">Extend Booking</button>
      </div>
    </div>
  </div>
</div>

<!-- COUPONS MODAL -->
<div class="modal fade" id="viewCoupons" tabindex="-1" role="dialog" aria-labelledby="viewCouponsModalTitle" aria-hidden="true" data-keyboard="false" data-backdrop="static">
  <div class="modal-dialog modal-dialog-centered" role="document">
     <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="extendBookingModalLongTitle">All Coupons</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="modal-body"> 
            <div class="container">
              <div class="row" id="availCopons">
                <!-- Available Coupons will append here -->
              </div>
            </div>
        </div>
    </div>
  </div>
</div>

<!-- START JOURNEY BUTTON -->
<div class="modal fade modal-ld" id="startJourneyModal" tabindex="-1" role="dialog" aria-labelledby="startJourneyModalTitle" aria-hidden="true" data-keyboard="false" data-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Start Journey</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          </div>
          <form id="start-jouney-form" name="start-jouney-form" enctype="multipart/form-data" method="POST" action="{{route('admin.store.start-journey-details')}}">
            @csrf
             <input type="hidden" name="booking_id" value="{{$rentalBookingDetails->booking_id}}">
             <input type="hidden" name="type" value="start">
              <div class="modal-body">
                <div class="col-md-6 mb-3">
                    <label id="">Enter Km Driven</label> <span class="text-danger">*</span>
                    <input type="text" value="" id="start_km" name="start_km" class="form-control" placeholder="Enter Km Driven" />
                </div>
                <div class="col-md-6 mb-3">
                    <input type="hidden" id="startImgCount" value="{{count($rentalBookingDetails->StartImages)}}">
                    <label id="">Before Pick-up Images</label> <span class="text-danger">*</span>
                    <input type="file" id="start_journey_imgs" name="start_journey_imgs[]" class="form-control" multiple>
                </div>
                @if($rentalBookingDetails && is_countable($rentalBookingDetails->StartImages) && count($rentalBookingDetails->StartImages) > 0)
                <div class="row">
                    @foreach($rentalBookingDetails->StartImages as $key => $val)
                        <div class="col-md-4 mb-3">
                            <div class="image-display">
                                <img src="{{ $val->image_url }}" alt="Start Journey" style="width: 250px; height: 175px; border: 1px solid #ccc; border-radius: 5px; padding: 5px;" class="img-thumbnail m-2">
                            </div>
                        </div>
                    @endforeach
                </div>
                @endif
              </div>
              <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Start Booking</button>
              </div>
          </form>
      </div>
    </div>
</div>

<!-- END JOURNEY BUTTON -->
<div class="modal fade modal-ld" id="endJourneyModal" tabindex="-1" role="dialog" aria-labelledby="endJourneyModalTitle" aria-hidden="true" data-keyboard="false" data-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">End Journey</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          </div>
          <form id="end-jouney-form" name="end-jouney-form" enctype="multipart/form-data" method="POST" action="{{route('admin.store.end-journey-details')}}">
            @csrf
            <input type="hidden" name="booking_id" value="{{$rentalBookingDetails->booking_id}}">
             <input type="hidden" name="type" value="end">
              <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="end_km">Enter Km Driven</label> <span class="text-danger">*</span>
                        <input type="number" id="end_km" name="end_km" class="form-control" placeholder="Enter Km Driven" value="{{$rentalBookingDetails->end_kilometers}}" />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <input type="hidden" id="endImgCount" value="{{count($rentalBookingDetails->EndImages)}}">
                        <label id="end_journey_imgs">Before Return Images</label> <span class="text-danger">*</span>
                        <input type="file" id="end_journey_imgs" name="end_journey_imgs[]" class="form-control" multiple>
                    </div>
                </div>
                @if($rentalBookingDetails && is_countable($rentalBookingDetails->EndImages) && count($rentalBookingDetails->EndImages) > 0)
                <div class="row">
                    @foreach($rentalBookingDetails->EndImages as $key => $val)
                        <div class="col-md-4 mb-3">
                            <div class="image-display">
                                <img src="{{ $val->image_url }}" alt="End Journey" style="width: 250px; height: 175px; border: 1px solid #ccc; border-radius: 5px; padding: 5px;" class="img-thumbnail m-2">
                            </div>
                        </div>
                    @endforeach
                </div>
                @endif
                <div id="penaltyDetails">
                    <div class="row">
                        <input type="hidden" class="form-control" id="admin_penalty_id" name="admin_penalty_id">
                        <div class="col-md-4"><label>Admin Penalty</label>
                            <input type="number" class="form-control" id="admin_penalty" name="admin_penalty" @if($bookingTransaction != '' && isset($bookingTransaction->additional_charges))value="{{$bookingTransaction->additional_charges}}"@else value=""@endif>
                        </div>
                        <div class="col-md-4"><label>Exceed KM. Limit</label>
                            <input type="number" class="form-control" id="exceed_km_limit" name="exceed_km_limit" @if($bookingTransaction != '' && isset($bookingTransaction->exceeded_km_limit))value="{{$bookingTransaction->exceeded_km_limit}}"@else value=""@endif>
                        </div>
                        <div class="col-md-4"><label>Exceed Hours Limit</label>
                            <input type="number" class="form-control" id="exceed_hours_limit" name="exceed_hours_limit" @if($bookingTransaction != '' && isset($bookingTransaction->late_return))value="{{$bookingTransaction->late_return}}"@else value=""@endif>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12"><label>Admin Penalty Info</label>
                            <input type="text" class="form-control" id="admin_penalty_info" name="admin_penalty_info" @if($bookingTransaction != '' && isset($bookingTransaction->additional_charges_info))value="{{$bookingTransaction->additional_charges_info}}"@else value=""@endif>
                        </div>
                    </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="submit" class="btn btn-primary" id="endBooking">End this Booking</button>
              </div>
          </form>
      </div>
    </div>
</div>

<!-- CANCEL BOOKING -->
<div class="modal fade modal-ld" id="cancelModal" tabindex="-1" role="dialog" aria-labelledby="cancelModalTitle" aria-hidden="true" data-keyboard="false" data-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Cancel Booking</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          </div>
              <div class="modal-body">
                <div class="row">
                    <div class="col-md-12 mb-3" id="cancelBookingDetails">
                        
                    </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="confirmCancelBooking">Confirm Cancel Booking</button>
              </div>
          </form>
      </div>
    </div>
</div>

</body>
</html>

@push('scripts')
    <script src="{{ asset('all_js/admin_js/booking.js') }}"></script>
@endpush

@endsection
