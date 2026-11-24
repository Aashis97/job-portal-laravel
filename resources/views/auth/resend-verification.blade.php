@extends('layouts.public')
@section('title', 'Resend Verification - Online Job Portal')
@section('content')
<div class="auth-container">
    <div class="left-panel"><div class="brand-content"><div class="brand-logo"><i class="fa-solid fa-envelope-circle-check"></i></div><h1 class="brand-title">Verify Your Email</h1><p class="brand-description">Didn't receive the verification email? Enter your registered email address and we'll send a new verification link.</p><div class="feature-list"><div class="feature-item"><i class="fa-solid fa-check"></i>Fast Verification</div><div class="feature-item"><i class="fa-solid fa-shield-halved"></i>Secure Email Tokens</div><div class="feature-item"><i class="fa-solid fa-paper-plane"></i>Email Delivery</div><div class="feature-item"><i class="fa-solid fa-user-check"></i>Activate Your Account</div></div></div></div>
    <div class="right-panel"><div class="auth-card">
        <div class="auth-logo"><i class="fa-solid fa-envelope"></i></div><h2 class="auth-title">Resend Verification</h2><p class="auth-subtitle">Enter your registered email address.</p>
        @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="server-errors">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <form action="{{ route('auth.verification.send') }}" method="POST" id="resendVerificationForm">
            @csrf
            <div class="form-group"><label for="email">Email Address</label><div class="input-group"><i class="fa-solid fa-envelope"></i><input type="email" name="email" id="email" value="{{ old('email') }}" placeholder="Enter your email" autocomplete="email" maxlength="150" required></div></div>
            <button class="btn-login" type="submit"><i class="fa-solid fa-paper-plane"></i>Send Verification Email</button>
        </form>
        <div class="auth-footer-links"><a href="{{ route('login') }}"><i class="fa-solid fa-arrow-left"></i>Back to Login</a></div>
    </div></div>
</div>
@endsection
