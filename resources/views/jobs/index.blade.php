@extends('layouts.dashboard')

@section('title', 'Find Jobs')
@section('role', 'Job Seeker')
@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/jobseeker/jobs.css') }}">
@endsection

@section('content')
<section class="jobseeker-jobs-page">
    <div class="page-header">
        <div><span class="page-tag">Jobs</span><h1>Find your next opportunity</h1><p>Browse open positions and apply with one click.</p></div>
        <div class="results-count"><strong>{{ number_format($jobs->total()) }}</strong><span>open jobs</span></div>
    </div>

    @if(session('danger'))<div class="alert alert-danger" role="alert">{{ session('danger') }}</div>@endif
    @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif

    <form action="{{ route('jobs.index') }}" method="GET" class="jobs-filter-form">
        <div class="filter-search"><i class="fa-solid fa-magnifying-glass"></i><input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Search by job title, company or location..." maxlength="100"></div>
        <div class="filter-row">
            <select name="category_id"><option value="0">All Categories</option>@foreach($categories as $category)<option value="{{ $category->category_id }}" @selected((int) $filters['category_id'] === (int) $category->category_id)>{{ $category->category_name }}</option>@endforeach</select>
            <select name="employment_type"><option value="">Employment Type</option>@foreach($employmentTypes as $type)<option value="{{ $type }}" @selected($filters['employment_type'] === $type)>{{ $type }}</option>@endforeach</select>
            <select name="workplace"><option value="">Workplace</option>@foreach($workplaces as $workplace)<option value="{{ $workplace }}" @selected($filters['workplace'] === $workplace)>{{ $workplace }}</option>@endforeach</select>
            <select name="experience_level"><option value="">Experience Level</option>@foreach($experienceLevels as $level)<option value="{{ $level }}" @selected($filters['experience_level'] === $level)>{{ $level }}</option>@endforeach</select>
            <input type="number" name="min_salary" value="{{ $filters['min_salary'] }}" placeholder="Min salary" min="0" step="1">
        </div>
        <div class="filter-actions"><button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i>Apply Filters</button>@if(collect($filters)->contains(fn ($value) => $value !== '' && $value !== 0 && $value !== '0'))<a href="{{ route('jobs.index') }}" class="btn btn-light"><i class="fa-solid fa-rotate-left"></i>Clear</a>@endif</div>
    </form>

    @if($jobs->isEmpty())
        <div class="jobs-empty"><div class="empty-icon"><i class="fa-solid fa-briefcase"></i></div><h3>No jobs found</h3><p>Try adjusting your filters or searching with a different keyword.</p></div>
    @else
        <div class="jobs-grid">
            @foreach($jobs as $job)
                @php
                    $salary = '—';
                    if ($job->minimum_salary !== null || $job->maximum_salary !== null) {
                        $salary = ($job->currency ?: 'USD') . ' '
                            . ($job->minimum_salary !== null ? number_format((float) $job->minimum_salary, 0) : '—')
                            . ' – '
                            . ($job->maximum_salary !== null ? number_format((float) $job->maximum_salary, 0) : '—');
                    }
                    $isApplied = $job->application_id !== null;
                    $isSaved = $job->saved_job_id !== null;
                @endphp
                <article class="job-card">
                    <div class="job-card-top">
                        <div class="company-logo">
                            @if($job->logo)<img src="{{ asset('assets/uploads/companies/' . $job->logo) }}" alt="" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"><span class="logo-fallback" style="display:none"><i class="fa-solid fa-building"></i></span>@else<span class="logo-fallback"><i class="fa-solid fa-building"></i></span>@endif
                        </div>
                        <div class="job-card-header-info"><a href="{{ route('jobs.show', ['id' => $job->job_id]) }}" class="job-title">{{ $job->job_title }}</a><span class="job-company">{{ $job->company_name }}</span></div>
                        @if($isApplied)<span class="badge-applied"><i class="fa-solid fa-check"></i>Applied</span>@elseif($isSaved)<span class="badge-saved"><i class="fa-solid fa-bookmark"></i>Saved</span>@endif
                    </div>
                    <div class="job-card-meta"><span><i class="fa-solid fa-briefcase"></i>{{ $job->employment_type }}</span><span><i class="fa-solid fa-location-dot"></i>{{ $job->workplace }}</span><span><i class="fa-solid fa-layer-group"></i>{{ $job->experience_level }}</span></div>
                    @if($job->category_name || $job->location)<div class="job-card-sub">@if($job->category_name)<span><i class="fa-solid fa-tag"></i>{{ $job->category_name }}</span>@endif @if($job->location)<span><i class="fa-solid fa-map-pin"></i>{{ $job->location }}</span>@endif</div>@endif
                    <div class="job-card-salary"><i class="fa-solid fa-money-bill-wave"></i>{{ $salary }}</div>
                    <div class="job-card-footer"><a href="{{ route('jobs.show', ['id' => $job->job_id]) }}" class="btn btn-primary btn-sm">View Details</a>@if($isApplied)<button type="button" class="btn btn-light btn-sm" disabled><i class="fa-solid fa-check"></i>Applied</button>@elseif($isSaved)<form action="{{ route('jobs.unsave') }}" method="POST" class="inline-action">@csrf<input type="hidden" name="job_id" value="{{ $job->job_id }}"><input type="hidden" name="return_to" value="jobs"><button type="submit" class="btn btn-light btn-sm"><i class="fa-solid fa-bookmark"></i>Saved</button></form>@else<form action="{{ route('jobs.save') }}" method="POST" class="inline-action">@csrf<input type="hidden" name="job_id" value="{{ $job->job_id }}"><input type="hidden" name="return_to" value="jobs"><button type="submit" class="btn btn-outline btn-sm"><i class="fa-regular fa-bookmark"></i>Save</button></form>@endif</div>
                </article>
            @endforeach
        </div>
        @if($jobs->hasPages())
            <nav class="jobs-pagination" aria-label="Pagination">
                @if($jobs->previousPageUrl())<a href="{{ $jobs->previousPageUrl() }}" class="pagination-btn"><i class="fa-solid fa-chevron-left"></i> Previous</a>@endif
                <div class="pagination-pages">@foreach($jobs->getUrlRange(max(1, $jobs->currentPage() - 2), min($jobs->lastPage(), $jobs->currentPage() + 2)) as $page => $url)<a href="{{ $url }}" class="pagination-number {{ $page === $jobs->currentPage() ? 'active' : '' }}">{{ $page }}</a>@endforeach</div>
                @if($jobs->nextPageUrl())<a href="{{ $jobs->nextPageUrl() }}" class="pagination-btn">Next <i class="fa-solid fa-chevron-right"></i></a>@endif
            </nav>
        @endif
    @endif
</section>
@endsection
