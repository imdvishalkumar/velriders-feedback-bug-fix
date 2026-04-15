@extends('templates.admin')

@section('page-title')
    Policies
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
                                <h3 class="card-title">All Policies</h3>
                            </div>
                        </div>
                    </div>
                    
                    <!-- /.card-header -->
                    <div class="card-body">
                        <table id="policy" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Id</th>
                                    <th>Policy Title</th>
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

            function loadPolicies() {
                $.ajax({
                    type: "GET",
                    url: "{{ route('admin.get-all-policies') }}",
                    success: function(response) {
                        let html = '';
                        response.forEach((policy) => {
                            html += `<tr>
                                <td>${policy.policy_id}</td>
                                <td>${policy.title}</td>
                                <td>
                                    <a class="btn btn-sm btn-primary update-btn" href="policy/edit/${policy.policy_id}">Edit</a>
                                    <a class="btn btn-sm btn-danger reset-btn" href="policy/reset/${policy.policy_id}">Reset</a>
                                </td>
                            </tr>`;
                        });
                        $('.table-data').html(html);

                        $("#policy").DataTable({
                            "responsive": true,
                            "lengthChange": false,
                            "autoWidth": false,
                            "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
                        }).buttons().container().appendTo('#policy_wrapper .col-md-6:eq(0)');
                    }
                });
            }

          
            loadPolicies();
        });
    </script>

@endpush
