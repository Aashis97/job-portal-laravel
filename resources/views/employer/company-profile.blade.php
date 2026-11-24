@extends('layouts.dashboard')

@section('title', 'Company Profile')
@section('role', 'Employer')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/employer/company_profile.css') }}">
@endsection

@section('content')
<div class="company-profile-page">
    <div class="page-heading">
        <div class="page-heading-title">
            <span class="page-heading-icon"><i class="fa-solid fa-building" aria-hidden="true"></i></span>
            <div><h1>Company Profile</h1><p>Manage your company information and employer profile.</p></div>
        </div>
        <a href="{{ route('employer.dashboard') }}" class="back-dashboard-btn"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Dashboard</a>
    </div>

    @if(session('company_profile_success'))
        <div class="profile-alert success"><i class="fa-solid fa-circle-check" aria-hidden="true"></i><span>{{ session('company_profile_success') }}</span><button type="button" class="alert-close" aria-label="Close"><i class="fa-solid fa-xmark"></i></button></div>
    @endif
    @if(session('company_profile_error'))
        <div class="profile-alert error"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><span>{{ session('company_profile_error') }}</span><button type="button" class="alert-close" aria-label="Close"><i class="fa-solid fa-xmark"></i></button></div>
    @endif
    @if($errors->any())
        <div class="profile-alert error"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><span>{{ implode(' ', $errors->all()) }}</span></div>
    @endif

    <div class="company-profile-grid">
        <aside class="company-preview-card">
            <div class="company-preview-top">
                <div class="company-logo-wrapper"><img src="{{ $logoUrl }}" alt="Company Logo" class="company-logo" id="companyLogoPreview" onerror="this.src='{{ asset('assets/images/company.png') }}';"></div>
                @php
                    $statusIcon = match ($company->status) {
                        'Approved' => 'fa-solid fa-circle-check',
                        'Rejected' => 'fa-solid fa-circle-xmark',
                        'Suspended' => 'fa-solid fa-ban',
                        default => 'fa-solid fa-clock',
                    };
                @endphp
                <span class="company-status {{ $statusClass }}"><i class="{{ $statusIcon }}" aria-hidden="true"></i>{{ $company->status }}</span>
            </div>
            <div class="company-preview-content">
                <h2>{{ $company->company_name !== '' ? $company->company_name : 'Your Company' }}</h2>
                <p><i class="fa-solid fa-envelope" aria-hidden="true"></i>{{ $employer->email }}</p>
            </div>
            <div class="company-preview-footer">
                <div class="preview-info"><span>Employer ID</span><strong>#{{ $employer->employer_id }}</strong></div>
                <div class="preview-info"><span>Company ID</span><strong>{{ $employer->company_id ? '#' . $employer->company_id : 'Not Assigned' }}</strong></div>
            </div>
        </aside>

        <div class="company-form-card">
            <div class="form-card-header">
                <div><h2>Company Information</h2><p>Keep your company information up to date.</p></div>
                <span class="required-note">* Required</span>
            </div>

            <form action="{{ route('employer.company-profile.update') }}" method="POST" enctype="multipart/form-data" id="companyProfileForm" novalidate>
                @csrf

                <div class="form-section">
                    <div class="section-heading">
                        <span class="section-icon"><i class="fa-solid fa-image" aria-hidden="true"></i></span>
                        <div><h3>Company Logo</h3><p>Upload a professional logo for your company.</p></div>
                    </div>
                    <div class="logo-upload-area">
                        <div class="logo-preview-box"><img src="{{ $logoUrl }}" alt="Company Logo Preview" id="logoPreview" onerror="this.src='{{ asset('assets/images/company.png') }}';"></div>
                        <div class="logo-upload-content">
                            <label for="company_logo" class="upload-logo-btn"><i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i>Choose Logo</label>
                            <input type="file" name="company_logo" id="company_logo" accept=".jpg,.jpeg,.png,.webp">
                            <p>JPG, JPEG, PNG or WEBP. Maximum size: 2 MB.</p>
                            <span id="logoFileName" class="selected-file-name"></span>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="section-heading">
                        <span class="section-icon"><i class="fa-solid fa-address-card" aria-hidden="true"></i></span>
                        <div><h3>Company Contact</h3><p>Contact details candidates and administrators can use to reach your organization.</p></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="company_email">Company Email <span>*</span></label>
                            <div class="input-wrapper"><i class="fa-solid fa-envelope"></i><input type="email" name="company_email" id="company_email" value="{{ old('company_email', $company->company_email) }}" maxlength="150" required></div>
                        </div>
                        <div class="form-group">
                            <label for="company_phone">Company Phone</label>
                            <div class="input-wrapper"><i class="fa-solid fa-phone"></i><input type="tel" name="company_phone" id="company_phone" value="{{ old('company_phone', $company->company_phone) }}" maxlength="20"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="company_website">Website</label>
                        <div class="input-wrapper"><i class="fa-solid fa-globe"></i><input type="url" name="company_website" id="company_website" value="{{ old('company_website', $company->website) }}" maxlength="255" placeholder="https://example.com"></div>
                    </div>
                    <div class="form-group"><label for="company_description">Company Description</label><textarea name="company_description" id="company_description" rows="5" maxlength="5000" placeholder="Tell candidates about your company">{{ old('company_description', $company->description) }}</textarea></div>
                    <div class="form-group"><label for="company_address">Company Address</label><textarea name="company_address" id="company_address" rows="3" maxlength="1000" placeholder="Enter your company address">{{ old('company_address', $company->address) }}</textarea></div>
                </div>

                <div class="form-section">
                    <div class="section-heading">
                        <span class="section-icon"><i class="fa-solid fa-building" aria-hidden="true"></i></span>
                        <div><h3>Company Details</h3><p>Basic information about your organization.</p></div>
                    </div>
                    <div class="form-group">
                        <label for="company_name">Company Name <span>*</span></label>
                        <div class="input-wrapper"><i class="fa-solid fa-building"></i><input type="text" name="company_name" id="company_name" value="{{ old('company_name', $company->company_name) }}" placeholder="Enter company name" maxlength="150" required></div>
                        <small class="field-help">Enter the official name of your company.</small>
                    </div>
                </div>

                <div class="form-section">
                    <div class="section-heading">
                        <span class="section-icon"><i class="fa-solid fa-user-tie" aria-hidden="true"></i></span>
                        <div><h3>Employer Information</h3><p>Your role and department within the company.</p></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label for="designation">Designation</label><div class="input-wrapper"><i class="fa-solid fa-id-badge"></i><input type="text" name="designation" id="designation" value="{{ old('designation', $employer->designation) }}" placeholder="e.g. HR Manager" maxlength="100"></div></div>
                        <div class="form-group"><label for="department">Department</label><div class="input-wrapper"><i class="fa-solid fa-sitemap"></i><input type="text" name="department" id="department" value="{{ old('department', $employer->department) }}" placeholder="e.g. Human Resources" maxlength="100"></div></div>
                    </div>
                    <div class="email-information">
                        <div class="email-information-icon"><i class="fa-solid fa-envelope"></i></div>
                        <div><strong>Login Email</strong><span>{{ $employer->email }}</span></div>
                    </div>
                </div>

                @if($employer->company_id)
                    @php
                        $statusMessage = match ($company->status) {
                            'Approved' => 'Your company profile has been approved.',
                            'Rejected' => 'Your company profile was rejected. Update the information and resubmit it for verification.',
                            'Suspended' => 'Your company account is currently suspended.',
                            default => 'Your company profile is waiting for administrator approval.',
                        };
                    @endphp
                    <div class="status-information">
                        <div class="status-information-icon"><i class="fa-solid fa-shield-halved"></i></div>
                        <div><strong>Company Verification Status</strong><p>{{ $statusMessage }}</p></div>
                        <span class="status-pill {{ $statusClass }}">{{ $company->status }}</span>
                    </div>
                @endif

                <div class="form-actions">
                    <a href="{{ route('employer.dashboard') }}" class="cancel-btn">Cancel</a>
                    <button type="submit" class="save-profile-btn" id="saveProfileBtn"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>Save Changes</button>
                    @if($company->status === 'Rejected')
                        <button type="submit" name="submit_for_verification" value="1" class="save-profile-btn"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i>Save &amp; resubmit for verification</button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('assets/js/employer/company_profile.js') }}"></script>
@endsection
