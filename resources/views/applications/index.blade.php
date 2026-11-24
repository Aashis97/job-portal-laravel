@extends('layouts.dashboard')

@section('title', 'My Applications')
@section('role', 'Job Seeker')
@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/jobseeker/applications.css') }}">
@endsection

@section('content')
@php
    $tabMeta = [
        'Applied' => ['Applied', 'applied'],
        'Under Review' => ['Under Review', 'review'],
        'Shortlisted' => ['Shortlisted', 'shortlisted'],
        'Interview Scheduled' => ['Interview', 'interview'],
        'Selected' => ['Selected', 'selected'],
        'Rejected' => ['Rejected', 'rejected'],
        'Withdrawn' => ['Withdrawn', 'withdrawn'],
    ];
    $pageStart = max(1, $applications->currentPage() - 2);
    $pageEnd = min($applications->lastPage(), $applications->currentPage() + 2);
@endphp
<section class="jobseeker-applications-page">
    <div class="page-header">
        <div>
            <span class="page-tag">Activity</span>
            <h1>My Applications</h1>
            <p>Track every job you've applied to and monitor your progress.</p>
        </div>
        <div class="results-count">
            <strong>{{ number_format($totalApplications) }}</strong>
            <span>total</span>
        </div>
    </div>

    @foreach(['success', 'danger', 'warning', 'info'] as $type)
        @if(session($type))
            <div class="alert alert-{{ $type }}" role="{{ $type === 'danger' ? 'alert' : 'status' }}">{{ session($type) }}</div>
        @endif
    @endforeach

    <div class="applications-tabs">
        <a href="{{ route('applications.index', ['q' => $queryText ?: null]) }}" class="tab {{ $status === '' ? 'active' : '' }}">
            All <span class="tab-count">{{ number_format($totalApplications) }}</span>
        </a>
        @foreach($tabMeta as $key => [$label, $slug])
            @if($statusCounts[$key] > 0 || $status === $key)
                <a href="{{ route('applications.index', ['status' => $key, 'q' => $queryText ?: null]) }}" class="tab tab-{{ $slug }} {{ $status === $key ? 'active' : '' }}">
                    {{ $label }} <span class="tab-count">{{ number_format($statusCounts[$key]) }}</span>
                </a>
            @endif
        @endforeach
    </div>

    <form action="{{ route('applications.index') }}" method="GET" class="applications-search-form">
        @if($status !== '')<input type="hidden" name="status" value="{{ $status }}">@endif
        <div class="search-wrapper">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input type="search" name="q" value="{{ $queryText }}" placeholder="Search by job title or company..." maxlength="100" autocomplete="off">
        </div>
        @if($queryText !== '')
            <a href="{{ route('applications.index', ['status' => $status ?: null]) }}" class="btn btn-light">Clear</a>
        @endif
    </form>

    @if($applications->isEmpty())
        <div class="applications-empty">
            <div class="empty-icon"><i class="fa-solid fa-file-circle-question" aria-hidden="true"></i></div>
            <h3>{{ $status !== '' || $queryText !== '' ? 'No applications match your filters' : 'No applications yet' }}</h3>
            <p>{{ $status !== '' || $queryText !== '' ? 'Try a different search term or clear the filter.' : 'Start exploring jobs and submit your first application.' }}</p>
            @if($status !== '' || $queryText !== '')
                <a href="{{ route('applications.index') }}" class="btn btn-light">Clear Filters</a>
            @else
                <a href="{{ route('jobs.index') }}" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>Find Jobs</a>
            @endif
        </div>
    @else
        <div class="applications-list">
            @foreach($applications as $application)
                @php
                    $canWithdraw = in_array($application->application_status, ['Applied', 'Under Review', 'Shortlisted'], true);
                    $appliedAt = $application->applied_at ? \Illuminate\Support\Carbon::parse($application->applied_at) : null;
                    $statusClass = \Illuminate\Support\Str::slug($application->application_status);
                @endphp
                <article class="application-card">
                    <div class="application-card-logo">
                        @if($application->logo)
                            <img src="{{ asset('assets/uploads/companies/' . $application->logo) }}" alt="" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                            <span class="logo-fallback" style="display:none"><i class="fa-solid fa-building" aria-hidden="true"></i></span>
                        @else
                            <span class="logo-fallback"><i class="fa-solid fa-building" aria-hidden="true"></i></span>
                        @endif
                    </div>
                    <div class="application-card-main">
                        <div class="application-card-top">
                            <a href="{{ route('jobs.show', ['id' => $application->job_id]) }}" class="application-job-title">{{ $application->job_title }}</a>
                            <span class="status-badge status-{{ $statusClass }}">{{ $application->application_status }}</span>
                        </div>
                        <div class="application-company"><i class="fa-solid fa-building" aria-hidden="true"></i>{{ $application->company_name ?: '—' }}</div>
                        <div class="application-meta">
                            <span><i class="fa-solid fa-briefcase" aria-hidden="true"></i>{{ $application->employment_type }}</span>
                            <span><i class="fa-solid fa-location-dot" aria-hidden="true"></i>{{ $application->workplace }}</span>
                            <span><i class="fa-solid fa-calendar-days" aria-hidden="true"></i>Applied {{ $appliedAt ? $appliedAt->format('M j, Y') : '—' }}</span>
                            @if($application->skill_match_score !== null)
                                <span class="meta-score"><i class="fa-solid fa-chart-simple" aria-hidden="true"></i>{{ number_format((float) $application->skill_match_score, 0) }}% match</span>
                            @endif
                        </div>
                        <div class="application-card-actions">
                            <a href="{{ route('jobs.show', ['id' => $application->job_id]) }}" class="btn btn-light btn-sm"><i class="fa-solid fa-eye" aria-hidden="true"></i>View Job</a>
                            @if($canWithdraw)
                                <form action="{{ route('applications.withdraw') }}" method="POST" class="inline-form" data-confirm="Withdraw this application? This cannot be undone.">
                                    @csrf
                                    <input type="hidden" name="application_id" value="{{ $application->application_id }}">
                                    <button type="submit" class="btn btn-outline btn-sm"><i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>Withdraw</button>
                                </form>
                            @elseif($application->application_status === 'Selected')
                                <span class="badge-selected"><i class="fa-solid fa-trophy" aria-hidden="true"></i>Congratulations!</span>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        @if($applications->lastPage() > 1)
            <nav class="applications-pagination" aria-label="Pagination">
                @if($applications->previousPageUrl())
                    <a href="{{ $applications->previousPageUrl() }}" class="pagination-btn"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i> Previous</a>
                @endif
                <div class="pagination-pages">
                    @foreach($applications->getUrlRange($pageStart, $pageEnd) as $page => $url)
                        <a href="{{ $url }}" class="pagination-number {{ $page === $applications->currentPage() ? 'active' : '' }}">{{ $page }}</a>
                    @endforeach
                </div>
                @if($applications->nextPageUrl())
                    <a href="{{ $applications->nextPageUrl() }}" class="pagination-btn">Next <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
                @endif
            </nav>
        @endif
    @endif
</section>
@endsection

@section('js')
<script src="{{ asset('assets/js/jobseeker/applications.js') }}"></script>
@endsection
