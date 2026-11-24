<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminApplicationController extends Controller
{
    private const STATUSES = [
        'Applied',
        'Under Review',
        'Shortlisted',
        'Interview Scheduled',
        'Selected',
        'Rejected',
        'Withdrawn',
    ];

    public function index(Request $request)
    {
        $status = in_array($request->query('status'), self::STATUSES, true)
            ? $request->query('status')
            : '';
        $jobId = filter_var($request->query('job_id'), FILTER_VALIDATE_INT);
        $jobId = $jobId && $jobId > 0 ? $jobId : 0;
        $companyId = filter_var($request->query('company_id'), FILTER_VALIDATE_INT);
        $companyId = $companyId && $companyId > 0 ? $companyId : 0;
        $minScore = is_numeric($request->query('min_score')) && (float) $request->query('min_score') > 0
            ? min(100, max(0, (float) $request->query('min_score')))
            : 0;
        $search = mb_substr(trim((string) $request->query('q', '')), 0, 100);

        $query = DB::table('applications as a')
            ->join('users as u', 'u.user_id', '=', 'a.user_id')
            ->join('jobs as j', 'j.job_id', '=', 'a.job_id')
            ->leftJoin('companies as c', 'c.company_id', '=', 'j.company_id')
            ->when($status !== '', fn ($query) => $query->where('a.application_status', $status))
            ->when($jobId > 0, fn ($query) => $query->where('a.job_id', $jobId))
            ->when($companyId > 0, fn ($query) => $query->where('j.company_id', $companyId))
            ->when($minScore > 0, fn ($query) => $query->where('a.skill_match_score', '>=', $minScore))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('u.full_name', 'like', '%' . $search . '%')
                        ->orWhere('u.email', 'like', '%' . $search . '%')
                        ->orWhere('j.job_title', 'like', '%' . $search . '%')
                        ->orWhere('c.company_name', 'like', '%' . $search . '%');
                });
            });

        $applications = $query->orderByDesc('a.application_id')->paginate(25, [
            'a.application_id',
            'a.application_status',
            'a.skill_match_score',
            'a.ai_rank',
            'a.applied_at',
            'u.user_id as applicant_user_id',
            'u.full_name as applicant_name',
            'u.email as applicant_email',
            'u.profile_image as applicant_image',
            'j.job_id',
            'j.job_title',
            'j.employment_type',
            'j.workplace',
            'c.company_id',
            'c.company_name',
        ])->withQueryString();

        $stats = DB::table('applications')
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("SUM(CASE WHEN application_status = 'Applied' THEN 1 ELSE 0 END) AS applied")
            ->selectRaw("SUM(CASE WHEN application_status = 'Under Review' THEN 1 ELSE 0 END) AS under_review")
            ->selectRaw("SUM(CASE WHEN application_status = 'Shortlisted' THEN 1 ELSE 0 END) AS shortlisted")
            ->selectRaw("SUM(CASE WHEN application_status = 'Interview Scheduled' THEN 1 ELSE 0 END) AS interview")
            ->selectRaw("SUM(CASE WHEN application_status = 'Selected' THEN 1 ELSE 0 END) AS selected")
            ->selectRaw("SUM(CASE WHEN application_status = 'Rejected' THEN 1 ELSE 0 END) AS rejected")
            ->selectRaw("SUM(CASE WHEN application_status = 'Withdrawn' THEN 1 ELSE 0 END) AS withdrawn")
            ->selectRaw('SUM(CASE WHEN applied_at >= ? THEN 1 ELSE 0 END) AS last_7_days', [now()->subDays(7)->toDateTimeString()])
            ->selectRaw('COALESCE(AVG(skill_match_score), 0) AS avg_match')
            ->first();

        return view('admin.applications', compact(
            'applications',
            'stats',
            'status',
            'jobId',
            'companyId',
            'minScore',
            'search'
        ));
    }

    public function show(Request $request)
    {
        $validated = $request->validate(['id' => ['required', 'integer', 'min:1']]);
        $application = DB::table('applications as a')
            ->join('users as u', 'u.user_id', '=', 'a.user_id')
            ->join('jobs as j', 'j.job_id', '=', 'a.job_id')
            ->leftJoin('job_seekers as js', 'js.user_id', '=', 'a.user_id')
            ->leftJoin('companies as c', 'c.company_id', '=', 'j.company_id')
            ->leftJoin('employers as e', 'e.employer_id', '=', 'j.employer_id')
            ->leftJoin('users as eu', 'eu.user_id', '=', 'e.user_id')
            ->where('a.application_id', $validated['id'])
            ->first([
                'a.application_id',
                'a.job_id',
                'a.user_id',
                'a.cover_letter',
                'a.application_status',
                'a.skill_match_score',
                'a.ai_rank',
                'a.employer_notes',
                'a.applied_at',
                'a.updated_at',
                'u.full_name as applicant_name',
                'u.email as applicant_email',
                'u.phone as applicant_phone',
                'u.profile_image as applicant_image',
                'u.account_status as applicant_status',
                'u.email_verified as applicant_verified',
                'js.seeker_id',
                'js.current_job_title',
                'js.years_of_experience',
                'js.education_level',
                'js.availability',
                'js.profile_completed',
                'js.resume_file',
                'js.city as seeker_city',
                'js.state as seeker_state',
                'js.country as seeker_country',
                'j.job_title',
                'j.employment_type',
                'j.workplace',
                'j.experience_level',
                'j.location as job_location',
                'j.job_status',
                'j.company_id',
                'j.employer_id',
                'c.company_name',
                'c.logo as company_logo',
                'c.status as company_status',
                'e.user_id as employer_user_id',
                'eu.full_name as employer_name',
                'eu.email as employer_email',
            ]);
        abort_unless($application, 404);

        return view('admin.application-details', compact('application'));
    }

    public function resume(Request $request)
    {
        $validated = $request->validate(['id' => ['required', 'integer', 'min:1']]);
        $filename = DB::table('applications as a')
            ->leftJoin('resume as r', 'r.user_id', '=', 'a.user_id')
            ->leftJoin('job_seekers as js', 'js.user_id', '=', 'a.user_id')
            ->where('a.application_id', $validated['id'])
            ->selectRaw('COALESCE(r.resume_file, js.resume_file) AS resume_file')
            ->value('resume_file');
        abort_unless(is_string($filename) && $filename !== '', 404, 'No resume has been uploaded.');

        $filename = basename($filename);
        $directory = dirname(base_path()) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'resumes';
        $root = realpath($directory);
        $path = realpath($directory . DIRECTORY_SEPARATOR . $filename);
        abort_unless($root && $path && str_starts_with($path, $root . DIRECTORY_SEPARATOR) && is_file($path) && is_readable($path), 404, 'Resume file could not be found.');

        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        abort_unless(isset($mimeTypes[$extension]), 415, 'Unsupported resume file type.');

        return response()->file($path, [
            'Content-Type' => $mimeTypes[$extension],
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'application_id' => ['required', 'integer', 'min:1'],
            'action' => ['required', 'in:force_withdraw,delete'],
        ]);
        $applicationId = (int) $validated['application_id'];
        $action = $validated['action'];

        $result = DB::transaction(function () use ($applicationId, $action) {
            $application = DB::table('applications')
                ->where('application_id', $applicationId)
                ->lockForUpdate()
                ->first(['application_id', 'application_status', 'job_id']);
            if (!$application) {
                return 'missing';
            }
            if ($action === 'force_withdraw') {
                if (in_array($application->application_status, ['Withdrawn', 'Rejected'], true)) {
                    return 'already';
                }
                DB::table('applications')->where('application_id', $applicationId)->update([
                    'application_status' => 'Withdrawn',
                    'updated_at' => now(),
                ]);

                return 'withdrawn';
            }

            DB::table('interviews')->where('application_id', $applicationId)->delete();
            DB::table('applications')->where('application_id', $applicationId)->delete();
            DB::table('jobs')->where('job_id', $application->job_id)->update([
                'total_applications' => DB::raw('CASE WHEN total_applications > 0 THEN total_applications - 1 ELSE 0 END'),
            ]);

            return 'deleted';
        });

        if ($result === 'missing') {
            return redirect()->route('admin.applications')->with('danger', 'Application not found.');
        }
        if ($result === 'already') {
            return redirect()->route('admin.application-details', ['id' => $applicationId])
                ->with('info', 'This application is already withdrawn or rejected.');
        }
        if ($result === 'deleted') {
            return redirect()->route('admin.applications')->with('success', 'Application deleted permanently.');
        }

        return redirect()->route('admin.application-details', ['id' => $applicationId])
            ->with('success', 'Application force-withdrawn.');
    }
}
