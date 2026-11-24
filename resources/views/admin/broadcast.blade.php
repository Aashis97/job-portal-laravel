@extends('layouts.dashboard')
@section('title', 'Send Broadcast')
@section('role', 'Administrator')
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/admin/broadcast.css') }}">@endsection
@section('js')
<script>
window.broadcastData = { counts: { all_users: {{ $counts['all_users'] }}, employers: {{ $counts['employers'] }}, jobseekers: {{ $counts['jobseekers'] }}, admins: {{ $counts['admins'] }}, specific: 1 } };
</script>
<script src="{{ asset('assets/js/admin/broadcast.js') }}"></script>
@endsection
@section('sidebar')
<div class="sidebar-section"><div class="sidebar-section-title">MAIN</div><a href="{{ route('admin.dashboard') }}" class="sidebar-link"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a><a href="{{ route('admin.analytics') }}" class="sidebar-link"><i class="fa-solid fa-chart-line"></i><span>Analytics</span></a></div>
<div class="sidebar-section"><div class="sidebar-section-title">MANAGEMENT</div><a href="{{ route('admin.users') }}" class="sidebar-link"><i class="fa-solid fa-users"></i><span>Users</span></a><a href="{{ route('admin.companies') }}" class="sidebar-link"><i class="fa-solid fa-building"></i><span>Companies</span></a><a href="{{ route('admin.jobs') }}" class="sidebar-link"><i class="fa-solid fa-briefcase"></i><span>Jobs</span></a><a href="{{ route('admin.applications') }}" class="sidebar-link"><i class="fa-solid fa-file-lines"></i><span>Applications</span></a><a href="{{ route('admin.reports') }}" class="sidebar-link"><i class="fa-solid fa-flag"></i><span>Reports</span></a><a href="{{ route('admin.audit-log') }}" class="sidebar-link"><i class="fa-solid fa-clipboard-list"></i><span>Audit Log</span></a><a href="{{ route('admin.taxonomy') }}" class="sidebar-link"><i class="fa-solid fa-tags"></i><span>Categories &amp; Skills</span></a><a href="{{ route('admin.settings') }}" class="sidebar-link"><i class="fa-solid fa-gear"></i><span>Settings</span></a></div>
<div class="sidebar-section"><div class="sidebar-section-title">COMMUNICATION</div><a href="{{ route('admin.notifications') }}" class="sidebar-link"><i class="fa-solid fa-bell"></i><span>Notifications</span></a><a href="{{ route('admin.broadcast') }}" class="sidebar-link active"><i class="fa-solid fa-paper-plane"></i><span>Send Broadcast</span></a><a href="{{ route('admin.messages') }}" class="sidebar-link"><i class="fa-solid fa-envelope"></i><span>Messages</span></a></div>
@endsection
@section('content')
<div class="admin-broadcast-page">
    <a href="{{ route('admin.notifications') }}" class="back-link"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to inbox</a>
    <div class="admin-page-header"><div><span class="admin-page-label">COMMUNICATION</span><h1>Send Broadcast</h1><p>Deliver a notification to a group of users or a single recipient.</p></div></div>
    @foreach(['success' => 'admin-alert-success', 'danger' => 'admin-alert-danger', 'warning' => 'admin-alert-warning', 'info' => 'admin-alert-info'] as $key => $class)
        @if(session($key))<div class="admin-alert {{ $class }}" role="status"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><div><span>{{ session($key) }}</span></div></div>@endif
    @endforeach
    @if($errors->any())<div class="admin-alert admin-alert-danger" role="alert">{{ $errors->first() }}</div>@endif
    <div class="broadcast-layout">
        <div class="broadcast-form-column">
            <form action="{{ route('admin.broadcast.send') }}" method="POST" id="broadcastForm" novalidate>
                @csrf
                <section class="form-card"><div class="form-card-header"><div class="form-card-icon"><i class="fa-solid fa-users" aria-hidden="true"></i></div><div><h2>Recipients</h2><p>Who should receive this notification?</p></div></div><div class="form-card-body">
                    <div class="audience-options">
                        @foreach(['all_users' => 'All Users', 'employers' => 'Employers only', 'jobseekers' => 'Job Seekers only', 'admins' => 'Administrators only', 'specific' => 'A specific user (by email)'] as $audience => $label)
                            <label class="audience-option"><input type="radio" name="audience" value="{{ $audience }}" @checked(old('audience', 'all_users') === $audience)><span class="audience-content"><strong>{{ $label }}</strong><small>@if($audience === 'specific')Enter an email address below @else{{ number_format($counts[$audience]) }} active recipient{{ $counts[$audience] === 1 ? '' : 's' }}@endif</small></span></label>
                        @endforeach
                    </div>
                    <div class="specific-email-field" id="specificEmailField" @if(old('audience', 'all_users') !== 'specific') hidden @endif><label for="specific_email">Recipient Email <span class="required">*</span></label><div class="input-wrapper"><i class="fa-solid fa-envelope" aria-hidden="true"></i><input type="email" id="specific_email" name="specific_email" value="{{ old('specific_email') }}" maxlength="150" placeholder="user@example.com" autocomplete="off"></div><small class="field-help">The recipient must exist on the platform.</small></div>
                </div></section>
                <section class="form-card"><div class="form-card-header"><div class="form-card-icon"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i></div><div><h2>Message</h2><p>Compose the notification content.</p></div></div><div class="form-card-body">
                    <div class="form-group"><label for="notif_type">Type</label><div class="select-wrapper"><i class="fa-solid fa-tag" aria-hidden="true"></i><select name="notif_type" id="notif_type">@foreach(['System', 'Application', 'Interview', 'Job', 'Message', 'Reminder'] as $notificationType)<option value="{{ $notificationType }}" @selected(old('notif_type', 'System') === $notificationType)>{{ $notificationType }}</option>@endforeach</select><i class="fa-solid fa-chevron-down select-arrow" aria-hidden="true"></i></div></div>
                    <div class="form-group"><label for="title">Title <span class="required">*</span></label><div class="input-wrapper"><i class="fa-solid fa-heading" aria-hidden="true"></i><input type="text" id="title" name="title" value="{{ old('title') }}" maxlength="200" placeholder="e.g. Scheduled maintenance window" autocomplete="off" required></div><div class="field-help-row"><small>Keep it short and clear.</small><small><span id="titleCount">{{ mb_strlen(old('title', '')) }}</span> / 200</small></div></div>
                    <div class="form-group"><label for="message">Message <span class="required">*</span></label><textarea id="message" name="message" rows="7" maxlength="1000" placeholder="Write the full message the recipient will see..." required>{{ old('message') }}</textarea><div class="field-help-row"><small>Up to 1000 characters.</small><small><span id="messageCount">{{ mb_strlen(old('message', '')) }}</span> / 1000</small></div></div>
                </div></section>
                <div class="form-actions-row"><a href="{{ route('admin.notifications') }}" class="btn btn-light">Cancel</a><button type="submit" class="btn btn-primary" id="sendBroadcastBtn"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Send Broadcast</button></div>
            </form>
        </div>
        <aside class="broadcast-preview-column"><div class="preview-card"><div class="preview-card-header"><h3><i class="fa-solid fa-eye" aria-hidden="true"></i> Live Preview</h3></div><div class="preview-notification"><div class="notification-icon notif-system" id="previewIcon"><i class="fa-solid fa-bell" aria-hidden="true"></i></div><div class="notification-body"><div class="notification-head"><h4 id="previewTitle">Your title will appear here</h4><span class="notification-type-badge notif-system" id="previewTypeBadge">System</span></div><p id="previewMessage">Your message will appear here once you start typing.</p><small class="preview-time">Just now</small></div></div><div class="preview-meta"><div class="preview-meta-item"><span>Recipients</span><strong id="previewCount">{{ number_format($counts['all_users']) }}</strong></div><div class="preview-meta-item"><span>Type</span><strong id="previewType">System</strong></div></div><div class="preview-info"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><p>Broadcasts cannot be recalled. Verify recipients and content before sending.</p></div></div></aside>
    </div>
</div>
@endsection
