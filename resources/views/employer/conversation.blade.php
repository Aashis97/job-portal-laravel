@extends('layouts.dashboard')

@section('title', 'Conversation')
@section('role', 'Employer')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/jobseeker/messages.css') }}">
@endsection

@section('content')
@php
    $initials = collect(preg_split('/\s+/', trim((string) $peer->full_name)) ?: [])
        ->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
    $originalSubject = (string) ($thread->first()->subject ?? '');
    $replySubject = $originalSubject !== '' ? (stripos($originalSubject, 're:') === 0 ? $originalSubject : 'Re: ' . $originalSubject) : '';
@endphp
<section class="jobseeker-conversation-page" data-chat-endpoint="{{ route('messages.chat') }}" data-peer-id="{{ $peer->user_id }}" data-current-user-id="{{ $currentUserId }}">
    <a href="{{ route('employer.messages') }}" class="back-link"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Back to inbox</a>
    <header class="conversation-header">
        <div class="conversation-avatar">
            @if($peer->profile_image && $peer->profile_image !== 'default.png')
                <img src="{{ asset('assets/uploads/profiles/' . $peer->profile_image) }}" alt="" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"><span class="avatar-fallback" style="display:none">{{ $initials ?: 'U' }}</span>
            @else
                <span class="avatar-fallback">{{ $initials ?: 'U' }}</span>
            @endif
        </div>
        <div class="conversation-header-info"><h1>{{ $peer->full_name }}</h1><div class="conversation-meta"><span><i class="fa-solid fa-user-tag" aria-hidden="true"></i>{{ match ((int) $peer->role_id) { 1 => 'Administrator', 2 => 'Employer', 3 => 'Job Seeker', default => 'User' } }}</span><span><i class="fa-solid fa-envelope" aria-hidden="true"></i>{{ $peer->email }}</span></div></div>
    </header>

    @foreach(['success', 'danger', 'warning', 'info'] as $type)
        @if(session($type))<div class="alert alert-{{ $type }}" role="{{ $type === 'danger' ? 'alert' : 'status' }}">{{ session($type) }}</div>@endif
    @endforeach

    <div class="conversation-thread" id="conversationThread" data-last-message-id="{{ $lastMessageId }}">
        @if($thread->isEmpty())
            <div class="conversation-empty"><i class="fa-regular fa-comments" aria-hidden="true"></i><p>No messages yet with {{ $peer->full_name }}.</p><span>Write the first message below.</span></div>
        @else
            @php $lastDate = ''; @endphp
            @foreach($thread as $message)
                @php
                    $sentAt = \Illuminate\Support\Carbon::parse($message->sent_at);
                    $messageDate = $sentAt->format('Y-m-d');
                    $isMine = (int) $message->sender_id === $currentUserId;
                @endphp
                @if($messageDate !== $lastDate)<div class="thread-date-separator"><span>{{ $sentAt->format('F j, Y') }}</span></div>@php $lastDate = $messageDate; @endphp @endif
                <div class="thread-bubble {{ $isMine ? 'bubble-mine' : 'bubble-theirs' }}" data-message-date="{{ $messageDate }}">
                    <div class="bubble-meta"><span class="bubble-author">{{ $isMine ? 'You' : $peer->full_name }}</span><span class="bubble-time">{{ $sentAt->format('g:i A') }}</span></div>
                    @if($message->subject)<div class="bubble-subject">{{ $message->subject }}</div>@endif
                    <div class="bubble-body">{!! nl2br(e($message->message)) !!}</div>
                    @if($message->attachment)<div class="bubble-attachment"><i class="fa-solid fa-paperclip" aria-hidden="true"></i>@if(filter_var($message->attachment, FILTER_VALIDATE_URL))<a href="{{ $message->attachment }}" target="_blank" rel="noopener noreferrer">{{ basename($message->attachment) }}</a>@else<span>{{ $message->attachment }}</span>@endif</div>@endif
                </div>
            @endforeach
        @endif
    </div>

    <p class="chat-live-status" role="status" aria-live="polite"></p>
    <form action="{{ route('messages.chat') }}" method="POST" class="reply-form" id="chatReplyForm" data-chat-form>
        @csrf
        <input type="hidden" name="csrf_token" value="{{ csrf_token() }}">
        <input type="hidden" name="receiver_id" value="{{ $peer->user_id }}">
        <input type="hidden" name="subject" value="{{ $replySubject }}">
        <div class="reply-header"><i class="fa-solid fa-reply" aria-hidden="true"></i><span>Reply to {{ $peer->full_name }}</span></div>
        <div class="reply-body"><textarea name="message" id="replyMessage" rows="4" maxlength="5000" placeholder="Type your reply..." required></textarea><div class="reply-footer"><span class="char-counter"><span id="replyCharCount">0</span> / 5000</span><button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i>Send Reply</button></div></div>
    </form>
</section>
@endsection

@section('js')
<script src="{{ asset('assets/js/jobseeker/messages.js') }}"></script>
@endsection
