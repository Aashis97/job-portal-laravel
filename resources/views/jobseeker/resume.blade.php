@extends('layouts.dashboard')

@section('title', 'My Resume')
@section('role', 'Job Seeker')
@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/jobseeker/resume.css') }}">
@endsection

@section('content')
<section class="jobseeker-resume-page">
    <div class="page-header">
        <div>
            <span class="page-tag">Career</span>
            <h1>My Resume</h1>
            <p>Keep your resume up to date to improve job matching and application chances.</p>
        </div>
        @if($resume && $parserStatus === 'Completed')
            <span class="parser-badge parser-success"><i class="fa-solid fa-circle-check" aria-hidden="true"></i>Parsed</span>
        @elseif($resume && $parserStatus === 'Processing')
            <span class="parser-badge parser-processing"><i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>Processing</span>
        @elseif($resume && $parserStatus === 'Failed')
            <span class="parser-badge parser-danger"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>Parse failed</span>
        @endif
    </div>

    @foreach(['success', 'danger', 'warning', 'info'] as $type)
        @if(session($type))
            <div class="alert alert-{{ $type }}" role="{{ $type === 'danger' ? 'alert' : 'status' }}">{{ session($type) }}</div>
        @endif
    @endforeach
    @if($errors->any())
        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
    @endif

    <div class="resume-layout">
        <div class="resume-card current-resume-card">
            <div class="resume-card-header">
                <div class="resume-card-icon"><i class="fa-solid fa-file-lines" aria-hidden="true"></i></div>
                <div><span>Current Resume</span><h2>{{ $resume ? 'Your Resume File' : 'No Resume Yet' }}</h2></div>
            </div>

            @if($resume)
                @php
                    $uploadedAt = $resume->uploaded_at ? \Illuminate\Support\Carbon::parse($resume->uploaded_at) : null;
                    $statusClass = match($parserStatus) {
                        'Completed' => 'status-badge status-completed',
                        'Processing' => 'status-badge status-processing',
                        'Failed' => 'status-badge status-failed',
                        default => 'status-badge status-pending',
                    };
                @endphp
                <div class="resume-summary">
                    <div class="resume-summary-row"><span>Title</span><strong>{{ $resume->resume_title ?: 'My Resume' }}</strong></div>
                    <div class="resume-summary-row"><span>File</span><strong class="resume-file-name">{{ basename($resume->resume_file) }}</strong></div>
                    <div class="resume-summary-row"><span>Uploaded</span><strong>{{ $uploadedAt ? $uploadedAt->diffForHumans() : '—' }}</strong></div>
                    <div class="resume-summary-row"><span>Status</span><strong><span class="{{ $statusClass }}">{{ $parserStatus ?: 'Pending' }}</span></strong></div>
                    @if($resume->highest_education)
                        <div class="resume-summary-row"><span>Education</span><strong>{{ $resume->highest_education }}</strong></div>
                    @endif
                    @if($resume->total_experience !== null)
                        <div class="resume-summary-row"><span>Experience</span><strong>{{ number_format((float) $resume->total_experience, 1) }} years</strong></div>
                    @endif
                </div>

                <div class="resume-actions">
                    <a href="{{ route('jobseeker.resume.file') }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary"><i class="fa-solid fa-eye" aria-hidden="true"></i>View</a>
                    <a href="{{ route('jobseeker.resume.file', ['download' => 1]) }}" class="btn btn-light"><i class="fa-solid fa-download" aria-hidden="true"></i>Download</a>
                    <form action="{{ route('jobseeker.resume.parse') }}" method="POST" class="inline-action" data-confirm="{{ $parserStatus === 'Completed' ? 'Re-parse this resume? Existing extracted skills will be replaced.' : 'Parse this resume to extract skills?' }}">
                        @csrf
                        <button type="submit" class="btn btn-light"><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>{{ $parserStatus === 'Completed' ? 'Re-parse' : 'Parse' }}</button>
                    </form>
                    <form action="{{ route('jobseeker.resume.delete') }}" method="POST" class="inline-action" data-confirm="Delete this resume? This cannot be undone.">
                        @csrf
                        <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash" aria-hidden="true"></i>Delete</button>
                    </form>
                </div>

                @if($parsedSkills)
                    <div class="parsed-skills">
                        <div class="parsed-skills-header"><i class="fa-solid fa-tags" aria-hidden="true"></i>Detected skills ({{ count($parsedSkills) }})</div>
                        <div class="parsed-skills-list">
                            @foreach($parsedSkills as $skill)
                                <span class="skill-chip">{{ $skill }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif
            @else
                <div class="resume-empty-state">
                    <div class="empty-icon"><i class="fa-solid fa-file-arrow-up" aria-hidden="true"></i></div>
                    <h3>No resume uploaded</h3>
                    <p>Upload your resume to apply for jobs and improve matching.</p>
                </div>
            @endif
        </div>

        <div class="resume-card upload-resume-card">
            <div class="resume-card-header">
                <div class="resume-card-icon"><i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i></div>
                <div><span>{{ $resume ? 'Replace Resume' : 'Upload Resume' }}</span><h2>{{ $resume ? 'Update Your Resume' : 'Add a Resume' }}</h2></div>
            </div>
            <form action="{{ route('jobseeker.resume.upload') }}" method="POST" enctype="multipart/form-data" class="resume-form" id="resumeUploadForm">
                @csrf
                <input type="hidden" name="action" value="upload">
                <div class="form-group">
                    <label for="resume_title">Resume Title</label>
                    <input type="text" id="resume_title" name="resume_title" maxlength="150" placeholder="e.g. Senior PHP Developer — 2026" value="{{ old('resume_title', $resume->resume_title ?? 'My Resume') }}">
                </div>
                <div class="form-group">
                    <label for="resume_file">Resume File <span class="required">*</span></label>
                    <div class="resume-dropzone" id="resumeDropzone">
                        <input type="file" name="resume_file" id="resume_file" accept=".pdf,.doc,.docx" required>
                        <div class="dropzone-content" id="dropzoneContent">
                            <div class="dropzone-icon"><i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i></div>
                            <h4>Drag & drop your resume here</h4>
                            <p>or <span class="browse">browse files</span></p>
                            <small>PDF, DOC, DOCX — max 5 MB</small>
                        </div>
                        <div class="selected-file" id="selectedFile" hidden>
                            <div class="selected-file-icon"><i class="fa-solid fa-file-lines" aria-hidden="true"></i></div>
                            <div class="selected-file-info"><strong id="selectedFileName">—</strong><span id="selectedFileSize">0 KB</span></div>
                            <button type="button" class="remove-file-btn" id="removeFileBtn" aria-label="Remove selected file"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="uploadSubmitBtn"><i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i>{{ $resume ? 'Replace Resume' : 'Upload Resume' }}</button>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection

@section('js')
<script src="{{ asset('assets/js/jobseeker/resume.js') }}"></script>
@endsection
