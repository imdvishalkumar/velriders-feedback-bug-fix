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
            <h3 class="card-title">Add Vehicle Model</h3>
        </div>
        <form class="card-body" action="{{ route('admin.vehicleModel-insert') }}" id="vehicleModel-form" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="name">Model Name</label><span class="text-danger">*</span>
                        <input type="text" id="name" class="form-control" name="name" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="category">Category Name</label><span class="text-danger">*</span>
                        <select id="category" class="form-control custom-select" name="category" required>
                            <option selected disabled>Select one</option>
                            @foreach($vehicleCategoryList as $val)
                                <option value="{{ $val->category_id }}">{{ $val->name }}</option>
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
                                <option value="{{ $name['manufacturer_id'] }}">{{ $name['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                         <input type="hidden" id="modelImg" value="">
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
                        <input type="number" id="min_price" class="form-control" name="min_price" required min="1">
                    </div>
                </div>
            </div>
            <div id="min_calculation" class="row">
                <!-- MINIMUM AMOUNT CALCULATION WILL APPEND HERE -->
            </div>
            <hr/>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="max_price">Maximum Price</label><span class="text-danger">*</span>
                        <input type="number" id="max_price" class="form-control" name="max_price" required min="1">
                    </div>
                </div>
            </div>
            <div id="max_calculation" class="row mb-5">
                <!-- MAXIMUM AMOUNT CALCULATION WILL APPEND HERE -->
            </div>
            <button type="submit" class="btn btn-primary">Add Models</button>
            <a href="/admin/vehicle-models" class="btn btn-danger">Cancel</a>
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
                    var duration = hours <= 24 ? `${hours} Hours` : `${hours / 24} Days`;
                    var min_calculationItem = $('<div class="col-md-3">').html(
                        `<b>${duration}:</b> ₹ <input type="text" data-val="${hours}" name="minPriceCalc[${hours}]" style="width: 100px; margin-left: 10px;margin:3px" placeholder="Edit" class="" value="${tripAmount}" id="minpriceval_${hours}" readonly>`);
                    $('#min_calculation').append(min_calculationItem);
                @endforeach
            }else{
                $('#min_calculation').empty();
                @foreach ($rules as $rule)
                    var hours = {{ $rule->hours }};
                    var duration = hours <= 24 ? `${hours} Hours` : `${hours / 24} Days`;
                    var min_calculationItem = $('<div class="col-md-3">').html(
                        `<b>${duration}:</b> ₹ <input type="text" data-val="${hours}" class="" name="minPriceCalc[${hours}]" style="width: 100px; margin-left: 10px;margin:3px" placeholder="Edit" value="0" id="minpriceval_${hours}" readonly>`);
                    $('#min_calculation').append(min_calculationItem);
                @endforeach
            }
        }).trigger('keyup'); // Trigger keyup event once on page load

        $('#max_price').on('keyup', function() {
            const rentalPrice = parseFloat($(this).val());
            if (!isNaN(rentalPrice) && rentalPrice > 0 && rentalPrice != '') {
                $('#max_calculation').empty();
                @foreach ($rules as $rule)
                    var hours = {{ $rule->hours }};
                    var multiplier = {{ $rule->multiplier }};
                    var tripAmount = multiplier * rentalPrice;
                    var duration = hours <= 24 ? `${hours} Hours` : `${hours / 24} Days`;
                    var min_calculationItem = $('<div class="col-md-3">').html(
                        `<b>${duration}:</b> ₹ <input type="text" data-val="${hours}" name="maxPriceCalc[${hours}]" style="width: 100px; margin-left: 10px;margin:3px" placeholder="Edit" class="" value="${tripAmount}" id="maxpriceval_${hours}" readonly>`);
                        console.log("MIN CALAL - " + min_calculationItem);
                    $('#max_calculation').append(min_calculationItem);
                @endforeach
            }else{
                $('#max_calculation').empty();
                @foreach ($rules as $rule)
                    var hours = {{ $rule->hours }};
                    var duration = hours <= 24 ? `${hours} Hours` : `${hours / 24} Days`;
                    var min_calculationItem = $('<div class="col-md-3">').html(
                        `<b>${duration}:</b> ₹ <input type="text" data-val="${hours}" class="" name="maxPriceCalc[${hours}]" style="width: 100px; margin-left: 10px;margin:3px" placeholder="Edit" value="0" id="maxpriceval_${hours}" readonly>`);
                    $('#max_calculation').append(min_calculationItem);
                @endforeach
            }
        }).trigger('keyup'); // Trigger keyup event once on page load
    });
 </script>
@endpush

@endsection
