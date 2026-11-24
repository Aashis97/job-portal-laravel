@extends('layouts.dashboard')

@section('title', 'Interview Details')
@section('role', 'Employer')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/employer/interviews.css') }}">
@endsection

@section('content')
@php
    $candidateName = trim((string) $interview->full_name) ?: 'Unknown Candidate';
    $nameParts = preg_split('/\s+/', $candidateName, -1, PREG_SPLIT_NO_EMPTY);
    $initials = mb_strtoupper(mb_substr($nameParts[0] ?? 'C', 0, 1));
    if (count($nameParts) > 1) {
        $initials .= mb_strtoupper(mb_substr($nameParts[count($nameParts) - 1], 0, 1));
    }
    $status = $interview->interview_status ?: 'Scheduled';
    $statusConfig = [
        'Scheduled' => ['icon' => 'fa-calendar-check', 'class' => 'status-scheduled'],
        'Completed' => ['icon' => 'fa-circle-check', 'class' => 'status-completed'],
        'Cancelled' => ['icon' => 'fa-circle-xmark', 'class' => 'status-cancelled'],
        'Rescheduled' => ['icon' => 'fa-calendar-days', 'class' => 'status-rescheduled'],
    ];
    $statusInfo = $statusConfig[$status] ?? ['icon' => 'fa-circle-question', 'class' => 'status-default'];
    $date = \Illuminate\Support\Carbon::parse($interview->interview_date);
    $time = \Illuminate\Support\Carbon::parse($interview->interview_time);
    $isUpcoming = in_array($status, ['Scheduled', 'Rescheduled'], true) && $date->toDateString() >= now()->toDateString();
    $isPast = $date->toDateString() < now()->toDateString();
    $typeIcon = ['Online' => 'fa-video', 'Onsite' => 'fa-location-dot', 'Phone' => 'fa-phone'][$interview->interview_type] ?? 'fa-calendar';
    $hasResume = is_string($interview->resume_file) && $interview->resume_file !== '';
