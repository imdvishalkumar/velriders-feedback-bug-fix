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
                <h3 style="margin: 0; padding-top: 10px;">SHAILESH CAR & BIKE PVT LTD</h3>
                <p style="font-size: 11px; margin: 5px 0; line-height: 1.4;">
                    {{$companyAdd}}<br/>
                    {{$companyPhone}}@if($companyAltPhone), {{$companyAltPhone}}@endif<br/>
                    {{$companyEmail}}
                </p>
            </td>
        </tr>
    </table>

    <table class="column-bordered-table" align="center" style="width: 85.5% !important; margin-top: 10px;">
        <tr>
            <td rowspan="2" style="text-align: left; vertical-align: top; width: 45%; padding: 10px;">
                <h4 style="margin: 0 0 10px 0; border-bottom: 1px solid #ccc; padding-bottom: 5px;">CarHost Details</h4>
                @php 
                    $hostName = '';
                    if(isset($carHost->firstname)) $hostName = $carHost->firstname;
                    if(isset($carHost->lastname)) $hostName .= ' '.$carHost->lastname;
                @endphp
                <div style="font-size: 13px;">
                    @if(isset($carHost->business_name) && $carHost->business_name != '')
                        <div style="font-weight: bold; margin-bottom: 5px;">{{$carHost->business_name}}</div>
                    @endif
                    <div style="font-weight: bold; margin-bottom: 5px; font-size: 14px;">{{$hostName}}</div>
                    <div style="margin-bottom: 3px;">{{$carHost->mobile_number ?? ''}}</div>
                    <div style="margin-bottom: 5px;">{{$carHost->email ?? ''}}</div>
                    @if(isset($carHost->billing_address))
                        <div style="font-size: 11px; color: #555;">{{$carHost->billing_address}}</div>
                    @endif
                </div>
            </td>
            <td style="background-color: #f9f9f9; padding: 5px 10px;"><h4 style="margin: 0;">Tax Invoice</h4></td>
        </tr>
        <tr>
            <td style="padding: 10px; vertical-align: top;">
                <table width="100%" style="font-size: 12px; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 2px 0;"><b>Invoice No. :</b> VR-{{ $history->id }}</td>
                        <td style="text-align: right; padding: 2px 0;"><b>Date :</b> {{ date('d-m-Y', strtotime($history->created_at)) }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding: 2px 0;"><b>Booking ID :</b> {{ $history->booking_id }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding: 8px 0 2px 0; border-top: 1px dashed #ccc;">
                            <b>Pickup:</b> {{ date('d-m-Y H:i', strtotime($data->pickup_date)) }}
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding: 2px 0;">
                            <b>Return:</b> {{ date('d-m-Y H:i', strtotime($data->end_datetime ?? $data->return_date)) }}
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding: 8px 0 2px 0; border-top: 1px dashed #ccc;">
                            <b>Vehicle:</b> {{ $history->newVehicle->vehicle_name ?? 'N/A' }} ({{ $history->newVehicle->model->category->name ?? '' }})
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding: 2px 0;">
                            <b>Registration Number:</b> {{ $history->newVehicle->license_plate ?? 'N/A' }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="column-bordered-table" cellpadding="0" cellspacing="0" align="center" style="width: 85.5% !important; margin-top: 10px;">
        <thead>
            <tr style="background-color: #f2f2f2;">
                <th style="padding: 8px; text-align: left;">Particular</th>
                <th style="padding: 8px; width: 60px;">Qty.</th>
                <th style="padding: 8px; width: 80px;">Rate</th>
                <th style="padding: 8px; width: 80px;">Discount</th>
                <th style="padding: 8px; width: 100px;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @if(isset($newBooking) && !empty($newBooking))
                <tr>
                    <td style="padding: 8px; font-size: 13px;">
                        <b>Booking</b> | {{$newBookingTimeStamp}}
                    </td>
                    <td style="padding: 8px; text-align: center; font-size: 13px;">1.00</td>
                    <td style="padding: 8px; text-align: right; font-size: 13px;">{{$newBooking['trip_amount'] ?? '0.00'}}</td>
                    <td style="padding: 8px; text-align: right; font-size: 13px;">{{$newBooking['coupon_discount'] ?? '0.00'}}</td>
                    <td style="padding: 8px; text-align: right; font-size: 13px;">{{$newBooking['total_amount'] ?? '0.00'}}</td>
                </tr>
            @endif

            @if(isset($extension) && !empty($extension['trip_amount']))
                @foreach($extension['trip_amount'] as $i => $amt)
                    <tr>
                        <td style="padding: 8px; font-size: 13px;">
                            <b>Extension</b> | {{ $extension['timestamp'][$i] ?? '' }}
                        </td>
                        <td style="padding: 8px; text-align: center; font-size: 13px;">1.00</td>
                        <td style="padding: 8px; text-align: right; font-size: 13px;">{{$amt}}</td>
                        <td style="padding: 8px; text-align: right; font-size: 13px;">{{$extension['coupon_discount'][$i] ?? '0.00'}}</td>
                        <td style="padding: 8px; text-align: right; font-size: 13px;">{{$extension['total_amount'][$i] ?? '0.00'}}</td>
                    </tr>
                @endforeach
            @endif

            @if(isset($paidPenalties) && !empty($paidPenalties['trip_amount']))
                @foreach($paidPenalties['trip_amount'] as $i => $amt)
                    <tr>
                        <td style="padding: 8px; font-size: 13px;">
                            <b>Penalty/Adjustment</b> | {{ $paidPenalties['timestamp'][$i] ?? '' }}
                        </td>
                        <td style="padding: 8px; text-align: center; font-size: 13px;">1.00</td>
                        <td style="padding: 8px; text-align: right; font-size: 13px;">{{$amt}}</td>
                        <td style="padding: 8px; text-align: right; font-size: 13px;">{{$paidPenalties['coupon_discount'][$i] ?? '0.00'}}</td>
                        <td style="padding: 8px; text-align: right; font-size: 13px;">{{$paidPenalties['total_amount'][$i] ?? '0.00'}}</td>
                    </tr>
                @endforeach
            @endif

            <tr>
                <td style="padding: 8px; font-size: 12px; color: #444;">
                    <div style="font-weight: bold; margin-bottom: 2px;">Vehicle Amendment</div>
                    <div>Changed to {{ $history->newVehicle->vehicle_name ?? '' }} ({{ $history->newVehicle->license_plate ?? 'N/A' }})</div>
                    <div style="font-style: italic; margin-top: 3px;">Reason: {{ $history->change_reason }}</div>
                </td>
                <td style="padding: 8px; text-align: center;">-</td>
                <td style="padding: 8px; text-align: center;">-</td>
                <td style="padding: 8px; text-align: center;">-</td>
                <td style="padding: 8px; text-align: center;">-</td>
            </tr>
        </tbody>
    </table>
    
    <table class="column-bordered-table" align="center" style="width: 85.5% !important;">
        <tr>
            <td style="width: 60%; padding: 10px; border-right: none;">
                <div style="font-size: 11px; margin-bottom: 5px;"><b>Amount in words:</b></div>
                @php
                    $grandTotal = ($totalAmt ?? 0) + ($amountDue ?? 0);
                    $amountInWords = $grandTotal > 0 ? getIndianCurrency((float)$grandTotal) : '';
                @endphp
                <div style="font-size: 12px; font-weight: bold; text-transform: uppercase;">{{$amountInWords}} ONLY</div>
                
                <div style="margin-top: 15px; font-size: 11px;">
                    <b>GST No :</b> {{$companyGst}} | <b>PAN No :</b> {{$companyPan}}
                </div>
            </td>
            <td style="padding: 0; border-left: none;">
                <table width="100%" cellpadding="5" style="border-collapse: collapse; font-size: 13px;">
                    <tr>
                        <td style="border-bottom: 1px solid #ccc;">Total Rate</td>
                        <td style="text-align: right; border-bottom: 1px solid #ccc; font-weight: bold;">{{number_format($rateTotal, 2)}}</td>
                    </tr>
                    <tr style="background-color: #f2f2f2;">
                        <td style="border-bottom: 1px solid #000;"><b>Grand Total</b></td>
                        <td style="text-align: right; border-bottom: 1px solid #000; font-weight: bold;">{{number_format($grandTotal, 2)}}</td>
                    </tr>
                    <tr>
                        <td style="color: green; border-top: 1px solid #000;">Amount Paid</td>
                        <td style="text-align: right; font-weight: bold; color: green; border-top: 1px solid #000;">{{number_format($totalAmt, 2)}}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table align="center" style="width: 85.5%; margin-top: 30px;">
        <tr>
            <td style="width: 60%;"></td>
            <td style="text-align: center; border: 1px solid #eee; padding: 20px;">
                <div style="font-size: 12px; margin-bottom: 40px;">For, <b>SHAILESH CAR & BIKE PVT LTD</b></div>
                <div style="font-size: 12px; font-weight: bold; border-top: 1px solid #ccc; padding-top: 5px;">AUTHORISED SIGNATORY</div>
            </td>
        </tr>
    </table>

    <div style="text-align: center; margin-top: 40px; font-size: 12px; color: #666; width: 100%;">
        Thank you for choosing <b>VELRIDERS</b>.
    </div>
</body>
</html>
