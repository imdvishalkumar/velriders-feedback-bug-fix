<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sample Invoice</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header img {
            max-width: 150px;
            height: auto;
        }

        .invoice-details {
            margin-bottom: 20px;
        }

        .invoice-details h1,
        .invoice-details h2 {
            margin: 5px 0;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
        }

        .invoice-table th,
        .invoice-table td {
            padding: 10px;
            border: 1px solid #ddd;
        }

        .invoice-table th {
            background-color: #f2f2f2;
            text-align: left;
        }

        .invoice-address {
            text-align: right;
        }

        .total-row td {
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <img src="" alt="Company Logo">
            <h1>FATMAN SERVICES</h1>
            <h2>24 - Gujarat<br>Phone No. 9824406456</h2>
            <!-- <img src="http://127.0.0.1:8000/images/mask.svg"> -->
        </div>
        <div class="invoice-address">
            <p>SHOP NO. 5 DWARKESH COMPLEX, BELOW SHIVHARI HOTEL,
                NEAR SAMARPAN OVER BRIDGE,
                JAMNAGAR - 361008, 24 - Gujarat
                9909927077, 9909727077, SHAILESHCARBIKE@GMAIL.COM, velrider.com</p>
            <h2>Retail Invoice</h2>
        </div>
        <table class="invoice-table">
            <thead>
                <thead>
                    <tr class="total-row">
                        <td colspan="3"></td>
                        <td colspan="6">Retail Invoice</td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="3">
                            <h2>FATMAN SERVICES</h2>
                            UNIT NO-329, THIRD FLOOR RAHEJA TESLA BLDG,
                            NO-1B TTC MIDC GEN-2/1/C PART EDISON TURBHE
                            MIDC, NAVI MUMBAI, 27 - Maharashtra, THANE,
                            400705 Phone No. 9987996535
                            GST No : 27AAACK6125D1ZW
                        </td>
                        <td colspan="6">
                            <div class="invoice-details">
                                <p>Invoice No. : S/141</p>
                                <p>Date : 10-02-2023</p>
                                <p>Reference No. : </p>
                                <p>Date : </p>
                            </div>
                        </td>

                    </tr>
                </thead>

                <tr>
                    <th>Sr</th>
                    <th>Particular</th>
                    <th>Qty.</th>
                    <th>Unit Rate</th>
                    <th>Disc.</th>
                    <th>GST %</th>
                    <th>GST Amt.</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>SEDAN RAJKOT TO JAMNAGAR 21-02-2023</td>
                    <td>1.00 PAC</td>
                    <td>2,000.00</td>
                    <td>12.00</td>
                    <td>240.00</td>
                    <td>2,240.00</td>
                    <td>1,000.00</td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="6"></td>
                    <td>Total</td>
                    <td>2,240.00</td>
                </tr>
                <tr class="total-row">
                    <td colspan="6"></td>
                    <td>Grand Total</td>
                    <td>2,240.00</td>
                </tr>
            </tfoot>
        </table>
    </div>
</body>

</html>