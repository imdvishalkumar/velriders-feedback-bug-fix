@extends('templates.admin')

@section('page-title')
Admins
@endsection

@section('content')
<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Admins</h3>

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
                                <th style="width: 20%">
                                    Username
                                </th>
                                <th style="width: 30%">
                                    Role
                                </th>
                                <th>
                                    Created at
                                </th>
                                <th style="width: 20%">
                                </th>
                            </tr>
                        </thead>
                        <tbody class="table-data">
                        </tbody>
                    </table>
                </div>
                <!-- /.card-body -->
            </div>
            {{-- <div class="card">
                <div class="card-header">
                    <h3 class="card-title">DataTable with default features</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <table id="example1" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Id</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Created</th>
                                <th>Operations</th>
                            </tr>
                        </thead>
                        <tbody class="table-data">
                        </tbody>
                    </table>
                </div>
                <!-- /.card-body -->
            </div> --}}
        </div>
        <div class="col-md-4">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title form-title">Add User</h3>

                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <form class="card-body" id="user-form">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="role">Role</label>
                        @php
                            $roles = getRoles();
                        @endphp
                        <select id="role" name="role" class="form-control custom-select">
                            <option selected disabled>Select one</option>
                            @foreach($roles as $key => $val)
                                <option value="{{$val->id}}">{{$val->name}}</option>
                            @endforeach
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
    $(document).ready(function () {
        function loadAdmins() {
            $.ajax({
                url: "{{ route('admin.get-all-users') }}",
                type: "GET",
                beforeSend: function () {
                    $('.table-data').html(
                        '<tr><td colspan="5" class="text-center">Loading...</td></tr>');
                },
                success: function (response) {
                    let admins = response.data;
                    let html = '';
                    admins.forEach(admin => {
                        html += `
                                <tr>
                                    <td>
                                        ${admin.id}
                                    </td>
                                    <td>
                                        <a>
                                            ${admin.username}
                                        </a>
                                    </td>
                                    <td>
                                        ${admin.rolename}
                                    </td>
                                    <td class="project_progress">
                                        ${new Date(admin.created_at).toDateString()}
                                    </td>
                                    <td class="project-actions text-right">
                                        <a class="btn btn-info btn-sm mb-3 update-btn" data-updateId='${admin.id}'>
                                            <i class="fas fa-pencil-alt">
                                            </i>
                                            Edit
                                        </a>
                                        <a class="btn btn-danger btn-sm delete-btn" data-deleteId='${admin.id}'>
                                            <i class="fas fa-trash">
                                            </i>
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                            `;
                    });
                    $('.table-data').html(html);
                }
            });
        }

        $('.btn-clear').click(function () {
            $('#username').val('');
            $('#role').val('');
            $('.form-title').text('Add User');
            $('.btn-submit').text('Add');
            $('label[for="password"]').removeClass('d-none');
            $('#password').removeClass('d-none');
            $('.btn-clear').addClass('d-none');
        });

        $(document).on('click', ".update-btn", function () {
            let adminId = $(this).data('updateid');
            let loading = undefined;
            $.ajax({
                url: "{{ route('admin.get-user') }}",
                type: "GET",
                data: {
                    id: adminId
                },
                beforeSend: function () {
                    loding = Toast.open({
                        type: 'info',
                        message: 'Loading...',
                        background: 'blue',
                        duration: 0,
                    });
                },
                success: function (response) {
                    Toast.dismiss(loding);
                    let admin = response.data;
                    $('#username').val(admin.username);
                    $('#role').val(admin.role);
                    $('#user-form').attr('data-updateId', admin.admin_id);
                    $('.form-title').text('Update User');
                    $('.btn-submit').text('Update');
                    $('label[for="password"]').addClass('d-none');
                    $('#password').addClass('d-none');
                    $('.btn-clear').removeClass('d-none');
                }
            });
        });

        $(document).on('click', ".delete-btn", function () {
            let adminId = $(this).data('deleteid');
            let loading = undefined;
            $.ajax({
                url: "{{ route('admin.delete-user') }}",
                type: "DELETE",
                data: {
                    id: adminId,
                    _token: "{{ csrf_token() }}",
                },
                beforeSend: function () {
                    loding = Toast.open({
                        type: 'info',
                        message: 'Loading...',
                        background: 'blue',
                        duration: 0,
                    });
                },
                success: function (response) {
                    Toast.dismiss(loding);
                    Toast.open({
                        type: 'success',
                        message: response.message,
                        background: 'green',
                        duration: 3000,
                    });
                    loadAdmins();
                }
            });
        });

        $('#user-form').submit(function (e) {
            e.preventDefault();
            let loading = undefined;
            let username = $('#username').val().trim();
            let password = $('#password').val().trim();
            let role = $('#role').val();
            let adminId = $('#user-form').attr('data-updateId');
            let url = "{{ route('admin.add-user') }}";
            let method = "POST";
            
            // Validation
            if (username === '') {
                Toast.open({
                    type: 'error',
                    message: 'Please enter a username',
                    background: 'red',
                    duration: 3000,
                });
                return;
            }

            /*if (password === '') {
                Toast.open({
                    type: 'error',
                    message: 'Please enter a password',
                    background: 'red',
                    duration: 3000,
                });
                return;
            }*/

            if (!role) {
                Toast.open({
                    type: 'error',
                    message: 'Please select a role',
                    background: 'red',
                    duration: 3000,
                });
                return;
            }

            if (adminId) {
                url = "{{ route('admin.update-user') }}";
                method = "PUT";
            }

            $.ajax({
                url: url,
                type: method,
                data: {
                    username: username,
                    password: password,
                    role: role,
                    id: adminId,
                    _token: "{{ csrf_token() }}",
                },
                beforeSend: function () {
                    loading = Toast.open({
                        type: 'info',
                        message: 'Loading...',
                        background: 'blue',
                        duration: 0,
                    });
                },
                success: function (response) {
                    Toast.dismiss(loading);
                    Toast.open({
                        type: 'success',
                        message: response.message,
                        background: 'green',
                        duration: 3000,
                    });
                    $("#user-form").attr('data-updateId', '');
                    $('#username').val('');
                    $('#role').val('');
                    $("#password").val('');
                    $("label[for='password']").removeClass('d-none');
                    $("#password").removeClass('d-none');
                    $('.form-title').text('Add User');
                    $('.btn-submit').text('Add');
                    $('.btn-clear').addClass('d-none');
                    loadAdmins();
                }
            });
        });

        loadAdmins();
    });
</script>
@endpush