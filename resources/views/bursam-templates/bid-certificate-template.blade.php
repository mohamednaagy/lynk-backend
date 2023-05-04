<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ownership Certificate</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ccc;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .subtitle {
            font-size: 18px;
            margin-bottom: 10px;
        }

        .details {
            margin-bottom: 20px;
        }

        .details p {
            margin: 0;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .table th, .table td {
            padding: 10px;
            border: 1px solid #ccc;
            text-align: left;
            vertical-align: top;
        }

        .table th {
            background-color: #f2f2f2;
        }

        .table td:first-child {
            font-weight: bold;
        }

        .footer {
            text-align: center;
            font-size: 14px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <img src="logo.png" alt="Logo" width="100">
        <div class="title">Ownership Certificate</div>
        <div class="subtitle">Certificate No: CPO03MAY23-0000040-000</div>
        <p>This is to certify that the following transaction has been executed through the BURSA Suq Al-Sila' in
            accordance with the Rules of Bursa Malaysia Islamic Services Sdn. Bhd.</p>
    </div>
    <div class="details">
        <p>This is to certify that:</p>
        <table class="table">
            <tbody>
            <tr>
                <td>Buyer:</td>
                <td>{{ $buyer }}</td>
            </tr>
            <tr>
                <td>Owner:</td>
                <td>{{ $owner }}</td>
            </tr>
            <tr>
                <td>Bid No:</td>
                <td>{{ $bidno }}</td>
            </tr>
            <tr>
                <td>Total Value:</td>
                <td>{{ $totalvalue }} {{ $currency }}</td>
            </tr>
            <tr>
                <td>Price:</td>
                <td>{{ $price }} {{ $currency }}</td>
            </tr>
            <tr>
                <td>Price (MYR Equivalent):</td>
                <td>{{ $price_myr_equivalent }} MYR</td>
            </tr>
            <tr>
                <td>Purchase Time/Date:</td>
                <td>{{ $purchase_timedate }}</td>
            </tr>
            <tr>
                <td>Value Date:</td>
                <td>{{ $valuedate }}</td>
            </tr>
            <tr>
                <td>Product Name:</td>
                <td>{{ $pname }}</td>
            </tr>
            <tr>
                <td>Product Volume:</td>
                <td>{{ $pvolume }}</td>
            </tr>
            <tr>
                <td>Line:</td>
                <td>
                    <table>
                        <tbody>
                        <tr>
                            <td>Supplier:</td>
                            <td>{{ $line[0]['SUPPLIER'] }}</td>
                        </tr>
                        <tr>
                            <td>Volume:</td>
                            <td>{{ $line[0]['VOLUME'] }}</td>
                        </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
            </tbody>
        </table>
        <p>This certificate is non-transferable and must be presented upon request.</p>
    </div>
    <div class="footer">
        <p>Issued on 03 May 2023 by XYZ Company</p>
        <p>For inquiries, please contact us at info@xyzcompany.com</p>
    </div>
</div>
</body>
</html>
