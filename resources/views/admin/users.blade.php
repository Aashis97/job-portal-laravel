@extends('layouts.dashboard')
@section('title', $pageTitle)
@section('role', 'Administrator')
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/admin/users.css') }}">@endsection
@section('js')<script src="{{ asset('assets/js/admin/users.js') }}"></script>@endsection
@section('sidebar')
<div class="sidebar-section"><div class="sidebar-section-title">MAIN</div><a href="{{ route('admin.dashboard') }}" class="sidebar-link"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a></div>
<div class="sidebar-section"><div class="sidebar-section-title">MANAGEMENT</div><a href="{{ route('admin.users') }}" class="sidebar-link active"><i class="fa-solid fa-users"></i><span>Users</span></a><a href="{{ url('/admin/companies.php') }}" class="sidebar-link"><i class="fa-solid fa-building"></i><span>Companies</span></a><a href="{{ url('/admin/jobs.php') }}" class="sidebar-link"><i class="fa-solid fa-briefcase"></i><span>Jobs</span></a></div>
@endsection
@section('content')
<div class="admin-users-page">
    <div class="admin-page-header"><div><span class="admin-page-label">USER MANAGEMENT</span><h1>{{ $pageTitle }}</h1><p>Browse, filter and manage all registered accounts.</p></div><div class="admin-page-date"><i class="fa-solid fa-users" aria-hidden="true"></i>{{ number_format($users->total()) }} total</div></div>

    <div class="admin-tabs">
        @foreach([
            0 => ['All', $tabCounts->all_count],
            1 => ['Admins', $tabCounts->admin_count],
            2 => ['Employers', $tabCounts->employer_count],
            3 => ['Job Seekers', $tabCounts->jobseeker_count],
        ] as $role => [$label, $count])
            @php($tabQuery = array_filter(['role' => $role ?: null, 'status' => $statusFilter ?: null, 'verified' => $verifiedFilter !== '' ? $verifiedFilter : null, 'q' => $search ?: null]))
            <a href="{{ route('admin.users', $tabQuery) }}" class="admin-tab {{ $roleFilter === $role ? 'active' : '' }}">{{ $label }} <span class="tab-count">{{ number_format((int) $count) }}</span></a>
        @endforeach
    </div>

    <form action="{{ route('admin.users') }}" method="GET" class="admin-filter-form">
        @if($roleFilter > 0)<input type="hidden" name="role" value="{{ $roleFilter }}">@endif
        <div class="filter-field filter-search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input type="search" name="q" value="{{ $search }}" placeholder="Search by name, email or phone..." maxlength="100" autocomplete="off"></div>
        <div class="filter-field"><select name="status"><option value="">All Statuses</option>@foreach($statuses as $status)<option value="{{ $status }}" @selected($statusFilter === $status)>{{ $status }}</option>@endforeach</select></div>
        <div class="filter-field"><select name="verified"><option value="">Any Verification</option><option value="1" @selected($verifiedFilter === '1')>Verified</option><option value="0" @selected($verifiedFilter === '0')>Not Verified</option></select></div>
        <div class="filter-actions"><button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter" aria-hidden="true"></i> Apply</button>@if($search !== '' || $statusFilter !== '' || $verifiedFilter !== '')<a href="{{ route('admin.users', $roleFilter > 0 ? ['role' => $roleFilter] : []) }}" class="btn btn-light">Clear</a>@endif</div>
    </form>

    <section class="admin-panel">
        <div class="admin-panel-header"><div><h2>Results</h2><p>{{ number_format($users->total()) }} {{ $users->total() === 1 ? 'user' : 'users' }}@if($users->currentPage() > 1) &middot; page {{ $users->currentPage() }} of {{ $users->lastPage() }}@endif</p></div></div>
        <div class="admin-table-wrapper"><table class="admin-table"><thead><tr><th scope="col">User</th><th scope="col">Contact</th><th scope="col">Role</th><th scope="col">Status</th><th scope="col">Joined</th><th scope="col">Action</th></tr></thead><tbody>
            @forelse($users as $user)
                @php($roleLabel = [1 => 'Administrator', 2 => 'Employer', 3 => 'Job Seeker'][(int) $user->role_id] ?? 'User')
                @php($statusClass = 'status-'.(strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $user->account_status) ?? '')) ?: 'unknown'))
                <tr>
                    <td><div class="table-user"><div class="table-avatar">{{ mb_strtoupper(mb_substr(trim((string) $user->full_name) ?: 'U', 0, 1)) }}</div><div><a href="{{ route('admin.user-details', ['id' => $user->user_id]) }}" class="table-user-link"><strong>{{ $user->full_name }}</strong></a><small class="table-subtext">#{{ $user->user_id }}@if((int) $user->role_id === 2 && $user->company_name) &middot; {{ $user->company_name }}@elseif((int) $user->role_id === 3 && $user->years_of_experience !== null) &middot; {{ number_format((float) $user->years_of_experience, 1) }}y exp @endif</small></div></div></td>
                    <td><div class="table-contact"><span class="table-email" title="{{ $user->email }}">{{ $user->email }}</span>@if($user->phone)<small class="table-subtext">{{ $user->phone }}</small>@endif</div></td>
                    <td><span class="role-pill role-{{ (int) $user->role_id }}">{{ $roleLabel }}</span></td>
                    <td><span class="status-badge {{ $statusClass }}">{{ $user->account_status }}</span>@if(!(bool) $user->email_verified)<small class="table-subtext">unverified email</small>@endif</td>
                    <td><span title="{{ $user->created_at ?? '' }}">{{ $user->created_at ? \Illuminate\Support\Carbon::parse($user->created_at)->diffForHumans() : '—' }}</span></td>
                    <td><a href="{{ route('admin.user-details', ['id' => $user->user_id]) }}" class="btn btn-sm btn-outline"><i class="fa-solid fa-eye" aria-hidden="true"></i> View</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty-table">No users match your filters.</td></tr>
            @endforelse
        </tbody></table></div>

        @if($users->lastPage() > 1)
            <nav class="admin-pagination" aria-label="Pagination">
                @if($users->onFirstPage()) @else<a href="{{ $users->previousPageUrl() }}" class="pagination-btn"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i> Previous</a>@endif
                <div class="pagination-pages">
                    @foreach($users->getUrlRange(max(1, $users->currentPage() - 2), min($users->lastPage(), $users->currentPage() + 2)) as $page => $url)
                        <a href="{{ $url }}" class="pagination-number {{ $page === $users->currentPage() ? 'active' : '' }}">{{ $page }}</a>
                    @endforeach
                </div>
                @if($users->hasMorePages())<a href="{{ $users->nextPageUrl() }}" class="pagination-btn">Next <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>@endif
            </nav>
        @endif
    </section>
</div>
@endsection
