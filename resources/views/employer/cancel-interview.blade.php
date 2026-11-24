@extends('layouts.dashboard')

@section('title', 'Cancel Interview')
@section('role', 'Employer')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/employer/interviews.css') }}">
@endsection

@section('content')
<div class="employer-interviews-page">
    <section class="page-header"><div class="page-header-content"><div class="page-header-icon"><i class="fa-solid fa-calendar-xmark" aria-hidden="true"></i></div><div><span class="page-eyebrow">Interview Management</span><h1>Cancel Interview?</h1><p>You are about to cancel the interview for {{ $interview->job_title }}.</p></div></div></section>
    <section class="dashboard-card">
        <div class="card-header"><div class="card-header-title"><div class="card-header-icon"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i></div><div><h2>Confirm Cancellation</h2><p>The interview record will be kept for history.</p></div></div></div>
        <div class="card-body">
            <div class="application-info-list">
                <div><span>Job</span><strong>{{ $interview->job_title }}</strong></div>
                <div><span>Candidate</span><strong>{{ $interview->full_name }}</strong></div>
                <div><span>Interviewer</span><strong>{{ $interview->interviewer_name }}</strong></div>
                <div><span>Type</span><strong>{{ $interview->interview_type }}</strong></div>
                <div><span>Date</span><strong>{{ \Illuminate\Support\Carbon::parse($interview->interview_date)->format('F j, Y') }}</strong></div>
                <div><span>Time</span><strong>{{ \Illuminate\Support\Carbon::parse($interview->interview_time)->format('h:i A') }}</strong></div>
            </div>
            <div class="interview-warning"><div class="interview-warning-icon"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i></div><div class="interview-warning-content"><strong>Cancellation keeps its history</strong><p>The interview will be marked Cancelled and retained. The candidate's application will return to Under Review if it is still marked Interview Scheduled.</p></div></div>
            <div class="form-actions"><a href="{{ route('employer.interview-details', ['interview_id' => $interview->interview_id]) }}" class="btn btn-light"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Go Back</a><form action="{{ route('employer.interviews.status') }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this interview?');">@csrf<input type="hidden" name="interview_id" value="{{ $interview->interview_id }}"><input type="hidden" name="status" value="Cancelled"><button type="submit" class="btn btn-danger"><i class="fa-solid fa-calendar-xmark" aria-hidden="true"></i>Cancel Interview</button></form></div>
        </div>
    </section>
</div>
@endsection
