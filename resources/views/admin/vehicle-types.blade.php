@extends('templates.admin')

@section('page-title')
    Vehicle Types
@endsection

@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">All Types</h3>

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
                        <table class="table table-striped projects">
                            <thead>
                                <tr>
                                    <th style="width: 1%">
                                        Id
                                    </th>
                                    <th style="width: 30%">
                                        Name
                                    </th>
                                    <th style="width: 30%">
                                        Convenience Fees
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
                        <h3 class="card-title form-title">Add Vehicle Types</h3>

                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <form class="card-body" id="branch-form">
                        @csrf
                        <input type="hidden" id="typeStoreId" name="typeStoreId">
                        <div class="form-group">
                            <label for="vehicle_types">Name</label>
                            <input type="text" id="name" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="c_fees">Convenience Fees</label>
                            <input type="text" id="c_fees" name="c_fees" class="form-control" required>
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
        function loadBranches() {
                $.ajax({
                    url: "{{ route('admin.get-all-vehicle-types') }}",
                    type: "GET",
                    beforeSend: function() {
                        $('.table-data').html(
                            '<tr><td colspan="9" class="text-center">Loading...</td></tr>');
                    },
                    success: function(response) {
                        let types = response.data;
                        let html = '';
                        types.forEach(type => {
                            html += `<tr>
                            <td>${type.type_id}</td>
                            <td>${type.name}</td>
                            <td>${type.convenience_fees}</td>
                            <td class="project-actions text-right">
                                <a class="btn btn-info btn-sm update-btn" data-operationId='${type.type_id}'>
                                    <i class="fas fa-pencil-alt">
                                    </i>
                                    Edit
                                </a>
                                <a class="btn btn-danger btn-sm delete-btn" data-operationId='${type.type_id}'>
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

        $(document).ready(function() {
            

            $('.btn-clear').click(function() {
                $('#branch-form').attr('data-updateId', '');
                $('.form-title').text('Add Vehicle Types');
                $('.btn-submit').text('Add');
                $('.btn-clear').addClass('d-none');
                $('#branch-form')[0].reset();
            });

            $(document).on('click', ".update-btn", function() {
                let typeId = $(this).data('operationid');
                $('#typeStoreId').val(typeId);
                let loading = undefined;
                $.ajax({
                    url: "{{ route('admin.get-vehicle-types') }}",
                    type: "GET",
                    data: {
                        id: typeId
                    },
                    success: function(response) {
                        let type = response.data;
                        $('#branch-form').attr('data-updateId', type.type_id);
                        $('#name').val(type.name);
                        $('#c_fees').val(type.convenience_fees);
                        $('.form-title').text('Update Vehicle Types');
                        $('.btn-submit').text('Update');
                        $('.btn-clear').removeClass('d-none');
                    }
                });
            });

            $(document).on('click', ".delete-btn", function() {
                let typeId = $(this).data('operationid');
                let loading = undefined;
                
                // Confirmation dialog
                if (!confirm("Are you sure you want to delete this item?")) {
                    return; // Do nothing if the user cancels
                }
                
                $.ajax({
                    url: "{{ route('admin.delete-types') }}",
                    type: "DELETE",
                    data: {
                        id: typeId,
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

            loadBranches();
        });

        jQuery.validator.addMethod("checkName", function(value, element, params) 
        {
            var isValid = false; 
            var typeStoreId = $('#typeStoreId').val();
            $.ajax({
                url: "{{ route('admin.check.vehicle.types') }}",
                method:"POST",
                async: false,
                cache: false,
                data: {
                    "_token":  "{{ csrf_token() }}",
                    "value":value,
                    "id":typeStoreId,
                },
                success: function(response) {
                   if (response == "1") 
                      {isValid = true; }
                },
            });
           return isValid;
        }, jQuery.validator.format("Name already exist"));

        $('#branch-form').validate({ 
           rules: {
              name: {required: true, checkName: true},
              c_fees: {required: true, number:true},
           },
           messages :{
                name : { required : 'Please enter Name' },
                c_fees : { required : 'Please enter Conveniese Fees' },
            },
            highlight: function (element) {
                //console.log(element, element.type, element.tagName)
                if ($(element).is('select') || $(element).is('input')) {
                    $(element).parent('.select-wrap').addClass('error');
                } else {
                    $(element).addClass('error');
                }
            },
            submitHandler: function (form) {
                let loading = undefined;
                let url = "{{ route('admin.create-types') }}";
                let method = "POST";
                let typeId = $('#branch-form').attr('data-updateId');
                let name = $('#name').val().trim(); // Get and trim the name field value
                let c_fees = $('#c_fees').val().trim(); // Get and trim the name field value
                if (typeId) {
                    url = "{{ route('admin.update-types') }}";
                    method = "PUT";
                }
                // Validation for the name field
                /*if (name === '') {
                    Toast.open({
                        type: 'error',
                        message: 'Name and Convenience fees both are Mandatory',
                        background: 'red',
                        duration: 3000,
                    });
                    return;
                }*/
                let data = {
                    name: name, // Use the trimmed name value
                    c_fees: c_fees, 
                    _token: "{{ csrf_token() }}",
                };
                if (typeId) {
                    data.id = typeId;
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
                        $('.form-title').text('Add Vehicle Types');
                        $('.btn-submit').text('Add');
                        $('.btn-clear').addClass('d-none');
                    }
                });
                //form.submit();
            }
        });
    </script>
@endpush
