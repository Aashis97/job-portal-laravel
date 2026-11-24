@extends('layouts.public')

@section('title', 'Find Your Dream Job - Online Job Portal System')

@section('content')
<header class="site-header" id="siteHeader">
    <nav class="site-nav" aria-label="Main navigation">
        <a href="{{ url('/') }}" class="site-logo">
            <span class="site-logo-icon"><i class="fa-solid fa-briefcase"></i></span>
            <span class="site-logo-text"><strong>JobPortal</strong><small>Nepal</small></span>
        </a>
        <ul class="site-links">
            <li><a href="{{ url('/') }}" class="is-active">Home</a></li>
            <li><a href="{{ url('/jobseeker/jobs.php') }}">Browse Jobs</a></li>
            <li><a href="{{ url('/#categories') }}">Categories</a></li>
            <li><a href="{{ url('/#why') }}">Why Us</a></li>
        </ul>
        <div class="site-actions">
            <button type="button" class="site-theme-toggle" data-site-theme-toggle aria-label="Switch to dark mode">
                <i class="fa-solid fa-moon"></i>
            </button>
            @auth
                @php
                    $dashboard = match ((int) auth()->user()->role_id) {
                        1 => route('admin.dashboard'),
                        2 => route('employer.dashboard'),
                        default => route('jobseeker.dashboard'),
                    };
                @endphp
                <a href="{{ $dashboard }}" class="site-btn site-btn-primary"><i class="fa-solid fa-gauge-high"></i>Dashboard</a>
                <a href="{{ route('logout') }}" class="site-btn site-btn-ghost">Logout</a>
            @else
                <a href="{{ route('login') }}" class="site-btn site-btn-ghost">Login</a>
                <a href="{{ route('register') }}" class="site-btn site-btn-primary"><i class="fa-solid fa-user-plus"></i>Register</a>
            @endauth
        </div>
        <button type="button" class="site-toggle" id="siteToggle" aria-label="Toggle navigation" aria-expanded="false" aria-controls="siteMobileMenu">
            <i class="fa-solid fa-bars" aria-hidden="true"></i>
        </button>
    </nav>
    <div class="site-mobile" id="siteMobileMenu" hidden>
        <a href="{{ url('/') }}">Home</a>
        <a href="{{ url('/jobseeker/jobs.php') }}">Browse Jobs</a>
        <a href="{{ url('/#categories') }}">Categories</a>
        <a href="{{ url('/#why') }}">Why Us</a>
        <div class="site-mobile-divider"></div>
        @auth
            <a href="{{ $dashboard }}">Dashboard</a>
            <a href="{{ route('logout') }}">Logout</a>
        @else
            <a href="{{ route('login') }}">Login</a>
            <a href="{{ route('register') }}" class="site-mobile-primary">Register</a>
        @endauth
    </div>
</header>

<section class="landing-hero">
    <div class="landing-hero-inner">
        <div class="hero-copy">
            <span class="hero-pill"><span class="hero-pill-dot"></span>Nepal's modern job portal</span>
            <h1 class="hero-title">Find your <span class="hero-gradient">dream job</span> faster than ever.</h1>
            <p class="hero-lead">Thousands of verified openings from trusted companies. Apply in one click, track every step, and get matched to roles that fit.</p>
            <form class="hero-search" action="{{ url('/jobseeker/jobs.php') }}" method="GET" role="search">
                <div class="hero-search-field">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" name="q" placeholder="Job title, skill or keyword" maxlength="100">
                </div>
                <div class="hero-search-field">
                    <i class="fa-solid fa-location-dot"></i>
                    <input type="text" name="location" placeholder="City or location" maxlength="100">
                </div>
                <button type="submit" class="hero-search-btn"><i class="fa-solid fa-magnifying-glass"></i>Search Jobs</button>
            </form>
            <div class="hero-stats">
                <div class="hero-stat"><strong>{{ $stats['jobs'] }}</strong><span>Open Jobs</span></div>
                <div class="hero-stat"><strong>{{ $stats['companies'] }}</strong><span>Companies</span></div>
                <div class="hero-stat"><strong>{{ $stats['candidates'] }}</strong><span>Candidates</span></div>
            </div>
        </div>
        <div class="hero-visual" aria-hidden="true">
            <div class="hero-visual-glow"></div>
            <div class="hero-fake-card hero-fake-card-1"><div class="hero-fake-avatar" style="background:linear-gradient(135deg,#dbeafe,#bfdbfe);color:#2563eb;">G</div><div class="hero-fake-lines"><span class="hero-fake-line"></span><span class="hero-fake-line short"></span></div><span class="hero-fake-badge hero-fake-badge-blue">Hiring</span></div>
            <div class="hero-fake-card hero-fake-card-2"><div class="hero-fake-avatar" style="background:linear-gradient(135deg,#dcfce7,#bbf7d0);color:#16a34a;">N</div><div class="hero-fake-lines"><span class="hero-fake-line"></span><span class="hero-fake-line short"></span></div><span class="hero-fake-badge hero-fake-badge-green">New</span></div>
            <div class="hero-float hero-float-1"><i class="fa-solid fa-circle-check"></i>Verified employer</div>
            <div class="hero-float hero-float-2"><i class="fa-solid fa-wand-magic-sparkles"></i>AI match 92%</div>
        </div>
    </div>
