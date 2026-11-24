@extends('layouts.dashboard')

@section('title', 'Candidate Comparison')
@section('role', 'Employer')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/employer/candidate_comparison.css') }}">
@endsection

@section('content')
<section class="candidate-comparison-page">
    <header class="comparison-page-header">
        <div class="comparison-header-content">
            <div class="comparison-breadcrumb"><a href="{{ route('employer.recommended-candidates', ['job_id' => $jobId]) }}">Recommended candidates</a><i class="fa-solid fa-chevron-right" aria-hidden="true"></i><span>Comparison</span></div>
            <h1>Candidate Comparison</h1>
            <p>Compare shortlisted candidates using the same transparent, AI-assisted scoring model.</p>
        </div>
        <div class="comparison-header-actions"><a href="{{ route('employer.recommended-candidates', ['job_id' => $jobId]) }}" class="comparison-btn comparison-btn-secondary"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to Candidates</a></div>
    </header>

    <section class="comparison-job-card">
        <div class="comparison-job-icon"><i class="fa-solid fa-briefcase" aria-hidden="true"></i></div>
        <div class="comparison-job-content"><span>Comparing candidates for</span><h2>{{ $jobTitle }}</h2><p>{{ $companyName }}</p></div>
        <div class="comparison-candidate-count"><strong>{{ count($candidates) }}</strong><span>Candidates</span></div>
    </section>

    @php($winner = $candidates[0])
    <section class="comparison-winner-card">
        <div class="winner-icon"><i class="fa-solid fa-trophy" aria-hidden="true"></i></div>
        <div class="winner-content"><span class="winner-label">Highest AI-Assisted Match</span><h2>{{ $winner['name'] ?: 'Candidate' }}</h2><p>{{ $winner['recommendation'] }} based on the current scoring model.</p></div>
        <div class="winner-score"><strong>{{ number_format($winner['overallScore'], 1) }}%</strong><span>Overall Score</span></div>
    </section>

    <section class="comparison-panel">
        <div class="section-heading"><div><span class="section-eyebrow">Candidate Analysis</span><h2>Side-by-Side Comparison</h2></div></div>
        <div class="comparison-table-wrapper">
            <table class="comparison-table">
                <thead><tr><th class="comparison-label-column">Criteria</th>
                    @foreach($candidates as $candidate)
                        @php($nameParts = preg_split('/\s+/', trim($candidate['name']), -1, PREG_SPLIT_NO_EMPTY))
                        <th><div class="comparison-candidate-header"><div class="candidate-avatar">{{ mb_strtoupper(mb_substr($nameParts[0] ?? 'C', 0, 1)) }}@if(count($nameParts) > 1){{ mb_strtoupper(mb_substr($nameParts[count($nameParts) - 1], 0, 1)) }}@endif</div><div><strong>{{ $candidate['name'] ?: 'Candidate' }}</strong><span>Rank #{{ $candidate['comparisonRank'] }}</span></div></div></th>
                    @endforeach
                </tr></thead>
                <tbody>
                    @foreach([
                        ['Overall Score', 'overallScore', 1],
                        ['Required Skill Match · 50%', 'skillMatch', 1],
                        ['Experience Match · 20%', 'experienceMatch', 1],
                        ['Profile Completeness · 10%', 'profileScore', 1],
                        ['Resume Availability · 10%', 'resumeScore', 1],
                        ['Application Readiness · 10%', 'readinessScore', 1],
                    ] as [$label, $key, $precision])
                        <tr @class(['comparison-highlight' => $key === 'overallScore'])><th>{{ $label }}</th>@foreach($candidates as $candidate)<td>@if($key === 'overallScore')<span class="comparison-score {{ $candidate['scoreClass'] }}">{{ number_format($candidate[$key], $precision) }}%</span>@else<strong>{{ number_format($candidate[$key], $precision) }}%</strong>@endif</td>@endforeach</tr>
                    @endforeach
                    <tr><th>Recommendation</th>@foreach($candidates as $candidate)<td><span class="comparison-recommendation {{ $candidate['scoreClass'] }}">{{ $candidate['recommendation'] }}</span></td>@endforeach</tr>
                    <tr><th>Stored AI Rank</th>@foreach($candidates as $candidate)<td>@if($candidate['aiRank'])<span class="rank-badge">#{{ $candidate['aiRank'] }}</span>@else<span class="muted-value">Not ranked</span>@endif</td>@endforeach</tr>
                    <tr><th>Application Status</th>@foreach($candidates as $candidate)<td>{{ $candidate['status'] }}</td>@endforeach</tr>
                    <tr><th>Matched Skills</th>@foreach($candidates as $candidate)<td>@forelse($candidate['matchedSkills'] as $skill)<span class="skill-tag matched">{{ $skill }}</span>@empty<span class="muted-value">None</span>@endforelse</td>@endforeach</tr>
                    <tr><th>Missing Skills</th>@foreach($candidates as $candidate)<td>@forelse($candidate['missingSkills'] as $skill)<span class="skill-tag missing">{{ $skill }}</span>@empty<span class="muted-value">None</span>@endforelse</td>@endforeach</tr>
                    <tr><th>Additional Skills</th>@foreach($candidates as $candidate)<td>@forelse($candidate['additionalSkills'] as $skill)<span class="skill-tag">{{ $skill }}</span>@empty<span class="muted-value">None</span>@endforelse</td>@endforeach</tr>
                    <tr><th>Availability</th>@foreach($candidates as $candidate)<td>{{ $candidate['availability'] ?: 'Not specified' }}</td>@endforeach</tr>
                    <tr><th>Contact</th>@foreach($candidates as $candidate)<td><a href="mailto:{{ $candidate['email'] }}">{{ $candidate['email'] }}</a></td>@endforeach</tr>
                </tbody>
            </table>
        </div>
    </section>
    <p class="comparison-disclaimer"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> These AI-assisted scores are decision-support information and do not replace human review or the employer's hiring decision.</p>
</section>
@endsection
