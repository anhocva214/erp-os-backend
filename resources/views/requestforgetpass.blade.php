<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Forget Password</title>
    </head>
    <body>
        <h3>Hello, {{$mailData['name']}}</h3>
        <p>
            You recently requested a password reset for your account. To reset your password, please click the link below:
        </p>
        <p>
            <a href="{{$mailData['resetLink']}}">{{$mailData['resetLink']}}</a>
        </p>
        <p>
            If you did not request a password reset, please ignore this email.
        </p>
        <p>Thank you,</p>
        <p>{{$mailData['companyName']}}</p>
        <p style="color: gray;">
            P.S: For security reasons, this link will expire in {{$mailData['expiryHours']}} hours.
        </p>
        <p style="color: gray;">
            Note: This is an automated email. Please do not reply to this email.
        </p>
    </body>
</html>
