<!DOCTYPE html>
<html lang="{{ session('language', 'en') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#7954d8">
    <title>@yield('title', 'Online Job Portal System')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/language.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/landing.css') }}">
    @if (request()->routeIs('login', 'register', 'auth.password.*', 'auth.verification.*'))
        <link rel="stylesheet" href="{{ asset('assets/css/auth.css') }}">
    @endif
</head>
<body>
@yield('content')
<script src="{{ asset('assets/js/landing.js') }}" defer></script>
<script src="{{ asset('assets/js/language.js') }}" defer></script>
</body>
</html>
