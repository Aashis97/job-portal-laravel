@extends('layouts.dashboard')
@section('title', 'My Profile')
@section('role', 'Administrator')
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/admin/profile.css') }}">@endsection
@section('js')
<script>
document.getElementById('profile_image')?.addEventListener('change', function () {
    const fileName = this.files?.[0]?.name || 'No new photo selected';
    document.querySelector('[data-file-name]').textContent = fileName;
});
</script>
@endsection
@section('sidebar')
<div class="sidebar-section"><div class="sidebar-section-title">MAIN</div><a href="{{ route('admin.dashboard') }}" class="sidebar-link"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a><a href="{{ route('admin.analytics') }}" class="sidebar-link"><i class="fa-solid fa-chart-line"></i><span>Analytics</span></a></div>
<div class="sidebar-section"><div class="sidebar-section-title">MANAGEMENT</div><a href="{{ route('admin.users') }}" class="sidebar-link"><i class="fa-solid fa-users"></i><span>Users</span></a><a href="{{ route('admin.companies') }}" class="sidebar-link"><i class="fa-solid fa-building"></i><span>Companies</span></a><a href="{{ route('admin.jobs') }}" class="sidebar-link"><i class="fa-solid fa-briefcase"></i><span>Jobs</span></a><a href="{{ route('admin.applications') }}" class="sidebar-link"><i class="fa-solid fa-file-lines"></i><span>Applications</span></a><a href="{{ route('admin.reports') }}" class="sidebar-link"><i class="fa-solid fa-flag"></i><span>Reports</span></a><a href="{{ route('admin.audit-log') }}" class="sidebar-link"><i class="fa-solid fa-clipboard-list"></i><span>Audit Log</span></a><a href="{{ route('admin.taxonomy') }}" class="sidebar-link"><i class="fa-solid fa-tags"></i><span>Categories &amp; Skills</span></a><a href="{{ route('admin.settings') }}" class="sidebar-link"><i class="fa-solid fa-gear"></i><span>Settings</span></a></div>
<div class="sidebar-section"><div class="sidebar-section-title">COMMUNICATION</div><a href="{{ route('admin.notifications') }}" class="sidebar-link"><i class="fa-solid fa-bell"></i><span>Notifications</span></a><a href="{{ route('admin.broadcast') }}" class="sidebar-link"><i class="fa-solid fa-paper-plane"></i><span>Send Broadcast</span></a><a href="{{ route('admin.messages') }}" class="sidebar-link"><i class="fa-solid fa-envelope"></i><span>Messages</span></a></div>
<div class="sidebar-section"><div class="sidebar-section-title">ACCOUNT</div><a href="{{ route('admin.profile') }}" class="sidebar-link active"><i class="fa-solid fa-user"></i><span>My Profile</span></a></div>
@endsection
@section('content')
<section class="admin-profile-page">
    <header class="profile-page-header"><div><span class="profile-eyebrow">Administrator account</span><h1>My Profile</h1><p>Update your personal details and profile photo.</p></div><a href="{{ route('admin.dashboard') }}" class="profile-back-link"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Dashboard</a></header>
    @foreach(['success' => 'success', 'danger' => 'danger', 'warning' => 'warning', 'info' => 'info'] as $key => $class)
        @if(session($key))<div class="profile-flash profile-flash-{{ $class }}" role="status">{{ session($key) }}</div>@endif
    @endforeach
    @if($errors->any())<div class="profile-flash profile-flash-danger" role="alert">{{ $errors->first() }}</div>@endif
    <form class="admin-profile-form" action="{{ route('admin.profile.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <section class="profile-card" aria-labelledby="profile-photo-heading"><div class="profile-photo-section">
            <img class="profile-avatar-preview" src="{{ $profileImageUrl }}" alt="Profile photo for {{ $profile->full_name }}">
            <div class="profile-photo-copy"><h2 id="profile-photo-heading">Profile photo</h2><p>JPG, PNG, or WebP. Maximum size 5 MB.</p><label class="profile-upload-button" for="profile_image"><i class="fa-solid fa-camera" aria-hidden="true"></i> Choose photo</label><input id="profile_image" name="profile_image" type="file" accept="image/jpeg,image/png,image/webp"><span class="profile-file-name" data-file-name>No new photo selected</span></div>
        </div></section>
        <section class="profile-card profile-details-card" aria-labelledby="profile-details-heading">
            <div class="profile-card-heading"><div class="profile-heading-icon"><i class="fa-regular fa-id-card" aria-hidden="true"></i></div><div><h2 id="profile-details-heading">Personal details</h2><p>These details are shown in your administrator account.</p></div></div>
            <div class="profile-fields">
                <div class="profile-field"><label for="full_name">Full name</label><input id="full_name" name="full_name" type="text" value="{{ old('full_name', $profile->full_name) }}" maxlength="100" autocomplete="name" required></div>
                <div class="profile-field"><label for="email">Email address</label><input id="email" type="email" value="{{ $profile->email }}" readonly aria-describedby="email-note"><small id="email-note">Contact an administrator to change your sign-in email.</small></div>
                <div class="profile-field"><label for="phone">Phone number</label><input id="phone" name="phone" type="tel" value="{{ old('phone', $profile->phone) }}" maxlength="20" autocomplete="tel"></div>
                <div class="profile-field"><label for="role">Role</label><input id="role" type="text" value="Administrator" readonly></div>
            </div>
            <div class="profile-form-footer"><span>Member since {{ \Illuminate\Support\Carbon::parse($profile->created_at)->format('M j, Y') }}</span><button type="submit" class="profile-save-button"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save profile</button></div>
        </section>
    </form>
</section>
@endsection
