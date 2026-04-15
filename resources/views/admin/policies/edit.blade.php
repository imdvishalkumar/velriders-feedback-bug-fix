@extends('templates.admin')

@section('page-title')
Policy
@endsection

@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Edit Policy</h3>
        </div>
        <form class="card-body" action="{{ route('admin.update-policy', ['id' => $policy->policy_id]) }}" id="policy-form" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="policy_title">Policy Title</label>
                        <input type="text" class="form-control" id="policy_title" name="policy_title" value="{{ $policy->title }}" required>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="policy_content">Policy Content</label>
                        <textarea rows="5" class="form-control" id="policy_content" class="cms_ckeditor" name="policy_content">
                            @isset($policy->content)
                            {!! $policy->content !!}
                            @endisset
                        </textarea>
                    </div>
                </div>
                <div id="error-policy_content"></div>
            </div>
            <button type="submit" class="btn btn-primary" onclick="return validatePolicy();">Update Policy</button>
            <a href="{{route('admin.policies')}}" class="btn btn-danger">Cancel</a>
        </form>
    </div>
</div>
</div>
</div>
</section>

@push('scripts')
    <script src="{{asset('all_js/admin_js/policies.js')}}"></script>
@endpush
@endsection