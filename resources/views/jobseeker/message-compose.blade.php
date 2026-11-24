@extends('layouts.dashboard')

@section('title', 'New Message')
@section('role', 'Job Seeker')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/jobseeker/messages.css') }}">
@endsection

@section('content')
<section class="jobseeker-compose-page">
    <a href="{{ route('jobseeker.messages') }}" class="back-link"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Back to messages</a>
    <div class="compose-header">
        <span class="page-tag">Compose</span>
        <h1>New Message</h1>
        <p>Send a message to an employer or a user you've chatted with before.</p>
    </div>

    @foreach(['success', 'danger', 'warning', 'info'] as $type)
        @if(session($type))
            <div class="alert alert-{{ $type }}" role="{{ $type === 'danger' ? 'alert' : 'status' }}">{{ session($type) }}</div>
        @endif
    @endforeach
    @if($errors->any())
        <div class="alert alert-danger" role="alert">{{ implode(' ', $errors->all()) }}</div>
    @endif

    @if($recipients->isEmpty())
        <div class="compose-empty">
            <div class="empty-icon"><i class="fa-solid fa-users-slash" aria-hidden="true"></i></div>
            <h3>No recipients available</h3>
            <p>You can message an employer after you've applied to one of their jobs. Apply to a job first, then come back here.</p>
            <a href="{{ route('jobs.index') }}" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>Browse Jobs</a>
        </div>
    @else
        <form action="{{ route('jobseeker.messages.send') }}" method="POST" class="compose-form" id="composeForm">
            @csrf
            <div class="form-group">
                <label for="receiver_id">Recipient <span class="required">*</span></label>
                <select name="receiver_id" id="receiver_id" required>
                    <option value="">— Choose recipient —</option>
                    @foreach($recipients as $recipient)
                        <option value="{{ $recipient->user_id }}" @selected((int) old('receiver_id', $prefillId) === (int) $recipient->user_id)>
                            {{ $recipient->full_name }} — {{ (int) $recipient->role_id === 2 ? 'Employer' : ((int) $recipient->role_id === 3 ? 'Job Seeker' : 'User') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="subject">Subject</label>
                <input type="text" id="subject" name="subject" maxlength="200" value="{{ old('subject') }}" placeholder="e.g. Question about the PHP Developer position" autocomplete="off">
            </div>
            <div class="form-group">
                <label for="message">Message <span class="required">*</span></label>
                <textarea id="message" name="message" rows="10" maxlength="5000" placeholder="Type your message here..." required>{{ old('message') }}</textarea>
                <div class="char-counter"><span id="messageCharCount">0</span> / 5000</div>
            </div>
            <div class="form-group">
                <label for="attachment">Attachment URL <span class="optional">(optional)</span></label>
                <input type="url" id="attachment" name="attachment" maxlength="500" value="{{ old('attachment') }}" placeholder="https://..." autocomplete="off">
                <small>Paste a link to a file hosted elsewhere (portfolio, LinkedIn, Google Drive, etc.).</small>
            </div>
            <div class="compose-actions">
                <a href="{{ route('jobseeker.messages') }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary" id="composeSubmit"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i>Send Message</button>
            </div>
        </form>
    @endif
</section>
@endsection

@section('js')
<script src="{{ asset('assets/js/jobseeker/messages.js') }}"></script>
@endsection
