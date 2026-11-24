@extends('layouts.dashboard')
@section('title', 'Employer Dashboard')
@section('role', 'Employer')
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/employer/dashboard.css') }}">@endsection
@section('sidebar')
<div class="sidebar-section"><div class="sidebar-section-title">MAIN</div><a href="{{ route('employer.dashboard') }}" class="sidebar-link active"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a></div>
<div class="sidebar-section"><div class="sidebar-section-title">RECRUITMENT</div><a href="{{ route('employer.jobs') }}" class="sidebar-link"><i class="fa-solid fa-briefcase"></i><span>Manage Jobs</span></a><a href="{{ route('employer.applications') }}" class="sidebar-link"><i class="fa-solid fa-users"></i><span>Applicants</span></a><a href="{{ route('employer.interviews') }}" class="sidebar-link"><i class="fa-solid fa-calendar-check"></i><span>Interviews</span></a><a href="{{ route('employer.analytics') }}" class="sidebar-link"><i class="fa-solid fa-chart-line"></i><span>Analytics</span></a></div>
<div class="sidebar-section"><div class="sidebar-section-title">COMPANY</div><a href="{{ route('employer.company-profile') }}" class="sidebar-link"><i class="fa-solid fa-building-user"></i><span>Company Profile</span></a></div>
@endsection
@section('content')
<div class="employer-dashboard">
    <div class="employer-welcome"><div class="welcome-content"><span class="welcome-label">Employer Dashboard</span><h1>Welcome back, {{ auth()->user()->full_name }}</h1></div></div>
    <div class="dashboard-stats">
        <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-briefcase"></i></div><div><span class="stat-label">Jobs</span><strong class="stat-value">{{ $stats['jobs'] }}</strong></div></div>
        <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-file-circle-check"></i></div><div><span class="stat-label">Applications</span><strong class="stat-value">{{ $stats['applications'] }}</strong></div></div>
        <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-calendar-check"></i></div><div><span class="stat-label">Interviews</span><strong class="stat-value">{{ $stats['interviews'] }}</strong></div></div>
    </div>
</div>
@endsection
