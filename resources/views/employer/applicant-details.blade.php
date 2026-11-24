@extends('layouts.dashboard')

@section('title', 'Applicant Details')
@section('role', 'Employer')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/employer/applicant_details.css') }}">
@endsection

@section('content')
@php
    $statusClasses = [
        'Applied' => 'status-applied',
        'Under Review' => 'status-review',
        'Shortlisted' => 'status-shortlisted',
        'Interview Scheduled' => 'status-interview',
        'Selected' => 'status-selected',
        'Rejected' => 'status-rejected',
        'Withdrawn' => 'status-withdrawn',
    ];
    $statusIcons = [
        'Applied' => 'fa-paper-plane',
        'Under Review' => 'fa-magnifying-glass',
        'Shortlisted' => 'fa-star',
        'Interview Scheduled' => 'fa-calendar-check',
        'Selected' => 'fa-circle-check',
        'Rejected' => 'fa-circle-xmark',
        'Withdrawn' => 'fa-arrow-right-from-bracket',
    ];
    $status = $application->application_status ?: 'Applied';
    $statusClass = $statusClasses[$status] ?? 'status-applied';
    $matchScore = is_numeric($application->skill_match_score) ? max(0, min(100, (float) $application->skill_match_score)) : null;
    $aiRank = is_numeric($application->ai_rank) ? (int) $application->ai_rank : null;
    $candidateName = trim((string) $application->full_name) ?: 'Unnamed Candidate';
    $nameParts = preg_split('/\s+/', $candidateName, -1, PREG_SPLIT_NO_EMPTY);
    $initials = mb_strtoupper(mb_substr($nameParts[0] ?? 'C', 0, 1));
    if (count($nameParts) > 1) {
        $initials .= mb_strtoupper(mb_substr($nameParts[count($nameParts) - 1], 0, 1));
    }
    $progressStatuses = ['Applied', 'Under Review', 'Shortlisted', 'Interview Scheduled', 'Selected'];
    $currentProgressIndex = array_search($status, $progressStatuses, true);
    $resumeAvailable = is_string($application->resume_file) && trim($application->resume_file) !== '';
    $companyLogo = $application->logo && $application->logo !== 'company.png'
        ? route('company.logo', ['filename' => basename($application->logo)])
        : asset('assets/images/company.png');
