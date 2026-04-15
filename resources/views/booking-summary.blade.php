<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Summary</title>
    <style>
        * {
            font-family: DejaVu Sans, sans-serif;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: #fff;
            color: #333;
            margin: 0;
            padding: 5px;
            font-size: 11px;
        }

        .container {
            max-width: 600px;
            margin: auto;
            padding: 5px;
            border: 1px solid #eaeaea;
            border-radius: 5px;
            page-break-inside: avoid;
        }

        .header {
            text-align: center;
            padding: 5px 0;
            page-break-inside: avoid;
        }

        .header h2 {
            margin: 0;
            font-size: 16px;
            color: #007bff;
        }

        .section {
            padding: 5px 0;
            border-bottom: 1px solid #eaeaea;
            page-break-inside: avoid;
        }

        .section:last-child {
            border-bottom: none;
        }

        .section-title {
            font-size: 12px;
            margin-bottom: 4px;
            color: #333;
        }

        .detail {
            margin-bottom: 1px;
            font-size: 11px;
        }

        .detail-title {
            display: inline-block;
            min-width: 100px;
            font-weight: bold;
        }

        .footer {
            text-align: center;
            padding: 5px 0;
            font-size: 10px;
            color: #999;
            page-break-inside: avoid;
        }

        /* Two-column layout for sections */
        .two-column {
            display: flex;
            flex-wrap: wrap;
        }

        .two-column .section {
            flex: 1;
            min-width: 50%;
            padding: 2px;
            page-break-inside: avoid;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h2>Booking Summary</h2>
        </div>

        <div class="section">
            <div class="section-title">Booking Details</div>
            <div class="detail"><span class="detail-title">Booking ID:</span> {{ $data->booking_id }}</div>
            <div class="detail"><span class="detail-title">Status:</span> {{ $data->status }}</div>
            <div class="detail"><span class="detail-title">Pickup Branch:</span> {{ $data->fromBranch->name ?? 'N/A' }}
            </div>
            <div class="detail"><span class="detail-title">Return Branch:</span> {{ $data->fromBranch->name ?? 'N/A' }}
            </div>
            <div class="detail"><span class="detail-title">Booking Start Date:</span>
                @isset($data->pickup_date) {{ \Carbon\Carbon::parse($data->pickup_date)->format('d M Y, h:i A') }} @endisset</div>
            <div class="detail"><span class="detail-title">Booking End Date:</span>
                @isset($data->return_date) {{ \Carbon\Carbon::parse($data->return_date)->format('d M Y, h:i A') }} @endisset</div>

            <div class="detail"><span class="detail-title">Start Date:</span>
                @isset($data->start_datetime) {{ \Carbon\Carbon::parse($data->start_datetime)->format('d M Y, h:i A') }} @endisset</div>
            <div class="detail"><span class="detail-title">End Date:</span>
                @isset($data->end_datetime) {{ \Carbon\Carbon::parse($data->end_datetime)->format('d M Y, h:i A') }} @endisset</div>
            @php
            use Illuminate\Support\Str;

            $totalMinutes = $data->rental_duration_minutes;
            $days = intdiv($totalMinutes, 1440);
            $hours = intdiv($totalMinutes % 1440, 60);
            $minutes = $totalMinutes % 60;
            @endphp

            <div class="detail">
                <span class="detail-title">Rental Duration:</span>
                @if ($days)
                {{ $days }} {{ Str::plural('Day', $days) }}
                @endif
                @if ($hours)
                {{ $hours }} {{ Str::plural('Hour', $hours) }}
                @endif
                @if ($minutes)
                {{ $minutes }} {{ Str::plural('Minute', $minutes) }}
                @endif
            </div>
        </div>
        @if(isset($data->vehicle) || isset($data->customer))
        <div class="two-column">
            @if(isset($data->vehicle))
            <div class="section">
                <div class="section-title">Vehicle Details</div>
                <div class="detail"><span class="detail-title">Vehicle:</span> {{ $data->vehicle->vehicle_name ?? ''}}</div>
                <div class="detail"><span class="detail-title">Category:</span> {{ $data->vehicle->category_name ?? ''}}</div>
                <div class="detail"><span class="detail-title">Reg. Number:</span> {{ $data->vehicle->license_plate ?? '' }}
                </div>
            </div>
            @endif
            @if(isset($data->customer))
            <div class="section">
                <div class="section-title">Customer Details</div>
                <div class="detail"><span class="detail-title">Name:</span> {{ $data->customer->firstname ?? '' }} {{ $data->customer->lastname ?? ''}}</div>
                <div class="detail"><span class="detail-title">Email:</span> {{ $data->customer->email ?? '' }}</div>
                <div class="detail"><span class="detail-title">Mobile:</span> {{ $data->customer->mobile_number ?? '' }}</div>
                <div class="detail"><span class="detail-title">DOB:</span> {{ $data->customer->dob ?? '' }}</div>
                <br />
                <div class="section-title">Document Details</div>
                <div class="detail"><span class="detail-title">Gov. ID Status:</span>@isset($data->gov_status){{ $data->gov_status }}@endisset</div>
                <div class="detail"><span class="detail-title">Gov. ID Number:</span>@isset($data->gov_id_number){{ $data->gov_id_number }}@endisset</div>
                <div class="detail"><span class="detail-title">DL ID Status:</span>@isset($data->dl_status){{ $data->dl_status }}@endisset</div>
                <div class="detail"><span class="detail-title">DL ID Number:</span>@isset($data->dl_id_number){{ $data->dl_id_number }}@endisset</div>
            </div>
            @endif
        </div>
        @endif
     
        <div class="section">
            <div class="section-title">Price Summary</div><br/>
            @php $finalPrice = 0; @endphp
            @foreach ($data->price_summary as $key => $item)
            <div class="detail">
                @php
                    $updatedKey = '';
                    if(strtolower($item['key']) == 'final amount'){
                        $cleanedPrice = str_replace(['₹', ','], '', $item['value']);
                        $finalPrice += $cleanedPrice;
                    }
                    if(strtolower($item['key']) == 'refundable deposit used'){
                        $cleanedPrice = str_replace(['₹', ','], '', $item['value']);
                        $finalPrice += $cleanedPrice;
                    }
                    if($key == 0){
                        $position = strpos($item['key'], "Amount");
                        if ($position !== false) {
                        $position += strlen("Amount");
                        $firstPart = 'Trip Amount';
                        $secondPart = substr($item['key'], $position);
                        $secondPart = str_replace('From', '<br/>From', $secondPart);
                        $updatedKey = $firstPart.'<br/>'.$secondPart;
                        }
                    }
                    else{
                        $updatedKey = $item['key'];
                    }
                @endphp
                @if(strtolower($item['key']) == 'final amount')
                    <span class="detail-title">Final Price: </span> <span> ₹ {{number_format(round($finalPrice), 2)}} </span>
                @else
                    <span class="detail-title">{!! $updatedKey !!}: </span> <span> {{ $item['value'] }} </span>
                @endif
            </div>
            @endforeach
        </div>

        <div class="footer">
            Thank you for choosing VELRIDERS.
        </div>
    </div>
</body>
</html>
