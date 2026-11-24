@extends('layouts.dashboard')

@section('title', 'AI Candidate Score')
@section('role', 'Employer')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/employer/applicant_details.css') }}">
@endsection

@section('content')
<div class="applicant-details-page">
    <a href="{{ route('employer.applicant-details', ['application_id' => $application->application_id]) }}" class="back-link">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Applicant Details
    </a>

    @if(session('error'))
        <div class="status-notice status-notice-danger" role="alert">
            <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
            <div><strong>{{ session('error') }}</strong></div>
        </div>
    @endif

    <section class="candidate-header-card">
        <div class="candidate-header-left">
            <div class="candidate-header-info">
                <span class="card-kicker">AI-ASSISTED CANDIDATE EVALUATION</span>
                <h1>{{ trim((string) $application->full_name) ?: 'Unknown Candidate' }}</h1>
                <p><i class="fa-solid fa-briefcase" aria-hidden="true"></i> {{ $application->job_title }}</p>
                <p><i class="fa-solid fa-envelope" aria-hidden="true"></i> {{ $application->email }}</p>
            </div>
        </div>
        <div class="score-card">
            <div class="score-card-header"><span>Overall Score</span><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i></div>
            <div class="large-score-circle" style="--score: {{ $score['overall'] }};">
                <div class="score-circle-inner"><strong>{{ number_format($score['overall'], 0) }}%</strong><span>Overall</span></div>
            </div>
            <div class="score-rating"><strong>{{ $score['recommendation'] }}</strong><p>Decision-support information; hiring decisions remain with your team.</p></div>
        </div>
    </section>

    <section class="details-card">
        <div class="card-header">
            <div><span class="card-kicker">TRANSPARENT WEIGHTED MODEL</span><h2>Candidate Evaluation</h2></div>
        </div>
        <div class="comparison-summary">
            <div class="comparison-number"><strong>{{ number_format($score['skillMatch'], 1) }}%</strong><span>Required skills · 50%</span></div>
            <div class="comparison-divider"></div>
            <div class="comparison-number"><strong>{{ number_format($score['experienceMatch'], 1) }}%</strong><span>Experience · 20%</span></div>
            <div class="comparison-divider"></div>
            <div class="comparison-number"><strong>{{ number_format($score['profileScore'], 1) }}%</strong><span>Profile · 10%</span></div>
            <div class="comparison-divider"></div>
            <div class="comparison-number"><strong>{{ number_format($score['resumeScore'], 1) }}%</strong><span>Resume · 10%</span></div>
            <div class="comparison-divider"></div>
            <div class="comparison-number"><strong>{{ number_format($score['readiness'], 1) }}%</strong><span>Readiness · 10%</span></div>
        </div>
        <div class="comparison-groups">
            <div class="comparison-group">
                <h3 class="matched-heading"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Matched Skills</h3>
                @if($score['matchedSkills'])
                    <div class="comparison-tags">@foreach($score['matchedSkills'] as $skill)<span class="comparison-tag matched">{{ $skill }}</span>@endforeach</div>
                @else
                    <p class="comparison-empty">No required skills matched.</p>
                @endif
            </div>
            <div class="comparison-group">
                <h3 class="missing-heading"><i class="fa-solid fa-circle-xmark" aria-hidden="true"></i> Missing Skills</h3>
                @if($score['missingSkills'])
                    <div class="comparison-tags">@foreach($score['missingSkills'] as $skill)<span class="comparison-tag missing">{{ $skill ?: 'Unnamed skill' }}</span>@endforeach</div>
                @else
                    <p class="comparison-empty">Candidate matches all required skills.</p>
                @endif
            </div>
        </div>
        <p class="card-empty">Experience compatibility uses the neutral 50% value because the existing profile schema has no candidate experience-level field.</p>
    </section>
</div>
@endsection
