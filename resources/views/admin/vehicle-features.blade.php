@extends('templates.admin')

@push('styles')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.11/cropper.min.css" rel="stylesheet">
@endpush

@section('page-title')
    Vechicle Features
@endsection

@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-9">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">All Features</h3>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body">
                            <table id="vehicle-feature" class="table table-bordered table-striped table-responsive">
                                <thead>
                                    <tr>
                                        <th>Id</th>
                                        <th>Feature Name</th>
                                        <th>Icon</th>
                                        <th>Created</th>
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
                            <h3 class="card-title form-title">Add Feature</h3>
                        </div>
                        <form action="{{ route('submit.feature') }}" method="POST" enctype="multipart/form-data" id="feature-form">
                            @csrf
                            <input type="hidden" id="fId" name="fId">
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="featureName">Feature Name</label>
                                    <input type="text" class="form-control" id="featureName" name="featureName" placeholder="Enter feature name" required autocomplete="off">
                                    <div class="invalid-feedback">Please enter a feature name.</div>
                                </div>
                                <div class="form-group">
                                    <label for="featureIcon">Feature Logo</label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="featureIcon" name="featureIcon" onchange="previewImage()" accept="image/*" required>
                                        <label class="custom-file-label" for="featureIcon" id="logoLabel">Choose file</label>
                                        <div class="invalid-feedback">Please select a feature icon.</div>
                                    </div>
                                    <img src="" id="preview" style="max-width: 100px; max-height: 100px; margin-top: 10px; display: none;">
                                    <div id="featureIconPreview">
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
            function loadFeatures() {
                $('#featureIcon').prop('required', true);
                $.ajax({
                    url: "{{ route('admin.get-all-feature') }}",
                    type: "GET",
                    beforeSend: function() {
                        $('.table-data').html(
                            '<tr><td colspan="5" class="text-center">Loading...</td></tr>');
                    },
                    success: function(response) {
                        let feature = response.data;
                        let html = '';
                        feature.forEach((item, index) => {
                            html += `<tr>
                            <td>${index + 1}</td>
                            <td>${item.name}</td>
                            <td><img src="${item.icon}" alt="${item.name}" width="100" height="100"></td>
                            <td>${new Date(item.created_at).toDateString()}</td>
                            <td class="project-actions text-right">
                                <a class="btn btn-info btn-sm update-btn" data-operationId='${item.feature_id}'>
                                    <i class="fas fa-pencil-alt">
                                    </i>
                                    Edit
                                </a>
                                <a class="btn btn-danger btn-sm delete-btn" data-operationId='${item.feature_id}'>
                                    <i class="fas fa-trash">
                                    </i>
                                    Delete
                                </a>
                            </td>
                        </tr>`;
                        });

                        $('.table-data').html(html);
                        $("#vehicle-feature").DataTable({
                            "responsive": true,
                            "lengthChange": false,
                            "autoWidth": false,
                            "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
                        }).buttons().container().appendTo('#vehicle-feature_wrapper .col-md-6:eq(0)');
                    }
                });
            }
            loadFeatures();

            $(document).on('click', ".update-btn", function() {
                let featureId = $(this).data('operationid');
                let loading = undefined;
                $.ajax({
                    url: "{{ route('admin.get-vehicle-features') }}",
                    type: "GET",
                    data: {
                        id: featureId
                    },
                    success: function(response) {
                        let feature = response.data;
                        var iconImg = feature.icon;
                        var iconHtml = '<img style="width: 250px; height: 175px;" src="' + iconImg + '" alt="Feature Icon" class="img-thumbnail m-2">';
                        $('#feature-form').attr('data-updateId', feature.feature_id);
                        $('#featureName').val(feature.name);
                        $('#featureIconPreview').html(iconHtml);
                        $('#featureIcon').removeAttr('required');
                        $('.form-title').text('Update Vehicle Feature');
                        $('.btn-submit').text('Update');
                        $('.btn-clear').removeClass('d-none');
                        $('#fId').val(feature.feature_id);
                    }
                });
            });

            $(document).on('click', ".delete-btn", function() {
                let featureId = $(this).data('operationid');
                let loading = undefined;
                $.ajax({
                    url: "{{ route('admin.delete-features') }}",
                    type: "DELETE",
                    data: {
                        id: featureId,
                        _token: "{{ csrf_token() }}",
                    },
                    success: function(response) {
                        Toast.open({
                            type: 'success',
                            message: response.message,
                            background: 'green',
                            duration: 3000,
                        });
                        loadFeatures();
                    }
                });
            });
        });
    </script>

    <script>
        // Add event listener to file input
        document.getElementById('manufacturerLogo').addEventListener('change', function(e) {
            var fileName = e.target.files[0].name;
            // Update label text with selected file name
            document.getElementById('logoLabel').innerText = fileName;
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

<script>
    // Check if there's a success message in the session and display toast notification if it exists
    @if(session('success'))
        // Initialize Notyf
        const notyf = new Notyf({
            position: {
                x: 'center',
                y: 'top',
            }
        });

        // Show toast notification with success message
        notyf.success('{{ session('success') }}');
    @endif

    // Check if there are validation errors in the session and display toast notification if they exist
    @if ($errors->any())
        // Initialize Notyf
        const notyf = new Notyf({
            position: {
                x: 'center',
                y: 'top',
            }
        });

        // Iterate over each validation error and show a toast notification for each
        @foreach ($errors->all() as $error)
            notyf.error('{{ $error }}');
        @endforeach
    @endif
</script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.11/cropper.min.js"></script>
@endpush
