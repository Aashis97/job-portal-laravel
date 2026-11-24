@extends('layouts.dashboard')

@section('title', 'AI Candidate Ranking')
@section('role', 'Employer')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/employer/ai_candidate_ranking.css') }}">
@endsection

@section('content')
<section class="employer-ai-ranking-page">
    @if(session('error') || session('warning'))
        <div class="ai-ranking-empty" role="status">{{ session('error') ?? session('warning') }}</div>
    @endif
    @if(session('success'))
        <div class="ai-ranking-empty" role="status">{{ session('success') }}</div>
    @endif
    <header class="ai-ranking-header">
        <div class="ai-ranking-header-icon"><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i></div>
        <div>
            <span>Candidate tools</span>
            <h1>AI Candidate Ranking</h1>
            <p>Rank eligible applicants using the transparent skills, profile, resume, and readiness model.</p>
        </div>
    </header>
    <p><a href="{{ route('employer.recommended-candidates') }}">View recommended candidates</a></p>
    @if($jobs->isEmpty())
        <div class="ai-ranking-empty">
            <i class="fa-solid fa-briefcase" aria-hidden="true"></i>
            <h2>No jobs available</h2>
            <p>Post a job before running candidate ranking.</p>
            <a href="{{ route('employer.jobs.create') }}">Post a Job</a>
        </div>
    @else
        <div class="ai-ranking-job-list">
            @foreach($jobs as $job)
                <article class="ai-ranking-job">
                    <div class="ai-ranking-job-copy">
                        <span class="ai-ranking-job-status">{{ $job->job_status }}</span>
                        <h2>{{ $job->job_title }}</h2>
                        <p>{{ number_format((int) $job->eligible_applications) }} eligible candidate{{ (int) $job->eligible_applications === 1 ? '' : 's' }}</p>
                    </div>
                    @if((int) $job->eligible_applications > 0)
                        <form action="{{ route('employer.candidate-ranking.run') }}" method="POST">
                            @csrf
                            <input type="hidden" name="job_id" value="{{ $job->job_id }}">
                            <button type="submit"><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> Rank candidates</button>
                        </form>
                    @else
                        <span class="ai-ranking-no-candidates">No applicants yet</span>
                    @endif
                </article>
            @endforeach
        </div>
    @endif
</section>
@endsection
