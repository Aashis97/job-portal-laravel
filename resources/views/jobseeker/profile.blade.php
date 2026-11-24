@extends('layouts.dashboard')

@section('title', 'My Profile')
@section('role', 'Job Seeker')
@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/jobseeker/profile.css') }}">
@endsection

@section('content')
<section class="jobseeker-profile-page">
    <div class="page-header">
        <div>
            <span class="page-eyebrow">JOB SEEKER</span>
            <h1>My Profile</h1>
            <p>Keep your professional information updated to improve your job opportunities.</p>
        </div>
        <div class="profile-completion-summary">
            <div class="completion-summary-icon"><i class="fa-solid fa-user-check" aria-hidden="true"></i></div>
            <div><span>Profile Completion</span><strong>{{ $profileCompletion }}%</strong></div>
        </div>
    </div>

    @foreach(['success', 'danger', 'warning', 'info'] as $type)
        @if(session($type))
            <div class="profile-alert profile-alert-{{ $type }}">
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                <span>{{ session($type) }}</span>
            </div>
        @endif
    @endforeach
    @if($errors->any())
        <div class="profile-alert profile-alert-danger" role="alert">
            <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <div class="profile-progress-card">
        <div class="profile-progress-header">
            <div><span class="profile-section-label">Profile Status</span><h2>{{ $profileCompletion }}% Complete</h2></div>
            <span class="profile-progress-percentage">{{ $profileCompletion }}%</span>
        </div>
        <div class="profile-progress-bar" role="progressbar" aria-valuenow="{{ $profileCompletion }}" aria-valuemin="0" aria-valuemax="100">
            <div class="profile-progress-fill" style="width: {{ $profileCompletion }}%;"></div>
        </div>
        <p>{{ $profileCompletion >= 100 ? 'Your profile is complete. You are ready to explore job opportunities.' : 'Complete your profile so employers can better understand your experience and skills.' }}</p>
    </div>

    <form action="{{ route('jobseeker.profile.update') }}" method="POST" enctype="multipart/form-data" class="profile-form" id="jobseekerProfileForm">
        @csrf

        <div class="profile-card">
            <div class="profile-card-header">
                <div class="profile-card-icon"><i class="fa-solid fa-camera" aria-hidden="true"></i></div>
                <div><span>Personalize Your Profile</span><h2>Profile Photo</h2></div>
            </div>
            <div class="profile-photo-upload">
                <img src="{{ $profileImageUrl }}" alt="Current profile photo" class="profile-photo-preview" onerror="this.onerror=null;this.src='{{ asset('assets/images/default-profile.svg') }}'">
                <div class="profile-photo-controls">
                    <label for="profile_image">Upload a new photo</label>
                    <input type="file" id="profile_image" name="profile_image" accept="image/jpeg,image/png,image/webp">
                    <small>JPG, PNG, or WEBP. Maximum size 5 MB; minimum dimensions 100 × 100 pixels.</small>
                </div>
            </div>
        </div>

        <div class="profile-card">
            <div class="profile-card-header">
                <div class="profile-card-icon"><i class="fa-solid fa-user" aria-hidden="true"></i></div>
                <div><span>Personal Information</span><h2>Basic Information</h2></div>
            </div>
            <div class="profile-form-grid">
                <div class="profile-form-group">
                    <label for="full_name">Full Name</label>
                    <div class="profile-input-wrapper"><i class="fa-solid fa-user" aria-hidden="true"></i><input id="full_name" value="{{ $user->full_name }}" readonly></div>
                    <small>Name is managed from your account settings.</small>
                </div>
                <div class="profile-form-group">
                    <label for="email">Email Address</label>
                    <div class="profile-input-wrapper"><i class="fa-solid fa-envelope" aria-hidden="true"></i><input id="email" type="email" value="{{ $user->email }}" readonly></div>
                    <small>Email is managed from your account settings.</small>
                </div>
                <div class="profile-form-group">
                    <label for="date_of_birth">Date of Birth</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth', $profile->date_of_birth) }}">
                </div>
                <div class="profile-form-group">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender">
                        <option value="">Select Gender</option>
                        @foreach(['Male', 'Female', 'Other'] as $gender)
                            <option value="{{ $gender }}" @selected(old('gender', $profile->gender) === $gender)>{{ $gender }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="profile-form-group">
                    <label for="phone">Phone Number</label>
                    <div class="profile-input-wrapper"><i class="fa-solid fa-phone" aria-hidden="true"></i><input id="phone" value="{{ $user->phone }}" readonly></div>
                    <small>Phone is managed from your account settings.</small>
                </div>
                <div class="profile-form-group profile-form-group-full">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="3" maxlength="500" placeholder="Enter your full address">{{ old('address', $profile->address) }}</textarea>
                </div>
                <div class="profile-form-group"><label for="city">City</label><input id="city" name="city" maxlength="100" value="{{ old('city', $profile->city) }}" placeholder="e.g. Kathmandu"></div>
                <div class="profile-form-group"><label for="state">State / Province</label><input id="state" name="state" maxlength="100" value="{{ old('state', $profile->state) }}" placeholder="e.g. Bagmati"></div>
                <div class="profile-form-group"><label for="country">Country</label><input id="country" name="country" maxlength="100" value="{{ old('country', $profile->country) }}" placeholder="e.g. Nepal"></div>
                <div class="profile-form-group"><label for="postal_code">Postal Code</label><input id="postal_code" name="postal_code" maxlength="20" value="{{ old('postal_code', $profile->postal_code) }}" placeholder="Postal code"></div>
            </div>
        </div>

        <div class="profile-card">
            <div class="profile-card-header">
                <div class="profile-card-icon"><i class="fa-solid fa-briefcase" aria-hidden="true"></i></div>
                <div><span>Career</span><h2>Professional Information</h2></div>
            </div>
            <div class="profile-form-grid">
                <div class="profile-form-group"><label for="education_level">Education Level</label><input id="education_level" name="education_level" maxlength="150" value="{{ old('education_level', $profile->education_level) }}" placeholder="e.g. Bachelor's Degree"></div>
                <div class="profile-form-group"><label for="years_of_experience">Years of Experience</label><input type="number" id="years_of_experience" name="years_of_experience" min="0" max="999.9" step="0.1" value="{{ old('years_of_experience', $profile->years_of_experience ?? '0') }}" placeholder="e.g. 2.5"></div>
                <div class="profile-form-group"><label for="current_job_title">Current Job Title</label><input id="current_job_title" name="current_job_title" maxlength="150" value="{{ old('current_job_title', $profile->current_job_title) }}" placeholder="e.g. PHP Developer"></div>
                <div class="profile-form-group"><label for="expected_salary">Expected Salary</label><input type="number" id="expected_salary" name="expected_salary" min="0" step="0.01" value="{{ old('expected_salary', $profile->expected_salary) }}" placeholder="Expected salary"></div>
                <div class="profile-form-group">
                    <label for="availability">Availability</label>
                    <select id="availability" name="availability">
                        <option value="">Select Availability</option>
                        @foreach(['Immediately', '15 Days', '30 Days', '60 Days'] as $availability)
                            <option value="{{ $availability }}" @selected(old('availability', $profile->availability) === $availability)>{{ $availability }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="profile-form-group profile-form-group-full">
                    <label for="profile_summary">Professional Summary</label>
                    <textarea id="profile_summary" name="profile_summary" rows="6" maxlength="3000" placeholder="Write a short professional summary about yourself...">{{ old('profile_summary', $profile->profile_summary) }}</textarea>
                    <small>Maximum 3000 characters.</small>
                </div>
            </div>
        </div>

        <div class="profile-card">
            <div class="profile-card-header">
                <div class="profile-card-icon"><i class="fa-solid fa-link" aria-hidden="true"></i></div>
                <div><span>Online Presence</span><h2>Social & Professional Links</h2></div>
            </div>
            <div class="profile-form-grid">
                <div class="profile-form-group">
                    <label for="linkedin_url">LinkedIn URL</label>
                    <div class="profile-input-wrapper"><i class="fa-brands fa-linkedin" aria-hidden="true"></i><input type="url" id="linkedin_url" name="linkedin_url" maxlength="255" value="{{ old('linkedin_url', $profile->linkedin_url) }}" placeholder="https://linkedin.com/in/username"></div>
                </div>
                <div class="profile-form-group">
                    <label for="github_url">GitHub URL</label>
                    <div class="profile-input-wrapper"><i class="fa-brands fa-github" aria-hidden="true"></i><input type="url" id="github_url" name="github_url" maxlength="255" value="{{ old('github_url', $profile->github_url) }}" placeholder="https://github.com/username"></div>
                </div>
                <div class="profile-form-group profile-form-group-full">
                    <label for="portfolio_url">Portfolio URL</label>
                    <div class="profile-input-wrapper"><i class="fa-solid fa-globe" aria-hidden="true"></i><input type="url" id="portfolio_url" name="portfolio_url" maxlength="255" value="{{ old('portfolio_url', $profile->portfolio_url) }}" placeholder="https://yourportfolio.com"></div>
                </div>
            </div>
        </div>

        <div class="profile-card profile-resume-card">
            <div class="profile-card-header">
                <div class="profile-card-icon"><i class="fa-solid fa-file-lines" aria-hidden="true"></i></div>
                <div><span>Resume</span><h2>Resume Status</h2></div>
            </div>
            @if($resume)
                <div class="profile-resume-status uploaded">
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                    <div><strong>Resume uploaded</strong><span>{{ $resume->resume_title ?: $resume->resume_file }}</span></div>
                </div>
            @else
                <div class="profile-resume-status empty">
                    <i class="fa-solid fa-file-arrow-up" aria-hidden="true"></i>
                    <div><strong>No resume uploaded</strong><span>Upload your resume from the My Resume page.</span></div>
                </div>
            @endif
            <a href="{{ route('jobseeker.resume') }}" class="profile-secondary-button">
                <i class="fa-solid fa-file-arrow-up" aria-hidden="true"></i>Manage Resume
            </a>
        </div>

        <div class="profile-form-actions">
            <a href="{{ route('jobseeker.dashboard') }}" class="profile-cancel-button">Cancel</a>
            <button type="submit" class="profile-save-button" id="profileSaveButton"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>Save Profile</button>
        </div>
    </form>
</section>
@endsection
