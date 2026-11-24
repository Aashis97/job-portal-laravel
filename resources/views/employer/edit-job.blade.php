@extends('layouts.dashboard')

@section('title', 'Edit Job')
@section('role', 'Employer')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/employer/edit_job.css') }}">
@endsection

@section('content')
@php
    $oldSkills = old('skills');
    $selectedSkillIds = $selectedSkills;
    if ($oldSkills !== null) {
        $selectedSkillIds = [];
        if (is_array($oldSkills)) {
            foreach ($oldSkills as $skillId) {
                if (is_scalar($skillId)) {
                    $selectedSkillIds[] = (int) $skillId;
                }
            }
        }
    }
    $selectedSkillIds = array_map('strval', $selectedSkillIds);
@endphp
<section class="edit-job-page">
    @if(session('danger'))
        <div class="alert alert-danger" role="alert">{{ session('danger') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger" role="alert">Please correct the highlighted form errors and try again.</div>
    @endif

    <div class="edit-page-header">
        <div class="edit-page-header-left">
            <a href="{{ route('employer.jobs') }}" class="back-link"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Back to Manage Jobs</a>
            <span class="page-eyebrow"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>Employer Panel</span>
            <h1>Edit Job</h1>
            <p>Update your job posting information and requirements.</p>
        </div>
        <div class="header-job-status"><span class="job-status-badge status-{{ strtolower($job->job_status) }}"><span class="status-dot"></span>{{ $job->job_status }}</span></div>
    </div>

    <div class="edit-job-layout">
        <div class="edit-job-main">
            <form action="{{ route('employer.jobs.update') }}" method="POST" id="editJobForm" novalidate>
                @csrf
                <input type="hidden" name="job_id" value="{{ $job->job_id }}">

                <div class="form-card">
                    <div class="form-card-header"><div class="form-section-icon"><i class="fa-solid fa-briefcase" aria-hidden="true"></i></div><div><h2>Basic Information</h2><p>Update the main details of your job posting.</p></div></div>
                    <div class="form-card-body">
                        <div class="form-group full-width"><label for="job_title">Job Title <span class="required">*</span></label><input type="text" id="job_title" name="job_title" value="{{ old('job_title', $job->job_title) }}" placeholder="e.g. Senior PHP Developer" maxlength="150" required><small>Use a clear and professional job title.</small>@error('job_title')<small class="field-error">{{ $message }}</small>@enderror</div>
                        <div class="form-grid">
                            <div class="form-group"><label for="category_id">Job Category <span class="required">*</span></label><select id="category_id" name="category_id" required><option value="">Select category</option>@foreach($categories as $category)<option value="{{ $category->category_id }}" @selected((string) old('category_id', $job->category_id) === (string) $category->category_id)>{{ $category->category_name }}</option>@endforeach</select>@error('category_id')<small class="field-error">{{ $message }}</small>@enderror</div>
                            <div class="form-group"><label for="location">Location <span class="required">*</span></label><div class="input-with-icon"><i class="fa-solid fa-location-dot" aria-hidden="true"></i><input type="text" id="location" name="location" value="{{ old('location', $job->location) }}" placeholder="e.g. Kathmandu, Nepal" maxlength="150" required></div>@error('location')<small class="field-error">{{ $message }}</small>@enderror</div>
                        </div>
                    </div>
                </div>

                <div class="form-card">
                    <div class="form-card-header"><div class="form-section-icon"><i class="fa-solid fa-align-left" aria-hidden="true"></i></div><div><h2>Job Description</h2><p>Update the role, qualifications, and responsibilities candidates will see.</p></div></div>
                    <div class="form-card-body">
                        <div class="form-group full-width"><label for="job_description">Description <span class="required">*</span></label><textarea id="job_description" name="job_description" rows="7" maxlength="10000" required>{{ old('job_description', $job->job_description) }}</textarea>@error('job_description')<small class="field-error">{{ $message }}</small>@enderror</div>
                        <div class="form-group full-width"><label for="job_requirements">Requirements</label><textarea id="job_requirements" name="job_requirements" rows="5" maxlength="10000">{{ old('job_requirements', $job->job_requirements) }}</textarea>@error('job_requirements')<small class="field-error">{{ $message }}</small>@enderror</div>
                        <div class="form-group full-width"><label for="job_responsibilities">Responsibilities</label><textarea id="job_responsibilities" name="job_responsibilities" rows="5" maxlength="10000">{{ old('job_responsibilities', $job->job_responsibilities) }}</textarea>@error('job_responsibilities')<small class="field-error">{{ $message }}</small>@enderror</div>
                    </div>
                </div>

                <div class="form-card">
                    <div class="form-card-header"><div class="form-section-icon"><i class="fa-solid fa-user-tie" aria-hidden="true"></i></div><div><h2>Employment Details</h2><p>Specify the type and requirements of the position.</p></div></div>
                    <div class="form-card-body">
                        <div class="form-grid three-columns">
                            <div class="form-group"><label for="employment_type">Employment Type <span class="required">*</span></label><select id="employment_type" name="employment_type" required><option value="">Select type</option>@foreach(['Full-Time', 'Part-Time', 'Contract', 'Internship', 'Freelance'] as $type)<option value="{{ $type }}" @selected(old('employment_type', $job->employment_type) === $type)>{{ $type }}</option>@endforeach</select>@error('employment_type')<small class="field-error">{{ $message }}</small>@enderror</div>
                            <div class="form-group"><label for="workplace">Workplace <span class="required">*</span></label><select id="workplace" name="workplace" required>@foreach(['Onsite', 'Remote', 'Hybrid'] as $workplace)<option value="{{ $workplace }}" @selected(old('workplace', $job->workplace) === $workplace)>{{ $workplace === 'Onsite' ? 'On-site' : $workplace }}</option>@endforeach</select>@error('workplace')<small class="field-error">{{ $message }}</small>@enderror</div>
                            <div class="form-group"><label for="experience_level">Experience Level <span class="required">*</span></label><select id="experience_level" name="experience_level" required>@foreach(['Fresher', 'Junior', 'Mid', 'Senior', 'Expert'] as $level)<option value="{{ $level }}" @selected(old('experience_level', $job->experience_level) === $level)>{{ $level === 'Mid' ? 'Mid Level' : $level }}</option>@endforeach</select>@error('experience_level')<small class="field-error">{{ $message }}</small>@enderror</div>
                        </div>
                    </div>
                </div>

                <div class="form-card">
                    <div class="form-card-header"><div class="form-section-icon"><i class="fa-solid fa-money-bill-wave" aria-hidden="true"></i></div><div><h2>Experience, Compensation &amp; Deadline</h2><p>Keep the experience level, salary range, vacancy count, and application deadline current.</p></div></div>
                    <div class="form-card-body">
                        <div class="form-grid three-columns">
                            <div class="form-group"><label for="minimum_experience">Minimum Experience (years)</label><input type="number" id="minimum_experience" name="minimum_experience" min="0" max="99" step="0.1" value="{{ old('minimum_experience', $job->minimum_experience ?? '0') }}">@error('minimum_experience')<small class="field-error">{{ $message }}</small>@enderror</div>
                            <div class="form-group"><label for="minimum_salary">Minimum Salary</label><input type="number" id="minimum_salary" name="minimum_salary" min="0" max="9999999999.99" step="0.01" value="{{ old('minimum_salary', $job->minimum_salary) }}">@error('minimum_salary')<small class="field-error">{{ $message }}</small>@enderror</div>
                            <div class="form-group"><label for="maximum_salary">Maximum Salary</label><input type="number" id="maximum_salary" name="maximum_salary" min="0" max="9999999999.99" step="0.01" value="{{ old('maximum_salary', $job->maximum_salary) }}">@error('maximum_salary')<small class="field-error">{{ $message }}</small>@enderror</div>
                            <div class="form-group"><label for="currency">Currency</label><select id="currency" name="currency">@foreach(['USD', 'NPR', 'EUR', 'GBP', 'INR'] as $currency)<option value="{{ $currency }}" @selected(old('currency', $job->currency ?? 'USD') === $currency)>{{ $currency }}</option>@endforeach</select>@error('currency')<small class="field-error">{{ $message }}</small>@enderror</div>
                            <div class="form-group"><label for="vacancy">Number of Vacancies</label><input type="number" id="vacancy" name="vacancy" min="1" max="999999" step="1" value="{{ old('vacancy', $job->vacancy ?? 1) }}">@error('vacancy')<small class="field-error">{{ $message }}</small>@enderror</div>
                            <div class="form-group"><label for="application_deadline">Application Deadline</label><input type="date" id="application_deadline" name="application_deadline" min="{{ now()->toDateString() }}" value="{{ old('application_deadline', $job->application_deadline) }}">@error('application_deadline')<small class="field-error">{{ $message }}</small>@enderror</div>
                        </div>
                    </div>
                </div>

                <div class="form-card">
                    <div class="form-card-header"><div class="form-section-icon"><i class="fa-solid fa-code" aria-hidden="true"></i></div><div><h2>Required Skills</h2><p>Update the technical and professional skills required for this position.</p></div></div>
                    <div class="form-card-body">
                        <div class="skills-header"><span>Select required skills</span><span class="selected-skills-count" id="selectedSkillsCount">{{ count($selectedSkillIds) }} selected</span></div>
                        @if($skills->isNotEmpty())
                            <div class="skills-grid">@foreach($skills as $skill)<label class="skill-checkbox"><input type="checkbox" name="skills[]" value="{{ $skill->skill_id }}" @checked(in_array((string) $skill->skill_id, $selectedSkillIds, true))><span class="skill-checkmark"><i class="fa-solid fa-check" aria-hidden="true"></i></span><span class="skill-name">{{ $skill->skill_name }}</span></label>@endforeach</div>
                        @else
                            <div class="no-skills-message"><i class="fa-solid fa-circle-info" aria-hidden="true"></i>No skills are currently available.</div>
                        @endif
                        @error('skills')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                </div>

                <div class="form-card">
                    <div class="form-card-header"><div class="form-section-icon"><i class="fa-solid fa-toggle-on" aria-hidden="true"></i></div><div><h2>Job Status</h2><p>Control the current state of your job posting.</p></div></div>
                    <div class="form-card-body">
                        <div class="status-options">
                            @foreach(['Draft' => 'Save without publishing', 'Open' => 'Visible to job seekers', 'Closed' => 'Stop accepting applications', 'Expired' => 'Mark the position as expired'] as $status => $description)
                                @php $statusIcon = ['Draft' => 'fa-file-pen', 'Open' => 'fa-circle-check', 'Closed' => 'fa-lock', 'Expired' => 'fa-clock-rotate-left'][$status]; @endphp
                                <label class="status-option status-option-{{ strtolower($status) }}"><input type="radio" name="job_status" value="{{ $status }}" @checked(old('job_status', $job->job_status) === $status)><span class="status-option-content"><span class="status-option-icon"><i class="fa-solid {{ $statusIcon }}" aria-hidden="true"></i></span><span class="status-option-text"><strong>{{ $status }}</strong><small>{{ $description }}</small></span><span class="radio-indicator"></span></span></label>
                            @endforeach
                        </div>
                        @error('job_status')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                </div>

                <div class="form-actions">
                    <a href="{{ route('employer.jobs') }}" class="btn btn-secondary"><i class="fa-solid fa-xmark" aria-hidden="true"></i>Cancel</a>
                    <button type="submit" class="btn btn-primary" id="updateJobButton"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>Update Job</button>
                </div>
            </form>
        </div>

        <aside class="edit-job-sidebar">
            <div class="sidebar-card">
                <div class="sidebar-card-header"><div class="sidebar-icon"><i class="fa-solid fa-circle-info" aria-hidden="true"></i></div><div><h3>Job Summary</h3><p>Current posting</p></div></div>
                <div class="job-summary"><div class="summary-item"><span>Job ID</span><strong>#{{ $job->job_id }}</strong></div><div class="summary-item"><span>Category</span><strong>{{ $job->category_name }}</strong></div><div class="summary-item"><span>Company</span><strong>{{ $job->company_name }}</strong></div><div class="summary-item"><span>Current Status</span><strong class="summary-status">{{ $job->job_status }}</strong></div></div>
            </div>
            <div class="sidebar-card">
                <div class="sidebar-card-header"><div class="sidebar-icon"><i class="fa-solid fa-building" aria-hidden="true"></i></div><div><h3>Company</h3><p>Posting company</p></div></div>
                @php $companyLogo = $job->logo && $job->logo !== 'company.png' ? route('company.logo', ['filename' => basename($job->logo)]) : asset('assets/images/company.png'); @endphp
                <div class="company-info"><div class="company-logo"><img src="{{ $companyLogo }}" alt="{{ $job->company_name }}" onerror="this.src='{{ asset('assets/images/company.png') }}'"></div><div class="company-details"><strong>{{ $job->company_name }}</strong><span class="company-status"><span class="status-dot"></span>{{ $job->company_status }}</span></div></div>
            </div>
            <div class="sidebar-card">
                <div class="sidebar-card-header"><div class="sidebar-icon"><i class="fa-solid fa-lightbulb" aria-hidden="true"></i></div><div><h3>Editing Tips</h3><p>Keep your listing effective</p></div></div>
                <ul class="tips-list"><li><i class="fa-solid fa-check" aria-hidden="true"></i><span>Keep job details accurate and current.</span></li><li><i class="fa-solid fa-check" aria-hidden="true"></i><span>Close the job when you stop accepting applications.</span></li><li><i class="fa-solid fa-check" aria-hidden="true"></i><span>Jobs with applications cannot be deleted.</span></li></ul>
            </div>
        </aside>
    </div>
</section>
@endsection

@section('js')
<script src="{{ asset('assets/js/employer/edit_job.js') }}"></script>
@endsection
