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
        <img src="{{ 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('logo.png'))) }}" alt="Logo" width="150">
        <div class="title">Ownership Certificate</div>
        <div class="subtitle">Certificate No: {{$e_cert_no}}</div>
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
                <td>Seller:</td>
                <td>{{ $seller }}</td>
            </tr>
            <tr>
                <td>Total Value:</td>
                <td>{{ $total_value }} {{ $currency }}</td>
            </tr>
            <tr>
                <td>Murabha Value:</td>
                <td>{{ $murabaha_value }} {{ $currency }}</td>
            </tr>
            <tr>
                <td>Product Price:</td>
                <td>{{ $price }} {{ $currency }}</td>
            </tr>
            <tr>
                <td>Product Price (MYR Equivalent):</td>
                <td>{{ $price_myr_equivalent }} MYR</td>
            </tr>
            <tr>
                <td>Reporting Time/Date:</td>
                <td>{{ $reporting_time_date }}</td>
            </tr>
            <tr>
                <td>Value Date:</td>
                <td>{{ $value_date }}</td>
            </tr>
            <tr>
                <td>Product Name:</td>
                <td>{{ $p_name }}</td>
            </tr>
            <tr>
                <td>Product Volume:</td>
                <td>{{ $p_volume }}</td>
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
    </div>
</div>
</body>
</html>
