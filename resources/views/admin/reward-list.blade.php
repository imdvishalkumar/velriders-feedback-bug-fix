@extends('templates.admin')

@section('page-title')
    Reward List
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
                                <h3 class="card-title">Rewards</h3>
                            </div>
                        </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <table id="rewardList" class="table table-bordered table-striped table-responsive">
                            <thead>
                                <tr>
                                    <th>Customer Details</th>
                                    <th>Used Referral Code</th>
                                    <th>Referred User Details</th>
                                    <th>Booking Id</th>
                                    <th>Reward Type</th>
                                    <th>Reward Amount/Percent</th>
                                    <th>Payable Amount</th>
                                    <th>User Bank Details </th>
                                    <th>Payable Status</th>
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

<div class="modal fade" id="viewBankDetails" tabindex="-1" role="dialog" aria-labelledby="viewBankModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewBankModalLabel"><b>Bank Details</b></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    Account Holder Name: <label id="holder_name"></label>
                </div>
                <div class="form-group">
                    Bank Name: <label id="bank_name"></label>
                </div>
                <div class="form-group">
                    Branch Name: <label id="branch_name"></label>
                </div>
                <div class="form-group">
                    City: <label id="city"></label>
                </div>
                <div class="form-group">
                    Account Number: <label id="account_number"></label>
                </div>
                <div class="form-group">
                    IFSC Code: <label id="ifsc_code"></label>
                </div>
                <div class="form-group">
                    Nick Name: <label id="nick_name"></label>
                </div>
            </div>                    
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
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
            const tableData = $('.table-data');
            const rewardList = $("#rewardList");

            function loadRewardList() {
                var callingUrl = '/admin/get-rewards';
                $.ajax({
                    type: "GET",
                    url: sitePath + callingUrl,
                    success: function(response) {
                        let html = '';
                        response.forEach((reward) => {
                            var customerInfo =  referredUser = '';
                            var rewardType = '';
                            var isPaid = '';
                            var payBtn = '';
                            var paidBtn = 'hidden';
                            if(reward.customer_details.firstname != null && reward.customer_details.lastname != null){
                                customerInfo += ' <b>Name - </b>'+reward.customer_details.firstname +' '+reward.customer_details.lastname+'<br/>';
                            }
                            if(reward.customer_details.email != null){
                                customerInfo += ' <b>Email - </b>' + reward.customer_details.email + '<br/>';
                            }
                            if(reward.customer_details.mobile_number != null){
                                customerInfo += ' <b>Mobile No. - </b>' + reward.customer_details.mobile_number + '<br/>';
                            }

                            if(reward.referred_user && reward.referred_user.firstname != null && reward.referred_user.lastname != null){
                                referredUser += ' <b>Name - </b>'+reward.referred_user.firstname +' '+reward.referred_user.lastname+'<br/>';
                            }
                            if(reward.referred_user && reward.referred_user.email != null){
                                referredUser += ' <b>Email - </b>' + reward.referred_user.email + '<br/>';
                            }
                            if(reward.referred_user && reward.referred_user.mobile_number != null){
                                referredUser += ' <b>Mobile No. - </b>' + reward.referred_user.mobile_number + '<br/>';
                            }

                            if(reward.reward_type == 1){
                                rewardType = 'FIXED';
                            }else if(reward.reward_type == 2){
                                rewardType = 'PERCENTAGE';
                            }
                            if(reward.is_paid == 0){
                                isPaid = 'PENDING';
                            }else if(reward.is_paid == 1){
                                isPaid = 'PAID';
                                payBtn = 'hidden';
                                paidBtn = '';
                            }

                            html += `<tr>
                                <td>${customerInfo}</td>
                                <td>${reward.used_referral_code}</td>
                                <td>${referredUser}</td>
                                <td>${reward.booking_id}</td>
                                <td>${rewardType}</td>
                                <td>${reward.reward_amount_or_percent}</td>
                                <td>${reward.payable_amount}</td>
                                <td>${isPaid}</td>
                                <td><a class="text-primary viewBank" id="rewardcode_${reward.used_referral_code}" data-id="${reward.used_referral_code}" href="javascript:void(0);">View Details</a></td>
                                <td><span>
                                        <a class="btn btn-primary payAmt" ${payBtn} id="reward_${reward.id}" data-id="${reward.id}" href="javascript:void(0);">Pay</a>
                                        <a class="btn btn-secondary" ${paidBtn} href="javascript:void(0);">Paid</a>
                                    </span>
                                </td>
                            </tr>`;
                        });
                        tableData.html(html);
                        rewardList.DataTable({
                            "responsive": true,
                            "lengthChange": false,
                            "autoWidth": false,
                            "pageLength": 200,
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
                        }).buttons().container().appendTo('#rewardList_wrapper .col-md-6:eq(0)');
                    }
                });
            }

            loadRewardList();

            setTimeout(function(){
                $("#success-message").fadeOut("slow", function(){
                    $(this).remove();
                });
            }, 2000); 

            $(document).on("click",".payAmt",function() {
                var rewardId = $(this).attr('data-id');
                swal.fire({
                    title: "Are you Sure ? you want to change payment status ?",
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#007bff',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No'
                }).then(function(result){
                    if(result.value){
                        $.ajax({
                            type: "POST",
                            url: "{{ route('admin.store-paystatus') }}",
                            data: {_token: '{{ csrf_token() }}', rewardId:rewardId}, 
                            success: function(response) {
                                if(response.status == true){
                                    swal.fire({
                                        title: response.message,
                                        confirmButtonColor: '#007bff',
                                        confirmButtonText: 'Ok',
                                    }).then(function(result){
                                        location.reload();
                                    });
                                }else{
                                    swal.fire({
                                        title: response.message,
                                        type: 'error',
                                        confirmButtonColor: '#007bff',
                                        confirmButtonText: 'Ok',
                                    });
                                }
                            }
                        });
                    }
                });
            });

            $(document).on("click",".viewBank",function() {
                var referralCode = $(this).attr('data-id');
                $.ajax({
                    type: "POST",
                    url: "{{ route('admin.get-bank-details') }}",
                    data: {_token: '{{ csrf_token() }}', referralCode:referralCode}, 
                    success: function(response) {
                        if(response.status == true && response.details != ''){
                            $('#holder_name').text(response.details.account_holder_name);
                            $('#bank_name').text(response.details.bank_name);
                            $('#branch_name').text(response.details.branch_name);
                            $('#city').text(response.details.city);
                            $('#account_number').text(response.details.account_no);
                            $('#ifsc_code').text(response.details.ifsc_code);
                            $('#nick_name').text(response.details.nick_name);
                            $('#viewBankDetails').modal('show');
                        }else{
                            swal.fire({
                                title: response.message,
                                type: 'error',
                                confirmButtonColor: '#007bff',
                                confirmButtonText: 'Ok',
                            });
                        }
                    }
                });

            });


        });
    </script>
@endpush
