@extends('templates.admin')

@section('page-title')
    Cities
@endsection

@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">All Cities</h3>

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
                                        City Name
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
                        <h3 class="card-title form-title">Add City</h3>

                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <form class="card-body" id="city-form">
                        @csrf
                        <div class="form-group">
                            <label for="city_name">City Name</label>
                            <input type="text" id="city_name" name="city_name" class="form-control" required>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <label for="lat">Lat</label>
                                    <input type="text" id="lat" name="lat" class="form-control" required>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label for="long">Long</label>
                                    <input type="text" id="long" name="long" class="form-control" required>
                                </div>
                            </div>
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
            function loadCities() {
                $.ajax({
                    url: "{{ route('admin.get-all-cities') }}",
                    type: "GET",
                    beforeSend: function() {
                        $('.table-data').html(
                            '<tr><td colspan="9" class="text-center">Loading...</td></tr>');
                    },
                    success: function(response) {
                        let cities = response.data;
                        let html = '';
                        cities.forEach(city => {
                            html += `<tr>
                            <td>${city.id}</td>
                            <td>${city.name}</td>
                            <td class="project-actions text-right">
                                <a class="btn btn-info btn-sm update-btn" data-operationId='${city.id}'>
                                    <i class="fas fa-pencil-alt">
                                    </i>
                                    Edit
                                </a>
                                <a class="btn btn-danger btn-sm delete-btn" data-operationId='${city.id}'>
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
                $('#city-form').attr('data-updateId', '');
                $('.form-title').text('Add City');
                $('.btn-submit').text('Add');
                $('.btn-clear').addClass('d-none');
                $('#city-form')[0].reset();
            });

            $(document).on('click', ".update-btn", function() {
                let cityId = $(this).data('operationid');
                let loading = undefined;
                console.log(cityId);
                $.ajax({
                    url: "{{ route('admin.get-city') }}",
                    type: "GET",
                    data: {
                        id: cityId
                    },
                    success: function(response) {
                        let city = response.data;
                        $('#city-form').attr('data-updateId', city.id);
                        $('#city_name').val(city.name);
                        $('#lat').val(city.latitude);
                        $('#long').val(city.longitude);
                        $('.form-title').text('Update City');
                        $('.btn-submit').text('Update');
                        $('.btn-clear').removeClass('d-none');
                    }
                });
            });

            $(document).on('click', ".delete-btn", function() {
                let cityId = $(this).data('operationid');
                let loading = undefined;
                $.ajax({
                    url: "{{ route('admin.delete-city') }}",
                    type: "DELETE",
                    data: {
                        id: cityId,
                        _token: "{{ csrf_token() }}",
                    },
                    success: function(response) {
                        Toast.open({
                            type: 'success',
                            message: response.message,
                            background: 'green',
                            duration: 3000,
                        });
                        loadCities();
                    }
                });
            });

            $('#city-form').submit(function(e) {
                e.preventDefault();
                let loading = undefined;
                let url = "{{ route('admin.add-city') }}";
                let method = "POST";
                let cityId = $('#city-form').attr('data-updateId');
                if (cityId) {
                    url = "{{ route('admin.update-city') }}";
                    method = "PUT";
                }
                let data = {
                    name: $('#city_name').val(),
                    latitude: $('#lat').val(),
                    longitude: $('#long').val(),
                    _token: "{{ csrf_token() }}",
                };
                if (cityId) {
                    data.id = cityId;
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
                        loadCities();
                        $('#city-form')[0].reset();
                        $('#city-form').attr('data-updateId', '');
                        $('.form-title').text('Add City');
                        $('.btn-submit').text('Add');
                        $('.btn-clear').addClass('d-none');
                    }
                });
            });

            loadCities();
        });
    </script>
@endpush
