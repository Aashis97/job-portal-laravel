<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployerAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $employer = DB::table('employers')
            ->where('user_id', $request->user()->user_id)
            ->first(['employer_id', 'company_id']);
        abort_unless($employer, 403, 'Employer profile could not be found.');

        $employerId = (int) $employer->employer_id;
        $companyName = DB::table('companies')
            ->where('company_id', $employer->company_id)
            ->value('company_name') ?? 'Your company';

        $jobs = DB::table('jobs')
            ->where('employer_id', $employerId)
            ->selectRaw('COUNT(*) AS total_jobs')
            ->selectRaw("SUM(CASE WHEN job_status = 'Open' THEN 1 ELSE 0 END) AS active_jobs")
            ->first();

        $applications = DB::table('applications as a')
            ->join('jobs as j', 'j.job_id', '=', 'a.job_id')
            ->where('j.employer_id', $employerId)
            ->selectRaw('COUNT(*) AS total_applications')
            ->selectRaw('COUNT(DISTINCT a.user_id) AS total_candidates')
            ->selectRaw("SUM(CASE WHEN a.application_status = 'Applied' THEN 1 ELSE 0 END) AS applied")
            ->selectRaw("SUM(CASE WHEN a.application_status = 'Under Review' THEN 1 ELSE 0 END) AS under_review")
            ->selectRaw("SUM(CASE WHEN a.application_status = 'Shortlisted' THEN 1 ELSE 0 END) AS shortlisted")
            ->selectRaw("SUM(CASE WHEN a.application_status = 'Interview Scheduled' THEN 1 ELSE 0 END) AS interview_scheduled")
            ->selectRaw("SUM(CASE WHEN a.application_status = 'Selected' THEN 1 ELSE 0 END) AS selected")
            ->selectRaw("SUM(CASE WHEN a.application_status = 'Rejected' THEN 1 ELSE 0 END) AS rejected")
            ->selectRaw("SUM(CASE WHEN a.application_status = 'Withdrawn' THEN 1 ELSE 0 END) AS withdrawn")
            ->selectRaw('COALESCE(AVG(a.skill_match_score), 0) AS average_skill_match')
            ->first();

        $interviews = DB::table('interviews as i')
            ->join('applications as a', 'a.application_id', '=', 'i.application_id')
            ->join('jobs as j', 'j.job_id', '=', 'a.job_id')
            ->where('j.employer_id', $employerId)
            ->selectRaw('COUNT(*) AS total_interviews')
            ->selectRaw("SUM(CASE WHEN i.interview_status = 'Scheduled' THEN 1 ELSE 0 END) AS scheduled")
            ->selectRaw("SUM(CASE WHEN i.interview_status = 'Completed' THEN 1 ELSE 0 END) AS completed")
            ->selectRaw("SUM(CASE WHEN i.interview_status = 'Cancelled' THEN 1 ELSE 0 END) AS cancelled")
            ->selectRaw("SUM(CASE WHEN i.interview_status = 'Rescheduled' THEN 1 ELSE 0 END) AS rescheduled")
            ->first();

        $jobPerformance = DB::table('jobs as j')
            ->leftJoin('applications as a', 'a.job_id', '=', 'j.job_id')
            ->where('j.employer_id', $employerId)
            ->groupBy('j.job_id', 'j.job_title', 'j.job_status')
            ->select([
                'j.job_id',
                'j.job_title',
                'j.job_status',
            ])
            ->selectRaw('COUNT(a.application_id) AS applications')
            ->selectRaw("COUNT(CASE WHEN a.application_status = 'Under Review' THEN 1 END) AS under_review")
            ->selectRaw("COUNT(CASE WHEN a.application_status = 'Shortlisted' THEN 1 END) AS shortlisted")
            ->selectRaw("COUNT(CASE WHEN a.application_status = 'Interview Scheduled' THEN 1 END) AS interviews")
            ->selectRaw("COUNT(CASE WHEN a.application_status = 'Selected' THEN 1 END) AS selected")
            ->selectRaw('COALESCE(AVG(a.skill_match_score), 0) AS average_match')
            ->orderByDesc('applications')
            ->orderByDesc('j.job_id')
            ->limit(10)
            ->get();

        $topSkills = DB::table('job_skills as js')
            ->join('skills as s', 's.skill_id', '=', 'js.skill_id')
            ->join('jobs as j', 'j.job_id', '=', 'js.job_id')
            ->where('j.employer_id', $employerId)
            ->groupBy('s.skill_id', 's.skill_name')
            ->select(['s.skill_id', 's.skill_name'])
            ->selectRaw('COUNT(js.job_id) AS usage_count')
            ->orderByDesc('usage_count')
            ->orderBy('s.skill_name')
            ->limit(8)
            ->get();

        $recentApplications = DB::table('applications as a')
            ->join('jobs as j', 'j.job_id', '=', 'a.job_id')
            ->join('users as u', 'u.user_id', '=', 'a.user_id')
            ->where('j.employer_id', $employerId)
            ->orderByDesc('a.application_id')
            ->limit(6)
            ->get([
                'a.application_id',
                'a.application_status',
                'a.skill_match_score',
                'a.ai_rank',
                'j.job_title',
                'u.full_name',
                'u.email',
            ]);

        $hiringRate = $applications->total_applications > 0
            ? round(((int) $applications->selected / (int) $applications->total_applications) * 100, 1)
            : 0.0;
        $interviewCompletionRate = $interviews->total_interviews > 0
            ? round(((int) $interviews->completed / (int) $interviews->total_interviews) * 100, 1)
            : 0.0;
        $jobChart = [
            'labels' => $jobPerformance->pluck('job_title')->all(),
            'applications' => $jobPerformance->pluck('applications')->map(fn ($value) => (int) $value)->all(),
            'selected' => $jobPerformance->pluck('selected')->map(fn ($value) => (int) $value)->all(),
        ];
        $chartData = [
            'applicationStatus' => [
                'Applied' => (int) $applications->applied,
                'Under Review' => (int) $applications->under_review,
                'Shortlisted' => (int) $applications->shortlisted,
                'Interview Scheduled' => (int) $applications->interview_scheduled,
                'Selected' => (int) $applications->selected,
                'Rejected' => (int) $applications->rejected,
                'Withdrawn' => (int) $applications->withdrawn,
            ],
            'interviewStatus' => [
                'Scheduled' => (int) $interviews->scheduled,
                'Completed' => (int) $interviews->completed,
                'Cancelled' => (int) $interviews->cancelled,
                'Rescheduled' => (int) $interviews->rescheduled,
            ],
            'jobLabels' => $jobChart['labels'],
            'jobApplications' => $jobChart['applications'],
            'jobSelected' => $jobChart['selected'],
        ];

        return view('employer.analytics', compact(
            'companyName',
            'jobs',
            'applications',
            'interviews',
            'jobPerformance',
            'topSkills',
            'recentApplications',
            'hiringRate',
            'interviewCompletionRate',
            'chartData'
        ));
    }
}
