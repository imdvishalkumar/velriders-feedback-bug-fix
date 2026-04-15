@extends('templates.admin')

@section('page-title')
    Bookings
    @if (session('success'))
        <div id="success-message" class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
@endsection
<style>
    .pagination {
        flex-wrap: wrap;
        justify-content: center;
    }
    .startKmTxt, .endKmTxt {
        width: 60px;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #444;
        line-height: 20px !important;
    }
    .select2 {
        width:100%!important;
    }
</style>

@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="card">

                    <div class="card-header">
                        <div class="row">
                            <div class="col-md-1">
                                <h3 class="card-title">All Bookings</h3>
                            </div>
                        </div>
                        <hr/>
                        <form id="booking-filter-form">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="booking_pickup_date_filter">Pick-up Date</label> <span class="error">*</span>
                                        <div class="input-group date" data-target-input="nearest">
                                            <input type="text" id="booking_pickup_date_filter" name="booking_pickup_date_filter" class="form-control datetimepicker-input" data-target="#booking_pickup_date_filter" placeholder="Select Pickup Date" autocomplete="off" />
                                            <div class="input-group-append" data-target="#booking_pickup_date_filter" data-toggle="datetimepicker">
                                                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                            </div>
                                        </div>
                                        <span id="booking_pickup_date_filter_error"></span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="booking_return_date_filter">Return Date</label> <span class="error">*</span>
                                        <div class="input-group date" data-target-input="nearest">
                                            <input type="text" id="booking_return_date_filter" name="booking_return_date_filter" class="form-control datetimepicker-input" data-target="#booking_return_date_filter" placeholder="Select Pickup Date" autocomplete="off"/>
                                            <div class="input-group-append" data-target="#booking_return_date_filter" data-toggle="datetimepicker">
                                                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                            </div>
                                        </div>
                                        <span id="booking_return_date_filter_error"></span>                                        
                                    </div>
                                </div>
                                <div class="col-md-1 mt-4">
                                    <button type="submit" class="btn btn-primary p-3" id="searchBookingDefaultFilterBtn">Search</button>
                                    <button type="button" class="btn btn-primary p-3" id="searchBookingActualFilterBtn">Search</button>
                                </div>
                                <div class="col-md-1 mt-4">
                                    <button type="button" class="btn btn-danger p-3" id="bookfilterClearBtn">Clear</button>
                                </div>

                                <div class="col-md-2 text-right">
                                    <a href="javascript:void(0);" onclick='exportBookings("{{url('admin/export-bookings/csv')}}");' class="btn btn-success p-2 mx-2">
                                      <i class="fa fa-file-csv fa-2x"></i>
                                    </a>
                                    <a class="btn btn-danger p-2" onclick='exportBookings("{{url('admin/export-bookings/pdf')}}");' href="javascript:void(0);">
                                      <i class="fa fa-file-pdf fa-2x"></i>
                                    </a>
                                </div>
                                <div class="col-md-2">
                                    <!-- <a href="{{route('admin.get-pending-booking')}}" class="btn btn-primary float-right ml-3">View all Pending Orders</a> -->
                                    @haspermission('add-booking', 'admin_web')
                                    <a href="{{route('admin.add-booking')}}" class="btn btn-primary float-right">Add Booking</a>
                                    @endhaspermission
                                </div>
                                
                            </div>
                        </form>
                        <div class="row mt-3">
                            <div class="col-md-1 mr-5">
                                <select id="booking_status" name="booking_status" class="form-control">
                                    <option value="">Status</option>
                                    @php $bookingStatus = config('global_values.booking_statuses'); @endphp
                                    @if(is_countable($bookingStatus) && count($bookingStatus) > 0)
                                        @foreach($bookingStatus as $val)
                                            {{-- @if($val['id'] != 'pending') --}}
                                            <option value="{{$val['id']}}">{{$val['status']}}</option>
                                            {{-- @endif --}}
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-3 mr-5">
                                <select id="search_booking" name="search_booking" class="form-control">
                                    <option value="">-- Search Booking id --</option>
                                        @if(is_countable($bookingIds) && count($bookingIds) > 0)
                                            @foreach($bookingIds as $key => $val)
                                                <option value="{{$val}}">{{$val}}</option>
                                            @endforeach
                                        @endif
                                </select>
                            </div>
                            <div class="col-md-3 mr-5">
                                <select id="search_customer" name="search_customer" class="form-control">
                                    <option value="">-- Search Customer --</option>
                                        @if(is_countable($customerArr) && count($customerArr) > 0)
                                            @foreach($customerArr as $key => $val)
                                             <option value="{{ $val->customer_id }}">{{ $val->name }} - {{ $val->email }} - {{ $val->mobile_number }}</option>
                                            @endforeach
                                        @endif
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select id="search_vehicle" name="search_vehicle" class="form-control">
                                    <option value="">-- Search Vehicle --</option>
                                        @if(is_countable($vehicleArr) && count($vehicleArr) > 0)
                                            @foreach($vehicleArr as $key => $val)
                                                <option value="{{$val->vehicle_id}}">{{$val->vehicle_id}} - {{$val->vehicle_name}} - {{$val->license_plate}} @if($val->is_deleted == 1)({{'DELETED'}})@else {{''}}@endif</option>
                                            @endforeach
                                        @endif
                                </select>
                            </div>
                            
                        </div>
                    </div>

                    <div class="card-body" id="bookingHtml">

                    </div>

                    <!-- <div class="card-body table-responsive" id="bookingHtml">
                        <table id="example1" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Booking Id</th>
                                    <th>Customer Details</th>
                                    <th>Vehicle Details</th>
                                    <th>Pickup Date</th>
                                    <th>Return Date</th>
                                    <th>Start Kilometers</th>
                                    <th>End Kilometers</th>
                                    <th>Start OTP</th>
                                    <th>End OTP</th>
                                    <th>Rental Type</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                    <th>Duration Minutes </th>
                                    <th>Penalty Details</th>
                                    <th>Summary PDF</th>
                                    <th>Invoice PDF</th>
                                    <th>Unlimited Kilometers</th>
                                    <th>Vehicle City</th>
                                    <th>Penalty</th>
                                    <th>Booking Creation Date</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody class="table-data">

                            </tbody>
                        </table> 
                    </div> -->

                </div>
            </div>
        </div>
    </section>