</section>

@if($categories->isNotEmpty())
<section class="landing-section landing-categories" id="categories">
    <div class="section-header"><span class="section-eyebrow">Popular categories</span><h2>Browse jobs by category</h2><p>Explore roles across industries.</p></div>
    <div class="category-grid">
        @foreach($categories as $category)
            <a class="category-card" href="{{ url('/jobseeker/jobs.php') }}?category_id={{ $category->category_id }}">
                <span class="category-icon"><i class="fa-solid fa-briefcase"></i></span>
                <span class="category-name">{{ $category->category_name }}</span>
                <span class="category-count">{{ number_format($category->job_count) }} {{ $category->job_count == 1 ? 'job' : 'jobs' }}</span>
            </a>
        @endforeach
    </div>
</section>
@endif

@if($featuredJobs->isNotEmpty())
<section class="landing-section landing-featured">
    <div class="section-header section-header-split"><div><span class="section-eyebrow">Latest jobs</span><h2>Featured opportunities</h2><p>Fresh openings from verified employers.</p></div><a class="section-link" href="{{ url('/jobseeker/jobs.php') }}">Browse all jobs <i class="fa-solid fa-arrow-right"></i></a></div>
    <div class="featured-grid">
        @foreach($featuredJobs as $job)
            <a class="featured-card" href="{{ url('/jobseeker/job_details.php') }}?id={{ $job->job_id }}">
                <div class="featured-card-top"><div class="featured-logo"><i class="fa-solid fa-building"></i></div><div class="featured-meta"><span class="featured-company">{{ $job->company_name }}</span></div></div>
                <h3 class="featured-title">{{ $job->job_title }}</h3>
                <div class="featured-chips"><span class="chip">{{ $job->employment_type }}</span><span class="chip">{{ $job->workplace }}</span></div>
                <div class="featured-bottom"><span class="featured-salary">View opportunity</span><span class="featured-cta">View job <i class="fa-solid fa-arrow-right"></i></span></div>
            </a>
        @endforeach
    </div>
</section>
@endif

<section class="landing-section landing-why" id="why">
    <div class="section-header"><span class="section-eyebrow">Why choose us</span><h2>Built for job seekers and employers</h2></div>
    <div class="why-grid">
        <div class="why-card"><span class="why-icon"><i class="fa-solid fa-user-check"></i></span><h3>Verified employers</h3><p>Companies are checked before posting jobs.</p></div>
        <div class="why-card"><span class="why-icon"><i class="fa-solid fa-wand-magic-sparkles"></i></span><h3>AI job matching</h3><p>Recommendations based on skills and experience.</p></div>
        <div class="why-card"><span class="why-icon"><i class="fa-solid fa-shield-halved"></i></span><h3>Secure platform</h3><p>CSRF, session and file protection throughout the portal.</p></div>
        <div class="why-card"><span class="why-icon"><i class="fa-solid fa-comments"></i></span><h3>Direct messaging</h3><p>Talk to recruiters and applicants in the portal.</p></div>
    </div>
</section>
@endsection
