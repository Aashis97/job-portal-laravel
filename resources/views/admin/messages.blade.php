@extends('layouts.dashboard')
@section('title', $message ? 'Message Details' : 'Messages')
@section('role', 'Administrator')
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/admin/messages.css') }}">@endsection
@section('sidebar')
<div class="sidebar-section"><div class="sidebar-section-title">MAIN</div><a href="{{ route('admin.dashboard') }}" class="sidebar-link"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a><a href="{{ route('admin.analytics') }}" class="sidebar-link"><i class="fa-solid fa-chart-line"></i><span>Analytics</span></a></div>
<div class="sidebar-section"><div class="sidebar-section-title">MANAGEMENT</div><a href="{{ route('admin.users') }}" class="sidebar-link"><i class="fa-solid fa-users"></i><span>Users</span></a><a href="{{ route('admin.companies') }}" class="sidebar-link"><i class="fa-solid fa-building"></i><span>Companies</span></a><a href="{{ route('admin.jobs') }}" class="sidebar-link"><i class="fa-solid fa-briefcase"></i><span>Jobs</span></a><a href="{{ route('admin.applications') }}" class="sidebar-link"><i class="fa-solid fa-file-lines"></i><span>Applications</span></a><a href="{{ route('admin.reports') }}" class="sidebar-link"><i class="fa-solid fa-flag"></i><span>Reports</span></a><a href="{{ route('admin.audit-log') }}" class="sidebar-link"><i class="fa-solid fa-clipboard-list"></i><span>Audit Log</span></a><a href="{{ route('admin.taxonomy') }}" class="sidebar-link"><i class="fa-solid fa-tags"></i><span>Categories &amp; Skills</span></a><a href="{{ route('admin.settings') }}" class="sidebar-link"><i class="fa-solid fa-gear"></i><span>Settings</span></a></div>
<div class="sidebar-section"><div class="sidebar-section-title">COMMUNICATION</div><a href="{{ route('admin.notifications') }}" class="sidebar-link"><i class="fa-solid fa-bell"></i><span>Notifications</span></a><a href="{{ route('admin.broadcast') }}" class="sidebar-link"><i class="fa-solid fa-paper-plane"></i><span>Send Broadcast</span></a><a href="{{ route('admin.messages') }}" class="sidebar-link active"><i class="fa-solid fa-envelope"></i><span>Messages</span></a></div>
<div class="sidebar-section"><div class="sidebar-section-title">ACCOUNT</div><a href="{{ route('admin.profile') }}" class="sidebar-link"><i class="fa-solid fa-user"></i><span>My Profile</span></a></div>
@endsection
@section('content')
@php
    $buildQuery = static function (array $overrides = []) use ($tab, $search, $page): array {
        $query = array_filter([
            'tab' => $tab !== 'inbox' ? $tab : null,
            'q' => $search !== '' ? $search : null,
            'page' => $page > 1 ? $page : null,
        ], static fn ($value) => $value !== null);
        $query = array_merge($query, $overrides);

        return array_filter($query, static fn ($value) => $value !== null && $value !== '');
    };
