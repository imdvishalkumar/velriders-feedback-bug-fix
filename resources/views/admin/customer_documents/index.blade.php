@extends('templates.admin')

@section('page-title')
    Customers Documents
@endsection
<style>
.select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #444;
    line-height: 20px !important;
}
</style>
@section('content')
    <!-- Main content -->
    <section class="content">
        <div id="error-container">
            @if (session('success'))
                <div id="success-message" class="alert alert-success">
                    {{ session('success') }}
                </div>
            @elseif(session('error'))
                <div id="error-message" class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif
        </div>
            
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-md-2">
                                <h3 class="card-title">All Customers Documents</h3>
                            </div>
                            <div class="col-md-4">
                                <select id="customer" class="form-control custom-select" name="customer">
                                    <option selected disabled value="">Search Customer</option>
                                    @if(isset($customers) && is_countable($customers) && count($customers) > 0)
                                        @foreach($customers as $key => $val)
                                            <option value="{{$val->customer_id}}" @if(old('customer') == $val->customer_id){{'selected'}}@else{{''}}@endif>{{$val->customer_id}} ({{$val->firstname}} {{$val->lastname}})</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-4"></div>
                            <div class="col-md-2">
                                <a href="{{route('admin.add-document')}}" class="btn btn-primary float-right">Add Documents</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body" id="customerDocumentsHtml">

                        <!-- Data will append here -->

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
        load_page_details(1);
        $('#customer').select2();  

        $(document).on("click", ".messageType", function (e) {
        //$('input[name^="rejection_type"]').change(function() {
            var docId = this.id.match(/\d+/)[0]; // Extracts the number from the ID
            if ($(this).val() == 'custom') {
                $('#customMessageGroup' + docId).show();
                $('#templateSelectGroup' + docId).hide();
            } else {
                $('#customMessageGroup' + docId).hide();
                $('#templateSelectGroup' + docId).show();
            }
        });

        $(document).on("click", ".approveBtn", function (e) {
        //$('.approve-form').submit(function(event) {
            var documentType = $(this).data('document-type');
            var documentId = $(this).data('document-id');
            var carStatus = '';
            var bikeStatus = '';
            if($('#carCheckbox'+documentId).prop('checked')){
                carStatus = 'car';
            }
            if($('#bikeCheckbox'+documentId).prop('checked')){
                bikeStatus = 'bike';
            }
            // Only apply checkbox validation if the document type is not 'dl'
            if (documentType === 'dl') {
                var isCheckedCar = $('#carCheckbox' + documentId).is(':checked');
                var isCheckedBike = $('#bikeCheckbox' + documentId).is(':checked');

                if (!isCheckedCar && !isCheckedBike) {
                    $('#vehicleTypeError' + documentId).show(); // Show error message
                    event.preventDefault(); // Prevent form submission
                    return false; // Ensure the form does not submit
                } else {
                    $('#vehicleTypeError' + documentId).hide(); // Hide error message if no error
                }
            }

            var url = $('#approveUrl_'+documentId).val();
            $.ajax({
                type: 'POST',
                url: url,
                data: {
                    bike_status: bikeStatus,
                    car_status: carStatus,
                    document_type:documentType,
                    _token: "{{ csrf_token() }}",
                },
                success: function(response) {
                    Toast.success(response);
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                },
                error: function(xhr, status, error) {
                    console.error('Error toggling Document status:', error);
                }
            });

        });

        $(document).on("click", ".rejectBtn", function (e) {
            var documentType = $(this).data('document-type');
            var documentId = $(this).data('document-id');
            var value = '';
            var type = '';
            if($('#customRejection'+documentId).prop('checked')){
                value = $('#custom_rejection_message'+documentId).val();
                type = 'custom';
            }
            if($('#templateRejection'+documentId).prop('checked')){
                value = $('#rejection_message_id'+documentId).val();
                type = 'template';
            }

            var url = $('#rejectUrl_'+documentId).val();
            $.ajax({
                type: 'POST',
                url: url,
                data: {
                    value: value,
                    type: type,
                    _token: "{{ csrf_token() }}",
                },
                success: function(response) {
                    Toast.success(response);
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                },
                error: function(xhr, status, error) {
                    console.error('Error toggling Document status:', error);
                }
            });

        });

        function handleSuccess() {
            Toast.success('Customer Document status updated Successfully');
            setTimeout(function() {
                $("#success-message").fadeOut("slow", function() {
                    $(this).remove();
                });
                setTimeout(function() {
                    location.reload();
                }, 500); // Reload after 0.5 seconds
            }, 2000); // Fade out after 2 seconds
        }
        $(document).on("click",".blockToggle", function() {
                var docId = $(this).attr('data-id');
                var isChecked = $(this).prop('checked');
                var status = isChecked ? 'blocked' : 'unblocked';
                $.ajax({
                    type: 'POST',
                    url: '{{ route("admin.block-customer-document") }}',
                    data: {
                        docId: docId,
                        status: status,
                        _token: "{{ csrf_token() }}",
                    },
                    success: handleSuccess,
                    error: function(xhr, status, error) {
                        console.error('Error toggling Document status:', error);
                    }
                });
            });
    });

    $(document).on("change", "#customer", function (e) {
        load_page_details(1);
    });

    function load_page_details(page){
      $('#customerDocumentsHtml').html('');
      var customerId = $('#customer').val();
        $.ajax({
              url: sitePath+"/admin/customer-documents-ajax",
              method:"GET",
              data:{
                pageno:page, 
                customerId:customerId,
              },
              beforeSend:function(){
                 // $('#customerDocumentsHtml').prepend('<span class="spinner-border" role="status" aria-hidden="true"></span>');
               },
              success:function(data)
              {
                $('#customerDocumentsHtml').html(data);
              }
        });
    }

    </script>
@endpush
