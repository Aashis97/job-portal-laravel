@extends('layouts.dashboard')
@section('title', 'Notifications')
@section('role', 'Administrator')
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/admin/notifications.css') }}">@endsection
@section('js')<script src="{{ asset('assets/js/admin/notifications.js') }}"></script>@endsection
@section('sidebar')
<div class="sidebar-section"><div class="sidebar-section-title">MAIN</div><a href="{{ route('admin.dashboard') }}" class="sidebar-link"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a><a href="{{ route('admin.analytics') }}" class="sidebar-link"><i class="fa-solid fa-chart-line"></i><span>Analytics</span></a></div>
<div class="sidebar-section"><div class="sidebar-section-title">MANAGEMENT</div><a href="{{ route('admin.users') }}" class="sidebar-link"><i class="fa-solid fa-users"></i><span>Users</span></a><a href="{{ route('admin.companies') }}" class="sidebar-link"><i class="fa-solid fa-building"></i><span>Companies</span></a><a href="{{ route('admin.jobs') }}" class="sidebar-link"><i class="fa-solid fa-briefcase"></i><span>Jobs</span></a><a href="{{ route('admin.applications') }}" class="sidebar-link"><i class="fa-solid fa-file-lines"></i><span>Applications</span></a><a href="{{ route('admin.reports') }}" class="sidebar-link"><i class="fa-solid fa-flag"></i><span>Reports</span></a><a href="{{ route('admin.audit-log') }}" class="sidebar-link"><i class="fa-solid fa-clipboard-list"></i><span>Audit Log</span></a><a href="{{ route('admin.taxonomy') }}" class="sidebar-link"><i class="fa-solid fa-tags"></i><span>Categories &amp; Skills</span></a><a href="{{ route('admin.settings') }}" class="sidebar-link"><i class="fa-solid fa-gear"></i><span>Settings</span></a></div>
<div class="sidebar-section"><div class="sidebar-section-title">COMMUNICATION</div><a href="{{ route('admin.notifications') }}" class="sidebar-link active"><i class="fa-solid fa-bell"></i><span>Notifications</span></a><a href="{{ route('admin.broadcast') }}" class="sidebar-link"><i class="fa-solid fa-paper-plane"></i><span>Send Broadcast</span></a><a href="{{ route('admin.messages') }}" class="sidebar-link"><i class="fa-solid fa-envelope"></i><span>Messages</span></a></div>
@endsection
@section('content')
@php
    $types = ['System', 'Application', 'Interview', 'Job', 'Message', 'Reminder'];
    $typeClasses = ['application' => 'notif-application', 'interview' => 'notif-interview', 'job' => 'notif-job', 'message' => 'notif-message', 'reminder' => 'notif-reminder'];
    $icons = ['application' => 'fa-solid fa-file-circle-plus', 'interview' => 'fa-solid fa-calendar-check', 'job' => 'fa-solid fa-briefcase', 'message' => 'fa-solid fa-message', 'reminder' => 'fa-solid fa-clock'];