<div class="modal fade" id="addPenalty" tabindex="-1" role="dialog" aria-labelledby="addPenaltyModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPenaltyModalLabel">Add/Edit Penalty</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addPenaltyForm" method="POST" action="{{route('admin.store.penalty')}}">
                @csrf
                <input type="hidden" id="bookingId" name="bookingId" value="">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="amount">Penalty Amount:</label> <span class="error">*</span>
                        <input type="text" name="amount" id="amount" class="form-control" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="penalty_details">Penalty Details:</label> <span class="error">*</span>
                        <textarea name="penalty_details" id="penalty_details" class="form-control" rows="5" placeholder="Enter Penalty Details"></textarea>
                    </div>
                </div>                    
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="updateCustomer">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="viewPriceSummary" tabindex="-1" role="dialog" aria-labelledby="addPenaltyModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPenaltyModalLabel">Price Summary</h5>
                <button type="button" class="close closePriceSumary" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="priceSummaryDiv">

                </div>
            </div>                    
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary closePriceSumary">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')

    <script type="text/javascript" src="{{asset('all_js/admin_js/booking.js')}}"></script>
    <script>
       /* $.fn.editable.defaults.mode = 'inline';
        const Toast = new Notyf({
            position: {
                x: 'center',
                y: 'top',
            }
        });*/

        /*$(document).ready(function() {
            const tableData = $('.table-data');
            const example1 = $("#example1");

            function loadBookings(selectedStatus = '') {
                $.ajax({
                    type: "GET",
                    url: sitePath + '/admin/get-all-booking/'+selectedStatus,
                    success: function(response) {
                        let html = '';
                        var priceSummary = '';
                        var tripAmount = '';
                        var convenienceFee = '';
                        var totalPrice = '';
                        var refundableDeposit = '';
                        response.forEach((vehicle) => {
                            var date = new Date(vehicle.created_at);
                            var formattedDate = $.datepicker.formatDate("dd-mm-yy", date);
                            var hours = ("0" + date.getHours()).slice(-2);
                            var minutes = ("0" + date.getMinutes()).slice(-2);
                            var formattedCreatedAt = formattedDate + " " + hours + ":" + minutes;
                            var buttonStatus = 'btn btn-primary refund-payment';
                            var rStatus = 'Refund';
                            var details = vehicle.cDetails;
                            var downloadText = 'You can download after Status becomes Completed';
                            var downloadSummaryHref = sitePath+'/admin/rental-booking/summary/'+vehicle.customer_id+'/'+vehicle.booking_id;
                            var downloadInvoiceHref = 'javascript:void(0);';
                            var branch = '';
                            var finalAmount = 0;
                            if(details){
                                tripAmount = details.trip_amount?details.trip_amount : 0;
                                convenienceFee = details.convenience_fee ? details.convenience_fee : 0;
                                totalPrice = details.total_price ? details.total_price : 0;
                                refundableDeposit = details.refundable_deposit ? details.refundable_deposit : 0;
                            }
                            var refundStatus = 'hidden';
                            if(vehicle && vehicle.status && vehicle.status.toLowerCase() == 'completed'){
                                refundStatus = '';
                                downloadText = "Download";
                                downloadInvoiceHref = sitePath+'/admin/rental-booking/invoice/'+vehicle.customer_id+'/'+vehicle.booking_id;
                            }
                            if(vehicle.cDetails){
                                if(vehicle.cDetails.refundable_deposit == 0){
                                    buttonStatus = 'btn btn-secondary';
                                    rStatus = 'Refunded';    
                                }
                            }
                            if(vehicle.vehicle.branch){
                                branch = vehicle.vehicle.branch.name;
                            }
                            var customerDetails = '';
                            var vehicleDetails = '';
                            if(vehicle.customer.firstname != null && vehicle.customer.lastname != null){
                                customerDetails += ' <b>Name - </b>'+vehicle.customer.firstname +' '+vehicle.customer.lastname+'<br/>';
                            }
                            if(vehicle.customer.email != null){
                                customerDetails += ' <b>Email - </b>' + vehicle.customer.email + '<br/>';
                            }
                            if(vehicle.customer.mobile_number != null){
                                customerDetails += ' <b>Mobile No. - </b>' + vehicle.customer.mobile_number + '<br/>';
                            }
                            if(vehicle.customer.dob != null){
                                customerDetails += ' <b>Date of Birth. - </b>' + vehicle.customer.dob + '<br/>';
                            }
                            if(vehicle.customer.documents != null){
                                customerDetails += ' <b>Driving License Status - </b>' + vehicle.customer.documents.dl + '<br/>';
                                customerDetails += ' <b>GovId Status - </b>' + vehicle.customer.documents.govtid;
                            }
                            if(vehicle.vehicle.vehicle_name != null){
                                vehicleDetails += ' <b>Model - </b>'+vehicle.vehicle.vehicle_name+'<br/>';
                            }
                            if(vehicle.vehicle.vehicle_name != null){
                                vehicleDetails += ' <b>Color - </b>'+vehicle.vehicle.color+'<br/>';
                            }
                            if(vehicle.vehicle.vehicle_name != null){
                                vehicleDetails += ' <b>License Plate - </b>'+vehicle.vehicle.license_plate+'<br/>';
                            }

                            var startOtpButton = 'hidden';
                            var endOtpButton = 'hidden';
                            var resetVisibleStatus = 'hidden';
                            
                            if(vehicle && vehicle.status && vehicle.status.toLowerCase() == 'confirmed' && vehicle.customer.documents.dl == 'Approved' && vehicle.customer.documents.govtid == 'Approved'){
                                if(vehicle.startJourneyOtpStatus){
                                    startOtpButton = '';    
                                }
                            }
                            if(vehicle && vehicle.status && vehicle.status.toLowerCase() == 'running' || vehicle.status.toLowerCase() == 'penalty_paid'){
                                endOtpButton = '';
                            }
                            if(vehicle.endJourneyStaus){
                                endOtpButton = 'hidden';
                            }

                            if(vehicle.end_datetime != null && (vehicle.status == 'running' || vehicle.status == 'completed')){
                                resetVisibleStatus = '';
                            }

                            var penaltyText = 'Add Penalty';
                            var penaltyClass = "addPenalty";
                         
                            if(vehicle.pDetails){
                                penaltyText ='Edit Penalty';
                                penaltyClass = 'addPenalty'; 
                            }
                            if(vehicle.status == 'pending' || vehicle.status == 'confirmed'){
                                penaltyText = 'You can not add due to this booking is not started yet';
                                penaltyClass = 'disabled text-secondary';
                            }
                            if(details){
                                finalAmount = details.final_amount
                            }
                        
                            html += `<tr>
                                <td>${vehicle.booking_id}</td>
                                <td>${customerDetails}</td>
                                <td>${vehicleDetails}</td>
                                <td>${vehicle.pickup_date}</td>
                                <td>${vehicle.return_date}</td>
                                <td>
                                    <a href="javascript:void(0);" class="startKmEdit" data-name="startKm" data-type="text" data-pk="${vehicle.booking_id}" title="Click to add/edit Start Km">${vehicle.start_kilometers || 0}</a>
                                </td>
                                <td>
                                    <a href="javascript:void(0);" class="endKmEdit" data-name="endKm" data-type="text" data-pk="${vehicle.booking_id}" title="Click to add/edit End Km">${vehicle.end_kilometers || 0}</a>
                                </td>
                                <td>{{--Deposit : ${vehicle.updated_rental_price*5} --}}
                                    <span ${startOtpButton}>
                                    <a class="btn btn-success start-otp-btn" href="booking-update-start-Otp/${vehicle.booking_id}" data-booking-id="${vehicle.booking_id}">Start</a><br/><span id="displayStartOtp_${vehicle.booking_id}"> </span>
                                </td>
                                <td>
                                    <span ${endOtpButton}>
                                    <a class="btn btn-success end-otp-btn" href="booking-update-end-otp/${vehicle.booking_id}" data-booking-id="${vehicle.booking_id}">End</a><br/><span id="displayEndOtp_${vehicle.booking_id}"> </span>
                                </td>
                                {{-- <td>${tripAmount}</td>
                                <td>${convenienceFee}</td>
                                <td>${totalPrice}</td>
                                <td>${refundableDeposit}</td>
                                <td>${finalAmount}</td> --}}
                                <td>${vehicle.rental_type}</td>
                                <td>
                                    <a class="btn btn-secondary m-2" target="_blank" href="booking-priview/${vehicle.booking_id}"><i class="fa fa-eye" aria-hidden="true"></i></a>
                                    <span ${resetVisibleStatus}>
                                        <a class="btn btn-danger m-2 resetBooking" href="javascript:void(0);" data-id="${vehicle.booking_id}">Reset</a>
                                    </span>
                                </td>
                                <td>${vehicle.status.toUpperCase()}</td>
                                <td>${vehicle.rental_duration_minutes}</td>
                                <td>${vehicle.penalty_details || 0}</td>
                                <td><a href="${downloadSummaryHref}" target="_blank" download>Download</a></td>
                                <td><a href="${downloadInvoiceHref}" target="_blank" download>${downloadText}</a></td>
                                <td>${vehicle.unlimited_kms}</td>
                                <td>${branch}</td>
                                <td><a href="javascript:void(0);" class="${penaltyClass}" data-id="${vehicle.booking_id}">${penaltyText}</a></td>
                                <td>${formattedCreatedAt}</td>
                                <td><span ${refundStatus}>
                                    <a class="${buttonStatus}" id="refundBtn_${vehicle.booking_id}" data-id="${vehicle.booking_id}" href="javascript:void(0);">${rStatus}</a>
                                    <a class="btn btn-primary" id="processBtn_${vehicle.booking_id}" href="javascript:void(0);" style="display: none;">Processing...</a>
                                    </span>
                                </td>
                            </tr>`;
                        });

                        // Destroy existing DataTable instance to reflact filters
                        if ($.fn.DataTable.isDataTable(example1)) {
                            example1.DataTable().clear().destroy();
                        }
                        tableData.html(html);

                        $.ajaxSetup({
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            }
                        }); 

                        //Below code for inline editing Start and End Kilometers
                        $('.startKmEdit').editable({
                               url: "{{ route('admin.km.update') }}",
                               type: 'text',
                               pk: $(this).data('pk'),
                               name: 'startKm',
                               title: 'Enter KM.',
                               success: function(response, newValue) {
                                    if(response.success == true){
                                        Toast.success("Start Km updated Successfully");
                                    }else{
                                        Toast.success("Something went Wrong");
                                    }
                                }
                        });
                        $('.endKmEdit').editable({
                               url: "{{ route('admin.km.update') }}",
                               type: 'text',
                               pk: $(this).data('pk'),
                               name: 'endKm',
                               title: 'Enter KM.',
                               success: function(response, newValue) {
                                    if(response.success == true){
                                        Toast.success("End Km updated Successfully");
                                    }else{
                                        Toast.success("Something went Wrong");
                                    }
                                }
                        });
                        example1.DataTable({
                            "responsive": true,
                            "lengthChange": false,
                            "autoWidth": false,
                            "pageLength": 50,
                            "buttons": ["copy", "csv", "excel", 
                            {
                            extend: 'pdfHtml5',
                            orientation: 'landscape',
                            pageSize: 'A3',
                             exportOptions: {
                                    columns: [0,1,2,3,4,5,6,7,8,9,10,11,12,16,17] // Specify the columns you want to export (zero-based index)
                                } 
                            }
                            ,"print", "colvis"]
                            //pageSize: LEGAL
                        }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
                    }
                });
            }

            loadBookings();

            $('.table-data').on('click', '.start-otp-btn', function(event) {
                event.preventDefault();
                var bookingId = $(this).data('booking-id');
                swal.fire({
                    title: "Are you Sure ? you want send Start OTP ?",
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#007bff',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No'
                }).then(function(result){
                    if(result.value){
                        $.ajax({
                            type: "POST",
                            url: "{{ route('admin.booking-updateOtp', ['booking_id' => '__booking_id__']) }}".replace('__booking_id__', bookingId),
                            data: {_token: '{{ csrf_token() }}'}, // Include CSRF token
                            success: function(response) {
                                $('#displayStartOtp_'+bookingId).text(response.startOtp);
                                Toast.success(response.success);
                            },
                            error: function(xhr, status, error) {
                                console.error(xhr.responseText);
                            }
                        });
                    }else{}
                });
            });

            $('.table-data').on('click', '.end-otp-btn', function(event) {
                event.preventDefault();
                var bookingId = $(this).data('booking-id');
                swal.fire({
                    title: "Are you Sure ? you want send End OTP ?",
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#007bff',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No'
                }).then(function(result){
                    if(result.value){
                        $.ajax({
                            type: "POST",
                            url: sitePath + '/admin/get-completion-price-summary',
                            data: {_token: '{{ csrf_token() }}', bookingId: bookingId},
                            success: function(response) {
                                if(response.status == true){
                                    $('#priceSummaryDiv').html(response.html);
                                    $('#viewPriceSummary').modal('show');    
                                }else{
                                    swal.fire({
                                        title: "Something went wrong",
                                        confirmButtonColor: '#007bff',
                                        confirmButtonText: 'Ok',
                                    })
                                }
                            },
                        });
                    }else{}
                });
            });

            $('.closePriceSumary').on('click', function () {
                $('#viewPriceSummary').modal('hide');
                var bId = $('#bId').val();
                $.ajax({
                    type: "POST",
                    url: "{{ route('admin.booking-end-otp', ['booking_id' => '__booking_id__']) }}".replace('__booking_id__', bId),
                    data: {_token: '{{ csrf_token() }}'}, // Include CSRF token
                    success: function(response) {
                        $('#displayEndOtp_'+bId).text(response.endOtp);
                        Toast.success(response.success);
                    },
                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                    }
                });
            });

            setTimeout(function(){
                $("#success-message").fadeOut("slow", function(){
                    $(this).remove();
                });
            }, 2000); 

             $('.table-data').on('click', '.addPenalty', function(event) {
                var bookingId = $(this).attr('data-id');
                $('#bookingId').val(bookingId);

                 $.ajax({
                    type: "POST",
                    url: "{{ route('admin.get.penalty') }}",
                    data: {_token: '{{ csrf_token() }}', bookingId:bookingId }, 
                    success: function(response) {
                        $('#amount').val(response.penalty_amt);
                        $('#penalty_details').val(response.penalty_info);
                    },
                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                    }
                });
                $('#addPenalty').modal('show');
             });

            $('#addPenaltyForm').validate({ 
               rules: {
                  amount: {required: true, number:true},
                  penalty_details: {required: true, maxlength:500},
                 
               },
               messages :{
                    amount : { required : 'Please enter Amount' },
                    penalty_details : { required : 'Please enter Penalty Details' },
                   
                },
                highlight: function (element) {
                    if ($(element).is('select') || $(element).is('input')) {
                        $(element).parent('.select-wrap').addClass('error');
                    } else {
                        $(element).addClass('error');
                    }
                },
            });

            //For Refund Customer Payment
            $(document).on("click",".refund-payment",function() {
                var bookingId = $(this).attr('data-id');
                swal.fire({
                    title: "Are you Sure ? you want to refund for this booking ?",
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#007bff',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No'
                }).then(function(result){
                    if(result.value){
                        $('#refundBtn_' + bookingId).hide();
                        $('#processBtn_' + bookingId).show();
                        $('#refundBtn_'+bookingId).hide();
                        $('#processBtn_'+bookingId).removeAttr('hidden');
                        $.ajax({
                            type: "POST",
                            url: "{{ route('admin.customer.refund.process') }}",
                            data: {
                                _token: '{{ csrf_token() }}',
                                bookingId: bookingId,
                            }, 
                            success: function(response) {
                                $('#refundBtn_'+bookingId).show();
                                ///$('#processBtn').hide();
                                $('#processBtn_'+bookingId).attr('hidden', true);
                                swal.fire({
                                    title: response.message,
                                    confirmButtonColor: '#007bff',
                                    confirmButtonText: 'Ok',
                                }).then(function(result){
                                      location.reload();
                                });
                            }
                        });     
                    }else if(result.dismiss == 'cancel'){}
                });
            });

            $(document).on("change","#booking_status",function() {
                var selectedStatus = $(this).val();
                loadBookings(selectedStatus);
                
            });

            $(document).on('click', '.resetBooking', function (event) {
                var bookingId = $(this).attr('data-id');
                swal.fire({
                    title: "Are you Sure ? you want to Reset this booking ?",
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#007bff',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No'
                }).then(function(result){
                    if(result.value){
                        $.ajax({
                            type: "POST",
                            url: "{{ route('admin.reset.booking') }}",
                            data: {
                                _token: '{{ csrf_token() }}',
                                bookingId: bookingId,
                            }, 
                            success: function(response) {
                                if(response.status == true){
                                    swal.fire({
                                        title: response.message,
                                        confirmButtonColor: '#007bff',
                                        confirmButtonText: 'Ok',
                                    }).then(function(result){
                                          location.reload();
                                    });
                                }else{
                                    swal.fire({
                                        title: response.message,
                                        confirmButtonColor: '#007bff',
                                        confirmButtonText: 'Ok',
                                    });
                                }
                            }
                        });
                    }else{

                    }
                });
            });
        });*/

    </script>
@endpush
