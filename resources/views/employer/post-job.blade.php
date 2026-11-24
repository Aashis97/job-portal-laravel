@extends('layouts.dashboard')

@section('title', 'Post a Job')
@section('role', 'Employer')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/employer/post_job.css') }}">
@endsection

@section('content')
@php
    $oldSkills = old('skills', []);
    $selectedSkillIds = [];
    if (is_array($oldSkills)) {
        foreach ($oldSkills as $skillId) {
            if (is_scalar($skillId)) {
                $selectedSkillIds[] = (string) $skillId;
            }
        }
    }
@endphp
<div class="post-job-page">
    <div class="post-job-heading">
        <div class="post-job-heading-left">
            <div class="post-job-heading-icon"><i class="fa-solid fa-briefcase" aria-hidden="true"></i></div>
            <div><h1>Post a New Job</h1><p>Create a professional job listing and find the right candidates.</p></div>
        </div>
        <a href="{{ route('employer.jobs') }}" class="back-dashboard-btn"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Back to Jobs</a>
    </div>

    @if(session('danger'))
        <div class="job-alert error" role="alert"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><span>{{ session('danger') }}</span></div>
    @endif
    @if($errors->any())
        <div class="job-alert error" role="alert"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><span>Please correct the highlighted form errors and try again.</span></div>
    @endif

    <div class="post-job-layout">
        <div class="job-form-card">
            <div class="job-form-header">
                <div><h2>Job Information</h2><p>Complete all required information before publishing.</p></div>
                <div class="required-label">* Required</div>
            </div>

            <form action="{{ route('employer.jobs.store') }}" method="POST" id="postJobForm" novalidate>
                @csrf
                <section class="job-form-section">
                    <div class="section-title"><div class="section-title-icon"><i class="fa-solid fa-circle-info" aria-hidden="true"></i></div><div><h3>Basic Information</h3><p>Enter the main information about this job.</p></div></div>
                    <div class="form-group">
                        <label for="job_title">Job Title <span>*</span></label>
                        <div class="input-wrapper"><i class="fa-solid fa-heading" aria-hidden="true"></i><input type="text" name="job_title" id="job_title" maxlength="150" required autocomplete="off" placeholder="e.g. Senior PHP Developer" value="{{ old('job_title') }}"></div>
                        @error('job_title')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label for="category_id">Job Category <span>*</span></label>
                        <div class="select-wrapper"><i class="fa-solid fa-layer-group" aria-hidden="true"></i><select name="category_id" id="category_id" required><option value="">Select job category</option>@foreach($categories as $category)<option value="{{ $category->category_id }}" @selected((string) old('category_id') === (string) $category->category_id)>{{ $category->category_name }}</option>@endforeach</select><span class="select-arrow"><i class="fa-solid fa-chevron-down" aria-hidden="true"></i></span></div>
                        @error('category_id')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label for="location">Job Location</label>
                        <div class="input-wrapper"><i class="fa-solid fa-location-dot" aria-hidden="true"></i><input type="text" name="location" id="location" maxlength="150" autocomplete="off" placeholder="e.g. Kathmandu, Nepal" value="{{ old('location') }}"></div>
                        @error('location')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                </section>

                <section class="job-form-section">
                    <div class="section-title"><div class="section-title-icon"><i class="fa-solid fa-align-left" aria-hidden="true"></i></div><div><h3>Job Description</h3><p>Describe the position, requirements and responsibilities.</p></div></div>
                    <div class="form-group"><label for="job_description">Job Description <span>*</span></label><div class="textarea-wrapper"><i class="fa-solid fa-file-lines" aria-hidden="true"></i><textarea name="job_description" id="job_description" maxlength="10000" rows="7" required placeholder="Describe the job position, purpose, work environment and what the selected candidate will do...">{{ old('job_description') }}</textarea></div>@error('job_description')<small class="field-error">{{ $message }}</small>@enderror</div>
                    <div class="form-group"><label for="job_requirements">Job Requirements</label><div class="textarea-wrapper"><i class="fa-solid fa-list-check" aria-hidden="true"></i><textarea name="job_requirements" id="job_requirements" maxlength="10000" rows="6" placeholder="Enter qualifications, education, technical skills, certifications or other requirements...">{{ old('job_requirements') }}</textarea></div>@error('job_requirements')<small class="field-error">{{ $message }}</small>@enderror</div>
                    <div class="form-group"><label for="job_responsibilities">Job Responsibilities</label><div class="textarea-wrapper"><i class="fa-solid fa-tasks" aria-hidden="true"></i><textarea name="job_responsibilities" id="job_responsibilities" maxlength="10000" rows="6" placeholder="List the main responsibilities and day-to-day duties for this position...">{{ old('job_responsibilities') }}</textarea></div>@error('job_responsibilities')<small class="field-error">{{ $message }}</small>@enderror</div>
                </section>

                <section class="job-form-section">
                    <div class="section-title"><div class="section-title-icon"><i class="fa-solid fa-briefcase" aria-hidden="true"></i></div><div><h3>Employment Details</h3><p>Specify employment type, workplace and experience requirements.</p></div></div>
                    <div class="form-row">
                        <div class="form-group"><label for="employment_type">Employment Type <span>*</span></label><div class="select-wrapper"><i class="fa-solid fa-clock" aria-hidden="true"></i><select name="employment_type" id="employment_type" required><option value="">Select employment type</option>@foreach(['Full-Time', 'Part-Time', 'Contract', 'Internship', 'Freelance'] as $type)<option value="{{ $type }}" @selected(old('employment_type') === $type)>{{ $type }}</option>@endforeach</select><span class="select-arrow"><i class="fa-solid fa-chevron-down" aria-hidden="true"></i></span></div>@error('employment_type')<small class="field-error">{{ $message }}</small>@enderror</div>
                        <div class="form-group"><label for="workplace">Workplace <span>*</span></label><div class="select-wrapper"><i class="fa-solid fa-building" aria-hidden="true"></i><select name="workplace" id="workplace" required>@foreach(['Onsite' => 'On-site', 'Remote' => 'Remote', 'Hybrid' => 'Hybrid'] as $value => $label)<option value="{{ $value }}" @selected(old('workplace', 'Onsite') === $value)>{{ $label }}</option>@endforeach</select><span class="select-arrow"><i class="fa-solid fa-chevron-down" aria-hidden="true"></i></span></div>@error('workplace')<small class="field-error">{{ $message }}</small>@enderror</div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label for="experience_level">Experience Level <span>*</span></label><div class="select-wrapper"><i class="fa-solid fa-user-tie" aria-hidden="true"></i><select name="experience_level" id="experience_level" required>@foreach(['Fresher', 'Junior', 'Mid', 'Senior', 'Expert'] as $level)<option value="{{ $level }}" @selected(old('experience_level', 'Junior') === $level)>{{ $level === 'Mid' ? 'Mid Level' : $level }}</option>@endforeach</select><span class="select-arrow"><i class="fa-solid fa-chevron-down" aria-hidden="true"></i></span></div>@error('experience_level')<small class="field-error">{{ $message }}</small>@enderror</div>
                        <div class="form-group"><label for="minimum_experience">Minimum Experience (Years)</label><div class="input-wrapper"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i><input type="number" name="minimum_experience" id="minimum_experience" min="0" max="99" step="0.1" inputmode="decimal" placeholder="e.g. 2" value="{{ old('minimum_experience', '0') }}"></div>@error('minimum_experience')<small class="field-error">{{ $message }}</small>@enderror</div>
                    </div>
                </section>

                <section class="job-form-section">
                    <div class="section-title"><div class="section-title-icon"><i class="fa-solid fa-money-bill-wave" aria-hidden="true"></i></div><div><h3>Salary &amp; Vacancy</h3><p>Add compensation information and the number of available positions.</p></div></div>
                    <div class="form-row">
                        <div class="form-group"><label for="minimum_salary">Minimum Salary</label><div class="input-wrapper"><i class="fa-solid fa-money-bill" aria-hidden="true"></i><input type="number" name="minimum_salary" id="minimum_salary" min="0" max="9999999999.99" step="0.01" inputmode="decimal" placeholder="e.g. 50000" value="{{ old('minimum_salary') }}"></div>@error('minimum_salary')<small class="field-error">{{ $message }}</small>@enderror</div>
                        <div class="form-group"><label for="maximum_salary">Maximum Salary</label><div class="input-wrapper"><i class="fa-solid fa-money-check-dollar" aria-hidden="true"></i><input type="number" name="maximum_salary" id="maximum_salary" min="0" max="9999999999.99" step="0.01" inputmode="decimal" placeholder="e.g. 100000" value="{{ old('maximum_salary') }}"></div>@error('maximum_salary')<small class="field-error">{{ $message }}</small>@enderror</div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label for="currency">Currency</label><div class="select-wrapper"><i class="fa-solid fa-coins" aria-hidden="true"></i><select name="currency" id="currency">@foreach(['USD' => 'US Dollar', 'NPR' => 'Nepalese Rupee', 'EUR' => 'Euro', 'GBP' => 'Pound Sterling', 'INR' => 'Indian Rupee'] as $code => $name)<option value="{{ $code }}" @selected(old('currency', 'USD') === $code)>{{ $code }} - {{ $name }}</option>@endforeach</select><span class="select-arrow"><i class="fa-solid fa-chevron-down" aria-hidden="true"></i></span></div>@error('currency')<small class="field-error">{{ $message }}</small>@enderror</div>
                        <div class="form-group"><label for="vacancy">Number of Vacancies</label><div class="input-wrapper"><i class="fa-solid fa-users" aria-hidden="true"></i><input type="number" name="vacancy" id="vacancy" min="1" max="999999" step="1" inputmode="numeric" placeholder="e.g. 3" value="{{ old('vacancy', '1') }}"></div>@error('vacancy')<small class="field-error">{{ $message }}</small>@enderror</div>
                    </div>
                </section>

                <section class="job-form-section">
                    <div class="section-title"><div class="section-title-icon"><i class="fa-solid fa-calendar-days" aria-hidden="true"></i></div><div><h3>Application Details</h3><p>Set the application deadline and publishing status.</p></div></div>
                    <div class="form-row">
                        <div class="form-group"><label for="application_deadline">Application Deadline</label><div class="input-wrapper"><i class="fa-solid fa-calendar-day" aria-hidden="true"></i><input type="date" name="application_deadline" id="application_deadline" min="{{ now()->toDateString() }}" value="{{ old('application_deadline') }}"></div>@error('application_deadline')<small class="field-error">{{ $message }}</small>@enderror</div>
                        <div class="form-group"><label>Publishing Status <span>*</span></label><div class="status-options">
                            <label class="status-option"><input type="radio" name="job_status" value="Draft" @checked(old('job_status', 'Draft') === 'Draft')><span class="status-option-content"><span class="status-option-icon draft"><i class="fa-solid fa-file" aria-hidden="true"></i></span><span><strong>Save as Draft</strong><small>Publish the job later.</small></span></span></label>
                            <label class="status-option"><input type="radio" name="job_status" value="Open" @checked(old('job_status') === 'Open')><span class="status-option-content"><span class="status-option-icon open"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></span><span><strong>Publish Job</strong><small>Make the job visible to candidates.</small></span></span></label>
                        </div>@error('job_status')<small class="field-error">{{ $message }}</small>@enderror</div>
                    </div>
                </section>

                <section class="job-form-section">
                    <div class="section-title"><div class="section-title-icon"><i class="fa-solid fa-code" aria-hidden="true"></i></div><div><h3>Required Skills</h3><p>Select the skills required for this position.</p></div></div>
                    @if($skills->isNotEmpty())
                        <div class="skills-selection">@foreach($skills as $skill)<label class="skill-checkbox"><input type="checkbox" name="skills[]" value="{{ $skill->skill_id }}" @checked(in_array((string) $skill->skill_id, $selectedSkillIds, true))><span class="skill-checkmark"><i class="fa-solid fa-check" aria-hidden="true"></i></span><span class="skill-name">{{ $skill->skill_name }}</span></label>@endforeach</div>
                    @else
                        <div class="no-skills"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i><span>No skills are currently available. Please add skills to the database first.</span></div>
                    @endif
                    @error('skills')<small class="field-error">{{ $message }}</small>@enderror
                </section>

                <div class="job-form-actions">
                    <a href="{{ route('employer.jobs') }}" class="cancel-job-btn"><i class="fa-solid fa-xmark" aria-hidden="true"></i>Cancel</a>
                    <button type="reset" class="reset-job-btn"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i>Reset</button>
                    <button type="submit" class="submit-job-btn" id="submitJobBtn"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i>Save Job</button>
                </div>
            </form>
        </div>

        <aside class="job-post-sidebar">
            <div class="job-side-card">
                <div class="side-card-header"><div class="side-card-icon"><i class="fa-solid fa-building" aria-hidden="true"></i></div><div><h3>Posting Company</h3><p>Your approved company</p></div></div>
                @php $companyLogo = $company->logo && $company->logo !== 'company.png' ? route('company.logo', ['filename' => basename($company->logo)]) : asset('assets/images/company.png'); @endphp
                <div class="posting-company"><div class="posting-company-logo"><img src="{{ $companyLogo }}" alt="{{ $company->company_name }}" onerror="this.src='{{ asset('assets/images/company.png') }}'"></div><div><strong>{{ $company->company_name }}</strong><span><i class="fa-solid fa-circle-check" aria-hidden="true"></i>Approved</span></div></div>
            </div>
            <div class="job-side-card">
                <div class="side-card-header"><div class="side-card-icon"><i class="fa-solid fa-lightbulb" aria-hidden="true"></i></div><div><h3>Posting Tips</h3><p>Create better job listings</p></div></div>
                <ul class="job-tips"><li><i class="fa-solid fa-check" aria-hidden="true"></i><span>Use a clear and specific job title.</span></li><li><i class="fa-solid fa-check" aria-hidden="true"></i><span>Write a detailed and easy-to-understand description.</span></li><li><i class="fa-solid fa-check" aria-hidden="true"></i><span>Add relevant skills to improve candidate matching.</span></li><li><i class="fa-solid fa-check" aria-hidden="true"></i><span>Provide realistic experience and salary information.</span></li><li><i class="fa-solid fa-check" aria-hidden="true"></i><span>Set an appropriate application deadline.</span></li></ul>
            </div>
            <div class="job-side-card ai-info-card"><div class="ai-info-icon"><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i></div><h3>AI Candidate Matching</h3><p>Your job requirements and selected skills can be used by the candidate matching system to calculate skill compatibility and support recruiter review.</p></div>
        </aside>
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('assets/js/employer/post_job.js') }}"></script>
@endsection
