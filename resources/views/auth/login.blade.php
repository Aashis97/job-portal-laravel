@extends('layouts.public')
@section('title', 'Login - Online Job Portal')
@section('content')
<div class="auth-container auth-page-login">
    <div class="left-panel"><div class="brand-content"><div class="brand-logo"><i class="fa-solid fa-briefcase"></i></div><span class="brand-kicker">CAREERS, CONNECTED</span><h1 class="brand-title">Online Job Portal</h1><p class="brand-description">Find your dream career with our AI-powered recruitment platform.</p></div></div>
    <div class="right-panel"><div class="auth-card">
        <div class="auth-logo"><i class="fa-solid fa-briefcase"></i></div>
        <span class="auth-card-kicker">WELCOME BACK</span><h1 class="auth-title">Welcome Back</h1><p class="auth-subtitle">Sign in to your account</p>
        @if(session('danger'))<div class="alert alert-danger">{{ session('danger') }}</div>@endif
        @if(session('warning'))<div class="alert alert-warning">{{ session('warning') }}</div>@endif
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="server-errors">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <form action="{{ route('login.submit') }}" method="POST" id="loginForm">
            @csrf
            <div class="form-group"><label for="email">Email Address</label><div class="input-group"><i class="fa-solid fa-envelope"></i><input type="email" id="email" name="email" value="{{ old('email') }}" required maxlength="150"></div></div>
            <div class="form-group"><label for="password">Password</label><div class="input-group"><i class="fa-solid fa-lock"></i><input type="password" id="password" name="password" required></div></div>
            <div class="form-group"><label for="captcha">Security Check: {{ $captchaQuestion }}</label><div class="input-group"><i class="fa-solid fa-shield-halved"></i><input type="text" id="captcha" name="captcha" required maxlength="3"></div></div>
            <div class="form-options"><label class="remember"><input type="checkbox" name="remember" value="1">Remember Me</label><a href="{{ route('auth.password.forgot') }}">Forgot Password?</a></div>
            <button type="submit" class="btn-login"><i class="fa-solid fa-right-to-bracket"></i> Login</button>
        </form>
        <div class="auth-footer-links">Don't have an account? <a href="{{ route('register') }}">Register Now</a><br><a href="{{ route('auth.verification.resend') }}">Resend Verification</a></div>
    </div></div>
</div>
@endsection
