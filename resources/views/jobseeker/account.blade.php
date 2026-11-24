@extends('layouts.dashboard')

@section('title', 'Account')
@section('role', 'Job Seeker')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/jobseeker/settings.css') }}">
@endsection

@section('content')
<section class="jobseeker-settings-page">
    <div class="page-header"><div><span class="page-tag">Security</span><h1>Account</h1></div></div>

    @foreach(['success', 'danger', 'warning', 'info'] as $type)
        @if(session($type))
            <div class="alert alert-{{ $type }}">{{ session($type) }}</div>
        @endif
    @endforeach
    @if($errors->any())
        <div class="alert alert-danger">{{ implode(' ', $errors->all()) }}</div>
    @endif

    <div class="settings-card">
        <div class="settings-card-header"><h2>Account status</h2><p>Your account details and deactivation options.</p></div>
        <div class="settings-info-grid">
            <div><span>Full Name</span><strong>{{ $user->full_name }}</strong></div>
            <div><span>Email</span><strong>{{ $user->email }}</strong></div>
            <div><span>Status</span><strong>{{ $user->account_status }}</strong></div>
            <div><span>Member Since</span><strong>{{ \Illuminate\Support\Carbon::parse($user->created_at)->format('M j, Y') }}</strong></div>
        </div>
    </div>

    <div class="settings-card">
        <div class="settings-card-header"><h2>Deactivate account</h2><p>This will sign you out and block future logins until reactivated by support.</p></div>
        <form action="{{ route('jobseeker.account.deactivate') }}" method="POST" class="settings-form">
            @csrf
            <div class="form-group">
                <label for="current_password">Current password</label>
                <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
            </div>
            <div class="form-group">
                <label for="confirmation">Type DEACTIVATE to confirm</label>
                <input type="text" id="confirmation" name="confirmation" required placeholder="DEACTIVATE">
            </div>
            <div class="alert alert-warning">Deactivating your account is permanent until an admin reactivates it.</div>
            <div class="settings-form-actions"><button type="submit" class="btn btn-danger"><i class="fa-solid fa-user-slash" aria-hidden="true"></i>Deactivate Account</button></div>
        </form>
    </div>
</section>
@endsection
