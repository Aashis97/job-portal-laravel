@extends('layouts.dashboard')

@section('title', 'Recommended Candidates')
@section('role', 'Employer')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/employer/recommended_candidates.css') }}">
@endsection

@section('js')
<script src="{{ asset('assets/js/employer/recommended_candidates.js') }}"></script>
@endsection

@section('content')
<div class="recommended-candidates-page">
    <div class="rc-page">
        @if(session('success') || session('error'))
            <div class="rc-alert" role="status">{{ session('success') ?? session('error') }}</div>
        @endif
        <header class="rc-hero">
            <div class="rc-hero-copy">
                <span class="rc-eyebrow"><i class="fa-solid fa-sparkles" aria-hidden="true"></i> AI-assisted recruiting</span>
                <h1>Recommended candidates</h1>
                <p>Review applicants ranked by skill match, check profile readiness, and compare your shortlist.</p>
                <span class="rc-company"><i class="fa-solid fa-building" aria-hidden="true"></i>{{ $employer->company_name }}</span>
            </div>
            <a class="rc-button rc-button-primary" href="{{ route('employer.candidate-ranking') }}">
                <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> Run AI ranking
            </a>
        </header>

        <section class="rc-metrics" aria-label="Candidate overview">
            <article class="rc-metric"><span class="rc-metric-icon"><i class="fa-solid fa-users" aria-hidden="true"></i></span><div><span>Matching applicants</span><strong>{{ number_format($totalRecommended) }}</strong></div></article>
            <article class="rc-metric rc-metric-excellent"><span class="rc-metric-icon"><i class="fa-solid fa-star" aria-hidden="true"></i></span><div><span>Excellent match</span><strong>{{ number_format($scoreCounts['excellent']) }}</strong></div></article>
            <article class="rc-metric rc-metric-strong"><span class="rc-metric-icon"><i class="fa-solid fa-ranking-star" aria-hidden="true"></i></span><div><span>Strong match</span><strong>{{ number_format($scoreCounts['strong']) }}</strong></div></article>
            <article class="rc-metric rc-metric-average"><span class="rc-metric-icon"><i class="fa-solid fa-chart-line" aria-hidden="true"></i></span><div><span>Average match score</span><strong>{{ number_format($averageScore, 1) }}%</strong></div></article>
        </section>

        <section class="rc-filter-panel" aria-labelledby="rcFiltersTitle">
            <div class="rc-section-heading"><div><span class="rc-eyebrow">Refine your search</span><h2 id="rcFiltersTitle">Candidate filters</h2></div></div>
            <form method="GET" action="{{ route('employer.recommended-candidates') }}" class="rc-filter-form">
                <div class="rc-field"><label for="job_id">Job posting</label><select name="job_id" id="job_id"><option value="0">All jobs</option>@foreach($jobs as $job)<option value="{{ $job->job_id }}" @selected((int) $jobId === (int) $job->job_id)>{{ $job->job_title }}{{ $job->job_status !== 'Open' ? ' · ' . $job->job_status : '' }}</option>@endforeach</select></div>
                <div class="rc-field"><label for="minimum_score">Minimum match</label><select name="minimum_score" id="minimum_score">@foreach([0 => 'Any score', 60 => '60% and above', 70 => '70% and above', 80 => '80% and above', 90 => '90% and above'] as $value => $label)<option value="{{ $value }}" @selected((int) $minimumScore === $value)>{{ $label }}</option>@endforeach</select></div>
                <div class="rc-field"><label for="status_filter">Application status</label><select name="status" id="status_filter"><option value="">All active applications</option>@foreach($allowedApplicationStatuses as $status)<option value="{{ $status }}" @selected($statusFilter === $status)>{{ $status }}</option>@endforeach</select></div>
                <div class="rc-field rc-search-field"><label for="search">Candidate or job</label><div class="rc-search-input"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input type="search" name="search" id="search" value="{{ $search }}" maxlength="100" placeholder="Search name, email, or role"></div></div>
                <div class="rc-filter-actions"><button type="submit" class="rc-button rc-button-primary"><i class="fa-solid fa-filter" aria-hidden="true"></i> Apply filters</button><a href="{{ route('employer.recommended-candidates') }}" class="rc-button rc-button-secondary">Reset</a></div>
            </form>
        </section>

        <section class="rc-results" aria-labelledby="rcResultsTitle">
            <div class="rc-results-heading"><div><span class="rc-eyebrow">Ranked applicants</span><h2 id="rcResultsTitle">Candidate recommendations</h2></div><span class="rc-results-count">{{ number_format($totalRecommended) }} {{ $totalRecommended === 1 ? 'candidate' : 'candidates' }}</span></div>
            <div class="rc-distribution" aria-label="Match score distribution">
                @foreach(['excellent' => ['Excellent', '90%+'], 'strong' => ['Strong', '80–89%'], 'good' => ['Good', '70–79%'], 'moderate' => ['Moderate', '60–69%'], 'low' => ['Developing', 'Below 60%']] as $class => [$label, $range])
                    <div class="rc-distribution-item rc-score-{{ $class }}"><span class="rc-distribution-dot" aria-hidden="true"></span><span>{{ $label }}</span><strong>{{ number_format($scoreCounts[$class]) }}</strong><small>{{ $range }}</small></div>
                @endforeach
            </div>
            @if($candidates->isEmpty())
                <div class="rc-empty-state"><span class="rc-empty-icon"><i class="fa-solid fa-user-magnifying-glass" aria-hidden="true"></i></span><h3>No matching candidates</h3><p>Try changing the filters, or run AI ranking for applications that do not yet have match scores.</p><a href="{{ route('employer.candidate-ranking') }}" class="rc-button rc-button-primary">Run AI ranking</a></div>
            @else
                <div class="rc-candidate-list">
                    @foreach($candidates as $index => $candidate)
                        @php
                            $score = max(0, min(100, (float) ($candidate->skill_match_score ?? 0)));
                            $scoreClass = match (true) {
                                $score >= 90 => 'excellent',
                                $score >= 80 => 'strong',
                                $score >= 70 => 'good',
                                $score >= 60 => 'moderate',
                                default => 'low',
                            };
                            $profileCompleted = (int) ($candidate->profile_completed ?? 0) === 1;
                            $hasResume = !empty($candidate->resume_file);
                            $candidateSkills = $skillsByUser[(int) $candidate->user_id] ?? [];
                        @endphp
                        <article class="rc-candidate-card">
                            <div class="rc-card-topline"><span class="rc-list-rank">#{{ $index + 1 }}</span>@if($candidate->ai_rank)<span class="rc-ai-rank"><i class="fa-solid fa-ranking-star" aria-hidden="true"></i> AI rank {{ $candidate->ai_rank }}</span>@endif<label class="rc-compare-option"><input type="checkbox" value="{{ $candidate->application_id }}" data-compare-candidate><span>Compare</span></label></div>
                            <div class="rc-card-main">
                                <div class="rc-identity"><div class="rc-avatar"><span class="rc-avatar-fallback" aria-hidden="true">{{ mb_strtoupper(mb_substr(trim((string) $candidate->full_name) ?: 'C', 0, 1)) }}</span></div><div class="rc-person"><h3>{{ $candidate->full_name ?: 'Candidate' }}</h3><a href="mailto:{{ $candidate->email }}"><i class="fa-regular fa-envelope" aria-hidden="true"></i> {{ $candidate->email }}</a></div></div>
                                <div class="rc-match rc-score-{{ $scoreClass }}" aria-label="{{ number_format($score, 1) }} percent match"><span class="rc-match-ring" style="--rc-score: {{ number_format($score, 1, '.', '') }}%"><span>{{ number_format($score, 0) }}<small>%</small></span></span><span class="rc-match-copy"><small>Skill match</small><strong>{{ ucfirst($scoreClass) }} match</strong></span></div>
                            </div>
                            <div class="rc-role"><span><i class="fa-solid fa-briefcase" aria-hidden="true"></i>{{ $candidate->job_title }}</span>@if($candidate->category_name)<span><i class="fa-solid fa-layer-group" aria-hidden="true"></i>{{ $candidate->category_name }}</span>@endif @if($candidate->availability)<span><i class="fa-regular fa-clock" aria-hidden="true"></i>{{ $candidate->availability }}</span>@endif</div>
                            <div class="rc-card-details">
                                <div class="rc-readiness"><span class="rc-detail-label">Profile readiness</span><div class="rc-readiness-items"><span class="{{ $profileCompleted ? 'is-ready' : 'is-missing' }}">Profile</span><span class="{{ $hasResume ? 'is-ready' : 'is-missing' }}">Resume</span><span class="{{ $candidateSkills ? 'is-ready' : 'is-missing' }}">Skills</span></div></div>
                                <div class="rc-status-block"><span class="rc-detail-label">Application status</span><span class="rc-status">{{ $candidate->application_status }}</span></div>
                                @if($candidateSkills)<div class="rc-skills"><span class="rc-detail-label">Candidate skills</span><div class="rc-skill-list">@foreach(array_slice($candidateSkills, 0, 5) as $skill)<span>{{ $skill }}</span>@endforeach</div></div>@endif
                            </div>
                            <div class="rc-card-actions"><a class="rc-button rc-button-primary" href="{{ route('employer.candidate-insights', ['application_id' => $candidate->application_id]) }}"><i class="fa-solid fa-brain" aria-hidden="true"></i> AI insights</a><a class="rc-button rc-button-secondary" href="{{ route('employer.applicant-details', ['application_id' => $candidate->application_id]) }}">View application</a></div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
        <aside class="rc-disclaimer"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><p><strong>AI-assisted recommendations.</strong> Match scores support review by highlighting relevant skills and profile readiness. They are decision-support information, not a substitute for human hiring judgment.</p></aside>
    </div>
    <div class="rc-compare-bar" data-compare-bar data-compare-url="{{ route('employer.candidate-comparison') }}" hidden>
        <span><strong data-compare-count>0</strong> selected <small>(select 2–4 candidates)</small></span>
        <div><button type="button" class="rc-button rc-button-secondary" data-compare-clear>Clear</button><button type="button" class="rc-button rc-button-primary" data-compare-go disabled><i class="fa-solid fa-code-compare" aria-hidden="true"></i> Compare candidates</button></div>
    </div>
</div>
@endsection
