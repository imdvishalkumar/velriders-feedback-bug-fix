@extends('templates.admin')

@section('page-title')
    Send Email
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
                                <h1>Email Form</h1>
                            </div>
                            <form id="email-form" method="POST" action="{{ route('send-email.send') }}">
                                @csrf
                                <div class="row">
                                    <div class="col-lg-4 col-md-6">
                                        <label for="start_date">Start Date:</label>
                                        <input type="date" id="start_date" name="start_date">
                                    </div>
                                    <div class="col-lg-4 col-md-6">
                                        <label for="end_date">End Date:</label>
                                        <input type="date" id="end_date" name="end_date">
                                    </div>
                                    <div class="col-lg-4 col-md-12">
                                        <label for="apply-filter-btn">Search:</label>
                                        <button type="button" class="btn btn-sm btn-success" id="apply-filter-btn">Apply Filter</button>
                                    </div>
                                    <div class="col-lg-4 col-md-6">
                                        <label for="select-all">Select All:</label>
                                        <input type="checkbox" id="select-all">
                                    </div>
                                </div>
                                
                                <div class="select-container mb-3"> 
                                    <br>
                                    <label for="to">Select Customers:</label>
                                    <select id="to" name="to[]" multiple style="width: 100%">
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->email }}">{{ $customer->name }} - {{ $customer->email }} - {{ $customer->mobile_number }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <label for="subject">Subject:</label>
                                <input type="text" id="subject" name="subject" placeholder="Enter email subject" required>
                                <label for="content">Content:</label>
                                <textarea id="content" name="content" rows="6" placeholder="Write your email content here" required></textarea>
                                <button class="mt-3" type="submit" id="disableButton">Send Email</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @push('scripts')
    <script>
        $('#email-form').on('submit', function() {
            var submitButton = $('#disableButton');
            submitButton.prop('disabled', true);
            submitButton.text('Submitting...');
        });

        $(document).ready(function() {
            $("#to").select2();
            CKEDITOR.replace('content', {
                versionCheck: false,
            });

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
                        call_from: 1,
                    },
                    success: function(response) {
                        $('#to').empty();
                        response.forEach(function(customer) {
                            $('#to').append('<option value="' + customer.email + '">' + customer.full_name + ' - ' + customer.email + ' - ' + customer.mobile_number + '</option>');
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                    }
                });
            });

            $('#select-all').change(function() {
                if ($(this).is(':checked')) {
                    var options = $('#to option');
                    var values = $.map(options, function(option) {
                        return option.value;
                    });
                    $('#to').val(values).trigger('change');
                } else {
                    $('#to').val(null).trigger('change');
                }
            });
        });
    </script>
    @endpush
@endsection
