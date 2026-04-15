@extends('templates.admin')

@section('page-title')
    Branches
@endsection

@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">All Branches</h3>

                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                            <button type="button" class="btn btn-tool" data-card-widget="remove" title="Remove">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped projects table-responsive">
                            <thead>
                                <tr>
                                    <th style="width: 1%">
                                        Id
                                    </th>
                                    <th style="width: 30%">
                                        Branch Name
                                    </th>
                                    <th style="width: 30%">
                                        City Name
                                    </th>
                                    <th>
                                        Manager
                                    </th>
                                    <th>
                                        Address
                                    </th>
                                    <th>
                                        Phone
                                    </th>
                                    <th>
                                        Email
                                    </th>
                                    <th>
                                        Openining Hours
                                    </th>
                                    <th>
                                        Operations
                                    </th><th>
                                        Is Head Branch
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="table-data">
                            </tbody>
                        </table>
                    </div>
                    <!-- /.card-body -->
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title form-title">Add Branch</h3>

                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <form class="card-body" id="branch-form">
                        @csrf
                        <div class="form-group">
                            <label for="branch_name">Branch Name</label>
                            <input type="text" id="branch_name" name="branch_name" class="form-control" required>
                        </div>

                         <div class="form-group">
                            <label for="city_name">City Name</label>
                            <select id="city_name" name="city_name" class="form-control"> 
                                <option value="">Select City Type</option>
                                @foreach($cities as $city)
                                    <option value="{{ $city->id }}">{{ $city->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="manager_name">Manager Name</label>
                            <input type="text" id="manager_name" name="manager_name" class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="address">Address</label>
                            <input type="text" id="address" name="address" class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="text" id="phone" name="phone" class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control">
                        </div>

                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <label for="lat">Lat</label>
                                    <input type="text" id="lat" name="lat" class="form-control">
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label for="long">Long</label>
                                    <input type="text" id="long" name="long" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="opening_time">Opening Hours</label>
                            <input type="text" id="opening_time" name="opening_time" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="is_head_branch">Is Head Branch</label>
                            <select id="is_head_branch" name="is_head_branch" class="form-control"> 
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary btn-submit btn-sm" href="#">
                            Add
                        </button>
                        <button type="button" class="btn d-none btn-danger btn-clear btn-sm" href="#">
                            Cancel
                        </button>
                    </form>
                </div>
                <!-- /.card -->
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        const Toast = new Notyf({
            position: {
                x: 'center',
                y: 'top',
            }
        });
        $(document).ready(function() {
            function loadBranches() {
                $.ajax({
                    url: "{{ route('admin.get-all-branches') }}",
                    type: "GET",
                    beforeSend: function() {
                        $('.table-data').html(
                            '<tr><td colspan="9" class="text-center">Loading...</td></tr>');
                    },
                    success: function(response) {
                        let branches = response.data;
                        let html = '';
                        var cityName = '';
                        var isHeadBranch = 'No';
                        branches.forEach(branch => {
                            if(branch.city){
                                cityName = branch.city.name;
                            }
                            if(branch.is_head_branch === 1){
                                isHeadBranch = 'Yes';
                            }else{
                                isHeadBranch = 'No';
                            }

                            html += `<tr>
                            <td>${branch.branch_id}</td>
                            <td>${branch.name}</td>
                            <td>${cityName}</td>
                            <td>${branch.manager_name}</td>
                            <td>${branch.address}</td>
                            <td>${branch.phone}</td>
                            <td>${branch.email}</td>
                            <td>${branch.opening_hours}</td>
                            <td>${isHeadBranch}</td>
                            <td class="project-actions text-right">
                                <a class="btn btn-info btn-sm update-btn" data-operationId='${branch.branch_id}'>
                                    <i class="fas fa-pencil-alt">
                                    </i>
                                    Edit
                                </a>
                                <a class="btn btn-danger btn-sm delete-btn" data-operationId='${branch.branch_id}'>
                                    <i class="fas fa-trash">
                                    </i>
                                    Delete
                                </a>
                            </td>
                        </tr>`;
                        });
                        $('.table-data').html(html);
                    }
                });
            }

            $('.btn-clear').click(function() {
                $('#branch-form').attr('data-updateId', '');
                $('.form-title').text('Add Branch');
                $('.btn-submit').text('Add');
                $('.btn-clear').addClass('d-none');
                $('#branch-form')[0].reset();
            });

            $(document).on('click', ".update-btn", function() {
                let branchId = $(this).data('operationid');
                let loading = undefined;
                $.ajax({
                    url: "{{ route('admin.get-branch') }}",
                    type: "GET",
                    data: {
                        id: branchId
                    },
                    success: function(response) {
                        let branch = response.data;
                        $('#branch-form').attr('data-updateId', branch.branch_id);
                        $('#branch_name').val(branch.name);
                        $('#city_name').val(branch.city_id);
                        $('#manager_name').val(branch.manager_name);
                        $('#address').val(branch.address);
                        $('#phone').val(branch.phone);
                        $('#email').val(branch.email);
                        $('#lat').val(branch.latitude);
                        $('#long').val(branch.longitude);
                        $('#opening_time').val(branch.opening_hours);
                        $('#is_head_branch').val(branch.is_head_branch);
                        $('.form-title').text('Update Branch');
                        $('.btn-submit').text('Update');
                        $('.btn-clear').removeClass('d-none');
                    }
                });
            });

            $(document).on('click', ".delete-btn", function() {
                let branchId = $(this).data('operationid');
                let loading = undefined;
                $.ajax({
                    url: "{{ route('admin.delete-branch') }}",
                    type: "DELETE",
                    data: {
                        id: branchId,
                        _token: "{{ csrf_token() }}",
                    },
                    success: function(response) {
                        Toast.open({
                            type: 'success',
                            message: response.message,
                            background: 'green',
                            duration: 3000,
                        });
                        loadBranches();
                    }
                });
            });

            $('#branch-form').submit(function(e) {
                e.preventDefault();
                let loading = undefined;
                let url = "{{ route('admin.add-branch') }}";
                let method = "POST";
                let branchId = $('#branch-form').attr('data-updateId');
                if (branchId) {
                    url = "{{ route('admin.update-branch') }}";
                    method = "PUT";
                }
                let data = {
                    name: $('#branch_name').val(),
                    city_name: $('#city_name').val(),
                    manager_name: $('#manager_name').val(),
                    address: $('#address').val(),
                    phone: $('#phone').val(),
                    email: $('#email').val(),
                    latitude: $('#lat').val(),
                    longitude: $('#long').val(),
                    opening_hours: $('#opening_time').val(),
                    is_head_branch: $('#is_head_branch').val(),
                    _token: "{{ csrf_token() }}",
                };
                if (branchId) {
                    data.id = branchId;
                }
                $.ajax({
                    url: url,
                    type: method,
                    data: data,
                    success: function(response) {
                        Toast.open({
                            type: 'success',
                            message: response.message,
                            background: 'green',
                            duration: 3000,
                        });
                        loadBranches();
                        $('#branch-form')[0].reset();
                        $('#branch-form').attr('data-updateId', '');
                        $('.form-title').text('Add Branch');
                        $('.btn-submit').text('Add');
                        $('.btn-clear').addClass('d-none');
                    }
                });
            });

            loadBranches();
        });
    </script>
@endpush
