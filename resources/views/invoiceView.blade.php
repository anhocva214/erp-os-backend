<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title>Invoice</title>
    <link
            href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap"
            rel="stylesheet"
    />
    <style>

        .wrapper {
            display: grid;
            grid-template-columns:2fr 3fr 2fr;
            font-size: 12px;
            padding: 10px 0;
            width: 1000px;
            max-width: 90%;
            margin: 0 auto;
            grid-auto-rows: minmax(70px, auto);
        }

        .wrapper > div {
            padding: 0.6em;
        }

        .box1 {
            grid-column: 1;
            grid-row: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .box2 {
            grid-column: 2;
            grid-row: 1;
            text-align: center;
        }

        .box3 {
            grid-column: 3;
            grid-row: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .box4 {
            grid-column: 1/4;
            grid-row: 2;
            text-align: center;
        }

        .box5 {
            grid-column: 1/2;
            grid-row: 3;
        }

        .box6 {
            grid-column: 3/4;
            grid-row: 3;
        }

        .box7 {
            justify-content: stretch;
            grid-column: 1/4;
            grid-row: 4;
        }

        .box8 {
            grid-column: 1/2;
            grid-row: 6;
        }

        .box9 {
            grid-column: 3/4;
            grid-row: 5;
        }

        .box10 {
            grid-column: 3/4;
            grid-row: 10;
        }

        .box11 {
            grid-column: 1/2;
            grid-row: 10;
        }

        .box12 {
            grid-column: 1/4;
            grid-row: 11;
            text-align: center;
        }

        .box13 {
            grid-column: 1/3;
            grid-row: 5;
        }

        .box14 {
            grid-column: 1/4;
            grid-row: 7;
        }

        .footer {
            position: fixed;
            left: 180;
            bottom: 0;
            text-align: center;
            width: 1000px;
            max-width: 90%;
        }

        body {
            font-family: "Inter", sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table.table1,
        table.table1 th,
        table.table1 td {
            border: 1px solid silver;
        }

        table.table1 th,
        table.table1 td {
            text-align: left;
            padding: 5px;
        }

        table.table1 tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        table.table2 {
            border-collapse: collapse;
            width: 100%;
        }

        table.table2 th, table.table2 td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        hr.hr1 {
            border-top: 1px dotted rgb(63, 63, 63);
        }

    </style>
    <base href="{{ env('APP_URL') }}">
</head>
<body>
<div class="wrapper">

    <div class="box1">
        <img src={{env('APP_URL') . ':' . '8000' . '/files/' . $mailData['company']['logo']}} width="100"  alt="">
    </div>

    <div class="box2">

        <h1>{{$mailData['company']['companyName']}}</h1>
        <p>{{$mailData['company']['address']}}</p>
        <p>{{$mailData['company']['phone']}}</p>
        <p>{{$mailData['company']['email']}}</p>
    </div>

    <div class="box3">
        <img src={{$mailData['company']['logo']}} width="100" alt="">
    </div>

    <div class="box4">
        <hr class="hr1">
        <h3 class="center">INVOICE</h3>
        <hr class="hr1">
    </div>

    <div class="box5">
        <table class="table2">
            <tr>
                <th>Client Name</th>
                <td>{{ $mailData['customerName'] }}</td>
            </tr>
            <tr>
                <th>Address</th>
                <td>{{ $mailData['customerAddress'] }}</td>
            </tr>
            <tr>
                <th>Email</th>
                <td>{{ $mailData['customerEmail'] }}</td>
            </tr>
        </table>
    </div>

    <div class="box6">
        <table class="table2">
            <tr>
                <th>Invoice No</th>
                <td>{{$mailData['invoiceId']}}</td>
            </tr>
            <tr>
                <th>Invoice Date</th>
                <td>{{$mailData['invoiceDate']}}</td>
            </tr>
            <tr>
                <th>Sales Person</th>
                <td>{{$mailData['salePerson']}}</td>
            </tr>
        </table>
    </div>

    <div class="box7">
        <table class="table1">
            <thead>
            <th>Product Name</th>
            <th>Qty</th>
            <th>Price</th>
            <th>Tax</th>
            <th>Subtotal</th>
            </thead>
            <tbody>
            @foreach($mailData['productNames'] as $key => $productName)
                <tr>
                    <td>{{ $productName }}</td>
                    <td>{{ $mailData['productQuantities'][$key] }}</td>
                    <td>{{ $mailData['productPrices'][$key] }}</td>
                    <td>{{ $mailData['productVats'][$key] }}%</td>
                    <td>{{ $mailData['subtotal'][$key] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>



    <div class="box9">
        <table class="table2">
            <tr>
                <th>Discount (-)</th>
                <td>{{ $mailData['discountAmount'] }}</td>
            </tr>
            <tr>
                <th>Vat (+)</th>
                @foreach($mailData['govTax'] as $key => $tax)
                    <td>{{ $tax['percentage'] }}%</td>
                @endforeach
            </tr>
            <tr>
                <th>Grand total</th>
                <td>{{ $mailData['totalAmount'] }}</td>
            </tr>
            <tr>
                <th>Paid</th>
                <td>{{$mailData['paidAmount']}}</td>
            </tr>
            <tr>
                <th>Due</th>
                <td>{{$mailData['dueAmount']}}</td>
            </tr>
        </table>
    </div>

    <div class="box10">
        <hr>
        <p>Received By</p>
    </div>

    <div class="box11">
        <hr>
        <p>Authorized By</p>
    </div>

    <div class="box12">
        <hr>
        <p>Powered by OMEGA SOLUTION | Contact: 01885 996601</p>
    </div>

    <div class="box13">
        <p><b>In Words: </b>{{ numberToWords($mailData['totalAmount']) }}</p>
        <p><b>Notes: </b>{{ $mailData['note'] }}</p>
    </div>
</div>
</body>
</html>