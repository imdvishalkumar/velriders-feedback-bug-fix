@extends('templates.admin')

@section('page-title')
Coupon
@endsection

@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Edit Coupon</h3>
        </div>
        <form class="card-body" action="{{ route('admin.update-coupon', ['id' => $coupon->id]) }}" id="vehicleModel-form" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="couponStoreId" name="couponStoreId" value="{{ $coupon->id }}">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="code">Coupon Code</label>
                        <input type="text" class="form-control" id="code" name="code" value="{{ $coupon->code }}" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="percentage_discount">Percentage Discount</label>
                        <input type="number" class="form-control" id="percentage_discount" name="percentage_discount" value="{{ $coupon->percentage_discount }}" required>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label for="max_discount_amount">Max Discount Amount</label>
                        <input type="number" class="form-control" id="max_discount_amount" name="max_discount_amount" value="{{ $coupon->max_discount_amount }}" required>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label for="fixed_discount_amount">Fixed Discount Amount</label>
                        <input type="number" class="form-control" id="fixed_discount_amount" name="fixed_discount_amount" value="{{ $coupon->fixed_discount_amount }}" required>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="valid_from">Valid From</label>  
                        <div class="input-group date" data-target-input="nearest">
                            <input type="text" id="valid_from"  name="valid_from" class="form-control datetimepicker-input" data-target="#valid_from" placeholder="Select Date" autocomplete="off" data-existing-date="@isset($coupon->valid_from){{ date('d-m-Y H:i', strtotime($coupon->valid_from)) }}@endisset" @isset($coupon->valid_from)value="{{date('d-m-Y H:i', strtotime($coupon->valid_from))}}"@endisset />
                            <div class="input-group-append" data-target="#valid_from" data-toggle="datetimepicker">
                                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                            </div>
                        </div>
                        <span id="valid_from_error"></span>
                    </div>
                </div>
                <div class="col-md-3">
                    <label for="is_active">Is Active</label>
                    <div class="input-group-append">
                        <select id="is_active" class="form-control custom-select" name="is_active" required>
                            <option value="1" {{ optional($coupon)->is_active == '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ optional($coupon)->is_active == '0' ? 'selected' : '' }}>In Active</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label for="valid_to">Valid To</label>
                        <!-- <input type="text" class="form-control" id="valid_to" name="valid_to" value="{{ $coupon->valid_to ? \Carbon\Carbon::parse($coupon->valid_to)->format('Y-m-d') : '' }}" required autocomplete="off" placeholder="Select Date"> -->
                        <div class="input-group date" data-target-input="nearest">
                            <input type="text" id="valid_to" name="valid_to" class="form-control datetimepicker-input" data-target="#valid_to" placeholder="Select Date" autocomplete="off" @isset($coupon->valid_to)value="{{date('d-m-Y H:i', strtotime($coupon->valid_to))}}"@endisset/>
                            <div class="input-group-append" data-target="#valid_to" data-toggle="datetimepicker">
                                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                            </div>
                        </div>
                        <span id="valid_to_error"></span>
                    </div>
                </div>

                <div class="col-md-3">
                    <label for="discount_type">Discount Type</label>
                    <div class="input-group-append">
                        <select class="custom-select" name="discount_type" id="discount_type">
                            <option value="percentage" {{ optional($coupon)->type == 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                            <option value="fixed" {{ optional($coupon)->type == 'fixed' ? 'selected' : '' }}>Fixed</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row mb-5">
                <div class="col-md-3" id="singleUse" @if($coupon->single_use_per_customer != 1 && $coupon->one_time_use_among_all == 1){{'hidden'}}@else{{''}}@endif>
                    <label for="single_use_per_customer">Single Use Per Customer <h5 class="small">(This type of coupon is used only once per customer)</h5></label>
                    <div class="input-group-append">
                        <select class="custom-select" name="single_use_per_customer" id="single_use_per_customer">
                            <option selected disabled>Select one</option>
                            @foreach(config('global_values.coupon_uses') as $key => $val)
                                <option value="{{$key}}" @if($coupon->single_use_per_customer == $key){{'selected'}}@else{{''}}@endif>{{$val}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3" id="oneTime" @if($coupon->one_time_use_among_all != 1 && $coupon->single_use_per_customer == 1 ){{'hidden'}}@else{{''}}@endif>
                    <label for="one_time_use_among_all">One time Use Among all Customers <h5 class="small">(This type coupon is used by only one customer amoung all customers)</h5></label>
                    <div class="input-group-append">
                        <select class="custom-select" name="one_time_use_among_all" id="one_time_use_among_all">
                            <option selected disabled>Select one</option>
                            @foreach(config('global_values.coupon_uses') as $key => $val)
                                <option value="{{$key}}" @if($coupon->one_time_use_among_all == $key){{'selected'}}@else{{''}}@endif>{{$val}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Update Coupons</button>
            <a href="{{ route('admin.coupon.coupons') }}" class="btn btn-danger">Cancel</a>
        </form>
    </div>
</div>
</div>
</div>
</section>

@push('scripts')
    <script src="{{asset('all_js/admin_js/coupons.js')}}"></script>
@endpush
@endsection