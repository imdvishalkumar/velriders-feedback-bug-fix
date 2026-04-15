@extends('templates.admin')

@section('page-title')
    Add Rental Booking
    @if (session('success'))
        <div id="success-message" class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div id="success-message" class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
@endsection

<style>
.select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #444;
    line-height: 20px !important;
}
</style>

@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Add Booking</h3>
                        </div>
                        @if ($errors->any())
                            <div class="alert alert-danger" id="error-message">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif  
                        <form class="card-body" action="{{ route('admin.booking-insert') }}" id="booking-form" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="customer">Customer</label> <span class="error">*</span>
                                        <select id="customer" class="form-control custom-select pricesummary" name="customer">
                                            <option selected disabled value="">Select one</option>
                                            @if(isset($customers) && is_countable($customers) && count($customers) > 0)
                                                @foreach($customers as $key => $val)
                                                    <option value="{{$val->customer_id}}" @if(old('customer') == $val->customer_id){{'selected'}}@else{{''}}@endif>{{$val->customer_id}} ({{$val->firstname}} {{$val->lastname}})</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <span id="customer_error"></span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="vehicle">Vehicle</label> <span class="error">*</span>
                                        <select id="vehicle" class="form-control custom-select pricesummary" name="vehicle">
                                            <option selected disabled value="">Select one</option>
                                            @if(isset($vehicles) && is_countable($vehicles) && count($vehicles) > 0)
                                                @foreach($vehicles as $key => $val)
                                                    <option value="{{$val->vehicle_id}}" @if(old('vehicle') == $val->vehicle_id){{'selected'}}@else{{''}}@endif>{{$val->vehicle_id}} ({{$val->model->name}} - {{$val->license_plate}})</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <span id="vehicle_error"></span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="booking_start_date">Select From Date & Time</label>                 
                                        <!-- <input type="text" class="form-control pricesummary" name="booking_start_date" id="booking_start_date" placeholder="Select From Date Time" readonly> -->
                                        <div class="input-group date" data-target-input="nearest">
                                            <input type="text" id="booking_start_date" name="booking_start_date" class="form-control datetimepicker-input" onchange="dateEvent()" data-target="#booking_start_date" placeholder="Select From Date Time" autocomplete="off" value="{{old('booking_start_date')}}"/>
                                            <div class="input-group-append" data-target="#booking_start_date" data-toggle="datetimepicker">
                                                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                            </div>
                                        </div>
                                        <span id="from_error"></span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="booking_end_date">Select To Date & Time</label>                 
                                        <!-- <input type="text" class="form-control pricesummary" name="booking_end_date" id="booking_end_date" placeholder="Select To Date Time" readonly> -->
                                        <div class="input-group date" data-target-input="nearest">
                                            <input type="text" id="booking_end_date" name="booking_end_date" class="form-control datetimepicker-input" onchange="dateEvent()" data-target="#booking_end_date" placeholder="Select To Date Time" autocomplete="off" value="{{old('booking_end_date')}}"/>
                                            <div class="input-group-append" data-target="#booking_end_date" data-toggle="datetimepicker">
                                                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                            </div>
                                        </div>
                                        <span id="to_error"></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="priceSummaryView">
                                <hr/>
                                <h5><b>Price Summary</b></h5>
                                -------------------------------------------
                                <div class="row">
                                    <div class="col-md-6"> Trip Amount - 
                                        <b>
                                            <a href="javascript:void(0);"><label id="trip_amt_lbl"></label></a>
                                            <input type="text" name="trip_amt_txt" value="0" id="trip_amt_txt" style="width: 100px; margin-left: 10px;margin:3px">&nbsp;
                                            <span id="trip_amt_span"><i class="fa fa-check fa-lg text-success" aria-hidden="true"></i></span>
                                        </b>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        Tax Amount - <b><label id="tax_amt"></label></b>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        Convenience Fee - <b><label id="convenience_fee"></label></b>
                                    </div>
                                </div>
                                <div class="row" id="coupon">
                                    <div class="col-md-6">
                                        Coupon Discount - <b><label id="coupon_discount"></label></b>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        Total Amount - <b><label id="total_amt"></label></b>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        Refundable Amount - <b><label id="refundable_amt"></label></b>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        Final Amount - <b><label id="final_amt"></label></b>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        Warning - <b><label id="warning_text"></label></b>
                                    </div>
                                </div>
                            </div>
                            <hr/>
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="coupon_code">Enter Coupon Code</label>                 
                                        <input type="text" class="form-control" name="coupon_code" id="coupon_code" placeholder="Enter Coupon Code">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label for="payment_mode">Payment Mode</label> <span class="error">*</span>
                                    <select id="payment_mode" class="form-control custom-select" name="payment_mode">
                                        <option selected disabled value="">Select one</option>
                                        @php $paymentModes = config('global_values.payment_modes'); @endphp
                                        @if(isset($paymentModes) && is_countable($paymentModes) && count($paymentModes) > 0)
                                            @foreach($paymentModes as $key => $val)
                                                <option value="{{$val}}" @if(old('payment_mode') == $val){{'selected'}}@else{{''}}@endif>{{ucfirst($val)}}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="ref_number">Transaction Reference Number</label> <span class="error">*</span>
                                    <input type="text" class="form-control" name="ref_number" id="ref_number" placeholder="Enter Transaction Reference Number" value="{{old('ref_number')}}">
                                </div>
                                <div class="col-md-5">
                                    <label for="additional_note">Additional Notes</label>
                                    <textarea id="additional_note" class="form-control" name="additional_note" rows="1" value="{{old('additional_note')}}"></textarea>
                                </div>
                            </div>
                            <input class="form-check-input" type="hidden" id="unlimited_km" name="unlimited_km" value="0">
                            <!-- <div class="row mt-3">
                                <div class="col-md-3">
                                    <div class="form-check">
                                      <input class="form-check-input" type="checkbox" id="unlimited_km" name="unlimited_km" value="1">
                                      <label class="form-check-label" for="unlimited_km">
                                        Unlimited Kilomenters
                                      </label>
                                    </div>
                                </div>
                            </div> -->

                            <button type="submit" class="btn btn-primary mt-5" id="addBooking">Add Booking</button>
                            <a href="{{route('admin.bookings')}}" class="btn btn-danger mt-5">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

