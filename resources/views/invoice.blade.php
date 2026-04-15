<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 14px;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: auto;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .details, .footer {
            margin-top: 20px;
        }
        .details table, .items table {
            width: 100%;
            border-collapse: collapse;
        }
        .details td, .items th, .items td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .items th {
            background-color: #f2f2f2;
        }
        .footer {
            font-size: 12px;
            text-align: center;
            margin-top: 40px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Invoice</h1>
        </div>

        <div class="details">
            <table>
                <tr>
                    <td><strong>Invoice Number:</strong> {{ $invoiceData['invoice_number'] }}</td>
                    <td><strong>Date:</strong> {{ $invoiceData['date'] }}</td>
                </tr>
                <tr>
                    <td><strong>Company:</strong> {{ $invoiceData['company']['name'] }}</td>
                    <td><strong>Customer:</strong> {{ $invoiceData['customer']['name'] }}</td>
                </tr>
                <tr>
                    <td>{{ $invoiceData['company']['address'] }}</td>
                    <td>{{ $invoiceData['customer']['address'] }}</td>
                </tr>
            </table>
        </div>

        <div class="items">
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoiceData['items'] as $item)
                        <tr>
                            <td>{{ $item['description'] }}</td>
                            <td>{{ $item['quantity'] }}</td>
                            <td>{{ $item['unit_price'] }}</td>
                            <td>{{ $item['total'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align: right;"><strong>Grand Total:</strong></td>
                        <td>{{ $invoiceData['grand_total'] }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="footer">
            <p>Thank you for your business!</p>
            <p>If you have any questions about this invoice, please contact [Your Company Contact Information]</p>
        </div>
    </div>
</body>
</html>
