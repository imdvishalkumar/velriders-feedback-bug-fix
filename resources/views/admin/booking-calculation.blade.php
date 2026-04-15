@extends('templates.admin')

@section('page-title')
    Booking Calculation
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
                                <h3 class="card-title">All Bookings</h3>
                            </div>
                        </div>
                        <form id="filter-form">
                            <div class="row mt-3 ml-2">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="from_date">From Date</label> <span
                                            class="error">*</span>
                                        <input type="date" class="form-control" name="from_date"
                                            id="from_date">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="to_date">To Date</label> <span
                                            class="error">*</span>
                                        <input type="date" class="form-control" name="to_date"
                                            id="to_date">
                                    </div>
                                </div>
                                <div class="col-md-1 mt-4">
                                    <button type="submit" class="btn btn-primary p-3" id="searchDefaultBtn">Search</button>
                                    <button type="button" class="btn btn-primary p-3" id="searchActualBtn">Search</button>
                                </div>
                                <div class="col-md-1 mt-4">
                                    <button type="button" class="btn btn-danger p-3" id="clearBtn">Clear</button>
                                </div>
                            </div>
                        </form>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <table id="bookingCalculation" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Invoice No</th>
                                    <th>Booking Id</th>
                                    <th>Invoice Date</th>
                                    <th>Payment Mode</th>
                                    <th>Party  Name</th>
                                    <th>GSTN (IF Have)</th>
                                    <th>B2B/B2C</th>
                                    <th>GST %</th>
                                    <th>Taxable Value</th>
                                    <th>CGST</th>
                                    <th>SGST</th>
                                    <th>IGST (Out Of State)</th>
                                    <th>Convenience Fees Amount</th>
                                    <th>Convenience Fees GST</th>
                                    <th>Vehicle Commission Amount</th>
                                    <th>Vehicle Commission Tax</th>
                                    <th>Total Value</th>

                                   <!--  <th>Vehicle Details</th>
                                    <th>Pickup Date and Return Date</th>
                                    <th>Status</th>
                                    <th>Trip Amount</th>
                                    <th>Coupon Discount</th>
                                    <th>Convenience Fees</th>
                                    <th>Tax</th>
                                    <th>Total Price ((Trip Amount + Convenience Fees + Tax) - Coupon Discount)</th>
                                    <th>Refundable Amount</th>
                                    <th>Final Amount (Total Price + Refundable Amount)</th> -->
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
            $('#searchActualBtn').hide();
            const tableData = $('.table-data');
            const bookingCalculation = $("#bookingCalculation");

            function loadBookingCalculations(fromDate = '', toDate = '') {
                var callingUrl = '/admin/get-bookings?from_date='+fromDate+'&to_date='+toDate;
                $.ajax({
                    type: "GET",
                    // url: "{{ route('admin.get-bookings') }}",
                    url: sitePath + callingUrl,
                    success: function(response) {
                        let html = '';
                        var priceSummary = '';
                        response.forEach((vehicle) => {
                            var tripAmount = '';
                            var convenienceFee = '';
                            var totalPrice = '';
                            var couponDiscount = 0;
                            var couponCode = '';
                            var calculation = '';
                            var displayTax = 0;
                            var tax = 0;
                            var penaltyDetails = 0;
                            var final_amount = 0;
                            var details = '';
                            var refundableDeposit = 0;
                            var cGSTsGST = '';
                            var cGSTsGSTPercent = '';
                            var iGST = 0;
                            var gstPercent = 5;
                            var taxableAmount = 0;
                            var customerDetails = '';
                            var vehicleDetails = '';
                            var customerGst = '';
                            var b2bb2c = 'B2C';
                            var bookingDate = '-';
                            var convenienceFeesAmount = 0;
                            var convenienceFeesGST = 0;
                            var lastAmt = 0;
                            var sequenceNo = '-';
                            var paymentMode = '-';

                            if(vehicle.finalAmt){
                                lastAmt = vehicle.finalAmt;
                            }
                            /*if(vehicle.cDetails){
                                details = vehicle.cDetails;
                            }*/
                            
                            var multiplier = vehicle.multiplier;
                            var rentalPrice = vehicle.vehicle.rental_price;
                            var hours = vehicle.hours;
                            var tripHours = vehicle.tripDurationInHours;
                            tax = vehicle.tax ? vehicle.tax : 0;
                           /* if(details != ''){
                                tripAmount = details.trip_amount?details.trip_amount : 0;
                                couponDiscount = details.coupon?details.coupon : 0;
                                couponCode = details.discount_code?details.discount_code : '';
                                convenienceFee = details.convenience_fee ? details.convenience_fee : 0;
                                totalPrice = details.total_price ? details.total_price : 0;
                                refundableDeposit = details.refundable_deposit ? details.refundable_deposit : 0;
                                tax = details.tax_amount ? details.tax_amount : 0;
                                final_amount = details.final_amount ? details.final_amount : 0;
                            }*/
                            if(vehicle.customer.gst_number != null){
                                //customerDetails += ' <b>GST Number - </b>' + vehicle.customer.gst_number;    
                                customerGst += vehicle.customer.gst_number;    
                                b2bb2c = "B2B";
                                gstPercent = 12;
                            }

                            if(vehicle.customer && tax != 0){
                                //newtax = tax.replace("₹ ", "");      
                                //tax = newtax.replace(/,/g , '');    
                                var gst = '';
                                lastAmt = parseFloat(lastAmt) + parseFloat(tax) + parseFloat(vehicle.vehicleCommissionTaxAmt) + parseFloat(vehicle.vehicleCommissionAmt); 
                                lastAmt = Math.round(lastAmt);
                                if(vehicle.customer.gst_number){
                                    gst = vehicle.customer.gst_number.startsWith(24);
                                }
                                    
                                if(vehicle.customer.gst_number == null){
                                    tax = parseFloat(tax).toFixed(2);
                                   
                                    var gstTax = tax / 2;
                                    displayTax = "<b>CGST</b> - "+parseFloat(gstTax).toFixed(2)+" <br/><b>SGST</b> - "+parseFloat(gstTax).toFixed(2)+"<br/><b>Total Tax</b> - "+parseFloat(tax).toFixed(2);         
                                    cGSTsGST = parseFloat(gstTax).toFixed(2);
                                    cGSTsGSTPercent = gstPercent / 2;

                                    cGSTsGST = cGSTsGST + "("+cGSTsGSTPercent+"%)";
                                }   
                                else if(gst && tax != 0){
                                    tax = parseFloat(tax).toFixed(2);
                                    var gstTax = tax / 2;
                                    displayTax = "<b>CGST</b> - "+parseFloat(gstTax).toFixed(2)+" <br/><b>SGST</b> - "+parseFloat(gstTax).toFixed(2)+"<br/><b>Total Tax</b> - "+parseFloat(tax).toFixed(2);     
                                    cGSTsGST = parseFloat(gstTax).toFixed(2);  
                                    cGSTsGSTPercent = gstPercent / 2;
                                    cGSTsGST = cGSTsGST + "("+cGSTsGSTPercent+"%)";
                                }
                                else if(!gst && tax != 0){
                                    displayTax = "<b>IGST</b> - "+parseFloat(tax).toFixed(2);
                                    iGST = parseFloat(tax).toFixed(2);
                                    cGSTsGSTPercent = gstPercent;
                                    iGST = iGST + "("+cGSTsGSTPercent+"%)";
                                }
                            }
                            if(vehicle.invoiceDate){
                                bookingDate = vehicle.invoiceDate;
                            }
                            if(vehicle.taxableAmount){
                                taxableAmount = vehicle.taxableAmount;
                                /*if(couponDiscount){
                                    if(taxableAmount > couponDiscount){
                                        taxableAmount -= couponDiscount;    
                                    }
                                    if(lastAmt > couponDiscount){
                                        lastAmt -= couponDiscount;
                                    }
                                }*/
                            }

                            if(vehicle.convenienceFeesAmount){
                                convenienceFeesAmount = vehicle.convenienceFeesAmount;
                            }
                            if(vehicle.convenienceFeesGST){
                                convenienceFeesGST = vehicle.convenienceFeesGST;
                            }

                            if(vehicle.customer.firstname != null && vehicle.customer.lastname != null){
                                customerDetails += vehicle.customer.firstname +' '+vehicle.customer.lastname+'<br/>';
                            }
                            if(vehicle.sequence_no){
                                sequenceNo = vehicle.sequence_no;
                            }
                            if(vehicle.paymentMode){
                                paymentMode = vehicle.paymentMode;
                            }
                            /*if(vehicle.customer.email != null){
                                customerDetails += ' <b>Email - </b>' + vehicle.customer.email + '<br/>';
                            }
                            if(vehicle.customer.mobile_number != null){
                                customerDetails += ' <b>Mobile No. - </b>' + vehicle.customer.mobile_number + '<br/>';    
                            }*/
                            
                            /*calculation += '<b>&nbsp; Trip Amount </b>( '+tripAmount+' ) <br/>';
                            calculation += '&nbsp; Trip Amount = (Multiplier ('+multiplier+') * Rental Price ('+rentalPrice+')) / Hours ('+hours+') * Trip Hours ('+tripHours+') <br/><br/>';
                            calculation += '<b>+ Convenience Fee </b>( '+convenienceFee+' )<br/>';
                            calculation += '<b>+ Tax </b>( '+displayTax+' )<br/><hr/>';
                            calculation += '<b>- Coupon Discount - </b>( '+couponDiscount+' )<br/>';
                            calculation += '&nbsp; Coupon Code - '+couponCode+' <br/><hr/>';
                            calculation += '<b>&nbsp; Total Amount </b>( '+totalPrice+' ) <br/>';
                            calculation += '<b>+ Refundable Amount </b>( '+refundableDeposit+' )<br/><hr/>';
                            if(details != ''){
                                calculation += '<b>Final Amount </b>( '+details.final_amount+' )';
                            }*/

                            html += `<tr>
                                <td>${sequenceNo}</td>
                                <td>${vehicle.booking_id}</td>
                                <td>${bookingDate}</td>
                                <td>${paymentMode}</td>
                                <td>${customerDetails}</td>
                                <td>${customerGst}</td>
                                <td>${b2bb2c}</td>
                                <td>${gstPercent}%</td>
                                <td>${taxableAmount}</td>
                                <td>${cGSTsGST}</td>
                                <td>${cGSTsGST}</td>
                                <td>${iGST}</td>
                                <td>${convenienceFeesAmount}</td>
                                <td>${convenienceFeesGST}</td>
                                <td>${vehicle.vehicleCommissionAmt}</td>
                                <td>${vehicle.vehicleCommissionTaxAmt}</td>
                                <td>${lastAmt}</td>

                                {{--<td>${vehicle.vehicle.vehicle_name}</td>
                                <td>${vehicle.pickup_date_formatted} - ${vehicle.return_date_formatted}</td>
                                <td>${capitalize(vehicle.status)}</td>
                                <td>${tripAmount}</td>
                                <td>${couponDiscount}</td>
                                <td>${convenienceFee}</td>
                                <td>${displayTax}</td>
                                <td>${totalPrice}</td>
                                <td>${refundableDeposit}</td>
                                <td>${final_amount}</td>--}}
                            </tr>`;
                        });
                        
                        // Destroy existing DataTable instance to reflact filters
                        if ($.fn.DataTable.isDataTable(bookingCalculation)) {
                            bookingCalculation.DataTable().clear().destroy();
                        }

                        tableData.html(html);

                        bookingCalculation.DataTable({
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
                        }).buttons().container().appendTo('#bookingCalculation_wrapper .col-md-6:eq(0)');
                    }
                });
            }

            function capitalize(str) {
                return str.charAt(0).toUpperCase() + str.slice(1);
            }
            
            loadBookingCalculations();

            setTimeout(function(){
                $("#success-message").fadeOut("slow", function(){
                    $(this).remove();
                });
            }, 2000); 

            $(document).on("click","#searchActualBtn",function() {
                var fromDate = $('#from_date').val();
                var toDate = $('#to_date').val();
                loadBookingCalculations(fromDate, toDate);
            });

            $(document).on("click","#clearBtn",function() {
                var fromDate = $('#from_date').val('');
                var toDate = $('#to_date').val('');
                if ($.fn.DataTable.isDataTable(bookingCalculation)) {
                    bookingCalculation.DataTable().clear().destroy();
                }
                //loadBookingCalculations();
            }); 

            jQuery.validator.addMethod("greaterThan", 
                function(value, element, params) {

                    if (!/Invalid|NaN/.test(new Date(value))) {
                        return new Date(value) > new Date($(params).val());
                    }
                    return isNaN(value) && isNaN($(params).val()) || $(params).val() != '' && (Number(value) > Number($(params).val())); 
                },'Must be greater than {0}.');

            $('#filter-form').validate({ 
               rules: {
                  from_date: {required: true},
                  to_date: {required: true, greaterThan: "#from_date"},
               },
               messages :{
                    from_date : { required : 'Please select From Date' },
                    to_date : { required : 'Please enter To Date', greaterThan: 'To date must be greater than From date' },
                },
                highlight: function (element) {
                    //console.log(element, element.type, element.tagName)
                    if ($(element).is('select') || $(element).is('input')) {
                        $(element).parent('.select-wrap').addClass('error');
                    } else {
                        $(element).addClass('error');
                    }
                },
                submitHandler: function (form, event) {
                   event.preventDefault();
                   $('#searchDefaultBtn').hide();
                   $('#searchActualBtn').show();
                   $('#searchActualBtn').trigger('click');
                }
            });

        });
    </script>
@endpush
