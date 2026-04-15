@extends('templates.admin')

@section('page-title')
    Categories
@endsection

@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Vehicle Categories</h3>

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
                                        Vehicle Types
                                    </th>
                                    <th style="width: 20%">
                                        Name
                                    </th>
                                    <th style="width: 20%">
                                        Icon
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
                        <h3 class="card-title form-title">Add Vehicle Category</h3>
                    </div>
                    <form action="{{ route('submit.vehicle-category') }}" method="POST" enctype="multipart/form-data" id="categoryForm">
                        @csrf
                        <input type="hidden" id="cId" name="cId">
                        <div class="card-body">
                            <div class="form-group">
                                <label for="vehicleType">Vehicle Type</label>
                                <select class="form-control" id="vehicleType" name="vehicleType" required>
                                    <option value="">Select Vehicle Type</option>
                                    @foreach($vehicleTypes as $type)
                                        <option value="{{ $type->type_id }}">{{ $type->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Please select a vehicle type.</div>
                            </div>                                
                            <div class="form-group">
                                <label for="name">Name</label>
                                <input type="text" class="form-control" id="name" name="name" placeholder="Enter Name" required autocomplete="off">
                                <div class="invalid-feedback">Please enter a name.</div>
                            </div>
                            <div class="form-group">
                                <label for="icon">Icon</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="icon" name="icon" onchange="previewImage()" accept="image/*" required>
                                    <label class="custom-file-label" for="icon" id="logoLabel">Choose file</label>
                                    <div class="invalid-feedback">Please select a icon.</div>
                                </div>
                                <img src="" id="preview" style="max-width: 100px; max-height: 100px; margin-top: 10px; display: none;">
                                <div id="iconPreview">
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary btn-submit" name="submit">Submit</button>
                        </div>
                    </form>
                </div>
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
            function loadCategories() {
                $.ajax({
                    url: "{{ route('admin.get-all-vehicle-categories') }}",
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
                            <td>${type.category_id}</td>
                            <td>${type.get_vehicle_type.name}</td>
                            <td>${type.name}</td>
                            <td><img src="${type.icon}" alt="${type.name}" width="100" height="100"></td>
                            <td class="project-actions text-right">
                                <a class="btn btn-info btn-sm update-btn" data-operationId='${type.category_id}'>
                                    <i class="fas fa-pencil-alt">
                                    </i>
                                    Edit
                                </a>
                                <a class="btn btn-danger btn-sm delete-btn" data-operationId='${type.category_id}'>
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
                $('#category-form').attr('data-updateId', '');
                $('.form-title').text('Add Category');
                $('.btn-submit').text('Add');
                $('.btn-clear').addClass('d-none');
                $('#category-form')[0].reset();
            });

            $(document).on('click', ".update-btn", function() {
                let typeId = $(this).data('operationid');
                let loading = undefined;
                $.ajax({
                    url: "{{ route('admin.get-vehicle-category') }}",
                    type: "GET",
                    data: {
                        id: typeId
                    },
                    success: function(response) {
                        let category = response.data;
                        var iconImg = category.icon;
                        var iconHtml = '<img style="width: 250px; height: 175px;" src="' + iconImg + '" alt="Feature Icon" class="img-thumbnail m-2">';
                        $('#category-form').attr('data-updateId', category.category_id);
                        $('#name').val(category.name);
                        $('#vehicleType').val(category.vehicle_type_id);
                        $('#iconPreview').html(iconHtml);
                        $('#icon').removeAttr('required');

                        $('.form-title').text('Update Category');
                        $('.btn-submit').text('Update');
                        $('.btn-clear').removeClass('d-none');
                        $('#cId').val(category.category_id);
                    }
                });
            });
            loadCategories();

            $(document).on('click', ".delete-btn", function() {
                let typeId = $(this).data('operationid');
                let loading = undefined;
                // Confirmation dialog
                if (!confirm("Are you sure you want to delete this item?")) {
                    return; // Do nothing if the user cancels
                }
                $.ajax({
                    url: "{{ route('admin.delete-vehicle-category') }}",
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
                        loadCategories();
                    }
                });
            });
        });

        function previewImage() {
            var preview = document.querySelector('#preview');
            var file = document.querySelector('input[type=file]').files[0];
            var reader = new FileReader();

            reader.onloadend = function() {
                preview.src = reader.result;
                preview.style.display = 'block';
            }

            if (file) {
                reader.readAsDataURL(file);
            } else {
                preview.src = '';
                preview.style.display = null;
            }
        }

    </script>
@endpush
