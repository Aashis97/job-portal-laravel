@extends('layouts.dashboard')

@section('title', 'Notifications')
@section('role', 'Job Seeker')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/jobseeker/notifications.css') }}">
@endsection

@section('content')
<section class="jobseeker-notifications-page">
    <div class="page-header">
        <div>
            <span class="page-tag">Alerts</span>
            <h1>Notifications</h1>
            <p>Stay updated with applications, interviews and platform activity.</p>
        </div>
        @if($totalUnread > 0)
            <form action="{{ route('jobseeker.notifications.read-all') }}" method="POST" class="mark-all-form" data-confirm="Mark all notifications as read?">
                @csrf
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check-double" aria-hidden="true"></i>Mark all as read</button>
            </form>
        @endif
    </div>

    @foreach(['success', 'danger', 'warning', 'info'] as $type)
        @if(session($type))
            <div class="alert alert-{{ $type }}" role="{{ $type === 'danger' ? 'alert' : 'status' }}">{{ session($type) }}</div>
        @endif
    @endforeach

    <div class="notification-tabs">
        <a href="{{ route('jobseeker.notifications') }}" class="tab {{ $tab === 'all' ? 'active' : '' }}">All <span class="tab-count">{{ number_format($totalAll) }}</span></a>
        <a href="{{ route('jobseeker.notifications', ['tab' => 'unread']) }}" class="tab tab-unread {{ $tab === 'unread' ? 'active' : '' }}"><i class="fa-solid fa-circle" aria-hidden="true"></i>Unread <span class="tab-count tab-count-warning">{{ number_format($totalUnread) }}</span></a>
        <a href="{{ route('jobseeker.notifications', ['tab' => 'read']) }}" class="tab tab-read {{ $tab === 'read' ? 'active' : '' }}"><i class="fa-solid fa-check-double" aria-hidden="true"></i>Read <span class="tab-count">{{ number_format($totalRead) }}</span></a>
    </div>

    @if($totalAll > 0)
        <form action="{{ route('jobseeker.notifications') }}" method="GET" class="notification-type-form">
            @if($tab !== 'all')
                <input type="hidden" name="tab" value="{{ $tab }}">
            @endif
            <div class="select-wrapper">
                <i class="fa-solid fa-filter" aria-hidden="true"></i>
                <select name="type" onchange="this.form.submit()">
                    <option value="">All types</option>
                    @foreach($allowedTypes as $allowedType)
                        <option value="{{ $allowedType }}" @selected($typeFilter === $allowedType)>{{ $allowedType }}</option>
                    @endforeach
                </select>
            </div>
            @if($typeFilter !== '')
                <a href="{{ route('jobseeker.notifications', $tab !== 'all' ? ['tab' => $tab] : []) }}" class="btn btn-light">Clear</a>
            @endif
        </form>
    @endif

    @if($notifications->isEmpty())
        <div class="notifications-empty">
            <div class="empty-icon"><i class="fa-regular fa-bell-slash" aria-hidden="true"></i></div>
            <h3>{{ $typeFilter !== '' || $tab !== 'all' ? 'No notifications match your filters' : 'No notifications yet' }}</h3>
            <p>{{ $typeFilter !== '' || $tab !== 'all' ? 'Try a different filter or clear the selection.' : "You'll see updates here when employers review your applications." }}</p>
            @if($typeFilter !== '' || $tab !== 'all')
                <a href="{{ route('jobseeker.notifications') }}" class="btn btn-light">Clear Filters</a>
            @else
                <a href="{{ route('jobs.index') }}" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>Browse Jobs</a>
            @endif
        </div>
    @else
        <div class="notifications-list">
            @foreach($notifications as $notification)
                @php
                    $isRead = (int) $notification->is_read === 1;
                    $type = (string) $notification->notification_type;
                    $typeClass = match (strtolower(trim($type))) {
                        'application' => 'notif-application',
                        'interview' => 'notif-interview',
                        'job' => 'notif-job',
                        'message' => 'notif-message',
                        'reminder' => 'notif-reminder',
                        default => 'notif-system',
                    };
                    $icon = match (strtolower(trim($type))) {
                        'application' => 'fa-solid fa-file-circle-plus',
                        'interview' => 'fa-solid fa-calendar-check',
                        'job' => 'fa-solid fa-briefcase',
                        'message' => 'fa-solid fa-message',
                        'reminder' => 'fa-solid fa-clock',
                        default => 'fa-solid fa-bell',
                    };
                @endphp
                <article class="notification-item {{ $isRead ? 'is-read' : 'is-unread' }}" data-id="{{ $notification->notification_id }}">
                    <div class="notification-icon {{ $typeClass }}"><i class="{{ $icon }}" aria-hidden="true"></i></div>
                    <div class="notification-body">
                        <div class="notification-head">
                            <h3 class="notification-title">{{ $notification->title }}</h3>
                            <span class="notification-type-badge {{ $typeClass }}">{{ $type !== '' ? $type : 'System' }}</span>
                        </div>
                        <p class="notification-message">{!! nl2br(e($notification->message)) !!}</p>
                        <div class="notification-meta">
                            <span class="notification-time"><i class="fa-regular fa-clock" aria-hidden="true"></i>{{ \Illuminate\Support\Carbon::parse($notification->created_at)->diffForHumans() }}</span>
                            @unless($isRead)
                                <span class="unread-marker"><span class="unread-dot"></span>Unread</span>
                            @endunless
                        </div>
                    </div>
                    <div class="notification-actions">
                        @unless($isRead)
                            <form action="{{ route('jobseeker.notifications.read') }}" method="POST" class="inline-action">
                                @csrf
                                <input type="hidden" name="notification_id" value="{{ $notification->notification_id }}">
                                <input type="hidden" name="tab" value="{{ $tab }}">
                                <input type="hidden" name="type" value="{{ $typeFilter }}">
                                <input type="hidden" name="page" value="{{ $currentPage }}">
                                <button type="submit" class="btn btn-light btn-sm" title="Mark as read"><i class="fa-solid fa-check" aria-hidden="true"></i>Read</button>
                            </form>
                        @else
                            <span class="read-status"><i class="fa-solid fa-check-double" aria-hidden="true"></i>Read</span>
                        @endunless
                    </div>
                </article>
            @endforeach
        </div>

        @if($totalPages > 1)
            @php
                $paginationParams = array_filter([
                    'tab' => $tab !== 'all' ? $tab : null,
                    'type' => $typeFilter !== '' ? $typeFilter : null,
                ]);
            @endphp
            <nav class="notifications-pagination" aria-label="Pagination">
                @if($currentPage > 1)
                    <a href="{{ route('jobseeker.notifications', array_merge($paginationParams, ['page' => $currentPage - 1])) }}" class="pagination-btn"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i> Previous</a>
                @endif
                <div class="pagination-pages">
                    @php
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($totalPages, $currentPage + 2);
                    @endphp
                    @if($startPage > 1)
                        <a href="{{ route('jobseeker.notifications', array_merge($paginationParams, ['page' => 1])) }}" class="pagination-number">1</a>
                        @if($startPage > 2)<span class="pagination-ellipsis">…</span>@endif
                    @endif
                    @for($pageNumber = $startPage; $pageNumber <= $endPage; $pageNumber++)
                        <a href="{{ route('jobseeker.notifications', array_merge($paginationParams, ['page' => $pageNumber])) }}" class="pagination-number {{ $pageNumber === $currentPage ? 'active' : '' }}">{{ $pageNumber }}</a>
                    @endfor
                    @if($endPage < $totalPages)
                        @if($endPage < $totalPages - 1)<span class="pagination-ellipsis">…</span>@endif
                        <a href="{{ route('jobseeker.notifications', array_merge($paginationParams, ['page' => $totalPages])) }}" class="pagination-number">{{ $totalPages }}</a>
                    @endif
                </div>
                @if($currentPage < $totalPages)
                    <a href="{{ route('jobseeker.notifications', array_merge($paginationParams, ['page' => $currentPage + 1])) }}" class="pagination-btn">Next <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
                @endif
            </nav>
        @endif
    @endif
</section>
@endsection

@section('js')
<script src="{{ asset('assets/js/jobseeker/notifications.js') }}"></script>
@endsection
