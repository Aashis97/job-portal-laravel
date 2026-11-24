@extends('layouts.dashboard')

@section('title', 'Recommended Jobs')
@section('role', 'Job Seeker')
@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/jobseeker/recommendations.css') }}">
@endsection

@section('content')
<section class="jobseeker-recommendations-page">
    <div class="page-header">
        <div>
            <span class="page-tag"><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>AI Powered</span>
            <h1>Recommended Jobs</h1>
            <p>Jobs matched to your skills, experience and location.</p>
        </div>
        <form action="{{ route('jobseeker.recommendations.generate') }}" method="POST" class="regenerate-form" data-confirm="{{ $recommendations->isNotEmpty() ? 'Regenerate recommendations? Existing matches will be replaced.' : 'Generate job recommendations?' }}">
            @csrf
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i>{{ $recommendations->isNotEmpty() ? 'Refresh' : 'Generate' }}</button>
        </form>
    </div>

    @foreach(['success', 'danger', 'warning', 'info'] as $type)
        @if(session($type))
            <div class="alert alert-{{ $type }}" role="{{ $type === 'danger' ? 'alert' : 'status' }}">{{ session($type) }}</div>
        @endif
    @endforeach

    <div class="info-banner">
        <div class="info-banner-icon"><i class="fa-solid fa-brain" aria-hidden="true"></i></div>
        <div class="info-banner-content">
            <h3>Smart matching</h3>
            <p>Recommendations are weighted on three signals: <strong>skill match (65%)</strong>, <strong>experience level (20%)</strong>, and <strong>location preference (15%)</strong>.</p>
            @if($lastGenerated)
                <span class="last-generated"><i class="fa-regular fa-clock" aria-hidden="true"></i>Last generated {{ \Illuminate\Support\Carbon::parse($lastGenerated)->diffForHumans() }}</span>
            @endif
        </div>
    </div>

    @if($recommendations->isEmpty())
        <div class="recommendations-empty">
            <div class="empty-icon"><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i></div>
            <h2>No recommendations yet</h2>
            <p>Complete your profile, add skills and upload a resume, then click below to generate matches.</p>
            <form action="{{ route('jobseeker.recommendations.generate') }}" method="POST" data-confirm="Generate job recommendations now?">
                @csrf
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>Generate Recommendations</button>
            </form>
            <div class="empty-hints">
                <a href="{{ route('jobseeker.profile') }}"><i class="fa-solid fa-user-pen" aria-hidden="true"></i>Complete your profile</a>
                <a href="{{ route('jobseeker.resume') }}"><i class="fa-solid fa-file-arrow-up" aria-hidden="true"></i>Upload your resume</a>
                <a href="{{ route('jobs.index') }}"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>Browse jobs manually</a>
            </div>
        </div>
    @else
        <div class="recommendations-grid">
            @foreach($recommendations as $job)
                @php
                    $score = (float) $job->matching_score;
                    $scoreClass = $score >= 80 ? 'score-excellent' : ($score >= 60 ? 'score-good' : ($score >= 40 ? 'score-moderate' : 'score-low'));
                    $shortDescription = mb_strimwidth(trim(strip_tags((string) $job->job_description)), 0, 160, '…');
                    $hasSalary = $job->minimum_salary !== null || $job->maximum_salary !== null;
                @endphp
                <article class="recommendation-card">
                    <div class="rec-card-top">
                        <div class="company-logo">
                            @if($job->logo)
                                <img src="{{ asset('assets/uploads/companies/' . $job->logo) }}" alt="" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                <span class="logo-fallback" style="display:none"><i class="fa-solid fa-building" aria-hidden="true"></i></span>
                            @else
                                <span class="logo-fallback"><i class="fa-solid fa-building" aria-hidden="true"></i></span>
                            @endif
                        </div>
                        <div class="company-info">
                            <span class="company-name">{{ $job->company_name }}</span>
                            @if($job->category_name)
                                <span class="company-category"><i class="fa-solid fa-tag" aria-hidden="true"></i>{{ $job->category_name }}</span>
                            @endif
                        </div>
                        <div class="match-pill {{ $scoreClass }}"><strong>{{ number_format($score, 0) }}%</strong><span>match</span></div>
                    </div>

                    <a href="{{ route('jobs.show', ['id' => $job->job_id]) }}" class="recommendation-title">{{ $job->job_title }}</a>
                    <div class="recommendation-meta">
                        <span><i class="fa-solid fa-briefcase" aria-hidden="true"></i>{{ $job->employment_type }}</span>
                        <span><i class="fa-solid fa-location-dot" aria-hidden="true"></i>{{ $job->workplace }}</span>
                        <span><i class="fa-solid fa-layer-group" aria-hidden="true"></i>{{ $job->experience_level }}</span>
                    </div>
                    @if($job->location)
                        <div class="recommendation-location"><i class="fa-solid fa-map-pin" aria-hidden="true"></i>{{ $job->location }}</div>
                    @endif
                    @if($shortDescription !== '')
                        <p class="recommendation-description">{{ $shortDescription }}</p>
                    @endif
                    @if($hasSalary)
                        <div class="recommendation-salary">
                            <i class="fa-solid fa-money-bill-wave" aria-hidden="true"></i>{{ $job->currency ?: 'USD' }}
                            {{ number_format((float) ($job->minimum_salary ?? 0), 0) }} – {{ number_format((float) ($job->maximum_salary ?? 0), 0) }}
                        </div>
                    @endif
                    <div class="recommendation-footer">
                        <span class="ai-label"><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>AI Recommended</span>
                        <a href="{{ route('jobs.show', ['id' => $job->job_id]) }}" class="btn btn-primary btn-sm">View Job<i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>
@endsection

@section('js')
<script src="{{ asset('assets/js/jobseeker/recommendations.js') }}"></script>
@endsection
