@extends('layouts.dashboard')

@section('title', 'Apply for Job')
@section('role', 'Job Seeker')
@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/jobseeker/apply_job.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/jobseeker/job_details.css') }}">
@endsection

@section('content')
<section class="jobseeker-apply-page">
    <a href="{{ route('jobs.show', ['id' => $job->job_id]) }}" class="back-link">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
        Back to job details
    </a>

    @if(session('danger'))
        <div class="alert alert-danger" role="alert">{{ session('danger') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
    @endif

    <div class="apply-page-header">
        <span class="page-tag">
            <i class="fa-solid fa-file-signature" aria-hidden="true"></i>
            Application
        </span>
        <h1>Apply for {{ $job->job_title }}</h1>
        <p>at <strong>{{ $job->company->company_name }}</strong></p>
    </div>

    <div class="apply-card">
        <div class="apply-card-header">
            <div class="apply-card-icon"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i></div>
            <div>
                <h2>Submit your application</h2>
                <p>Add a cover letter or upload one as a file — both are optional.</p>
            </div>
        </div>

        <div class="apply-notice">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            <div>Your profile and resume are sent automatically with this application. Make sure they are up to date.</div>
        </div>

        <form action="{{ route('applications.store') }}" method="POST" enctype="multipart/form-data" class="apply-form" id="applyJobForm">
            @csrf
            <input type="hidden" name="job_id" value="{{ $job->job_id }}">

            <div class="apply-form-group">
                <label for="cover_letter">Cover Letter <span class="optional">(optional)</span></label>
                <div class="textarea-wrapper">
                    <textarea name="cover_letter" id="cover_letter" rows="8" maxlength="10000" placeholder="Write a cover letter...">{{ old('cover_letter') }}</textarea>
                </div>
                <div class="char-counter"><span id="characterCount">{{ strlen(old('cover_letter', '')) }}</span> / 10000</div>
            </div>

            <div class="or-divider"><span>or</span></div>

            <div class="apply-form-group">
                <label for="cover_letter_file">Upload Cover Letter <span class="optional">(optional)</span></label>
                <label class="upload-dropzone" for="cover_letter_file">
                    <i class="fa-solid fa-file-arrow-up" aria-hidden="true"></i>
                    <span class="upload-text"><strong>Choose a file</strong><small>PDF, DOC or DOCX — max 5 MB</small></span>
                    <input type="file" name="cover_letter_file" id="cover_letter_file" accept=".pdf,.doc,.docx">
                </label>
                <div class="selected-file-name" id="coverLetterFileName">No file selected</div>
            </div>

            <div class="apply-form-actions">
                <a href="{{ route('jobs.show', ['id' => $job->job_id]) }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary" id="submitApplicationBtn">
                    <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                    Submit Application
                </button>
            </div>
        </form>
    </div>
</section>
@endsection

@section('js')
<script src="{{ asset('assets/js/jobseeker/apply_job.js') }}"></script>
@endsection
