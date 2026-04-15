<table id="example1" class="table table-bordered table-striped table-responsive">
    <thead>
        <tr>
            <th>Booking Id</th>
            <th>Customer Details</th>
            <th>Vehicle Details</th>
            <th>Pickup Date</th>
            <th>Return Date</th>
            @haspermission('booking-history-operations', 'admin_web')
            <th>Start Kilometers</th>
            <th>End Kilometers</th>
            @endhaspermission
            <th>Start OTP</th>
            <th>End OTP</th>
            <th>Rental Type</th>
            @haspermission('booking-history-operations', 'admin_web')
            <th>Action</th>
            @endhaspermission
            <th>Status</th>
            @haspermission('booking-history-operations', 'admin_web')
            <th>Penalty</th>
            @endhaspermission
        </tr>
    </thead>
    <tbody class="table-data">
        @if(is_countable($data['rentalBooking']) && count($data['rentalBooking']) > 0)
            @foreach($data['rentalBooking'] as $k => $v)
               <tr @if($v->penaltyStatus)style="background-color: #add8e6;" @else style=""@endif>
                    @php
                        $customerDetails = $vehicleDetails = '';
                        if($v['customer']['firstname'] != null && $v['customer']['lastname'] != null){
                            $customerDetails .= ' <b>Name - </b>'.$v['customer']['firstname'] .' '.$v['customer']['lastname'].'<br/>';
                        }
                        if($v['customer']['email'] != null){
                            $customerDetails .= ' <b>Email - </b>' . $v['customer']['email'] . '<br/>';
                        }
                        if($v['customer']['mobile_number'] != null){
                            $customerDetails .= ' <b>Mobile No. - </b>' . $v['customer']['mobile_number'] . '<br/>';
                        }
                        if($v['customer']['dob'] != null){
                            $customerDetails .= ' <b>Date of Birth. - </b>' . $v['customer']['dob'] . '<br/>';
                        }
                        if($v['customer']['documents'] != null){
                            $customerDetails .= ' <b>Driving License Status - </b>' . $v['customer']['documents']['dl'] . '<br/>';
                            $customerDetails .= ' <b>GovId Status - </b>' . $v['customer']['documents']['govtid'];
                        }

                        if($v['vehicle']['vehicle_name'] != null){
                            $vehicleDetails .= ' <b>Model - </b>'.$v['vehicle']['vehicle_name'].'<br/>';
                        }
                        if($v['vehicle']['color'] != null){
                            $vehicleDetails .= ' <b>Color - </b>'.$v['vehicle']['color'].'<br/>';
                        }
                        if($v['vehicle']['license_plate'] != null){
                            $vehicleDetails .= ' <b>License Plate - </b>'.$v['vehicle']['license_plate'].'<br/>';
                        }

                        $penaltyClass = "addPenalty";
                        $penaltyText = 'Add Penalty';
                        if($v['pDetails']){
                            $penaltyText = 'Edit Penalty';
                            $penaltyClass = 'addPenalty'; 
                        }
                        if($v['status'] == 'pending' || $v['status'] == 'confirmed'){
                            $penaltyText = 'You can not add due to this booking is not started yet';
                            $penaltyClass = 'disabled text-secondary';
                        }
                    @endphp
                    <td>{{$v->booking_id}}</td>
                    <td><a href="javascript:void(0);" class="viewCustBooking" id="cust_{{$v['booking_id']}}" data-bookid="{{$v['booking_id']}}" data-custid="{{$v['customer_id']}}">{!! $customerDetails !!}</a></td>
                    <td><a href="javascript:void(0);" class="viewVehiBooking" id="vehi_{{$v['booking_id']}}" data-bookid="{{$v['booking_id']}}" data-vehicleid="{{$v['vehicle_id']}}">{!! $vehicleDetails !!}</a></td>
                    <td>{{date('d-m-Y H:i', strtotime($v['pickup_date']))}}</td>
                    <td>{{date('d-m-Y H:i', strtotime($v['return_date']))}}</td>

                    @haspermission('booking-history-operations', 'admin_web')
                    <td>
                        <a href="javascript:void(0);"><label id="startKmLabel_{{$v['booking_id']}}" class="startKmLbl" data-id="{{$v['booking_id']}}">{{$v['start_kilometers'] ?? 0}}</label></a>
                        <input type="number" min="0" data-id="{{$v['booking_id']}}" class="startKmTxt" value="{{$v['start_kilometers'] ?? 0}}" id="startKmText_{{$v['booking_id']}}">&nbsp;
                        <span class="startKmSpan" data-id="{{$v['booking_id']}}" id="startKmSpan_{{$v['booking_id']}}"><i class="fa fa-check fa-lg text-success" aria-hidden="true"></i></span>
                    </td>
                    <td>
                        <a href="javascript:void(0);"><label id="endKmLabel_{{$v['booking_id']}}" class="endKmLbl" data-id="{{$v['booking_id']}}">{{$v['end_kilometers'] ?? 0}}</label></a>
                        <input type="number" min="0" data-id="{{$v['booking_id']}}" class="endKmTxt" value="{{$v['end_kilometers'] ?? 0}}" id="endKmText_{{$v['booking_id']}}">
                        <span class="endKmSpan" data-id="{{$v['booking_id']}}" id="endKmSpan_{{$v['booking_id']}}"><i class="fa fa-check fa-lg text-success" aria-hidden="true"></i></span>
                    </td>
                    @endhaspermission
                    
                    <td>
                        @if($v['status'] && strtolower($v['status']) == 'confirmed' && strtolower($v['customer']['documents']['dl']) == 'approved' && strtolower($v['customer']['documents']['govtid']) == 'approved' && $v['startJourneyOtpStatus'])
                            <a class="btn btn-success start-otp-btn" href="javascript:void(0);" data-booking-id="{{$v['booking_id']}}">Start </a><br/><span id="displayStartOtp_{{$v['booking_id']}}">
                        @else
                            <h6 class="text-red">Start OTP can't generate because either booking status is not in 'Confirmed' state OR Customer's DL/GOVT ID is not verified OR Customer's Email is not Verified</h6>
                        @endif                   
                    <td>
                        {{--@if($v['status'] && strtolower($v['status']) == 'running' || strtolower($v['status']) == 'penalty_paid' && !endJourneyOtpStatus)--}}
                        @if($v['status'] && strtolower($v['status']) == 'running' && !$v['endJourneyOtpStatus'] && !$v['$duePenalties'])
                            <a class="btn btn-success end-otp-btn" href="javascript:void(0);" data-booking-id="{{$v['booking_id']}}">End</a><br/><span id="displayEndOtp_{{$v['booking_id']}}"> </span> 
                        @else
                            <h6 class="text-red">End OTP can't be generate because either the booking status is not in 'Running' state OR the customer has unpaid penalty dues.</h6>
                        @endif
                    </td>

                    <td>@isset($v['rental_type']){{$v['rental_type']}}@endisset</td>

                    @haspermission('booking-history-operations', 'admin_web')
                    <td>
                        <a class="btn btn-secondary m-2" target="_blank" href="{{route('admin.booking-priview', $v['booking_id'])}}"><i class="fa fa-eye" aria-hidden="true"></i></a>
                       {{-- @if($v['end_datetime'] != null && (strtolower($v['status']) == 'running' || strtolower($v['status']) == 'completed'))
                            <a class="btn btn-danger m-2 resetBooking" href="javascript:void(0);" data-id="{{$v['booking_id']}}">Reset</a>
                        @endif --}}
                    </td>
                    @endhaspermission

                    <td>
                        @isset($v['status']){{strtoupper($v['status'])}}@endisset <br/>
                        @if(strtolower($v['status']) == 'canceled')
                            <a href="javascript:void(0);" class="undoCancelled text-danger font-weight-bold" data-booking-id="{{$v['booking_id']}}">Undo Cancelled</a>
                        @endif
                    </td>
                    @haspermission('booking-history-operations', 'admin_web')
                    <td><a href="javascript:void(0);" class="{{$penaltyClass}}" data-id="{{$v['booking_id']}}">{{$penaltyText}}</a></td>
                    @endhaspermission
                </tr>
            @endforeach
        @endif
    </tbody>
