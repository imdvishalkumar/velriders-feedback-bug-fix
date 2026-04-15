@extends('templates.admin')

@section('page-title')

@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Edit CarHost</h3>
        </div>
        <form class="card-body" action="{{ route('admin.carhost.update', $carHost->id) }}" id="carhost-form" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="carHostId" name="carHostId" value="{{$carHost->id}}">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="firstname">First Name</label> <span class="text-danger">*</span>
                        <input type="text" class="form-control" id="firstname" name="firstname" @if(isset($carHost->firstname))value="{{$carHost->firstname}}"@else value=""@endif>
                    </div>
                    @error('firstname')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="lastname">Last Name</label> <span class="text-danger">*</span>
                        <input type="text" class="form-control" id="lastname" name="lastname" @if(isset($carHost->lastname))value="{{$carHost->lastname}}"@else value=""@endif>
                    </div>
                    @error('lastname')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label for="email">Email</label> <span class="text-danger">*</span>
                        <input type="email" class="form-control" id="email" name="email" @if(isset($carHost->email))value="{{$carHost->email}}"@else value=""@endif>
                    </div>
                    @error('email')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label for="dob">D.O.B.</label> <span class="text-danger">*</span>
                        <input type="date" class="form-control" id="dob" name="dob" @if(!empty($carHost->dob)) value="{{ \Carbon\Carbon::parse($carHost->dob)->format('Y-m-d') }}"@else value="" @endif>
                    </div>
                    @error('dob')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="row mb-5">
                <div class="col-md-3">
                    <label for="mobile_number">Mobile Number</label> <span class="text-danger">*</span>
                    <input type="number" class="form-control" id="mobile_number" name="mobile_number" @if(isset($carHost->mobile_number))value="{{$carHost->mobile_number}}"@else value=""@endif>
                    @error('mobile_number')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label for="pan_number">PAN Number</label>
                    <input type="text" class="form-control" id="pan_number" name="pan_number" @if(isset($carHost->pan_number))value="{{$carHost->pan_number}}"@else value=""@endif>
                    @error('pan_number')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label for="gst_number">GST Number</label>
                    <input type="text" class="form-control" id="gst_number" name="gst_number" @if(isset($carHost->gst_number))value="{{$carHost->gst_number}}"@else value=""@endif>
                    @error('gst_number')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label for="business_name">Business Name</label>
                    <input type="text" class="form-control" id="business_name" name="business_name" @if(isset($carHost->business_name))value="{{$carHost->business_name}}"@else value=""@endif>
                    @error('business_name')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label for="profile_pic">Profile Picture</label>
                    <input type="file" class="form-control" id="profile_pic" name="profile_pic">
                    @if(isset($carHost->profile_picture_url))
                    <div class="image-display">
                        <img src="{{ $carHost->profile_picture_url }}" alt="Profile Picture Image" style="width: 250px; height: 175px; border: 1px solid #ccc; border-radius: 5px; padding: 5px;" class="img-thumbnail m-2">
                    </div>
                    @else
                    <img src="{{asset('images/noimg.png')}}" style="width: 250px; height: 175px;" alt="No Image" class="img-thumbnail m-2">
                    @endif
                </div>
            </div>
            <h5><b>Bank Details</b></h5>
            <hr/>
            <div class="row mb-3">
                <div class="col-md-3">
                    <label for="account_holder_name">Account Holder Name</label>
                    <input type="text" class="form-control" id="account_holder_name" name="account_holder_name" @if(isset($carHostBank->account_holder_name))value="{{$carHostBank->account_holder_name}}"@else value=""@endif>
                </div>
                <div class="col-md-3">
                    <label for="bank_name">Bank Name</label>
                    <input type="text" class="form-control" id="bank_name" name="bank_name" @if(isset($carHostBank->bank_name))value="{{$carHostBank->bank_name}}"@else value=""@endif>
                </div>
                <div class="col-md-3">
                    <label for="branch_name">Branch Name</label>
                    <input type="text" class="form-control" id="branch_name" name="branch_name" @if(isset($carHostBank->branch_name))value="{{$carHostBank->branch_name}}"@else value=""@endif>
                </div>
                <div class="col-md-3">
                    <label for="city">City</label>
                    <input type="text" class="form-control" id="city" name="city" @if(isset($carHostBank->city))value="{{$carHostBank->city}}"@else value=""@endif>
                </div>
            </div>
            <div class="row mb-5">
                <div class="col-md-3">
                    <label for="account_number">Account Number</label> <span class="text-danger">*</span>
                    <input type="text" class="form-control" id="account_number" name="account_number" @if(isset($carHostBank->account_no))value="{{$carHostBank->account_no}}"@else value=""@endif>
                </div>
                <div class="col-md-3">
                    <label for="ifsc_code">IFSC Code</label> <span class="text-danger">*</span>
                    <input type="text" class="form-control" id="ifsc_code" name="ifsc_code" @if(isset($carHostBank->ifsc_code))value="{{$carHostBank->ifsc_code}}"@else value=""@endif>
                </div>
                <div class="col-md-3">
                    <label for="nick_name">Nick Name</label>
                    <input type="text" class="form-control" id="nick_name" name="nick_name" @if(isset($carHostBank->nick_name))value="{{$carHostBank->nick_name}}"@else value=""@endif>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Add Carhost</button>
            <a href="{{ route('admin.carhost-mgt') }}" class="btn btn-danger">Cancel</a>
        </form>
    </div>
</div>
</div>
</div>
</section>

@push('scripts')
    <script src="{{asset('all_js/admin_js/carhost.js')}}"></script>
@endpush
@endsection