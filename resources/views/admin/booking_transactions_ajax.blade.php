<table id="example1" class="table table-bordered table-striped table-responsive">
    <thead>
        <tr>
            <th>Booking Id</th>
            <th>Invoice Id</th>
            <th>Transaction Id</th>
            <th>Customer Details</th>
            <th>Vehicle Details</th>
            <th>Pickup Date</th>
            <th>Return Date</th>
            <th>Taxable Amount</th>
            <th>Tax Details</th>
            <th>Convineince Amount</th>
            <th>Type</th>
            <th>Paid Status</th>
            <th>Used Payment Gateway</th>
            <th>Payment Gateway Charges (In Rs.)</th>
            <th>Vehicle Commission </th>
            <th>Final Amount</th>
            <th>Creation Date</th>
        </tr>
    </thead>
    <tbody class="table-data">
        @if(is_countable($data['rentalBookingTransactions']) && count($data['rentalBookingTransactions']) > 0)
            @foreach($data['rentalBookingTransactions'] as $k => $v)
               <tr>
                    @php
                        $customerDetails = $vehicleDetails = $taxDetails = $vehicleCommission = '';
                        if($v['vehicle_commission_amount'] > 0){
                            $vehicleCommission .= ' <b>Vehicle Commission Amount - </b> ₹ ' .$v['vehicle_commission_amount']. '<br/>';
                        }
                        if($v['vehicle_commission_tax_amt'] > 0){
                            $vehicleCommission .= '<b>Vehicle Commission Tax Amount - </b> ₹ ' .$v['vehicle_commission_tax_amt'].'<br/>';
                        }
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
                        
                        $taxDetails .= ' <b> Amount </b>₹ '.$v['taxAmt'].'<br/>';
                        $taxDetails .= ' <b> Percent </b>'.$taxPercent.' % <br/>';
                        
                        $paymentGateway = usedPaymentGateway($v['booking_id'], $v['type'], $v['razorpay_order_id'], $v['cashfree_order_id']);
                    @endphp
                    <td class="text-info font-weight-bold">{{$v['booking_id']}}</td>
                    <td class="text-secondary font-weight-bold">{{$v['rentalBooking']['sequence_no'] ?? '-'}}</td>
                    <td class="text-danger font-weight-bold">{{$v->id}}</td>
                    <td>{!! $customerDetails !!}</td>
                    <td>{!! $vehicleDetails !!}</td>
                    <td>{{ $v['start_date'] ? date('d-m-Y H:i', strtotime($v['start_date'])) : '-'}}</td>
                    <td>{{ $v['end_date'] ? date('d-m-Y H:i', strtotime($v['end_date'])) : '-'}}</td>
                    <td>₹ {{$v['taxableAmt'] ?? 0}}</td>
                    <td>{!! $taxDetails ?? '' !!}</td>
                    <td>₹ {{$v['convenienceFees']}}</td>
                    <td class="text-primary"><b>{{strtoupper($v->type)}}</b></td>
                    <td>
                        @if($v->paid == 1)
                            <span class="text-success font-weight-bold">{{'PAID'}}</span>
                        @else
                            <span class="text-danger font-weight-bold">{{'NOT PAID'}}</span>
                        @endif
                    </td>
                    <td>{{$paymentGateway['payment_gateway']}}</td>
                    <td>{{$paymentGateway['payment_gateway_charges']}}</td>
                    <td>{!! $vehicleCommission ?? '-' !!}</td>
                    <td>₹ {{$v['finalAmt']}}</td>
                    <td>{{date('d-m-Y H:i', strtotime($v['timestamp']))}}</td>
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
                                   onclick="loadBookingTransactionHistory( {{ $data['pageno'] - 1}} )"  
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
                                <a class="page-link" href="javascript:void(0);" onclick="loadBookingTransactionHistory(1)">1</a>
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
                                <a class="page-link" href="javascript:void(0);" onclick="loadBookingTransactionHistory({{ $i }})">
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
                                <a class="page-link" href="javascript:void(0);" onclick="loadBookingTransactionHistory({{ $totalPages }})">{{ $totalPages }}</a>
                            </li>
                        @endif
                        <!-- Next Button -->
                        <li class="page-item">
                            <a class="page-link" href="javascript:void(0);" 
                               @if($currentPage < $totalPages) 
                                   onclick="loadBookingTransactionHistory( {{ $currentPage + 1 }} )"  
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
   
$(document).ready(function(){
 
});


</script>