</table> 

<div class="row align-items-center m-3">
    @if( $data['total'] > 0)
        <div class="col-sm-3">
            <h4 class="card-title m-0 font-size-16 text-dark font-weight-semibold">
                Showing {{ $data['from'] }} to {{ $data['to'] }} of {{ $data['total'] }} entries
            </h4>
        </div>
        <div class="col-sm-9">
            <div class="overflow-auto">
                <nav>
                    <ul class="pagination justify-content-end mb-0 line-hight-normal">
                        <!-- Previous Button -->
                        <li class="page-item">
                            <a class="page-link" href="javascript:void(0);" 
                               @if($data['pageno'] != '1') 
                                   onclick="loadBookingHistory( {{ $data['pageno'] - 1}} )"  
                               @endif>
                                <i class="fa fa-angle-double-left" aria-hidden="true"></i>
                            </a>
                        </li>
                        <!-- Pagination Logic -->
                        @php
                            $currentPage = $data['pageno'];
                            $totalPages = $data['totalPages'];
                            $start = max($currentPage - 2, 1);
                            $end = min($currentPage + 2, $totalPages);
                        @endphp
                        <!-- First page always -->
                        @if ($start > 1)
                            <li class="page-item">
                                <a class="page-link" href="javascript:void(0);" onclick="loadBookingHistory(1)">1</a>
                            </li>
                            @if($start > 2)
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            @endif
                        @endif
                        <!-- Page numbers between -->
                        @for($i = $start; $i <= $end; $i++)
                            <li class="page-item @if($i == $currentPage) active @endif ">
                                <a class="page-link" href="javascript:void(0);" onclick="loadBookingHistory({{ $i }})">
                                    {{ $i }}
                                </a>
                            </li>
                        @endfor
                        <!-- Last page always -->
                        @if ($end < $totalPages)
                            @if($end < $totalPages - 1)
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            @endif
                            <li class="page-item">
                                <a class="page-link" href="javascript:void(0);" onclick="loadBookingHistory({{ $totalPages }})">{{ $totalPages }}</a>
                            </li>
                        @endif
                        <!-- Next Button -->
                        <li class="page-item">
                            <a class="page-link" href="javascript:void(0);" 
                               @if($currentPage < $totalPages) 
                                   onclick="loadBookingHistory( {{ $currentPage + 1 }} )"  
                               @endif>
                                <i class="fa fa-angle-double-right" aria-hidden="true"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    @endif
