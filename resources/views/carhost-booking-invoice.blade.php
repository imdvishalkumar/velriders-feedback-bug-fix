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
        </tr>
    </table>
    
    @php    
        $carHost = null;
        if(isset($data->vehicle) && isset($data->vehicle->vehicle_id)) {
            $carEligibility = \App\Models\CarEligibility::where('vehicle_id', $data->vehicle->vehicle_id)->with('carHost')->first();
            if($carEligibility && $carEligibility->carHost) {
                $carHost = $carEligibility->carHost;
            }
        }
    @endphp
    
    <table class="column-bordered-table"  align="center" style="width: 85.5% !important; ">
        <tr>
            @if($carHost)
            <td rowspan="2" style="text-align: left;">
                <h4 style="margin: 5px;">CarHost Details</h4>
                <h5 style="margin: 4px;font-weight: normal;">
                    @php 
                        $name = '';
                        if(isset($carHost->firstname) && $carHost->firstname != '')
                            $name = $carHost->firstname;
                        if(isset($carHost->lastname) && $carHost->lastname != '')
                            $name = $name.' '.$carHost->lastname;
                    @endphp
                    <h3>
                        @if(isset($carHost->gst_number) && $carHost->gst_number != '')
                            GST No. - {{$carHost->gst_number}} <br/>
                        @endif
                        @if(isset($carHost->business_name) && $carHost->business_name != '')
                            Business Name - {{$carHost->business_name}}
                        @endif
                    </h3>
                    <h3>{{$name}}</h3>
                    <h4>@isset($carHost->mobile_number){{$carHost->mobile_number}}@endisset <br/>@isset($carHost->email){{$carHost->email}}@endisset<br/>
                    @if(isset($carHost->billing_address))
                        {{$carHost->billing_address}}
                    @endif
                    </h4>
                </h5>
            </td>
            @endif
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
                                <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;"><h5 style="margin: 5px;font-weight: normal;">
                                    <b>Booking</b> | <b>{{$newBookingTimeStamp}}</b> <br/>
                                </td>
                                <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;"><h5 style="margin: 5px;font-weight: normal;text-align: right;">1.00</h5></td>
                                <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;"><h5 style="margin: 5px;font-weight: normal;text-align: right;">@isset($newBooking['trip_amount']){{$newBooking['trip_amount']}}@endisset</h5></td>
                                <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;"><h5 style="margin: 5px;font-weight: normal;text-align: right;">@isset($newBooking['coupon_discount']){{$newBooking['coupon_discount']}}@endisset</h5></td>
                                <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;"><h5 style="margin: 5px;font-weight: normal;text-align: right;">@isset($newBooking['total_amount']){{$newBooking['total_amount']}}@endisset</h5></td>
                            </tr>
                        @endif
                        <!-- EXTENSION -->
                        @for($i = $startIndex; $i < $endIndex; $i++)
                            <tr>
                                <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">
                                    <h5 style="margin: 6px;font-weight: normal;">
                                        <b>Extension</b> | <b>@isset($extension['timestamp'][$i]){{$extension['timestamp'][$i]}}@endisset</b><br/>
                                    </h5>
                                </td>

                                <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">
                                    <h5 style="margin: 5px;font-weight: normal;text-align: right;">1.00</h5>
                                </td>
                                
                                <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">
                                    <h5 style="margin: 5px;font-weight: normal;text-align: right;">
                                        @isset($extension['trip_amount'][$i]){{$extension['trip_amount'][$i]}}@endisset
                                    </h5>
                                </td>
                                
                                <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">
                                    <h5 style="margin: 5px;font-weight: normal;text-align: right;">
                                        @isset($extension['coupon_discount'][$i]){{$extension['coupon_discount'][$i]}}@endisset
                                    </h5>
                                </td>
                                <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">
                                    <h5 style="margin: 5px;font-weight: normal;text-align: right;">
                                        @isset($extension['total_amount'][$i]){{$extension['total_amount'][$i]}}@endisset
                                    </h5>
                                </td>
                            </tr>
                        @endfor
                        <!-- COMPLETION -->
                        @if($page == $totalPages - 1 && is_countable($completion) && count($completion) > 0)
                            @php
                                $completionTotalAmount = 0;
                                if(isset($completion['total_amount'])){
                                    $completionTotalAmount = str_replace(',', '', $completion['total_amount']);
                                    $completionTotalAmount = (float)$completionTotalAmount;
                                }
                            @endphp
                            @if($completionTotalAmount > 0)
                                <tr style="border-top: 1px solid #000; border-bottom: 1px solid #000;" @if($data->booking_id == 1805) style="background-color: #ffc8bac9;" @endif>
                                    <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">
                                        <h5 style="margin: 5px;font-weight: normal;">
                                            <b>Completion </b> | <b>@if($completionNewBooking != ''){{$completionNewBooking}}@endif</b><br/>
                                        <b>@if($penaltyText != ''){!! $penaltyText !!}@endif <br/>@if($data->booking_id == 1805){{'Lost key by customer at INS valsura Jamnagar'}}@endif</b>
                                        </h5>
                                    </td>
                                    <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;"><h5 style="margin: 5px;font-weight: normal;text-align: right;">1.00</h5></td>
                                    <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;"><h5 style="margin: 5px;font-weight: normal;text-align: right;">@isset($completion['trip_amount']){{$completion['trip_amount']}}@endisset</h5></td>
                                    <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;"><h5 style="margin: 5px;font-weight: normal;text-align: right;">@isset($completion['coupon_discount']){{$completion['coupon_discount']}}@endisset</h5></td>
                                    <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;"><h5 style="margin: 5px;font-weight: normal;text-align: right;">@isset($completion['total_amount']){{$completion['total_amount']}}@endisset</h5></td>
                                </tr>
                            @endif
                        @endif
                        <!-- PENALTIES -->
                        @if($page == $totalPages - 1 && is_countable($paidPenalties) && count($paidPenalties) > 0)
                            @foreach($paidPenalties['trip_amount'] as $key => $val)
                                <tr>
                                    <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">
                                        <h5 style="margin: 6px;font-weight: normal;">
                                            <b>Paid Penalties</b> | <b>@isset($paidPenalties['timestamp'][$key]){{$paidPenalties['timestamp'][$key]}}@endisset</b><br/>
                                        </h5>
                                    </td>
                                    <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">
                                        <h5 style="margin: 5px;font-weight: normal;text-align: right;">1.00</h5>
                                    </td>
                                    <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">
                                        <h5 style="margin: 5px;font-weight: normal;text-align: right;">@isset($paidPenalties['trip_amount'][$key]){{$paidPenalties['trip_amount'][$key]}}@endisset</h5>
                                    </td>
                                    <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">
                                        <h5 style="margin: 5px;font-weight: normal;text-align: right;">@isset($paidPenalties['coupon_discount'][$key]){{$paidPenalties['coupon_discount'][$key]}}@endisset</h5>
                                    </td>
                                    <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">
                                        <h5 style="margin: 5px;font-weight: normal;text-align: right;">@isset($paidPenalties['total_amount'][$key]){{$paidPenalties['total_amount'][$key]}}@endisset</h5>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                        @if($page == $totalPages - 1 && is_countable($duePenalties) && count($duePenalties) > 0)
                            @foreach($duePenalties['trip_amount'] as $key => $val)
                                @if($duePenalties['trip_amount'][$key] > 0)
                                <tr style="background-color: #ffc8bac9;">
                                    <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">
                                        <h5 style="margin: 6px;font-weight: normal;">
                                            <b>Due Penalties</b> | <b>@isset($duePenalties['timestamp'][$key]){{$duePenalties['timestamp'][$key]}}@endisset</b><br/>
                                        </h5>
                                    </td>
                                    <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">
                                        <h5 style="margin: 5px;font-weight: normal;text-align: right;">1.00</h5>
                                    </td>
                                    <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">
                                        <h5 style="margin: 5px;font-weight: normal;text-align: right;">
                                            @isset($duePenalties['trip_amount'][$key]){{$duePenalties['trip_amount'][$key]}}@endisset
                                        </h5>
                                    </td>
                                    <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">
                                        <h5 style="margin: 5px;font-weight: normal;text-align: right;">@isset($duePenalties['coupon_discount'][$key]){{$duePenalties['coupon_discount'][$key]}}@endisset</h5>
                                    </td>
                                    <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">
                                        <h5 style="margin: 5px;font-weight: normal;text-align: right;">@isset($duePenalties['total_amount'][$key]){{$duePenalties['total_amount'][$key]}}@endisset</h5>
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
                            $grandTotal = ($totalAmt ?? 0) + ($amountDue ?? 0);
                            if($grandTotal > 0){
                                $amountInWords = getIndianCurrency((float)$grandTotal);
                            }
                        @endphp
                        {{ucwords($amountInWords)}}
                    </span>
                </h5>
            </td>
            <td style="border: 0px solid #000">
                <h5 style="line-height:7px;text-align: left;padding-left: 5px;font-weight: normal;padding-top:15px;"><b>Total Rate</b><br/> </h5>
                <h5 style="line-height:7px;text-align: left;padding-left: 5px;font-weight: normal;padding-top:7px;"><b>Grand Total</b><br/> </h5>
            </td>
            <td style="border: 0px solid #000;">
                <h5 style="line-height:7px;text-align: right;padding-right: 5px;font-weight: normal;padding-top:15px;"><b>@isset($rateTotal){{number_format($rateTotal, 2)}}@endisset</b><br/></h5>
                <h5 style="line-height:7px;text-align: right;padding-right: 5px;font-weight: normal;padding-top:7px;"><b>
                    @isset($totalAmt){{number_format(($totalAmt + $amountDue), 2)}}@endisset</b><br/>
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
                    <br/><br/><span>{{number_format($amountDue, 2)}}</span>
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