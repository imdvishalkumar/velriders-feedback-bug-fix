@extends('templates.admin')

@section('page-title')
    Customer Canceled Refunds <h5 class="text-primary">Razorpay Balance (Live) - Rs. {{$balance}}</h5>
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
                                <h3 class="card-title">All Customers</h3>
                            </div>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <table id="customerRefund" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Booking Id</th>
                                    <th>Customer Details</th>
                                    <th>Vehicle Name</th>
                                    <th>Refund Percent</th>
                                    <th>Refund Amount</th>                        
                                    <th>Refund Status</th>                        
                                    <th>Action</th>                        
                                </tr>
                            </thead>
                            <tbody class="table-data">
                            </tbody>
                        </table>
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
            const tableData = $('.table-data');
            const customerRefund = $("#customerRefund");

            function loadBookings() {
                $.ajax({
                    type: "GET",
                    url: "{{ route('admin.customer.canceled.refund') }}",
                    success: function(response) {
                        let html = '';

                        response.forEach((cancelBooking) => {
                            var buttonStatus = 'btn btn-primary refund-payment';
                            var rStatus = 'Refund';
                            var status = '';
                            if(cancelBooking.refund_amount != 0){
                                if(cancelBooking.refund){
                                    if(cancelBooking.refund.status == 'processed'){
                                        buttonStatus = 'btn btn-secondary';
                                        rStatus = 'Refund in Process';    
                                    }
                                }
                                if(cancelBooking.refund_status == 0){
                                    status = 'Not Refunded';
                                }else if(cancelBooking.refund_status == 1){
                                    status = 'Refunded in Process';
                                }

                                var customerDetails = '';
                                var vehicleDetails = '';
                                if(cancelBooking.rental_booking.customer.firstname != '' && cancelBooking.rental_booking.customer.lastname != ''){
                                    customerDetails += cancelBooking.rental_booking.customer.firstname +' '+cancelBooking.rental_booking.customer.lastname;
                                }
                                if(cancelBooking.rental_booking.customer.email != ''){
                                    customerDetails += ' (' + cancelBooking.rental_booking.customer.email + ' )';
                                }
                                
                                html += `<tr>
                                    <td>${cancelBooking.booking_id}</td>
                                    <td>${customerDetails}</td>
                                    <td>${cancelBooking.rental_booking.vehicle.vehicle_name}</td>
                                    <td>${cancelBooking.refund_percent}</td>
                                    <td>${cancelBooking.refund_amount}</td>
                                    <td>${status}</td>
                                    <td>
                                        <a class="${buttonStatus}" id="refundBtn_${cancelBooking.booking_id}" data-id="${cancelBooking.booking_id}" href="javascript:void(0);" disabled>${rStatus}</a>
                                        <a class="btn btn-primary" id="processBtn_${cancelBooking.booking_id}" href="javascript:void(0);" hidden>Processing...</a>
                                    </td>
                                </tr>`;
                            }

                        });
                        tableData.html(html);

                        customerRefund.DataTable({
                            "responsive": true,
                            "lengthChange": false,
                            "autoWidth": false,
                            "pageLength": 50,
                            "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
                        }).buttons().container().appendTo('#customerRefund_wrapper .col-md-6:eq(0)');
                    }
                });
            }

            function capitalize(str) {
                return str.charAt(0).toUpperCase() + str.slice(1);
            }

            loadBookings();
        
            $(document).on("click",".refund-payment",function() {
                var bookingId = $(this).attr('data-id');
                swal.fire({
                    title: "Are you Sure ? you want to refund for this booking ?",
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#007bff',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No'
                }).then(function(result){
                    if(result.value){
                        $('#refundBtn_'+bookingId).hide();
                        $('#processBtn_'+bookingId).removeAttr('hidden');
                        $.ajax({
                            type: "POST",
                            url: "{{ route('admin.cenceled.refund.process') }}",
                            data: {
                                _token: '{{ csrf_token() }}',
                                bookingId: bookingId,
                            }, 
                            success: function(response) {
                                $('#refundBtn_'+bookingId).show();
                                $('#processBtn_'+bookingId).attr('hidden', true);
                                swal.fire({
                                    title: response.message,
                                    confirmButtonColor: '#007bff',
                                    confirmButtonText: 'Ok',
                                }).then(function(result){
                                      location.reload();
                                });
                            }
                        });     
                    }else if(result.dismiss == 'cancel'){}
                });
            });

            setTimeout(function(){
                $("#success-message").fadeOut("slow", function(){
                    $(this).remove();
                });
            }, 2000); 
          
        });
    </script>
@endpush
