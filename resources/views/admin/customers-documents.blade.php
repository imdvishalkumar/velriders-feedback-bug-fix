@extends('templates.admin')

@section('page-title')
    Customers Documents
@endsection

@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">All Customers Documents</h3>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <table id="example1" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Id</th>
                                    <th>Customer Name</th>
                                    <th>Document Type</th>
                                    <th>Document Number</th>
                                    <th>Document Front Url</th>
                                    <th>Document Back Url</th>
                                    <th>Expire Date</th>
                                    <th>Approved By</th>
                                    <th>Status</th>
                                    <th>Created</th>
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
            function loadDocuments() {
                $.ajax({
                    url: "{{ route('admin.get-all-documents') }}",
                    type: "GET",
                    beforeSend: function() {
                        $('.table-data').html(
                            '<tr><td colspan="5" class="text-center">Loading...</td></tr>');
                    },
                    success: function(response) {
                        if(response.data.length === 0) {
                            $('.table-data').html(
                                '<tr><td colspan="10" class="text-center">No data found</td></tr>');
                            return;
                        }

                        let documentsDetails = response.data;
                        let html = '';
                        documentsDetails.forEach((document, index) => {
                            html += `<tr>
                                <td>${index + 1}</td>
                                <td>${document.customer.firstname} ${document.customer.lastname}</td>
                                <td>${document.document_type}</td>
                                <td>${document.id_number}</td>
                                <td><a href="${document.document_image_url}" target="_blank">Click to see</a></td>
                                <td><a href="${document.document_back_image_url}" target="_blank">Click to see</a></td>
                                <td>${document.expiry_date}</td>
                                <td>${document.approved_by == null ? "-" : document.approved_by.username}</td>
                                <td><button id="btn-toggle" class="${document.is_approved == 0 ? "btn btn-success" : "btn btn-danger"}">
                                    ${document.is_approved == 0 ? "Click to Approve" : "Click to Disapprove"}
                                    </button></td>
                                <td>${new Date(document.created_at).toDateString()}</td>
                            </tr>`;
                        });

                        $('.table-data').html(html);
                        $("#example1").DataTable({
                            "responsive": true,
                            "lengthChange": false,
                            'searching': false,
                            "autoWidth": false,
                            "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
                        }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
                    }
                });
            }

            $(document).on('click', '#btn-toggle', function() {
                let documentId = $(this).closest('tr').find('td').eq(0).text();
                let status = $(this).text().trim() === "Click to Approve" ? 0 : 1;
                $.ajax({
                    url: "{{ route('admin.toggle-document-status') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        document_id: documentId,
                        status: status
                    },
                    success: function(response) {
                        if(response.status === true) {
                            Toast.success(response.message);
                            loadDocuments();
                        } else {
                            Toast.error(response.message);
                        }
                    },
                    error: function(err) {
                        console.log(err);
                        Toast.error("Something went wrong");
                    }
                });
            });
            
            loadDocuments();
        });
    </script>
@endpush
