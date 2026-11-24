@extends('layouts.dashboard')

@section('title', 'Applicants')
@section('role', 'Employer')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/employer/applicants.css') }}">
@endsection

@section('content')
<section class="employer-applicants-page">
    <div class="applicants-page-header">
        <div class="applicants-header-content">
            <div class="applicants-header-icon"><i class="fa-solid fa-users" aria-hidden="true"></i></div>
            <div><span class="applicants-page-label">Employer Panel</span><h1>Applicants</h1><p>Review candidates who applied for <strong>{{ $job->job_title }}</strong></p></div>
        </div>
        <a href="{{ route('employer.jobs') }}" class="applicants-back-btn"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Back to Jobs</a>
    </div>

    @if(session('success'))
        <div class="applicants-alert applicants-alert-success"><i class="fa-solid fa-circle-check" aria-hidden="true"></i><span>{{ session('success') }}</span></div>
    @endif
    @if(session('error') || session('danger'))
        <div class="applicants-alert applicants-alert-error"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><span>{{ session('error') ?? session('danger') }}</span></div>
    @endif

    <div class="applicants-job-card">
        <div class="job-card-icon"><i class="fa-solid fa-briefcase" aria-hidden="true"></i></div>
        <div class="job-card-content">
            <span class="job-card-label">Job Position</span>
            <h2>{{ $job->job_title }}</h2>
            <div class="job-card-meta">
                <span><i class="fa-solid fa-layer-group" aria-hidden="true"></i>{{ $job->category_name }}</span>
                @if($job->location)<span><i class="fa-solid fa-location-dot" aria-hidden="true"></i>{{ $job->location }}</span>@endif
                <span><i class="fa-solid fa-briefcase" aria-hidden="true"></i>{{ $job->employment_type }}</span>
            </div>
        </div>
        <div class="job-card-count"><strong>{{ $applicants->count() }}</strong><span>Applicants</span></div>
    </div>

    <div class="applicants-card">
        <div class="applicants-card-header">
            <div><span class="section-eyebrow">Applications</span><h2>Candidate Applications</h2><p>Review applicants and manage their application status.</p></div>
        </div>

        @if($applicants->isEmpty())
            <div class="applicants-empty">
                <div class="empty-icon"><i class="fa-solid fa-user-slash" aria-hidden="true"></i></div>
                <h3>No Applicants Yet</h3>
                <p>No candidates have applied for this job yet.</p>
            </div>
        @else
            <div class="applicants-table-wrapper">
                <table class="applicants-table">
                    <thead><tr><th>Candidate</th><th>Contact</th><th>Match Score</th><th>Status</th><th>Applied</th><th>Action</th></tr></thead>
                    <tbody>
                        @foreach($applicants as $applicant)
                            @php
                                $statusClass = match ($applicant->application_status) {
                                    'Applied' => 'applied',
                                    'Under Review' => 'review',
                                    'Shortlisted' => 'shortlisted',
                                    'Interview Scheduled' => 'interview',
                                    'Selected' => 'selected',
                                    'Rejected' => 'rejected',
                                    'Withdrawn' => 'withdrawn',
                                    default => 'applied',
                                };
                            @endphp
                            <tr>
                                <td>
                                    <div class="candidate-info">
                                        <div class="candidate-avatar">
                                            @if($applicant->profile_image)
                                                <img src="{{ route('employer.applicant-image', ['applicationId' => $applicant->application_id]) }}" alt="{{ $applicant->full_name }}">
                                            @else
                                                <i class="fa-solid fa-user" aria-hidden="true"></i>
                                            @endif
                                        </div>
                                        <div class="candidate-details"><strong>{{ $applicant->full_name ?: 'Candidate' }}</strong>@if($applicant->availability)<small>{{ $applicant->availability }}</small>@endif</div>
                                    </div>
                                </td>
                                <td><div class="candidate-contact"><span><i class="fa-solid fa-envelope" aria-hidden="true"></i>{{ $applicant->email }}</span>@if($applicant->phone)<span><i class="fa-solid fa-phone" aria-hidden="true"></i>{{ $applicant->phone }}</span>@endif</div></td>
                                <td>
                                    @if($applicant->skill_match_score !== null)
                                        <div class="match-score"><strong>{{ number_format((float) $applicant->skill_match_score, 1) }}%</strong></div>
                                    @else
                                        <span class="not-available">N/A</span>
                                    @endif
                                </td>
                                <td><span class="application-status status-{{ $statusClass }}">{{ $applicant->application_status ?: 'Applied' }}</span></td>
                                <td>{{ $applicant->applied_at ? \Illuminate\Support\Carbon::parse($applicant->applied_at)->format('M d, Y') : 'N/A' }}</td>
                                <td><a href="{{ route('employer.applicant-details', ['application_id' => $applicant->application_id]) }}" class="view-applicant-btn"><i class="fa-solid fa-eye" aria-hidden="true"></i>View</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>
@endsection
