@extends('templates.admin')

@section('page-title')
    Vehicles
@endsection

@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Add Vehicle</h3>
                        </div>
                        <!-- Display validation errors -->
                        @if ($errors->any())
                            <div class="alert alert-danger" id="error-message">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif                    
                        <form class="card-body" action="{{ route('admin.vehicle-insert') }}" id="vehicle-form" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="car_host">Car Host</label> <span class="error">*</span>
                                        <select id="car_host" class="form-control custom-select" name="car_host">
                                            <option selected disabled value="">Select one</option>
                                            @if(is_countable($carHost) && count($carHost))
                                                @foreach($carHost as $key => $val)
                                                    <option value="{{$val->id}}">{{$val->firstname}} {{$val->lastname}}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label for="commission_percent">Commission Percentage</label> @haspermission('admins', 'admin_web') <span class="error">*</span> @endhaspermission
                                    <input type="number" id="commission_percent" min="0" max="100" name="commission_percent" placeholder="Enter Commision Percent" class="form-control" @haspermission('admins', 'admin_web') value="" @else readonly value="30"@endhaspermission>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="branch">Branch</label>
                                        <select id="branch" class="form-control custom-select" name="branch">
                                            <option selected disabled value="">Select one</option>
                                            {!! $branchDropDown !!}
                                        </select>
                                        @error('branch')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="vehicle_type">Vehicle Type</label> <span class="error">*</span>
                                        <select id="vehicle_type" class="form-control custom-select" name="vehicle_type">
                                            <option selected disabled>Select one</option>
                                            @if (isset($allVehicleTypes) && is_countable($allVehicleTypes) && count($allVehicleTypes) > 0)
                                                @foreach ($allVehicleTypes as $val)
                                                    <option value="{{ $val->type_id }}">{{ $val->name }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        @error('vehicle_type')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="manufacturer">Manufacturer</label> <span class="error">*</span>
                                        <select id="manufacturer" class="form-control custom-select" name="manufacturer">
                                            <option selected disabled>Select one</option>

                                        </select>
                                        @error('manufacturer')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="model">Model</label> <span class="error">*</span>
                                        <select id="model" class="form-control custom-select" name="model">
                                            <option selected disabled>Select one</option>

                                        </select>
                                        @error('model')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="category">Category</label>
                                        <input type="text" id="category" class="form-control" readonly>
                                        <!-- <select id="category" class="form-control custom-select" name="category">
                                            <option selected disabled>Select one</option>

                                        </select>
                                        @error('category')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror -->
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="year">Manufacture Year</label> <span class="error">*</span>
                                        <input type="text" id="year" class="form-control" name="year">
                                        @error('year')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="color">Color</label> <span class="error">*</span>
                                        <input type="text" id="color" class="form-control" name="color">
                                        @error('color')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="licence_plate">Registration Number</label> <span class="error">*</span>
                                        <input type="text" id="licence_plate" class="form-control" name="licence_plate">
                                        @error('licence_plate')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                {{--<div class="col-md-3">
                                    <div class="form-group">
                                        <label for="chassis_no">Chassis Number</label> <span class="error">*</span>
                                        <input type="text" id="chassis_no" class="form-control" name="chassis_no">
                                        @error('chassis_no')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>--}}
                            </div>
                            <div class="row">
                                <div class="col-md-9">
                                    <div class="form-group">
                                        <label for="description">Description</label> <span class="error">*</span>
                                        <input type="text" id="description" class="form-control" name="description">
                                        @error('description')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="availability">Is Available</label> <span class="error">*</span>
                                        <select id="availability" class="form-control custom-select" name="availability">
                                            <option selected disabled>Select one</option>
                                            <option value="1">Yes</option>
                                            <option value="0">No</option>
                                        </select>
                                        @error('availability')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <hr />
                            <h6 class="text-primary"><b>Properties</b></h6>
                            <hr />
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="vehicle_transmission">Transmission</label>
                                        <select id="vehicle_transmission" class="form-control custom-select" name="vehicle_transmission">
                                            <option selected disabled>Select one</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="engine_cc">Engine CC</label>
                                        <input type="text" id="engine_cc" class="form-control" name="engine_cc">
                                        @error('engine_cc')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="seating_capacity">Seating Capacity</label>
                                        <input type="text" id="seating_capacity" class="form-control"
                                            name="seating_capacity">
                                        @error('seating_capacity')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="mileage">Mileage</label>
                                        <input type="text" id="mileage" class="form-control" name="mileage">
                                        @error('mileage')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="fuel_type">Fuel Type</label>
                                        <select id="fuel_type" class="form-control custom-select" name="fuel_type">
                                            <option selected disabled>Select one</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="fuel_capacity">Fuel Capacity</label>
                                        <input type="text" id="fuel_capacity" class="form-control"
                                            name="fuel_capacity">
                                        @error('fuel_capacity')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <hr />
                            <h6 class="text-primary"><b>Vehicle Feature Selection</b></h6>
                            <hr />
                            <div class="row ml-2">
                                @if (isset($vehicleFeature) && is_countable($vehicleFeature) && count($vehicleFeature) > 0)
                                    <div class="form-group">
                                        @foreach ($vehicleFeature as $val)
                                            <input class="" type="checkbox" id="feature_{{ $val->feature_id }}"
                                                name="feature[]" value="{{ $val->feature_id }}"> {{ $val->name }}
                                            <br />
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <hr />
                            <h6 class="text-primary"><b>Images Upload Section</b></h6>
                            <hr />
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <input type="hidden" id="doc_rc_img" value="">
                                        <label for="document_rc_image">Document RC Image</label> <span
                                            class="error">*</span>
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="document_rc_image"
                                                name="document_rc_image[]" accept="image/*" multiple>
                                            <label class="custom-file-label" for="document_rc_image">Choose file</label>
                                            <div class="invalid-feedback">Please select a Images logo.</div>
                                        </div>                               
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <input type="hidden" id="doc_puc_img" value="">
                                        <label for="document_puc_image">Document PUC Image</label> <span
                                            class="error">*</span>
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="document_puc_image"
                                                name="document_puc_image" accept="image/*">
                                            <label class="custom-file-label" for="document_puc_image">Choose file</label>
                                            <div class="invalid-feedback">Please select a Images logo.</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <input type="hidden" id="doc_insurance_img" value="">
                                        <label for="document_insurance_image">Document Insurance Image</label> <span
                                            class="error">*</span>
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="document_insurance_image"
                                                name="document_insurance_image" accept="image/*">
                                            <label class="custom-file-label" for="document_insurance_image">Choose
                                                file</label>
                                            <div class="invalid-feedback">Please select a Images logo.</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <input type="hidden" id="c_img" value="">
                                        <label for="cutout_img">Cutout Image</label> <span class="error">*</span>
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="cutout_img" name="cutout_img" accept="image/*">
                                            <label class="custom-file-label" for="cutout_img">Choose file</label>
                                            <div class="invalid-feedback">Please select a Image.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    <div id="docRcImg">

                                    </div>  
                                </div>
                                <div class="col-md-3">
                                    <div id="docPucImg">
                                            
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div id="docInsuranceImg">
                                            
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div id="cutoutImg">
                                            
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="rc_expiry_date">RC Doc Expiry Date</label> <span
                                            class="error">*</span>
                                        <input type="date" class="form-control" name="rc_expiry_date"
                                            id="rc_expiry_date">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="puc_expiry_date">PUC Doc Expiry Date</label> <span
                                            class="error">*</span>
                                        <input type="date" class="form-control" name="puc_expiry_date"
                                            id="puc_expiry_date">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="insurance_expiry_date">Insurance Doc Expiry Date</label> <span
                                            class="error">*</span>
                                        <input type="date" class="form-control" name="insurance_expiry_date"
                                            id="insurance_expiry_date">
                                    </div>
                                </div>
                            </div>
                            <!-- <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="rc_number">RC Number</label>
                                        <input type="text" class="form-control" name="rc_number" id="rc_number">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="puc_number">PUC Number</label>
                                        <input type="text" class="form-control" name="puc_number" id="puc_number">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="insurance_number">Insurance Doc Number</label>
                                        <input type="text" class="form-control" name="insurance_number" id="insurance_number">
                                    </div>
                                </div>
                            </div> -->
                            <hr />
                            <div class="row">
                                <div class="col-md-3">
                                    <input type="hidden" value="0" id="bannerCnt">
                                    <label for="banner_images">Banner Image</label> <span class="error">*</span>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="banner_images"
                                            name="banner_images" accept="image/*" 
                                            title="Please select Banner Image" multiple>
                                        <label class="custom-file-label" for="banner_images">Choose file</label>
                                        <div class="invalid-feedback">Please select a Image.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3" id="bannerImgContainer">
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    <input type="hidden" value="0" id="regularCnt">
                                    <label for="regular_images">Regular Image</label> <span class="error">*</span>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="regular_images"
                                            name="regular_images" accept="image/*" 
                                            title="Please select Regular Image" multiple>
                                        <label class="custom-file-label" for="regular_images">Choose file</label>
                                        <div class="invalid-feedback">Please select a Image.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3 mt-2" id="regularImgContainer">
                            </div>
                            <hr />
                            <input type="hidden" id="min_rental_pirice" value="0">
                            <input type="hidden" id="max_rental_pirice" value="0">
                            <h6 class="text-primary"><b>Price Calculation</b></h6>
                            <hr />
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="rental_price">Rental Price</label> <span class="error">*</span>
                                        <input type="text" id="rental_price" class="form-control"
                                            name="rental_price">
                                        @error('rental_price')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="extra_km_rate">Extra Km Rate</label> <span class="error">*</span>
                                        <input type="text" id="extra_km_rate" class="form-control"
                                            name="extra_km_rate">
                                        @error('extra_km_rate')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="extra_hour_rate">Extra Hour Rate</label> <span class="error">*</span>
                                        <input type="text" id="extra_hour_rate" class="form-control"
                                            name="extra_hour_rate">
                                        @error('extra_hour_rate')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div id="calculation" class="row">
                            </div>

                            <hr />
                            <h6 class="text-primary"><b>Availibility Date Calander</b></h6>
                            <hr />
                            <div class="row">
                                <div class="col-md-2">
                                    <label for="calender_start_date_0">Select From Date & Time</label>  
                                    <div class="input-group date" data-target-input="nearest">
                                        <input type="text" id="calender_start_date_0" name="calender_start_date[0]" data-id="0" class="form-control datetimepicker-input calstartdate" data-target="#calender_start_date_0" placeholder="Select From Date Time" autocomplete="off" />
                                        <div class="input-group-append" data-target="#calender_start_date_0" data-toggle="datetimepicker">
                                            <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                        </div>
                                    </div>
                                    <span id="cal_start_date_error_0"></span>
                                </div>
                                <div class="col-md-2">
                                    <label for="calender_end_date_0">Select To Date & Time</label>   
                                    <div class="input-group date" data-target-input="nearest">
                                        <input type="text" id="calender_end_date_0" name="calender_end_date[0]" data-id="0" class="form-control datetimepicker-input calenddate" data-target="#calender_end_date_0" placeholder="Select To Date Time" autocomplete="off" />
                                        <div class="input-group-append" data-target="#calender_end_date_0" data-toggle="datetimepicker">
                                            <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                        </div>
                                    </div>
                                    <span id="cal_end_date_error_0"></span>
                                </div>
                                <div class="col-md-6">
                                    <label for="reason_0">Reason</label>                           
                                    <input type="text" class="form-control" name="reason[0]" id="reason_0">
                                </div>
                                <div class="col-md-1 mt-4">
                                    <a href="javascript:void(0);" id="addCalender"><i class="fas fa-plus-square fa-3x"></i></a> 
                                </div>
                                <div class="col-md-1 mt-4">
                                    <a href="javascript:void(0);" class="btn btn-danger" id="clearDates" data-id="0">Clear Dates</a> 
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div id="calenderAppend">
                                        
                                    </div>
                                </div>
                            </div>
                            <!-- <div class="row" id="vehicle-images-section">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="image_type">Image Type</label> <span class="error">*</span>
                                        <select id="image_type" class="form-control custom-select" name="addMoreInputFields[0][image_type]" >
                                            <option selected disabled>Select one</option>
                                            <option value="banner">Banner</option>
                                            <option value="cutout">Cutout</option>
                                            <option value="regular">Regular</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="Images">Images</label> <span class="error">*</span>
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="Images" name="addMoreInputFields[0][image_url][]" accept="image/*" multiple >
                                            <label class="custom-file-label" for="Images" id="logoLabel">Choose file</label>
                                            <div class="invalid-feedback">Please select a Images logo.</div>
                                        </div>
                                    </div>
                                </div>
                            </div> -->
                            <button type="submit" class="btn btn-primary mt-5">Add Vehicle</button>
                            <!-- <button type="button" id="addmoreimages" class="btn btn-secondary">Add More Images</button> -->
                            <a href="/admin/vehicles" class="btn btn-danger mt-5">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        /*var dtToday = new Date();
        var month = dtToday.getMonth() + 1;
        var day = dtToday.getDate();
        var year = dtToday.getFullYear();
        if(month < 10)
            month = '0' + month.toString();
        if(day < 10)
            day = '0' + day.toString();
        var maxDate = year + '-' + month + '-' + (day+1);
        var endMaxDate = year + '-' + month + '-' + (day+2);

        // Check if the input field is empty
        $('#rc_expiry_date').attr('min', maxDate);
        $('#puc_expiry_date').attr('min', maxDate);
        $('#insurance_expiry_date').attr('min', maxDate);
        $('.calstartdate').attr('min', maxDate);
        $('.calenddate').attr('min', endMaxDate);*/

        $('.calstartdate').datetimepicker({
            format: 'DD-MM-YYYY HH:mm', 
            minDate:moment(),
            autoclose: true,
            icons: {
                time: "fa fa-clock",
                date: "fa fa-calendar",
                up: "fa fa-arrow-up",
                down: "fa fa-arrow-down",
                previous: "fa fa-chevron-left",
                next: "fa fa-chevron-right",
                today: "fa fa-clock-o",
                clear: "fa fa-trash-o"
            },
        });
        $('.calenddate').datetimepicker({
            format: 'DD-MM-YYYY HH:mm', 
            minDate:moment(),
            autoclose: true,
            icons: {
                time: "fa fa-clock",
                date: "fa fa-calendar",
                up: "fa fa-arrow-up",
                down: "fa fa-arrow-down",
                previous: "fa fa-chevron-left",
                next: "fa fa-chevron-right",
                today: "fa fa-clock-o",
                clear: "fa fa-trash-o"
            },
        });

    </script>

    <!-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script> -->
    <script src="{{ asset('all_js/admin_js/vehicles.js') }}"></script>
    <script type="text/javascript">
        var typeId = '';
        var manufacturerId = '';
        var categoryId = '';
        var modelId = '';
        var bannerCnt = 0;
        var regularCnt = 0;
        var fuelTypeId = '';
        var transmissionId = '';
        var isPriceValid = true; 
        $(document).ready(function() {

            $('#rental_price').on('keyup', function() {
                const rentalPrice = parseFloat($(this).val());
                if (!isNaN(rentalPrice) && rentalPrice > 0 && rentalPrice != '') {
                    // Clear previous calculations
                    $('#calculation').empty();
                    @foreach ($rules as $rule)
                        var hours = {{ $rule->hours }};
                        var multiplier = {{ $rule->multiplier }};
                        var tripAmount = multiplier * rentalPrice;
                        // Determine if the trip duration is in hours or days
                        var duration = hours <= 24 ? `${hours} Hours` : `${hours / 24} Days`;
                        var calculationItem = $('<div class="col-md-3">').html(
                            `<b>${duration}:</b> ₹ <input type="text" data-val="${hours}" name="priceCalc[${hours}]" style="width: 100px; margin-left: 10px;margin:3px" placeholder="Edit" class="priceChange" value="${tripAmount}" id="priceval_${hours}"><br/><label class="text-danger" id="error_${hours}" hidden></label>`);
                        $('#calculation').append(calculationItem);
                    @endforeach
                }else{
                    $('#calculation').empty();
                    @foreach ($rules as $rule)
                        var hours = {{ $rule->hours }};
                        var duration = hours <= 24 ? `${hours} Hours` : `${hours / 24} Days`;
                        var calculationItem = $('<div class="col-md-3">').html(
                            `<b>${duration}:</b> ₹ <input type="text" data-val="${hours}" class="priceChange" name="priceCalc[${hours}]" style="width: 100px; margin-left: 10px;margin:3px" placeholder="Edit" value="0" id="priceval_${hours}"><br/><label class="text-danger" id="error_${hours}" hidden></label>`);
                        $('#calculation').append(calculationItem);
                    @endforeach
                }
            }).trigger('keyup'); // Trigger keyup event once on page load

            $(document).on('keyup', '.priceChange', function(ev){
                var enteredPriceVal = $(this).val();
                var currentHours = $(this).data('val');
                var modelId = $('#model').val();
                $.ajax({
                    url: sitePath + "/admin/check-updatedprice",
                    method:"GET",
                    async: false,
                    cache: false,
                    data: {
                        "_token":  $('meta[name="csrf-token"]').attr('content'), 
                        "currentHours":currentHours,
                        "enteredPriceVal":enteredPriceVal,
                        "modelId":modelId,
                    },
                    success: function(response) {
                        var minPrice = response.minPrice;
                        var maxPrice = response.maxPrice;
                        if(response.status == false){
                            $('#error_' + currentHours).css('display', 'block');
                            $('#error_'+currentHours).removeAttr('hidden');
                            $('#error_'+currentHours).text("Rental Price must be between "+minPrice+" and "+maxPrice);
                            isPriceValid = false; 
                        }else{
                            $('#error_' + currentHours).attr('hidden', true);
                            $('#error_'+currentHours).text("");
                            isPriceValid = true; 
                        }
                    },
                });
                //if($(this).val() <= 0){
                if($(this).val() != '' && $(this).val() == 0){
                    @foreach ($rules as $rule)
                        var hours = {{ $rule->hours }};
                        if(hours < currentHours){
                            $('#priceval_'+hours).val(0);
                        }
                    @endforeach
                }
            });
        });

    </script>
@endpush
