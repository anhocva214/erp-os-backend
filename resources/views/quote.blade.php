<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title>Quote</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet"/>
    <style>
        body {
            font-family: "Inter", sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            background-color: #f4f4f4;
        }

        .wrapper {
            display: grid;
            grid-template-columns: 1fr 2fr 1fr;
            font-size: 14px;
            width: 80%;
            margin: 20px auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .box1, .box3 {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .box2 {
            text-align: center;
            padding: 20px;
        }

        .box4 {
            grid-column: 1/4;
            grid-row: 2;
            text-align: center;
            padding: 20px;
            border-bottom: 1px solid #ddd;
        }

        .box5, .box6, .box9 {
            padding: 20px;
        }

        .box7 {
            padding: 20px;
            border-top: 1px solid #ddd;
            border-bottom: 1px solid #ddd;
        }

        .box10, .box11, .box12, .box13 {
            text-align: center;
            padding: 20px;
        }

        .footer {
            text-align: center;
            padding: 10px;
            background-color: #333;
            color: #fff;
            position: fixed;
            bottom: 0;
            width: 100%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table.table1, table.table1 th, table.table1 td {
            border: 1px solid #ddd;
        }

        table.table1 th, table.table1 td {
            text-align: left;
            padding: 12px;
        }

        table.table1 tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        table.table2 {
            border-collapse: collapse;
            width: 100%;
        }

        table.table2 th, table.table2 td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        hr.hr1 {
            border-top: 1px dotted #ddd;
        }

        h1, h3 {
            margin: 0;
        }

        p {
            margin-bottom: 10px;
        }

        img {
            max-width: 100%;
            height: auto;
        }

        .center {
            text-align: center;
        }

        .in-words {
            font-weight: bold;
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
        <h3 class="center">Quote</h3>
        <hr class="hr1">
    </div>

    <div class="box5">
        <table class="table2">
            <tr>
                <th>Quote Name</th>
                <td>{{ $mailData['quoteName'] }}</td>
            </tr>
            <tr>
                <th>Quotes Owner</th>
                <td>{{$mailData['qouteOwner']}}</td>
            </tr>
            <tr>
                <th>Qoute Date</th>
                <td>{{ $mailData['qouteDate'] }}</td>
            </tr>
            <tr>
                <th>Expiration Date</th>
                <td>{{$mailData['expirationDate']}}</td>
            </tr>
        </table>
    </div>
    

    <div class="box7">
        <table class="table1">
            <thead>
            <th>Product Name</th>
            <th>Qty</th>
            <th>Unit Price</th>
            </thead>
            <tbody>
            @foreach($mailData['productNames'] as $key => $productName)
                <tr>
                    <td>{{ $productName }}</td>
                    <td>{{ $mailData['productQuantities'][$key] }}</td>
                    <td>{{ $mailData['productPrices'][$key] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

  
    <div class="box9">
        <table class="table2">
           
            <tr>
                <th>Total</th>
                <td>{{ $mailData['totalAmount'] }}</td>
            </tr>
            <tr>
                <th>In Words: </th>
                <td>{{ numberToWords($mailData['totalAmount']) }}</td>
            </tr>
            <tr>
                <th>termsAndConditions: </th>
                <td>{{ $mailData['termsAndConditions'] }}</td>
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
    

</div>
</body>
</html>