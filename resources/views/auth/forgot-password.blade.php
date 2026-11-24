@extends('layouts.public')
@section('title', 'Forgot Password - Online Job Portal')
@section('content')
<div class="auth-container">
    <div class="left-panel"><div class="brand-content"><div class="brand-logo"><i class="fa-solid fa-key"></i></div><h1 class="brand-title">Forgot Password?</h1><p class="brand-description">Enter your registered email address and we will send a secure password reset link to your inbox.</p><div class="feature-list"><div class="feature-item"><i class="fa-solid fa-shield-halved"></i>Secure Password Recovery</div><div class="feature-item"><i class="fa-solid fa-envelope"></i>Email Verification</div><div class="feature-item"><i class="fa-solid fa-lock"></i>Encrypted Reset Token</div><div class="feature-item"><i class="fa-solid fa-user-check"></i>Account Protection</div></div></div></div>
    <div class="right-panel"><div class="auth-card">
        <div class="auth-logo"><i class="fa-solid fa-lock"></i></div><h2 class="auth-title">Reset Password</h2><p class="auth-subtitle">Enter your registered email address below.</p>
        @if(session('danger'))<div class="alert alert-danger" role="alert">{{ session('danger') }}</div>@endif
        @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="server-errors">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <form action="{{ route('auth.password.email') }}" method="POST" id="forgotPasswordForm">
            @csrf
            <div class="form-group"><label for="email">Email Address</label><div class="input-group"><i class="fa-solid fa-envelope"></i><input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="Enter your registered email" autocomplete="email" maxlength="150" required></div></div>
            <div class="form-group"><label for="captcha">Security Check: {{ $captchaQuestion }}</label><div class="input-group"><i class="fa-solid fa-shield-halved"></i><input type="text" id="captcha" name="captcha" placeholder="Answer" inputmode="numeric" pattern="[0-9]*" autocomplete="off" maxlength="3" required></div></div>
            <button type="submit" class="btn-login"><i class="fa-solid fa-paper-plane"></i>Send Reset Link</button>
        </form>
        <div class="auth-footer-links"><a href="{{ route('login') }}"><i class="fa-solid fa-arrow-left"></i>Back to Login</a></div>
    </div></div>
</div>
@endsection
