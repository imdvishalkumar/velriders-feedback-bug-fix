<html lang="en">
<head>              
    <title>Test Page</title>
</head>         
<body>
    <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4">Test Payment</h1>
        
        <form id='myForm' name='myForm' action='' method='post' enctype="application/x-www-form-urlencoded">
            <input type="hidden" name="merchantID" value="{{$merchantID}}" />
            <input type="hidden" name="merchantTxnNo" value="{{$merchantTxnNo}}" />
            <input type="hidden" name="amount" value="{{$amount}}" />
            <input type="hidden" name="currencyCode" value="{{$currencyCode}}" />
            <input type="hidden" name="payType" value="{{$payType}}" />
            <input type="hidden" name="customerEmailId" value="{{$customerEmailId}}" />
            <input type="hidden" name="transactionType" value="{{$transactionType}}" />
            <input type="hidden" name="returnURL" value="{{$returnURL}}" />
            <input type="hidden" name="txnDate" value="{{$txnDate}}" />
            <input type="hidden" name="customerMobileNo" value="{{$customerMobileNo}}" />
            <input type="hidden" name="secureHash" value="{{$secureHash}}" />
        </form>

    </div>
</body>
</html>