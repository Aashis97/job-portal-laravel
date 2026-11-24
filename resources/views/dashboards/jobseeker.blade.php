@extends('layouts.dashboard')

@section('title', 'Job Seeker Dashboard')
@section('role', 'Job Seeker')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/jobseeker/dashboard.css') }}">
@endsection

@section('sidebar')
<div class="sidebar-section">
    <div class="sidebar-section-title">MAIN</div>
    <a href="{{ route('jobseeker.dashboard') }}" class="sidebar-link active"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a>
</div>
<div class="sidebar-section">
    <div class="sidebar-section-title">CAREER</div>
    <a href="{{ route('jobs.index') }}" class="sidebar-link"><i class="fa-solid fa-magnifying-glass"></i><span>Find Jobs</span></a>
    <a href="{{ route('applications.index') }}" class="sidebar-link"><i class="fa-solid fa-file-circle-check"></i><span>Applications</span></a>
    <a href="{{ route('saved_jobs.index') }}" class="sidebar-link"><i class="fa-solid fa-bookmark"></i><span>Saved Jobs</span></a>
    <a href="{{ route('jobseeker.recommendations') }}" class="sidebar-link"><i class="fa-solid fa-wand-magic-sparkles"></i><span>Recommended Jobs</span></a>
    <a href="{{ route('jobseeker.notifications') }}" class="sidebar-link"><i class="fa-solid fa-bell"></i><span>Notifications</span></a>
    <a href="{{ route('jobseeker.messages') }}" class="sidebar-link"><i class="fa-solid fa-message"></i><span>Messages</span></a>
    <a href="{{ route('jobseeker.profile') }}" class="sidebar-link"><i class="fa-solid fa-user"></i><span>My Profile</span></a>
    <a href="{{ route('jobseeker.resume') }}" class="sidebar-link"><i class="fa-solid fa-file-lines"></i><span>My Resume</span></a>
</div>
<div class="sidebar-section">
    <div class="sidebar-section-title">ACCOUNT</div>
    <a href="{{ route('jobseeker.settings') }}" class="sidebar-link"><i class="fa-solid fa-gear"></i><span>Settings</span></a>
    <a href="{{ route('jobseeker.account') }}" class="sidebar-link"><i class="fa-solid fa-user-shield"></i><span>Account</span></a>
</div>
@endsection

