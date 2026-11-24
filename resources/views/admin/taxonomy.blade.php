@extends('layouts.dashboard')
@section('title', 'Categories & Skills')
@section('role', 'Administrator')
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/admin/taxonomy.css') }}">@endsection
@section('sidebar')
<div class="sidebar-section"><div class="sidebar-section-title">MAIN</div><a href="{{ route('admin.dashboard') }}" class="sidebar-link"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a></div>
<div class="sidebar-section"><div class="sidebar-section-title">MANAGEMENT</div><a href="{{ route('admin.users') }}" class="sidebar-link"><i class="fa-solid fa-users"></i><span>Users</span></a><a href="{{ route('admin.companies') }}" class="sidebar-link"><i class="fa-solid fa-building"></i><span>Companies</span></a><a href="{{ route('admin.jobs') }}" class="sidebar-link"><i class="fa-solid fa-briefcase"></i><span>Jobs</span></a><a href="{{ route('admin.applications') }}" class="sidebar-link"><i class="fa-solid fa-file-lines"></i><span>Applications</span></a><a href="{{ route('admin.reports') }}" class="sidebar-link"><i class="fa-solid fa-flag"></i><span>Reports</span></a><a href="{{ route('admin.audit-log') }}" class="sidebar-link"><i class="fa-solid fa-clipboard-list"></i><span>Audit Log</span></a><a href="{{ route('admin.taxonomy') }}" class="sidebar-link active"><i class="fa-solid fa-tags"></i><span>Categories & Skills</span></a><a href="{{ route('admin.settings') }}" class="sidebar-link"><i class="fa-solid fa-gear"></i><span>Settings</span></a></div>
@endsection
@section('content')
<main class="admin-taxonomy-page">
    <header class="admin-page-header"><div><span class="admin-page-label">JOB MANAGEMENT</span><h1>Categories &amp; Skills</h1><p>Keep job categories and candidate/job skill options up to date.</p></div></header>
    @foreach(['success' => 'admin-alert--success', 'danger' => 'admin-alert--error'] as $key => $class)@if(session($key))<div class="admin-alert {{ $class }}" role="status">{{ session($key) }}</div>@endif @endforeach
    @if($errors->any())<div class="admin-alert admin-alert--error" role="alert">{{ $errors->first() }}</div>@endif
    <div class="taxonomy-grid">
        <section class="taxonomy-panel" aria-labelledby="categoriesHeading">
            <div class="taxonomy-panel-header"><div><h2 id="categoriesHeading">Job categories</h2><p>Inactive categories stay attached to existing jobs but are hidden from new job posts.</p></div><span class="taxonomy-count">{{ number_format($categories->count()) }}</span></div>
            <form action="{{ route('admin.taxonomy.update') }}" method="POST" class="taxonomy-create-form">
                @csrf<input type="hidden" name="action" value="category_create"><h3>Add category</h3>
                <label><span>Category name</span><input type="text" name="category_name" maxlength="100" required></label>
                <label><span>Description</span><textarea name="category_description" rows="2" maxlength="5000"></textarea></label>
                <label><span>Initial status</span><select name="status"><option value="Active">Active</option><option value="Inactive">Inactive</option></select></label>
                <button type="submit" class="btn btn-primary">Add category</button>
            </form>
            <div class="taxonomy-list">
                @foreach($categories as $category)
                    <article class="taxonomy-item">
                        <form action="{{ route('admin.taxonomy.update') }}" method="POST">
                            @csrf<input type="hidden" name="action" value="category_update"><input type="hidden" name="category_id" value="{{ $category->category_id }}">
                            <div class="taxonomy-item-heading"><strong>{{ $category->category_name }}</strong><span>{{ number_format((int) $category->job_count) }} jobs</span></div>
                            <label><span>Name</span><input type="text" name="category_name" value="{{ $category->category_name }}" maxlength="100" required></label>
                            <label><span>Description</span><textarea name="category_description" rows="2" maxlength="5000">{{ $category->category_description }}</textarea></label>
                            <div class="taxonomy-item-actions"><label><span>Status</span><select name="status"><option value="Active" @selected($category->status === 'Active')>Active</option><option value="Inactive" @selected($category->status === 'Inactive')>Inactive</option></select></label><button type="submit" class="btn btn-light">Save</button></div>
                        </form>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="taxonomy-panel" aria-labelledby="skillsHeading">
            <div class="taxonomy-panel-header"><div><h2 id="skillsHeading">Skills</h2><p>Skills used in job requirements and candidate matching.</p></div><span class="taxonomy-count">{{ number_format($skills->count()) }}</span></div>
            <form action="{{ route('admin.taxonomy.update') }}" method="POST" class="taxonomy-create-form">
                @csrf<input type="hidden" name="action" value="skill_create"><h3>Add skill</h3>
                <label><span>Skill name</span><input type="text" name="skill_name" maxlength="100" required></label>
                <label><span>Skill category</span><input type="text" name="skill_category" maxlength="100" placeholder="e.g. Frontend"></label>
                <button type="submit" class="btn btn-primary">Add skill</button>
            </form>
            <div class="taxonomy-list">
                @foreach($skills as $skill)
                    @php($inUse = (int) $skill->job_count + (int) $skill->seeker_count > 0)
                    <article class="taxonomy-item">
                        <form action="{{ route('admin.taxonomy.update') }}" method="POST">
                            @csrf<input type="hidden" name="action" value="skill_update"><input type="hidden" name="skill_id" value="{{ $skill->skill_id }}">
                            <div class="taxonomy-item-heading"><strong>{{ $skill->skill_name }}</strong><span>{{ number_format((int) $skill->job_count) }} jobs / {{ number_format((int) $skill->seeker_count) }} seekers</span></div>
                            <div class="taxonomy-item-fields"><label><span>Name</span><input type="text" name="skill_name" value="{{ $skill->skill_name }}" maxlength="100" required></label><label><span>Category</span><input type="text" name="skill_category" value="{{ $skill->skill_category }}" maxlength="100"></label></div>
                            <div class="taxonomy-item-actions"><button type="submit" class="btn btn-light">Save</button></div>
                        </form>
                        <form action="{{ route('admin.taxonomy.update') }}" method="POST" class="taxonomy-delete-form" onsubmit="return confirm('Delete this skill?')">
                            @csrf<input type="hidden" name="action" value="skill_delete"><input type="hidden" name="skill_id" value="{{ $skill->skill_id }}">
                            @if($inUse)<button type="submit" class="btn btn-danger" disabled title="Remove this skill from jobs and candidate profiles before deleting it.">In use</button>@else<button type="submit" class="btn btn-danger">Delete</button>@endif
                        </form>
                    </article>
                @endforeach
            </div>
        </section>
    </div>
</main>
@endsection
