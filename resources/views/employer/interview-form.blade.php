@extends('layouts.dashboard')

@section('title', $isEdit ? 'Edit Interview' : 'Schedule Interview')
@section('role', 'Employer')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/employer/interviews.css') }}">
@endsection

@section('content')
@php
    $candidateName = trim((string) $application->full_name) ?: 'Unknown Candidate';
    $nameParts = preg_split('/\s+/', $candidateName, -1, PREG_SPLIT_NO_EMPTY);
    $initials = mb_strtoupper(mb_substr($nameParts[0] ?? 'C', 0, 1));
    if (count($nameParts) > 1) {
        $initials .= mb_strtoupper(mb_substr($nameParts[count($nameParts) - 1], 0, 1));
    }
    $selectedType = old('interview_type', $interview->interview_type ?? '');
    $currentStatus = $interview->interview_status ?? 'Scheduled';
@endphp
<div class="employer-interview-edit-page">
    <section class="page-header">
        <div class="page-header-content">
            <a href="{{ $isEdit ? route('employer.interview-details', ['interview_id' => $interview->interview_id]) : route('employer.applicant-details', ['application_id' => $application->application_id]) }}" class="back-link"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i>{{ $isEdit ? 'Back to Interview' : 'Back to Applicant' }}</a>
            <div class="page-title-row"><div class="page-header-icon"><i class="fa-solid {{ $isEdit ? 'fa-calendar-pen' : 'fa-calendar-plus' }}" aria-hidden="true"></i></div><div><span class="page-eyebrow">Interview Management</span><h1>{{ $isEdit ? 'Edit Interview' : 'Schedule Interview' }}</h1><p>{{ $isEdit ? 'Update the interview schedule and meeting information for ' : 'Arrange an interview with ' }}{{ $candidateName }}.</p></div></div>
        </div>
    </section>

    @if(session('error'))<div class="alert alert-danger" role="alert">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger" role="alert">{{ implode(' ', $errors->all()) }}</div>@endif

    @if(!$isEdit && $existingInterview)
        <div class="interview-warning"><div class="interview-warning-icon"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i></div><div class="interview-warning-content"><strong>An interview already exists</strong><p>This application already has an interview scheduled. Review or edit the existing interview instead.</p><a href="{{ route('employer.interview-details', ['interview_id' => $existingInterview->interview_id]) }}" class="btn btn-secondary"><i class="fa-solid fa-eye" aria-hidden="true"></i>View Interview</a></div></div>
    @endif

    <section class="interview-summary-card">
        <div class="summary-candidate"><div class="candidate-avatar">{{ $initials }}</div><div><span class="summary-label">Candidate</span><h2>{{ $candidateName }}</h2><a href="mailto:{{ $application->email }}"><i class="fa-solid fa-envelope" aria-hidden="true"></i>{{ $application->email }}</a></div></div>
        <div class="summary-divider"></div>
        <div class="summary-job"><span class="summary-label">Position</span><h3>{{ $application->job_title }}</h3><span><i class="fa-solid fa-layer-group" aria-hidden="true"></i>{{ $application->category_name }}</span></div>
        @if($isEdit)<div class="summary-status"><span class="summary-label">Current Status</span><span class="interview-status status-{{ strtolower(str_replace([' ', '-'], '_', $currentStatus)) }}">{{ $currentStatus }}</span></div>@endif
    </section>

    <section class="interview-form-layout">
        <div class="interview-form-card">
            <div class="form-card-header"><div><span class="card-eyebrow">Interview Schedule</span><h2>{{ $isEdit ? 'Update Interview' : 'Interview Details' }}</h2><p>{{ $isEdit ? 'Modify the interview information below.' : 'Enter the interview information below.' }}</p></div><div class="form-header-icon"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i></div></div>

            @if(!$isEdit && $existingInterview)
                <div class="already-scheduled"><div class="already-scheduled-icon"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i></div><h3>Interview Already Scheduled</h3><p>An interview already exists for this application.</p><a href="{{ route('employer.interview-details', ['interview_id' => $existingInterview->interview_id]) }}" class="btn btn-primary"><i class="fa-solid fa-eye" aria-hidden="true"></i>View Interview</a></div>
            @else
                <form action="{{ $isEdit ? route('employer.interviews.update') : route('employer.interviews.store') }}" method="POST" class="interview-form" id="{{ $isEdit ? 'editInterviewForm' : 'scheduleInterviewForm' }}" novalidate>
                    @csrf
                    @if($isEdit)
                        <input type="hidden" name="interview_id" value="{{ $interview->interview_id }}">
                    @else
                        <input type="hidden" name="application_id" value="{{ $application->application_id }}">
                    @endif

                    <div class="form-section">
                        <div class="form-section-heading"><i class="fa-solid fa-user-tie" aria-hidden="true"></i><div><h3>Interviewer Information</h3><p>Enter the person responsible for conducting the interview.</p></div></div>
                        <div class="form-group"><label for="interviewer_name">Interviewer Name <span class="required">*</span></label><div class="input-with-icon"><i class="fa-solid fa-user-tie" aria-hidden="true"></i><input type="text" id="interviewer_name" name="interviewer_name" value="{{ old('interviewer_name', $interview->interviewer_name ?? '') }}" maxlength="150" placeholder="Enter interviewer name" autocomplete="name" required></div><small class="field-help">Maximum 150 characters.</small>@error('interviewer_name')<small class="field-error">{{ $message }}</small>@enderror</div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-heading"><i class="fa-solid fa-video" aria-hidden="true"></i><div><h3>Interview Method</h3><p>Choose how the interview will be conducted.</p></div></div>
                        <div class="interview-type-options">
                            @foreach(['Online' => 'fa-video', 'Onsite' => 'fa-location-dot', 'Phone' => 'fa-phone'] as $type => $icon)
                                <label class="interview-type-option"><input type="radio" name="interview_type" value="{{ $type }}" @checked($selectedType === $type) required><span class="type-option-content"><span class="type-option-icon"><i class="fa-solid {{ $icon }}" aria-hidden="true"></i></span><span><strong>{{ $type === 'Onsite' ? 'In-person' : $type }}</strong><small>{{ $type === 'Online' ? 'Video meeting' : ($type === 'Onsite' ? 'Physical location' : 'Phone call') }}</small></span></span></label>
                            @endforeach
                        </div>
                        @error('interview_type')<small class="field-error">{{ $message }}</small>@enderror
                    </div>

                    <div class="form-section">
                        <div class="form-section-heading"><i class="fa-regular fa-calendar-days" aria-hidden="true"></i><div><h3>Date &amp; Time</h3><p>Select when the interview will take place.</p></div></div>
                        <div class="form-row">
                            <div class="form-group"><label for="interview_date">Interview Date <span class="required">*</span></label><div class="input-with-icon"><i class="fa-regular fa-calendar" aria-hidden="true"></i><input type="date" id="interview_date" name="interview_date" value="{{ old('interview_date', $interview->interview_date ?? '') }}" min="{{ now()->toDateString() }}" required></div>@error('interview_date')<small class="field-error">{{ $message }}</small>@enderror</div>
                            <div class="form-group"><label for="interview_time">Interview Time <span class="required">*</span></label><div class="input-with-icon"><i class="fa-regular fa-clock" aria-hidden="true"></i><input type="time" id="interview_time" name="interview_time" value="{{ old('interview_time', isset($interview->interview_time) ? substr($interview->interview_time, 0, 5) : '') }}" required></div>@error('interview_time')<small class="field-error">{{ $message }}</small>@enderror</div>
                        </div>
                    </div>

                    <div class="form-section conditional-section" id="onlineMeetingSection">
                        <div class="form-section-heading"><i class="fa-solid fa-video" aria-hidden="true"></i><div><h3>Online Meeting</h3><p>Provide the meeting URL for online interviews.</p></div></div>
                        <div class="form-group"><label for="meeting_link">Meeting Link <span class="required conditional-required">*</span></label><div class="input-with-icon"><i class="fa-solid fa-link" aria-hidden="true"></i><input type="url" id="meeting_link" name="meeting_link" value="{{ old('meeting_link', $interview->meeting_link ?? '') }}" maxlength="1000" placeholder="https://meet.google.com/..."></div><small class="field-help">Example: Google Meet, Microsoft Teams, Zoom or another HTTPS meeting URL.</small>@error('meeting_link')<small class="field-error">{{ $message }}</small>@enderror</div>
                    </div>

                    <div class="form-section conditional-section" id="locationSection">
                        <div class="form-section-heading"><i class="fa-solid fa-location-dot" aria-hidden="true"></i><div><h3>Interview Location</h3><p>Provide the physical location for in-person interviews.</p></div></div>
                        <div class="form-group"><label for="location">Location <span class="required conditional-required">*</span></label><div class="input-with-icon"><i class="fa-solid fa-location-dot" aria-hidden="true"></i><input type="text" id="location" name="location" value="{{ old('location', $interview->location ?? '') }}" maxlength="255" placeholder="Office address, meeting room..."></div>@error('location')<small class="field-error">{{ $message }}</small>@enderror</div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-heading"><i class="fa-solid fa-message" aria-hidden="true"></i><div><h3>Interview Remarks</h3><p>Add instructions or additional information for this interview.</p></div></div>
                        <div class="form-group"><label for="remarks">Remarks</label><textarea id="remarks" name="remarks" rows="6" maxlength="2000" placeholder="Enter interview instructions, preparation notes or other relevant information...">{{ old('remarks', $interview->remarks ?? '') }}</textarea><div class="textarea-footer"><small class="field-help">Maximum 2000 characters.</small><span class="character-count" id="remarksCount">{{ strlen((string) old('remarks', $interview->remarks ?? '')) }}/2000</span></div>@error('remarks')<small class="field-error">{{ $message }}</small>@enderror</div>
                    </div>

                    @if($isEdit)
                        <div class="form-section"><div class="form-section-heading"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><div><h3>Interview Status</h3><p>The current status is preserved while editing schedule information.</p></div></div><div class="current-status-display"><span class="status-indicator"></span><div><strong>{{ $currentStatus }}</strong><span>Status is managed separately.</span></div></div></div>
                    @endif

                    <div class="form-actions"><a href="{{ $isEdit ? route('employer.interview-details', ['interview_id' => $interview->interview_id]) : route('employer.applicant-details', ['application_id' => $application->application_id]) }}" class="btn btn-light"><i class="fa-solid fa-xmark" aria-hidden="true"></i>Cancel</a><button type="submit" class="btn btn-primary" id="{{ $isEdit ? 'updateInterviewButton' : 'scheduleInterviewButton' }}"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i>{{ $isEdit ? 'Save Changes' : 'Schedule Interview' }}</button></div>
                </form>
            @endif
        </div>

        <aside class="interview-edit-sidebar">
            <section class="edit-sidebar-card">
                <div class="sidebar-card-heading"><div><span>Candidate</span><h3>{{ $candidateName }}</h3></div><i class="fa-solid fa-user" aria-hidden="true"></i></div>
                <div class="schedule-preview"><div class="preview-item"><i class="fa-solid fa-envelope" aria-hidden="true"></i><div><span>Email</span><strong>{{ $application->email }}</strong></div></div><div class="preview-item"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><div><span>Application Status</span><strong>{{ $application->application_status }}</strong></div></div><div class="preview-item"><i class="fa-solid fa-clock" aria-hidden="true"></i><div><span>Availability</span><strong>{{ $application->availability ?: 'Not specified' }}</strong></div></div></div>
                <a href="{{ route('employer.applicant-details', ['application_id' => $application->application_id]) }}" class="candidate-details-link"><span>View Candidate Details</span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </section>
            <section class="edit-sidebar-card"><div class="sidebar-card-heading"><div><span>Applied Job</span><h3>{{ $application->job_title }}</h3></div><i class="fa-solid fa-briefcase" aria-hidden="true"></i></div><div class="schedule-preview"><div class="preview-item"><i class="fa-solid fa-layer-group" aria-hidden="true"></i><div><span>Category</span><strong>{{ $application->category_name }}</strong></div></div><div class="preview-item"><i class="fa-solid fa-location-dot" aria-hidden="true"></i><div><span>Location</span><strong>{{ $application->job_location ?: 'Location not specified' }}</strong></div></div><div class="preview-item"><i class="fa-solid fa-building" aria-hidden="true"></i><div><span>Company</span><strong>{{ $application->company_name }}</strong></div></div></div></section>
            <section class="edit-sidebar-card interview-tips-card"><div class="tips-heading"><div class="tips-icon"><i class="fa-solid fa-lightbulb" aria-hidden="true"></i></div><h3>Interview Tips</h3></div><ul><li>Confirm the date and time before scheduling.</li><li>Include a meeting link for online interviews.</li><li>Provide the exact location for in-person interviews.</li><li>Add useful preparation instructions in the remarks.</li></ul></section>
        </aside>
    </section>
</div>
@endsection

@section('js')
<script src="{{ asset('assets/js/employer/interviews.js') }}"></script>
@endsection
