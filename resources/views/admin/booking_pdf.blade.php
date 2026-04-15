<style>
  table {
    border-collapse: collapse;
    width: 100%; /* Or a fixed width in mm */
    table-layout: fixed; /* Important for consistent cell widths */
  }
  th, td {
    border: 1px solid black;
    padding: 5px;
    word-wrap: break-word;
    font-size: 10pt; /* Adjust as needed */
  }
</style>
<table id="bookingPdf">
    <thead>
        <tr>
            <th><b>Booking Id</b></th>
            <th><b>Customer Details</b></th>
            <th><b>Vehicle Details</b></th>
            <th><b>Pickup Date</b></th>
            <th><b>Return Date</b></th>
            <th><b>Start Kilometers</b></th>
            <th><b>End Kilometers</b></th>
            <th><b>Rental Type</b></th>
            <th><b>Status</b></th>
        </tr>
    </thead>
    <tbody>
        @if(is_countable($data) && count($data) > 0)
            @foreach($data as $k => $v)
               <tr>
                    @php
                        $customerDetails = $vehicleDetails = '';
                        if($v->customer->firstname != null && $v->customer->lastname != null){
                            $customerDetails .= ' <b>Name - </b>'.$v->customer->firstname .' '.$v->customer->lastname.'<br/>';
                        }
                        if($v->customer->email != null){
                            $customerDetails .= ' <b>Email - </b>' . $v->customer->email . '<br/>';
                        }
                        if($v->customer->mobile_number != null){
                            $customerDetails .= ' <b>Mobile No. - </b>' . $v->customer->mobile_number . '<br/>';
                        }
                        if($v->customer->dob != null){
                            $customerDetails .= ' <b>Date of Birth. - </b>' . $v->customer->dob . '<br/>';
                        }
                        if($v->customer->documents != null){
                            $customerDetails .= ' <b>Driving License Status - </b>' . $v->customer->documents['dl'] . '<br/>';
                            $customerDetails .= ' <b>GovId Status - </b>' . $v->customer->documents['govtid'];
                        }

                        if($v->vehicle->vehicle_name != null){
                            $vehicleDetails .= ' <b>Model - </b>'.$v->vehicle->vehicle_name.'<br/>';
                        }
                        if($v->vehicle->color != null){
                            $vehicleDetails .= ' <b>Color - </b>'.$v->vehicle->color.'<br/>';
                        }
                        if($v->vehicle->license_plate != null){
                            $vehicleDetails .= ' <b>License Plate - </b>'.$v->vehicle->license_plate.'<br/>';
                        }
                    @endphp
                    <td>{{$v->booking_id}}</td>
                    <td>{!! $customerDetails !!}</td>
                    <td>{!! $vehicleDetails !!}</td>
                    <td>{{date('d-m-Y H:i', strtotime($v->pickup_date))}}</td>
                    <td>{{date('d-m-Y H:i', strtotime($v->return_date))}}</td>
                    <td>{{$v->start_kilometers ?? 0}}</td>
                    <td>{{$v->end_kilometers ?? 0}}</td>
                    <td>{{$v->rental_type ?? ''}}</td>
                    <td>{{strtoupper($v->status)}}</td>
                </tr>
            @endforeach
        @endif
    </tbody>
</table> 