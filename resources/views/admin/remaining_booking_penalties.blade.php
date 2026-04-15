@extends('templates.admin')

@section('page-title')
    Remianing Bookings Penalties
    @if (session('success'))
        <div id="success-message" class="alert alert-success">
            {{ session('success') }}
        </div>
    @elseif(session('error'))
        <div id="error-message" class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
@endsection
<style>
  
</style>

@section('content')
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <table id="booking-penalties-table" class="table table-bordered table-striped table-responsive">
                    <thead>
                        <tr>
                            <th>Booking Id</th>
                            <th>Customer Details</th>
                            <th>Vehicle Details</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Late Return </th>
                            <th>Exceeded km. Limit</th>
                            <th>Additional Charges</th>
                            <th>Additional Charges Info</th>
                            <th>Tax Amount</th>
                            <th>Amount to Pay</th>
                            <th>Edit Penalties</th>
                        </tr>
                    </thead>
                    <tbody class="table-data">
                        @if(is_countable($bookingTransaction) && count($bookingTransaction) > 0)
                            @foreach($bookingTransaction as $k => $v)
                                @php $vehicleDetails = $customerDetails = '';
                                    if($v['rentalBooking']['customer']['firstname'] != null && $v['rentalBooking']['customer']['lastname'] != null){
                                        $customerDetails .= ' <b>Name - </b>'.$v['rentalBooking']['customer']['firstname'] .' '.$v['rentalBooking']['customer']['lastname'].'<br/>';
                                    }
                                    if($v['rentalBooking']['customer']['email'] != null){
                                        $customerDetails .= ' <b>Email - </b>' . $v['rentalBooking']['customer']['email'] . '<br/>';
                                    }
                                    if($v['rentalBooking']['customer']['mobile_number'] != null){
                                        $customerDetails .= ' <b>Mobile No. - </b>' . $v['rentalBooking']['customer']['mobile_number'] . '<br/>';
                                    }

                                    if($v['rentalBooking']['vehicle']['vehicle_name'] != null){
                                        $vehicleDetails .= ' <b>Model - </b>'.$v['rentalBooking']['vehicle']['vehicle_name'].'<br/>';
                                    }
                                    if($v['rentalBooking']['vehicle']['color'] != null){
                                        $vehicleDetails .= ' <b>Color - </b>'.$v['rentalBooking']['vehicle']['color'].'<br/>';
                                    }
                                    if($v['rentalBooking']['vehicle']['license_plate'] != null){
                                        $vehicleDetails .= ' <b>License Plate - </b>'.$v['rentalBooking']['vehicle']['license_plate'].'<br/>';
                                    }
                                @endphp
                               <tr>
                                    <td>{{$v['booking_id']}}</td>
                                    <td>{!! $customerDetails !!}</td>
                                    <td>{!! $vehicleDetails !!}</td>
                                    <td>{{$v['start_date'] ? date('d-m-Y H:i', strtotime($v['start_date'])) : '-'}}</td>
                                    <td>{{$v['end_date'] ? date('d-m-Y H:i', strtotime($v['end_date'])) : '-'}}</td>
                                    <td>₹ {{round($v['late_return']) ?? 0}}</td>
                                    <td>₹ {{round($v['exceeded_km_limit']) ?? 0}}</td>
                                    <td>₹ {{$v['additional_charges'] ?? 0}}</td>
                                    <td>{{$v['additional_charges_info'] ?? '-'}}</td>
                                    <td>₹ {{$v['tax_amt'] ?? 0}}</td>
                                    <td>₹ {{$v['amount_to_pay'] ?? 0}}</td>
                                    <td><a href="javascript:void(0);" data-id="{{$v['id']}}" class="editPenalties">Click to Edit</a></td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table> 
                <div class="row">
                    <div class="col-sm-12">
                        <div class="d-flex justify-content-end mr-5">
                            {{ $bookingTransaction->links() }} 
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<!-- EDIT PENALTY POPUP -->
<div class="modal fade modal-ld" id="editPenaltyModal" tabindex="-1" role="dialog" aria-labelledby="editPenaltyModalTitle" aria-hidden="true" data-keyboard="false" data-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Edit Penalty</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          </div>
          <form id="edit-penalty-form" name="edit-penalty-form" enctype="multipart/form-data" method="POST" action="{{route('admin.store-completion-penalties')}}">
            @csrf
              <div class="modal-body">
                <div id="penaltyDetails">
                    <div class="row">
                        <input type="hidden" class="form-control" id="transaction_id" name="transaction_id">
                        <div class="col-md-4"><label>Late Return</label>
                            <input type="text" class="form-control" id="exceed_hours_limit" name="exceed_hours_limit">
                        </div>
                        <div class="col-md-4"><label>Exceed KM. Limit</label>
                            <input type="text" class="form-control" id="exceed_km_limit" name="exceed_km_limit">
                        </div>
                        <div class="col-md-4"><label>Additional Charges</label>
                            <input type="text" class="form-control" id="admin_penalty" name="admin_penalty">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12"><label>Additional Charges Info</label>
                            <input type="text" class="form-control" id="admin_penalty_info" name="admin_penalty_info">
                        </div>
                    </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="submit" class="btn btn-primary" id="endBooking">Edit Penalty</button>
              </div>
          </form>
      </div>
    </div>
</div>
@endsection

@push('scripts')
    <script type="text/javascript" src="{{asset('all_js/admin_js/booking.js')}}"></script>
@endpush