@section('content')
<section class="jobseeker-dashboard">
    <div class="dashboard-welcome">
        <div class="welcome-content">
            <span class="welcome-label">Job Seeker Dashboard</span>
            <h1>Welcome back, {{ $user->full_name }}!</h1>
            <p>Manage your profile, discover new opportunities, and track your job applications.</p>
        </div>
        <div class="welcome-actions">
            <a href="{{ route('jobs.index') }}" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>Find Jobs</a>
            <a href="{{ route('jobseeker.profile') }}" class="btn btn-outline"><i class="fa-solid fa-user-pen" aria-hidden="true"></i>Update Profile</a>
        </div>
    </div>

    <div class="dashboard-stats">
        <div class="stat-card"><div class="stat-icon applications-icon"><i class="fa-solid fa-file-circle-check" aria-hidden="true"></i></div><div class="stat-content"><span class="stat-label">Applications</span><strong class="stat-value">{{ number_format($stats['total_applications']) }}</strong><span class="stat-description">Total submitted</span></div></div>
        <div class="stat-card"><div class="stat-icon saved-icon"><i class="fa-solid fa-bookmark" aria-hidden="true"></i></div><div class="stat-content"><span class="stat-label">Saved Jobs</span><strong class="stat-value">{{ number_format($stats['saved_jobs']) }}</strong><span class="stat-description">Bookmarked</span></div></div>
        <div class="stat-card"><div class="stat-icon shortlisted-icon"><i class="fa-solid fa-star" aria-hidden="true"></i></div><div class="stat-content"><span class="stat-label">Shortlisted</span><strong class="stat-value">{{ number_format($stats['shortlisted']) }}</strong><span class="stat-description">From applications</span></div></div>
        <div class="stat-card"><div class="stat-icon interview-icon"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i></div><div class="stat-content"><span class="stat-label">Interviews</span><strong class="stat-value">{{ number_format($stats['interviews']) }}</strong><span class="stat-description">Scheduled</span></div></div>
    </div>

    <div class="dashboard-grid">
        <div class="dashboard-card profile-completion-card">
            <div class="card-header">
                <div><span class="card-subtitle">Profile</span><h2>Profile Completion</h2></div>
                <div class="card-header-icon"><i class="fa-solid fa-user-check" aria-hidden="true"></i></div>
            </div>
            <div class="profile-completion">
                <div class="completion-header"><strong>{{ $profileCompletion }}%</strong><span>Complete</span></div>
                <div class="progress-bar" role="progressbar" aria-valuenow="{{ $profileCompletion }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-fill" style="width: {{ $profileCompletion }}%;"></div></div>
                @if($profileCompletion < 100)
                    <p class="completion-message">Complete your profile to improve your chances of being discovered by employers.</p>
                    <a href="{{ route('jobseeker.profile') }}" class="card-action">Complete Profile<i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                @else
                    <p class="completion-message success">Your profile is complete.</p>
                    <a href="{{ route('jobseeker.profile') }}" class="card-action">View Profile<i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                @endif
            </div>
        </div>

        <div class="dashboard-card resume-card">
            <div class="card-header">
                <div><span class="card-subtitle">Resume</span><h2>Your Resume</h2></div>
                <div class="card-header-icon"><i class="fa-solid fa-file-lines" aria-hidden="true"></i></div>
            </div>
            @if($resumeFile)
                <div class="resume-status uploaded">
                    <div class="resume-icon"><i class="fa-solid fa-file-circle-check" aria-hidden="true"></i></div>
                    <div>
                        <strong>Resume uploaded</strong>
                        <span>{{ ($resume->resume_title ?? null) ?: $resumeFile }}</span>
                        @if($resume && $resume->updated_at)
                            <small>Updated {{ \Illuminate\Support\Carbon::parse($resume->updated_at)->diffForHumans() }}</small>
                        @endif
                    </div>
                </div>
                <a href="{{ route('jobseeker.resume') }}" class="card-action">Manage Resume<i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            @else
                <div class="resume-status empty">
                    <div class="resume-icon"><i class="fa-solid fa-file-arrow-up" aria-hidden="true"></i></div>
                    <div><strong>No resume uploaded</strong><span>Upload your resume to apply for jobs.</span></div>
                </div>
                <a href="{{ route('jobseeker.resume') }}" class="card-action">Upload Resume<i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            @endif
        </div>
    </div>

    <div class="dashboard-grid dashboard-grid-large">
        <div class="dashboard-card applications-card">
            <div class="card-header">
                <div><span class="card-subtitle">Activity</span><h2>Recent Applications</h2></div>
                <a href="{{ route('applications.index') }}" class="view-all-link">View All<i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </div>
            @if($recentApplications->isNotEmpty())
                <div class="applications-list">
                    @foreach($recentApplications as $application)
                        @php
                            $statusSlug = \Illuminate\Support\Str::slug((string) $application->application_status);
                            $hasScore = $application->skill_match_score !== null && $application->skill_match_score !== '';
                        @endphp
                        <div class="application-item">
                            <div class="application-company-icon"><i class="fa-solid fa-building" aria-hidden="true"></i></div>
                            <div class="application-info">
                                <a href="{{ route('jobs.show', ['id' => $application->job_id]) }}" class="application-job-title">{{ $application->job_title }}</a>
                                <span class="application-company">{{ $application->company_name ?: '—' }}</span>
                                <span class="application-meta">{{ $application->employment_type }} <span>•</span> {{ $application->workplace }}</span>
                            </div>
                            <div class="application-right">
                                <span class="status-badge {{ $statusSlug !== '' ? 'status-' . $statusSlug : 'status-default' }}">{{ $application->application_status }}</span>
                                @if($hasScore)<span class="match-score">{{ number_format((float) $application->skill_match_score, 0) }}% match</span>@endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state">
                    <div class="empty-icon"><i class="fa-solid fa-file-circle-question" aria-hidden="true"></i></div>
                    <h3>No applications yet</h3>
                    <p>Start exploring jobs and submit your first application.</p>
                    <a href="{{ route('jobs.index') }}" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>Find Jobs</a>
                </div>
            @endif
        </div>

        <div class="dashboard-card quick-actions-card">
            <div class="card-header"><div><span class="card-subtitle">Shortcuts</span><h2>Quick Actions</h2></div><div class="card-header-icon"><i class="fa-solid fa-bolt" aria-hidden="true"></i></div></div>
            <div class="quick-actions">
                <a href="{{ route('jobseeker.profile') }}" class="quick-action"><span class="quick-action-icon"><i class="fa-solid fa-user-pen" aria-hidden="true"></i></span><span class="quick-action-content"><strong>Update Profile</strong><small>Manage your professional information</small></span><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
                <a href="{{ route('jobseeker.resume') }}" class="quick-action"><span class="quick-action-icon"><i class="fa-solid fa-file-arrow-up" aria-hidden="true"></i></span><span class="quick-action-content"><strong>{{ $resumeFile ? 'Manage Resume' : 'Upload Resume' }}</strong><small>{{ $resumeFile ? 'View or replace your resume' : 'Upload your latest resume' }}</small></span><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
                <a href="{{ route('jobs.index') }}" class="quick-action"><span class="quick-action-icon"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></span><span class="quick-action-content"><strong>Find Jobs</strong><small>Search available opportunities</small></span><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
                <a href="{{ route('applications.index') }}" class="quick-action"><span class="quick-action-icon"><i class="fa-solid fa-list-check" aria-hidden="true"></i></span><span class="quick-action-content"><strong>My Applications</strong><small>Track your applications</small></span><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
                <a href="{{ route('saved_jobs.index') }}" class="quick-action"><span class="quick-action-icon"><i class="fa-solid fa-bookmark" aria-hidden="true"></i></span><span class="quick-action-content"><strong>Saved Jobs</strong><small>View your saved opportunities</small></span><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
            </div>
        </div>
    </div>

    <div class="dashboard-card jobs-card">
        <div class="card-header">
            <div><span class="card-subtitle">Opportunities</span><h2>Latest Jobs</h2></div>
            <a href="{{ route('jobs.index') }}" class="view-all-link">Browse All Jobs<i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </div>
        @if($recentJobs->isNotEmpty())
            <div class="job-list">
                @foreach($recentJobs as $job)
                    <div class="job-item">
                        <div class="job-company-icon"><i class="fa-solid fa-building" aria-hidden="true"></i></div>
                        <div class="job-info">
                            <a href="{{ route('jobs.show', ['id' => $job->job_id]) }}" class="job-title">{{ $job->job_title }}</a>
                            <span class="job-company">{{ $job->company_name ?: '—' }}</span>
                            <div class="job-meta">
                                <span><i class="fa-solid fa-location-dot" aria-hidden="true"></i>{{ $job->workplace }}</span>
                                <span><i class="fa-solid fa-briefcase" aria-hidden="true"></i>{{ $job->employment_type }}</span>
                                <span><i class="fa-solid fa-layer-group" aria-hidden="true"></i>{{ $job->experience_level }}</span>
                            </div>
                        </div>
                        <div class="job-action"><a href="{{ route('jobs.show', ['id' => $job->job_id]) }}" class="btn btn-small btn-outline">View Job</a></div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <div class="empty-icon"><i class="fa-solid fa-briefcase" aria-hidden="true"></i></div>
                <h3>No open jobs available</h3>
                <p>There are currently no open jobs to display.</p>
            </div>
        @endif
    </div>
</section>
@endsection
