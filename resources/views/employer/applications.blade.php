@extends('layouts.dashboard')

@section('title', $isCandidatesView ? 'Candidates' : 'Applications')
@section('role', 'Employer')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/employer/applications.css') }}">
@endsection

@section('content')
<section class="employer-applications-page">
    <header class="applications-page-header">
        <div><span class="applications-eyebrow">Employer workspace</span><h1>{{ $isCandidatesView ? 'Candidates' : 'Applications' }}</h1><p>{{ $isCandidatesView ? 'Choose a job to review its candidate pipeline.' : 'Track and review applications across your job openings.' }}</p></div>
        <a class="applications-post-link" href="{{ route('employer.jobs.create') }}"><i class="fa-solid fa-plus" aria-hidden="true"></i>Post a Job</a>
    </header>

    <div class="applications-summary" aria-label="Application summary">
        <div><span>Job openings</span><strong>{{ number_format($jobs->count()) }}</strong></div>
        <div><span>Total applications</span><strong>{{ number_format($totalApplications) }}</strong></div>
        <div><span>Awaiting review</span><strong>{{ number_format($pendingApplications) }}</strong></div>
    </div>

    <section class="application-jobs-section" aria-labelledby="application-jobs-heading">
        <div class="application-jobs-heading"><div><h2 id="application-jobs-heading">Your job openings</h2><p>Select a job to see its applicants and application statuses.</p></div><a href="{{ route('employer.jobs') }}">Manage jobs <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></div>

        @if($jobs->isEmpty())
            <div class="applications-empty"><i class="fa-solid fa-briefcase" aria-hidden="true"></i><h3>No job openings yet</h3><p>Post a job to start receiving applications.</p><a href="{{ route('employer.jobs.create') }}">Create a job</a></div>
        @else
            <div class="application-job-list">
                @foreach($jobs as $job)
                    <article class="application-job-row">
                        <div class="application-job-icon"><i class="fa-solid fa-briefcase" aria-hidden="true"></i></div>
                        <div class="application-job-main"><h3>{{ $job->job_title }}</h3><div class="application-job-meta"><span>{{ $job->location ?: 'Location not specified' }}</span><span>{{ $job->employment_type }}</span><span class="job-status status-{{ strtolower($job->job_status) }}">{{ $job->job_status }}</span></div></div>
                        <div class="application-job-count"><strong>{{ number_format((int) $job->application_count) }}</strong><span>applications</span></div>
                        <a class="application-job-action" href="{{ route('employer.applicants', ['job_id' => $job->job_id]) }}">View applicants <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</section>
@endsection
