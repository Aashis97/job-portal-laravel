@extends('layouts.dashboard')
@section('title', 'Jobs')
@section('role', 'Administrator')
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/admin/jobs.css') }}">@endsection
@section('js')<script src="{{ asset('assets/js/admin/jobs.js') }}"></script>@endsection
@section('sidebar')
<div class="sidebar-section"><div class="sidebar-section-title">MAIN</div><a href="{{ route('admin.dashboard') }}" class="sidebar-link"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a></div>
<div class="sidebar-section"><div class="sidebar-section-title">MANAGEMENT</div><a href="{{ route('admin.users') }}" class="sidebar-link"><i class="fa-solid fa-users"></i><span>Users</span></a><a href="{{ route('admin.companies') }}" class="sidebar-link"><i class="fa-solid fa-building"></i><span>Companies</span></a><a href="{{ route('admin.jobs') }}" class="sidebar-link active"><i class="fa-solid fa-briefcase"></i><span>Jobs</span></a></div>
@endsection
@section('content')
<div class="admin-jobs-page">
    <div class="admin-page-header"><div><span class="admin-page-label">JOB MANAGEMENT</span><h1>Jobs</h1><p>Moderate, monitor, and manage all job postings on the platform.</p></div><div class="admin-page-date"><i class="fa-solid fa-briefcase" aria-hidden="true"></i>{{ number_format($jobs->total()) }} shown</div></div>
    @foreach(['success' => 'admin-alert--success', 'danger' => 'admin-alert--error', 'info' => 'admin-alert--info'] as $key => $class)
        @if(session($key))<div class="admin-alert {{ $class }}" role="status">{{ session($key) }}</div>@endif
    @endforeach
    @if((int) $stats->overdue_open > 0 && !$overdue)<a href="{{ route('admin.jobs', ['overdue' => 1]) }}" class="admin-alert admin-alert-warning"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i><div><strong>{{ number_format((int) $stats->overdue_open) }}</strong><span>open job{{ (int) $stats->overdue_open === 1 ? '' : 's' }} past the application deadline</span></div><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>@endif
    <div class="jobs-kpi-grid">
        @foreach([['kpi-blue', 'fa-briefcase', 'all_count', 'Total jobs'], ['kpi-green', 'fa-circle-check', 'open_count', 'Open'], ['kpi-orange', 'fa-file-pen', 'draft_count', 'Draft'], ['kpi-slate', 'fa-lock', 'closed_count', 'Closed'], ['kpi-purple', 'fa-eye', 'total_views', 'Total views'], ['kpi-emerald', 'fa-file-lines', 'total_applications', 'Applications']] as [$style, $icon, $key, $label])
            <div class="jobs-kpi-card"><div class="kpi-icon {{ $style }}"><i class="fa-solid {{ $icon }}" aria-hidden="true"></i></div><div><strong>{{ number_format((int) $stats->{$key}) }}</strong><span>{{ $label }}</span></div></div>
        @endforeach
    </div>
    <div class="admin-tabs">
        @foreach(['' => ['All', 'all_count'], 'Open' => ['Open', 'open_count'], 'Draft' => ['Draft', 'draft_count'], 'Closed' => ['Closed', 'closed_count'], 'Expired' => ['Expired', 'expired_count']] as $value => [$label, $count])
            <a href="{{ route('admin.jobs', array_filter(['status' => $value ?: null, 'category_id' => $categoryId ?: null, 'company_id' => $companyId ?: null, 'q' => $search ?: null])) }}" class="admin-tab {{ $status === $value && !$overdue ? 'active' : '' }}">{{ $label }} <span class="tab-count">{{ number_format((int) $stats->{$count}) }}</span></a>
        @endforeach
        @if((int) $stats->overdue_open > 0)<a href="{{ route('admin.jobs', ['overdue' => 1]) }}" class="admin-tab admin-tab-warning {{ $overdue ? 'active' : '' }}"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Overdue <span class="tab-count tab-count-warning">{{ number_format((int) $stats->overdue_open) }}</span></a>@endif
    </div>
    <form action="{{ route('admin.jobs') }}" method="GET" class="admin-filter-form">
        @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
        @if($companyId)<input type="hidden" name="company_id" value="{{ $companyId }}">@endif
        @if($overdue)<input type="hidden" name="overdue" value="1">@endif
        <div class="filter-field filter-search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input type="search" name="q" value="{{ $search }}" placeholder="Search title, company or location..." maxlength="100" autocomplete="off"></div>
        <div class="filter-field"><select name="category_id"><option value="0">All Categories</option>@foreach($categories as $category)<option value="{{ $category->category_id }}" @selected($categoryId === (int) $category->category_id)>{{ $category->category_name }}</option>@endforeach</select></div>
        <div class="filter-actions"><button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter" aria-hidden="true"></i> Apply</button>@if($search || $categoryId || $companyId || $overdue)<a href="{{ route('admin.jobs', $status ? ['status' => $status] : []) }}" class="btn btn-light">Clear</a>@endif</div>
    </form>
    <section class="admin-panel">
        <div class="admin-panel-header"><div><h2>Results</h2><p>{{ number_format($jobs->total()) }} {{ $jobs->total() === 1 ? 'job' : 'jobs' }}@if($jobs->currentPage() > 1) &middot; page {{ $jobs->currentPage() }} of {{ $jobs->lastPage() }}@endif</p></div></div>
        <div class="admin-table-wrapper"><table class="admin-table jobs-table-full"><thead><tr><th scope="col">Job</th><th scope="col">Company</th><th scope="col">Category</th><th scope="col">Views</th><th scope="col">Applications</th><th scope="col">Status</th><th scope="col">Posted</th><th scope="col">Action</th></tr></thead><tbody>
            @forelse($jobs as $job)
                @php($isOverdue = $job->job_status === 'Open' && $job->application_deadline && $job->application_deadline < today()->toDateString())
                @php($statusClass = 'status-'.(strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $job->job_status) ?? '')) ?: 'unknown'))
                <tr><td><a href="{{ route('admin.job-details', ['id' => $job->job_id]) }}" class="job-cell-link"><strong class="job-cell-title">{{ $job->job_title }}</strong></a><small class="table-subtext">{{ $job->employment_type }} &middot; {{ $job->workplace }}@if($job->location) &middot; {{ $job->location }}@endif</small></td><td>@if($job->company_id)<a href="{{ route('admin.company-details', ['id' => $job->company_id]) }}" class="table-user-link">{{ $job->company_name }}</a>@else<span class="muted-value">—</span>@endif</td><td>{{ $job->category_name ?? '—' }}</td><td>{{ number_format((int) $job->views) }}</td><td><strong class="metric-highlight">{{ number_format((int) $job->total_applications) }}</strong></td><td><span class="status-badge {{ $statusClass }}">{{ $job->job_status }}</span>@if($isOverdue)<small class="table-subtext overdue-label">Overdue</small>@endif</td><td>{{ $job->created_at ? \Illuminate\Support\Carbon::parse($job->created_at)->diffForHumans() : '—' }}</td><td><a href="{{ route('admin.job-details', ['id' => $job->job_id]) }}" class="btn-sm btn-outline"><i class="fa-solid fa-eye" aria-hidden="true"></i> View</a></td></tr>
            @empty
                <tr><td colspan="8" class="empty-table">No jobs match your filters.</td></tr>
            @endforelse
        </tbody></table></div>
        @if($jobs->lastPage() > 1)<nav class="admin-pagination" aria-label="Pagination">@if($jobs->previousPageUrl())<a href="{{ $jobs->previousPageUrl() }}" class="pagination-btn">Previous</a>@endif<div class="pagination-pages">@foreach($jobs->getUrlRange(max(1, $jobs->currentPage() - 2), min($jobs->lastPage(), $jobs->currentPage() + 2)) as $page => $url)<a href="{{ $url }}" class="pagination-number {{ $page === $jobs->currentPage() ? 'active' : '' }}">{{ $page }}</a>@endforeach</div>@if($jobs->nextPageUrl())<a href="{{ $jobs->nextPageUrl() }}" class="pagination-btn">Next</a>@endif</nav>@endif
    </section>
</div>
@endsection
