<!DOCTYPE html>
<html>
<head>
    <title>Welcome to {{ config('app.name') }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">

    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
        
        <h2 style="color: #1e3a5f;">Welcome, {{ $user->name }}!</h2>
        
        <p>An account has been created for you on <strong>{{ config('app.name') }}</strong>.</p>
        
        <p>Here are your login credentials:</p>
        
        <div style="background-color: #f8fafc; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <p style="margin: 5px 0;"><strong>Login URL:</strong> <a href="{{ url('/login') }}">{{ url('/login') }}</a></p>
            <p style="margin: 5px 0;"><strong>Email:</strong> {{ $user->email }}</p>
            <p style="margin: 5px 0;"><strong>Password:</strong> {{ $password }}</p>
        </div>

        <!-- <p><em>For security reasons, we strongly recommend changing your password after you log in for the first time.</em></p> -->

        <br>
        <p>Best regards,<br>
        The {{ config('app.name') }} Team</p>
    </div>

</body>
</html>
