@extends('layouts.dashboard')
@section('title', 'Report #'.$report->report_id)
@section('role', 'Administrator')
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/admin/report_details.css') }}">@endsection
@section('js')<script src="{{ asset('assets/js/admin/report_details.js') }}"></script>@endsection
@section('sidebar')
<div class="sidebar-section"><div class="sidebar-section-title">MAIN</div><a href="{{ route('admin.dashboard') }}" class="sidebar-link"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a></div>
<div class="sidebar-section"><div class="sidebar-section-title">MANAGEMENT</div><a href="{{ route('admin.users') }}" class="sidebar-link"><i class="fa-solid fa-users"></i><span>Users</span></a><a href="{{ route('admin.companies') }}" class="sidebar-link"><i class="fa-solid fa-building"></i><span>Companies</span></a><a href="{{ route('admin.jobs') }}" class="sidebar-link"><i class="fa-solid fa-briefcase"></i><span>Jobs</span></a><a href="{{ route('admin.applications') }}" class="sidebar-link"><i class="fa-solid fa-file-lines"></i><span>Applications</span></a><a href="{{ route('admin.reports') }}" class="sidebar-link active"><i class="fa-solid fa-flag"></i><span>Reports</span></a><a href="{{ route('admin.audit-log') }}" class="sidebar-link"><i class="fa-solid fa-clipboard-list"></i><span>Audit Log</span></a><a href="{{ route('admin.taxonomy') }}" class="sidebar-link"><i class="fa-solid fa-tags"></i><span>Categories &amp; Skills</span></a><a href="{{ route('admin.settings') }}" class="sidebar-link"><i class="fa-solid fa-gear"></i><span>Settings</span></a></div>
@endsection
@section('content')
@php
    $targetType = (string) $report->target_type;
    $status = (string) $report->status;
    $statusClass = ['open' => 'status-open', 'in_review' => 'status-review', 'resolved' => 'status-resolved', 'dismissed' => 'status-dismissed'][$status] ?? 'status-default';
    $reasonLabel = ['spam' => 'Spam', 'inappropriate' => 'Inappropriate content', 'fraud' => 'Fraud / Scam', 'fake' => 'Fake / Misleading', 'harassment' => 'Harassment', 'copyright' => 'Copyright', 'other' => 'Other'][$report->reason] ?? 'Other';
    $targetIcon = ['job' => 'briefcase', 'company' => 'building', 'user' => 'user', 'application' => 'file-lines', 'message' => 'envelope'][$targetType] ?? 'flag';
    $targetStatus = $target['status'] ?? '';
    $targetStatusClass = 'status-'.(strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $targetStatus) ?? '')) ?: 'default');
