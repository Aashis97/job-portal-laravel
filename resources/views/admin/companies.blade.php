@extends('layouts.dashboard')
@section('title', 'Companies')
@section('role', 'Administrator')
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/admin/companies.css') }}">@endsection
@section('js')<script src="{{ asset('assets/js/admin/companies.js') }}"></script>@endsection
@section('sidebar')
<div class="sidebar-section"><div class="sidebar-section-title">MAIN</div><a href="{{ route('admin.dashboard') }}" class="sidebar-link"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a></div>
<div class="sidebar-section"><div class="sidebar-section-title">MANAGEMENT</div><a href="{{ route('admin.users') }}" class="sidebar-link"><i class="fa-solid fa-users"></i><span>Users</span></a><a href="{{ route('admin.companies') }}" class="sidebar-link active"><i class="fa-solid fa-building"></i><span>Companies</span></a><a href="{{ url('/admin/jobs.php') }}" class="sidebar-link"><i class="fa-solid fa-briefcase"></i><span>Jobs</span></a></div>
@endsection
@section('content')
<div class="admin-companies-page">
    <div class="admin-page-header"><div><span class="admin-page-label">JOB MANAGEMENT</span><h1>Companies</h1><p>Review, verify, and manage all registered companies.</p></div><div class="admin-page-date"><i class="fa-solid fa-building" aria-hidden="true"></i>{{ number_format($companies->total()) }} total</div></div>

    <div class="admin-tabs">
        @foreach([
            '' => ['All', 'all_count', ''],
            'Pending' => ['Pending', 'pending_count', 'fa-hourglass-half'],
            'Approved' => ['Approved', 'approved_count', 'fa-circle-check'],
            'Rejected' => ['Rejected', 'rejected_count', 'fa-circle-xmark'],
            'Suspended' => ['Suspended', 'suspended_count', 'fa-pause'],
        ] as $value => [$label, $countKey, $icon])
            <a href="{{ route('admin.companies', array_filter(['status' => $value ?: null, 'q' => $search ?: null])) }}" class="admin-tab {{ $status === $value ? 'active' : '' }}">@if($icon)<i class="fa-solid {{ $icon }}" aria-hidden="true"></i>@endif {{ $label }} <span class="tab-count {{ $value === 'Pending' ? 'tab-count-warning' : '' }}">{{ number_format((int) $tabCounts->{$countKey}) }}</span></a>
        @endforeach
    </div>

    <form action="{{ route('admin.companies') }}" method="GET" class="admin-filter-form">
        @if($status !== '')<input type="hidden" name="status" value="{{ $status }}">@endif
        <div class="filter-field filter-search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input type="search" name="q" value="{{ $search }}" placeholder="Search by company, email, industry, city or owner..." maxlength="100" autocomplete="off"></div>
        <div class="filter-actions"><button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter" aria-hidden="true"></i> Search</button>@if($search !== '')<a href="{{ route('admin.companies', $status !== '' ? ['status' => $status] : []) }}" class="btn btn-light">Clear</a>@endif</div>
    </form>

    <section class="admin-panel">
        <div class="admin-panel-header"><div><h2>Results</h2><p>{{ number_format($companies->total()) }} {{ $companies->total() === 1 ? 'company' : 'companies' }}@if($companies->currentPage() > 1) &middot; page {{ $companies->currentPage() }} of {{ $companies->lastPage() }}@endif</p></div></div>
        <div class="admin-table-wrapper"><table class="admin-table companies-table"><thead><tr><th scope="col">Company</th><th scope="col">Owner</th><th scope="col">Industry</th><th scope="col">Jobs</th><th scope="col">Status</th><th scope="col">Registered</th><th scope="col">Action</th></tr></thead><tbody>
            @forelse($companies as $company)
                @php($statusClass = 'status-'.(strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $company->status) ?? '')) ?: 'unknown'))
                <tr>
                    <td><div class="company-cell"><div class="company-logo-mini"><img src="{{ asset('assets/uploads/companies/'.($company->logo ?: 'company.png')) }}" alt=""><span class="logo-fallback"><i class="fa-solid fa-building" aria-hidden="true"></i></span></div><div><a href="{{ route('admin.company-details', ['id' => $company->company_id]) }}" class="company-name-link"><strong>{{ $company->company_name }}</strong></a><small class="table-subtext">#{{ $company->company_id }} @if($company->company_email)&middot; {{ $company->company_email }}@endif</small></div></div></td>
                    <td>@if($company->owner_user_id)<div class="table-contact"><a href="{{ route('admin.user-details', ['id' => $company->owner_user_id]) }}" class="table-user-link"><strong>{{ $company->owner_name }}</strong></a><small class="table-subtext">{{ $company->owner_email }}</small></div>@else<span class="muted-value">No owner assigned</span>@endif</td>
                    <td>{{ $company->industry ?: '—' }}</td>
                    <td><div class="jobs-count-cell"><strong>{{ number_format((int) $company->total_jobs) }}</strong>@if((int) $company->open_jobs > 0)<small class="table-subtext">{{ number_format((int) $company->open_jobs) }} open</small>@endif</div></td>
                    <td><span class="status-badge {{ $statusClass }}">{{ $company->status }}</span></td>
                    <td><span title="{{ $company->created_at ?? '' }}">{{ $company->created_at ? \Illuminate\Support\Carbon::parse($company->created_at)->diffForHumans() : '—' }}</span></td>
                    <td><a href="{{ route('admin.company-details', ['id' => $company->company_id]) }}" class="btn-sm btn-outline"><i class="fa-solid fa-eye" aria-hidden="true"></i> {{ $company->status === 'Pending' ? 'Review' : 'View' }}</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty-table">No companies match your filters.</td></tr>
            @endforelse
        </tbody></table></div>
        @if($companies->lastPage() > 1)
            <nav class="admin-pagination" aria-label="Pagination">
                @if($companies->previousPageUrl())<a href="{{ $companies->previousPageUrl() }}" class="pagination-btn"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i> Previous</a>@endif
                <div class="pagination-pages">@foreach($companies->getUrlRange(max(1, $companies->currentPage() - 2), min($companies->lastPage(), $companies->currentPage() + 2)) as $page => $url)<a href="{{ $url }}" class="pagination-number {{ $page === $companies->currentPage() ? 'active' : '' }}">{{ $page }}</a>@endforeach</div>
                @if($companies->nextPageUrl())<a href="{{ $companies->nextPageUrl() }}" class="pagination-btn">Next <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>@endif
            </nav>
        @endif
    </section>
</div>
@endsection
