@extends('templates.admin')

@section('page-title')
    Send Notification
@endsection

@section('content')
 <style>
        /* Reset styles */
        body, html {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            font-size: 16px;
            line-height: 1.6;
        }
        /* Container */
        .container {
            display: flex;
            flex-direction: column; /* Change to column for responsiveness */
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ccc;
        }
        /* Side Menu */
        .side-menu {
            flex: 1;
            background-color: #f4f4f4;
            padding: 20px;
        }
        /* Main Content */
        .main-content {
            flex: 3;
            padding: 20px;
        }
        /* Header */
        .header {
            background-color: #f4f4f4;
            padding: 10px;
            text-align: center;
        }
        /* Footer */
        .footer {
            background-color: #f4f4f4;
            padding: 10px;
            text-align: center;
        }
        /* Form */
        form {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 10px;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
        }
        button {
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            border: none;
            cursor: pointer;
            display: block; /* Make button block-level for responsiveness */
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #007bff;
        }
    </style>
    <section class="content">
        @if (session('success'))
            <div id="success-message" class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div id="success-message" class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="container">
                        <div class="main-content">
                            <div class="header">
                                <h1>Notification Form</h1>
                            </div>
                            <form id="notification-form" method="POST" action="{{ route('admin.send-push-notification') }}">
                                @csrf
                                <div class="row mb-3">
                                    <div class="col-lg-3 col-md-6">
                                        <label for="start_date">Start Date:</label>
                                        <input type="date" id="start_date" name="start_date" class="form-control">
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <label for="end_date">End Date:</label>
                                        <input type="date" id="end_date" name="end_date" class="form-control">
                                    </div>
                                    <div class="col-lg-2 col-md-6">
                                        <label for="apply-filter-btn">Search:</label>
                                        <button type="button" class="btn btn-sm btn-success" id="apply-filter-btn">Apply Filter</button>
                                    </div>
                                    <div class="col-lg-2 col-md-6">
                                        <label for="select-all">Select All:</label>
                                        <input type="checkbox" id="select-all" name="selectall">
                                    </div>
                                    <div class="col-lg-2 col-md-6" id="showGuest">
                                        <label for="show-guest">Show to Guest User:</label>
                                        <input type="checkbox" id="show-guest" name="showguest">
                                    </div>
                                </div>

                                <div class="select-container mb-3">
                                    <br>
                                    <label for="to">Select Customers:</label>
                                    <select id="to" name="to[]" multiple class="form-control" style="width: 100%;">
                                        <option value="0">ALL CUSTOMERS</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->mobile_number }}">{{ $customer->name }} - {{ $customer->email }} - {{ $customer->mobile_number }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <label for="title">Title:</label>
                                <input type="text" id="title" name="title" placeholder="Enter Notification Title" class="form-control">
                                <label for="content">Body:</label>
                                <textarea id="content" name="content" rows="4" placeholder="Write your Notification content here" class="form-control"></textarea>
                                <button type="submit" id="notificationBtn" class="btn btn-primary mt-3">Send Notification</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
   <!--  <section class="content">
        @if (session('success'))
        <div id="success-message" class="alert alert-success">
            {{ session('success') }}
        </div>
        @endif
        @if (session('error'))
        <div id="success-message" class="alert alert-danger">
            {{ session('error') }}
        </div>
        @endif
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="container">
                        <div class="main-content">
                            <div class="header">
                                <h1>Notification Form</h1>
                            </div>
                            <form id="notification-form" method="POST" action="{{ route('admin.send-push-notification') }}">
                                @csrf
                                <div class="row mb-3">
                                    <div class="col-lg-3">
                                        <label for="start_date">Start Date:</label>
                                        <input type="date" id="start_date" name="start_date">
                                    </div>
                                    <div class="col-lg-3">
                                        <label for="end_date">End Date:</label>
                                        <input type="date" id="end_date" name="end_date">
                                    </div>
                                    <div class="col-lg-2">
                                        <label for="apply-filter-btn">Search:</label>
                                        <a class="btn btn-sm btn-success" id="apply-filter-btn">Apply Filter</a>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="select-all">Select All:</label>
                                        <input type="checkbox" id="select-all" name="selectall">
                                    </div>
                                    <div class="col-md-2" id="showGuest">
                                        <label for="show-guest">Is Show to Guest User ?:</label>
                                        <input type="checkbox" id="show-guest" name="showguest">
                                    </div>
                                </div>
                            
                                <div class="select-container mb-3"> 
                                    <br>
                                    <label for="to">Select Customers:</label>
                                    <select id="to" name="to[]" multiple style="width: 750px">
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->mobile_number }}">{{ $customer->name }} - {{ $customer->email }} - {{ $customer->mobile_number }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <label for="title">Title:</label>
                                <input type="text" id="title" name="title" placeholder="Enter Notification Title">
                                <label for="content">Body:</label>
                                <textarea id="content" name="content" rows="4" placeholder="Write your Notification content here"></textarea>
                                <button type="submit" id="notificationBtn">Send Notification</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section> -->
@endsection
@push('scripts')
<script>
$(document).ready(function() {
    $('#showGuest').hide();
    $("#to").select2();
    
    $('#apply-filter-btn').click(function(e) {
        e.preventDefault();
        var startDate = $('#start_date').val();
        var endDate = $('#end_date').val();

        $.ajax({
            url: '{{ route("admin.email.filter-data") }}',
            type: 'GET',
            data: {
                start_date: startDate,
                end_date: endDate,
                call_from:2,
            },
            success: function(response) {
                $('#to').empty();
                response.forEach(function(customer) {
                    $('#to').append('<option value="' + customer.mobile_number + '">' + customer.full_name + ' - ' + customer.email + ' - ' + customer.mobile_number + '</option>');
                });
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
            }
        });
    });

    // $('#notification-form').on('submit', function() {
    //     var submitButton = $('#notificationBtn');
    //     submitButton.prop('disabled', true);
    //     submitButton.text('Submitting...');
    // });
    /*$('#select-all').change(function() {
        if ($(this).is(':checked')) {
            $('#to option').prop('selected', true);
        } else {
            $('#to option').prop('selected', false);
        }
    });*/
    $('#select-all').change(function() {
        if ($(this).prop('checked')==true){ 
            $('#showGuest').show();
        } else{
            $('#showGuest').hide();
        }
        // RESTRICT LOAD ALL CUSTOMERS ON DROPDOWN
        if ($(this).is(':checked')) {
            var options = $('#to option');
            // var values = $.map(options, function(option) {
            //     return option.value;
            // });
            values = 0;
            $('#to').val(values).trigger('change');
        }else{
            $('#to').val(null).trigger('change');
        }
    });

});

$('#notification-form').validate({ 
   rules: {
      'to[]': {required: true},
      title: {required: true},
      content: {required: true, maxlength:1000},
   },
   messages :{
        'to[]': { required : 'Please select Mobile Number' },
        title : { required : 'Please enter Title' },
        content : { required : 'Please enter Body Content' },
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
        var submitButton = $('#notificationBtn');
        submitButton.prop('disabled', true);
        submitButton.text('Submitting...');
        form.submit(); // Submits the form
    }
});
</script>
@endpush