@endphp
<div class="admin-notifications-page">
    <div class="admin-page-header"><div><span class="admin-page-label">COMMUNICATION</span><h1>Notifications</h1><p>Your admin inbox and platform broadcasts.</p></div><div class="page-header-actions"><a href="{{ route('admin.broadcast') }}" class="btn btn-primary"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Send Broadcast</a></div></div>
    @foreach(['success' => 'admin-alert-success', 'danger' => 'admin-alert-danger', 'warning' => 'admin-alert-warning', 'info' => 'admin-alert-info'] as $key => $class)
        @if(session($key))<div class="admin-alert {{ $class }}" role="status"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><div><span>{{ session($key) }}</span></div></div>@endif
    @endforeach
    <div class="notifications-kpi-grid">
        @foreach([['kpi-blue', 'fa-bell', 'total', 'Total'], ['kpi-orange', 'fa-circle-exclamation', 'unread', 'Unread'], ['kpi-green', 'fa-circle-check', 'read', 'Read'], ['kpi-purple', 'fa-calendar-day', 'this_week', 'This week']] as [$style, $icon, $key, $label])
            <div class="kpi-card"><div class="kpi-icon {{ $style }}"><i class="fa-solid {{ $icon }}" aria-hidden="true"></i></div><div><strong>{{ number_format($stats[$key]) }}</strong><span>{{ $label }}</span></div></div>
        @endforeach
    </div>
    <div class="notif-toolbar">
        <div class="admin-tabs notif-tabs">
            @foreach(['all' => ['All', 'total'], 'unread' => ['Unread', 'unread'], 'read' => ['Read', 'read']] as $tabKey => [$label, $countKey])
                <a href="{{ route('admin.notifications', array_filter(['tab' => $tabKey === 'all' ? null : $tabKey, 'type' => $type ?: null, 'q' => $search ?: null])) }}" class="admin-tab {{ $tab === $tabKey ? 'active' : '' }}">@if($tabKey === 'unread')<i class="fa-solid fa-circle" aria-hidden="true"></i>@elseif($tabKey === 'read')<i class="fa-solid fa-check-double" aria-hidden="true"></i>@endif {{ $label }} <span class="tab-count {{ $tabKey === 'unread' ? 'tab-count-warning' : '' }}">{{ number_format($stats[$countKey]) }}</span></a>
            @endforeach
        </div>
        @if($stats['unread'] > 0)<form action="{{ route('admin.notification-action') }}" method="POST" class="inline-form" onsubmit="return confirm('Mark all notifications as read?')">@csrf<input type="hidden" name="action" value="mark_all_read"><input type="hidden" name="tab" value="{{ $tab }}"><button type="submit" class="btn btn-light"><i class="fa-solid fa-check-double" aria-hidden="true"></i> Mark all as read</button></form>@endif
    </div>
    <form action="{{ route('admin.notifications') }}" method="GET" class="admin-filter-form">
        @if($tab !== 'all')<input type="hidden" name="tab" value="{{ $tab }}">@endif
        <div class="filter-field filter-search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input type="search" name="q" value="{{ $search }}" placeholder="Search title or message..." maxlength="100" autocomplete="off"></div>
        <div class="filter-field"><select name="type"><option value="">All Types</option>@foreach($types as $notificationType)<option value="{{ $notificationType }}" @selected($type === $notificationType)>{{ $notificationType }}</option>@endforeach</select></div>
        <div class="filter-actions"><button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter" aria-hidden="true"></i> Apply</button>@if($search !== '' || $type !== '')<a href="{{ route('admin.notifications', $tab !== 'all' ? ['tab' => $tab] : []) }}" class="btn btn-light">Clear</a>@endif</div>
    </form>
    <section class="notifications-list">
        @forelse($notifications as $notification)
            @php($isRead = (bool) $notification->is_read)
            @php($typeClass = $typeClasses[strtolower($notification->notification_type)] ?? 'notif-system')
            @php($icon = $icons[strtolower($notification->notification_type)] ?? 'fa-solid fa-bell')
            <article class="notification-item {{ $isRead ? 'is-read' : 'is-unread' }}" data-id="{{ $notification->notification_id }}">
                <div class="notification-icon {{ $typeClass }}"><i class="{{ $icon }}" aria-hidden="true"></i></div>
                <div class="notification-body"><div class="notification-head"><h3 class="notification-title">{{ $notification->title }}</h3><span class="notification-type-badge {{ $typeClass }}">{{ $notification->notification_type }}</span></div><p class="notification-message">{!! nl2br(e($notification->message)) !!}</p><div class="notification-meta"><span><i class="fa-regular fa-clock" aria-hidden="true"></i> {{ \Illuminate\Support\Carbon::parse($notification->created_at)->diffForHumans() }}</span>@if(!$isRead)<span class="unread-marker"><span class="unread-dot"></span> Unread</span>@endif</div></div>
                <div class="notification-actions">
                    <form action="{{ route('admin.notification-action') }}" method="POST" class="inline-action">@csrf<input type="hidden" name="action" value="{{ $isRead ? 'mark_unread' : 'mark_read' }}"><input type="hidden" name="notification_id" value="{{ $notification->notification_id }}"><input type="hidden" name="tab" value="{{ $tab }}"><input type="hidden" name="page" value="{{ $notifications->currentPage() }}"><button type="submit" class="btn-sm btn-outline" title="{{ $isRead ? 'Mark as unread' : 'Mark as read' }}"><i class="fa-solid {{ $isRead ? 'fa-envelope' : 'fa-check' }}" aria-hidden="true"></i> {{ $isRead ? 'Unread' : 'Read' }}</button></form>
                    <form action="{{ route('admin.notification-action') }}" method="POST" class="inline-action" onsubmit="return confirm('Delete this notification?')">@csrf<input type="hidden" name="action" value="delete"><input type="hidden" name="notification_id" value="{{ $notification->notification_id }}"><input type="hidden" name="tab" value="{{ $tab }}"><input type="hidden" name="page" value="{{ $notifications->currentPage() }}"><button type="submit" class="btn-sm btn-danger" title="Delete"><i class="fa-solid fa-trash" aria-hidden="true"></i></button></form>
                </div>
            </article>
        @empty
            <div class="empty-state-large"><i class="fa-regular fa-bell-slash" aria-hidden="true"></i><h3>No notifications</h3><p>{{ $search !== '' || $type !== '' || $tab !== 'all' ? 'Nothing matches your current filters.' : 'You have no notifications yet.' }}</p></div>
        @endforelse
    </section>
    @if($notifications->lastPage() > 1)<nav class="admin-pagination" aria-label="Pagination">@if($notifications->previousPageUrl())<a href="{{ $notifications->previousPageUrl() }}" class="pagination-btn"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i> Previous</a>@endif<div class="pagination-pages">@foreach($notifications->getUrlRange(max(1, $notifications->currentPage() - 2), min($notifications->lastPage(), $notifications->currentPage() + 2)) as $page => $url)<a href="{{ $url }}" class="pagination-number {{ $page === $notifications->currentPage() ? 'active' : '' }}">{{ $page }}</a>@endforeach</div>@if($notifications->nextPageUrl())<a href="{{ $notifications->nextPageUrl() }}" class="pagination-btn">Next <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>@endif</nav>@endif
</div>
@endsection
