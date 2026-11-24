@extends('layouts.dashboard')
@section('title', 'Applications')
@section('role', 'Administrator')
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/admin/applications.css') }}">@endsection
@section('js')<script src="{{ asset('assets/js/admin/applications.js') }}"></script>@endsection
@section('sidebar')
<div class="sidebar-section"><div class="sidebar-section-title">MAIN</div><a href="{{ route('admin.dashboard') }}" class="sidebar-link"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a></div>
<div class="sidebar-section"><div class="sidebar-section-title">MANAGEMENT</div><a href="{{ route('admin.users') }}" class="sidebar-link"><i class="fa-solid fa-users"></i><span>Users</span></a><a href="{{ route('admin.companies') }}" class="sidebar-link"><i class="fa-solid fa-building"></i><span>Companies</span></a><a href="{{ route('admin.jobs') }}" class="sidebar-link"><i class="fa-solid fa-briefcase"></i><span>Jobs</span></a><a href="{{ route('admin.applications') }}" class="sidebar-link active"><i class="fa-solid fa-file-lines"></i><span>Applications</span></a></div>
@endsection
@section('content')
<div class="admin-applications-page">
    <div class="admin-page-header"><div><span class="admin-page-label">JOB MANAGEMENT</span><h1>Applications</h1><p>Monitor every application submitted across the platform.</p></div><div class="admin-page-date"><i class="fa-solid fa-file-lines" aria-hidden="true"></i>{{ number_format($applications->total()) }} shown</div></div>
    @foreach(['success' => 'admin-alert--success', 'danger' => 'admin-alert--error', 'info' => 'admin-alert--info'] as $key => $class)@if(session($key))<div class="admin-alert {{ $class }}" role="status">{{ session($key) }}</div>@endif @endforeach
    <div class="applications-kpi-grid">
        @foreach([['kpi-blue', 'fa-inbox', 'total', 'Total'], ['kpi-purple', 'fa-star', 'shortlisted', 'Shortlisted'], ['kpi-cyan', 'fa-calendar-check', 'interview', 'Interviewing'], ['kpi-green', 'fa-trophy', 'selected', 'Selected'], ['kpi-orange', 'fa-calendar-day', 'last_7_days', 'Last 7 days']] as [$style, $icon, $key, $label])
            <div class="kpi-card"><div class="kpi-icon {{ $style }}"><i class="fa-solid {{ $icon }}" aria-hidden="true"></i></div><div><strong>{{ number_format((int) $stats->{$key}) }}</strong><span>{{ $label }}</span></div></div>
        @endforeach
        <div class="kpi-card"><div class="kpi-icon kpi-emerald"><i class="fa-solid fa-chart-simple" aria-hidden="true"></i></div><div><strong>{{ number_format((float) $stats->avg_match, 1) }}%</strong><span>Avg match</span></div></div>
    </div>
    <div class="admin-tabs">
        @foreach(['' => ['All', 'total'], 'Applied' => ['Applied', 'applied'], 'Under Review' => ['Under Review', 'under_review'], 'Shortlisted' => ['Shortlisted', 'shortlisted'], 'Interview Scheduled' => ['Interview', 'interview'], 'Selected' => ['Selected', 'selected'], 'Rejected' => ['Rejected', 'rejected'], 'Withdrawn' => ['Withdrawn', 'withdrawn']] as $value => [$label, $key])
            <a href="{{ route('admin.applications', array_filter(['status' => $value ?: null, 'job_id' => $jobId ?: null, 'company_id' => $companyId ?: null, 'min_score' => $minScore ?: null, 'q' => $search ?: null])) }}" class="admin-tab {{ $status === $value ? 'active' : '' }}">{{ $label }} <span class="tab-count">{{ number_format((int) $stats->{$key}) }}</span></a>
        @endforeach
    </div>
    <form action="{{ route('admin.applications') }}" method="GET" class="admin-filter-form applications-filter">
        @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
        @if($jobId)<input type="hidden" name="job_id" value="{{ $jobId }}">@endif
        @if($companyId)<input type="hidden" name="company_id" value="{{ $companyId }}">@endif
        <div class="filter-field filter-search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input type="search" name="q" value="{{ $search }}" placeholder="Search applicant, email, job or company..." maxlength="100" autocomplete="off"></div>
        <div class="filter-field"><select name="min_score"><option value="0">Any match</option>@foreach([60, 70, 80, 90] as $score)<option value="{{ $score }}" @selected((float) $minScore === (float) $score)>{{ $score }}% and above</option>@endforeach</select></div>
        <div class="filter-actions"><button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter" aria-hidden="true"></i> Apply</button>@if($search || $minScore || $jobId || $companyId)<a href="{{ route('admin.applications', $status ? ['status' => $status] : []) }}" class="btn btn-light">Clear</a>@endif</div>
    </form>
    <section class="admin-panel">
        <div class="admin-panel-header"><div><h2>Results</h2><p>{{ number_format($applications->total()) }} {{ $applications->total() === 1 ? 'application' : 'applications' }}</p></div></div>
        <div class="admin-table-wrapper"><table class="admin-table applications-table-full"><thead><tr><th>Applicant</th><th>Job</th><th>Company</th><th>Match</th><th>Status</th><th>Applied</th><th>Action</th></tr></thead><tbody>
            @forelse($applications as $application)
                @php($score = $application->skill_match_score)
                @php($scoreClass = $score === null ? '' : ($score >= 90 ? 'score-excellent' : ($score >= 80 ? 'score-strong' : ($score >= 70 ? 'score-good' : ($score >= 60 ? 'score-moderate' : 'score-low')))))
                @php($statusClass = 'status-'.(strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $application->application_status) ?? '')) ?: 'unknown'))
                <tr><td><div class="table-user"><div class="table-avatar">{{ mb_strtoupper(mb_substr(trim((string) $application->applicant_name) ?: 'U', 0, 1)) }}</div><div><a href="{{ route('admin.user-details', ['id' => $application->applicant_user_id]) }}" class="table-user-link"><strong>{{ $application->applicant_name }}</strong></a><small class="table-subtext">{{ $application->applicant_email }}</small></div></div></td><td><strong class="job-cell-title">{{ $application->job_title }}</strong><small class="table-subtext">{{ $application->employment_type }} &middot; {{ $application->workplace }}</small></td><td>@if($application->company_id)<a href="{{ route('admin.company-details', ['id' => $application->company_id]) }}" class="table-user-link">{{ $application->company_name }}</a>@else<span class="muted-value">—</span>@endif</td><td>@if($score !== null)<span class="match-score-pill {{ $scoreClass }}">{{ number_format((float) $score, 1) }}%</span>@else<span class="muted-value">Not scored</span>@endif</td><td><span class="status-badge {{ $statusClass }}">{{ $application->application_status }}</span></td><td>{{ $application->applied_at ? \Illuminate\Support\Carbon::parse($application->applied_at)->diffForHumans() : '—' }}</td><td><a href="{{ route('admin.application-details', ['id' => $application->application_id]) }}" class="btn-sm btn-outline"><i class="fa-solid fa-eye" aria-hidden="true"></i> View</a></td></tr>
            @empty
                <tr><td colspan="7" class="empty-table">No applications match your filters.</td></tr>
            @endforelse
        </tbody></table></div>
        @if($applications->lastPage() > 1)<nav class="admin-pagination" aria-label="Pagination">@if($applications->previousPageUrl())<a href="{{ $applications->previousPageUrl() }}" class="pagination-btn">Previous</a>@endif<div class="pagination-pages">@foreach($applications->getUrlRange(max(1, $applications->currentPage() - 2), min($applications->lastPage(), $applications->currentPage() + 2)) as $page => $url)<a href="{{ $url }}" class="pagination-number {{ $page === $applications->currentPage() ? 'active' : '' }}">{{ $page }}</a>@endforeach</div>@if($applications->nextPageUrl())<a href="{{ $applications->nextPageUrl() }}" class="pagination-btn">Next</a>@endif</nav>@endif
    </section>
</div>
@endsection
