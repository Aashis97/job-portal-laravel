@extends('layouts.dashboard')

@section('title', 'New Message')
@section('role', 'Employer')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/jobseeker/messages.css') }}">
@endsection

@section('content')
<section class="jobseeker-compose-page">
    <a href="{{ route('employer.messages') }}" class="back-link"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Back to messages</a>
    <div class="compose-header"><span class="page-tag">Compose</span><h1>New Message</h1><p>Message a candidate or team member.</p></div>

    @foreach(['success', 'danger', 'warning', 'info'] as $type)
        @if(session($type))<div class="alert alert-{{ $type }}" role="{{ $type === 'danger' ? 'alert' : 'status' }}">{{ session($type) }}</div>@endif
    @endforeach
    @if($errors->any())<div class="alert alert-danger" role="alert">{{ implode(' ', $errors->all()) }}</div>@endif

    @if($recipients->isEmpty())
        <div class="compose-empty"><div class="empty-icon"><i class="fa-solid fa-users-slash" aria-hidden="true"></i></div><h3>No recipients available</h3><p>There are no active accounts available to message.</p><a href="{{ route('employer.messages') }}" class="btn btn-primary">Back to Messages</a></div>
    @else
        <form action="{{ route('employer.messages.send') }}" method="POST" class="compose-form" id="composeForm">
            @csrf
            <div class="form-group"><label for="receiver_id">Recipient <span class="required">*</span></label><select name="receiver_id" id="receiver_id" required><option value="">— Choose recipient —</option>@foreach($recipients as $recipient)<option value="{{ $recipient->user_id }}" @selected((int) old('receiver_id', $prefillId) === (int) $recipient->user_id)>{{ $recipient->full_name }} — {{ match ((int) $recipient->role_id) { 1 => 'Admin', 2 => 'Employer', 3 => 'Job Seeker', default => 'User' } }}</option>@endforeach</select></div>
            <div class="form-group"><label for="subject">Subject</label><input type="text" id="subject" name="subject" maxlength="200" value="{{ old('subject') }}" placeholder="Optional subject" autocomplete="off"></div>
            <div class="form-group"><label for="message">Message <span class="required">*</span></label><textarea id="message" name="message" rows="6" maxlength="5000" placeholder="Type your message here..." required>{{ old('message') }}</textarea><div class="char-counter"><span id="messageCharCount">0</span> / 5000</div></div>
            <div class="form-group"><label for="attachment">Attachment URL <span class="optional">(optional)</span></label><input type="url" id="attachment" name="attachment" maxlength="500" value="{{ old('attachment') }}" placeholder="https://..." autocomplete="off"><small>Paste a link to a file hosted elsewhere.</small></div>
            <div class="compose-actions"><a href="{{ route('employer.messages') }}" class="btn btn-light">Cancel</a><button type="submit" class="btn btn-primary" id="composeSubmit"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i>Send Message</button></div>
        </form>
    @endif
</section>
@endsection

@section('js')
<script src="{{ asset('assets/js/jobseeker/messages.js') }}"></script>
@endsection
