@extends('layouts.dashboard')
@section('title', 'Company Review')
@section('role', 'Administrator')
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/admin/company_details.css') }}">@endsection
@section('sidebar')
<div class="sidebar-section"><div class="sidebar-section-title">MAIN</div><a href="{{ route('admin.dashboard') }}" class="sidebar-link"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a></div>
<div class="sidebar-section"><div class="sidebar-section-title">MANAGEMENT</div><a href="{{ route('admin.users') }}" class="sidebar-link"><i class="fa-solid fa-users"></i><span>Users</span></a><a href="{{ route('admin.companies') }}" class="sidebar-link active"><i class="fa-solid fa-building"></i><span>Companies</span></a></div>
@endsection
@section('content')
<div class="admin-company-details">
    <a href="{{ route('admin.companies') }}" class="back-link"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to companies</a>
    @foreach(['success' => 'admin-alert--success', 'danger' => 'admin-alert--error', 'info' => 'admin-alert--info'] as $key => $class)
        @if(session($key))<div class="admin-alert {{ $class }}" role="status">{{ session($key) }}</div>@endif
    @endforeach
    @if($errors->any())<div class="admin-alert admin-alert--error" role="alert">{{ $errors->first() }}</div>@endif

    <header class="company-header-card">
        <div class="company-header-main">
            <div class="company-header-logo"><img src="{{ asset('assets/uploads/companies/'.($company->logo ?: 'company.png')) }}" alt=""><span class="logo-fallback"><i class="fa-solid fa-building" aria-hidden="true"></i></span></div>
            <div class="company-header-info">
                <h1>{{ $company->company_name }}</h1>
                <div class="company-header-meta">
                    @if($company->company_email)<span class="meta-item"><i class="fa-solid fa-envelope" aria-hidden="true"></i>{{ $company->company_email }}</span>@endif
                    @if($company->website)<a class="meta-item" href="{{ $company->website }}" rel="noopener noreferrer"><i class="fa-solid fa-globe" aria-hidden="true"></i>{{ $company->website }}</a>@endif
                    @if($company->city || $company->country)<span class="meta-item"><i class="fa-solid fa-location-dot" aria-hidden="true"></i>{{ collect([$company->city, $company->country])->filter()->implode(', ') }}</span>@endif
                </div>
                <div class="company-header-badges"><span class="tag-badge"><i class="fa-solid fa-industry" aria-hidden="true"></i>{{ $company->industry ?: 'Industry not listed' }}</span><span class="status-badge status-{{ strtolower($company->status) }}">{{ $company->status }}</span></div>
            </div>
        </div>
        <div class="company-header-id"><span>Company ID</span><strong>#{{ $company->company_id }}</strong></div>
    </header>

    <div class="details-grid">
        <div class="details-main">
            <section class="details-card">
                <div class="details-card-header"><h2>Company information</h2></div>
                <dl class="info-list">
                    @foreach(['Company email' => 'company_email', 'Phone' => 'company_phone', 'Industry' => 'industry', 'Company size' => 'company_size', 'Founded year' => 'founded_year', 'Address' => 'address', 'City' => 'city', 'State' => 'state', 'Country' => 'country', 'Postal code' => 'postal_code', 'Registered' => 'created_at'] as $label => $field)
                        <div class="info-row"><dt>{{ $label }}</dt><dd>{{ $company->{$field} ?: '—' }}</dd></div>
                    @endforeach
                </dl>
                @if($company->description)<div class="company-description"><h3>About the company</h3><p>{{ $company->description }}</p></div>@endif
                @if(isset($company->rejection_reason) && $company->rejection_reason)<div class="company-description"><h3>Rejection reason</h3><p>{{ $company->rejection_reason }}</p></div>@endif
            </section>
        </div>
        <aside class="details-side">
            <section class="details-card">
                <div class="details-card-header"><h2>Primary contact</h2></div>
                @if($company->owner_user_id)
                    <div class="owner-card"><a class="owner-name" href="{{ route('admin.user-details', ['id' => $company->owner_user_id]) }}">{{ $company->owner_name }}</a><span class="owner-contact"><i class="fa-solid fa-envelope" aria-hidden="true"></i>{{ $company->owner_email }}</span></div>
                @else
                    <div class="empty-state"><i class="fa-solid fa-user-slash" aria-hidden="true"></i><p>No owner assigned.</p></div>
                @endif
            </section>
            <section class="details-card">
                <div class="details-card-header"><h2>Recruitment activity</h2></div>
                <div class="stat-mini-grid"><div class="stat-mini"><strong>{{ number_format((int) $company->total_jobs) }}</strong><span>Total jobs</span></div><div class="stat-mini"><strong>{{ number_format((int) $company->open_jobs) }}</strong><span>Open jobs</span></div></div>
            </section>
            <section class="details-card">
                <div class="details-card-header"><h2>Verification actions</h2></div>
                <div class="action-list">
                    @if($company->status !== 'Approved')
                        <form action="{{ route('admin.company-status.update') }}" method="POST">@csrf<input type="hidden" name="company_id" value="{{ $company->company_id }}"><button class="action-btn action-btn-success" type="submit" name="action" value="approve"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Approve company</button></form>
                    @endif
                    @if($company->status !== 'Rejected')
                        <form action="{{ route('admin.company-status.update') }}" method="POST">@csrf<input type="hidden" name="company_id" value="{{ $company->company_id }}"><input type="hidden" name="action" value="reject"><label for="rejectionReason">Rejection reason</label><textarea id="rejectionReason" name="reason" maxlength="1000" rows="3" required>{{ old('reason') }}</textarea><button class="action-btn action-btn-danger" type="submit"><i class="fa-solid fa-circle-xmark" aria-hidden="true"></i> Reject company</button></form>
                    @endif
                    @if($company->status === 'Approved')
                        <form action="{{ route('admin.company-status.update') }}" method="POST">@csrf<input type="hidden" name="company_id" value="{{ $company->company_id }}"><button class="action-btn action-btn-warning" type="submit" name="action" value="suspend"><i class="fa-solid fa-pause" aria-hidden="true"></i> Suspend company</button></form>
                    @endif
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection
