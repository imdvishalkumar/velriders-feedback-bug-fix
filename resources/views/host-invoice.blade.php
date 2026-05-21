<html>
<head>
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    @page { 
        margin: 20px;
    }
    body {
        font-family: 'DejaVu Sans', sans-serif;
        font-size: 11px;
        margin: 0;
        padding: 0;
    }
    .header-table {
        width: 100%;
        margin-bottom: 5px;
    }
    .logo-img {
        width: 130px;
    }
    .company-info {
        text-align: right;
    }
    .company-name {
        font-size: 15px;
        font-weight: bold;
        margin: 0;
    }
    .company-address {
        font-size: 9px;
        margin-top: 3px;
        font-weight: bold;
    }
    .main-table {
        width: 100%;
        border-collapse: collapse;
        border: 1px solid #000;
    }
    .main-table td, .main-table th {
        border: 1px solid #000;
        padding: 5px;
        vertical-align: top;
    }
    .particulars-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        border: 1px solid #000;
    }
    .particulars-table th {
        border: 1px solid #000;
        text-align: center;
        padding: 6px;
        font-size: 12px;
        font-weight: bold;
    }
    .particulars-table td {
        border: 1px solid #000;
        padding: 5px;
    }
    .font-bold { font-weight: bold; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
</style>
</head>
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

        $extensions = [];
        if(isset($extension['trip_amount']) && is_array($extension['trip_amount'])){
            foreach($extension['trip_amount'] as $i => $amt){
                $extensions[] = [
                    'amount' => $amt,
                    'timestamp' => $extension['timestamp'][$i] ?? '',
                    'discount' => $extension['coupon_discount'][$i] ?? 0,
                    'total' => $extension['total_amount'][$i] ?? 0
                ];
            }
        }

        $penalties = [];
        if(isset($paidPenalties['trip_amount']) && is_array($paidPenalties['trip_amount'])){
            foreach($paidPenalties['trip_amount'] as $i => $amt){
                $penalties[] = [
                    'amount' => $amt,
                    'timestamp' => $paidPenalties['timestamp'][$i] ?? '',
                    'discount' => $paidPenalties['coupon_discount'][$i] ?? 0,
                    'total' => $paidPenalties['total_amount'][$i] ?? 0
                ];
            }
        }
    @endphp

    <table class="header-table">
        <tr>
            <td>
                @php
                    $logoPath = public_path('/images/mask.jpg');
                    $image = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : '';
                @endphp            
                @if($image)
                    <img src="data:image/png;base64,{{ $image }}" class="logo-img">
                @endif
            </td>
            <td class="company-info">
                <p class="company-name">SHAILESH CAR & BIKE PVT LTD</p>
                <p class="company-address">
                    {{$companyAdd}}<br/>
                    {{$companyPhone}}, {{$companyAltPhone}}, {{$companyEmail}}
                </p>
            </td>
        </tr>
    </table>

    <table class="main-table">
        <tr style="height: 120px;">
            <td style="width: 50%;">
                <p class="font-bold" style="font-size: 13px; margin: 0 0 8px 0;">CarHost Details</p>
                @php 
                    $hostName = '';
                    if(isset($carHost->firstname)) $hostName = $carHost->firstname;
                    if(isset($carHost->lastname)) $hostName .= ' '.$carHost->lastname;
                @endphp
                <p style="margin: 2px 0; line-height: 1.4;">
                    @if(isset($carHost->gst_number) && $carHost->gst_number != '')
                        <b>GST No. - {{$carHost->gst_number}}</b><br/>
                    @endif
                    @if(isset($carHost->business_name) && $carHost->business_name != '')
                        <b>Business Name - {{$carHost->business_name}}</b><br/>
                    @endif
                    <span style="font-size: 13px; font-weight: bold;">{{$hostName}}</span><br/>
                    {{$carHost->mobile_number ?? ''}}<br/>
                    {{$carHost->email ?? ''}}
                </p>
            </td>
            <td>
                <p class="font-bold" style="font-size: 13px; margin: 0 0 8px 0;">Tax Invoice</p>
                <p style="margin: 2px 0; line-height: 1.6;">
                    <b>Invoice No. :</b> @if(isset($is_history) && $is_history) VR-{{ $history->id }} @else VR-{{ $data->sequence_no ?? $data->booking_id }} @endif &nbsp;&nbsp;
                    <b>Booking ID :</b> {{ $data->booking_id }} &nbsp;&nbsp;
                    <b>Date :</b> {{ date('d-m-Y', strtotime((isset($is_history) && $is_history) ? $history->created_at : ($completionNewBooking ?: now()))) }}<br/>
                    <b>Pickup Date -</b> {{ date('d-m-Y H:i', strtotime($data->pickup_date)) }} | <b>Return Date -</b> {{ date('d-m-Y H:i', strtotime($data->end_datetime ?? $data->return_date)) }}<br/>
                    <b>Vehicle -</b> {{ $data->vehicle->vehicle_name ?? 'N/A' }} ({{ $data->vehicle->model->category->name ?? $data->vehicle->category_name ?? '' }})<br/>
                    <b>Registration Number:</b> {{ $data->vehicle->license_plate ?? 'N/A' }}
                </p>
            </td>
        </tr>
    </table>

    <table class="particulars-table">
        <thead>
            <tr>
                <th style="width: 45%;">Particular</th>
                <th style="width: 10%;">Qty.</th>
                <th style="width: 15%;">Rate</th>
                <th style="width: 15%;">Discount</th>
                <th style="width: 15%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @if(isset($newBooking) && !empty($newBooking) && (isset($newBooking['total_amount']) && $newBooking['total_amount'] != 0))
                <tr>
                    <td style="height: 30px;"><b>Booking</b> | {{$newBookingTimeStamp}}</td>
                    <td class="text-center">1.00</td>
                    <td class="text-right">{{number_format((float)str_replace(',','',$newBooking['trip_amount']), 2)}}</td>
                    <td class="text-right">{{number_format((float)str_replace(',','',$newBooking['coupon_discount']), 2)}}</td>
                    <td class="text-right">{{number_format((float)str_replace(',','',$newBooking['total_amount']), 2)}}</td>
                </tr>
            @endif

            @foreach($extensions as $ext)
                <tr>
                    <td style="height: 30px;"><b>Extension</b> | {{ $ext['timestamp'] }}</td>
                    <td class="text-center">1.00</td>
                    <td class="text-right">{{number_format((float)str_replace(',','',$ext['amount']), 2)}}</td>
                    <td class="text-right">{{number_format((float)str_replace(',','',$ext['discount']), 2)}}</td>
                    <td class="text-right">{{number_format((float)str_replace(',','',$ext['total']), 2)}}</td>
                </tr>
            @endforeach

            @foreach($penalties as $pen)
                <tr>
                    <td style="height: 30px;"><b>Penalty/Adjustment</b> | {{ $pen['timestamp'] }}</td>
                    <td class="text-center">1.00</td>
                    <td class="text-right">{{number_format((float)str_replace(',','',$pen['amount']), 2)}}</td>
                    <td class="text-right">{{number_format((float)str_replace(',','',$pen['discount']), 2)}}</td>
                    <td class="text-right">{{number_format((float)str_replace(',','',$pen['total']), 2)}}</td>
                </tr>
            @endforeach

            @if(isset($completion) && !empty($completion))
                @php 
                    $compAmounts = is_array($completion['total_amount'] ?? null) ? $completion['total_amount'] : [$completion['total_amount'] ?? 0];
                    $compRates = is_array($completion['trip_amount'] ?? null) ? $completion['trip_amount'] : [$completion['trip_amount'] ?? 0];
                    $compTimes = is_array($completion['timestamp'] ?? null) ? $completion['timestamp'] : [$completion['timestamp'] ?? ''];
                @endphp
                @foreach($compAmounts as $i => $compAmt)
                    @if($compAmt != 0)
                    <tr>
                        <td style="height: 30px;"><b>Completion</b> | {{ $compTimes[$i] ?? $completionNewBooking ?? '' }}</td>
                        <td class="text-center">1.00</td>
                        <td class="text-right">{{number_format((float)str_replace(',','',$compRates[$i] ?? 0), 2)}}</td>
                        <td class="text-right">0.00</td>
                        <td class="text-right">{{number_format((float)str_replace(',','',$compAmt), 2)}}</td>
                    </tr>
                    @endif
                @endforeach
            @endif

            @if(isset($is_history) && $is_history)
                <tr>
                    <td style="padding: 10px 5px; height: 65px;">
                        <span class="font-bold">Vehicle Amendment</span> ({{ ucfirst($history_type) }} Vehicle)<br/>
                        <span style="font-size: 10px;">
                            @if($history_type == 'old')
                                Used until {{ date('d-m-Y H:i', strtotime($history->change_datetime)) }}
                            @else
                                Used from {{ date('d-m-Y H:i', strtotime($history->change_datetime)) }}
                            @endif
                            <br/>
                            Reason: {{ $history->change_reason }}
                        </span>
                    </td>
                    <td class="text-center">-</td>
                    <td class="text-center">-</td>
                    <td class="text-center">-</td>
                    <td class="text-center">-</td>
                </tr>
            @endif
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" rowspan="2" style="vertical-align: middle; padding: 10px;">
                    <p style="margin: 0;"><b>Amount in words :</b> 
                        <span style="text-transform: capitalize;">
                            @php
                                $grandTotal = ($totalAmt ?? 0) + ($amountDue ?? 0);
                                $amountInWords = $grandTotal > 0 ? getIndianCurrency((float)$grandTotal) : '';
                            @endphp
                            {{ $amountInWords }}
                        </span>
                    </p>
                </td>
                <td class="font-bold" style="border-bottom: 1px solid #000; height: 30px;">Total Rate</td>
                <td class="text-right font-bold" style="border-bottom: 1px solid #000;">{{number_format($rateTotal, 2)}}</td>
            </tr>
            <tr>
                <td class="font-bold" style="height: 30px;">Grand Total</td>
                <td class="text-right font-bold">{{number_format($grandTotal, 2)}}</td>
            </tr>
            <tr>
                <td colspan="3" style="vertical-align: middle; padding: 10px;">
                    <b>GSTNo : {{$companyGst}}</b> &nbsp;&nbsp;&nbsp;&nbsp; <b>PAN No. : {{$companyPan}}</b>
                </td>
                <td class="font-bold" style="height: 30px;">Amount Paid</td>
                <td class="text-right font-bold">{{number_format($totalAmt, 2)}}</td>
            </tr>
            <tr>
                <td colspan="3" style="height: 120px;"></td>
                <td colspan="2" style="vertical-align: bottom; padding: 15px; text-align: center;">
                    <p style="margin: 0 0 50px 0;">For, SHAILESH CAR & BIKE PVT LTD</p>
                    <p class="font-bold" style="border-top: 1px solid #ccc; padding-top: 8px; margin: 0; font-size: 12px;">AUTHORISED SIGNATORY</p>
                </td>
            </tr>
        </tfoot>
    </table>

    <div style="text-align: center; margin-top: 40px; color: #888; font-size: 10px;">
        Thank you for choosing VELRIDERS.
    </div>
</body>
</html>
