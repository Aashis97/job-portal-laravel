@extends('layouts.dashboard')
@section('title', 'User Details')
@section('role', 'Administrator')
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/admin/user_details.css') }}">@endsection
@section('js')<script src="{{ asset('assets/js/admin/users_details.js') }}"></script>@endsection
@section('sidebar')
<div class="sidebar-section"><div class="sidebar-section-title">MAIN</div><a href="{{ route('admin.dashboard') }}" class="sidebar-link"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a></div>
<div class="sidebar-section"><div class="sidebar-section-title">MANAGEMENT</div><a href="{{ route('admin.users') }}" class="sidebar-link active"><i class="fa-solid fa-users"></i><span>Users</span></a><a href="{{ route('admin.companies') }}" class="sidebar-link"><i class="fa-solid fa-building"></i><span>Companies</span></a><a href="{{ route('admin.jobs') }}" class="sidebar-link"><i class="fa-solid fa-briefcase"></i><span>Jobs</span></a><a href="{{ route('admin.applications') }}" class="sidebar-link"><i class="fa-solid fa-file-lines"></i><span>Applications</span></a><a href="{{ route('admin.reports') }}" class="sidebar-link"><i class="fa-solid fa-flag"></i><span>Reports</span></a><a href="{{ route('admin.audit-log') }}" class="sidebar-link"><i class="fa-solid fa-clipboard-list"></i><span>Audit Log</span></a><a href="{{ route('admin.taxonomy') }}" class="sidebar-link"><i class="fa-solid fa-tags"></i><span>Categories &amp; Skills</span></a><a href="{{ route('admin.settings') }}" class="sidebar-link"><i class="fa-solid fa-gear"></i><span>Settings</span></a></div>
@endsection
@section('content')
@php($isSelf = (int) auth()->id() === (int) $user->user_id)
<div class="admin-user-details" data-user-id="{{ $user->user_id }}" data-user-name="{{ $user->full_name }}" data-user-role="{{ $user->role_id }}" data-is-self="{{ $isSelf ? '1' : '0' }}">
    <div class="admin-page-header"><div><span class="admin-page-label">USER MANAGEMENT</span><h1>{{ $user->full_name }}</h1><p>{{ [1 => 'Administrator', 2 => 'Employer', 3 => 'Job Seeker'][(int) $user->role_id] ?? 'User' }} account details</p></div><a href="{{ route('admin.users') }}" class="btn btn-light"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to users</a></div>
    @foreach(['success' => 'admin-alert-success', 'danger' => 'admin-alert-danger', 'warning' => 'admin-alert-warning', 'info' => 'admin-alert-info'] as $key => $class)
        @if(session($key))<div class="admin-alert {{ $class }}" role="status"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><div><span>{{ session($key) }}</span></div></div>@endif
    @endforeach
    <section class="admin-panel">
        <div class="admin-panel-header"><div><h2>Account information</h2><p>User ID #{{ $user->user_id }}</p></div></div>
        <div class="admin-detail-grid">
            <div><span>Full name</span><strong>{{ $user->full_name }}</strong></div>
            <div><span>Email</span><strong>{{ $user->email }}</strong></div>
            <div><span>Phone</span><strong>{{ $user->phone ?: '—' }}</strong></div>
            <div><span>Role</span><strong>{{ [1 => 'Administrator', 2 => 'Employer', 3 => 'Job Seeker'][(int) $user->role_id] ?? 'User' }}</strong></div>
            <div><span>Account status</span><strong>{{ $user->account_status }}</strong></div>
            <div><span>Email verification</span><strong>{{ $user->email_verified ? 'Verified' : 'Not verified' }}</strong></div>
            <div><span>Company</span><strong>{{ $user->company_name ?: '—' }}</strong></div>
            <div><span>Company status</span><strong>{{ $user->company_status ?: '—' }}</strong></div>
            <div><span>Experience</span><strong>{{ $user->years_of_experience !== null ? number_format((float) $user->years_of_experience, 1).' years' : '—' }}</strong></div>
            <div><span>Last login</span><strong>{{ $user->last_login ?: '—' }}</strong></div>
            <div><span>Joined</span><strong>{{ $user->created_at ?: '—' }}</strong></div>
        </div>
    </section>
    @if($isSelf)<div class="admin-alert admin-alert-info"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><div><strong>This is your own account</strong><span>Some actions are disabled on your own account for safety.</span></div></div>@endif
    <div class="details-grid">
        <div class="details-main">
            @if((int) $user->role_id === 2 && $user->company_name)
                <section class="details-card"><div class="details-card-header"><h2>Company Information</h2><span class="role-pill role-2">Employer</span></div><dl class="info-list"><div class="info-row"><dt>Company</dt><dd>{{ $user->company_name }}</dd></div><div class="info-row"><dt>Company Status</dt><dd>{{ $user->company_status ?: 'Not set' }}</dd></div></dl></section>
            @elseif((int) $user->role_id === 3)
                <section class="details-card"><div class="details-card-header"><h2>Candidate Profile</h2><span class="role-pill role-3">Job Seeker</span></div><dl class="info-list"><div class="info-row"><dt>Experience</dt><dd>{{ $user->years_of_experience !== null ? number_format((float) $user->years_of_experience, 1).' years' : 'Not provided' }}</dd></div></dl></section>
            @endif
        </div>
        <aside class="details-side">
            <section class="details-card"><div class="details-card-header"><h2>Account Status</h2></div><div class="action-list">
                @if($user->account_status !== 'Active')<form action="{{ route('admin.user-action') }}" method="POST">@csrf<input type="hidden" name="user_id" value="{{ $user->user_id }}"><input type="hidden" name="action" value="activate"><button type="submit" class="action-btn action-btn-success"><i class="fa-solid fa-circle-check" aria-hidden="true"></i><span>Activate account</span></button></form>@endif
                @if($user->account_status !== 'Blocked' && !$isSelf)<form action="{{ route('admin.user-action') }}" method="POST" onsubmit="return confirm('Block this user? They will be unable to log in.')">@csrf<input type="hidden" name="user_id" value="{{ $user->user_id }}"><input type="hidden" name="action" value="block"><button type="submit" class="action-btn action-btn-danger"><i class="fa-solid fa-ban" aria-hidden="true"></i><span>Block user</span></button></form>@endif
                @if($user->account_status !== 'Inactive' && !$isSelf)<form action="{{ route('admin.user-action') }}" method="POST" onsubmit="return confirm('Suspend this account? The user will not be able to log in until reactivated.')">@csrf<input type="hidden" name="user_id" value="{{ $user->user_id }}"><input type="hidden" name="action" value="suspend"><button type="submit" class="action-btn action-btn-warning"><i class="fa-solid fa-pause" aria-hidden="true"></i><span>Suspend account</span></button></form>@endif
                @if($user->account_status === 'Active' && !$isSelf)<form action="{{ route('admin.user-action') }}" method="POST" onsubmit="return confirm('Force logout from all devices?')">@csrf<input type="hidden" name="user_id" value="{{ $user->user_id }}"><input type="hidden" name="action" value="force_logout"><button type="submit" class="action-btn action-btn-light"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i><span>Force logout</span></button></form>@endif
                @if(!(bool) $user->email_verified)<form action="{{ route('admin.user-action') }}" method="POST" onsubmit="return confirm('Manually mark this email as verified?')">@csrf<input type="hidden" name="user_id" value="{{ $user->user_id }}"><input type="hidden" name="action" value="verify_email"><button type="submit" class="action-btn action-btn-success"><i class="fa-solid fa-envelope-circle-check" aria-hidden="true"></i><span>Verify email</span></button></form>@endif
            </div></section>
            <section class="details-card"><div class="details-card-header"><h2>Role</h2></div><p class="role-note">Current role: <strong>{{ [1 => 'Administrator', 2 => 'Employer', 3 => 'Job Seeker'][(int) $user->role_id] ?? 'User' }}</strong></p>
                @if(!$isSelf)<form action="{{ route('admin.user-action') }}" method="POST" class="role-form" onsubmit="return confirm('Change this user role? They will need to log in again.')">@csrf<input type="hidden" name="user_id" value="{{ $user->user_id }}"><input type="hidden" name="action" value="change_role"><label for="role-select">New role</label><select id="role-select" name="role_id" class="role-select"><option value="2" @selected((int) $user->role_id === 2)>Employer</option><option value="3" @selected((int) $user->role_id === 3)>Job Seeker</option><option value="1" @selected((int) $user->role_id === 1)>Administrator</option></select><button type="submit" class="action-btn action-btn-primary"><i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i><span>Apply role change</span></button></form>@else<p class="muted-value">You cannot change your own role.</p>@endif
            </section>
            @if(!$isSelf)<section class="details-card danger-card"><div class="details-card-header"><h2>Danger Zone</h2></div><p class="danger-note">Deleting a user permanently removes their account and related records. This action cannot be undone.</p><form action="{{ route('admin.user-action') }}" method="POST" onsubmit="return confirm('DELETE this account permanently?')">@csrf<input type="hidden" name="user_id" value="{{ $user->user_id }}"><input type="hidden" name="action" value="delete"><label for="delete-confirmation">Type the user's full name to confirm:</label><input id="delete-confirmation" name="delete_confirmation" type="text" maxlength="100" required autocomplete="off"><button type="submit" class="action-btn action-btn-danger-solid"><i class="fa-solid fa-trash" aria-hidden="true"></i><span>Delete user permanently</span></button></form></section>@endif
        </aside>
    </div>
</div>
@endsection