@endphp
<div class="admin-report-details" data-report-id="{{ $report->report_id }}">
    <a href="{{ route('admin.reports') }}" class="back-link"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to reports</a>
    @foreach(['success' => 'admin-alert-success', 'danger' => 'admin-alert-danger', 'warning' => 'admin-alert-warning', 'info' => 'admin-alert-info'] as $key => $class)
        @if(session($key))<div class="admin-alert {{ $class }}" role="status"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><div><span>{{ session($key) }}</span></div></div>@endif
    @endforeach
    <section class="report-header-card"><div class="report-header-main">
        <div class="report-header-icon report-type-{{ $targetType }}"><i class="fa-solid fa-flag" aria-hidden="true"></i></div>
        <div class="report-header-info"><h1>Report #{{ $report->report_id }}</h1><div class="report-header-meta">
            <span class="meta-item"><i class="fa-solid fa-layer-group" aria-hidden="true"></i> Target: {{ ucfirst($targetType) }}</span>
            <span class="meta-item"><i class="fa-solid fa-calendar" aria-hidden="true"></i> {{ \Illuminate\Support\Carbon::parse($report->created_at)->format('M j, Y · H:i') }}</span>
            @if($report->handled_at)<span class="meta-item"><i class="fa-solid fa-check" aria-hidden="true"></i> Handled {{ \Illuminate\Support\Carbon::parse($report->handled_at)->diffForHumans() }}@if($report->handler_name) by {{ $report->handler_name }}@endif</span>@endif
        </div><div class="report-header-badges"><span class="status-badge {{ $statusClass }}">{{ str_replace('_', ' ', ucfirst($status)) }}</span><span class="reason-badge">{{ $reasonLabel }}</span>@if($report->action_taken)<span class="action-badge"><i class="fa-solid fa-bolt" aria-hidden="true"></i> {{ $report->action_taken }}</span>@endif</div></div>
    </div></section>
    <div class="details-grid">
        <div class="details-main">
            <section class="details-card"><div class="details-card-header"><h2>Report Details</h2></div><dl class="info-list">
                <div class="info-row"><dt>Reason</dt><dd>{{ $reasonLabel }}</dd></div>
                <div class="info-row"><dt>Reported At</dt><dd>{{ \Illuminate\Support\Carbon::parse($report->created_at)->format('M j, Y g:i A') }}</dd></div>
                <div class="info-row"><dt>Status</dt><dd><span class="status-badge {{ $statusClass }}">{{ str_replace('_', ' ', ucfirst($status)) }}</span></dd></div>
            </dl>
            @if($report->details)<div class="text-content"><h3>Reporter's message</h3><p>{!! nl2br(e($report->details)) !!}</p></div>@endif
            </section>
            <section class="details-card"><div class="details-card-header"><h2>Reported {{ ucfirst($targetType) }}</h2>@if($target && $target['url'])<a href="{{ $target['url'] }}" class="panel-action">View full <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>@endif</div>
                @if(!$target)<div class="empty-state"><i class="fa-solid fa-circle-question" aria-hidden="true"></i><p>The reported item no longer exists in the database (target {{ $targetType }} #{{ $report->target_id }}).</p></div>
                @else<div class="target-card"><div class="target-card-icon"><i class="fa-solid fa-{{ $targetIcon }}" aria-hidden="true"></i></div><div class="target-card-info"><h3>{{ $target['title'] }}</h3>@if($target['meta'])<p class="target-meta">{{ $target['meta'] }}</p>@endif<div class="target-badges"><span class="target-type-badge target-{{ $targetType }}">{{ ucfirst($targetType) }} #{{ $target['id'] }}</span>@if($targetStatus)<span class="status-badge {{ $targetStatusClass }}">{{ $targetStatus }}</span>@endif</div></div></div>@endif
            </section>
            <section class="details-card"><div class="details-card-header"><h2>Reported By</h2></div><div class="reporter-card"><div class="reporter-avatar">{{ collect(explode(' ', trim((string) ($report->reporter_name ?? 'Guest'))))->filter()->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->join('') }}</div><div class="reporter-info">
                @if((int) $report->reporter_user_id > 0)<a href="{{ route('admin.user-details', ['id' => $report->reporter_user_id]) }}" class="owner-name">{{ $report->reporter_name ?? 'User' }}</a><span class="owner-contact"><i class="fa-solid fa-envelope" aria-hidden="true"></i> {{ $report->reporter_account_email }}</span><span class="owner-contact"><i class="fa-solid fa-user-tag" aria-hidden="true"></i> {{ match ((int) $report->reporter_role) {1 => 'Administrator', 2 => 'Employer', 3 => 'Job Seeker', default => 'User'} }}</span>
                @else<strong class="owner-name">{{ $report->reporter_email ?? 'Guest' }}</strong><span class="owner-contact"><i class="fa-solid fa-user-secret" aria-hidden="true"></i> Guest submission</span>@endif
            </div></div></section>
            <section class="details-card"><div class="details-card-header"><h2>Internal Notes</h2><span class="muted-hint">Visible to admins only</span></div><form action="{{ route('admin.report-action') }}" method="POST" class="notes-form">@csrf<input type="hidden" name="report_id" value="{{ $report->report_id }}"><input type="hidden" name="action" value="save_notes"><textarea name="admin_notes" rows="5" maxlength="2000" placeholder="Add internal notes about your review...">{{ $report->admin_notes }}</textarea><div class="notes-actions"><button type="submit" class="btn btn-light"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save notes</button></div></form></section>
        </div>
        <aside class="details-side">
            <section class="details-card"><div class="details-card-header"><h2>Status</h2></div><div class="info-list"><div class="info-row"><dt>Current</dt><dd><span class="status-badge {{ $statusClass }}">{{ str_replace('_', ' ', ucfirst($status)) }}</span></dd></div>@if($report->action_taken)<div class="info-row"><dt>Action taken</dt><dd>{{ $report->action_taken }}</dd></div>@endif @if($report->handler_name)<div class="info-row"><dt>Handled by</dt><dd>{{ $report->handler_name }}</dd></div>@endif</div></section>
            @if($canReview)<section class="details-card"><div class="details-card-header"><h2>Quick Actions</h2></div><div class="action-list">
                @if($status === 'open')<form action="{{ route('admin.report-action') }}" method="POST" class="action-form">@csrf<input type="hidden" name="report_id" value="{{ $report->report_id }}"><input type="hidden" name="action" value="mark_in_review"><button type="submit" class="action-btn action-btn-primary"><i class="fa-solid fa-eye" aria-hidden="true"></i><span>Mark as in review</span></button></form>@endif
                <form action="{{ route('admin.report-action') }}" method="POST" class="action-form" data-confirm="Dismiss this report? It will be closed without action.">@csrf<input type="hidden" name="report_id" value="{{ $report->report_id }}"><input type="hidden" name="action" value="dismiss"><button type="submit" class="action-btn action-btn-light"><i class="fa-solid fa-circle-xmark" aria-hidden="true"></i><span>Dismiss report</span></button></form>
            </div></section>@endif
            @if($canReview && count($followUpActions))<section class="details-card resolve-card"><div class="details-card-header"><h2>Resolve with Action</h2></div><form action="{{ route('admin.report-action') }}" method="POST" class="resolve-form" id="resolveForm">@csrf<input type="hidden" name="report_id" value="{{ $report->report_id }}"><input type="hidden" name="action" value="resolve_with_action"><div class="resolve-options">
                @foreach($followUpActions as $key => $label)<label class="resolve-option"><input type="radio" name="follow_up" value="{{ $key }}" required><span>{{ $label }}</span></label>@endforeach
                <label class="resolve-option resolve-option-safe"><input type="radio" name="follow_up" value="none" required><span>No follow-up action (close report)</span></label>
            </div><textarea name="action_note" rows="3" maxlength="500" placeholder="Optional note about the action taken..."></textarea><button type="submit" class="btn btn-primary" data-confirm="Resolve this report and apply the selected action?"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Resolve report</button></form></section>@endif
        </aside>
    </div>
</div>
@endsection
