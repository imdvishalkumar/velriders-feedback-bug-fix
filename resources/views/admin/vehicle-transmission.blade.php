@extends('templates.admin')

@section('page-title')
   Vehicle Transmission
@endsection

@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">All Transmission</h3>

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
                                        Vehicle Types
                                    </th>
                                    <th style="width: 30%">
                                        Name
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
                        <h3 class="card-title form-title">Add Vehicle Transmission</h3>
                    </div>
                    <form action="{{ route('submit.vehicle-transmission') }}" method="POST" enctype="multipart/form-data" id="fuelTypeForm">
                        @csrf
                        <input type="hidden" id="tId" name="tId">
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
            function loadVehicleTransmission() {
                $.ajax({
                    url: "{{ route('admin.get-all-vehicle-transmissions') }}",
                    type: "GET",
                    beforeSend: function() {
                        $('.table-data').html(
                            '<tr><td colspan="9" class="text-center">Loading...</td></tr>');
                    },
                    success: function(response) {
                        let transmission = response.data;
                        let html = '';
                        transmission.forEach(type => {
                            html += `<tr>
                            <td>${type.transmission_id}</td>
                            <td>${type.get_vehicle_type.name}</td>
                            <td>${type.name}</td>
                            <td class="project-actions text-right">
                                <a class="btn btn-info btn-sm update-btn" data-operationId='${type.transmission_id}'>
                                    <i class="fas fa-pencil-alt">
                                    </i>
                                    Edit
                                </a>
                                <a class="btn btn-danger btn-sm delete-btn" data-operationId='${type.transmission_id}'>
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
                $('#fuel-type-form').attr('data-updateId', '');
                $('.form-title').text('Add Vehicle Transmisison');
                $('.btn-submit').text('Add');
                $('.btn-clear').addClass('d-none');
                $('#fuel-type-form')[0].reset();
            });

            $(document).on('click', ".update-btn", function() {
                let typeId = $(this).data('operationid');
                let loading = undefined;
                $.ajax({
                    url: "{{ route('admin.get-vehicle-transmission') }}",
                    type: "GET",
                    data: {
                        id: typeId
                    },
                    success: function(response) {
                        let transmission = response.data;
                        $('#fuel-type-form').attr('data-updateId', transmission.fuel_type_id);
                        $('#name').val(transmission.name);
                        $('#vehicleType').val(transmission.vehicle_type_id);
                        $('.form-title').text('Update Vehicle Transmission');
                        $('.btn-submit').text('Update');
                        $('.btn-clear').removeClass('d-none');
                        $('#tId').val(transmission.transmission_id);
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
                    url: "{{ route('admin.delete-transmission') }}",
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
                        loadVehicleTransmission();
                    }
                });
            });

            loadVehicleTransmission();
        });
    </script>
@endpush
