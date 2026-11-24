@extends('layouts.dashboard')
@section('title', 'Recruitment Analytics')
@section('role', 'Employer')
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/employer/analytics.css') }}">@endsection
@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="{{ asset('assets/js/employer/analytics.js') }}"></script>
@endsection
@section('content')
<div class="analytics-page">
    <header class="analytics-hero">
        <div class="analytics-hero-copy">
            <span class="analytics-eyebrow"><i class="fa-solid fa-chart-line" aria-hidden="true"></i> Employer insights</span>
            <h1>Recruitment analytics</h1>
            <p>Understand your hiring pipeline, job performance, and interview outcomes in one place.</p>
            <span class="analytics-company"><i class="fa-solid fa-building" aria-hidden="true"></i> {{ $companyName }}</span>
        </div>
        <a class="analytics-button analytics-button-light" href="{{ route('employer.jobs') }}"><i class="fa-solid fa-briefcase" aria-hidden="true"></i> Manage jobs</a>
    </header>

    <script type="application/json" id="employerAnalyticsData">@json($chartData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>

    <section class="analytics-kpis" aria-label="Hiring overview">
        <article class="analytics-kpi analytics-kpi-blue"><span class="analytics-kpi-icon"><i class="fa-solid fa-briefcase" aria-hidden="true"></i></span><div><span>Total jobs</span><strong>{{ number_format((int) $jobs->total_jobs) }}</strong><small>{{ number_format((int) $jobs->active_jobs) }} open now</small></div></article>
        <article class="analytics-kpi analytics-kpi-violet"><span class="analytics-kpi-icon"><i class="fa-solid fa-file-lines" aria-hidden="true"></i></span><div><span>Applications</span><strong>{{ number_format((int) $applications->total_applications) }}</strong><small>{{ number_format((int) $applications->total_candidates) }} unique candidates</small></div></article>
        <article class="analytics-kpi analytics-kpi-amber"><span class="analytics-kpi-icon"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i></span><div><span>Interviews</span><strong>{{ number_format((int) $interviews->total_interviews) }}</strong><small>{{ number_format((int) $interviews->scheduled) }} scheduled</small></div></article>
        <article class="analytics-kpi analytics-kpi-green"><span class="analytics-kpi-icon"><i class="fa-solid fa-user-check" aria-hidden="true"></i></span><div><span>Selected</span><strong>{{ number_format((int) $applications->selected) }}</strong><small>{{ number_format($hiringRate, 1) }}% selection rate</small></div></article>
    </section>

    <section class="analytics-summary" aria-label="Recruitment indicators">
        <article class="analytics-summary-item"><span class="analytics-summary-icon"><i class="fa-solid fa-bullseye" aria-hidden="true"></i></span><div><span>Average skill match</span><strong>{{ number_format((float) $applications->average_skill_match, 1) }}%</strong></div></article>
        <article class="analytics-summary-item"><span class="analytics-summary-icon"><i class="fa-solid fa-user-group" aria-hidden="true"></i></span><div><span>Shortlisted</span><strong>{{ number_format((int) $applications->shortlisted) }}</strong></div></article>
        <article class="analytics-summary-item"><span class="analytics-summary-icon"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i></span><div><span>Interview completion</span><strong>{{ number_format($interviewCompletionRate, 1) }}%</strong></div></article>
        <article class="analytics-summary-item"><span class="analytics-summary-icon"><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i></span><div><span>Under review</span><strong>{{ number_format((int) $applications->under_review) }}</strong></div></article>
    </section>

    <section class="analytics-chart-grid" aria-label="Recruitment charts">
        <article class="analytics-panel"><div class="analytics-panel-heading"><div><span class="analytics-section-kicker">Candidate pipeline</span><h2>Application status</h2><p>How applications are progressing through review.</p></div><span class="analytics-panel-mark"><i class="fa-solid fa-chart-pie" aria-hidden="true"></i></span></div><div class="analytics-chart-wrap"><canvas id="applicationStatusChart" role="img" aria-label="Applications by status"></canvas></div></article>
        <article class="analytics-panel"><div class="analytics-panel-heading"><div><span class="analytics-section-kicker">Interview activity</span><h2>Interview outcomes</h2><p>Scheduled, completed, and rescheduled interviews.</p></div><span class="analytics-panel-mark"><i class="fa-solid fa-calendar-days" aria-hidden="true"></i></span></div><div class="analytics-chart-wrap"><canvas id="interviewStatusChart" role="img" aria-label="Interviews by status"></canvas></div></article>
    </section>

    <section class="analytics-panel analytics-performance">
        <div class="analytics-panel-heading"><div><span class="analytics-section-kicker">Role performance</span><h2>Applications by job</h2><p>Compare application volume with successful selections.</p></div><span class="analytics-panel-mark"><i class="fa-solid fa-chart-column" aria-hidden="true"></i></span></div>
        @if($jobPerformance->isNotEmpty())
            <div class="analytics-chart-wrap analytics-chart-wide"><canvas id="jobPerformanceChart" role="img" aria-label="Applications and selections by job"></canvas></div>
        @else
            <div class="analytics-empty"><span><i class="fa-solid fa-chart-column" aria-hidden="true"></i></span><h3>No job performance data yet</h3><p>Post a job and start receiving applications to see how your roles are performing.</p><a class="analytics-button analytics-button-primary" href="{{ route('employer.jobs.create') }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Post a job</a></div>
        @endif
    </section>

    <section class="analytics-lower-grid">
        <article class="analytics-panel">
            <div class="analytics-panel-heading"><div><span class="analytics-section-kicker">Your job descriptions</span><h2>Most requested skills</h2><p>Skills that appear most often across your open and past jobs.</p></div><span class="analytics-panel-mark"><i class="fa-solid fa-lightbulb" aria-hidden="true"></i></span></div>
            @if($topSkills->isNotEmpty())
                @php($maxSkillUsage = (int) $topSkills->max('usage_count'))
                <div class="analytics-skill-list">
                    @foreach($topSkills as $skill)
                        @php($skillPercent = $maxSkillUsage > 0 ? ((int) $skill->usage_count / $maxSkillUsage) * 100 : 0)
                        <div class="analytics-skill"><div class="analytics-skill-label"><strong>{{ $skill->skill_name }}</strong><span>{{ number_format((int) $skill->usage_count) }} {{ (int) $skill->usage_count === 1 ? 'job' : 'jobs' }}</span></div><div class="analytics-progress" role="progressbar" aria-label="{{ $skill->skill_name }}" aria-valuenow="{{ (int) round($skillPercent) }}" aria-valuemin="0" aria-valuemax="100"><span style="width: {{ number_format($skillPercent, 2, '.', '') }}%"></span></div></div>
                    @endforeach
                </div>
            @else
                <div class="analytics-empty analytics-empty-compact"><span><i class="fa-solid fa-lightbulb" aria-hidden="true"></i></span><p>Add skills to your job descriptions to see hiring demand here.</p></div>
            @endif
        </article>
        <article class="analytics-panel">
            <div class="analytics-panel-heading"><div><span class="analytics-section-kicker">Interview pipeline</span><h2>Interview activity</h2><p>Current status breakdown and completion progress.</p></div><span class="analytics-panel-mark"><i class="fa-solid fa-comments" aria-hidden="true"></i></span></div>
            <div class="analytics-interview-stats">
                @foreach(['Scheduled' => ['scheduled', 'is-blue'], 'Completed' => ['completed', 'is-green'], 'Rescheduled' => ['rescheduled', 'is-amber'], 'Cancelled' => ['cancelled', 'is-muted']] as $label => [$key, $class])
                    <div class="analytics-interview-stat {{ $class }}"><span>{{ $label }}</span><strong>{{ number_format((int) $interviews->{$key}) }}</strong></div>
                @endforeach
            </div>
            <div class="analytics-completion"><div><span>Completion rate</span><strong>{{ number_format($interviewCompletionRate, 1) }}%</strong></div><div class="analytics-progress" role="progressbar" aria-label="Interview completion rate" aria-valuenow="{{ (int) round($interviewCompletionRate) }}" aria-valuemin="0" aria-valuemax="100"><span style="width: {{ number_format(max(0, min(100, $interviewCompletionRate)), 1, '.', '') }}%"></span></div></div>
            <a class="analytics-text-link" href="{{ route('employer.interviews') }}">View interviews <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </article>
    </section>

    <section class="analytics-panel">
        <div class="analytics-panel-heading"><div><span class="analytics-section-kicker">Per-role breakdown</span><h2>Job performance</h2><p>Application and hiring outcomes for your highest-volume jobs.</p></div><a class="analytics-text-link" href="{{ route('employer.jobs') }}">Manage jobs <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></div>
        @if($jobPerformance->isNotEmpty())
            <div class="analytics-table-wrap"><table class="analytics-table"><thead><tr><th scope="col">Job title</th><th scope="col">Status</th><th scope="col" class="is-number">Applications</th><th scope="col" class="is-number">Shortlisted</th><th scope="col" class="is-number">Interviews</th><th scope="col" class="is-number">Selected</th><th scope="col" class="is-number">Avg. match</th></tr></thead><tbody>
                @foreach($jobPerformance as $job)
                    @php($jobStatusClass = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $job->job_status)))
                    <tr><td data-label="Job title"><a href="{{ route('employer.jobs') }}" class="analytics-job-title"><span><i class="fa-solid fa-briefcase" aria-hidden="true"></i></span>{{ $job->job_title }}</a></td><td data-label="Status"><span class="analytics-status analytics-status-{{ $jobStatusClass }}">{{ $job->job_status }}</span></td><td class="is-number" data-label="Applications">{{ number_format((int) $job->applications) }}</td><td class="is-number" data-label="Shortlisted">{{ number_format((int) $job->shortlisted) }}</td><td class="is-number" data-label="Interviews">{{ number_format((int) $job->interviews) }}</td><td class="is-number" data-label="Selected"><strong class="analytics-selected-number">{{ number_format((int) $job->selected) }}</strong></td><td class="is-number" data-label="Average match">{{ number_format((float) $job->average_match, 1) }}%</td></tr>
                @endforeach
            </tbody></table></div>
        @else
            <div class="analytics-empty analytics-empty-compact"><p>Your job performance breakdown will appear here when you post a job.</p></div>
        @endif
    </section>

    <section class="analytics-panel analytics-recent-panel">
        <div class="analytics-panel-heading"><div><span class="analytics-section-kicker">Latest activity</span><h2>Recent applications</h2><p>The latest candidates who applied to your jobs.</p></div><a class="analytics-text-link" href="{{ route('employer.applicants') }}">View applicants <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></div>
        @if($recentApplications->isNotEmpty())
            <div class="analytics-recent-list">
                @foreach($recentApplications as $application)
                    @php($candidateName = trim((string) $application->full_name) ?: 'Candidate')
                    @php($candidateInitials = collect(preg_split('/\s+/', $candidateName))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode(''))
                    @php($applicationStatusClass = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $application->application_status)))
                    <article class="analytics-recent-item"><span class="analytics-avatar" aria-hidden="true">{{ mb_strtoupper($candidateInitials ?: 'C') }}</span><div class="analytics-candidate"><strong>{{ $candidateName }}</strong><span>{{ $application->job_title }}</span></div><div class="analytics-match"><strong>{{ $application->skill_match_score !== null ? number_format((float) $application->skill_match_score, 1).'%' : '—' }}</strong><span>{{ $application->skill_match_score !== null ? 'Skill match' : 'Not scored' }}</span></div><span class="analytics-status analytics-status-{{ $applicationStatusClass }}">{{ $application->application_status }}</span><a class="analytics-view-application" href="{{ route('employer.applicant-details', ['application_id' => $application->application_id]) }}" aria-label="View application for {{ $candidateName }}"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a></article>
                @endforeach
            </div>
        @else
            <div class="analytics-empty analytics-empty-compact"><span><i class="fa-solid fa-user-group" aria-hidden="true"></i></span><p>New applications will show up here as candidates apply to your jobs.</p></div>
        @endif
    </section>
</div>
@endsection
