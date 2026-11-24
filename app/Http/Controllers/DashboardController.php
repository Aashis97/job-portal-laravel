<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function admin()
    {
        $stats = [
            'total_users' => DB::table('users')->count(),
            'total_employers' => DB::table('users')->where('role_id', 2)->count(),
            'total_job_seekers' => DB::table('users')->where('role_id', 3)->count(),
            'total_companies' => DB::table('companies')->count(),
            'total_jobs' => DB::table('jobs')->count(),
            'open_jobs' => DB::table('jobs')->where('job_status', 'Open')->count(),
            'total_applications' => DB::table('applications')->count(),
            'pending_users' => DB::table('users')->where('account_status', 'Pending')->count(),
            'pending_companies' => DB::table('companies')->where('status', 'Pending')->count(),
            'pending_jobs' => DB::table('jobs')->where('job_status', 'Draft')->count(),
            'blocked_users' => DB::table('users')->where('account_status', 'Blocked')->count(),
        ];

        $recentUsers = DB::table('users')
            ->orderByDesc('user_id')
            ->limit(5)
            ->get(['user_id', 'full_name', 'email', 'role_id', 'account_status', 'created_at']);

        $recentJobs = DB::table('jobs as j')
            ->leftJoin('companies as c', 'c.company_id', '=', 'j.company_id')
            ->orderByDesc('j.job_id')
            ->limit(5)
            ->get([
                'j.job_id',
                'j.job_title',
                'j.employment_type',
                'j.workplace',
                'j.job_status',
                'j.created_at',
                'c.company_name',
            ]);

        $recentApplications = DB::table('applications as a')
            ->join('users as u', 'u.user_id', '=', 'a.user_id')
            ->join('jobs as j', 'j.job_id', '=', 'a.job_id')
            ->orderByDesc('a.application_id')
            ->limit(5)
            ->get([
                'a.application_id',
                'a.application_status',
                'a.skill_match_score',
                'a.applied_at',
                'u.full_name as applicant_name',
                'j.job_title',
            ]);

        return view('dashboards.admin', compact(
            'stats',
            'recentUsers',
            'recentJobs',
            'recentApplications'
        ));
    }

    public function employer()
    {
        $employerId = DB::table('employers')
            ->where('user_id', auth()->user()->user_id)
            ->value('employer_id');

        $stats = [
            'jobs' => $employerId
                ? DB::table('jobs')->where('employer_id', $employerId)->count()
                : 0,
            'applications' => $employerId
                ? DB::table('applications')
                    ->join('jobs', 'jobs.job_id', '=', 'applications.job_id')
                    ->where('jobs.employer_id', $employerId)
                    ->count()
                : 0,
            'interviews' => $employerId
                ? DB::table('interviews')
                    ->join('applications', 'applications.application_id', '=', 'interviews.application_id')
                    ->join('jobs', 'jobs.job_id', '=', 'applications.job_id')
                    ->where('jobs.employer_id', $employerId)
                    ->count()
                : 0,
        ];

        return view('dashboards.employer', compact('stats'));
    }

    public function jobSeeker()
    {
        $user = auth()->user();
        $userId = (int) $user->user_id;
        $profile = DB::table('job_seekers')->where('user_id', $userId)->first();
        $completionFields = [
            'date_of_birth',
            'gender',
            'address',
            'city',
            'country',
            'education_level',
            'years_of_experience',
            'current_job_title',
            'expected_salary',
            'profile_summary',
            'availability',
        ];
        $completedFields = 0;
        if ($profile) {
            foreach ($completionFields as $field) {
                if ($profile->{$field} !== null && trim((string) $profile->{$field}) !== '') {
                    $completedFields++;
                }
            }
        }

        $stats = [
            'total_applications' => DB::table('applications')->where('user_id', $userId)->count(),
            'saved_jobs' => DB::table('saved_jobs')->where('user_id', $userId)->count(),
            'shortlisted' => DB::table('applications')
                ->where('user_id', $userId)
                ->where('application_status', 'Shortlisted')
                ->count(),
            'interviews' => DB::table('applications')
                ->where('user_id', $userId)
                ->where('application_status', 'Interview Scheduled')
                ->count(),
        ];

        $recentApplications = DB::table('applications as a')
            ->join('jobs as j', 'j.job_id', '=', 'a.job_id')
            ->leftJoin('companies as c', 'c.company_id', '=', 'j.company_id')
            ->where('a.user_id', $userId)
            ->orderByDesc('a.application_id')
            ->limit(5)
            ->get([
                'a.application_id',
                'a.application_status',
                'a.skill_match_score',
                'a.applied_at',
                'j.job_id',
                'j.job_title',
                'j.employment_type',
                'j.workplace',
                'c.company_name',
            ]);

        $recentJobs = DB::table('jobs as j')
            ->leftJoin('companies as c', 'c.company_id', '=', 'j.company_id')
            ->where('j.job_status', 'Open')
            ->orderByDesc('j.job_id')
            ->limit(5)
            ->get([
                'j.job_id',
                'j.job_title',
                'j.employment_type',
                'j.workplace',
                'j.experience_level',
                'j.job_status',
                'j.created_at',
                'c.company_name',
            ]);

        $resume = DB::table('resume')->where('user_id', $userId)->first();
        $resumeFile = $resume->resume_file ?? $profile->resume_file ?? null;
        $profileCompletion = (int) round(($completedFields / count($completionFields)) * 100);

        return view('dashboards.jobseeker', [
            'user' => $user,
            'stats' => $stats,
            'profileCompletion' => $profileCompletion,
            'resume' => $resume,
            'resumeFile' => $resumeFile,
            'recentApplications' => $recentApplications,
            'recentJobs' => $recentJobs,
        ]);
    }
}
