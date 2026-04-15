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
  
</style>

@section('content')
<section class="content">
    <div class="card p-4">
        <label><h5><b>You are showing the bookings of below Vehicle</b></h5></label><hr/>
        <div class="row">
            {!! $vehicleInfo !!}
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <table id="example1" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Booking Id</th>
                            <th>Invoice Id</th>
                            <th>Customer Details</th>
                            <th>Pickup Date</th>
                            <th>Return Date</th>
                            <th>Rental <br/> Duration<br/>Minutes</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Start <br/>Kilometers</th>
                            <th>End <br/>Kilometers</th>
                            <th>Rental Type</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody class="table-data">
                        @if(is_countable($rentalBooking) && count($rentalBooking) > 0)
                            @foreach($rentalBooking as $k => $v)
                              @php $customerDetails = ''; 
                                if($v['customer']['firstname'] != null && $v['customer']['lastname'] != null){
                                    $customerDetails .= ' <b>Name - </b>'.$v['customer']['firstname'] .' '.$v['customer']['lastname'].'<br/>';
                                }
                                if($v['customer']['email'] != null){
                                    $customerDetails .= ' <b>Email - </b>' . $v['customer']['email'] . '<br/>';
                                }
                                if($v['customer']['mobile_number'] != null){
                                    $customerDetails .= ' <b>Mobile No. - </b>' . $v['customer']['mobile_number'] . '<br/>';
                                }
                              @endphp
                               <tr>
                                    <td>{{$v->booking_id}}</td>
                                    <td>{{$v->sequence_no ?? '-'}}</td>
                                    <td>{!! $customerDetails !!}</td>
                                    <td>{{date('d-m-Y H:i', strtotime($v['pickup_date']))}}</td>
                                    <td>{{date('d-m-Y H:i', strtotime($v['return_date']))}}</td>

                                    <td>{{$v['rental_duration_minutes']}}</td>
                                    <td>{{$v['start_datetime'] ? date('d-m-Y H:i', strtotime($v['start_datetime'])) : '-'}}</td>
                                    <td>{{$v['end_datetime'] ? date('d-m-Y H:i', strtotime($v['end_datetime'])) : '-'}}</td>

                                    <td>{{$v['start_kilometers'] ?? 0}}</td>
                                    <td>{{$v['end_kilometers'] ?? 0}}</td>
                                    <td>@isset($v['rental_type']){{$v['rental_type']}}@endisset</td>
                                    <td class="text-info font-weight-bold">@isset($v['status']){{strtoupper($v['status'])}}@endisset</td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table> 
                <div class="row m-1">
                    <div class="col-md-3">
                        <a class="btn btn-success" href="{{route('admin.bookings')}}">Back to Booking History</a>
                    </div>
                    <div class="col-sm-9">
                        <div class="d-flex justify-content-end mr-5"
                            {{ $rentalBooking->links() }} 
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

@endsection