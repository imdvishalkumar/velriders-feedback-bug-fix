@extends('templates.admin')

@section('page-title')
Customer Document
@endsection

@section('content')
    <!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div id="error-container">
                @if (session('success'))
                    <div id="success-message" class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @elseif(session('error'))
                    <div id="error-message" class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif
            </div>

            <div class="card">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">GOVT ID. Doc.</h3>
                    </div>
                    <form class="card-body" action="{{ route('admin.store-document', ['type' => 'govt']) }}" id="govt-form" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="doc_type" value="govtid">
                        <div class="row mt-3 mb-3">
                            <div class="col-md-3">
                                <label for="dl_cid">Select Document Type</label> <span class="text-danger h4">*</span>
                                <select id="govtid_type" name="govtid_type" class="form-control">
                                    <option selected disabled>- Select Document Type -</option>
                                    @php $govtTypes = config('global_values.govt_types'); @endphp
                                    @if(is_countable($govtTypes) && count($govtTypes) > 0)
                                        @foreach($govtTypes as $k => $v)
                                            <option value="{{$k}}">{{$v}}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                             <div class="col-md-3">
                                <label for="dl_cid">Select Customer</label> <span class="text-danger h4">*</span>
                                <select id="govtid_cid" name="govtid_cid" class="form-control">
                                    <option selected disabled>- Select Customer -</option>
                                    @if(is_countable($customers) && count($customers) > 0)
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->customer_id }}">{{ $customer->firstname }} {{ $customer->lastname }} ({{ $customer->email }} - {{ $customer->mobile_number }})</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="code">Document Number</label> <span class="text-danger h4">*</span>
                                    <input type="text" class="form-control" id="gtdoc_number" name="gtdoc_number">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="doc_front_img">Document Front Image</label> <span class="text-danger h4">*</span>
                                    <input type="file" class="form-control" id="gtdoc_front_img" name="gtdoc_front_img">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="doc_back_img">Document Back Image</label> <span class="text-danger h4">*</span>
                                    <input type="file" class="form-control" id="gtdoc_back_img" name="gtdoc_back_img">
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Add</button>
                        <a href="{{ route('admin.customer_documents.index') }}" class="btn btn-danger">Cancel</a>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Driving License Doc.</h3>
                    </div>
                    <form class="card-body" action="{{ route('admin.store-document', ['type' => 'dl']) }}" id="dl-form" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="doc_type" value="dl">
                        <div class="row mb-3">
                            <div class="col-md-3 mt-2">
                                <div class="form-group">
                                    <label for="dl_cid">Select Customer</label> <span class="text-danger h4">*</span>
                                    <select id="dl_cid" name="dl_cid" class="form-control">
                                        <option selected disabled>- Select Customer -</option>
                                        @if(is_countable($customers) && count($customers) > 0)
                                            @foreach($customers as $customer)
                                                <option value="{{ $customer->customer_id }}">{{ $customer->firstname }} {{ $customer->lastname }} ({{ $customer->email }} - {{ $customer->mobile_number }})</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="code">Document Number</label> <span class="text-danger h4">*</span>
                                    <input type="text" class="form-control" id="dldoc_number" name="dldoc_number">
                                </div>
                            </div>
                            <div class="col-md-2 ml-3">
                                <div class="form-group">
                                    <label for="code">Which License do you have ?</label> <span class="text-danger h4">*</span>
                                    <div class="form-check">
                                      <input class="form-check-input" type="checkbox" value="car" id="car" name="license[]">
                                      <label class="form-check-label" for="car">Car</label>
                                    </div>
                                    <div class="form-check">
                                      <input class="form-check-input" type="checkbox" value="bike" id="bike" name="license[]">
                                      <label class="form-check-label" for="bike">Bike</label>
                                    </div>
                                    <span id="license_error"></span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="doc_front_img">Document Front Image</label> <span class="text-danger h4">*</span>
                                    <input type="file" class="form-control" id="dldoc_front_img" name="dldoc_front_img">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="doc_back_img">Document Back Image</label> <span class="text-danger h4">*</span>
                                    <input type="file" class="form-control" id="dldoc_back_img" name="dldoc_back_img">
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Add</button>
                        <a href="{{ route('admin.customer_documents.index') }}" class="btn btn-danger">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

@push('scripts')
    <script src="{{asset('all_js/admin_js/customers.js')}}"></script>
@endpush
@endsection