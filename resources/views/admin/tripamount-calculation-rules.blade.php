@extends('templates.admin')

@section('page-title')
    Trip Amount Calculation Rules
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
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header">
                        <div class="row">
                            <div class="col">
                                <h3 class="card-title">All Trip Calculations</h3>
                            </div>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <table id="tripCalculation" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Id</th>
                                    <th>Hours</th>
                                    <th>Multiplier</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody class="table-data">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title form-title">Add Trip Amount Calculation</h3>

                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <form class="card-body" id="trip-calculation-form">
                        @csrf
                        <input type="hidden" id="tripStoreId" name="tripStoreId">
                        <div class="form-group">
                            <label for="hours">Hours</label>
                            <input type="text" id="hours" name="hours" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="multiplier">Multiplier</label>
                            <input type="text" id="multiplier" name="multiplier" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-primary btn-submit btn-sm" href="#">
                            Add
                        </button>
                        <button type="button" class="btn d-none btn-danger btn-clear btn-sm" href="#">
                            Cancel
                        </button>
                    </form>
                </div>
                <!-- /.card -->
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
        const tableData = $('.table-data');
        const tripCalculation = $("#tripCalculation");

        function loadtripCalculations() {
            $.ajax({
                type: "GET",
                url: "{{ route('admin.get-trip-calculations') }}",
                success: function(response) {
                    var html = '';
                    response.forEach((trip) => {
                        html += `<tr>
                            <td>${trip.id}</td>
                            <td>${trip.hours}</td>
                            <td>${trip.multiplier}</td>
                            <td class="text-right">
                                <a class="btn btn-info btn-sm update-btn" data-operationId='${trip.id}'>
                                    <i class="fas fa-pencil-alt"></i> Edit</a>
                            </td>
                        </tr>`;
                    });
                    tableData.html(html);

                    tripCalculation.DataTable({
                        "responsive": true,
                        "lengthChange": false,
                        "autoWidth": false,
                        "pageLength": 50,
                        "buttons": ["copy", "csv", "excel","print", "colvis"]
                    }).buttons().container().appendTo('#tripCalculation_wrapper .col-md-6:eq(0)');
                }
            });
        }

        $(document).ready(function() {
            
            loadtripCalculations();
            setTimeout(function(){
                $("#success-message").fadeOut("slow", function(){
                    $(this).remove();
                });
            }, 2000); 
        });

        $(document).ready(function() {
            $('.btn-clear').click(function() {
                $('#trip-calculation-form').attr('data-updateId', '');
                $('.form-title').text('Add Trip Amount Calculation');
                $('.btn-submit').text('Add');
                $('.btn-clear').addClass('d-none');
                $('#trip-calculation-form')[0].reset();
            });
            $(document).on('click', ".update-btn", function() {
                let tripId = $(this).data('operationid');
                $('#tripStoreId').val(tripId);
                let loading = undefined;
                $.ajax({
                    url: sitePath + '/admin/get-trip-calculations',
                    type: "GET",
                    data: {
                        id: tripId
                    },
                    success: function(response) {
                        let trip = response;
                        $('#trip-calculation-form').attr('data-updateId', trip.id);
                        $('#hours').val(trip.hours);
                        $('#multiplier').val(trip.multiplier);
                        $('.form-title').text('Update Trip Amount Calculation');
                        $('.btn-submit').text('Update');
                        $('.btn-clear').removeClass('d-none');
                    }
                });
            });

            loadtripCalculations();

            $('#trip-calculation-form').validate({ 
                rules: {
                    hours: {required: true, number:true},
                    multiplier: {required: true, number:true},
                },
                messages :{
                        hours : { required : 'Please enter Hours' },
                        multiplier : { required : 'Please enter Multiplier' },
                },
                highlight: function (element) {
                    //console.log(element, element.type, element.tagName)
                    if ($(element).is('select') || $(element).is('input')) {
                        $(element).parent('.select-wrap').addClass('error');
                    } else {
                        $(element).addClass('error');
                    }
                },
                submitHandler: function (form) {
                    let loading = undefined;
                    let url = "{{ route('admin.create-trip-calculation') }}";
                    let method = "POST";
                    let tripId = $('#trip-calculation-form').attr('data-updateId');
                    let hours = $('#hours').val().trim(); // Get and trim the name field value
                    let multiplier = $('#multiplier').val().trim(); // Get and trim the name field value
                    if (tripId) {
                        url = "{{ route('admin.update-trip-calculation') }}";
                        method = "PUT";
                    }
                    let data = {
                        hours: hours, // Use the trimmed name value
                        multiplier: multiplier, 
                        _token: "{{ csrf_token() }}",
                    };
                    if (tripId) {
                        data.id = tripId;
                    }
                    $.ajax({
                        url: url,
                        type: method,
                        data: data,
                        success: function(response) {
                            Toast.open({
                                type: 'success',
                                message: response.message,
                                background: 'green',
                                duration: 3000,
                            });

                            loadtripCalculations();
                            $('#trip-calculation-form')[0].reset();
                            $('#trip-calculation-form').attr('data-updateId', '');
                            $('.form-title').text('Add Trip Amount Calculation');
                            $('.btn-submit').text('Add');
                            $('.btn-clear').addClass('d-none');
                        }
                    });
                }
            });

        });

    </script>
@endpush