</div>

<!-- <div class="row align-items-center m-3">
    @if( $data['total'] > 0)
        <div class="col-sm-2">
            <h4 class="card-title m-0 font-size-16 text-dark font-weight-semibold">
                Showing {{ $data['from'] }} to {{ $data['to'] }} of {{ $data['total'] }} entries
            </h4>
        </div>
        <div class="col-sm-10">
            <div class="overflow-auto">
                <nav>
                    <ul class="pagination justify-content-end mb-0 line-hight-normal">
                        <li class="page-item">
                            <a class="page-link" href="javascript:void(0);" @if($data['pageno'] != '1') onclick="loadBookingHistory( {{ $data['pageno'] - 1}} )"   @endif>
                                <i class="fa fa-angle-double-left" aria-hidden="true"></i>
                            </a>
                        </li>
                        @for($i = 1;$i<= $data['totalPages']; $i++)
                            <li class="page-item @if($i == $data['pageno']) active @endif ">
                                <a class="page-link" href="javascript:void(0);" onclick="loadBookingHistory({{ $i }} )">
                                    {{ $i }}
                                </a>
                            </li>
                        @endfor
                        <li class="page-item">
                            <a class="page-link" href="javascript:void(0);" @if($data['pageno'] < $data['totalPages']) onclick="loadBookingHistory( {{ $data['pageno'] + 1 }} )"  @endif>
                                <i class="fa fa-angle-double-right" aria-hidden="true"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    @endif
</div> -->

