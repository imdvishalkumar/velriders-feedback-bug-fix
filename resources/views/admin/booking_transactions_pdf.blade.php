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
            <th><b>Invoice Id</b></th>
            <th><b>Transaction Id</b></th>
            <th><b>Customer Details</b></th>
            <th><b>Vehicle Details</b></th>
            <th><b>Pickup Date</b></th>
            <th><b>Return Date</b></th>
            <th><b>Taxable Amount (In ₹)</b></th>
            <th><b>Tax Details</b></th>
            <th><b>Convineince Amount(In ₹)</b></th>
            <th><b>Final Amount(In ₹)</b></th>
            <th><b>Type</b></th>
            <th><b>Paid Status</b></th>
            <th><b>Creation Date</b></th>
        </tr>
    </thead>
    <tbody>
        @if(is_countable($chunk) && count($chunk) > 0)
            @foreach($chunk as $k => $v)
               <tr>
                    @php
                        $customerDetails = $vehicleDetails = $taxDetails = '';
                        if($v['rentalBooking']['customer']){
                            if($v['rentalBooking']['customer']['firstname'] != null && $v['rentalBooking']['customer']['lastname'] != null){
                                $customerDetails .= ' <b>Name - </b>'.$v['rentalBooking']['customer']['firstname'] .' '.$v['rentalBooking']['customer']['lastname'].'<br/>';
                            }
                            if($v['rentalBooking']['customer']['email'] != null){
                                $customerDetails .= ' <b>Email - </b>' . $v['rentalBooking']['customer']['email'] . '<br/>';
                            }
                            if($v['rentalBooking']['customer']['mobile_number'] != null){
                                $customerDetails .= ' <b>Mobile No. - </b>' . $v['rentalBooking']['customer']['mobile_number'] . '<br/>';
                            }
                            if($v['rentalBooking']['customer']['dob'] != null){
                                $customerDetails .= ' <b>Date of Birth. - </b>' . $v['rentalBooking']['customer']['dob'] . '<br/>';
                            }
                            if($v['rentalBooking']['customer']['documents'] != null){
                                $customerDetails .= ' <b>Driving License Status - </b>' . $v['rentalBooking']['customer']['documents']['dl'] . '<br/>';
                                $customerDetails .= ' <b>GovId Status - </b>' . $v['rentalBooking']['customer']['documents']['govtid'];
                            }
                        }

                        if($v['rentalBooking']['vehicle']){
                            if($v['rentalBooking']['vehicle']['vehicle_name'] != null){
                                $vehicleDetails .= ' <b>Model - </b>'.$v['rentalBooking']['vehicle']['vehicle_name'].'<br/>';
                            }
                            if($v['rentalBooking']['vehicle']['color'] != null){
                                $vehicleDetails .= ' <b>Color - </b>'.$v['rentalBooking']['vehicle']['color'].'<br/>';
                            }
                            if($v['rentalBooking']['vehicle']['license_plate'] != null){
                                $vehicleDetails .= ' <b>License Plate - </b>'.$v['rentalBooking']['vehicle']['license_plate'].'<br/>';
                            }
                        }

                        $taxPercent = 5;
                        if($v['rentalBooking']['customer']['gst_number'] != null){
                            $taxPercent = 12;
                        }
                        
                        $taxDetails .= ' <b> Amount - </b>'.$v['tax_amt'].'<br/>';
                        $taxDetails .= ' <b> Percent - </b>'.$taxPercent.' % <br/>';
                    
                    @endphp
                    <td class="text-info font-weight-bold">{{$v->booking_id}}</td>
                    <td class="text-secondary font-weight-bold">{{$v['rentalBooking']['sequence_no'] ?? '-'}}</td>
                    <td class="text-danger font-weight-bold">{{$v->id}}</td>
                    <td>{!! $customerDetails !!}</td>
                    <td>{!! $vehicleDetails !!}</td>
                    <td>{{ $v['start_date'] ? date('d-m-Y H:i', strtotime($v['start_date'])) : '-'}}</td>
                    <td>{{ $v['end_date'] ? date('d-m-Y H:i', strtotime($v['end_date'])) : '-'}}</td>
                    <td>{{$v['taxableAmt'] ?? 0}}</td>
                    <td>{!! $taxDetails ?? '' !!}</td>
                    <td>{{$v['convenienceFees']}}</td>
                    <td>{{$v['finalAmt']}}</td>
                    <td class="text-primary"><b>{{strtoupper($v->type)}}</b></td>
                    <td>
                        @if($v->paid == 1)
                            <span class="text-success font-weight-bold">{{'PAID'}}</span>
                        @else
                            <span class="text-danger font-weight-bold">{{'NOT PAID'}}</span>
                        @endif
                    </td>
                    <td>{{date('d-m-Y H:i', strtotime($v['timestamp']))}}</td>     
               </tr>
            @endforeach
        @endif
    </tbody>
</table> 