@extends('layouts.dashboard')

@section('title', 'Profile Image')
@section('role', 'Employer')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/employer/profile_image.css') }}">
@endsection

@section('sidebar')
<div class="sidebar-section"><div class="sidebar-section-title">MAIN</div><a href="{{ route('employer.dashboard') }}" class="sidebar-link"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a></div>
<div class="sidebar-section"><div class="sidebar-section-title">RECRUITMENT</div><a href="{{ route('employer.jobs') }}" class="sidebar-link"><i class="fa-solid fa-briefcase"></i><span>Manage Jobs</span></a><a href="{{ route('employer.applications') }}" class="sidebar-link"><i class="fa-solid fa-users"></i><span>Applicants</span></a><a href="{{ route('employer.interviews') }}" class="sidebar-link"><i class="fa-solid fa-calendar-check"></i><span>Interviews</span></a><a href="{{ route('employer.analytics') }}" class="sidebar-link"><i class="fa-solid fa-chart-line"></i><span>Analytics</span></a></div>
<div class="sidebar-section"><div class="sidebar-section-title">COMPANY</div><a href="{{ route('employer.company-profile') }}" class="sidebar-link"><i class="fa-solid fa-building-user"></i><span>Company Profile</span></a></div>
<div class="sidebar-section"><div class="sidebar-section-title">ACCOUNT</div><a href="{{ route('employer.profile-image.form') }}" class="sidebar-link active"><i class="fa-solid fa-image"></i><span>Profile Image</span></a></div>
@endsection

@section('content')
<main class="employer-profile-image-page">
    <section class="profile-image-page-header">
        <div class="page-header-content">
            <div class="page-header-icon"><i class="fa-solid fa-image" aria-hidden="true"></i></div>
            <div><span class="page-header-label">Employer Account</span><h1>Profile Image</h1><p>Upload a professional profile image for your employer account.</p></div>
        </div>
        <a href="{{ route('employer.dashboard') }}" class="back-settings-btn"><i class="fa-solid fa-arrow-left"></i><span>Dashboard</span></a>
    </section>

    @if(session('profile_image_success'))
        <div class="profile-alert profile-alert-success"><i class="fa-solid fa-circle-check"></i><div><strong>Success</strong><p>{{ session('profile_image_success') }}</p></div><button type="button" class="alert-close" aria-label="Close"><i class="fa-solid fa-xmark"></i></button></div>
    @endif
    @if(session('profile_image_error') || $errors->any())
        <div class="profile-alert profile-alert-error"><i class="fa-solid fa-circle-exclamation"></i><div><strong>Upload Error</strong><p>{{ session('profile_image_error') ?? implode(' ', $errors->all()) }}</p></div><button type="button" class="alert-close" aria-label="Close"><i class="fa-solid fa-xmark"></i></button></div>
    @endif

    <section class="profile-image-card">
        <div class="profile-image-card-header">
            <div><span class="section-label">Account Photo</span><h2>Update Profile Image</h2><p>Your profile image helps employers and candidates recognize your account across the portal.</p></div>
            <div class="header-badge"><i class="fa-solid fa-shield-halved"></i> Secure Upload</div>
        </div>

        <div class="profile-image-layout">
            <div class="current-image-section">
                <div class="image-preview-wrapper"><div class="image-preview-ring"><img src="{{ $profileImageUrl }}" alt="Employer profile image" id="profileImagePreview" class="profile-image-preview" onerror="this.src='{{ asset('assets/images/default-profile.svg') }}';"></div><div class="image-status-badge"><i class="fa-solid fa-camera"></i></div></div>
                <div class="current-image-info"><h3>{{ $user->full_name }}</h3><p>{{ $user->email }}</p><span class="image-status"><i class="fa-solid fa-circle"></i> Profile Image</span></div>
            </div>

            <div class="upload-section">
                <form action="{{ route('employer.profile-image.update') }}" method="POST" enctype="multipart/form-data" id="profileImageForm" class="profile-image-form">
                    @csrf
                    <div class="upload-dropzone" id="uploadDropzone">
                        <input type="file" name="profile_image" id="profile_image" class="file-input" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required>
                        <div class="upload-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                        <h3>Choose your profile image</h3>
                        <p>Drag &amp; drop your image here or <span>browse files</span></p>
                        <small>JPG, JPEG, PNG or WEBP · Maximum 5 MB</small>
                    </div>
                    <div class="selected-file" id="selectedFile" hidden>
                        <div class="selected-file-icon"><i class="fa-solid fa-file-image"></i></div>
                        <div class="selected-file-details"><strong id="selectedFileName">No file selected</strong><span id="selectedFileSize">0 KB</span></div>
                        <button type="button" id="removeSelectedFile" class="remove-file-btn" aria-label="Remove selected image"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                    <div class="upload-requirements">
                        <div class="requirement-title"><i class="fa-solid fa-circle-info"></i> Upload Requirements</div>
                        <ul><li><i class="fa-solid fa-check"></i> JPG, JPEG, PNG or WEBP format</li><li><i class="fa-solid fa-check"></i> Maximum file size: 5 MB</li><li><i class="fa-solid fa-check"></i> Recommended: square image</li><li><i class="fa-solid fa-check"></i> Minimum recommended size: 200 × 200 px</li></ul>
                    </div>
                    <div class="form-actions">
                        <a href="{{ route('employer.dashboard') }}" class="btn-secondary"><i class="fa-solid fa-xmark"></i> Cancel</a>
                        <button type="submit" class="btn-primary" id="uploadImageBtn"><i class="fa-solid fa-cloud-arrow-up"></i> Upload Image</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <section class="security-info-card"><div class="security-icon"><i class="fa-solid fa-lock"></i></div><div class="security-content"><h3>Your image is securely uploaded</h3><p>Uploaded images are validated by the server before being stored. Only supported image formats and safe file names are accepted.</p></div></section>
</main>
@endsection

@section('js')
<script src="{{ asset('assets/js/employer/profile_image.js') }}" defer></script>
@endsection
