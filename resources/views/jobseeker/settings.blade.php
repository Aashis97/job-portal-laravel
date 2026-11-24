@extends('layouts.dashboard')

@section('title', 'Settings')
@section('role', 'Job Seeker')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/jobseeker/settings.css') }}">
@endsection

@section('content')
<section class="jobseeker-settings-page">
    <div class="page-header">
        <div><span class="page-tag">Account</span><h1>Settings</h1></div>
    </div>

    @foreach(['success', 'danger', 'warning', 'info'] as $type)
        @if(session($type))
            <div class="alert alert-{{ $type }}">{{ session($type) }}</div>
        @endif
    @endforeach
    @if($errors->any())
        <div class="alert alert-danger">{{ implode(' ', $errors->all()) }}</div>
    @endif

    <div class="settings-card">
        <div class="settings-card-header"><h2>Account Information</h2><p>Managed by your account — contact support to change these</p></div>
        <div class="settings-info-grid">
            <div><span>Full Name</span><strong>{{ $user->full_name }}</strong></div>
            <div><span>Email Address</span><strong>{{ $user->email }}</strong></div>
            <div><span>Phone</span><strong>{{ $user->phone ?: '—' }}</strong></div>
            <div><span>Status</span><strong>{{ $user->account_status }}</strong></div>
            <div><span>Email Verified</span><strong>{{ (int) $user->email_verified === 1 ? 'Yes' : 'No' }}</strong></div>
            <div><span>Member Since</span><strong>{{ \Illuminate\Support\Carbon::parse($user->created_at)->format('M j, Y') }}</strong></div>
        </div>
    </div>

    <div class="settings-card">
        <div class="settings-card-header"><h2>Change Password</h2><p>Use a strong, unique password</p></div>
        <form action="{{ route('jobseeker.password.change') }}" method="POST" class="settings-form">
            @csrf
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <div class="password-field">
                    <input type="password" id="current_password" name="current_password" autocomplete="current-password" required>
                    <button type="button" class="password-toggle" data-target="current_password" aria-label="Show password"><i class="fa-solid fa-eye"></i></button>
                </div>
            </div>
            <div class="form-group">
                <label for="new_password">New Password</label>
                <div class="password-field">
                    <input type="password" id="new_password" name="new_password" autocomplete="new-password" required>
                    <button type="button" class="password-toggle" data-target="new_password" aria-label="Show password"><i class="fa-solid fa-eye"></i></button>
                </div>
                <div class="password-strength" data-level="0">
                    <div class="strength-bars" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span></div>
                    <span class="strength-text" id="strengthText">Enter a password</span>
                </div>
                <div class="password-requirements">
                    <span data-requirement="length"><i class="fa-solid fa-circle"></i>At least 8 characters</span>
                    <span data-requirement="uppercase"><i class="fa-solid fa-circle"></i>One uppercase letter</span>
                    <span data-requirement="lowercase"><i class="fa-solid fa-circle"></i>One lowercase letter</span>
                    <span data-requirement="number"><i class="fa-solid fa-circle"></i>One number</span>
                    <span data-requirement="special"><i class="fa-solid fa-circle"></i>One special character</span>
                </div>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <div class="password-field">
                    <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" required>
                    <button type="button" class="password-toggle" data-target="confirm_password" aria-label="Show password"><i class="fa-solid fa-eye"></i></button>
                </div>
                <div class="password-match-message" id="passwordMatchMessage"></div>
            </div>
            <div class="settings-form-actions"><button type="submit" class="btn btn-primary"><i class="fa-solid fa-key" aria-hidden="true"></i>Update Password</button></div>
        </form>
    </div>
</section>
@endsection

@section('js')
<script src="{{ asset('assets/js/jobseeker/settings.js') }}"></script>
@endsection
