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
    @php 
        $companyAdd = '';
        $companyPhone = ''; 
        $companyAltPhone = ''; 
        $companyEmail =  '';
        $companyGst =  '';
        $companyPan =  '';
        if(isset($companyDetails) && $companyDetails != ''){
            $companyAdd = @isset($companyDetails->address)?$companyDetails->address:'';
            $companyPhone = @isset($companyDetails->phone)?$companyDetails->phone:''; 
            $companyAltPhone = @isset($companyDetails->alt_phone)?$companyDetails->alt_phone:''; 
            $companyEmail =  @isset($companyDetails->email)?$companyDetails->email:'';
            $companyGst =  @isset($companyDetails->gst_no)?$companyDetails->gst_no:'';
            $companyPan =  @isset($companyDetails->pan_no)?$companyDetails->pan_no:'';
        }
    @endphp
    <table cellpadding="0" cellspacing="0" align="center" style="width: 85.5%;">
        <tr>
            <td style="text-align: left; vertical-align: top;">
                @php
                    $logoPath = public_path('/images/mask.jpg');
                    $image = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : '';
                @endphp            
                @if($image)
                    <img src="data:image/png;base64,{{ $image }}" alt="LOGO" width="150">
                @endif
            </td>
            <td style="text-align: right; vertical-align: top;">
                <h4 style="line-height: 0px; margin-bottom: 0px; margin-top: 15px;">SHAILESH CAR & BIKE PVT LTD</h4>
                <h6 style="line-height: 15px; margin-bottom: 5px;">{{$companyAdd}}<br/>{{$companyPhone}}, {{$companyAltPhone}}, {{$companyEmail}}</h6>
            </td>
        </tr>
    </table>

    <table class="column-bordered-table" align="center" style="width: 85.5% !important; margin-top: 10px;">
        <tr>
            <td rowspan="2" style="text-align: left; vertical-align: top; width: 45%; padding: 5px;">
                <h4 style="margin: 5px;">CarHost Details</h4>
                @php 
                    $hostName = '';
                    if(isset($carHost->firstname)) $hostName = $carHost->firstname;
                    if(isset($carHost->lastname)) $hostName .= ' '.$carHost->lastname;
                @endphp
                <h5 style="margin: 4px; font-weight: normal;">
                    @if(isset($carHost->gst_number) && $carHost->gst_number != '')
                        <span style="font-weight: bold; font-size: 13px;">GST No. - {{$carHost->gst_number}}</span><br/>
                    @endif
                    @if(isset($carHost->business_name) && $carHost->business_name != '')
                        <span style="font-weight: bold; font-size: 13px;">Business Name - {{$carHost->business_name}}</span><br/>
                    @endif
                    <h3 style="margin: 5px 0;">{{$hostName}}</h3>
                    <h4 style="margin: 2px 0;">
                        {{$carHost->mobile_number ?? ''}}<br/>
                        {{$carHost->email ?? ''}}
                    </h4>
                </h5>
            </td>
            <td><h4 style="margin: 2px;">Tax Invoice</h4></td>
        </tr>
        <tr>
            <td style="padding: 5px; vertical-align: top;">
                <h5 style="margin: 4px 0;">
                    <b>Invoice No. :</b> VR-{{ $history->id }}
                    <span style="margin-left: 20px;"><b>Booking ID :</b> {{ $history->booking_id }}</span>
                    <span style="margin-left: 40px;"><b>Date :</b> {{ date('d-m-Y', strtotime($history->created_at)) }}</span>
                </h5>
                <h5 style="margin: 8px 0;">
                    <b>Pickup Date -</b> {{ date('d-m-Y H:i', strtotime($data->pickup_date)) }} | <b>Return Date -</b> {{ date('d-m-Y H:i', strtotime($data->end_datetime ?? $data->return_date)) }}
                </h5>
                <h5 style="margin: 4px 0;">
                    <b>Vehicle -</b> {{ $history->newVehicle->vehicle_name ?? 'N/A' }} ({{ $history->newVehicle->model->category->name ?? '' }})
                </h5>
                <h5 style="margin: 4px 0;">
                    <b>Registration Number:</b> {{ $history->newVehicle->license_plate ?? 'N/A' }}
                </h5>
            </td>
        </tr>
    </table>

    <table class="column-bordered-table" cellpadding="0" cellspacing="0" align="center" style="width: 85.5% !important; margin-top: 10px;">
        <thead>
            <tr style="background-color: #f2f2f2;">
                <th style="padding: 5px; text-align: left; border: 2px solid #000;">Particular</th>
                <th style="padding: 5px; width: 60px; border: 2px solid #000;">Qty.</th>
                <th style="padding: 5px; width: 80px; border: 2px solid #000;">Rate</th>
                <th style="padding: 5px; width: 80px; border: 2px solid #000;">Discount</th>
                <th style="padding: 5px; width: 100px; border: 2px solid #000;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @if(isset($newBooking) && !empty($newBooking))
                <tr>
                    <td style="padding: 5px; border-right: 1px solid #000;">
                        <h5 style="margin: 5px; font-weight: normal;"><b>Booking</b> | {{$newBookingTimeStamp}}</h5>
                    </td>
                    <td style="padding: 5px; text-align: right; border-right: 1px solid #000;"><h5 style="margin: 5px; font-weight: normal;">1.00</h5></td>
                    <td style="padding: 5px; text-align: right; border-right: 1px solid #000;"><h5 style="margin: 5px; font-weight: normal;">{{number_format((float)$newBooking['trip_amount'], 2)}}</h5></td>
                    <td style="padding: 5px; text-align: right; border-right: 1px solid #000;"><h5 style="margin: 5px; font-weight: normal;">{{number_format((float)$newBooking['coupon_discount'], 2)}}</h5></td>
                    <td style="padding: 5px; text-align: right; border-right: 1px solid #000;"><h5 style="margin: 5px; font-weight: normal;">{{number_format((float)$newBooking['total_amount'], 2)}}</h5></td>
                </tr>
            @endif

            @if(isset($extension) && !empty($extension['trip_amount']))
                @foreach($extension['trip_amount'] as $i => $amt)
                    <tr>
                        <td style="padding: 5px; border-right: 1px solid #000;">
                            <h5 style="margin: 5px; font-weight: normal;"><b>Extension</b> | {{ $extension['timestamp'][$i] ?? '' }}</h5>
                        </td>
                        <td style="padding: 5px; text-align: right; border-right: 1px solid #000;"><h5 style="margin: 5px; font-weight: normal;">1.00</h5></td>
                        <td style="padding: 5px; text-align: right; border-right: 1px solid #000;"><h5 style="margin: 5px; font-weight: normal;">{{number_format((float)$amt, 2)}}</h5></td>
                        <td style="padding: 5px; text-align: right; border-right: 1px solid #000;"><h5 style="margin: 5px; font-weight: normal;">{{number_format((float)$extension['coupon_discount'][$i], 2)}}</h5></td>
                        <td style="padding: 5px; text-align: right; border-right: 1px solid #000;"><h5 style="margin: 5px; font-weight: normal;">{{number_format((float)$extension['total_amount'][$i], 2)}}</h5></td>
                    </tr>
                @endforeach
            @endif

            @if(isset($paidPenalties) && !empty($paidPenalties['trip_amount']))
                @foreach($paidPenalties['trip_amount'] as $i => $amt)
                    <tr>
                        <td style="padding: 5px; border-right: 1px solid #000;">
                            <h5 style="margin: 5px; font-weight: normal;"><b>Penalty/Adjustment</b> | {{ $paidPenalties['timestamp'][$i] ?? '' }}</h5>
                        </td>
                        <td style="padding: 5px; text-align: right; border-right: 1px solid #000;"><h5 style="margin: 5px; font-weight: normal;">1.00</h5></td>
                        <td style="padding: 5px; text-align: right; border-right: 1px solid #000;"><h5 style="margin: 5px; font-weight: normal;">{{number_format((float)$amt, 2)}}</h5></td>
                        <td style="padding: 5px; text-align: right; border-right: 1px solid #000;"><h5 style="margin: 5px; font-weight: normal;">{{number_format((float)$paidPenalties['coupon_discount'][$i], 2)}}</h5></td>
                        <td style="padding: 5px; text-align: right; border-right: 1px solid #000;"><h5 style="margin: 5px; font-weight: normal;">{{number_format((float)$paidPenalties['total_amount'][$i], 2)}}</h5></td>
                    </tr>
                @endforeach
            @endif

            <tr style="border-top: 2px solid #000;">
                <td style="padding: 5px; border-right: 1px solid #000;">
                    <h5 style="margin: 5px; font-weight: normal;">
                        <b>Vehicle Amendment</b><br/>
                        Changed to {{ $history->newVehicle->vehicle_name ?? '' }} ({{ $history->newVehicle->license_plate ?? 'N/A' }})<br/>
                        <span style="font-style: italic;">Reason: {{ $history->change_reason }}</span>
                    </h5>
                </td>
                <td style="padding: 5px; text-align: center; border-right: 1px solid #000;">-</td>
                <td style="padding: 5px; text-align: center; border-right: 1px solid #000;">-</td>
                <td style="padding: 5px; text-align: center; border-right: 1px solid #000;">-</td>
                <td style="padding: 5px; text-align: center; border-right: 1px solid #000;">-</td>
            </tr>
        </tbody>
    </table>
    
    <table class="column-bordered-table" cellpadding="0" cellspacing="0" style="width: 85.5% !important;" align="center">
        <tr>
            <td style="vertical-align: top; padding: 5px;">
                <h5 style="margin: 5px;"><b>Amount in words</b> : 
                    <span style="font-weight: normal;">
                        @php
                            $grandTotal = ($totalAmt ?? 0) + ($amountDue ?? 0);
                            $amountInWords = $grandTotal > 0 ? getIndianCurrency((float)$grandTotal) : '';
                        @endphp
                        {{ ucwords($amountInWords) }}
                    </span>
                </h5>
            </td>
            <td style="border: 0px solid #000; width: 120px; padding: 5px;">
                <h5 style="line-height: 12px; margin: 5px 0;"><b>Total Rate</b></h5>
                <h5 style="line-height: 12px; margin: 15px 0 5px 0;"><b>Grand Total</b></h5>
            </td>
            <td style="border: 0px solid #000; width: 100px; padding: 5px; text-align: right;">
                <h5 style="line-height: 12px; margin: 5px 0;"><b>{{number_format($rateTotal, 2)}}</b></h5>
                <h5 style="line-height: 12px; margin: 15px 0 5px 0;"><b>{{number_format($grandTotal, 2)}}</b></h5>
            </td>
        </tr>
        <tr>
            <td style="padding: 5px;">
                <h5 style="margin: 5px;">
                    <b>GSTNo</b> : {{$companyGst}} <b style="margin-left: 35px;">PAN No.</b> : {{$companyPan}}
                </h5>
            </td>
            <td style="text-align: left; padding: 15px; border-right: 0px solid #000; background-color: #f9f9f9;">
                <h5 style="margin: 0; color: #333;">Amount Paid</h5>
                @if(isset($amountDue) && $amountDue > 0)
                    <h5 style="margin: 10px 0 0 0;"><b>Amount Due</b></h5>
                @endif
            </td>
            <td style="text-align: right; padding: 15px; border-left: 0px solid #000; background-color: #f9f9f9;">
                <h5 style="margin: 0;">{{number_format($totalAmt, 2)}}</h5>
                @if(isset($amountDue) && $amountDue > 0)
                    <h5 style="margin: 10px 0 0 0;">{{number_format($amountDue, 2)}}</h5>
                @endif
            </td>
        </tr>
    </table>

    <table align="center" style="width: 85.5%; margin-top: 30px; border-collapse: collapse;">
        <tr>
            <td style="width: 55%;"></td>
            <td style="text-align: center; border: 1px solid #000; padding: 15px;">
                <h5 style="font-weight: normal; margin: 0 0 30px 0;">For, SHAILESH CAR & BIKE PVT LTD</h5>
                <h5 style="font-weight: bold; margin: 0; border-top: 1px solid #ccc; padding-top: 5px;">AUTHORISED SIGNATORY</h5>
            </td>
        </tr>
    </table>

    <div style="text-align: center; margin-top: 40px; font-size: 12px; color: #666; width: 100%;">
        Thank you for choosing VELRIDERS.
    </div>
</body>
</html>
