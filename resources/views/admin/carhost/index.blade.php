@extends('templates.admin')

@section('page-title')
    CarHost
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
                                <h3 class="card-title">All CarHost</h3>
                            </div>
                            
                            <div class="col" style="text-align: right;">
                                <a href="{{ route('admin.carhost.create') }}" class="btn btn-primary">Add CarHost</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- /.card-header -->
                    <div class="card-body">
                        <table id="carHostTable" class="table table-bordered table-striped table-responsive">
                            <thead>
                                <tr>
                                    <th>Id</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Mobile Number</th>
                                    <th>D.O.B</th>
                                    <th>Profile Picture</th>
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

            function loadCarHost() {
                $.ajax({
                    type: "GET",
                    url: "{{ route('admin.get-all-carhost') }}",
                    success: function(response) {
                        carhost = response;
                        let html = '';
                        carhost.forEach((carhost) => {
                            var email = '';
                            var name = '';
                            var profile = '';
                            if(carhost.email != '' && carhost.email != null)
                                email = carhost.email;
                            if(carhost.firstname != '' && carhost.firstname != null){
                               name += carhost.firstname;
                            }
                            if(carhost.lastname != '' && carhost.lastname != null){
                               name += ' '+carhost.lastname;
                            }
                            if(carhost.profile_picture_url != '' && carhost.profile_picture_url != null){
                                var imageUrl = carhost.profile_picture_url;
                                profile = `<img src="${imageUrl}" class="img-fluid" width="150px" height="150px">`;
                            }
                            html += `<tr>
                                <td>${carhost.id}</td>
                                <td>${name}</td>
                                <td>${email}</td>
                                <td>${carhost.mobile_number}</td>
                                <td>${carhost.dob}</td>
                                <td>${profile}</td>
                                <td><a href="javascript:void(0);" data-url="{{ url('/admin/carhost-edit') }}/${carhost.id}" data-id="${carhost.id}" class="editCarHost"><i class="fas fa-edit"></i></td>
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
            loadCarHost();

            $(document).on("click",".editCarHost",function() {
                var editUrl = $(this).attr('data-url');
                window.location.href = editUrl;
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
