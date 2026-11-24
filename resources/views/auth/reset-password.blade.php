@extends('layouts.public')
@section('title', 'Reset Password - Online Job Portal')
@section('content')
<div class="auth-container">
    <div class="left-panel"><div class="brand-content"><div class="brand-logo"><i class="fa-solid fa-lock"></i></div><h1 class="brand-title">Create New Password</h1><p class="brand-description">Choose a strong, unique password you haven't used before.</p><div class="feature-list"><div class="feature-item"><i class="fa-solid fa-shield-halved"></i>Password Encryption</div><div class="feature-item"><i class="fa-solid fa-key"></i>Secure Reset Token</div><div class="feature-item"><i class="fa-solid fa-user-shield"></i>Protected Account</div><div class="feature-item"><i class="fa-solid fa-check"></i>Password Policy Validation</div></div></div></div>
    <div class="right-panel"><div class="auth-card">
        <div class="auth-logo"><i class="fa-solid fa-key"></i></div><h2 class="auth-title">Reset Password</h2><p class="auth-subtitle">{{ $maskedEmail }}</p>
        @if(session('danger'))<div class="alert alert-danger" role="alert">{{ session('danger') }}</div>@endif
        @if($errors->any())<div class="server-errors">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <form action="{{ route('auth.password.update') }}" method="POST" id="resetPasswordForm">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="form-group"><label for="password">New Password</label><div class="input-group"><i class="fa-solid fa-lock"></i><input type="password" id="password" name="password" placeholder="Enter new password" autocomplete="new-password" required></div></div>
            <div class="form-group"><label for="confirm_password">Confirm New Password</label><div class="input-group"><i class="fa-solid fa-lock"></i><input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" autocomplete="new-password" required></div></div>
            <button type="submit" class="btn-login"><i class="fa-solid fa-rotate"></i>Update Password</button>
        </form>
        <div class="auth-footer-links"><a href="{{ route('login') }}"><i class="fa-solid fa-arrow-left"></i>Back to Login</a></div>
    </div></div>
</div>
@endsection
