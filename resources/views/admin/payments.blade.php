@extends('templates.admin')

@section('page-title')
    Payment 
@endsection

@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Payment History</h3>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <table id="example1" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th style="width: 1%">
                                        Id
                                    </th>
                                    <th style="width: 20%">
                                        Payment Type
                                    </th>
                                    <th style="width: 30%">
                                        Booking Id
                                    </th>
                                    <th>
                                        Amount
                                    </th>
                                    <th>
                                        Payment Date 
                                    </th>
                                    <th>
                                        Status
                                    </th>
                                    <!-- <th>
                                        Action
                                    </th> -->
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
                    url: "{{ route('admin.get-all-payment') }}",
                    beforeSend: function() {
                        $('.table-data').html(
                            '<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>'
                        );
                    },
                    success: function(response) {
                        $payment = response;
                        let html = '';
                        $payment.forEach((vehicle) => {
                            html += `<tr>
                                <td>${vehicle.payment_id}</td>
                                <td>${vehicle.payment_type}</td>
                                <td>${vehicle.booking_id}</td>
                                <td>${vehicle.amount}</td>
                                <td>${vehicle.payment_date}</td>
                                <td>${vehicle.status}</td>
                                {{--<td>
                                    <a class="btn btn-sm btn-primary" id="update-btn" data-update_id="${vehicle.payment_id}">Edit</a>
                                    <a class="btn btn-sm btn-danger" id="delete-btn" data-delete_id="${vehicle.payment_id}">Delete</a>
                                </td>--}}
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

            function loadInsertForm() {
                $.ajax({
                    type: "GET",
                    url: "{{ route('admin.get-payments-insert-form') }}",
                    beforeSend: function() {
                        $('#dynamic-form').html(
                            '<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>'
                        );
                    },
                    success: function(response) {
                        $('#dynamic-form').html(response);
                    }
                });
            }

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

            $(document).on('submit', "#payment-form", function(e) {
                e.preventDefault();
                let formData = new FormData(this);
                console.log(formData);
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

                let method = 'POST';
                let url = '';
                url = "{{ route('admin.get-payments-store-form') }}";

                $.ajax({
                    type: method,
                    url: url,
                    data: formData,
                    contentType: false,
                    processData: false,
                    beforeSend: function() {
                        $('#dynamic-form').html(
                            '<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>'
                        );
                    },
                    success: function(response) {
                        console.log(response);
                        if (response.status == true) {
                            Toast.success(response.message);
                            loadInsertForm();
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
            
            $(document).on('click', '#cancel', function() {
                loadInsertForm();
            });

            // $(document).on('click', '#delete-btn', function() {
            //     let id = $(this).data('delete_id');
            //     $.ajax({
            //         type: "DELETE",
            //         url: "{{ route('admin.vehicle-delete') }}",
            //         data: {
            //             id: id,
            //             _token: "{{ csrf_token() }}",
            //         },
            //         beforeSend: function() {
            //             $('#dynamic-form').html(
            //                 '<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>'
            //             );
            //         },
            //         success: function(response) {
            //             if (response.status == true) {
            //                 Toast.success(response.message);
            //                 loadInsertForm();
            //                 loadVehicles();
            //             } else {
            //                 Toast.error(response.message);
            //             }
            //         },
            //         error: function(response) {
            //             Toast.error('Something went wrong');
            //             console.log(response);
            //         }
            //     });
            // });

            loadInsertForm();
        });
    </script>
@endpush
