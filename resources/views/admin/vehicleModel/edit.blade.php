@extends('templates.admin')

@section('page-title')
    Models
@endsection

@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Edit Vehicle Model</h3>
        </div>
        <form class="card-body" action="{{ route('admin.vehicle-model-update', $model->model_id) }}" id="vehicleModel-form" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="name">Model Name</label><span class="text-danger">*</span>
                        <input type="text" id="name" class="form-control" name="name" required @isset($model->name)value="{{$model->name}}"@endisset>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="category">Category Name</label><span class="text-danger">*</span>
                        <select id="category" class="form-control custom-select" name="category" required>
                            <option selected disabled>Select one</option>
                            @foreach($vehicleCategoryList as $val)
                                <option value="{{ $val->category_id }}" @if(isset($model->category_id) && $model->category_id == $val->category_id){{'selected'}}@else{{''}}@endif>{{ $val->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="manufacturer">Manufacturers Name</label><span class="text-danger">*</span>
                        <select id="manufacturer" class="form-control custom-select" name="manufacturer" required>
                            <option selected disabled>Select one</option>
                            @foreach($vehicleManufacturerList as $name)
                                <option value="{{ $name['manufacturer_id'] }}" @if(isset($model->manufacturer_id) && $model->manufacturer_id == $name->manufacturer_id){{'selected'}}@else{{''}}@endif>{{ $name['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <input type="hidden" id="modelImg" @if(isset($model->model_image))value="{{$model->model_image}}"@else value="" @endif>
                        <label for="model_image">Model Image</label><span class="text-danger">*</span>
                        <input type="file" class="form-control" id="model_image" name="model_image">
                    </div>
                </div>
            </div>
            <hr/>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="min_price">Minimum Price</label><span class="text-danger">*</span>
                        <input type="text" id="min_price" class="form-control" min="1" name="min_price" required @isset($model->min_price)value="{{$model->min_price}}"@endisset>
                    </div>
                </div>
            </div>
            <div id="min_calculation" class="row">
                @if(is_countable($vehicleModelMinPriceDetails) && count($vehicleModelMinPriceDetails) > 0)
                    @foreach($vehicleModelMinPriceDetails as $vehiclePriceDetail)
                        @php 
                            $hours = $vehiclePriceDetail->hours;
                            $duration = $hours <= 24 ? $hours.'Hours' : ($hours / 24).'Days';
                        @endphp
                        <div class="col-md-3">
                            <b>{{$duration}}:</b> ₹ <input type="text" data-val="{{$hours}}" name="minPriceCalc[{{$hours}}]" style="width: 100px; margin-left: 10px;margin:3px" placeholder="Edit" value="{{$vehiclePriceDetail->rate}}" id="minpriceval_{{$hours}}" readonly>
                        </div>
                    @endforeach
                @endif
            </div>
            <hr/>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="max_price">Maximum Price</label><span class="text-danger">*</span>
                        <input type="text" id="max_price" class="form-control" min="1" name="max_price" required @isset($model->max_price)value="{{$model->max_price}}"@endisset>
                    </div>
                </div>
            </div>
            <div id="max_calculation" class="row mb-5">
                @if(is_countable($vehicleModelMaxPriceDetails) && count($vehicleModelMaxPriceDetails) > 0)
                    @foreach($vehicleModelMaxPriceDetails as $vehiclePriceDetail)
                        @php 
                            $hours = $vehiclePriceDetail->hours;
                            $duration = $hours <= 24 ? $hours.'Hours' : ($hours / 24).'Days';
                        @endphp
                        <div class="col-md-3">
                            <b>{{$duration}}:</b> ₹ <input type="text" data-val="{{$hours}}" name="maxPriceCalc[{{$hours}}]" style="width: 100px; margin-left: 10px;margin:3px" placeholder="Edit" value="{{$vehiclePriceDetail->rate}}" id="maxpriceval_{{$hours}}" readonly>
                        </div>
                    @endforeach
                @endif
            </div>
            <button type="submit" class="btn btn-primary">Update Models</button>
            <a href="{{route('admin.vehicle-models')}}" class="btn btn-danger">Cancel</a>
        </form>
    </div>
</div>
</div>
</div>
</section>
@push('scripts')
 <script src="{{ asset('all_js/admin_js/vehicles.js') }}"></script>
 <script>
    $( document ).ready(function() {
        $('#min_price').on('keyup', function() {
            const rentalPrice = parseFloat($(this).val());
            if (!isNaN(rentalPrice) && rentalPrice > 0 && rentalPrice != '') {
                $('#min_calculation').empty();
                @foreach ($rules as $rule)
                    var hours = {{ $rule->hours }};
                    var multiplier = {{ $rule->multiplier }};
                    var tripAmount = multiplier * rentalPrice;
                    // Determine if the trip duration is in hours or days
                    var duration = hours <= 24 ? `${hours} Hours` : `${hours / 24} Days`;
                    var calculationItem = $('<div class="col-md-3">').html(
                            `<b>${duration}:</b> ₹ <input type="text" data-val="${hours}"  name="minPriceCalc[${hours}]" style="width: 100px; margin-left: 10px;margin:3px" placeholder="Edit" class="" value="${tripAmount}" id="minpriceval_${hours}" readonly>`);
                        $('#min_calculation').append(calculationItem);
                @endforeach
            }else{
                $('#min_calculation').empty();
                @foreach ($rules as $rule)
                    var hours = {{ $rule->hours }};
                    var duration = hours <= 24 ? `${hours} Hours` : `${hours / 24} Days`;
                    var calculationItem = $('<div class="col-md-3">').html(
                        `<b>${duration}:</b> ₹ <input type="text" data-val="${hours}" class="" name="minPriceCalc[${hours}]" style="width: 100px; margin-left: 10px;margin:3px" placeholder="Edit" value="0" id="minpriceval_${hours}" readonly>`);
                    $('#min_calculation').append(calculationItem);
                @endforeach
            }
        });

        $('#max_price').on('keyup', function() {
            const rentalPrice = parseFloat($(this).val());
            if (!isNaN(rentalPrice) && rentalPrice > 0 && rentalPrice != '') {
                $('#max_calculation').empty();
                @foreach ($rules as $rule)
                    var hours = {{ $rule->hours }};
                    var multiplier = {{ $rule->multiplier }};
                    var tripAmount = multiplier * rentalPrice;
                    // Determine if the trip duration is in hours or days
                    var duration = hours <= 24 ? `${hours} Hours` : `${hours / 24} Days`;
                    var calculationItem = $('<div class="col-md-3">').html(
                            `<b>${duration}:</b> ₹ <input type="text" data-val="${hours}"  name="maxPriceCalc[${hours}]" style="width: 100px; margin-left: 10px;margin:3px" placeholder="Edit" class="" value="${tripAmount}" id="maxpriceval_${hours}" readonly>`);
                        $('#max_calculation').append(calculationItem);
                @endforeach
            }else{
                $('#max_calculation').empty();
                @foreach ($rules as $rule)
                    var hours = {{ $rule->hours }};
                    var duration = hours <= 24 ? `${hours} Hours` : `${hours / 24} Days`;
                    var calculationItem = $('<div class="col-md-3">').html(
                        `<b>${duration}:</b> ₹ <input type="text" data-val="${hours}" class="" name="maxPriceCalc[${hours}]" style="width: 100px; margin-left: 10px;margin:3px" placeholder="Edit" value="0" id="maxpriceval_${hours}" readonly>`);
                    $('#max_calculation').append(calculationItem);
                @endforeach
            }
        });
    });
</script>
@endpush
@endsection
