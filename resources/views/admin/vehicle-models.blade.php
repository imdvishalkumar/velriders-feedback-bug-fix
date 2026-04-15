@extends('templates.admin')

@section('page-title')
    Vehicle Models
@endsection

@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">All Vehicle Models</h3>
                    </div>
                    <div class="col" style="text-align: right;">
                        <a href="{{ route('admin.vehicleModel.create') }}" class="btn btn-primary">Add Vehicle Models</a>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <table id="example1" class="table table-bordered table-striped table-responsive">
                            <thead>
                                <tr>
                                    <th>Id</th>
                                    <th>Model Name</th>
                                    <th>Manufacturers Name</th>
                                    <th>Category Name</th>
                                    <th>Minimum Price</th>
                                    <th>Maximum Price</th>
                                </tr>
                            </thead>
                            <tbody class="table-data">
                            </tbody>
                        </table>
                    </div>
                    <!-- /.card-body -->
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
            function loadModels() {

                $.ajax({
                    url: "{{ route('admin.get-all-vehicle-models') }}",
                    type: "GET",
                    beforeSend: function() {
                        $('.table-data').html(
                            '<tr><td colspan="5" class="text-center">Loading...</td></tr>');
                    },
                    success: function(response) {
                        let manufacturer = response.data;
                        let html = '';
                        manufacturer.forEach((item, index) => {
                            var categoryName = '';
                            var minPrice = '';
                            var maxPrice = '';
                            if(item.category && item.category.name){
                                categoryName = item.category.name;
                            }
                            if(item.min_price && item.min_price){
                                minPrice = item.min_price;
                            }
                            if(item.max_price && item.max_price){
                                maxPrice = item.max_price;
                            }
                            html += `<tr>
                                <td>${index + 1}</td>
                                <td>${item.name}</td>
                                <td>${item.manufacturer.name}</td>
                                <td>${categoryName}</td>
                                <td>${minPrice}</td>
                                <td>${maxPrice}</td>
                                <td class="project-actions text-right">
                                    <a class="btn btn-info btn-sm update-btn" data-href="/admin/vehicle-model/edit/" data-operationId='${item.model_id}'>
                                        <i class="fas fa-pencil-alt">
                                        </i>
                                        Edit
                                    </a>
                                    <a class="btn btn-danger btn-sm delete-btn" data-operationId='${item.model_id}'>
                                        <i class="fas fa-trash">
                                        </i>
                                        Delete
                                    </a>
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
            loadModels();

            $(document).on('click', ".update-btn", function() {
                let modelId = $(this).data('operationid');
                let editUrl = $(this).attr('data-href');
                window.location.href = sitePath+editUrl+modelId;
            });
            $(document).on('click', ".delete-btn", function() {
                let typeId = $(this).data('operationid');
                let loading = undefined;
                // Confirmation dialog
                /*if (!confirm("Are you sure you want to delete this item?")) {
                    return; // Do nothing if the user cancels
                }*/
                $.ajax({
                    url: "{{ route('admin.delete-vehicle-models') }}",
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
                        loadModels();
                    }
                });
            });

        });
    </script>
@endpush