@endphp
<div class="interview-details-page">
    <section class="page-header">
        <div class="page-header-content"><a href="{{ route('employer.interviews') }}" class="back-link"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Back to Interviews</a><div class="page-title-row"><div class="page-header-icon"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i></div><div><span class="page-eyebrow">Interview Management</span><h1>Interview Details</h1><p>Review and manage the interview for {{ $candidateName }}.</p></div></div></div>
        <div class="page-header-actions">@if(!in_array($status, ['Completed', 'Cancelled'], true))<a href="{{ route('employer.interviews.edit', ['interview_id' => $interview->interview_id]) }}" class="btn btn-primary"><i class="fa-solid fa-pen" aria-hidden="true"></i>Edit Interview</a>@endif</div>
    </section>

    @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger" role="alert">{{ session('error') }}</div>@endif

    <section class="interview-status-banner {{ $statusInfo['class'] }}"><div class="status-banner-icon"><i class="fa-solid {{ $statusInfo['icon'] }}" aria-hidden="true"></i></div><div class="status-banner-content"><span>Interview Status</span><strong>{{ $status }}</strong></div><div class="status-banner-meta">@if($isUpcoming)<span class="status-highlight"><i class="fa-solid fa-bolt" aria-hidden="true"></i>Upcoming Interview</span>@elseif($isPast && $status !== 'Completed')<span class="status-highlight warning"><i class="fa-solid fa-clock" aria-hidden="true"></i>Interview Date Passed</span>@endif</div></section>

    <div class="interview-details-grid">
        <div class="details-main-column">
            <section class="details-card candidate-card">
                <div class="card-heading"><div><span class="card-eyebrow">Candidate</span><h2>Candidate Information</h2></div><i class="fa-solid fa-user-tie" aria-hidden="true"></i></div>
                <div class="candidate-profile"><div class="candidate-avatar-large">{{ $initials }}</div><div class="candidate-profile-info"><h3>{{ $candidateName }}</h3><a href="mailto:{{ $interview->email }}" class="profile-email"><i class="fa-solid fa-envelope" aria-hidden="true"></i>{{ $interview->email }}</a>@if($interview->availability)<span class="profile-detail"><i class="fa-solid fa-user-clock" aria-hidden="true"></i>{{ $interview->availability }}</span>@endif</div></div>
                <div class="candidate-actions"><a href="{{ route('employer.applicant-details', ['application_id' => $interview->application_id]) }}" class="btn btn-secondary"><i class="fa-solid fa-user" aria-hidden="true"></i>Applicant Profile</a>@if($hasResume)<a href="{{ route('employer.applicant-resume', ['application_id' => $interview->application_id]) }}" class="btn btn-light" target="_blank" rel="noopener"><i class="fa-solid fa-file-pdf" aria-hidden="true"></i>View Resume</a>@endif</div>
            </section>

            <section class="details-card schedule-card">
                <div class="card-heading"><div><span class="card-eyebrow">Schedule</span><h2>Interview Schedule</h2></div><i class="fa-regular fa-calendar-days" aria-hidden="true"></i></div>
                <div class="schedule-details-grid">
                    <div class="schedule-detail-item"><div class="detail-icon"><i class="fa-regular fa-calendar" aria-hidden="true"></i></div><div><span>Date</span><strong>{{ $date->format('l, F d, Y') }}</strong></div></div>
                    <div class="schedule-detail-item"><div class="detail-icon"><i class="fa-regular fa-clock" aria-hidden="true"></i></div><div><span>Time</span><strong>{{ $time->format('h:i A') }}</strong></div></div>
                    <div class="schedule-detail-item"><div class="detail-icon"><i class="fa-solid {{ $typeIcon }}" aria-hidden="true"></i></div><div><span>Interview Type</span><strong>{{ $interview->interview_type }}</strong></div></div>
                    <div class="schedule-detail-item"><div class="detail-icon"><i class="fa-solid fa-user-tie" aria-hidden="true"></i></div><div><span>Interviewer</span><strong>{{ $interview->interviewer_name ?: 'Not specified' }}</strong></div></div>
                    @if($interview->meeting_link)<div class="schedule-detail-item"><div class="detail-icon"><i class="fa-solid fa-link" aria-hidden="true"></i></div><div><span>Meeting Link</span><strong><a href="{{ $interview->meeting_link }}" target="_blank" rel="noopener noreferrer">{{ $interview->meeting_link }}</a></strong></div></div>@endif
                    @if($interview->location)<div class="schedule-detail-item"><div class="detail-icon"><i class="fa-solid fa-location-dot" aria-hidden="true"></i></div><div><span>Location</span><strong>{{ $interview->location }}</strong></div></div>@endif
                </div>
                @if($interview->remarks)<div class="interview-remarks"><h3><i class="fa-solid fa-message" aria-hidden="true"></i>Interview Remarks</h3><p>{{ $interview->remarks }}</p></div>@endif
            </section>

            <section class="details-card">
                <div class="card-heading"><div><span class="card-eyebrow">Applied Position</span><h2>{{ $interview->job_title }}</h2></div><i class="fa-solid fa-briefcase" aria-hidden="true"></i></div>
                <div class="job-meta"><span><i class="fa-solid fa-layer-group" aria-hidden="true"></i>{{ $interview->category_name }}</span><span><i class="fa-solid fa-location-dot" aria-hidden="true"></i>{{ $interview->job_location ?: 'Location not specified' }}</span><span><i class="fa-solid fa-building" aria-hidden="true"></i>{{ $interview->company_name }}</span><span><i class="fa-solid fa-clock" aria-hidden="true"></i>{{ $interview->employment_type }}</span></div>
            </section>
        </div>

        <aside class="details-sidebar">
            <section class="sidebar-card ai-match-card"><div class="sidebar-card-heading"><div><span>AI Candidate Analysis</span><h3>Skill Match</h3></div><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i></div><div class="match-score"><div class="match-score-circle"><strong>{{ number_format($matchScore, 0) }}%</strong></div><div><span>Skill Match Score</span><strong>{{ $matchedSkillCount }} / {{ $jobSkills->count() }} skills matched</strong></div></div>@if($interview->ai_rank !== null)<div class="ai-rank-row"><div class="ai-rank-icon"><i class="fa-solid fa-ranking-star" aria-hidden="true"></i></div><div><span>AI Candidate Rank</span><strong>#{{ (int) $interview->ai_rank }}</strong></div></div>@endif</section>

            <section class="sidebar-card"><div class="sidebar-card-heading"><div><span>Application</span><h3>Application Information</h3></div><i class="fa-solid fa-file-lines" aria-hidden="true"></i></div><div class="application-info-list"><div><span>Application ID</span><strong>#{{ $interview->application_id }}</strong></div><div><span>Job ID</span><strong>#{{ $interview->job_id }}</strong></div><div><span>Application Status</span><strong>{{ $interview->application_status }}</strong></div><div><span>Profile Completed</span><strong><span class="small-status {{ (int) $interview->profile_completed === 1 ? 'success' : 'warning' }}"><i class="fa-solid {{ (int) $interview->profile_completed === 1 ? 'fa-check' : 'fa-minus' }}" aria-hidden="true"></i>{{ (int) $interview->profile_completed === 1 ? 'Yes' : 'No' }}</span></strong></div></div></section>

            <section class="sidebar-card action-card"><div class="sidebar-card-heading"><div><span>Management</span><h3>Interview Actions</h3></div><i class="fa-solid fa-sliders" aria-hidden="true"></i></div><div class="sidebar-actions">
                @if(!in_array($status, ['Completed', 'Cancelled'], true))
                    <a href="{{ route('employer.interviews.edit', ['interview_id' => $interview->interview_id]) }}" class="sidebar-action edit-action"><i class="fa-solid fa-pen" aria-hidden="true"></i><div><strong>Edit Interview</strong><span>Change schedule or details</span></div></a>
                    <a href="{{ route('employer.interviews.cancel', ['interview_id' => $interview->interview_id]) }}" class="sidebar-action cancel-action"><i class="fa-solid fa-ban" aria-hidden="true"></i><div><strong>Cancel Interview</strong><span>Cancel this interview</span></div></a>
                @endif
                @if(in_array($status, ['Scheduled', 'Rescheduled'], true))
                    <form action="{{ route('employer.interviews.status') }}" method="POST" class="status-form">@csrf<input type="hidden" name="interview_id" value="{{ $interview->interview_id }}"><input type="hidden" name="status" value="Completed"><button type="submit" class="sidebar-action complete-action"><i class="fa-solid fa-circle-check" aria-hidden="true"></i><div><strong>Mark Completed</strong><span>Finish this interview</span></div></button></form>
                @endif
            </div></section>
        </aside>
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('assets/js/employer/interview_details.js') }}"></script>
@endsection
