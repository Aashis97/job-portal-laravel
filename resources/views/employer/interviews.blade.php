@extends('layouts.dashboard')

@section('title', 'Interview Management')
@section('role', 'Employer')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/employer/interviews.css') }}">
@endsection

@section('content')
<div class="employer-interviews-page">
    <section class="page-header">
        <div class="page-header-content">
            <div class="page-header-icon"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i></div>
            <div><span class="page-eyebrow">Employer Panel</span><h1>Interview Management</h1><p>Schedule, manage and track candidate interviews across your job openings.</p></div>
        </div>
        <div class="page-header-actions"><a href="{{ route('employer.applications') }}" class="btn btn-secondary"><i class="fa-solid fa-users" aria-hidden="true"></i>Applicants</a></div>
    </section>

    @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger" role="alert">{{ session('error') }}</div>@endif

    <section class="interview-statistics">
        <div class="stat-card"><div class="stat-icon stat-icon-blue"><i class="fa-solid fa-calendar-days" aria-hidden="true"></i></div><div class="stat-content"><span class="stat-label">Total Interviews</span><strong class="stat-value">{{ (int) ($stats->total_interviews ?? 0) }}</strong></div></div>
        <div class="stat-card"><div class="stat-icon stat-icon-green"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i></div><div class="stat-content"><span class="stat-label">Scheduled</span><strong class="stat-value">{{ (int) ($stats->scheduled_interviews ?? 0) }}</strong></div></div>
        <div class="stat-card"><div class="stat-icon stat-icon-purple"><i class="fa-solid fa-clock" aria-hidden="true"></i></div><div class="stat-content"><span class="stat-label">Upcoming</span><strong class="stat-value">{{ $upcomingCount }}</strong></div></div>
        <div class="stat-card"><div class="stat-icon stat-icon-teal"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></div><div class="stat-content"><span class="stat-label">Completed</span><strong class="stat-value">{{ (int) ($stats->completed_interviews ?? 0) }}</strong></div></div>
        <div class="stat-card"><div class="stat-icon stat-icon-red"><i class="fa-solid fa-calendar-xmark" aria-hidden="true"></i></div><div class="stat-content"><span class="stat-label">Cancelled</span><strong class="stat-value">{{ (int) ($stats->cancelled_interviews ?? 0) }}</strong></div></div>
    </section>

    <section class="filter-card">
        <form action="{{ route('employer.interviews') }}" method="GET" class="interview-filter-form">
            <div class="filter-field search-field"><label for="search">Search</label><div class="input-with-icon"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input type="search" id="search" name="search" value="{{ $search }}" placeholder="Candidate, job, email or interviewer..." maxlength="100"></div></div>
            <div class="filter-field"><label for="status">Interview Status</label><select id="status" name="status"><option value="">All Statuses</option>@foreach(['Scheduled', 'Completed', 'Cancelled', 'Rescheduled'] as $option)<option value="{{ $option }}" @selected($status === $option)>{{ $option }}</option>@endforeach</select></div>
            <div class="filter-field"><label for="type">Interview Type</label><select id="type" name="type"><option value="">All Types</option>@foreach(['Online', 'Onsite', 'Phone'] as $option)<option value="{{ $option }}" @selected($type === $option)>{{ $option === 'Onsite' ? 'In-person' : $option }}</option>@endforeach</select></div>
            <div class="filter-actions"><button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter" aria-hidden="true"></i>Filter</button><a href="{{ route('employer.interviews') }}" class="btn btn-light"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i>Reset</a></div>
        </form>
    </section>

    <section class="interviews-section">
        <div class="section-heading"><div><span class="section-eyebrow">Interview Schedule</span><h2>{{ $interviews->count() }} Interview{{ $interviews->count() === 1 ? '' : 's' }}</h2></div><div class="section-heading-meta">@if($status)<span class="filter-badge">Status: {{ $status }}</span>@endif @if($type)<span class="filter-badge">Type: {{ $type }}</span>@endif</div></div>

        @if($interviews->isNotEmpty())
            <div class="interview-list">
                @foreach($interviews as $interview)
                    @php
                        $candidateName = trim((string) $interview->full_name) ?: 'Unknown Candidate';
                        $nameParts = preg_split('/\s+/', $candidateName, -1, PREG_SPLIT_NO_EMPTY);
                        $initials = mb_strtoupper(mb_substr($nameParts[0] ?? 'C', 0, 1));
                        if (count($nameParts) > 1) {
                            $initials .= mb_strtoupper(mb_substr($nameParts[count($nameParts) - 1], 0, 1));
                        }
                        $statusClass = strtolower(str_replace([' ', '-'], '_', $interview->interview_status));
                        $typeClass = strtolower(str_replace([' ', '-'], '_', $interview->interview_type));
                        $isUpcoming = in_array($interview->interview_status, ['Scheduled', 'Rescheduled'], true) && $interview->interview_date >= $today;
                    @endphp
                    <article class="interview-card {{ $isUpcoming ? 'is-upcoming' : '' }}">
                        <div class="interview-candidate"><div class="candidate-avatar">{{ $initials }}</div><div class="candidate-info"><h3>{{ $candidateName }}</h3><a href="mailto:{{ $interview->email }}" class="candidate-email"><i class="fa-solid fa-envelope" aria-hidden="true"></i>{{ $interview->email }}</a><span class="candidate-availability"><i class="fa-solid fa-user-clock" aria-hidden="true"></i>{{ $interview->availability ?: 'Availability not specified' }}</span></div></div>
                        <div class="interview-job"><span class="info-label">Position</span><a href="{{ route('employer.applicant-details', ['application_id' => $interview->application_id]) }}" class="job-title">{{ $interview->job_title }}</a><span class="job-category"><i class="fa-solid fa-layer-group" aria-hidden="true"></i>{{ $interview->category_name }}</span></div>
                        <div class="interview-schedule"><span class="info-label">Interview Schedule</span><div class="schedule-date"><i class="fa-regular fa-calendar" aria-hidden="true"></i><strong>{{ \Illuminate\Support\Carbon::parse($interview->interview_date)->format('M d, Y') }}</strong></div><div class="schedule-time"><i class="fa-regular fa-clock" aria-hidden="true"></i>{{ \Illuminate\Support\Carbon::parse($interview->interview_time)->format('h:i A') }}</div>@if($isUpcoming)<span class="upcoming-label"><i class="fa-solid fa-bolt" aria-hidden="true"></i>Upcoming</span>@endif</div>
                        <div class="interview-meta"><span class="info-label">Interview</span><span class="interview-type-badge type-{{ $typeClass }}"><i class="fa-solid {{ $interview->interview_type === 'Online' ? 'fa-video' : ($interview->interview_type === 'Onsite' ? 'fa-location-dot' : 'fa-phone') }}" aria-hidden="true"></i>{{ $interview->interview_type }}</span><span class="interview-status status-{{ $statusClass }}">{{ $interview->interview_status }}</span></div>
                        <div class="interview-actions">
                            <a href="{{ route('employer.interview-details', ['interview_id' => $interview->interview_id]) }}" class="action-btn action-view" title="View Interview"><i class="fa-solid fa-eye" aria-hidden="true"></i><span>View</span></a>
                            @if(!in_array($interview->interview_status, ['Completed', 'Cancelled'], true))
                                <a href="{{ route('employer.interviews.edit', ['interview_id' => $interview->interview_id]) }}" class="action-btn action-edit" title="Edit Interview"><i class="fa-solid fa-pen" aria-hidden="true"></i><span>Edit</span></a>
                            @endif
                            @if(in_array($interview->interview_status, ['Scheduled', 'Rescheduled'], true))
                                <a href="{{ route('employer.interviews.cancel', ['interview_id' => $interview->interview_id]) }}" class="action-btn action-cancel" title="Cancel Interview"><i class="fa-solid fa-ban" aria-hidden="true"></i><span>Cancel</span></a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="empty-state"><div class="empty-state-icon"><i class="fa-regular fa-calendar-xmark" aria-hidden="true"></i></div><h3>No Interviews Found</h3>
                @if($search !== '' || $status !== '' || $type !== '')
                    <p>No interviews match your current filters. Try changing the search or filter options.</p><a href="{{ route('employer.interviews') }}" class="btn btn-primary"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i>Clear Filters</a>
                @else
                    <p>You have not scheduled any interviews yet. Interviews scheduled from applicant management will appear here.</p><a href="{{ route('employer.applications') }}" class="btn btn-primary"><i class="fa-solid fa-users" aria-hidden="true"></i>View Applicants</a>
                @endif
            </div>
        @endif
    </section>

    <section class="interview-tips"><div class="tips-icon"><i class="fa-solid fa-lightbulb" aria-hidden="true"></i></div><div class="tips-content"><h3>Interview Management Tips</h3><div class="tips-grid"><div class="tip-item"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i><span>Keep interview dates and times accurate to avoid candidate confusion.</span></div><div class="tip-item"><i class="fa-solid fa-video" aria-hidden="true"></i><span>For online interviews, always provide a valid meeting link.</span></div><div class="tip-item"><i class="fa-solid fa-message" aria-hidden="true"></i><span>Use remarks to record important interview instructions or notes.</span></div></div></div></section>
</div>
@endsection
