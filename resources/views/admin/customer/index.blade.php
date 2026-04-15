@extends('templates.admin')

@section('page-title')
    Customers
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
                        <table id="customers" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Profile Picture</th>
                                    <th>Email</th>
                                    <th>Mobile</th>
                                    <th>Country Code</th>
                                    <th>DOB</th>
                                    <th>Billing Address</th>
                                    <th>Shipping Address</th>
                                    <th>Device ID</th>
                                    <th>Device Token</th>
                                    <th>Status</th>
                                    <th>Resend Mail</th>
                                    <th>Action</th>
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

<div class="modal fade" id="editCustomerModal" tabindex="-1" role="dialog" aria-labelledby="editCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCustomerModalLabel">Edit Customer</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editCustomerForm">
                <div class="modal-body">
                    <!-- Form for editing customer data -->
                    
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" name="email" id="email" class="form-control" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="mobile_number">Mobile:</label>
                        <input type="text" name="mobile_number" id="mobile_number" class="form-control" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="country_code">Country Code:</label>
                        <input type="text" name="country_code" id="country_code" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="dob">DOB:</label>
                        <input type="text" name="dob" id="dob" class="form-control" autocomplete="off" placeholder="Select Date">
                    </div>
                    <div class="form-group">
                        <label for="billing_address">Billing Address:</label>
                        <input type="text" name="billing_address" id="billing_address" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="shipping_address">Shipping Address:</label>
                        <input type="text" name="shipping_address" id="shipping_address" class="form-control">
                    </div>
                        <input type="hidden" name="customer_id" id="customer_id" class="form-control">
                    
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="updateCustomer">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

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
             $( "#dob" ).datepicker({  maxDate: new Date() });
             // Define a function to handle success response
           /* function handleSuccess() {
                Toast.success('Operation successful');
                setTimeout(function() {
                    $("#success-message").fadeOut("slow", function() {
                        $(this).remove();
                    });
                    setTimeout(function() {
                        location.reload();
                    }, 500); // Reload after 0.5 seconds
                }, 2000); // Fade out after 2 seconds
            }*/

            function loadCustomers() {
                $.ajax({
                    type: "GET",
                    url: "{{ route('admin.customers.index') }}",
                    success: function(response) {
                        $customers = response;
                        let html = '';
                        var cStatus = '';
                        $customers.forEach((customer) => {
                            var name = '';
                            if(customer.is_deleted)
                                cStatus = 'Deleted';
                            else if(customer.is_blocked)
                                cStatus = 'Bloked';
                            else
                                cStatus = 'Active';

                            if(customer.firstname != null)
                                name += customer.firstname;
                            if(customer.lastname != null)
                                name += ' ' +customer.lastname;
                            var noImg = '{{asset('images/noimg.png')}}';
                            var profile_picture_url = noImg;
                            if(customer.profile_picture_url != null)
                                    profile_picture_url = customer.profile_picture_url;
                            /*$.get(customer.profile_picture_url)
                            .done(function() { 
                                if(customer.profile_picture_url != null)
                                    profile_picture_url = customer.profile_picture_url;
                            })*/
                            var email = customer.email != null ? customer.email : '';
                            var mobile_number = customer.mobile_number != null ? customer.mobile_number : '';
                            var country_code = customer.country_code != null ? customer.country_code : '';
                            var dob = customer.dob != null ? customer.dob : '';
                            var billing_address = customer.billing_address != null ? customer.billing_address : '';
                            var shipping_address = customer.shipping_address != null ? customer.shipping_address : '';
                            var device_id = customer.device_id != null ? customer.device_id : '';
                            var device_token = customer.device_token != '' ? customer.device_token : '';
                            var blockStatus = '';
                            var blockText = 'Block';
                            if(customer.is_blocked && customer.is_blocked != 0){
                                blockStatus = 'checked';
                                blockText = 'Un-Block';
                            }
                            html += `<tr>
                                <td>${customer.customer_id}</td>
                                <td>${name}</td>
                                <td><img src="${profile_picture_url}" alt="${customer.firstname}" width="100" height="100"></td>
                                <td>${email}</td>
                                <td>${mobile_number}</td>
                                <td>${country_code}</td>
                                <td>${dob}</td>
                                <td>${billing_address}</td>
                                <td>${shipping_address}</td>
                                <td>${device_id}</td>
                                <td>${device_token}</td>
                                <td>${cStatus}</td>
                                <td><a class="btn btn-sm btn-danger sendmail-btn" data-cust_id="${customer.customer_id}">Click to send Mail </a>
                                </td>
                                <td><a class="btn btn-sm btn-primary update-btn" data-update_id="${customer.customer_id}">Edit</a><a class="btn btn-sm btn-danger delete-btn" data-delete_id="${customer.customer_id}">Delete</a>
                                <div class=""><input type="checkbox" class="blockToggle" data-id="${customer.customer_id}" ${blockStatus}><label class="">${blockText}</label></div>
                                </td></tr>`;
                        });
                        $('.table-data').html(html);

                        $("#customers").DataTable({
                            "responsive": true,
                            "lengthChange": false,
                            "autoWidth": false,
                            "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
                        }).buttons().container().appendTo('#customers_wrapper .col-md-6:eq(0)');
                    }
                });
            }

            function handleSuccess() {
                Toast.success('Customer data updated Successfully');
                setTimeout(function() {
                    $("#success-message").fadeOut("slow", function() {
                        $(this).remove();
                    });
                    setTimeout(function() {
                        location.reload();
                    }, 500); // Reload after 0.5 seconds
                }, 3000); // Fade out after 2 seconds
            }
            
            loadCustomers();
            /*$(document).on('click', '#delete-btn', function() {
                let id = $(this).data('delete_id');
                
                $.ajax({
                    type: "post",
                    url: "{{ route('admin.delete-coupon') }}",
                    data: {
                        id: id,
                        _token: "{{ csrf_token() }}",
                    },
                    success: handleSuccess,
                    error: function(response) {
                        Toast.error('Something went wrong');
                        console.log(response);
                    }
                });
            });*/

            // AJAX call to fetch customer data for editing
            $(document).on("click",".update-btn",function() {
                var custId = $(this).attr('data-update_id');
                $.ajax({
                    type: 'GET',
                    url: "{{ url('admin/customer/edit') }}/" + custId,
                    success: function(response) {
                        console.log(response);
                        $('#editCustomerModal').find('#email').val(response.email);
                        $('#editCustomerModal').find('#mobile_number').val(response.mobile_number);
                        $('#editCustomerModal').find('#country_code').val(response.country_code);
                        $('#editCustomerModal').find('#dob').val(response.dob);
                        $('#editCustomerModal').find('#billing_address').val(response.billing_address);
                        $('#editCustomerModal').find('#shipping_address').val(response.shipping_address);
                        $('#editCustomerModal').find('#customer_id').val(response.customer_id);
                        $('#editCustomerModal').modal('show');
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching Customer data:', error);
                    }
                });
            });

            $(document).on("click",".sendmail-btn",function() {
                var custId = $(this).attr('data-cust_id');
                $.ajax({
                    type: 'GET',
                    url: "{{ url('admin/customer/sendmail') }}/" + custId,
                    success: function(response) {
                        console.log(response);
                        if(response){
                            Toast.success('Mail sent Successfully');
                        }else{
                            Toast.error('Email Id not found OR may be some issue while sending mail');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching Customer data:', error);
                    }
                });
            });

            // AJAX call to update customer data
            $("#updateCustomer").click(function(event) {
            //$("#updateCustomer").click(function() {
                var formData = {
                    'customer_id': $('#customer_id').val(),
                    'email': $('#email').val(),
                    'mobile_number': $('#mobile_number').val(),
                    'country_code': $('#country_code').val(),
                    'dob': $('#dob').val(),
                    'billing_address': $('#billing_address').val(),
                    'shipping_address': $('#shipping_address').val(),
                    '_token': "{{ csrf_token() }}",
                };
                console.log(formData);
                $.ajax({
                    type: 'POST',
                    url: "{{ url('admin/customer/update') }}",
                    data: formData,
                    dataType: 'json',
                    success: handleSuccess,
                    error: function(xhr, status, error) {
                        var errors = xhr.responseJSON.errors;
                        $.each(errors, function(key, value) {
                            console.error(key + ": " + value);
                        });
                    }
                });
            });

            $(document).on("click",".delete-btn",function() {
                var customerId = $(this).attr('data-delete_id');
                $.ajax({
                    type: 'POST',
                    url: '{{ route("delete.customer") }}',
                    data: {
                        customerId: customerId,
                        _token: "{{ csrf_token() }}",
                    },
                    success: handleSuccess,
                    error: function(xhr, status, error) {
                        console.error('Error deleting Customer:', error);
                    }
                });
            });

             $(document).on("click",".blockToggle", function() {
                var custId = $(this).attr('data-id');
                var isChecked = $(this).prop('checked');
                var status = isChecked ? 'blocked' : 'unblocked';
                $.ajax({
                    type: 'POST',
                    url: '{{ route("block.customer") }}',
                    data: {
                        custId: custId,
                        status: status,
                        _token: "{{ csrf_token() }}",
                    },
                    success: handleSuccess,
                    error: function(xhr, status, error) {
                        console.error('Error toggling Customer status:', error);
                    }
                });
            });

        });

        $('#editCustomerForm').validate({ 
           rules: {
              email: {required: true},
              mobile_number: {required: true},
              country_code: {required: true},
           },
           messages :{
                email : { required : 'Please enter Email' },
                mobile_number : { required : 'Please enter Mobile Number' },
                country_code : { required : 'Please enter Country Code' },
            },
            highlight: function (element) {
                console.log(element, element.type, element.tagName)
                if ($(element).is('select') || $(element).is('input')) {
                    $(element).parent('.select-wrap').addClass('error');
                } else {
                    $(element).addClass('error');
                }
            },
             submitHandler: function (form) {
                  $('#updateCustomer').attr('type', 'button');
              }
        });

        /*var dtToday = new Date();
        var month = dtToday.getMonth() + 1;
        var day = dtToday.getDate();
        var year = dtToday.getFullYear();
        if(month < 10)
            month = '0' + month.toString();
        if(day < 10)
            day = '0' + day.toString();
        var maxDate = year + '-' + month + '-' + day;
        $('#dob').attr('max', maxDate);*/
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
