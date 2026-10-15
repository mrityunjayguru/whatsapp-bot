<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'ChatBot') }}</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at 50% 20%, #1e293b 0%, #0f172a 100%);
            color: #f8fafc;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        /* Subtle ambient glowing background spheres */
        body::before {
            content: '';
            position: absolute;
            width: 450px;
            height: 450px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.18) 0%, rgba(99, 102, 241, 0) 70%);
            top: 20%;
            left: 50%;
            transform: translate(-50%, -50%);
            border-radius: 50%;
            pointer-events: none;
        }

        .card {
            position: relative;
            z-index: 1;
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 1.5rem;
            padding: 3rem 2.5rem;
            max-width: 480px;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.45), 0 0 0 1px rgba(255, 255, 255, 0.05);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
        }

        .icon-wrapper {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(168, 85, 247, 0.2) 100%);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: #818cf8;
        }

        .icon-wrapper svg {
            width: 32px;
            height: 32px;
        }

        h1 {
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 0.75rem;
            color: #ffffff;
        }

        p {
            color: #94a3b8;
            font-size: 0.975rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .btn-register {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            width: 100%;
            padding: 0.95rem 1.75rem;
            font-size: 1rem;
            font-weight: 600;
            color: #ffffff;
            text-decoration: none;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            border-radius: 0.875rem;
            box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.45);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .btn-register:hover {
            background: linear-gradient(135deg, #4338ca 0%, #6d28d9 100%);
            transform: translateY(-2px);
            box-shadow: 0 15px 25px -5px rgba(79, 70, 229, 0.55);
        }

        .btn-register:active {
            transform: translateY(0);
        }

        .btn-register svg {
            width: 18px;
            height: 18px;
            transition: transform 0.2s ease;
        }

        .btn-register:hover svg {
            transform: translateX(3px);
        }

        .btn-login {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            width: 100%;
            margin-top: 0.85rem;
            padding: 0.95rem 1.75rem;
            font-size: 1rem;
            font-weight: 600;
            color: #cbd5e1;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 0.875rem;
            border: 1px solid rgba(255, 255, 255, 0.12);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-login:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }

        .btn-login:active {
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-wrapper">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
        </div>
        <h1>Welcome</h1>
        <p>Get started with our chatbot platform by registering your company.</p>
        <a href="https://chat.designdemonz.com/register-company" class="btn-register">
            <span>Register Company</span>
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
            </svg>
        </a>
        <a href="{{ route('login') }}" class="btn-login">
            <span>Log In</span>
        </a>
    </div>
</body>
</html>
