@extends('layouts.dashboard')

@section('title', 'Saved Jobs')
@section('role', 'Job Seeker')
@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/jobseeker/saved_jobs.css') }}">
@endsection

@section('content')
@php
    $pageStart = max(1, $savedJobs->currentPage() - 2);
    $pageEnd = min($savedJobs->lastPage(), $savedJobs->currentPage() + 2);
@endphp
<section class="jobseeker-saved-page">
    <div class="page-header">
        <div>
            <span class="page-tag">Bookmarked</span>
            <h1>Saved Jobs</h1>
            <p>Jobs you have bookmarked for later.</p>
        </div>
        <div class="results-count">
            <strong>{{ number_format($savedJobs->total()) }}</strong>
            <span>saved</span>
        </div>
    </div>

    @foreach(['success', 'danger', 'warning'] as $type)
        @if(session($type))
            <div class="alert alert-{{ $type }}" role="{{ $type === 'danger' ? 'alert' : 'status' }}">{{ session($type) }}</div>
        @endif
    @endforeach

    @if($savedJobs->total() > 0)
        <form action="{{ route('saved_jobs.index') }}" method="GET" class="saved-search-form">
            <div class="search-wrapper">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input type="search" name="q" value="{{ $queryText }}" placeholder="Search saved jobs by title or company..." maxlength="100" autocomplete="off">
            </div>
            @if($queryText !== '')
                <a href="{{ route('saved_jobs.index') }}" class="btn btn-light">Clear</a>
            @endif
        </form>
    @endif

    @if($savedJobs->isEmpty())
        <div class="saved-empty">
            <div class="empty-icon"><i class="fa-regular fa-bookmark" aria-hidden="true"></i></div>
            <h3>{{ $queryText !== '' ? 'No saved jobs match your search' : 'No saved jobs yet' }}</h3>
            <p>{{ $queryText !== '' ? 'Try a different keyword or clear the search.' : 'Browse open jobs and click Save to bookmark them for later.' }}</p>
            @if($queryText !== '')
                <a href="{{ route('saved_jobs.index') }}" class="btn btn-light">Clear Search</a>
            @else
                <a href="{{ route('jobs.index') }}" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>Browse Jobs</a>
            @endif
        </div>
    @else
        <div class="saved-grid">
            @foreach($savedJobs as $job)
                @php
                    $isApplied = (int) $job->is_applied === 1;
                    $isOpen = $job->job_status === 'Open';
                    $salaryText = '—';
                    if ($job->minimum_salary !== null || $job->maximum_salary !== null) {
                        $salaryText = ($job->currency ?: 'USD') . ' '
                            . ($job->minimum_salary !== null ? number_format((float) $job->minimum_salary, 0) : '—')
                            . ' – '
                            . ($job->maximum_salary !== null ? number_format((float) $job->maximum_salary, 0) : '—');
                    }
                @endphp
                <article class="saved-job-card">
                    <div class="saved-job-top">
                        <div class="saved-job-logo">
                            @if($job->logo)
                                <img src="{{ asset('assets/uploads/companies/' . $job->logo) }}" alt="" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                <span class="logo-fallback" style="display:none"><i class="fa-solid fa-building" aria-hidden="true"></i></span>
                            @else
                                <span class="logo-fallback"><i class="fa-solid fa-building" aria-hidden="true"></i></span>
                            @endif
                        </div>
                        <div class="saved-job-header">
                            <a href="{{ route('jobs.show', ['id' => $job->job_id]) }}" class="saved-job-title">{{ $job->job_title }}</a>
                            <span class="saved-job-company">{{ $job->company_name ?: '—' }}</span>
                        </div>
                        @if(!$isOpen)
                            <span class="closed-flag">Closed</span>
                        @elseif($isApplied)
                            <span class="applied-flag"><i class="fa-solid fa-check" aria-hidden="true"></i>Applied</span>
                        @endif
                    </div>

                    <div class="saved-job-meta">
                        <span><i class="fa-solid fa-briefcase" aria-hidden="true"></i>{{ $job->employment_type }}</span>
                        <span><i class="fa-solid fa-location-dot" aria-hidden="true"></i>{{ $job->workplace }}</span>
                        <span><i class="fa-solid fa-layer-group" aria-hidden="true"></i>{{ $job->experience_level }}</span>
                    </div>
                    @if($job->location)
                        <div class="saved-job-location"><i class="fa-solid fa-map-pin" aria-hidden="true"></i>{{ $job->location }}</div>
                    @endif
                    <div class="saved-job-salary"><i class="fa-solid fa-money-bill-wave" aria-hidden="true"></i>{{ $salaryText }}</div>

                    <div class="saved-job-footer">
                        <a href="{{ route('jobs.show', ['id' => $job->job_id]) }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-eye" aria-hidden="true"></i>View</a>
                        <form action="{{ route('jobs.unsave') }}" method="POST" class="inline-form" data-confirm="Remove this job from your saved list?">
                            @csrf
                            <input type="hidden" name="job_id" value="{{ $job->job_id }}">
                            <input type="hidden" name="return_to" value="saved">
                            <button type="submit" class="btn btn-outline btn-sm"><i class="fa-solid fa-bookmark" aria-hidden="true"></i>Remove</button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>

        @if($savedJobs->lastPage() > 1)
            <nav class="saved-pagination" aria-label="Pagination">
                @if($savedJobs->previousPageUrl())
                    <a href="{{ $savedJobs->previousPageUrl() }}" class="pagination-btn"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i> Previous</a>
                @endif
                <div class="pagination-pages">
                    @foreach($savedJobs->getUrlRange($pageStart, $pageEnd) as $page => $url)
                        <a href="{{ $url }}" class="pagination-number {{ $page === $savedJobs->currentPage() ? 'active' : '' }}">{{ $page }}</a>
                    @endforeach
                </div>
                @if($savedJobs->nextPageUrl())
                    <a href="{{ $savedJobs->nextPageUrl() }}" class="pagination-btn">Next <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
                @endif
            </nav>
        @endif
    @endif
</section>
@endsection

@section('js')
<script src="{{ asset('assets/js/jobseeker/saved_jobs.js') }}"></script>
@endsection
