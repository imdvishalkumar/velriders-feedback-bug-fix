@extends('templates.admin')

@section('page-title')
    Booking Transaction History
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
                                <h3 class="card-title">Filters</h3>
                            </div>
                        </div>
                        <hr/>
                        <form id="filter-form">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="pickup_date_filter">Pick-up Date</label> <span class="error">*</span>
                                        <!-- <input type="date" class="form-control" name="pickup_date_filter" id="pickup_date_filter"> -->
                                        <div class="input-group date" data-target-input="nearest">
                                            <input type="text" id="pickup_date_filter" name="pickup_date_filter" class="form-control datetimepicker-input" data-target="#pickup_date_filter" placeholder="Select Pickup Date" autocomplete="off" />
                                            <div class="input-group-append" data-target="#pickup_date_filter" data-toggle="datetimepicker">
                                                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                            </div>
                                        </div>
                                        <span id="pickup_date_filter_error"></span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="return_date_filter">Return Date</label> <span class="error">*</span>
                                        <!-- <input type="date" class="form-control" name="return_date_filter" id="return_date_filter"> -->
                                        <div class="input-group date" data-target-input="nearest">
                                            <input type="text" id="return_date_filter" name="return_date_filter" class="form-control datetimepicker-input" data-target="#return_date_filter" placeholder="Select Pickup Date" autocomplete="off"/>
                                            <div class="input-group-append" data-target="#return_date_filter" data-toggle="datetimepicker">
                                                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                            </div>
                                        </div>
                                        <span id="return_date_filter_error"></span>                                        
                                    </div>
                                </div>
                                <div class="col-md-1 mt-4">
                                    <button type="submit" class="btn btn-primary p-3" id="searchDefaultFilterBtn">Search</button>
                                    <button type="button" class="btn btn-primary p-3" id="searchActualFilterBtn">Search</button>
                                </div>
                                <div class="col-md-1 mt-4">
                                    <button type="button" class="btn btn-danger p-3" id="filterClearBtn">Clear</button>
                                </div>
                                <div class="col-md-2 text-right mt-4">
                                    <a href="javascript:void(0);" onclick='exportBookingTransaction("{{url('admin/export-booking-transaction/csv')}}");' class="btn btn-success p-2 mx-2">
                                      <i class="fa fa-file-csv fa-2x"></i>
                                    </a>
                                    <a class="btn btn-danger p-2" onclick='exportBookingTransaction("{{url('admin/export-booking-transaction/pdf')}}");' href="javascript:void(0);">
                                      <i class="fa fa-file-pdf fa-2x"></i>
                                    </a>
                                </div>
                            </div>
                        </form>
                        <div class="row mt-3">
                            <div class="col-md-2">
                                <select id="search_tran_booking" name="search_tran_booking" class="form-control">
                                    <option value="">-- Booking id --</option>
                                        @if(is_countable($bookingIds) && count($bookingIds) > 0)
                                            @foreach($bookingIds as $key => $val)
                                                <option value="{{$val}}">{{$val}}</option>
                                            @endforeach
                                        @endif
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select id="tax_percent" name="tax_percent" class="form-control">
                                    <option value="">-- Tax Percent --</option>
                                        @php $taxPercent = config('global_values.tax_percent'); @endphp
                                        @if(is_countable($taxPercent) && count($taxPercent) > 0)
                                            @foreach($taxPercent as $key => $val)
                                                <option value="{{$val}}">{{$val}} %</option>
                                            @endforeach
                                        @endif
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select id="paid_status" name="paid_status" class="form-control">
                                    <option value="">-- Paid Status --</option>
                                        @php $paidStatus = config('global_values.paid_status'); @endphp
                                        @if(is_countable($paidStatus) && count($paidStatus) > 0)
                                            @foreach($paidStatus as $key => $val)
                                                <option value="{{$key}}" @if($key == 1){{'selected'}}@else{{''}}@endif>{{$val}}</option>
                                            @endforeach
                                        @endif
                                </select>
                            </div>
                             <div class="col-md-3">
                                <select id="search_tran_customer" name="search_tran_customer" class="form-control">
                                    <option value="">-- Customer --</option>
                                        @if(is_countable($customerArr) && count($customerArr) > 0)
                                            @foreach($customerArr as $key => $val)
                                             <option value="{{ $val->customer_id }}">{{ $val->name }} - {{ $val->email }} - {{ $val->mobile_number }}</option>
                                            @endforeach
                                        @endif
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select id="search_tran_vehicle" name="search_tran_vehicle" class="form-control">
                                    <option value="">-- Vehicle --</option>
                                        @if(is_countable($vehicleArr) && count($vehicleArr) > 0)
                                            @foreach($vehicleArr as $key => $val)
                                                <option value="{{$val->vehicle_id}}">{{$val->vehicle_id}} - {{$val->vehicle_name}} - {{$val->license_plate}} @if($val->is_deleted == 1)({{'DELETED'}})@else {{''}}@endif</option>
                                            @endforeach
                                        @endif
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="card-body" id="bookingTransactionHtml">

                    </div>

                </div>
            </div>
        </div>
    </section>

@endsection

@push('scripts')

    <script type="text/javascript" src="{{asset('all_js/admin_js/booking.js')}}"></script>
    
@endpush
