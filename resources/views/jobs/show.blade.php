@extends('layouts.dashboard')

@section('title', $job->job_title . ' - Job Details')
@section('role', 'Job Seeker')
@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/jobseeker/job_details.css') }}">
@endsection

@section('content')
@php
    $hasSalary = $job->minimum_salary !== null || $job->maximum_salary !== null;
    $salaryText = $hasSalary
        ? ($job->currency ?: 'USD') . ' '
            . ($job->minimum_salary !== null ? number_format((float) $job->minimum_salary, 0) : '—')
            . ' – '
            . ($job->maximum_salary !== null ? number_format((float) $job->maximum_salary, 0) : '—')
        : 'Not disclosed';
    $deadline = $job->application_deadline ? \Illuminate\Support\Carbon::parse($job->application_deadline) : null;
    $isOpen = $job->job_status === 'Open' && (!$deadline || !$deadline->startOfDay()->isBefore(today()));
@endphp
<section class="jobseeker-job-details-page">
    <a href="{{ route('jobs.index') }}" class="back-link"><i class="fa-solid fa-arrow-left"></i>Back to jobs</a>

    @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if(session('danger'))<div class="alert alert-danger" role="alert">{{ session('danger') }}</div>@endif

    <header class="job-details-header">
        <div class="job-details-header-left">
            <div class="company-logo-large">
                @if($job->logo)<img src="{{ asset('assets/uploads/companies/' . $job->logo) }}" alt="" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"><span class="logo-fallback" style="display:none"><i class="fa-solid fa-building"></i></span>@else<span class="logo-fallback"><i class="fa-solid fa-building"></i></span>@endif
            </div>
            <div class="job-details-header-info">
                <h1>{{ $job->job_title }}</h1>
                <div class="job-details-meta">
                    <a class="meta-item" href="{{ route('jobs.index', ['q' => $job->company_name]) }}"><i class="fa-solid fa-building"></i>{{ $job->company_name }}</a>
                    @if($job->category_name)<span class="meta-item"><i class="fa-solid fa-tag"></i>{{ $job->category_name }}</span>@endif
                    @if($job->location)<span class="meta-item"><i class="fa-solid fa-location-dot"></i>{{ $job->location }}</span>@endif
                    <span class="meta-item"><i class="fa-solid fa-clock"></i>Posted {{ \Illuminate\Support\Carbon::parse($job->created_at)->format('M j, Y') }}</span>
                </div>
                <div class="job-details-badges">
                    <span class="badge {{ $isOpen ? 'badge-open' : 'badge-closed' }}"><span class="dot"></span>{{ $isOpen ? 'Accepting applications' : 'Closed' }}</span>
                    <span class="badge badge-soft"><i class="fa-solid fa-briefcase"></i>{{ $job->employment_type }}</span>
                    <span class="badge badge-soft"><i class="fa-solid fa-location-dot"></i>{{ $job->workplace }}</span>
                    <span class="badge badge-soft"><i class="fa-solid fa-layer-group"></i>{{ $job->experience_level }}</span>
                </div>
            </div>
        </div>
    </header>

    <div class="job-details-layout">
        <main class="job-details-main">
            <section class="details-card"><h2>Overview</h2><div class="overview-grid">
                <div><span>Salary</span><strong>{{ $salaryText }}</strong></div>
                <div><span>Minimum Experience</span><strong>{{ $job->minimum_experience !== null ? number_format((float) $job->minimum_experience, 1) . ' years' : 'Any' }}</strong></div>
                <div><span>Vacancy</span><strong>{{ number_format((int) $job->vacancy) }}</strong></div>
                <div><span>Deadline</span><strong>{{ $deadline ? $deadline->format('M j, Y') : '—' }}</strong></div>
            </div></section>
            @foreach(['Job Description' => $job->job_description, 'Requirements' => $job->job_requirements, 'Responsibilities' => $job->job_responsibilities] as $heading => $text)
                @if($text)<section class="details-card"><h2>{{ $heading }}</h2><div class="text-content">{!! nl2br(e($text)) !!}</div></section>@endif
            @endforeach
            <section class="details-card"><h2>Required Skills</h2>
                @if($requiredSkills->isNotEmpty())<div class="skills-list">@foreach($requiredSkills as $skill)<span class="skill-chip {{ $skill->importance === 'Preferred' ? 'skill-preferred' : '' }}">{{ $skill->skill_name }} @if($skill->importance === 'Preferred')<small>preferred</small>@endif</span>@endforeach</div>@else<p class="muted">No specific skills listed for this job.</p>@endif
            </section>
            <section class="details-card"><h2>About {{ $job->company_name }}</h2><div class="company-info-grid">
                @if($job->industry)<div><span>Industry</span><strong>{{ $job->industry }}</strong></div>@endif
                @if($job->company_city || $job->company_country)<div><span>Headquarters</span><strong>{{ trim(($job->company_city ?? '') . ', ' . ($job->company_country ?? ''), ', ') }}</strong></div>@endif
                @if($job->website)<div><span>Website</span><strong><a href="{{ $job->website }}" target="_blank" rel="noopener noreferrer">{{ $job->website }}</a></strong></div>@endif
                @if($job->company_email)<div><span>Email</span><strong><a href="mailto:{{ $job->company_email }}">{{ $job->company_email }}</a></strong></div>@endif
            </div>@if($job->company_description)<div class="text-content">{!! nl2br(e($job->company_description)) !!}</div>@endif</section>
            @if($relatedJobs->isNotEmpty())<section class="details-card"><h2>More jobs at {{ $job->company_name }}</h2><ul class="related-jobs">@foreach($relatedJobs as $related)<li><a href="{{ route('jobs.show', ['id' => $related->job_id]) }}"><strong>{{ $related->job_title }}</strong><small>{{ $related->employment_type }} · {{ $related->workplace }}</small></a></li>@endforeach</ul></section>@endif
        </main>
        <aside class="job-details-sidebar">
            <div class="sidebar-card apply-card">
                <div class="apply-status"><span class="badge {{ $isOpen ? 'badge-open' : 'badge-closed' }}"><span class="dot"></span>{{ $isOpen ? 'Open' : 'Closed' }}</span></div>
                @if($application)
                    <div class="already-applied"><i class="fa-solid fa-circle-check"></i><div><strong>Application submitted</strong><span>{{ $application->application_status }}</span></div></div>
                    <a href="{{ url('/jobseeker/applications.php') }}" class="btn btn-primary btn-block"><i class="fa-solid fa-list-check"></i>View my applications</a>
                @elseif($isOpen)
                    <a href="{{ url('/jobseeker/apply_job.php') }}?job_id={{ $job->job_id }}" class="btn btn-primary btn-block"><i class="fa-solid fa-paper-plane"></i>Apply Now</a>
                @else
                    <div class="not-available"><i class="fa-solid fa-circle-exclamation"></i><span>This job is no longer accepting applications.</span></div>
                @endif
                @if($isSaved)
                    <form action="{{ route('jobs.unsave') }}" method="POST" class="inline-form">@csrf<input type="hidden" name="job_id" value="{{ $job->job_id }}"><input type="hidden" name="return_to" value="details"><button type="submit" class="btn btn-light btn-block"><i class="fa-solid fa-bookmark"></i>Saved — remove</button></form>
                @else
                    <form action="{{ route('jobs.save') }}" method="POST" class="inline-form">@csrf<input type="hidden" name="job_id" value="{{ $job->job_id }}"><input type="hidden" name="return_to" value="details"><button type="submit" class="btn btn-outline btn-block"><i class="fa-regular fa-bookmark"></i>Save this job</button></form>
                @endif
            </div>
            <div class="sidebar-card summary-card"><h3>Job Summary</h3>
                <div class="summary-row"><span><i class="fa-solid fa-briefcase"></i>Employment</span><strong>{{ $job->employment_type }}</strong></div>
                <div class="summary-row"><span><i class="fa-solid fa-location-dot"></i>Workplace</span><strong>{{ $job->workplace }}</strong></div>
                <div class="summary-row"><span><i class="fa-solid fa-user-clock"></i>Experience</span><strong>{{ $job->experience_level }}</strong></div>
                <div class="summary-row"><span><i class="fa-solid fa-money-bill-wave"></i>Salary</span><strong>{{ $salaryText }}</strong></div>
                <div class="summary-row"><span><i class="fa-solid fa-eye"></i>Views</span><strong>{{ number_format((int) $job->views) }}</strong></div>
            </div>
        </aside>
    </div>
</section>
@endsection
