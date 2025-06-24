
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return Cart Order Updated!</title>

    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f8f8;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }

        h1, h2, p {
            color: #333333;
            margin: 0;
        }

        h1 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .button-64 {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            color: #ffffff;
            background: linear-gradient(144deg, #AF40FF, #5B42F3 50%, #00DDEB);
            border-radius: 8px;
            padding: 12px 20px;
            font-size: 16px;
            transition: background 0.3s ease;
        }

        .button-64:hover {
            background: linear-gradient(144deg, #5B42F3, #AF40FF 50%, #00DDEB);
        }

        .ticket-details {
            background-color: #f5f5f5;
            padding: 20px;
            margin-top: 20px;
            border-radius: 8px;
        }

        .ticket-details h2 {
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ccc;
        }

        .ticket-details p {
            margin: 10px 0;
            line-height: 1.4;
        }

        .contact-info {
            margin-top: 20px;
            font-size: 14px;
            color: #777777;
        }
    </style>
</head>

<body>
    <div class="email-container">
        <h1>Your Return Cart Order Status updated!</h1>
        <p>Hi {{$customerName}},</p>
        <p>Your order <strong>{{$invoiceId}}</strong> has been <strong>{{$returnCartOrderStatus}}</strong></p>
       
        <div class="ticket-details">
            <h2>Return Cart Order Details</h2>
            <p><strong>ID:</strong> {{$invoiceId}}</p>
            <p><strong>Date:</strong> {{$date}}</p>
            <p><strong>Type:</strong>{{$returnType}}</p>
            <p><strong>Note:</strong> {{$note}}</p>
            <p><strong>Status:</strong> {{$returnCartOrderStatus}}</p>
            <p><strong>Total:</strong> {{$totalAmount}}</p>
        </div>

        <div class="ticket-details">
            <h2>Customer Details</h2>
            <p><strong>Name:</strong> {{$customerName}}</p>
            <p><strong>Phone:</strong> {{$customerPhone}}</p>
            <p><strong>Email:</strong> {{$customerEmail}}</p>
        </div>

        <div class="contact-info">
            <p><strong>Best regards,</strong></p>
            <p><strong>{{$companyName}}</strong><br>{{$tagLine}}<br>{{$address}}<br>{{$phone}}<br>{{$email}}<br>{{$website}}<br></p>
        </div>
    </div>
</body>

</html>
