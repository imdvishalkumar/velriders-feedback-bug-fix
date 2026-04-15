@extends('templates.admin')

@section('page-title')
    Pending Bookings
@endsection

@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-md-2">
                                <h3 class="card-title">All Pending Bookings</h3>
                            </div>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <table id="pending_booking" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Booking Id</th>
                                    <th>Customer Details</th>
                                    <th>Vehicle Details</th>
                                    <th>Pickup Date</th>
                                    <th>Return Date</th>
                                    <th>Vehicle City</th>
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
        $.fn.editable.defaults.mode = 'inline';
        const Toast = new Notyf({
            position: {
                x: 'center',
                y: 'top',
            }
        });

        $(document).ready(function() {
            const tableData = $('.table-data');
            const pending_booking = $("#pending_booking");

            function loadBookings() {
                $.ajax({
                    type: "GET",
                    url: "{{ route('admin.get-all-booking', 'pending') }}",
                    success: function(response) {
                        let html = '';
                        var priceSummary = '';
                        var tripAmount = '';
                        var convenienceFee = '';
                        var totalPrice = '';

                        response.forEach((vehicle) => {
                            var customerDetails = '';
                            var vehicleDetails = '';
                            if(vehicle.customer.firstname != null && vehicle.customer.lastname != null){
                                customerDetails += ' <b>Name - </b>'+vehicle.customer.firstname +' '+vehicle.customer.lastname+'<br/>';
                            }
                            if(vehicle.customer.email != null){
                                customerDetails += ' <b>Email - </b>' + vehicle.customer.email + '<br/>';
                            }
                            if(vehicle.customer.mobile_number != null){
                                customerDetails += ' <b>Mobile No. - </b>' + vehicle.customer.mobile_number + '<br/>';
                            }
                            if(vehicle.customer.dob != null){
                                customerDetails += ' <b>Date of Birth. - </b>' + vehicle.customer.dob + '<br/>';
                            }
                            if(vehicle.customer.documents != null){
                                customerDetails += ' <b>DL Status - </b>' + vehicle.customer.documents.dl + '<br/>';
                                customerDetails += ' <b>GovId Status - </b>' + vehicle.customer.documents.govtid;
                            }
                           
                            html += `<tr>
                                <td>${vehicle.booking_id}</td>
                                <td>${customerDetails}</td>
                                <td>${vehicle.vehicle.vehicle_name}</td>
                                <td>${vehicle.pickup_date}</td>
                                <td>${vehicle.return_date}</td>
                                <td>${vehicle.vehicle.branch.name}</td>
                            </tr>`;
                        });
                        tableData.html(html);
                        $.ajaxSetup({
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            }
                        });

                        pending_booking.DataTable({
                            "responsive": true,
                            "lengthChange": false,
                            "autoWidth": false,
                            "pageLength": 50,
                            "buttons": ["copy", "csv", "excel", 
                            {
                            extend: 'pdfHtml5',
                            orientation: 'landscape',
                            pageSize: 'A3',
                             exportOptions: {
                                    columns: [0,1,2,3,4,5,6,7,8,9,10,11,12,16,17] // Specify the columns you want to export (zero-based index)
                                } 
                            }
                            ,"print", "colvis"]
                            //pageSize: LEGAL
                        }).buttons().container().appendTo('#pending_booking_wrapper .col-md-6:eq(0)');
                    }
                });
            }

            loadBookings();
            setTimeout(function(){
                $("#success-message").fadeOut("slow", function(){
                    $(this).remove();
                });
            }, 2000); 
        });
    </script>
@endpush
