@extends('layouts.dashboard')

@section('title', 'Notifications')
@section('role', 'Employer')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/employer/notifications.css') }}">
@endsection

@section('content')
<section class="employer-notifications-page">
    <section class="notifications-page-header">
        <div class="notifications-header-content">
            <div class="notifications-title-wrapper">
                <div class="notifications-title-icon"><i class="fa-solid fa-bell"></i></div>
                <div><span class="notifications-eyebrow">Employer Panel</span><h1>Notifications</h1><p>Stay updated with applications, interviews, candidates and recruitment activities.</p></div>
            </div>
            @if($unreadCount > 0)
                <div class="notification-header-stat"><span class="notification-stat-number">{{ $unreadCount }}</span><span class="notification-stat-label">Unread</span></div>
            @endif
        </div>
    </section>

    <section class="notifications-toolbar">
        <div class="notifications-toolbar-left"><span class="notification-total"><i class="fa-solid fa-layer-group"></i>{{ $total }} {{ $total === 1 ? 'Notification' : 'Notifications' }}</span></div>
        <div class="notifications-toolbar-actions">
            @if($unreadCount > 0)
                <button type="button" class="notification-action-btn" id="markAllReadBtn" data-action="mark-all-read"><i class="fa-solid fa-check-double"></i>Mark All as Read</button>
            @endif
        </div>
    </section>

    @if(session('notification_error'))
        <div class="notification-status-message is-error" role="alert">{{ session('notification_error') }}</div>
    @endif

    <section class="notifications-container">
        @if($notifications->isEmpty())
            <div class="notifications-empty">
                <div class="notifications-empty-icon"><i class="fa-regular fa-bell-slash"></i></div>
                <h2>No Notifications</h2>
                <p>You don't have any notifications yet. New recruitment activities will appear here.</p>
                <a href="{{ route('employer.dashboard') }}" class="notifications-empty-btn"><i class="fa-solid fa-arrow-left"></i>Back to Dashboard</a>
            </div>
        @else
            <div class="notification-list">
                @foreach($notifications as $notification)
                    @php
                        $isRead = (int) $notification->is_read === 1;
                        $type = trim((string) $notification->notification_type);
                        $typeKey = strtolower($type);
                        $icon = match ($typeKey) {
                            'new application', 'application' => 'fa-solid fa-file-circle-plus',
                            'interview scheduled', 'interview' => 'fa-solid fa-calendar-check',
                            'interview rescheduled' => 'fa-solid fa-calendar-days',
                            'interview cancelled', 'interview canceled' => 'fa-solid fa-calendar-xmark',
                            'company approved' => 'fa-solid fa-circle-check',
                            'company rejected' => 'fa-solid fa-circle-xmark',
                            'ai ranking completed', 'ai ranking' => 'fa-solid fa-wand-magic-sparkles',
                            'candidate selected' => 'fa-solid fa-user-check',
                            'candidate rejected' => 'fa-solid fa-user-xmark',
                            default => 'fa-solid fa-bell',
                        };
                        $typeClass = match ($typeKey) {
                            'new application', 'application', 'company approved', 'candidate selected' => 'notification-success',
                            'interview scheduled', 'interview' => 'notification-primary',
                            'interview rescheduled' => 'notification-warning',
                            'interview cancelled', 'interview canceled', 'company rejected', 'candidate rejected' => 'notification-danger',
                            'ai ranking completed', 'ai ranking' => 'notification-ai',
                            default => 'notification-default',
                        };
                    @endphp
                    <article class="notification-card {{ $isRead ? 'is-read' : 'is-unread' }}" data-notification-id="{{ $notification->notification_id }}">
                        <div class="notification-card-icon {{ $typeClass }}"><i class="{{ $icon }}"></i></div>
                        <div class="notification-card-content">
                            <div class="notification-card-top">
                                <div class="notification-card-title"><h3>{{ $notification->title }}</h3>@unless($isRead)<span class="unread-dot"></span>@endunless</div>
                                <span class="notification-type {{ $typeClass }}">{{ $type !== '' ? $type : 'General' }}</span>
                            </div>
                            <p class="notification-message">{!! nl2br(e($notification->message)) !!}</p>
                            <div class="notification-card-bottom">
                                <span class="notification-time"><i class="fa-regular fa-clock"></i>{{ \Illuminate\Support\Carbon::parse($notification->created_at)->format('M d, Y · h:i A') }}</span>
                                <div class="notification-card-actions">
                                    @unless($isRead)
                                        <button type="button" class="notification-small-btn mark-read-btn" data-notification-id="{{ $notification->notification_id }}" data-action="mark-read"><i class="fa-solid fa-check"></i>Mark as Read</button>
                                    @else
                                        <span class="read-status"><i class="fa-solid fa-check-double"></i>Read</span>
                                    @endunless
                                    <button type="button" class="notification-small-btn delete-notification-btn" data-notification-id="{{ $notification->notification_id }}" data-action="delete-notification"><i class="fa-solid fa-trash"></i>Delete</button>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            @if($totalPages > 1)
                <nav class="notification-pagination" aria-label="Notification pagination">
                    @if($page > 1)<a href="{{ route('employer.notifications', ['page' => $page - 1]) }}" class="pagination-btn"><i class="fa-solid fa-chevron-left"></i>Previous</a>@endif
                    <div class="pagination-pages">
                        @for($pageNumber = max(1, $page - 2); $pageNumber <= min($totalPages, $page + 2); $pageNumber++)
                            <a href="{{ route('employer.notifications', ['page' => $pageNumber]) }}" class="pagination-number {{ $pageNumber === $page ? 'active' : '' }}">{{ $pageNumber }}</a>
                        @endfor
                    </div>
                    @if($page < $totalPages)<a href="{{ route('employer.notifications', ['page' => $page + 1]) }}" class="pagination-btn">Next<i class="fa-solid fa-chevron-right"></i></a>@endif
                </nav>
            @endif
        @endif
    </section>
</section>
<script>
window.employerNotifications = {
    csrfToken: @json(csrf_token()),
    markReadUrl: @json(route('employer.notifications.read')),
    markAllReadUrl: @json(route('employer.notifications.read-all')),
    deleteUrl: @json(route('employer.notifications.delete')),
    unreadCount: @json($unreadCount),
    totalNotifications: @json($total),
    currentPage: @json($page)
};
</script>
@endsection

@section('js')
<script src="{{ asset('assets/js/employer/notifications.js') }}"></script>
@endsection