<script>
    $(document).on('click', '.start-otp-btn', function(event) {
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

$(document).on('click', '.addPenalty', function(event) {
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

$(document).ready(function(){
    $(".startKmTxt").each(function() {
        $(this).hide();
    });
    $(".startKmSpan").each(function() {
        $(this).hide();
    });
    $(".endKmTxt").each(function() {
        $(this).hide();
    });
    $(".endKmSpan").each(function() {
        $(this).hide();
    });
});

$(document).on('click', '.startKmLbl', function(event) {
   var bookingId = $(this).attr('data-id');
   $('#startKmLabel_'+bookingId).hide();
   $('#startKmText_'+bookingId).show();
   $('#startKmSpan_'+bookingId).show();
});
$(document).on('click', '.endKmLbl', function(event) {
   var bookingId = $(this).attr('data-id');
   $('#endKmLabel_'+bookingId).hide();
   $('#endKmText_'+bookingId).show();
   $('#endKmSpan_'+bookingId).show();
});
$(document).on('click', '.startKmSpan', function(event) {
    var bookingId = $(this).attr('data-id');
    var updatedVal = $('#startKmText_'+bookingId).val();
    if(updatedVal != ''){
        $.ajax({
            type: "POST",
            url: "{{ route('admin.km.update') }}",
            data: {_token: '{{ csrf_token() }}', pk:bookingId, name:'startKm', value:updatedVal}, 
            success: function(response) {
                $('#startKmLabel_'+bookingId).show();
                $('#startKmLabel_'+bookingId).text(updatedVal);
                $('#startKmText_'+bookingId).hide();
                $('#startKmSpan_'+bookingId).hide();
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
            }
        });
    }else{
        swal.fire({
            title: "Please enter any numbers",
            confirmButtonColor: '#007bff',
            confirmButtonText: 'Ok',
        })
    }
});
$(document).on('click', '.endKmSpan', function(event) {
    var bookingId = $(this).attr('data-id');
    var updatedVal = $('#endKmText_'+bookingId).val();
    if(updatedVal != ''){
        $.ajax({
            type: "POST",
            url: "{{ route('admin.km.update') }}",
            data: {_token: '{{ csrf_token() }}', pk:bookingId, name:'endKm', value:updatedVal}, 
            success: function(response) {
                $('#endKmLabel_'+bookingId).show();
                $('#endKmLabel_'+bookingId).text(updatedVal);
                $('#endKmText_'+bookingId).hide();
                $('#endKmSpan_'+bookingId).hide();
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
            }
        });
    }else{
        swal.fire({
            title: "Please enter any numbers",
            confirmButtonColor: '#007bff',
            confirmButtonText: 'Ok',
        })
    }
});

$(document).on('click', '.viewCustBooking', function(event) {
    var custId = $(this).attr('data-custid');
    // var bookId = $(this).attr('data-bookid');
    var url = '{{ route("admin.customer.bookings", ":custId") }}'.replace(':custId', custId);
    window.location.href = url;
});

$(document).on('click', '.viewVehiBooking', function(event) {
    var vehicleId = $(this).attr('data-vehicleid');
    // var bookId = $(this).attr('data-bookid');
    var url = '{{ route("admin.vehicle.bookings", ":vehicleId") }}'.replace(':vehicleId', vehicleId);
    window.location.href = url;
});

$(document).on('click', '.undoCancelled', function(event) {
    var bookingId = $(this).attr('data-booking-id');
    swal.fire({
        title: "Are you Sure ? you want to Undo Cancelled ?",
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
                url: "{{ route('admin.undo-cancel') }}",
                data: {_token: '{{ csrf_token() }}', bookingId: bookingId },
                success: function(response) {
                   if(response == true){
                        swal.fire({
                            title: "Booking status is now changed from Cancelled to Confirmed",
                            type: 'success',
                        }).then(({value}) => {
                            if (value) {
                                location.reload();
                            }
                        });            
                   }else{
                        swal.fire({
                            title: "Something went wrong",
                            type: 'error',
                        });              
                   }
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                }
            });
        }else{}
    });
});


</script>