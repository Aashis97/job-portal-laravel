@extends('layouts.dashboard')

@section('title', 'Manage Jobs')
@section('role', 'Employer')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/employer/manage_jobs.css') }}">
@endsection

@section('content')
<section class="manage-jobs-page">
    <div class="page-header">
        <div class="page-header-content">
            <span class="page-eyebrow"><i class="fa-solid fa-briefcase" aria-hidden="true"></i>Employer Panel</span>
            <h1>Manage Jobs</h1>
            <p>Create, manage, edit, and monitor your job postings from one place.</p>
        </div>
        <div class="page-header-action"><a href="{{ route('employer.jobs.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus" aria-hidden="true"></i>Post New Job</a></div>
    </div>

    @foreach(['success', 'danger', 'warning', 'info'] as $type)
        @if(session($type))
            <div class="alert alert-{{ $type }}"><i class="fa-solid fa-circle-{{ $type === 'success' ? 'check' : 'exclamation' }}" aria-hidden="true"></i><span>{{ session($type) }}</span></div>
        @endif
    @endforeach

    <div class="job-stat-grid">
        @foreach([
            ['All', '', 'fa-layer-group'],
            ['Open', 'Open', 'fa-circle-check'],
            ['Draft', 'Draft', 'fa-file-pen'],
            ['Closed', 'Closed', 'fa-lock'],
            ['Expired', 'Expired', 'fa-clock-rotate-left'],
        ] as [$label, $filter, $icon])
            <a href="{{ route('employer.jobs', $filter !== '' ? ['status' => $filter] : []) }}" class="job-stat-card {{ $status === $filter ? 'active' : '' }}">
                <div class="job-stat-icon"><i class="fa-solid {{ $icon }}" aria-hidden="true"></i></div>
                <div class="job-stat-content"><span class="job-stat-label">{{ $label === 'All' ? 'All Jobs' : $label }}</span><strong>{{ $statusCounts[$label] }}</strong></div>
            </a>
        @endforeach
    </div>

    <div class="jobs-toolbar">
        <form action="{{ route('employer.jobs') }}" method="GET" class="jobs-search-form">
            @if($status !== '')<input type="hidden" name="status" value="{{ $status }}">@endif
            <div class="search-input-wrapper"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input type="search" name="search" value="{{ $search }}" placeholder="Search by job title, location or category..." maxlength="100"></div>
            <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-search" aria-hidden="true"></i>Search</button>
            @if($search !== '')<a href="{{ route('employer.jobs', $status !== '' ? ['status' => $status] : []) }}" class="clear-search">Clear</a>@endif
        </form>
        @if($status !== '')
            <div class="active-filter"><span>Showing: <strong>{{ $status }}</strong></span><a href="{{ route('employer.jobs') }}" title="Clear status filter"><i class="fa-solid fa-xmark"></i></a></div>
        @endif
    </div>

    <div class="jobs-section">
        <div class="section-heading">
            <div><span class="section-eyebrow">Your Job Postings</span><h2>{{ $statusCounts['All'] }} {{ $statusCounts['All'] === 1 ? 'Job' : 'Jobs' }}</h2></div>
            @if($company)
                @php $companyLogo = $company->logo && $company->logo !== 'company.png' ? route('company.logo', ['filename' => basename($company->logo)]) : asset('assets/images/company.png'); @endphp
                <div class="company-mini">
                    <div class="company-mini-logo"><img src="{{ $companyLogo }}" alt="{{ $company->company_name }}" onerror="this.src='{{ asset('assets/images/company.png') }}'"></div>
                    <div><strong>{{ $company->company_name }}</strong><span>{{ $company->status }}</span></div>
                </div>
            @endif
        </div>

        @if($jobs->isEmpty())
            <div class="empty-jobs">
                <div class="empty-jobs-icon"><i class="fa-solid fa-briefcase" aria-hidden="true"></i></div>
                <h3>No Jobs Found</h3>
                <p>{{ $search !== '' || $status !== '' ? 'No job postings match your current search or filter.' : "You haven't posted any jobs yet. Start by creating your first job posting." }}</p>
                @if($search !== '' || $status !== '')
                    <a href="{{ route('employer.jobs') }}" class="btn btn-secondary"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i>Clear Filters</a>
                @else
                    <a href="{{ route('employer.jobs.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus" aria-hidden="true"></i>Post Your First Job</a>
                @endif
            </div>
        @else
            <div class="jobs-list">
                @foreach($jobs as $job)
                    @php
                        $statusClass = strtolower(str_replace(' ', '-', $job->job_status));
                        $skills = $skillMap->get($job->job_id, collect());
                    @endphp
                    <article class="job-card">
                        <div class="job-card-main">
                            <div class="job-title-row">
                                <div class="job-title-icon"><i class="fa-solid fa-briefcase" aria-hidden="true"></i></div>
                                <div class="job-title-content"><h3>{{ $job->job_title }}</h3><p>{{ $job->category_name }}</p></div>
                                <span class="job-status status-{{ $statusClass }}"><span class="status-dot"></span>{{ $job->job_status }}</span>
                            </div>
                            <div class="job-meta">
                                <span><i class="fa-solid fa-location-dot" aria-hidden="true"></i>{{ $job->location }}</span>
                                <span><i class="fa-solid fa-clock" aria-hidden="true"></i>{{ $job->employment_type }}</span>
                                <span><i class="fa-solid fa-building" aria-hidden="true"></i>{{ $job->workplace }}</span>
                                <span><i class="fa-solid fa-user-tie" aria-hidden="true"></i>{{ $job->experience_level }}</span>
                            </div>
                            @if($skills->isNotEmpty())
                                <div class="job-skills"><span class="skills-label"><i class="fa-solid fa-code" aria-hidden="true"></i>Skills</span><div class="skills-list">@foreach($skills as $skill)<span class="skill-tag">{{ $skill }}</span>@endforeach</div></div>
                            @else
                                <div class="job-no-skills"><i class="fa-solid fa-circle-info" aria-hidden="true"></i>No skills specified</div>
                            @endif
                        </div>
                        <div class="job-card-actions">
                            <a href="{{ route('employer.jobs.edit', ['id' => $job->job_id]) }}" class="job-action edit" title="Edit Job"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i><span>Edit</span></a>
                            <a href="{{ route('employer.applicants', ['job_id' => $job->job_id]) }}" class="job-action applicants" title="View Applicants"><i class="fa-solid fa-users" aria-hidden="true"></i><span>Applicants</span></a>
                            <button type="button" class="job-action delete" data-job-id="{{ $job->job_id }}" data-job-title="{{ $job->job_title }}" title="Delete Job"><i class="fa-solid fa-trash" aria-hidden="true"></i><span>Delete</span></button>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</section>

<div class="delete-modal" id="deleteModal" aria-hidden="true">
    <div class="delete-modal-overlay"></div>
    <div class="delete-modal-content">
        <button type="button" class="delete-modal-close" id="closeDeleteModal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        <div class="delete-modal-icon"><i class="fa-solid fa-trash"></i></div>
        <h3>Delete Job?</h3>
        <p>Are you sure you want to delete <strong id="deleteJobTitle"></strong>? This action cannot be undone.</p>
        <div class="delete-modal-actions">
            <button type="button" class="btn btn-secondary" id="cancelDelete">Cancel</button>
            <form action="{{ route('employer.jobs.delete') }}" method="POST" id="deleteJobForm">
                @csrf
                <input type="hidden" name="job_id" id="deleteJobId">
                <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash" aria-hidden="true"></i>Delete Job</button>
            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('assets/js/employer/manage_jobs.js') }}"></script>
@endsection
