@extends('layouts.dashboard')

@section('title', 'Messages')
@section('role', 'Employer')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/jobseeker/messages.css') }}">
@endsection

@section('content')
<section class="jobseeker-messages-page">
    <div class="page-header">
        <div><span class="page-tag">Inbox</span><h1>Messages</h1><p>Manage conversations with candidates, employers, and team members.</p></div>
        <a href="{{ route('employer.messages.compose') }}" class="btn btn-primary"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>New Message</a>
    </div>

    @foreach(['success', 'danger', 'warning', 'info'] as $type)
        @if(session($type))
            <div class="alert alert-{{ $type }}" role="{{ $type === 'danger' ? 'alert' : 'status' }}">{{ session($type) }}</div>
        @endif
    @endforeach

    <div class="messages-tabs">
        <a href="{{ route('employer.messages') }}" class="tab {{ $tab === 'inbox' ? 'active' : '' }}"><i class="fa-solid fa-inbox" aria-hidden="true"></i>Inbox <span class="tab-count {{ $unreadTotal > 0 ? 'tab-count-warning' : '' }}">{{ number_format($unreadTotal > 0 ? $unreadTotal : $inboxTotal) }}</span></a>
        <a href="{{ route('employer.messages', ['tab' => 'sent']) }}" class="tab {{ $tab === 'sent' ? 'active' : '' }}"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i>Sent <span class="tab-count">{{ number_format($sentTotal) }}</span></a>
    </div>

    <form action="{{ route('employer.messages') }}" method="GET" class="messages-search-form">
        @if($tab !== 'inbox')<input type="hidden" name="tab" value="{{ $tab }}">@endif
        <div class="search-wrapper"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input type="search" name="q" value="{{ $search }}" placeholder="Search messages..." maxlength="100" autocomplete="off"></div>
        @if($search !== '')<a href="{{ route('employer.messages', $tab !== 'inbox' ? ['tab' => $tab] : []) }}" class="btn btn-light">Clear</a>@endif
    </form>

    @if($messages->isEmpty())
        <div class="messages-empty"><div class="empty-icon"><i class="fa-regular fa-envelope" aria-hidden="true"></i></div><h3>{{ $search !== '' ? 'No messages match your search' : ($tab === 'sent' ? 'No sent messages' : 'Your inbox is empty') }}</h3><p>{{ $search !== '' ? 'Try a different keyword.' : 'Start a conversation with a candidate or team member.' }}</p><a href="{{ route('employer.messages.compose') }}" class="btn btn-primary"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>New Message</a></div>
    @else
        <div class="messages-list">
            @foreach($messages as $message)
                @php
                    $isUnread = $tab === 'inbox' && (int) $message->is_read === 0;
                    $preview = mb_strimwidth(trim((string) $message->message), 0, 140, '…');
                    $initials = collect(preg_split('/\s+/', trim((string) $message->peer_name)) ?: [])
                        ->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
                @endphp
                <a href="{{ route('employer.conversation', ['user' => $message->peer_id]) }}" class="message-row {{ $isUnread ? 'is-unread' : '' }}">
                    <div class="message-avatar">
                        @if($message->peer_avatar && $message->peer_avatar !== 'default.png')
                            <img src="{{ asset('assets/uploads/profiles/' . $message->peer_avatar) }}" alt="" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"><span class="avatar-fallback" style="display:none">{{ $initials ?: 'U' }}</span>
                        @else
                            <span class="avatar-fallback">{{ $initials ?: 'U' }}</span>
                        @endif
                        @if($isUnread)<span class="unread-dot" aria-hidden="true"></span>@endif
                    </div>
                    <div class="message-body"><div class="message-head"><span class="peer-name">{{ $message->peer_name }}</span><span class="message-time">{{ \Illuminate\Support\Carbon::parse($message->sent_at)->diffForHumans() }}</span></div><div class="message-subject">{{ $message->subject ?: '(no subject)' }}</div><div class="message-preview">@if($tab === 'sent')<span class="sent-label">You:</span>@endif{{ $preview }}</div></div>
                    <div class="message-arrow"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></div>
                </a>
            @endforeach
        </div>
        @if($totalPages > 1)
            @php
                $queryParams = array_filter(['tab' => $tab !== 'inbox' ? $tab : null, 'q' => $search !== '' ? $search : null]);
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
            @endphp
            <nav class="messages-pagination" aria-label="Pagination">
                @if($page > 1)<a href="{{ route('employer.messages', array_merge($queryParams, ['page' => $page - 1])) }}" class="pagination-btn"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i> Previous</a>@endif
                <div class="pagination-pages">@for($pageNumber = $startPage; $pageNumber <= $endPage; $pageNumber++)<a href="{{ route('employer.messages', array_merge($queryParams, ['page' => $pageNumber])) }}" class="pagination-number {{ $pageNumber === $page ? 'active' : '' }}">{{ $pageNumber }}</a>@endfor</div>
                @if($page < $totalPages)<a href="{{ route('employer.messages', array_merge($queryParams, ['page' => $page + 1])) }}" class="pagination-btn">Next <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>@endif
            </nav>
        @endif
    @endif
</section>
@endsection
