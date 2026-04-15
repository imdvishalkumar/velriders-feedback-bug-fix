<html>
<style>
* { font-family: DejaVu Sans, sans-serif; }
@page { 
    margin-top: 150px;
    margin-right: 0px;
    margin-left: 0px;
    margin-bottom: 100px;
}
#header { 
    position: fixed; 
    top: 30px; 
    left: 0px; 
    right: 0px; 
    text-align: center; 
    font-weight: bold; 
}

.page-break {
    page-break-before: always;
}
.column-bordered-table {
    border: 2px solid #000;
    border-collapse: collapse;
    width: 720px;
    margin: 0 auto;
}
.column-bordered-table td, 
.column-bordered-table th {
    border: 2px solid #000;
    padding: 3px;
}
@font-face{
    font-family: 'Gabarito';
    font-style:'normal';
    font-weight:400;
    src: url('https://fonts.gstatic.com/s/gabarito/v1/u-470qkzMWQ8Jo6yPEiSxLpg.ttf') format('truetype');
}
body {
    font-family: 'Gabarito', sans-serif;
}
</style>
<body>
    <header id="header" style="margin-bottom: 20px;">
        @php 
            $companyAdd = '';
            $companyPhone = ''; 
            $companyAltPhone = ''; 
            $companyEmail =  '';
            $companyGst =  '';
            $companyPan =  '';
            $companyBankName = ''; 
            $companyBankAccNo =  '';
            $companyBankIfsc =  '';
            if(isset($companyDetails) && $companyDetails != ''){
                $companyAdd = @isset($companyDetails->address)?$companyDetails->address:'';
                $companyPhone = @isset($companyDetails->phone)?$companyDetails->phone:''; 
                $companyAltPhone = @isset($companyDetails->alt_phone)?$companyDetails->alt_phone:''; 
                $companyEmail =  @isset($companyDetails->email)?$companyDetails->email:'';
                $companyGst =  @isset($companyDetails->gst_no)?$companyDetails->gst_no:'';
                $companyPan =  @isset($companyDetails->pan_no)?$companyDetails->pan_no:'';
                $companyBankName = @isset($companyDetails->bank_name)?$companyDetails->bank_name:''; 
                $companyBankAccNo =  @isset($companyDetails->bank_account_no)?$companyDetails->bank_account_no:'';
                $companyBankIfsc =  @isset($companyDetails->bank_ifsc_code)?$companyDetails->bank_ifsc_code:'';
            }
            
            // Pagination logic
            $recordsPerPage = 10;
            $totalRecords = 0;
            if(isset($extension) && is_array($extension) && isset($extension['trip_amount']) && is_countable($extension['trip_amount'])) {
                $totalRecords = count($extension['trip_amount']);
            }
            $totalPages = $totalRecords > 0 ? ceil($totalRecords / $recordsPerPage) : 1;
        @endphp
    </header>
    
    <table cellpadding="0" cellspacing="0" width="720" align="center">
        <tr>
            <td style="text-align: left;">
                @php
                    $image = base64_encode(file_get_contents(public_path('/images/mask.jpg')));
                @endphp            
                <img src="data:image/png;base64,{{ $image }}" alt="LOGO" width="170" height="90">
            </td>
            <td style="text-align: right;">
                <h4 style="line-height: 0px;margin-bottom: 0px;margin-top: 15px;">SHAILESH CAR & BIKE PVT LTD</h4>
                <h6 style="line-height: 15px;margin-bottom: 5px;">{{$companyAdd}}<br/>{{$companyPhone}}, {{$companyAltPhone}}, {{$companyEmail}}</h6><br/>
            </td>
        </tr>`
    </table>
    
    <table class="column-bordered-table"  align="center" style="width: 85.5% !important; ">
        <tr>
            <td rowspan="2" style="text-align: left;">
                <!-- <h5 style="margin: 5px;">FATMAN SERVICES</h5><h6 style="margin: 4px;font-weight: normal;">, 24 - Gujarat Phone No. 9824406456<br/>GST No : 24AACFF9767F1Z9</h6> -->
                <h4 style="margin: 5px;">Customer Details</h4><h5 style="margin: 4px;font-weight: normal;">
                    @if(isset($data->customer))
                        @php 
                            $name = '';
                            if(isset($data->customer->firstname) && $data->customer->firstname != '')
                                $name = $data->customer->firstname;
                            if(isset($data->customer->lastname) && $data->customer->lastname != '')
                                $name = $name.' '.$data->customer->lastname;
                        @endphp
                        <h3>GST No. - @isset($data->customer->gst_number){{$data->customer->gst_number}}@endisset <br/> Business Name - @isset($data->customer->business_name){{$data->customer->business_name}}@endisset</h3>
                        <h3>{{$name}}</h3>
                        <h4>@isset($data->customer->mobile_number){{$data->customer->mobile_number}}@endisset <br/>@isset($data->customer->email){{$data->customer->email}}@endisset<br/>
                        
                        </h4>

                    @endif
                </h5>
            </td>
            <td><h4 style="margin:2px;">Tax Invoice</h4></td>
        </tr>
        <tr>
            <td>
                <h5 style="margin-left:4px;margin-top: 4px;margin-bottom: 5px;">Invoice No. : @isset($data->sequence_no){{ 'VR-'.$data->sequence_no }}@endisset
                    <span style="margin-left:20px;">Booking ID : @isset($data->booking_id){{ $data->booking_id }}@endisset</span>
                    <span style="margin-left:100px;">Date : <span style="font-weight:normal;">@if($completionNewBooking != ''){{date('d-m-Y' , strtotime($completionNewBooking))}}@endif
                </span></span></h5><br/>
                <h5 style="margin-left:4px;margin-top: 4px;margin-bottom: 10px;"><b>Pickup Date - </b> <span style="font-weight:normal;">@if($data->pickup_date != ''){{date('d-m-Y H:i' , strtotime($data->pickup_date))}}@endif </span> | <b>Return Date - </b> <span style="font-weight:normal;">@if($data->end_datetime != ''){{date('d-m-Y H:i' , strtotime($data->end_datetime))}}@endif </span></h5> 
                <h5 style="margin-left:4px;margin-top:4px;margin-bottom: 5px;"><b>Vehicle - </b> 
                    @isset($data->vehicle->vehicle_name)<span style="font-weight:normal;">{{ $data->vehicle->vehicle_name }} @endisset @isset($data->vehicle->category_name) - {{ $data->vehicle->category_name }}@endisset @isset($data->vehicle->license_plate)</span> <br> 
                </h5>
                <h5 style="margin-left:4px;margin-top:4px;margin-bottom: 5px;">
                    <b>Registration Number: </b><span style="font-weight:normal;">{{ $data->vehicle->license_plate }}@endisset</span> <br/>
                </h5>
                <h5 style="margin-left:4px;margin-top: 0px;"><span style="margin-left:120px;"></span></h5>
            </td>
        </tr>
    </table>
    
    {{-- @if(isset($extension) && is_array($extension) && isset($extension['trip_amount']) && is_array($extension['trip_amount']) && !empty($extension['trip_amount'])) --}}
        @for($page = 0; $page < $totalPages; $page++)
            @if($page > 0)
                <div class="page-break"></div>
            @endif
            
            <table class="" cellpadding="0" cellspacing="0"  width="720" align="center" style="border: 1px solid #000; border-collapse: collapse;">
                @if($page == 0)
                <thead>
                    <tr style="border: 2px solid #000; border-collapse: collapse;">
                        <th style="border: 2px solid #000; border-collapse: collapse;padding:3px;">Particular</th>
                        <th style="border: 2px solid #000; border-collapse: collapse;padding:3px;">Qty.</th>
                        <th style="border: 2px solid #000; border-collapse: collapse;padding:3px;">Rate</th>
                        <th style="border: 2px solid #000; border-collapse: collapse;padding:3px;">GST %</th>
                        <th style="border: 2px solid #000; border-collapse: collapse;padding:3px;">GST Amount</th>
                        <th style="border: 2px solid #000; border-collapse: collapse;padding:3px;">Discount</th>
                        <th style="border: 2px solid #000; border-collapse: collapse;padding:3px;">Amount</th>
                    </tr>
                </thead>
                @endif
                <tbody>
                    @php
                        $startIndex = $page * $recordsPerPage;
                        $endIndex = min($startIndex + $recordsPerPage, $totalRecords);
                    @endphp
                        @if($page == 0 && isset($newBooking) && is_countable($newBooking) && count($newBooking) > 0)
                            <tr>
                                <td style="border-right: 1px solid #000;"><h5 style="margin: 5px;font-weight: normal;">
                                    <b>Booking</b> | <b>{{$newBookingTimeStamp}}</b> <br/>
                                </td>
                                <td style="border-right: 1px solid #000;"><h5 style="margin: 5px;font-weight: normal;text-align: right;">1.00 </h5></td>
                                @foreach($newBooking as $key => $val)
                                    @if($key != 'convenience_fees' && $key != 'payment_gateway' && $key != 'payment_gateway_charges')
                                        <td style="border-right: 1px solid #000;"><h5 style="margin: 5px;font-weight: normal;text-align: right;">@isset($val){{$val}}@endisset</h5></td>
                                    @endif
                                @endforeach
                            </tr>
                        @endif
                        @if($page == 0 && isset($cFees) && is_array($cFees) && $cFees != '' && $cFees != 0)
                            <tr @if($newBookingVehicleServiceFees['trip_amount'] == 0 && $newBookingVehicleServiceFees['tax_amount'] == 0) style="border-bottom:1px solid #000;" @else style="" @endif>
                                <td style="border-right: 1px solid #000;"><h5 style="margin: 5px;font-weight: normal;">Convenience Fees</h5>
                                </td>
                                <td style="border-right: 1px solid #000;"><h5 style="margin: 5px;font-weight: normal;text-align: right;">1.00 </h5></td>
                                @foreach($cFees as $key => $val)
                                    <td style="border-right: 1px solid #000;"><h5 style="margin: 5px;font-weight: normal;text-align: right;">@isset($val){{$val}}@endisset</h5></td>
                                @endforeach
                            </tr>
                        @endif
                        @if($page == 0 && isset($newBookingVehicleServiceFees) && is_array($newBookingVehicleServiceFees) && $newBookingVehicleServiceFees != '' && $newBookingVehicleServiceFees != 0 && isset($newBookingVehicleServiceFees['trip_amount']) && $newBookingVehicleServiceFees['trip_amount'] > 0 && isset($newBookingVehicleServiceFees['tax_amount']) && $newBookingVehicleServiceFees['tax_amount'] > 0)
                            <tr style="border-bottom:1px solid #000;">
                                <td style="border-right: 1px solid #000;"><h5 style="margin: 5px;font-weight: normal;">Vehicle Service Fees</h5>
                                </td>
                                <td style="border-right: 1px solid #000;"><h5 style="margin: 5px;font-weight: normal;text-align: right;">1.00 </h5></td>
                                @foreach($newBookingVehicleServiceFees as $key => $val)
                                    <td style="border-right: 1px solid #000;"><h5 style="margin: 5px;font-weight: normal;text-align: right;">@isset($val){{$val}}@endisset</h5></td>
                                @endforeach
                            </tr>
                        @endif
                        <!-- EXTENSION -->
                        @for($i = $startIndex; $i < $endIndex; $i++)
                            <tr>
                                <td style="border-right: 1px solid #000;">
                                    <h5 style="margin: 6px;font-weight: normal;">
                                        <b>Extension</b> | <b>@isset($extension['timestamp'][$i]){{$extension['timestamp'][$i]}}@endisset</b><br/>
                                    </h5>
                                    @if(isset($extensionVehicleServiceFees['trip_amount'][$i]) && isset($extensionVehicleServiceFees['tax_amount'][$i]) && $extensionVehicleServiceFees['trip_amount'][$i] > 0 && $extensionVehicleServiceFees['tax_amount'][$i] > 0)
                                    <h5 style="margin: 6px;font-weight: normal;">
                                        Vehicle Service Fees
                                    </h5>
                                    @endif
                                </td>

                                <td style="border-right: 1px solid #000;">
                                    <h5 style="margin: 5px;font-weight: normal;text-align: right;">1.00<br/><br/>
                                        @if(isset($extensionVehicleServiceFees['trip_amount'][$i]) && isset($extensionVehicleServiceFees['tax_amount'][$i]) && $extensionVehicleServiceFees['trip_amount'][$i] > 0 && $extensionVehicleServiceFees['tax_amount'][$i] > 0)
                                            1.00 
                                        @endif
                                    </h5>
                                </td>
                                
                                <td style="border-right: 1px solid #000;">
                                    <h5 style="margin: 5px;font-weight: normal;text-align: right;">
                                        @isset($extension['trip_amount'][$i]){{$extension['trip_amount'][$i]}}@endisset<br/><br/>
                                        @if(isset($extensionVehicleServiceFees['trip_amount'][$i]) && isset($extensionVehicleServiceFees['tax_amount'][$i]) && $extensionVehicleServiceFees['trip_amount'][$i] > 0 && $extensionVehicleServiceFees['tax_amount'][$i] > 0)
                                            @isset($extensionVehicleServiceFees['trip_amount'][$i]){{$extensionVehicleServiceFees['trip_amount'][$i]}}@endisset
                                        @endif
                                    </h5>
                                </td>
                                
                                <td style="border-right: 1px solid #000;">
                                    <h5 style="margin: 5px;font-weight: normal;text-align: right;">
                                        @isset($extension['tax_percent'][$i]){{$extension['tax_percent'][$i]}}@endisset<br/><br/>
                                        @if(isset($extensionVehicleServiceFees['trip_amount'][$i]) && isset($extensionVehicleServiceFees['tax_amount'][$i]) && $extensionVehicleServiceFees['trip_amount'][$i] > 0 && $extensionVehicleServiceFees['tax_amount'][$i] > 0)
                                            @isset($extensionVehicleServiceFees['tax_percent'][$i]){{$extensionVehicleServiceFees['tax_percent'][$i]}}@endisset
                                        @endif
                                    </h5>
                                </td>
                                <td style="border-right: 1px solid #000;">
                                    <h5 style="margin: 5px;font-weight: normal;text-align: right;">
                                    @isset($extension['tax_amount'][$i]){{$extension['tax_amount'][$i]}}@endisset<br/><br/>
                                                                            @if(isset($extensionVehicleServiceFees['trip_amount'][$i]) && isset($extensionVehicleServiceFees['tax_amount'][$i]) && $extensionVehicleServiceFees['trip_amount'][$i] > 0 && $extensionVehicleServiceFees['tax_amount'][$i] > 0)
                                        @isset($extensionVehicleServiceFees['tax_amount'][$i]){{$extensionVehicleServiceFees['tax_amount'][$i]}}@endisset
                                    @endif
                                    </h5>
                                </td>
                                <td style="border-right: 1px solid #000;">
                                    <h5 style="margin: 5px;font-weight: normal;text-align: right;">@isset($extension['coupon_discount'][$i]){{$extension['coupon_discount'][$i]}}@endisset<br/><br/>
                                    @if(isset($extensionVehicleServiceFees['trip_amount'][$i]) && isset($extensionVehicleServiceFees['tax_amount'][$i]) && $extensionVehicleServiceFees['trip_amount'][$i] > 0 && $extensionVehicleServiceFees['tax_amount'][$i] > 0)
                                        @isset($extensionVehicleServiceFees['coupon_discount'][$i]){{$extensionVehicleServiceFees['coupon_discount'][$i]}}@endisset
                                    @endif
                                    </h5>
                                </td>
                                <td style="border-right: 1px solid #000;">
                                    <h5 style="margin: 5px;font-weight: normal;text-align: right;">
                                    @isset($extension['total_amount'][$i]){{$extension['total_amount'][$i]}}@endisset<br/><br/>
                                    @if(isset($extensionVehicleServiceFees['trip_amount'][$i]) && isset($extensionVehicleServiceFees['tax_amount'][$i]) && $extensionVehicleServiceFees['trip_amount'][$i] > 0 && $extensionVehicleServiceFees['tax_amount'][$i] > 0)
                                        @isset($extensionVehicleServiceFees['total_amount'][$i]){{$extensionVehicleServiceFees['total_amount'][$i]}}@endisset
                                    @endif
                                    </h5>
                                </td>
                            </tr>
                        @endfor
                        <!-- COMPLETION -->
                        @if($page == $totalPages - 1 && is_countable($completion) && count($completion) > 0)
                            <tr @if($completionVehicleServiceFees['trip_amount'] == 0 && $completionVehicleServiceFees['tax_amount'] == 0) style="border-bottom:1px solid #000;border-top: 1px solid #000;" @else style="border-top: 1px solid #000;" @endif" @if($data->booking_id == 1805) style="background-color: #ffc8bac9;" @endif>
                                <td style="border-right: 1px solid #000;">
                                    <h5 style="margin: 5px;font-weight: normal;">
                                        <b>Completion </b> | <b>@if($completionNewBooking != ''){{$completionNewBooking}}@endif</b><br/>
                                    <b>@if($penaltyText != ''){!! $penaltyText !!}@endif <br/>@if($data->booking_id == 1805){{'Lost key by customer at INS valsura Jamnagar'}}@endif</b>
                                    </h5>
                                </td>
                                <td style="border-right: 1px solid #000;"><h5 style="margin: 5px;font-weight: normal;text-align: right;">1.00 </h5></td>
                                @foreach($completion as $key => $val)
                                    @if($key != 'convenience_fees' && $key != 'payment_gateway' && $key != 'payment_gateway_charges')
                                        <td style="border-right: 1px solid #000;"><h5 style="margin: 5px;font-weight: normal;text-align: right;">@isset($val){{$val}}@endisset</h5></td>
                                    @endif
                                @endforeach
                            </tr>
                        @endif
                        @if($page == $totalPages - 1 && isset($completionVehicleServiceFees) && $completionVehicleServiceFees != '' && $completionVehicleServiceFees != 0 && isset($completionVehicleServiceFees['trip_amount']) &&  $completionVehicleServiceFees['trip_amount'] > 0 && $completionVehicleServiceFees['tax_amount'] > 0)
                            <tr style="border-bottom: 1px solid #000;" @if($data->booking_id == 1805) style="background-color: #ffc8bac9;" @endif>
                                <td style="border-right: 1px solid #000;"><h5 style="margin: 5px;font-weight: normal;">Vehicle Service Fees</h5>
                                </td>
                                <td style="border-right: 1px solid #000;"><h5 style="margin: 5px;font-weight: normal;text-align: right;">1.00 </h5></td>
                                @foreach($completionVehicleServiceFees as $key => $val)
                                    <td style="border-right: 1px solid #000;"><h5 style="margin: 5px;font-weight: normal;text-align: right;">@isset($val){{$val}}@endisset</h5></td>
                                @endforeach
                            </tr>
                        @endif
                        <!-- PENALTIES -->
                        @if($page == $totalPages - 1 && is_countable($paidPenalties) && count($paidPenalties) > 0)
                            @foreach($paidPenalties['trip_amount'] as $key => $val)
                                <tr>
                                    <td style="border-right: 1px solid #000;">
                                        <h5 style="margin: 6px;font-weight: normal;">
                                            <b>Paid Penalties</b> | <b>@isset($paidPenalties['timestamp'][$key]){{$paidPenalties['timestamp'][$key]}}@endisset</b><br/>
                                        </h5>
                                        @if(isset($paidPenaltyServiceCharge['trip_amount'][$key]) && isset($paidPenaltyServiceCharge['tax_amount'][$key]))
                                            <h5 style="margin: 6px;font-weight: normal;">
                                                Vehicle Service Fees
                                            </h5>
                                        @endif
                                    </td>
                                    <td style="border-right: 1px solid #000;">
                                        <h5 style="margin: 5px;font-weight: normal;text-align: right;">1.00<br/><br/>
                                            @if(isset($paidPenaltyServiceCharge['trip_amount'][$key]) && isset($paidPenaltyServiceCharge['tax_amount'][$key]))
                                                1.00 
                                            @endif
                                        </h5>
                                    </td>
                                    <td style="border-right: 1px solid #000;">
                                        <h5 style="margin: 5px;font-weight: normal;text-align: right;">@isset($paidPenalties['trip_amount'][$key]){{$paidPenalties['trip_amount'][$key]}}@endisset<br/><br/>
                                        @if(isset($paidPenaltyServiceCharge['trip_amount'][$key]) && isset($paidPenaltyServiceCharge['tax_amount'][$key]))
                                            @isset($paidPenaltyServiceCharge['trip_amount'][$key]){{$paidPenaltyServiceCharge['trip_amount'][$key]}}@endisset</h5>
                                        @endif
                                    </td>
                                    <td style="border-right: 1px solid #000;">
                                        <h5 style="margin: 5px;font-weight: normal;text-align: right;">@isset($paidPenalties['tax_percent'][$key]){{number_format($paidPenalties['tax_percent'][$key], 2)}}@endisset<br/><br/>
                                        @if(isset($paidPenaltyServiceCharge['trip_amount'][$key]) && $paidPenaltyServiceCharge['tax_amount'][$key] > 0)
                                            @isset($paidPenaltyServiceCharge['tax_percent'][$key]){{$paidPenaltyServiceCharge['tax_percent'][$key]}}@endisset</h5>
                                        @endif
                                    </td>
                                    <td style="border-right: 1px solid #000;">
                                        <h5 style="margin: 5px;font-weight: normal;text-align: right;">
                                            @isset($paidPenalties['tax_amount'][$key]){{$paidPenalties['tax_amount'][$key]}}@endisset<br/><br/>
                                            @if(isset($paidPenaltyServiceCharge['trip_amount'][$key]) && isset($paidPenaltyServiceCharge['tax_amount'][$key]))
                                                @isset($paidPenaltyServiceCharge['tax_amount'][$key]){{$paidPenaltyServiceCharge['tax_amount'][$key]}}@endisset
                                            @endif
                                        </h5>
                                    </td>
                                    <td style="border-right: 1px solid #000;">
                                        <h5 style="margin: 5px;font-weight: normal;text-align: right;">@isset($paidPenalties['coupon_discount'][$key]){{$paidPenalties['coupon_discount'][$key]}}@endisset<br/><br/>
                                        @if(isset($paidPenaltyServiceCharge['trip_amount'][$key]) && isset($paidPenaltyServiceCharge['tax_amount'][$key]))
                                            @isset($paidPenaltyServiceCharge['coupon_discount'][$key]){{$paidPenaltyServiceCharge['coupon_discount'][$key]}}@endisset</h5>
                                        @endif
                                    </td>
                                    <td style="border-right: 1px solid #000;">
                                        <h5 style="margin: 5px;font-weight: normal;text-align: right;">@isset($paidPenalties['total_amount'][$key]){{$paidPenalties['total_amount'][$key]}}@endisset<br/><br/>
                                        @if(isset($paidPenaltyServiceCharge['trip_amount'][$key]) && isset($paidPenaltyServiceCharge['tax_amount'][$key]))
                                            @isset($paidPenaltyServiceCharge['total_amount'][$key]){{$paidPenaltyServiceCharge['total_amount'][$key]}}@endisset
                                        @endif
                                        </h5>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                        @if($page == $totalPages - 1 && is_countable($duePenalties) && count($duePenalties) > 0)
                            @foreach($duePenalties['trip_amount'] as $key => $val)
                                @if($duePenalties['trip_amount'][$key] > 0)
                                <tr style="background-color: #ffc8bac9;">
                                    <td style="border-right: 1px solid #000;">
                                        <h5 style="margin: 6px;font-weight: normal;">
                                            <b>Due Penalties</b> | <b>@isset($duePenalties['timestamp'][$key]){{$duePenalties['timestamp'][$key]}}@endisset</b><br/>
                                        </h5>
                                        @if($duePenaltyServiceCharge['trip_amount'][$key] > 0 && $duePenaltyServiceCharge['tax_amount'][$key] > 0)
                                        <h5 style="margin: 6px;font-weight: normal;">
                                            Vehicle Service Fees
                                        </h5>
                                        @endif
                                    </td>
                                    <td style="border-right: 1px solid #000;">
                                        <h5 style="margin: 5px;font-weight: normal;text-align: right;">1.00<br/><br/>
                                            @if($duePenaltyServiceCharge['trip_amount'][$key] > 0 && $duePenaltyServiceCharge['tax_amount'][$key] > 0)
                                                1.00 
                                            @endif
                                        </h5>
                                    </td>
                                    <td style="border-right: 1px solid #000;">
                                        <h5 style="margin: 5px;font-weight: normal;text-align: right;">
                                            @isset($duePenalties['trip_amount'][$key]){{$duePenalties['trip_amount'][$key]}}@endisset<br/><br/>
                                            @if($duePenaltyServiceCharge['trip_amount'][$key] > 0 && $duePenaltyServiceCharge['tax_amount'][$key] > 0)
                                                @isset($duePenaltyServiceCharge['trip_amount'][$key]){{$duePenaltyServiceCharge['trip_amount'][$key]}}@endisset
                                            @endif
                                        </h5>
                                    </td>
                                    <td style="border-right: 1px solid #000;">
                                        <h5 style="margin: 5px;font-weight: normal;text-align: right;"> @isset($duePenalties['tax_percent'][$key]){{number_format($duePenalties['tax_percent'][$key], 2)}}@endisset<br/><br/>
                                        @if($duePenaltyServiceCharge['trip_amount'][$key] > 0 && $duePenaltyServiceCharge['tax_amount'][$key] > 0)
                                            @isset($duePenaltyServiceCharge['tax_percent'][$key]){{$duePenaltyServiceCharge['tax_percent'][$key]}}@endisset
                                        @endif
                                        </h5>
                                    </td>
                                    <td style="border-right: 1px solid #000;">
                                        <h5 style="margin: 5px;font-weight: normal;text-align: right;">
                                        @isset($duePenalties['tax_amount'][$key]){{$duePenalties['tax_amount'][$key]}}@endisset<br/><br/>
                                        @if($duePenaltyServiceCharge['trip_amount'][$key] > 0 && $duePenaltyServiceCharge['tax_amount'][$key] > 0)
                                            @isset($duePenaltyServiceCharge['tax_amount'][$key]){{$duePenaltyServiceCharge['tax_amount'][$key]}}@endisset
                                        @endif
                                        </h5>
                                    </td>
                                    <td style="border-right: 1px solid #000;">
                                        <h5 style="margin: 5px;font-weight: normal;text-align: right;">@isset($duePenalties['coupon_discount'][$key]){{$duePenalties['coupon_discount'][$key]}}@endisset<br/><br/>
                                        @if($duePenaltyServiceCharge['trip_amount'][$key] > 0 && $duePenaltyServiceCharge['tax_amount'][$key] > 0)
                                            @isset($duePenaltyServiceCharge['coupon_discount'][$key]){{$duePenaltyServiceCharge['coupon_discount'][$key]}}@endisset
                                        @endif
                                        </h5>
                                    </td>
                                    <td style="border-right: 1px solid #000;">
                                        <h5 style="margin: 5px;font-weight: normal;text-align: right;"> @isset($duePenalties['total_amount'][$key]){{$duePenalties['total_amount'][$key]}}@endisset<br/><br/>
                                        @if($duePenaltyServiceCharge['trip_amount'][$key] > 0 && $duePenaltyServiceCharge['tax_amount'][$key] > 0)
                                            @isset($duePenaltyServiceCharge['total_amount'][$key]){{$duePenaltyServiceCharge['total_amount'][$key]}}@endisset
                                        @endif
                                        </h5>
                                    </td>
                                </tr>
                                @endif
                            @endforeach
                        @endif
                </tbody>
            </table>
        @endfor
        
    {{-- @endif --}}
    
    <table class="column-bordered-table" cellpadding="0" cellspacing="0" style="width: 85.5% !important;" align="center">
        <tr>
            <td>
                <h5 style="margin-top:1px;margin-bottom:1px;margin-left:15px;"><b>Amount in words</b> : 
                    <span style="font-weight: normal;">
                        @php
                            $amountInWords = '';
                            if(isset($totalAmt)){
                                $amountInWords = str_replace(',', '', $totalAmt); 
                                $amountInWords = getIndianCurrency((float)($amountInWords));
                            }
                        @endphp
                        {{ucwords($amountInWords)}}
                    </span>
                </h5>
            </td>
            <td style="border: 0px solid #000">
                @php
                    $hasGroupedTotals = false;
                    $validGroupedTotals = [];
                    if(isset($groupedTotals)){
                        foreach($groupedTotals as $gstPercent => $totals){
                            $totalRateForGst = $totals['rate'] + $totals['vehicle_commission_rate'];
                            $totalTaxForGst = $totals['tax'] + $totals['vehicle_commission_tax'];
                            if($totalRateForGst > 0 || $totalTaxForGst > 0){
                                $hasGroupedTotals = true;
                                $validGroupedTotals[$gstPercent] = $totals;
                            }
                        }
                        // Sort by GST percentage
                        ksort($validGroupedTotals);
                    }
                @endphp
                @if($hasGroupedTotals)
                    @php $firstRow = true; @endphp
                    @foreach($validGroupedTotals as $gstPercent => $totals)
                        @php
                            $totalRateForGst = $totals['rate'] + $totals['vehicle_commission_rate'];
                            $totalTaxForGst = $totals['tax'] + $totals['vehicle_commission_tax'];
                            $paddingTop = $firstRow ? 'padding-top:15px;' : '';
                            $firstRow = false;
                        @endphp
                        @if($totalRateForGst > 0 || $totalTaxForGst > 0)
                            @php
                                // For CGST/SGST, show half percentage (2.5% for 5%, 9% for 18%, 6% for 12%)
                                // For IGST, show full percentage
                                $displayPercent = $gstStatus == 1 ? ($gstPercent / 2) : $gstPercent;
                            @endphp
                            <h5 style="line-height:7px;text-align: left;padding-left: 5px;font-weight: normal;{{$paddingTop}}"><b>Total Rate ({{$gstPercent}}% GST)</b><br/> </h5>
                            @if($gstStatus == 1)
                            <h5 style="line-height:7px;text-align: left;padding-left: 5px;font-weight: normal;">CGST ({{number_format($displayPercent, 1)}}%)<br/> </h5>
                            <h5 style="line-height:7px;text-align: left;padding-left: 5px;font-weight: normal;">SGST ({{number_format($displayPercent, 1)}}%)<br/> </h5>
                            @endif
                            @if($gstStatus == 2)
                            <h5 style="line-height:7px;text-align: left;padding-left: 5px;font-weight: normal;">IGST ({{$gstPercent}}%)<br/> </h5>
                            @endif
                        @endif
                    @endforeach
                @else
                    <h5 style="line-height:7px;text-align: left;padding-left: 5px;font-weight: normal;padding-top:15px;"><b>Total Rate</b><br/> </h5>
                    @if($gstStatus == 1)
                    <h5 style="line-height:7px;text-align: left;padding-left: 5px;font-weight: normal;">CGST<br/> </h5>
                    <h5 style="line-height:7px;text-align: left;padding-left: 5px;font-weight: normal;">SGST<br/> </h5>
                    @endif
                    @if($gstStatus == 2)
                    <h5 style="line-height:7px;text-align: left;padding-left: 5px;font-weight: normal;">IGST<br/> </h5>
                    @endif
                @endif
                <h5 style="line-height:7px;text-align: left;padding-left: 5px;font-weight: normal;padding-top:7px;"><b>Grand Total</b><br/> </h5>
            </td>
            <td style="border: 0px solid #000;">
                @if($hasGroupedTotals)
                    @php $firstRow = true; @endphp
                    @foreach($validGroupedTotals as $gstPercent => $totals)
                        @php
                            $totalRateForGst = $totals['rate'] + $totals['vehicle_commission_rate'];
                            $totalTaxForGst = $totals['tax'] + $totals['vehicle_commission_tax'];
                            $paddingTop = $firstRow ? 'padding-top:15px;' : '';
                            $firstRow = false;
                        @endphp
                        @if($totalRateForGst > 0 || $totalTaxForGst > 0)
                            <h5 style="line-height:7px;text-align: right;padding-right: 5px;font-weight: normal;{{$paddingTop}}"><b>{{number_format($totalRateForGst, 2)}}</b><br/></h5>
                            @if($gstStatus == 1)
                            <h5 style="line-height:7px;text-align: right;padding-right: 5px;font-weight: normal;">{{number_format(($totalTaxForGst / 2), 2)}}<br/></h5>
                            <h5 style="line-height:7px;text-align: right;padding-right: 5px;font-weight: normal;">{{number_format(($totalTaxForGst / 2), 2)}}<br/></h5>
                            @endif
                            @if($gstStatus == 2)
                            <h5 style="line-height:7px;text-align: right;padding-right: 5px;font-weight: normal;">{{number_format($totalTaxForGst, 2)}}<br/></h5>
                            @endif
                        @endif
                    @endforeach
                @else
                    <h5 style="line-height:7px;text-align: right;padding-right: 5px;font-weight: normal;padding-top:15px;"><b>@isset($rateTotal){{number_format($rateTotal, 2)}}@endisset</b><br/></h5>
                    @if($gstStatus == 1)
                    <h5 style="line-height:7px;text-align: right;padding-right: 5px;font-weight: normal;">@isset($totalTax){{number_format(($totalTax / 2), 2)}}@endisset<br/></h5>
                    <h5 style="line-height:7px;text-align: right;padding-right: 5px;font-weight: normal;">@isset($totalTax){{number_format(($totalTax / 2), 2)}}@endisset<br/></h5>
                    @endif
                    @if($gstStatus == 2)
                    <h5 style="line-height:7px;text-align: right;padding-right: 5px;font-weight: normal;">@isset($totalTax){{$totalTax}}@endisset<br/></h5>
                    @endif
                @endif
                <h5 style="line-height:7px;text-align: right;padding-right: 5px;font-weight: normal;padding-top:7px;"><b>
                    @php
                        $grandTotal = isset($totalAmt) ? $totalAmt : 0;
                        if(isset($amountDue) && $amountDue > 0){
                            $grandTotal += $amountDue;
                        }
                    @endphp
                    {{number_format($grandTotal, 2)}}
                    </b><br/>
                </h5>
            </td>
        </tr>
        <!-- <tr>
            <td>
                <h6 style="padding-left: 5px;padding-top: 5px;line-height: 17px;">Bank Details : <br/>Account No. : <span style="font-weight: normal;">{{$companyBankAccNo}}</span></br>Bank Name : <span style="font-weight: normal;">{{$companyBankName}}</span></br>
                    IFSC Code : <span style="font-weight: normal;">{{$companyBankIfsc}}</span></br>
                </h6>
            </td>
        </tr> -->
        <tr>
            <td><b style="margin-left:15px;">GSTNo</b> : <span style="font-weight:normal;">{{$companyGst}}</span> <b style="margin-left:35px;">PAN No.</b> : <span style="font-weight:normal;">{{$companyPan}}</span></td>
            <td style="text-align: left;padding: 15px;border-right: 0px solid #000;">
                Amount Paid
                @if(isset($amountDue) && $amountDue > 0)
                <br/><br/><b>Amount Due</b>
                @endif
            </td>
            <td style="text-align: right;padding: 15px;border-left: 0px solid #000;">
                @isset($totalAmt){{number_format($totalAmt, 2)}}@endisset
                @if(isset($amountDue) && $amountDue > 0)
                <br/><br/>
                <span>@isset($amountDue){{number_format($amountDue, 2)}}@endisset</span>
                @endif
            </td>
        </tr>
    </table>
    <table class="column-bordered-table" cellpadding="0" cellspacing="0" style="width: 85.5% !important;" align="center">
        <tr style="text-align:center;">
            <td style="padding-right: 350px;">

            </td>
            <td style="padding-top: 10px;">
                <h5 style="font-weight:normal;">For, SHAILESH CAR & BIKE PVT LTD</h5><h5 style="font-weight:normal;">AUTHORISED SIGNATORY</h5>
            </td>
        </tr>
    </table>
</br>
    <div class="footer" align="center">
        Thank you for choosing VELRIDERS.
    </div>
</body>
</html>