<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class EmployerApplicantController extends Controller
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

    public function applications(Request $request)
    {
        $employer = $this->employerFor($request);
        $jobs = DB::table('jobs as j')
            ->leftJoin('applications as a', 'a.job_id', '=', 'j.job_id')
            ->where('j.employer_id', $employer->employer_id)
            ->groupBy('j.job_id', 'j.job_title', 'j.job_status', 'j.location', 'j.employment_type')
            ->orderByDesc('j.job_id')
            ->get([
                'j.job_id',
                'j.job_title',
                'j.job_status',
                'j.location',
                'j.employment_type',
                DB::raw('COUNT(a.application_id) AS application_count'),
                DB::raw("SUM(CASE WHEN a.application_status IN ('Applied', 'Under Review') THEN 1 ELSE 0 END) AS pending_count"),
                DB::raw('MAX(a.applied_at) AS latest_application'),
            ]);

        $totalApplications = (int) $jobs->sum('application_count');
        $pendingApplications = (int) $jobs->sum('pending_count');
        $isCandidatesView = $request->query('view') === 'candidates';

        return view('employer.applications', compact(
            'jobs',
            'totalApplications',
            'pendingApplications',
            'isCandidatesView'
        ));
    }

    public function index(Request $request)
    {
        $jobId = filter_var($request->query('job_id'), FILTER_VALIDATE_INT);
        if ($jobId === false || $jobId === null || $jobId < 1) {
            return redirect()->route('employer.jobs')->with('danger', 'Select a valid job to view applicants.');
        }

        $employer = $this->employerFor($request);
        $job = DB::table('jobs as j')
            ->join('categories as c', 'c.category_id', '=', 'j.category_id')
            ->join('companies as co', 'co.company_id', '=', 'j.company_id')
            ->where('j.job_id', $jobId)
            ->where('j.employer_id', $employer->employer_id)
            ->first([
                'j.job_id',
                'j.job_title',
                'j.job_status',
                'j.total_applications',
                'j.location',
                'j.employment_type',
                'j.workplace',
                'j.experience_level',
                'c.category_name',
                'co.company_name',
            ]);
        if (!$job) {
            return redirect()->route('employer.jobs')->with('danger', 'Job not found or you do not have permission to view its applicants.');
        }

        $applicants = DB::table('applications as a')
            ->join('users as u', 'u.user_id', '=', 'a.user_id')
            ->leftJoin('job_seekers as js', 'js.user_id', '=', 'a.user_id')
            ->where('a.job_id', $job->job_id)
            ->orderByDesc('a.applied_at')
            ->get([
                'a.application_id',
                'a.job_id',
                'a.user_id',
                'a.application_status',
                'a.skill_match_score',
                'a.ai_rank',
                'a.applied_at',
                'u.full_name',
                'u.email',
                'u.phone',
                'u.profile_image',
                'js.seeker_id',
                'js.resume_file',
                'js.availability',
            ]);

        return view('employer.applicants', compact('job', 'applicants'));
    }

    public function show(Request $request)
    {
        $applicationId = filter_var($request->query('application_id'), FILTER_VALIDATE_INT);
        if ($applicationId === false || $applicationId === null || $applicationId < 1) {
            return redirect()->route('employer.jobs')->with('danger', 'Invalid application selected.');
        }

        $employer = $this->employerFor($request);
        $application = $this->ownedApplication($employer->employer_id, $applicationId);
        if (!$application) {
            return redirect()->route('employer.jobs')->with('danger', 'Application not found or you do not have permission to view it.');
        }

        $candidateSkills = DB::table('user_skills as us')
            ->join('skills as s', 's.skill_id', '=', 'us.skill_id')
            ->where('us.user_id', $application->user_id)
            ->orderBy('s.skill_name')
            ->get(['s.skill_id', 's.skill_name']);
        $jobSkills = DB::table('job_skills as js')
            ->join('skills as s', 's.skill_id', '=', 'js.skill_id')
            ->where('js.job_id', $application->job_id)
            ->orderBy('s.skill_name')
            ->get(['s.skill_id', 's.skill_name']);

        $candidateSkillNames = $candidateSkills->mapWithKeys(
            fn ($skill) => [mb_strtolower(trim($skill->skill_name)) => $skill->skill_name]
        );
        $matchedSkills = [];
        $missingSkills = [];
        foreach ($jobSkills as $skill) {
            $normalized = mb_strtolower(trim($skill->skill_name));
            if ($candidateSkillNames->has($normalized)) {
                $matchedSkills[] = $skill->skill_name;
            } else {
                $missingSkills[] = $skill->skill_name;
            }
        }

        return view('employer.applicant-details', [
            'application' => $application,
            'candidateSkills' => $candidateSkills,
            'jobSkills' => $jobSkills,
            'matchedSkills' => $matchedSkills,
            'missingSkills' => $missingSkills,
            'statuses' => self::STATUSES,
        ]);
    }

    public function updateStatus(Request $request)
    {
        $validated = $request->validate([
            'application_id' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'in:' . implode(',', self::STATUSES)],
        ]);
        $employer = $this->employerFor($request);

        try {
            $result = DB::transaction(function () use ($validated, $employer) {
                $application = DB::table('applications as a')
                    ->join('jobs as j', 'j.job_id', '=', 'a.job_id')
                    ->where('a.application_id', $validated['application_id'])
                    ->where('j.employer_id', $employer->employer_id)
                    ->lockForUpdate()
                    ->first(['a.application_id', 'a.job_id', 'a.application_status']);
                if (!$application) {
                    return null;
                }
                if ($application->application_status === $validated['status']) {
                    return ['job_id' => $application->job_id, 'unchanged' => true];
                }

                DB::table('applications')
                    ->where('application_id', $application->application_id)
                    ->update([
                        'application_status' => $validated['status'],
                        'updated_at' => now(),
                    ]);

                return ['job_id' => $application->job_id, 'unchanged' => false];
            });
        } catch (QueryException $exception) {
            Log::error('Employer application status update failed.', [
                'user_id' => $request->user()->user_id,
                'application_id' => $validated['application_id'],
                'exception' => $exception->getMessage(),
            ]);

            return back()->with('error', 'Unable to update the application status. Please try again.');
        }

        if (!$result) {
            return redirect()->route('employer.jobs')->with('error', 'Application not found or access denied.');
        }

        return redirect()->route('employer.applicant-details', ['application_id' => $validated['application_id']])
            ->with('success', $result['unchanged']
                ? 'Application status is already "' . $validated['status'] . '".'
                : 'Application status updated successfully to "' . $validated['status'] . '".');
    }

    public function resume(Request $request)
    {
        $applicationId = filter_var($request->query('application_id'), FILTER_VALIDATE_INT);
        abort_if($applicationId === false || $applicationId === null || $applicationId < 1, 400, 'Invalid application ID.');

        $employer = $this->employerFor($request);
        $resumeFile = DB::table('applications as a')
            ->join('jobs as j', 'j.job_id', '=', 'a.job_id')
            ->join('job_seekers as js', 'js.user_id', '=', 'a.user_id')
            ->where('a.application_id', $applicationId)
            ->where('j.employer_id', $employer->employer_id)
            ->where('j.company_id', $employer->company_id)
            ->value('js.resume_file');
        abort_unless(is_string($resumeFile) && $resumeFile !== '', 404, 'No resume has been uploaded by this candidate.');

        $path = $this->safeResumePath($resumeFile);
        abort_unless($path, 404, 'Resume file could not be found.');

        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        abort_unless(isset($mimeTypes[$extension]), 415, 'Unsupported resume file type.');

        return response()->file($path, [
            'Content-Type' => $mimeTypes[$extension],
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function profileImage(Request $request, int $applicationId)
    {
        $employer = $this->employerFor($request);
        $filename = DB::table('applications as a')
            ->join('jobs as j', 'j.job_id', '=', 'a.job_id')
            ->join('users as u', 'u.user_id', '=', 'a.user_id')
            ->where('a.application_id', $applicationId)
            ->where('j.employer_id', $employer->employer_id)
            ->value('u.profile_image');
        abort_unless(is_string($filename) && $filename !== '', 404);

        $filename = basename($filename);
        foreach ($this->profileImageDirectories() as $directory) {
            $root = realpath($directory);
            $path = realpath($directory . DIRECTORY_SEPARATOR . $filename);
            if (!$root || !$path || strpos($path, $root . DIRECTORY_SEPARATOR) !== 0 || !is_file($path)) {
                continue;
            }
            $mimeType = File::mimeType($path);
            abort_unless(in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true), 404);

            return response()->file($path, [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'private, max-age=3600',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        abort(404);
    }

    private function employerFor(Request $request): object
    {
        $employer = DB::table('employers')
            ->where('user_id', $request->user()->user_id)
            ->first(['employer_id', 'company_id']);
        abort_unless($employer, 403, 'Employer profile could not be found.');

        return $employer;
    }

    private function ownedApplication(int $employerId, int $applicationId): ?object
    {
        return DB::table('applications as a')
            ->join('users as u', 'u.user_id', '=', 'a.user_id')
            ->leftJoin('job_seekers as js', 'js.user_id', '=', 'a.user_id')
            ->join('jobs as j', 'j.job_id', '=', 'a.job_id')
            ->join('categories as c', 'c.category_id', '=', 'j.category_id')
            ->join('companies as co', 'co.company_id', '=', 'j.company_id')
            ->where('a.application_id', $applicationId)
            ->where('j.employer_id', $employerId)
            ->first([
                'a.application_id',
                'a.job_id',
                'a.user_id',
                'a.application_status',
                'a.skill_match_score',
                'a.ai_rank',
                'a.applied_at',
                'u.full_name',
                'u.email',
                'u.account_status',
                'u.email_verified',
                'u.profile_image',
                'js.seeker_id as job_seeker_id',
                'js.resume_file',
                'js.profile_completed',
                'js.availability',
                'j.job_title',
                'j.employment_type',
                'j.workplace',
                'j.experience_level',
                'j.location',
                'j.job_status',
                'c.category_name',
                'co.company_id',
                'co.company_name',
                'co.logo',
            ]);
    }

    private function safeResumePath(string $filename): ?string
    {
        if (basename($filename) !== $filename || str_contains($filename, '/') || str_contains($filename, '\\')) {
            Log::warning('Invalid resume filename encountered while serving an employer applicant resume.');
            return null;
        }

        $directory = dirname(base_path()) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'resumes';
        $root = realpath($directory);
        $path = realpath($directory . DIRECTORY_SEPARATOR . $filename);
        if (!$root || !$path || strpos($path, $root . DIRECTORY_SEPARATOR) !== 0 || !is_file($path) || !is_readable($path)) {
            return null;
        }

        return $path;
    }

    private function profileImageDirectories(): array
    {
        $projectRoot = dirname(base_path());

        return [
            $projectRoot . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profiles',
            $projectRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profile_images',
        ];
    }
}
