@extends('layouts.dashboard')

@section('title', 'Account')
@section('role', 'Employer')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/employer/settings.css') }}">
@endsection

@section('content')
<section class="employer-settings-page">
    <div class="settings-page-header"><div class="settings-header-content"><div class="settings-header-icon"><i class="fa-solid fa-user-shield"></i></div><div><span class="settings-page-label">Security</span><h1>Account</h1><p>Manage your account status and access.</p></div></div></div>

    @foreach(['success' => 'settings-alert-success', 'danger' => 'settings-alert-error', 'warning' => 'settings-alert-error'] as $type => $class)
        @if(session($type))
            <div class="settings-alert {{ $class }}"><div class="settings-alert-icon"><i class="fa-solid {{ $type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' }}"></i></div><div class="settings-alert-content"><strong>{{ ucfirst($type) }}</strong><span>{{ session($type) }}</span></div></div>
        @endif
    @endforeach

    <div class="settings-card">
        <div class="settings-card-header"><h2>Account status</h2><p>Your account details and deactivation options.</p></div>
        <div class="settings-card-body"><div class="settings-info-grid">
            <div class="settings-info-item"><span class="settings-info-label">Full Name</span><strong>{{ $user->full_name }}</strong></div>
            <div class="settings-info-item"><span class="settings-info-label">Email</span><strong>{{ $user->email }}</strong></div>
            <div class="settings-info-item"><span class="settings-info-label">Status</span><strong>{{ $user->account_status }}</strong></div>
            <div class="settings-info-item"><span class="settings-info-label">Member Since</span><strong>{{ $user->created_at ? \Illuminate\Support\Carbon::parse($user->created_at)->format('M j, Y') : '—' }}</strong></div>
        </div></div>
    </div>

    <div class="settings-card">
        <div class="settings-card-header"><h2>Deactivate account</h2><p>This will sign you out and block future logins until reactivated by support.</p></div>
        <div class="settings-card-body">
            <form action="{{ route('employer.account.deactivate') }}" method="POST" class="settings-form">
                @csrf
                <div class="settings-form-group"><label for="current_password">Current password</label><div class="settings-input-wrapper"><i class="fa-solid fa-lock"></i><input type="password" id="current_password" name="current_password" required autocomplete="current-password"></div></div>
                <div class="settings-form-group"><label for="confirmation">Type DEACTIVATE to confirm</label><div class="settings-input-wrapper"><i class="fa-solid fa-triangle-exclamation"></i><input type="text" id="confirmation" name="confirmation" required placeholder="DEACTIVATE" autocomplete="off"></div></div>
                <div class="settings-notice"><div class="settings-notice-icon"><i class="fa-solid fa-circle-info"></i></div><div><strong>Important</strong><p>Deactivating your account is permanent until an admin reactivates it.</p></div></div>
                <div class="settings-form-actions"><button type="submit" class="settings-deactivate-btn"><i class="fa-solid fa-user-slash"></i>Deactivate Account</button></div>
            </form>
        </div>
    </div>
</section>
@endsection