<!-- UNLIMITED KM. POPUP -->
<div class="modal fade modal-ld" id="unlimitedKmPopup" tabindex="-1" role="dialog" data-keyboard="false" data-backdrop="static">
    <div class="modal-dialog modal-md modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Unlimited KM.</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="unlimited_km_popup" name="unlimited_km_popup" value="1">
                            <label class="form-check-label" for="unlimited_km_popup">
                                <h5>Would you like to make this journey with <b>unlimited kilometers? </b></h5>
                            </label>
                        </div>
                    </div>
                </div>
                <div id="priceSummaryViewModal"><hr/>
                    <h5><b>Price Summary</b></h5>
                    -------------------------------------------
                    <div class="row">
                        <div class="col-md-7"> Trip Amount - 
                            <b>
                                <label id="trip_amt_popup_lbl"></label>
                            </b>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            Tax Amount - <b><label id="tax_amt_popup"></label></b>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            Convenience Fee - <b><label id="convenience_fee_popup"></label></b>
                        </div>
                    </div>
                    <div class="row" id="coupon">
                        <div class="col-md-6">
                            Coupon Discount - <b><label id="coupon_discount_popup"></label></b>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            Total Amount - <b><label id="total_amt_popup"></label></b>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            Refundable Amount - <b><label id="refundable_amt_popup"></label></b>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            Final Amount - <b><label id="final_amt_popup"></label></b>
                        </div>
                    </div>
                    <!-- <div class="row">
                        <div class="col-md-6">
                            Warning - <b><label id="warning_text_popup"></label></b>
                        </div>
                    </div> -->
                </div>
                <button type="button" class="btn btn-primary mt-5 text-center" id="dontApplyUnlimitedKm">Don't want to apply Unlimited KM. on this Booking</button>
                <button type="button" class="btn btn-primary mt-5 text-center" id="applyUnlimitedKm">Apply Unlimited KM.</button>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
    <script src="{{ asset('all_js/admin_js/booking.js') }}"></script>
@endpush
