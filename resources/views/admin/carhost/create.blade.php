@extends('templates.admin')

@section('page-title')
CarHost
@if (session('success'))
        <div id="success-message" class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div id="success-message" class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
@endsection

@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Add CarHost</h3>
        </div>
        <form class="card-body" action="{{ route('admin.store-carhost') }}" id="carhost-form" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="carHostId" name="carHostId">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="firstname">First Name</label> <span class="text-danger">*</span>
                        <input type="text" class="form-control" id="firstname" name="firstname">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="lastname">Last Name</label> <span class="text-danger">*</span>
                        <input type="text" class="form-control" id="lastname" name="lastname">
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label for="email">Email</label> <span class="text-danger">*</span>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label for="dob">D.O.B.</label> <span class="text-danger">*</span>
                        <input type="date" class="form-control" id="dob" name="dob">
                    </div>
                </div>
            </div>
            <div class="row mb-5">
                <div class="col-md-3" id="singleUse">
                    <label for="mobile_number">Mobile Number</label> <span class="text-danger">*</span>
                    <input type="number" class="form-control" id="mobile_number" name="mobile_number">
                </div>
                <div class="col-md-3" id="singleUse">
                    <label for="pan_number">PAN Number</label>
                    <input type="text" class="form-control" id="pan_number" name="pan_number">
                </div>
                <div class="col-md-3" id="singleUse">
                    <label for="gst_number">GST Number</label>
                    <input type="text" class="form-control" id="gst_number" name="gst_number">
                </div>
                <div class="col-md-3" id="singleUse">
                    <label for="business_name">Business Name</label>
                    <input type="text" class="form-control" id="business_name" name="business_name">
                </div>
                <div class="col-md-3" id="oneTime">
                    <label for="profile_pic">Profile Picture</label>
                    <input type="file" class="form-control" id="profile_pic" name="profile_pic">
                </div>
            </div>
            <hr/>
            <h5><b>Bank Details</b></h5>
            <hr/>
            <div class="row mb-3">
                <div class="col-md-3">
                    <label for="account_holder_name">Account Holder Name</label>
                    <input type="text" class="form-control" id="account_holder_name" name="account_holder_name">
                </div>
                <div class="col-md-3">
                    <label for="bank_name">Bank Name</label>
                    <input type="text" class="form-control" id="bank_name" name="bank_name">
                </div>
                <div class="col-md-3">
                    <label for="branch_name">Branch Name</label>
                    <input type="text" class="form-control" id="branch_name" name="branch_name">
                </div>
                <div class="col-md-3">
                    <label for="city">City</label>
                    <input type="text" class="form-control" id="city" name="city">
                </div>
            </div>
            <div class="row mb-5">
                <div class="col-md-3">
                    <label for="account_number">Account Number</label> <span class="text-danger">*</span>
                    <input type="text" class="form-control" id="account_number" name="account_number">
                </div>
                <div class="col-md-3">
                    <label for="ifsc_code">IFSC Code</label> <span class="text-danger">*</span>
                    <input type="text" class="form-control" id="ifsc_code" name="ifsc_code">
                </div>
                <div class="col-md-3">
                    <label for="nick_name">Nick Name</label>
                    <input type="text" class="form-control" id="nick_name" name="nick_name">
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