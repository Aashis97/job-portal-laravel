@extends('layouts.dashboard')

@section('title', 'Account Settings')
@section('role', 'Employer')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/employer/settings.css') }}">
@endsection

@section('content')
<section class="employer-settings-page">
    <div class="settings-page-header">
        <div class="settings-header-content"><div class="settings-header-icon"><i class="fa-solid fa-gear"></i></div><div><span class="settings-page-label">Account Management</span><h1>Account Settings</h1><p>Manage your account information and employer profile.</p></div></div>
    </div>

    @if(session('success') || session('danger'))
        <div class="settings-alert {{ session('success') ? 'settings-alert-success' : 'settings-alert-error' }}">
            <div class="settings-alert-icon"><i class="fa-solid {{ session('success') ? 'fa-circle-check' : 'fa-circle-exclamation' }}"></i></div>
            <div class="settings-alert-content"><strong>{{ session('success') ? 'Success' : 'Error' }}</strong><span>{{ session('success') ?? session('danger') }}</span></div>
            <button type="button" class="settings-alert-close" onclick="this.parentElement.remove();" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        </div>
    @endif
    @if($errors->any())
        <div class="settings-alert settings-alert-error"><div class="settings-alert-icon"><i class="fa-solid fa-circle-exclamation"></i></div><div class="settings-alert-content"><strong>Error</strong><span>{{ implode(' ', $errors->all()) }}</span></div></div>
    @endif

    <div class="settings-grid">
        <div class="settings-main-column">
            <div class="settings-card">
                <div class="settings-card-header"><div class="settings-card-heading"><div class="settings-card-icon"><i class="fa-solid fa-user"></i></div><div><h2>Account Information</h2><p>Update your personal account details.</p></div></div></div>
                <div class="settings-card-body">
                    <form action="{{ route('employer.settings.update') }}" method="POST" class="settings-form" id="settingsForm">
                        @csrf
                        <div class="settings-form-group"><label for="full_name">Full Name <span class="required">*</span></label><div class="settings-input-wrapper"><i class="fa-solid fa-user"></i><input type="text" name="full_name" id="full_name" value="{{ old('full_name', $user->full_name) }}" maxlength="100" required autocomplete="name" placeholder="Enter your full name"></div></div>
                        <div class="settings-form-group">
                            <label for="email">Email Address <span class="required">*</span></label>
                            <div class="settings-input-wrapper"><i class="fa-solid fa-envelope"></i><input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" maxlength="150" required autocomplete="email" placeholder="Enter your email address"></div>
                            <div class="settings-field-status {{ $user->email_verified ? 'verified' : 'pending' }}"><i class="fa-solid {{ $user->email_verified ? 'fa-circle-check' : 'fa-circle-exclamation' }}"></i>{{ $user->email_verified ? 'Email verified' : 'Email address is not verified' }}</div>
                        </div>
                        <div class="settings-form-group"><label for="phone">Phone Number</label><div class="settings-input-wrapper"><i class="fa-solid fa-phone"></i><input type="tel" name="phone" id="phone" value="{{ old('phone', $user->phone) }}" maxlength="30" autocomplete="tel" placeholder="Enter your phone number"></div></div>
                        <div class="settings-form-actions"><button type="reset" class="settings-reset-btn"><i class="fa-solid fa-rotate-left"></i>Reset</button><button type="submit" class="settings-save-btn" id="saveSettingsBtn"><i class="fa-solid fa-floppy-disk"></i>Save Changes</button></div>
                    </form>
                </div>
            </div>

            <div class="settings-card">
                <div class="settings-card-header"><div class="settings-card-heading"><div class="settings-card-icon"><i class="fa-solid fa-building-user"></i></div><div><h2>Employer Information</h2><p>Your employer information from the company profile.</p></div></div></div>
                <div class="settings-card-body">
                    <div class="settings-info-grid">
                        <div class="settings-info-item"><span class="settings-info-label">Company</span><strong>{{ $employer->company_name ?? 'Not assigned' }}</strong></div>
                        <div class="settings-info-item"><span class="settings-info-label">Designation</span><strong>{{ $employer->designation ?? 'Not specified' }}</strong></div>
                        <div class="settings-info-item"><span class="settings-info-label">Department</span><strong>{{ $employer->department ?? 'Not specified' }}</strong></div>
                        <div class="settings-info-item"><span class="settings-info-label">Primary Contact</span>@if($employer && $employer->is_primary_contact)<span class="settings-badge settings-badge-success"><i class="fa-solid fa-check"></i>Yes</span>@else<span class="settings-badge settings-badge-neutral">No</span>@endif</div>
                    </div>
                </div>
            </div>
        </div>

        <aside class="settings-side-column">
            <div class="settings-card settings-status-card">
                <div class="settings-card-header"><div class="settings-card-heading"><div class="settings-card-icon"><i class="fa-solid fa-shield-halved"></i></div><div><h2>Account Status</h2><p>Current account information.</p></div></div></div>
                <div class="settings-card-body">
                    @php
                        $statusClass = match ($user->account_status) {
                            'Active' => 'active',
                            'Inactive' => 'inactive',
                            'Blocked' => 'blocked',
                            default => 'pending',
                        };
                    @endphp
                    <div class="settings-status-item"><span>Account Status</span><span class="settings-status-badge {{ $statusClass }}"><span class="status-dot"></span>{{ $user->account_status }}</span></div>
                    <div class="settings-status-item"><span>Email Verification</span><span class="settings-status-badge {{ $user->email_verified ? 'active' : 'pending' }}"><span class="status-dot"></span>{{ $user->email_verified ? 'Verified' : 'Pending' }}</span></div>
                </div>
            </div>

            <div class="settings-card">
                <div class="settings-card-header"><div class="settings-card-heading"><div class="settings-card-icon"><i class="fa-solid fa-lock"></i></div><div><h2>Security</h2><p>Manage your account security.</p></div></div></div>
                <div class="settings-card-body">
                    <a href="{{ route('employer.password.form') }}" class="settings-security-link"><span class="settings-security-link-icon"><i class="fa-solid fa-key"></i></span><span class="settings-security-link-content"><strong>Change Password</strong><small>Update your account password</small></span><i class="fa-solid fa-chevron-right"></i></a>
                    <a href="{{ route('employer.profile-image.form') }}" class="settings-security-link"><span class="settings-security-link-icon"><i class="fa-solid fa-image"></i></span><span class="settings-security-link-content"><strong>Profile Image</strong><small>Update your profile picture</small></span><i class="fa-solid fa-chevron-right"></i></a>
                    <a href="{{ route('employer.account') }}" class="settings-security-link"><span class="settings-security-link-icon"><i class="fa-solid fa-user-shield"></i></span><span class="settings-security-link-content"><strong>Account</strong><small>Review your account status</small></span><i class="fa-solid fa-chevron-right"></i></a>
                </div>
            </div>

            <div class="settings-notice"><div class="settings-notice-icon"><i class="fa-solid fa-circle-info"></i></div><div><strong>Keep your information updated</strong><p>Your account information may be used for employer communications and job applications.</p></div></div>
        </aside>
    </div>
</section>
@endsection
