@extends('layouts.dashboard')
@section('title', 'Settings')
@section('role', 'Administrator')
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/admin/settings.css') }}">@endsection
@section('js')<script src="{{ asset('assets/js/admin/settings.js') }}"></script>@endsection
@section('sidebar')
<div class="sidebar-section"><div class="sidebar-section-title">MAIN</div><a href="{{ route('admin.dashboard') }}" class="sidebar-link"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a></div>
<div class="sidebar-section"><div class="sidebar-section-title">MANAGEMENT</div><a href="{{ route('admin.users') }}" class="sidebar-link"><i class="fa-solid fa-users"></i><span>Users</span></a><a href="{{ route('admin.companies') }}" class="sidebar-link"><i class="fa-solid fa-building"></i><span>Companies</span></a><a href="{{ route('admin.jobs') }}" class="sidebar-link"><i class="fa-solid fa-briefcase"></i><span>Jobs</span></a><a href="{{ route('admin.applications') }}" class="sidebar-link"><i class="fa-solid fa-file-lines"></i><span>Applications</span></a><a href="{{ route('admin.reports') }}" class="sidebar-link"><i class="fa-solid fa-flag"></i><span>Reports</span></a><a href="{{ route('admin.audit-log') }}" class="sidebar-link"><i class="fa-solid fa-clipboard-list"></i><span>Audit Log</span></a><a href="{{ route('admin.taxonomy') }}" class="sidebar-link"><i class="fa-solid fa-tags"></i><span>Categories &amp; Skills</span></a><a href="{{ route('admin.settings') }}" class="sidebar-link active"><i class="fa-solid fa-gear"></i><span>Settings</span></a></div>
@endsection
@section('content')
<div class="admin-settings-page">
    <div class="admin-page-header"><div><span class="admin-page-label">SYSTEM</span><h1>Platform Settings</h1><p>Configure site-wide behavior and content defaults.</p></div><div class="page-header-actions"><a href="{{ route('admin.audit-log') }}" class="btn btn-light"><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i> View Audit Log</a></div></div>
    @foreach(['success' => 'admin-alert-success', 'danger' => 'admin-alert-danger', 'warning' => 'admin-alert-warning'] as $key => $class)
        @if(session($key))<div class="admin-alert {{ $class }}" role="status"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><div><span>{{ session($key) }}</span></div></div>@endif
    @endforeach
    <section class="settings-readonly">
        <div class="readonly-header"><i class="fa-solid fa-lock" aria-hidden="true"></i><div><strong>Infrastructure settings</strong><span>Read-only — edit these in the Laravel environment configuration.</span></div></div>
        <div class="readonly-grid">
            <div><span>App URL</span><strong>{{ $infrastructure['app_url'] }}</strong></div>
            <div><span>Environment</span><strong>{{ $infrastructure['environment'] }}</strong></div>
            <div><span>SMTP Host</span><strong>{{ $infrastructure['smtp_host'] }}:{{ $infrastructure['smtp_port'] }}</strong></div>
            <div><span>SMTP From</span><strong>{{ $infrastructure['mail_from'] ?: '— not set —' }}</strong></div>
        </div>
    </section>
    @php($tabIcons = ['general' => 'fa-solid fa-sliders', 'registration' => 'fa-solid fa-user-plus', 'jobs' => 'fa-solid fa-briefcase', 'applications' => 'fa-solid fa-file-lines', 'email' => 'fa-solid fa-envelope'])
    <nav class="settings-tabs" aria-label="Settings groups">
        @foreach($tabLabels as $slug => $label)
            <a href="{{ route('admin.settings', ['tab' => $slug]) }}" class="settings-tab {{ $tab === $slug ? 'active' : '' }}"><i class="{{ $tabIcons[$slug] }}" aria-hidden="true"></i><span>{{ $label }}</span></a>
        @endforeach
    </nav>
    <form action="{{ route('admin.settings.update') }}" method="POST" id="settingsForm">
        @csrf<input type="hidden" name="tab" value="{{ $tab }}">
        <section class="settings-form-card">
            <div class="settings-form-header"><div><h2>{{ $tabLabels[$tab] }} Settings</h2><p>Changes apply immediately across the platform.</p></div></div>
            @if($settings->isEmpty())
                <div class="empty-inline"><i class="fa-solid fa-sliders" aria-hidden="true"></i><p>No settings available in this group yet.</p></div>
            @else
                <div class="settings-form-body">
                    @foreach($settings as $setting)
                        @php($key = $setting->setting_key)
                        @php($type = $setting->setting_type)
                        @php($value = (string) ($setting->setting_value ?? ''))
                        <div class="setting-row setting-type-{{ $type }}">
                            <div class="setting-label"><label for="f-{{ $key }}">{{ $setting->label }}</label>@if($setting->description)<small>{{ $setting->description }}</small>@endif</div>
                            <div class="setting-field">
                                @if($type === 'bool')
                                    <label class="switch"><input type="hidden" name="setting[{{ $key }}]" value="0"><input type="checkbox" id="f-{{ $key }}" name="setting[{{ $key }}]" value="1" @checked($value === '1')><span class="switch-track"><span class="switch-thumb"></span></span></label>
                                @elseif($type === 'int')
                                    <input type="number" id="f-{{ $key }}" name="setting[{{ $key }}]" value="{{ $value }}" step="1" min="0" inputmode="numeric">
                                @elseif($type === 'email')
                                    <input type="email" id="f-{{ $key }}" name="setting[{{ $key }}]" value="{{ $value }}" maxlength="150">
                                @elseif($type === 'url')
                                    <input type="url" id="f-{{ $key }}" name="setting[{{ $key }}]" value="{{ $value }}" maxlength="255">
                                @elseif($type === 'text')
                                    <textarea id="f-{{ $key }}" name="setting[{{ $key }}]" rows="4" maxlength="1000">{{ $value }}</textarea>
                                @else
                                    <input type="text" id="f-{{ $key }}" name="setting[{{ $key }}]" value="{{ $value }}" maxlength="255">
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
        @if($settings->isNotEmpty())
            <div class="settings-actions"><a href="{{ route('admin.settings', ['tab' => $tab]) }}" class="btn btn-light">Reset</a><button type="submit" class="btn btn-primary" id="saveSettingsBtn"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Changes</button></div>
        @endif
    </form>
</div>
@endsection
