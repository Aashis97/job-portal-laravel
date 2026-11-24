@extends('layouts.dashboard')

@section('title', 'Change Password')
@section('role', 'Employer')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/employer/change_password.css') }}">
@endsection

@section('content')
<section class="employer-password-page">
    <div class="password-page-header"><div class="password-header-content"><div class="password-header-icon"><i class="fa-solid fa-key"></i></div><div><span class="password-page-label">Account Security</span><h1>Change Password</h1><p>Keep your employer account secure by using a strong password.</p></div></div></div>

    @foreach(['success' => 'password-alert-success', 'danger' => 'password-alert-error'] as $type => $class)
        @if(session($type))
            <div class="password-alert {{ $class }}"><div class="password-alert-icon"><i class="fa-solid {{ $type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' }}"></i></div><div class="password-alert-content"><strong>{{ ucfirst($type) }}</strong><span>{{ session($type) }}</span></div><button type="button" class="password-alert-close" onclick="this.parentElement.remove();" aria-label="Close"><i class="fa-solid fa-xmark"></i></button></div>
        @endif
    @endforeach
    @if($errors->any())
        <div class="password-alert password-alert-error"><div class="password-alert-icon"><i class="fa-solid fa-circle-exclamation"></i></div><div class="password-alert-content"><strong>Error</strong><span>{{ implode(' ', $errors->all()) }}</span></div></div>
    @endif

    <div class="password-layout">
        <div class="password-card">
            <div class="password-card-header"><div class="password-card-icon"><i class="fa-solid fa-lock"></i></div><div><h2>Update Your Password</h2><p>Enter your current password and choose a new one.</p></div></div>
            <div class="password-card-body">
                <form action="{{ route('employer.password.update') }}" method="POST" class="password-form" id="changePasswordForm">
                    @csrf
                    <div class="password-form-group"><label for="current_password">Current Password <span class="required">*</span></label><div class="password-input-wrapper"><i class="fa-solid fa-lock"></i><input type="password" name="current_password" id="current_password" required autocomplete="current-password" placeholder="Enter your current password"><button type="button" class="password-toggle" data-target="current_password" aria-label="Show password"><i class="fa-solid fa-eye"></i></button></div></div>
                    <div class="password-form-group">
                        <label for="new_password">New Password <span class="required">*</span></label>
                        <div class="password-input-wrapper"><i class="fa-solid fa-key"></i><input type="password" name="new_password" id="new_password" required autocomplete="new-password" placeholder="Enter your new password"><button type="button" class="password-toggle" data-target="new_password" aria-label="Show password"><i class="fa-solid fa-eye"></i></button></div>
                        <div class="password-strength"><div class="strength-bars"><span></span><span></span><span></span><span></span></div><span class="strength-text" id="strengthText">Enter a password</span></div>
                        <div class="password-requirements">
                            <span data-requirement="length"><i class="fa-solid fa-circle"></i>At least 8 characters</span>
                            <span data-requirement="uppercase"><i class="fa-solid fa-circle"></i>One uppercase letter</span>
                            <span data-requirement="lowercase"><i class="fa-solid fa-circle"></i>One lowercase letter</span>
                            <span data-requirement="number"><i class="fa-solid fa-circle"></i>One number</span>
                            <span data-requirement="special"><i class="fa-solid fa-circle"></i>One special character</span>
                        </div>
                    </div>
                    <div class="password-form-group"><label for="confirm_password">Confirm New Password <span class="required">*</span></label><div class="password-input-wrapper"><i class="fa-solid fa-shield-halved"></i><input type="password" name="confirm_password" id="confirm_password" required autocomplete="new-password" placeholder="Confirm your new password"><button type="button" class="password-toggle" data-target="confirm_password" aria-label="Show password"><i class="fa-solid fa-eye"></i></button></div><div class="password-match-message" id="passwordMatchMessage"></div></div>
                    <div class="password-form-actions"><a href="{{ route('employer.settings') }}" class="password-cancel-btn"><i class="fa-solid fa-arrow-left"></i>Back to Settings</a><button type="submit" class="password-submit-btn" id="changePasswordBtn"><i class="fa-solid fa-key"></i>Change Password</button></div>
                </form>
            </div>
        </div>

        <aside class="password-security-card"><div class="security-card-icon"><i class="fa-solid fa-shield-halved"></i></div><h2>Password Security</h2><p>A strong password helps protect your employer account and company information.</p><div class="security-tips">
            <div class="security-tip"><span><i class="fa-solid fa-check"></i></span><div><strong>Use a unique password</strong><small>Avoid reusing passwords from other websites.</small></div></div>
            <div class="security-tip"><span><i class="fa-solid fa-check"></i></span><div><strong>Use different character types</strong><small>Combine uppercase, lowercase, numbers and symbols.</small></div></div>
        </div></aside>
    </div>
</section>
@endsection

@section('js')
<script>
(() => {
    const form = document.getElementById('changePasswordForm');
    const password = document.getElementById('new_password');
    const confirmation = document.getElementById('confirm_password');
    const strengthText = document.getElementById('strengthText');
    const matchMessage = document.getElementById('passwordMatchMessage');
    const bars = document.querySelectorAll('.strength-bars span');
    const requirements = {
        length: value => value.length >= 8,
        uppercase: value => /[A-Z]/.test(value),
        lowercase: value => /[a-z]/.test(value),
        number: value => /[0-9]/.test(value),
        special: value => /[^A-Za-z0-9]/.test(value)
    };

    document.querySelectorAll('.password-toggle').forEach(button => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.target);
            input.type = input.type === 'password' ? 'text' : 'password';
            button.setAttribute('aria-label', input.type === 'password' ? 'Show password' : 'Hide password');
        });
    });

    function updatePasswordFeedback() {
        const passed = Object.entries(requirements).filter(([name, check]) => {
            const valid = check(password.value);
            const item = document.querySelector('[data-requirement="' + name + '"]');
            item.classList.toggle('valid', valid);
            return valid;
        }).length;
        bars.forEach((bar, index) => bar.classList.toggle('active', index < Math.ceil(passed * 4 / 5)));
        strengthText.textContent = password.value ? (passed >= 4 ? 'Strong password' : passed >= 2 ? 'Medium password' : 'Weak password') : 'Enter a password';
        strengthText.className = 'strength-text ' + (passed >= 4 ? 'strong' : passed >= 2 ? 'medium' : password.value ? 'weak' : '');

        if (!confirmation.value) {
            matchMessage.textContent = '';
            matchMessage.className = 'password-match-message';
        } else {
            const matches = confirmation.value === password.value;
            matchMessage.textContent = matches ? 'Passwords match.' : 'Passwords do not match.';
            matchMessage.className = 'password-match-message ' + (matches ? 'match' : 'no-match');
        }
    }

    password.addEventListener('input', updatePasswordFeedback);
    confirmation.addEventListener('input', updatePasswordFeedback);
    form.addEventListener('submit', event => {
        if (password.value !== confirmation.value) {
            event.preventDefault();
            updatePasswordFeedback();
            confirmation.focus();
        }
    });
})();
</script>
@endsection