@endphp
<div class="applicant-details-page">
    <a href="{{ route('employer.applicants', ['job_id' => $application->job_id]) }}" class="back-link"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Back to Applicants</a>

    @if(session('success'))
        <div class="status-notice status-notice-success" role="status"><i class="fa-solid fa-circle-check" aria-hidden="true"></i><div><strong>{{ session('success') }}</strong></div></div>
    @endif
    @if(session('error'))
        <div class="status-notice status-notice-danger" role="alert"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><div><strong>{{ session('error') }}</strong></div></div>
    @endif
    @if($errors->any())
        <div class="status-notice status-notice-danger" role="alert"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><div><strong>{{ implode(' ', $errors->all()) }}</strong></div></div>
    @endif

    <section class="candidate-header-card">
        <div class="candidate-header-left">
            <div class="candidate-avatar-large">
                @if($application->profile_image)
                    <img src="{{ route('employer.applicant-image', ['applicationId' => $application->application_id]) }}" alt="{{ $candidateName }}">
                @else
                    {{ $initials }}
                @endif
            </div>
            <div class="candidate-header-info">
                <div class="candidate-name-row"><h1>{{ $candidateName }}</h1><span class="application-status {{ $statusClass }}"><span class="status-dot"></span>{{ $status }}</span></div>
                <a href="mailto:{{ $application->email }}" class="candidate-email"><i class="fa-solid fa-envelope" aria-hidden="true"></i>{{ $application->email }}</a>
                <div class="candidate-meta">
                    @if($application->availability)<span><i class="fa-solid fa-clock" aria-hidden="true"></i>{{ $application->availability }}</span>@endif
                    <span><i class="fa-solid fa-user-check" aria-hidden="true"></i>Profile: {{ (int) $application->profile_completed === 1 ? 'Completed' : 'Incomplete' }}</span>
                </div>
            </div>
        </div>
        <div class="candidate-header-actions">
            @if($resumeAvailable)
                <a href="{{ route('employer.applicant-resume', ['application_id' => $application->application_id]) }}" class="btn btn-primary" target="_blank" rel="noopener"><i class="fa-solid fa-file-pdf" aria-hidden="true"></i>View Resume</a>
            @else
                <span class="resume-unavailable"><i class="fa-solid fa-file-circle-xmark" aria-hidden="true"></i>Resume Not Available</span>
            @endif
            @if(!in_array($status, ['Rejected', 'Withdrawn'], true))
                <a href="{{ route('employer.interviews.schedule', ['application_id' => $application->application_id]) }}" class="btn btn-secondary"><i class="fa-solid fa-calendar-plus" aria-hidden="true"></i>Schedule Interview</a>
            @endif
        </div>
    </section>

    <div class="applicant-details-grid">
        <div class="details-main-column">
            <section class="details-card">
                <div class="card-header"><div><span class="card-kicker">APPLICATION</span><h2>Applied Position</h2></div><div class="card-header-icon"><i class="fa-solid fa-briefcase" aria-hidden="true"></i></div></div>
                <div class="job-detail-box">
                    <div class="job-company-logo"><img src="{{ $companyLogo }}" alt="{{ $application->company_name }}" onerror="this.src='{{ asset('assets/images/company.png') }}'"></div>
                    <div class="job-detail-content"><span class="job-category">{{ $application->category_name }}</span><h3>{{ $application->job_title }}</h3><p>{{ $application->company_name }}</p>
                        <div class="job-meta"><span><i class="fa-solid fa-location-dot" aria-hidden="true"></i>{{ $application->location ?: 'Location not specified' }}</span><span><i class="fa-solid fa-building" aria-hidden="true"></i>{{ $application->workplace }}</span><span><i class="fa-solid fa-clock" aria-hidden="true"></i>{{ $application->employment_type }}</span><span><i class="fa-solid fa-layer-group" aria-hidden="true"></i>{{ $application->experience_level }}</span></div>
                    </div>
                </div>
            </section>

            <section class="details-card">
                <div class="card-header"><div><span class="card-kicker">SKILLS</span><h2>Candidate Skills</h2></div><div class="card-header-icon"><i class="fa-solid fa-code" aria-hidden="true"></i></div></div>
                @if($candidateSkills->isNotEmpty())
                    <div class="large-skill-list">@foreach($candidateSkills as $skill)<span class="large-skill-tag {{ in_array($skill->skill_name, $matchedSkills, true) ? 'matched' : '' }}"><i class="fa-solid {{ in_array($skill->skill_name, $matchedSkills, true) ? 'fa-check' : 'fa-circle' }}" aria-hidden="true"></i>{{ $skill->skill_name }}</span>@endforeach</div>
                @else
                    <div class="card-empty"><i class="fa-solid fa-code" aria-hidden="true"></i><p>This candidate has not added any skills yet.</p></div>
                @endif
            </section>

            <section class="details-card">
                <div class="card-header"><div><span class="card-kicker">JOB MATCH</span><h2>Required Skill Comparison</h2></div><div class="card-header-icon ai-icon"><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i></div></div>
                @if($jobSkills->isNotEmpty())
                    <div class="comparison-summary"><div class="comparison-number"><strong>{{ count($matchedSkills) }}</strong><span>Matched Skills</span></div><div class="comparison-divider"></div><div class="comparison-number"><strong>{{ count($missingSkills) }}</strong><span>Missing Skills</span></div><div class="comparison-divider"></div><div class="comparison-number"><strong>{{ $jobSkills->count() }}</strong><span>Required Skills</span></div></div>
                    <div class="comparison-groups">
                        <div class="comparison-group"><h3 class="matched-heading"><i class="fa-solid fa-circle-check" aria-hidden="true"></i>Matched Skills</h3>@if($matchedSkills)<div class="comparison-tags">@foreach($matchedSkills as $skill)<span class="comparison-tag matched"><i class="fa-solid fa-check" aria-hidden="true"></i>{{ $skill }}</span>@endforeach</div>@else<p class="comparison-empty">No required skills matched.</p>@endif</div>
                        <div class="comparison-group"><h3 class="missing-heading"><i class="fa-solid fa-circle-xmark" aria-hidden="true"></i>Missing Skills</h3>@if($missingSkills)<div class="comparison-tags">@foreach($missingSkills as $skill)<span class="comparison-tag missing"><i class="fa-solid fa-xmark" aria-hidden="true"></i>{{ $skill }}</span>@endforeach</div>@else<p class="comparison-empty">Candidate matches all required skills.</p>@endif</div>
                    </div>
                @else
                    <div class="card-empty"><i class="fa-solid fa-list-check" aria-hidden="true"></i><p>No specific skills were defined for this job.</p></div>
                @endif
            </section>
        </div>

        <aside class="details-sidebar">
            <section class="score-card">
                <div class="score-card-header"><span>AI Skill Match</span><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i></div>
                @if($matchScore !== null)
                    <div class="large-score-circle" style="--score: {{ $matchScore }};"><div class="score-circle-inner"><strong>{{ number_format($matchScore, 0) }}%</strong><span>Match</span></div></div>
                    <div class="score-rating">
                        @if($matchScore >= 80)<strong>Excellent Match</strong><p>This candidate has a strong skill alignment with the job.</p>
                        @elseif($matchScore >= 60)<strong>Good Match</strong><p>This candidate has a good level of skill compatibility.</p>
                        @else<strong>Potential Match</strong><p>Review the candidate's complete profile before making a decision.</p>@endif
                    </div>
                @else
                    <div class="score-not-calculated"><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i><strong>Not Calculated</strong><p>AI skill matching has not been calculated for this candidate yet.</p></div>
                @endif
                @if(!in_array($status, ['Rejected', 'Withdrawn'], true))
                    <a href="{{ route('employer.candidate-score', ['application_id' => $application->application_id]) }}" class="btn btn-secondary">Calculate AI Score</a>
                @endif
                <a href="{{ route('employer.candidate-insights', ['application_id' => $application->application_id]) }}" class="btn btn-secondary">AI Insights</a>
            </section>

            <section class="ranking-card"><div class="ranking-icon"><i class="fa-solid fa-ranking-star" aria-hidden="true"></i></div><div class="ranking-content"><span>AI Candidate Rank</span><strong>{{ $aiRank !== null ? '#' . $aiRank : 'Pending' }}</strong><small>{{ $aiRank !== null ? 'Based on current ranking data' : 'AI ranking not calculated yet' }}</small></div></section>

            <section class="details-card compact-card">
                <div class="card-header"><div><span class="card-kicker">APPLICATION</span><h2>Application Information</h2></div></div>
                <div class="info-list"><div class="info-row"><span>Application ID</span><strong>#{{ $application->application_id }}</strong></div><div class="info-row"><span>Application Status</span><span class="application-status {{ $statusClass }}"><span class="status-dot"></span>{{ $status }}</span></div><div class="info-row"><span>Job Status</span><strong>{{ $application->job_status }}</strong></div><div class="info-row"><span>Availability</span><strong>{{ $application->availability ?: 'Not specified' }}</strong></div></div>
            </section>

            <section class="applicant-status-card">
                <div class="status-card-header">
                    <div class="status-card-title"><div class="status-card-icon"><i class="fa-solid fa-list-check" aria-hidden="true"></i></div><div><h2>Application Status</h2><p>Update candidate progress.</p></div></div>
                    <span class="application-status-badge {{ $statusClass }}"><i class="fa-solid {{ $statusIcons[$status] ?? 'fa-paper-plane' }}" aria-hidden="true"></i>{{ $status }}</span>
                </div>
                <div class="status-card-body">
                    <form action="{{ route('employer.applicant-status.update') }}" method="POST" class="application-status-form">
                        @csrf
                        <input type="hidden" name="application_id" value="{{ $application->application_id }}">
                        <div class="status-form-group"><label for="applicationStatus">Change Status</label><div class="status-select-wrapper"><i class="fa-solid fa-filter status-select-icon" aria-hidden="true"></i><select name="status" id="applicationStatus" class="status-select" required>@foreach($statuses as $statusOption)<option value="{{ $statusOption }}" @selected($status === $statusOption)>{{ $statusOption }}</option>@endforeach</select><i class="fa-solid fa-chevron-down status-select-arrow" aria-hidden="true"></i></div></div>
                        <button type="submit" class="btn-update-status" id="updateStatusButton"><i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i><span>Update</span></button>
                    </form>

                    <div class="status-progress">
                        @foreach($progressStatuses as $index => $progressStatus)
                            @php $progressClass = ($currentProgressIndex !== false && $currentProgressIndex >= $index) ? 'completed' : ''; @endphp
                            @if($status === $progressStatus)@php $progressClass .= ' current'; @endphp@endif
                            <div class="progress-step {{ trim($progressClass) }}"><div class="progress-icon"><i class="fa-solid {{ $statusIcons[$progressStatus] }}" aria-hidden="true"></i></div><span>{{ $progressStatus }}</span></div>
                            @if($index < count($progressStatuses) - 1)<div class="progress-line {{ $currentProgressIndex !== false && $currentProgressIndex > $index ? 'completed' : '' }}"></div>@endif
                        @endforeach
                    </div>

                    @if($status === 'Rejected')
                        <div class="status-notice status-notice-danger"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><div><strong>Application Rejected</strong><p>This candidate's application has been marked as rejected.</p></div></div>
                    @elseif($status === 'Withdrawn')
                        <div class="status-notice status-notice-warning"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i><div><strong>Application Withdrawn</strong><p>The candidate has withdrawn from this application.</p></div></div>
                    @elseif($status === 'Selected')
                        <div class="status-notice status-notice-success"><i class="fa-solid fa-circle-check" aria-hidden="true"></i><div><strong>Candidate Selected</strong><p>This candidate has been selected for the position.</p></div></div>
                    @endif
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection
