@extends('layouts.public')
@section('title', 'Register - Online Job Portal')
@section('content')
<div class="auth-container auth-page-register">
    <div class="left-panel"><div class="brand-content"><div class="brand-logo"><i class="fa-solid fa-user-plus"></i></div><span class="brand-kicker">YOUR NEXT CHAPTER STARTS HERE</span><h1 class="brand-title">Online Job Portal</h1><p class="brand-description">Join thousands of job seekers and employers.</p></div></div>
    <div class="right-panel"><div class="auth-card">
        <div class="auth-logo"><i class="fa-solid fa-user-plus"></i></div><span class="auth-card-kicker">GET STARTED</span><h1 class="auth-title">Create Account</h1><p class="auth-subtitle">Choose your account type and create your profile.</p>
        @if($errors->any())<div class="server-errors">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        @if(session('errors_list'))<div class="server-errors">@foreach(session('errors_list') as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <form action="{{ route('register.submit') }}" method="POST" id="registerForm" enctype="multipart/form-data">
            @csrf
            <div class="form-group"><label for="full_name">Full Name</label><div class="input-group"><i class="fa-solid fa-user"></i><input type="text" id="full_name" name="full_name" value="{{ old('full_name') }}" maxlength="100" required></div></div>
            <div class="form-group"><label for="gender">Gender (optional)</label><div class="input-group"><i class="fa-solid fa-venus-mars"></i><select id="gender" name="gender"><option value="">Select gender</option><option value="Male" @selected(old('gender') === 'Male')>Male</option><option value="Female" @selected(old('gender') === 'Female')>Female</option><option value="Other" @selected(old('gender') === 'Other')>Other</option></select></div></div>
            <div class="form-group"><label for="email">Email Address</label><div class="input-group"><i class="fa-solid fa-envelope"></i><input type="email" id="email" name="email" value="{{ old('email') }}" maxlength="150" required></div></div>
            <div class="form-group"><label>Register As</label><div class="role-selection"><label class="role-card"><input type="radio" name="role" value="job_seeker" @checked(old('role', 'job_seeker') === 'job_seeker')><i class="fa-solid fa-user"></i><span>Job Seeker</span></label><label class="role-card"><input type="radio" name="role" value="employer" @checked(old('role') === 'employer')><i class="fa-solid fa-building"></i><span>Employer</span></label></div></div>
            <div id="job-seeker-fields">
                <div class="form-group"><label for="phone">Phone Number (optional)</label><div class="input-group"><i class="fa-solid fa-phone"></i><input type="tel" id="phone" name="phone" value="{{ old('phone') }}" maxlength="10"></div></div>
                <div class="form-group"><label for="profile_photo">Profile Photo <span>(optional, JPG/PNG/WEBP up to 5 MB)</span></label><div class="input-group"><i class="fa-solid fa-image"></i><input type="file" id="profile_photo" name="profile_photo" accept="image/jpeg,image/png,image/webp"></div></div>
            </div>
            <div id="employer-fields" hidden>
                <div class="form-group"><label for="company_name">Company Name</label><div class="input-group"><i class="fa-solid fa-building"></i><input type="text" id="company_name" name="company_name" value="{{ old('company_name') }}" maxlength="150"></div></div>
                <div class="form-group"><label for="company_email">Company Email Address</label><div class="input-group"><i class="fa-solid fa-at"></i><input type="email" id="company_email" name="company_email" value="{{ old('company_email') }}" maxlength="150"></div></div>
                <div class="form-group"><label for="company_phone">Company Phone (optional)</label><div class="input-group"><i class="fa-solid fa-phone"></i><input type="tel" id="company_phone" name="company_phone" value="{{ old('company_phone') }}" maxlength="10"></div></div>
                <div class="form-group"><label for="company_website">Company Website (optional)</label><div class="input-group"><i class="fa-solid fa-globe"></i><input type="url" id="company_website" name="company_website" value="{{ old('company_website') }}" maxlength="255"></div></div>
                <div class="form-group"><label for="company_logo">Company Logo <span>(optional, JPG/PNG/WEBP up to 5 MB)</span></label><div class="input-group"><i class="fa-solid fa-image"></i><input type="file" id="company_logo" name="company_logo" accept="image/jpeg,image/png,image/webp"></div></div>
                <div class="form-group"><label for="company_description">Company Description (optional)</label><div class="input-group"><i class="fa-solid fa-align-left"></i><textarea id="company_description" name="company_description" maxlength="5000">{{ old('company_description') }}</textarea></div></div>
                <div class="form-group"><label for="company_address">Company Address (optional)</label><div class="input-group"><i class="fa-solid fa-location-dot"></i><textarea id="company_address" name="company_address" maxlength="1000">{{ old('company_address') }}</textarea></div></div>
            </div>
            <div class="form-group"><label for="password">Password</label><div class="input-group"><i class="fa-solid fa-lock"></i><input type="password" id="password" name="password" required></div></div>
            <div class="form-group"><label for="confirm_password">Confirm Password</label><div class="input-group"><i class="fa-solid fa-lock"></i><input type="password" id="confirm_password" name="confirm_password" required></div></div>
            <div class="form-group"><label for="captcha">Security Check: {{ $captchaQuestion }}</label><div class="input-group"><i class="fa-solid fa-shield-halved"></i><input type="text" id="captcha" name="captcha" required maxlength="3"></div></div>
            <div class="form-options"><label class="remember"><input type="checkbox" name="terms" value="1" required>I agree to the Terms and Conditions</label></div>
            <button type="submit" class="btn-login"><i class="fa-solid fa-user-plus"></i>Create Account</button>
        </form>
        <div class="auth-footer-links">Already have an account? <a href="{{ route('login') }}">Login</a></div>
    </div></div>
</div>
<script>
(function () {
    const employerFields = document.getElementById('employer-fields');
    const jobSeekerFields = document.getElementById('job-seeker-fields');
    document.querySelectorAll('input[name="role"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            employerFields.hidden = radio.value !== 'employer';
            jobSeekerFields.hidden = radio.value !== 'job_seeker';
        });
    });
    const selected = document.querySelector('input[name="role"]:checked');
    if (selected) {
        employerFields.hidden = selected.value !== 'employer';
        jobSeekerFields.hidden = selected.value !== 'job_seeker';
    }
})();
</script>
@endsection
