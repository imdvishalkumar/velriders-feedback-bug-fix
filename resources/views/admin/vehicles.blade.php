@extends('templates.admin')

@section('page-title')
    Vehicles
    @if (session('success'))
        <div id="success-message" class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
@endsection

@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row">
                            <div class="col">
                                <h3 class="card-title">All Vehicles</h3>
                            </div>
                            
                            <div class="col" style="text-align: right;">
                                <a href="{{ route('admin.vehicle.create') }}" class="btn btn-primary">Add Vehicles</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- /.card-header -->
                    <div class="card-body">
                        <table id="example1" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>
                                        Id
                                    </th>
                                    <th>
                                        Model
                                    </th>
                                    <th>
                                        Branch
                                    </th>
                                    <th>
                                        Category
                                    </th>
                                    <th>
                                        Year
                                    </th>
                                    <th>
                                        Description
                                    </th>
                                    <th>
                                        Color
                                    </th>
                                    <th>
                                        Registration Number
                                    </th>
                                    <th>
                                        Avaliablity
                                    </th>
                                    <th>
                                        Rental Price
                                    </th>
                                    <th>
                                        Publish / UnPublish
                                    </th>
                                    <th>
                                        Operations
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
            <div class="col-md-4" id="dynamic-form">

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

            function loadVehicles() {
                $.ajax({
                    type: "GET",
                    url: "{{ route('admin.get-all-vehicles') }}",
                    // beforeSend: function() {
                    //     $('.table-data').html(
                    //         '<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>'
                    //     );
                    // },
                    success: function(response) {
                        $vehicles = response.data;
                        let html = '';
                        $vehicles.forEach((vehicle) => {
                            var branchName = '';
                            if(vehicle.branch){
                                branchName = vehicle.branch.name;
                            }
                            var publishStatus = '';
                            var publishText = ' Publish';
                            if(vehicle.publish && vehicle.publish != 0){
                                publishStatus = 'checked';
                                publishText = ' UnPublish';
                            }
                            html += `<tr>
                                <td>${vehicle.vehicle_id}</td>
                                <td>${vehicle.vehicle_name}</td>
                                <td>${branchName}</td>
                                <td>${vehicle.category_name}</td>
                                <td>${vehicle.year}</td>
                                <td>${vehicle.description}</td>
                                <td>${vehicle.color}</td>
                                <td>${vehicle.license_plate}</td>
                                <td>${vehicle.availability == 1 ? "Yes" : "No"}</td>
                                <td>${vehicle.rental_price}</td>
                                <td><div class=""><input type="checkbox" id="publish_${vehicle.vehicle_id}" class="publishToggle" data-id="${vehicle.vehicle_id}" ${publishStatus}> <label class="">${publishText}</label></div></td>
                                <td>
                                    <a class="btn btn-sm btn-primary update-btn" data-update_id="${vehicle.vehicle_id}" href="vehicle/edit/${vehicle.vehicle_id}">Edit</a>
                                    <a class="btn btn-sm btn-danger" id="delete-btn" data-delete_id="${vehicle.vehicle_id}">Delete</a>
                                </td>
                            </tr>`;
                        });
                        $('.table-data').html(html);

                        $("#example1").DataTable({
                            "responsive": true,
                            "lengthChange": false,
                            "autoWidth": false,
                            "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
                        }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
                    }
                });
            }

            loadVehicles();

           
            function loadDateMessageInput() {
                $("#date-container").append(
                    `<div class='card p-2'>
                        <div class='form-group'>
                            <input type='date' placeholder='please selecte the date' name='dates[]' class='form-control ip-date' required>
                        </div>
                        <div class='form-group'>
                            <input type='text' placeholder='please provide a reason' name='reasons[]' class='form-control ip-date-reason' required>
                        </div>
                        <div class="d-flex justify-content-end align-items-center">
                            <button class='btn btn-sm btn-danger remove-date' type='button' id="btn-remove-ipbox">Remove</button>
                        </div>
                    </div>`
                );
            }

            $(document).on('click', "#btn-remove-ipbox", function() {
                $(this).closest('.card').remove();
            });

            $(document).on('click', "#add-more", function() {
                loadDateMessageInput();
            });

            $(document).on('submit', "#vehicle-form", function(e) {
                e.preventDefault();
                let formData = new FormData(this);
                let dates = [];
                let reasons = [];
                if ($('.ip-date').length > 0) {
                    $('.ip-date').each(function() {
                        dates.push($(this).val());
                    });
                    $('.ip-date-reason').each(function() {
                        reasons.push($(this).val());
                    });
                    formData.append('dates', dates);
                    formData.append('reasons', reasons);
                }
                formData.append('_token', "{{ csrf_token() }}");
                formData.delete('dates[]');
                formData.delete('reasons[]');

                if (formData.get('branch') == null) {
                    Toast.error('Please select a branch');
                    return;
                }

                if (formData.get('model') == null) {
                    Toast.error('Please select a model');
                    return;
                }

                if (formData.get('category') == null) {
                    Toast.error('Please select a category');
                    return;
                }

                if (formData.get('availability') == null) {
                    Toast.error('Please select a availability');
                    return;
                }

                let method = 'POST';
                let url = '';
                var id = formData.get('vehicle_id');

                if (id != '' && id != null) {
                    url = "{{ route('admin.vehicle-update') }}";
                } else {
                    url = "{{ route('admin.vehicle-insert') }}";
                }

            });

            $(document).on('click', '#delete-btn', function() {
                let id = $(this).data('delete_id');
                $.ajax({
                    type: "DELETE",
                    url: "{{ route('admin.vehicle-delete') }}",
                    data: {
                        id: id,
                        _token: "{{ csrf_token() }}",
                    },
                    // beforeSend: function() {
                    //     $('#dynamic-form').html(
                    //         '<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>'
                    //     );
                    // },
                    success: function(response) {
                        if (response.status == true) {
                            Toast.success(response.message);
                            loadVehicles();
                        } else {
                            Toast.error(response.message);
                        }
                    },
                    error: function(response) {
                        Toast.error('Something went wrong');
                        console.log(response);
                    }
                });
            });

            $(document).on("click",".publishToggle", function() {
                var vehicleId = $(this).attr('data-id');
                var isChecked = $(this).prop('checked');
                var status = isChecked ? 'publish' : 'unpublish';
                $.ajax({
                    type: 'POST',
                    url: '{{ route("publish.vehicle") }}',
                    data: {
                        vehicleId: vehicleId,
                        status: status,
                        _token: "{{ csrf_token() }}",
                    },
                    success: function(response) {
                        if (response.status == true) {
                            swal.fire({
                                title: response.message,
                                type: 'success',
                            }).then(function(result){
                                if(result.value){
                                    location.reload();
                                }else{}
                            });
                        } else {
                           $('#publish_'+vehicleId).prop('checked', false);
                            swal.fire({
                                title: response.message,
                                type: 'error',
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error toggling Customer status:', error);
                    }
                });
            });

        });
    </script>

<script>
    // Function to remove the success message after 3 seconds
    $(document).ready(function(){
        setTimeout(function(){
            $("#success-message").fadeOut("slow", function(){
                $(this).remove();
            });
        }, 2000); // 2000 milliseconds = 2 seconds
    });
</script>
@endpush