@endphp
<section class="admin-messages-page">
    <header class="messages-page-header">
        <div><span class="messages-eyebrow">Administrator</span><h1>{{ $message ? 'Message details' : 'Messages' }}</h1><p>Review messages sent to or from your administrator account.</p></div>
        <div class="messages-unread-summary"><strong>{{ number_format($unreadTotal) }}</strong><span>Unread</span></div>
    </header>

    @if($viewId > 0 && !$message)
        <div class="messages-alert" role="alert">That message is unavailable or does not belong to your account.</div>
    @endif

    @if($message)
        <article class="admin-message-detail">
            <a class="messages-back-link" href="{{ route('admin.messages', $buildQuery(['page' => null, 'view' => null])) }}"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to messages</a>
            <div class="message-detail-heading">
                <div><span class="message-detail-label">{{ (int) $message->receiver_id === (int) auth()->user()->user_id ? 'From' : 'To' }}</span><h2>{{ (int) $message->receiver_id === (int) auth()->user()->user_id ? $message->sender_name : $message->receiver_name }}</h2><p>{{ (int) $message->receiver_id === (int) auth()->user()->user_id ? $message->sender_email : $message->receiver_email }}</p></div>
                <time datetime="{{ $message->sent_at }}">{{ \Illuminate\Support\Carbon::parse($message->sent_at)->format('M j, Y · g:i A') }}</time>
            </div>
            <h3>{{ trim((string) ($message->subject ?? '')) !== '' ? $message->subject : '(no subject)' }}</h3>
            <div class="message-detail-body">{!! nl2br(e((string) $message->message)) !!}</div>
            @if(!empty($message->attachment))
                <a class="message-attachment" href="{{ $message->attachment }}" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-paperclip" aria-hidden="true"></i> View attachment</a>
            @endif
        </article>
    @else
        <nav class="messages-tabs" aria-label="Message folders">
            <a class="{{ $tab === 'inbox' ? 'active' : '' }}" href="{{ route('admin.messages', $buildQuery(['tab' => null, 'page' => null, 'view' => null])) }}">Inbox <span>{{ number_format($inboxTotal) }}</span></a>
            <a class="{{ $tab === 'sent' ? 'active' : '' }}" href="{{ route('admin.messages', $buildQuery(['tab' => 'sent', 'page' => null, 'view' => null])) }}">Sent <span>{{ number_format($sentTotal) }}</span></a>
        </nav>
        <form class="messages-search" action="{{ route('admin.messages') }}" method="GET">
            @if($tab === 'sent')<input type="hidden" name="tab" value="sent">@endif
            <label class="visually-hidden" for="messageSearch">Search messages</label><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input id="messageSearch" type="search" name="q" value="{{ $search }}" placeholder="Search messages"><button type="submit">Search</button>
        </form>
        <div class="admin-message-list" aria-label="Messages">
            @if($messages->isEmpty())
                <div class="messages-empty"><i class="fa-regular fa-envelope-open" aria-hidden="true"></i><h2>{{ $search !== '' ? 'No matching messages' : ($tab === 'sent' ? 'No sent messages' : 'Your inbox is empty') }}</h2><p>{{ $search !== '' ? 'Try another search term.' : 'Messages addressed to your administrator account will appear here.' }}</p></div>
            @else
                @foreach($messages as $item)
                    @php($preview = trim((string) $item->message))
                    <a class="admin-message-row {{ $tab === 'inbox' && !(bool) $item->is_read ? 'unread' : '' }}" href="{{ route('admin.messages', $buildQuery(['view' => $item->message_id])) }}">
                        <span class="message-row-icon"><i class="fa-regular fa-envelope" aria-hidden="true"></i></span>
                        <span class="message-row-main">
                            <span class="message-row-topline"><strong>{{ $item->peer_name }}</strong><time datetime="{{ $item->sent_at }}">{{ \Illuminate\Support\Carbon::parse($item->sent_at)->format('M j, Y · g:i A') }}</time></span>
                            <span class="message-row-subject">{{ trim((string) ($item->subject ?? '')) !== '' ? $item->subject : '(no subject)' }}</span>
                            <span class="message-row-preview">{{ mb_strlen($preview) > 160 ? mb_substr($preview, 0, 160) . '…' : $preview }}</span>
                        </span>
                        <i class="fa-solid fa-chevron-right message-row-arrow" aria-hidden="true"></i>
                    </a>
                @endforeach
            @endif
        </div>
        @if($totalPages > 1)
            <nav class="messages-pagination" aria-label="Message pages">
                @if($page > 1)<a href="{{ route('admin.messages', $buildQuery(['page' => $page - 1])) }}">Previous</a>@endif
                <span>Page {{ $page }} of {{ $totalPages }}</span>
                @if($page < $totalPages)<a href="{{ route('admin.messages', $buildQuery(['page' => $page + 1])) }}">Next</a>@endif
            </nav>
        @endif
    @endif
</section>
@endsection
