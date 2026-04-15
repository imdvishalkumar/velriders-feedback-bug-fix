@extends('templates.admin')

@section('page-title')
    Coupon
@endsection

@section('content')
    @if (session('success'))
    <div id="success-message" class="alert alert-success">
        {{ session('success') }}
    </div>
    @endif
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row">
                            <div class="col">
                                <h3 class="card-title">All Coupons</h3>
                            </div>
                            
                            <div class="col" style="text-align: right;">
                                <a href="{{ route('admin.coupon.create') }}" class="btn btn-primary">Add Coupon</a>
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
                                        Coupon code
                                    </th>
                                    <th>
                                        Coupon Type
                                    </th>
                                    <th>
                                        Percentage Discount
                                    </th>
                                    <th>
                                        Max Discount Amount
                                    </th>
                                    <th>
                                        Valid From
                                    </th>
                                    <th>
                                        Valid To
                                    </th>
                                    <th>
                                        Single Use Per Customer <h5 class="small">(This type of coupon is used only once per customer)</h5>
                                    </th>
                                    <th>
                                        One time Use Among all Customer <h5 class="small">(This type of coupon is used by only one customer amoung all customers)</h5>
                                    </th>
                                    <th>
                                        Is Active
                                    </th>
                                    <th>
                                        Is Show
                                    </th>
                                    <th>
                                        Acion
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

            function loadCoupons() {
                $.ajax({
                    type: "GET",
                    url: "{{ route('admin.get-all-coupons') }}",
                    success: function(response) {
                        $coupons = response;
                        let html = '';
                        $coupons.forEach((coupon) => {
                            var single_use = 'No';
                            var onetime_use = 'No';
                            const buttonLabel = coupon.is_active ? 'Active' : 'Inactive';
                            const isShowLabel = coupon.is_show ? 'Yes' : 'No';
                            const buttonClass = coupon.is_active ? 'btn-success' : 'btn-secondary';
                            // Determine if the checkbox should be checked based on coupon.is_active
                            const isChecked = coupon.is_active ? 'checked' : '';
                            const isShowChecked = coupon.is_show ? 'checked' : '';
                            var precentDis = '-';
                            var maxDis = '-';
                            if(coupon.percentage_discount != null){
                                precentDis = coupon.percentage_discount;
                            }
                            if(coupon.max_discount_amount != null){
                                maxDis = coupon.max_discount_amount;
                            }
                            if(coupon.single_use_per_customer){
                                single_use = 'Yes';
                            }
                            if(coupon.one_time_use_among_all){
                                onetime_use = 'Yes';
                            }

                            html += `<tr>
                                <td>${coupon.id}</td>
                                <td>${coupon.code}</td>
                                <td>${coupon.type}</td>
                                <td>${precentDis}</td>
                                <td>${maxDis}</td>
                                <td>${coupon.valid_from_formatted}</td>
                                <td>${coupon.valid_to_formatted}</td>
                                <td>${single_use}</td>
                                <td>${onetime_use}</td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input blockToggle" id="blockToggle${coupon.id}" ${isChecked} data-coupon_id="${coupon.id}" data-active_status="${coupon.is_active}">
                                        <label class="custom-control-label" for="blockToggle${coupon.id}">${buttonLabel}</label>
                                    </div>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input showBlockToggle" id="showBlockToggle${coupon.id}" ${isShowChecked} data-coupon_id="${coupon.id}" data-active_status="${coupon.is_show}">
                                        <label class="custom-control-label" for="showBlockToggle${coupon.id}">${isShowLabel}</label>
                                    </div>
                                </td>
                                <td>
                                    <a class="btn btn-sm btn-primary update-btn" data-update_id="${coupon.id}" href="coupon/edit/${coupon.id}">Edit</a>
                                    <a class="btn btn-sm btn-danger" id="delete-btn" data-delete_id="${coupon.id}">Delete</a>
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

            function handleSuccess() {
                Toast.success('Coupons Deleted Successfully');
                setTimeout(function() {
                    $("#success-message").fadeOut("slow", function() {
                        $(this).remove();
                    });
                    setTimeout(function() {
                        location.reload();
                    }, 500); // Reload after 0.5 seconds
                }, 2000); // Fade out after 2 seconds
                loadCoupons();
            }
            
            loadCoupons();
            $(document).on('click', '#delete-btn', function() {
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
            });

            $(document).on('click', '.blockToggle', function() {
                let id = $(this).data('coupon_id');
                var checkStatus = '';
                if($(this).is(":checked")){
                    checkStatus = 'checked';
                }   
                else{
                    checkStatus = 'unchecked';
                }
                $.ajax({
                    type: "post",
                    url: "{{ route('admin.toggle-coupon') }}",
                    data: {
                        id: id,
                        _token: "{{ csrf_token() }}",
                        checkStatus: checkStatus,
                    },
                    success: function(response) {
                        Toast.success(response);
                         setTimeout(function() {
                            location.reload();
                        }, 1000);
                    },
                    error: function(response) {
                        Toast.error('Something went wrong');
                        console.log(response);
                    }
                });
            });

             $(document).on('click', '.showBlockToggle', function() {
                let id = $(this).data('coupon_id');
                var checkStatus = '';
                if($(this).is(":checked")){
                    checkStatus = 'checked';
                }   
                else{
                    checkStatus = 'unchecked';
                }
                $.ajax({
                    type: "post",
                    url: "{{ route('admin.toggle-show-coupon') }}",
                    data: {
                        id: id,
                        _token: "{{ csrf_token() }}",
                        checkStatus: checkStatus,
                    },
                    success: function(response) {
                        Toast.success(response);
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    },
                    error: function(response) {
                        Toast.error('Something went wrong');
                        console.log(response);
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
