@extends('layouts.dashboard')

@section('title', 'Candidate AI Insights')
@section('role', 'Employer')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/employer/applicant_details.css') }}">
@endsection

@section('content')
<div class="applicant-details-page">
    <a href="{{ route('employer.applicant-details', ['application_id' => $application->application_id]) }}" class="back-link">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Applicant Details
    </a>

    <section class="candidate-header-card">
        <div class="candidate-header-left">
            <div class="candidate-header-info">
                <span class="card-kicker">AI CANDIDATE INTELLIGENCE</span>
                <h1>{{ trim((string) $application->full_name) ?: 'Unknown Candidate' }}</h1>
                <p><i class="fa-solid fa-briefcase" aria-hidden="true"></i> {{ $application->job_title }}</p>
                <p><i class="fa-solid fa-envelope" aria-hidden="true"></i> {{ $application->email }}</p>
            </div>
        </div>
        <div class="score-card">
            <div class="score-card-header"><span>Overall Match</span><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i></div>
            <div class="large-score-circle" style="--score: {{ $score['overall'] }};"><div class="score-circle-inner"><strong>{{ number_format($score['overall'], 0) }}%</strong><span>Overall</span></div></div>
            <div class="score-rating"><strong>{{ $score['recommendation'] }}</strong><p>AI-assisted candidate evaluation</p></div>
        </div>
    </section>

    <section class="details-card">
        <div class="card-header"><div><span class="card-kicker">WEIGHTED SCORING MODEL</span><h2>Score Breakdown</h2></div></div>
        <div class="comparison-summary">
            <div class="comparison-number"><strong>{{ number_format($score['skillMatch'], 1) }}%</strong><span>Skills · 50%</span></div>
            <div class="comparison-divider"></div>
            <div class="comparison-number"><strong>{{ number_format($score['experienceMatch'], 1) }}%</strong><span>Experience · 20%</span></div>
            <div class="comparison-divider"></div>
            <div class="comparison-number"><strong>{{ number_format($score['profileScore'], 1) }}%</strong><span>Profile · 10%</span></div>
            <div class="comparison-divider"></div>
            <div class="comparison-number"><strong>{{ number_format($score['resumeScore'], 1) }}%</strong><span>Resume · 10%</span></div>
            <div class="comparison-divider"></div>
            <div class="comparison-number"><strong>{{ number_format($score['readiness'], 1) }}%</strong><span>Readiness · 10%</span></div>
        </div>
    </section>

    <section class="details-card">
        <div class="card-header"><div><span class="card-kicker">SKILL INTELLIGENCE</span><h2>Skills Analysis</h2></div></div>
        <div class="comparison-groups">
            <div class="comparison-group"><h3 class="matched-heading"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Matched Skills</h3>@if($score['matchedSkills'])<div class="comparison-tags">@foreach($score['matchedSkills'] as $skill)<span class="comparison-tag matched">{{ $skill }}</span>@endforeach</div>@else<p class="comparison-empty">No required skills matched.</p>@endif</div>
            <div class="comparison-group"><h3 class="missing-heading"><i class="fa-solid fa-circle-xmark" aria-hidden="true"></i> Missing Skills</h3>@if($score['missingSkills'])<div class="comparison-tags">@foreach($score['missingSkills'] as $skill)<span class="comparison-tag missing">{{ $skill }}</span>@endforeach</div>@else<p class="comparison-empty">Candidate matches all required skills.</p>@endif</div>
            <div class="comparison-group"><h3><i class="fa-solid fa-plus" aria-hidden="true"></i> Additional Skills</h3>@if($additionalSkills)<div class="comparison-tags">@foreach($additionalSkills as $skill)<span class="comparison-tag">{{ $skill }}</span>@endforeach</div>@else<p class="comparison-empty">No additional skills listed.</p>@endif</div>
        </div>
    </section>

    <section class="details-card">
        <div class="card-header"><div><span class="card-kicker">POSITIVE SIGNALS AND HUMAN REVIEW</span><h2>Candidate Insights</h2></div></div>
        <div class="comparison-groups">
            <div class="comparison-group">
                <h3 class="matched-heading"><i class="fa-solid fa-thumbs-up" aria-hidden="true"></i> Candidate Strengths</h3>
                @if($strengths)
                    <ul>@foreach($strengths as $strength)<li>{{ $strength }}</li>@endforeach</ul>
                @else
                    <p class="comparison-empty">No strong positive signals were identified from the available data.</p>
                @endif
            </div>
            <div class="comparison-group">
                <h3 class="missing-heading"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Areas to Review</h3>
                <ul>@foreach($areasToReview as $area)<li>{{ $area }}</li>@endforeach</ul>
            </div>
        </div>
    </section>

    <section class="details-card">
        <div class="card-header"><div><span class="card-kicker">AI-ASSISTED RECOMMENDATION</span><h2>{{ $score['recommendation'] }}</h2></div></div>
        <p>{{ $humanReviewRecommendation }}</p>
        <div class="info-list">
            <div class="info-row"><span>Application status</span><strong>{{ $application->application_status ?: 'Unknown' }}</strong></div>
            <div class="info-row"><span>Job experience level</span><strong>{{ $application->experience_level ?: 'Not specified' }}</strong></div>
            <div class="info-row"><span>Candidate availability</span><strong>{{ $application->availability ?: 'Not specified' }}</strong></div>
            <div class="info-row"><span>Required skills</span><strong>{{ count($requiredSkills) }}</strong></div>
            <div class="info-row"><span>Candidate skills</span><strong>{{ count($candidateSkills) }}</strong></div>
            <div class="info-row"><span>AI rank</span><strong>{{ $application->ai_rank ? '#' . $application->ai_rank : 'Not ranked' }}</strong></div>
        </div>
    </section>

    <p class="card-empty"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> This insight is generated from currently available candidate information to support employer decision-making, not replace human judgment. Review resumes, qualifications, experience, and interview performance before making a hiring decision.</p>
</div>
<script>
    window.candidateAIInsights = @json($insightsData);
</script>
@endsection
