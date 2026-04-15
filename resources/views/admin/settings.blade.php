@extends('templates.admin')

@section('page-title')
    Settings
@endsection

@section('content')
    @if (session('success'))
    <div id="success-message" class="alert alert-success">
        {{ session('success') }}
    </div>
    @endif
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">App Details</h3>

                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                            <!-- <button type="button" class="btn btn-tool" data-card-widget="remove" title="Remove">
                                <i class="fas fa-times"></i>
                            </button> -->
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped projects">
                            <thead>
                                <tr>
                                    <th style="width: 1%">
                                        OS Name
                                    </th>
                                    <th style="width: 30%">
                                        App Details
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="table-data">
                            </tbody>
                        </table>
                    </div>
                    <!-- /.card-body -->
                </div>

                <div class="row">
                    <div class="col-md-5">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><b>Vehicle Show Flag</b></h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-check form-switch m-3">
                                          <input class="form-check-input showAll" type="checkbox" role="switch" id="show_all_vehicle" @if($setting != '' && $setting->show_all_vehicle == 1){{'checked'}}@else{{''}}@endif>
                                          <label class="form-check-label" for="show_all_vehicle">Show All Vehicle</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><b>Vehicle Booking Gap (In Minutes)</b></h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="row">
                                    <div class="col-md-6">
                                        <input class="form-control m-3" type="number" name="booking_gap" id="booking_gap" placeholder="Enter Minutes" @if($setting != '' && isset($setting->booking_gap))value="{{$setting->booking_gap}}"@else value="" @endif>
                                    </div>
                                    <div class="col-md-3">
                                        <button class="form-control btn btn-primary m-3" id="booking_gap_btn" type="button">Update</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                     <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><b>Vehicle Offer Details</b></h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <form class="card-body" id="offer-dates-form" nam="offer-dates-form" action="{{route('admin.store.offers-dates')}}" method="POST" enctype="multipart/form-data">
                                @csrf
                                    @if((is_countable($offerDates) && count($offerDates) <= 0) || $offerDates == '' || $offerDates == NULL )
                                    <div class="col-md-1 mt-2">
                                        <a href="javascript:void(0);" id="addOfferDates"><i class="fas fa-plus-square fa-3x"></i></a> 
                                    </div>
                                    @endif
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div id="offerDatesAppend">
                                                @if($offerDates != '')
                                                @foreach($offerDates as $key => $val)
                                                <div class="row">
                                                    <div class="col-md-2">
                                                        <label for="offer_start_date_exist_{{$key}}">Offer Start Date</label> 
                                                        <div class="input-group date" data-target-input="nearest">
                                                            <input type="text" class="form-control datetimepicker-input offerstartdate" id="offer_start_date_exist_{{$key}}" name="offer_start_date_exist[{{$key}}]" data-id="{{$key}}" @isset($val->vehicle_offer_start_date)value="{{date('d-m-Y H:i', strtotime($val->vehicle_offer_start_date))}}"@endisset data-target="#offer_start_date_exist_{{$key}}" placeholder="Select From Date Time" autocomplete="off" />
                                                            <div class="input-group-append" data-target="#offer_start_date_exist_{{$key}}" data-toggle="datetimepicker">
                                                                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                                            </div>
                                                        </div>
                                                        <span id="offer_start_date_exist_error_{{$key}}"></span>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label for="offer_end_date_exist_{{$key}}">Offer End Date</label>     
                                                        <div class="input-group date" data-target-input="nearest">
                                                            <input type="text" id="offer_end_date_exist_{{$key}}" name="offer_end_date_exist[{{$key}}]" data-id="{{$key}}" class="form-control datetimepicker-input offerenddate offerexistend" data-target="#offer_end_date_exist_{{$key}}" placeholder="Select To Date Time" @isset($val->vehicle_offer_end_date)value="{{date('d-m-Y H:i', strtotime($val->vehicle_offer_end_date))}}"@endisset autocomplete="off" />
                                                            <div class="input-group-append" data-target="#offer_end_date_exist_{{$key}}" data-toggle="datetimepicker">
                                                                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                                            </div>
                                                        </div>
                                                        <span id="offer_end_date_exist_error_{{$key}}"></span>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label for="offer_price_exist_{{$key}}">Vehicle Offer Price (In %)</label>     
                                                        <input type="text" id="offer_price_exist_{{$key}}" min="0" max="100" name="offer_price_exist[{{$key}}]" data-id="{{$key}}" class="form-control" data-target="#offer_price_exist_{{$key}}" placeholder="Select Offer Price" @isset($val->vehicle_offer_price)value="{{$val->vehicle_offer_price}}"@endisset autocomplete="off" />
                                                        <span id="offer_price_exist_error_{{$key}}"></span>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label for="vehicle_exist_{{$key}}">Vehicle</label> 
                                                        <select id="vehicle_exist_{{$key}}" name="vehicle_exist[{{$key}}]" class="form-control vehiclevalidate" required>
                                                            <option value="">-- Update Vehicle --</option>
                                                            @if(is_countable($vehicles) && count($vehicles) > 0)
                                                                @foreach($vehicles as $vkey => $vval)
                                                                    <option value="{{$vval->vehicle_id}}" @if($val->vehicle_id == $vval->vehicle_id){{'selected'}}@else{{''}}@endif>{{$vval->vehicle_id}} - {{$vval->vehicle_name}} - {{$vval->license_plate}}</option>
                                                                @endforeach
                                                            @endif
                                                        </select>
                                                    </div>

                                                    @if($key == 0)
                                                        <div class="col-md-1 mt-4">
                                                            <a href="javascript:void(0);" id="addOfferDates"><i class="fas fa-plus-square fa-3x"></i></a>
                                                        </div>
                                                        <div class="col-md-1 mt-4">
                                                            <a href="javascript:void(0);" class="btn btn-danger" id="clearOfferDates" data-id="{{$key}}">Clear Dates</a> 
                                                        </div>
                                                    @else
                                                        <div class="col-md-1 mt-4">
                                                            <a href="javascript:void(0);" id="deleteOfferDate"><i class="text-danger fas fa-minus-square fa-3x"></i></a>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach

                                                @endif
                                            </div>
                                        </div>
                                        <!-- <div class="col-md-3 m-3 pt-3">
                                            <label id="">Offer Start Date</label>
                                            <div class="input-group date" data-target-input="nearest">
                                                <input type="text" id="offer_start_date" class="form-control datetimepicker-input" data-target="#offer_start_date" placeholder="Select From Date" data-existing-date="@isset($setting->vehicle_offer_start_date){{date('d-m-Y H:i', strtotime($setting->vehicle_offer_start_date))}}@endisset" @isset($setting->vehicle_offer_start_date)value="{{date('d-m-Y H:i', strtotime($setting->vehicle_offer_start_date))}}"@endisset/>
                                                <div class="input-group-append" data-target="#offer_start_date" data-toggle="datetimepicker">
                                                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 m-3 pt-3">
                                            <label id="">Offer End Date</label>
                                            <div class="input-group date" data-target-input="nearest">
                                                <input type="text" id="offer_end_date" class="form-control datetimepicker-input" data-target="#offer_end_date" placeholder="Select End Date" data-existing-date="@isset($setting->vehicle_offer_end_date){{date('d-m-Y H:i', strtotime($setting->vehicle_offer_end_date))}}@endisset" @isset($setting->vehicle_offer_end_date)value="{{date('d-m-Y H:i', strtotime($setting->vehicle_offer_end_date))}}"@endisset/>
                                                <div class="input-group-append" data-target="#offer_end_date" data-toggle="datetimepicker">
                                                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                                </div>
                                            </div>
                                        </div> -->
                                    </div>
                                    <div class="row">
                                        <div class="col-md-2 mt-5">
                                            <button class="form-control btn btn-primary m-3" id="vehicle_offer_price_btn" type="submit">Update</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title form-title">Edit App Details</h3>
                    </div>
                    <form action="{{ route('submit.app-details') }}" method="POST" enctype="multipart/form-data" id="app-details">
                        @csrf
                        <input type="hidden" id="aId" name="aId" value="">
                        <div class="card-body mt-2">
                            <div class="form-group">
                                <label for="os_type">OS Type</label><span class="text-danger">*</span>
                                <select class="form-control" id="os_type" name="os_type" disabled>
                                    <option value="">Select OS Type</option>
                                    @php $osType = config('global_values.os_type'); @endphp
                                    @foreach($osType as $key => $val)
                                        <option value="{{ $key }}">{{ $val }}</option>
                                    @endforeach
                                </select>
                            </div>         
                            <div class="form-group">
                                <label for="version">Version</label><span class="text-danger">*</span>
                                <input type="text" class="form-control" id="version" name="version" placeholder="Enter Version" autocomplete="off">
                            </div>
                            <div class="form-group">
                                <label for="maintenance">Maintenance</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="maintenance" name="maintenance" value="1">
                                    <label class="form-check-label" for="maintenance"></label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="alert_title">Alert Title</label><span class="text-danger">*</span>
                                <input type="text" class="form-control" id="alert_title" name="alert_title" placeholder="Enter Alert Title" required autocomplete="off">
                            </div>
                            <div class="form-group">
                                <label for="alert_message">Alert Message</label><span class="text-danger">*</span>
                                <input type="text" class="form-control" id="alert_message" name="alert_message" placeholder="Enter Alert Message" required autocomplete="off">
                            </div>
                        </div>
                        <div class="card-footer mt-2 mb-3">
                            <button type="submit" class="btn btn-primary btn-submit" name="submit">Update</button>
                            <button type="button" class="btn btn-danger btn-clear">Clear</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><b>Payment Gateway Details</b></h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="row">
                            <div class="col-md-2 m-3 pt-3">
                                <label for="start_time">Payment Details change from time</label>
                                <div class="input-group date" data-target-input="nearest">
                                    <input type="text" id="start_time" class="form-control datetimepicker-input" data-target="#start_time" placeholder="Select Time" @isset($setting->payment_gateway_alter_start_time)value="{{date('H:i', strtotime($setting->payment_gateway_alter_start_time))}}"@endisset>
                                </div>
                            </div>
                            <div class="col-md-2 m-3 pt-3">
                                <label for="end_time">Payment Details change to time</label>
                                <div class="input-group date" data-target-input="nearest">
                                    <input type="text" id="end_time" class="form-control datetimepicker-input" data-target="#end_time" placeholder="Select Time" @isset($setting->payment_gateway_alter_end_time)value="{{date('H:i', strtotime($setting->payment_gateway_alter_end_time))}}"@endisset/>
                                </div>
                            </div>
                           {{-- @if($checkSetting->isNotEmpty()) --}}
                            <div class="col-md-4 m-3 pt-3 ml-3">
                                <label for="payment_gateway">Activate Payment Gateway</label>
                                <div class="form-check">
                                  <input class="form-check-input" type="radio" name="payment_gateway" id="razorpay" value="razorpay" @if(isset($setting->payment_gateway_type) && $setting->payment_gateway_type == 'razorpay'){{'checked'}}@else{{''}}@endif>
                                  <label class="form-check-label" for="razorpay">Razorpay</label>
                                </div>
                                <div class="form-check">
                                  <input class="form-check-input" type="radio" name="payment_gateway" id="cashfree" value="cashfree" @if(isset($setting->payment_gateway_type) && $setting->payment_gateway_type == 'cashfree'){{'checked'}}@else{{''}}@endif>
                                  <label class="form-check-label" for="cashfree">Cashfree</label>
                                </div>
                            </div>
                            {{-- @endif --}}
                            <div class="col-md-2 mt-5">
                                <button class="form-control btn btn-primary m-3" id="payment_gateway_btn" type="button">Update</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
         <div class="row mb-3">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><b>Refer & Earn Details</b></h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="row">
                            <div class="col-md-2 m-3 pt-3">
                                <label for="reward_type">Rewards Type</label>
                                <select id="reward_type" name="reward_type" class="form-control">
                                    <!-- <option value="">-Select-</option> -->
                                    @php $rewardTypes = config('global_values.reward_types'); @endphp
                                    @if(is_countable($rewardTypes) && count($rewardTypes) > 0)
                                        @foreach($rewardTypes as $key => $val)
                                            <option value="{{$key}}" @if($key == 2){{'selected'}}@endif>{{$val}}</option>
                                        @endforeach
                                       {{--
                                        @foreach($rewardTypes as $key => $val)
                                            <option value="{{$key}}" @if(isset($setting->reward_type) && $setting->reward_type == $key){{'selected'}}@else{{''}}@endif>{{$val}}</option>
                                        @endforeach
                                        --}} 
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-2 m-3 pt-3">
                                <label for="reward_val">Reward Amount/Percent</label>
                                <input type="text" class="form-control" id="reward_val" name="reward_val" placeholder="Enter" autocomplete="off" @if(isset($setting->reward_val))value="{{$setting->reward_val}}"@else value=""@endif>
                            </div>
                            <div class="col-md-2 m-3 pt-3">
                                <label for="max_discount_amount">Percent Maximum Discount Amount </label>
                                <input type="text" class="form-control" id="max_discount_amount" name="max_discount_amount" placeholder="Enter" autocomplete="off" @if(isset($setting->reward_max_discount_amount))value="{{$setting->reward_max_discount_amount}}"@else value="0"@endif>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-10 m-3 pt-3">
                                <label for="reward_html">Reward Html</label>
                                <textarea id="reward_html" name="reward_html" rows="5" class="form-control">@if(isset($setting->reward_html)){!! $setting->reward_html !!}@else{{''}}@endif</textarea>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-2 mt-2 m-2">
                                <button class="form-control btn btn-primary" id="refer_earn_btn" type="button">Update</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </section>
@endsection

@push('scripts')
<script src="{{ asset('all_js/admin_js/settings.js') }}"></script>
<script>
const Toast = new Notyf({
    position: {
        x: 'center',
        y: 'top',
    }
});
$(document).on('click', "#refer_earn_btn", function() {
    var rewardType = $('#reward_type').val();
    var rewardVal = $('#reward_val').val();
    var maxDiscountAmount = $('#max_discount_amount').val();
    var rewardHtml = CKEDITOR.instances.reward_html.getData();
    
    if(rewardType != '' && rewardVal != ''){
        if(rewardType != '' && rewardType == 2 && (maxDiscountAmount == '' || maxDiscountAmount == 0)){
            Toast.open({
                type: 'error',
                message: "Max Discount Amount is Mandatory",
                background: 'red',
                duration: 3000,
            }); 
        }else{
            $.ajax({
                url: sitePath + '/admin/submit-referearn-details',
                type: "POST",
                data: {
                    "_token":  $('meta[name="csrf-token"]').attr('content'), 
                    'rewardType' : rewardType, 
                    'rewardVal' : rewardVal,
                    'rewardHtml' : rewardHtml,
                    'maxDiscountAmount' : maxDiscountAmount,
                },
                success: function(response) {
                    if(response.status){
                        Toast.open({
                        type: 'success',
                        message: response.message,
                        background: 'green',
                            duration: 3000,
                        }); 
                        setTimeout(function() {
                            location.reload(); // Reload the page
                        }, 3000);     
                    }else{
                        Toast.open({
                            type: 'error',
                            message: response.message,
                            background: 'red',
                            duration: 3000,
                        });
                    }
                }
            });
        }
    }else{
        Toast.open({
            type: 'error',
            message: "Reward type and Reward value is Mandatory",
            background: 'red',
            duration: 3000,
        }); 
    } 
}); 
</script>
<script>
    $(document).ready(function() {
        CKEDITOR.replace('reward_html', {
            versionCheck: false,
        });
        function loadFuelTypes() {
            $.ajax({
                url: "{{ route('admin.get-app-details') }}",
                type: "GET",
                beforeSend: function() {
                    $('.table-data').html(
                        '<tr><td colspan="9" class="text-center">Loading...</td></tr>');
                },
                success: function(response) {
                    let appDetails = response.data;
                    let html = '';
                    appDetails.forEach(app => {
                        var os = appInfo = '';
                        if(app.os_type == 1){
                            os = 'Android';
                        }else{
                            os = 'IOS';
                        }
                        if(app.version){
                            appInfo = "<b>Version</b> - " + app.version + "<br/>";
                        }
                        if(app.maintenance){
                            appInfo += "<b>Maintenance</b> - " + app.maintenance + "<br/>";
                        }
                        if(app.alert_title){
                            appInfo += "<b>Alert Title</b> - " + app.alert_title + "<br/>";
                        }
                        if(app.alert_message){
                            appInfo += "<b>Alert Message</b> - " + app.alert_message + "<br/>";
                        }

                        html += `<tr>
                        <td>${os}</td>
                        <td>${appInfo}</td>
                        <td class="project-actions text-right">
                            <a class="btn btn-info btn-sm update-btn" data-operationId='${app.id}'>
                                <i class="fas fa-pencil-alt">
                                </i>
                                Edit
                            </a>
                        </td>
                    </tr>`;
                    });
                    $('.table-data').html(html);
                }
            });
        }

        $('.btn-clear').click(function() {
            $('#app-details')[0].reset();
            $('#maintenance').attr('checked', false);
        });

        $(document).on('click', ".update-btn", function() {
            let appId = $(this).data('operationid');
            let loading = undefined;
            $.ajax({
                url: "{{ route('admin.get-app-detail') }}",
                type: "GET",
                data: {
                    id: appId
                },
                success: function(response) {
                    let app = response.data;
                    $('#app-details').attr('data-updateId', app.app_type_id);
                    $('#os_type').val(app.os_type);
                    $('#version').val(app.version);
                    $('#alert_title').val(app.alert_title);
                    $('#alert_message').val(app.alert_message);
                    if(app.maintenance)
                        $('#maintenance').attr('checked', true);
                    else
                        $('#maintenance').attr('checked', false);
                    $('.btn-clear').removeClass('d-none');
                    $('#aId').val(app.id);
                }
            });
        });
        loadFuelTypes();
    });

    const vehicleOptions = {!! json_encode($vehicles->map(function($vehicle) {
        return [
            'id' => $vehicle->vehicle_id,
            'name' => "{$vehicle->vehicle_id} - {$vehicle->vehicle_name} - {$vehicle->license_plate}"
        ];
    })) !!};
</script>
@endpush
