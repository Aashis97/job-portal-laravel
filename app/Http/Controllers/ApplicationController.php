<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Job;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ApplicationController extends Controller
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

    public function create(Request $request)
    {
        $jobId = filter_var($request->query('job_id'), FILTER_VALIDATE_INT);
        if (!$jobId || $jobId < 1) {
            return redirect()->route('jobs.index')->with('danger', 'Invalid job selected.');
        }

        $job = Job::query()->with('company')->find($jobId);
        if (!$job || !$job->company) {
            return redirect()->route('jobs.index')->with('danger', 'The selected job no longer exists.');
        }

        if (!$this->acceptsApplications($job)) {
            return redirect()->route('jobs.show', ['id' => $jobId])
                ->with('danger', 'This job is no longer accepting applications.');
        }

        $alreadyApplied = Application::query()
            ->where('user_id', $request->user()->user_id)
            ->where('job_id', $jobId)
            ->exists();

        if ($alreadyApplied) {
            return redirect()->route('jobs.show', ['id' => $jobId])
                ->with('danger', 'You have already applied for this job.');
        }

        return view('applications.create', compact('job'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'job_id' => ['required', 'integer', 'min:1'],
            'cover_letter' => ['nullable', 'string', 'max:10000'],
            'cover_letter_file' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'extensions:pdf,doc,docx', 'max:5120'],
        ]);

        $userId = (int) $request->user()->user_id;
        $jobId = (int) $validated['job_id'];
        $job = Job::query()->with('company')->find($jobId);

        if (!$job || !$job->company) {
            return redirect()->route('jobs.index')->with('danger', 'The selected job no longer exists.');
        }

        if (!$this->acceptsApplications($job)) {
            return redirect()->route('jobs.show', ['id' => $jobId])
                ->with('danger', 'This job is no longer accepting applications.');
        }

        if (Application::query()->where('user_id', $userId)->where('job_id', $jobId)->exists()) {
            return redirect()->route('jobs.show', ['id' => $jobId])
                ->with('danger', 'You have already applied for this job.');
        }

        $file = $request->file('cover_letter_file');
        $storedFileName = null;
        $storedFilePath = null;

        if ($file) {
            $uploadDirectory = dirname(base_path()) . DIRECTORY_SEPARATOR . 'assets'
                . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'cover_letters';
            File::ensureDirectoryExists($uploadDirectory, 0755, true);
            $extension = strtolower($file->getClientOriginalExtension());
            $storedFileName = 'cover_' . $userId . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
            $storedFilePath = $uploadDirectory . DIRECTORY_SEPARATOR . $storedFileName;
            $file->move($uploadDirectory, $storedFileName);
        }

        try {
            $application = DB::transaction(function () use ($validated, $job, $jobId, $userId, $storedFileName) {
                $requiredSkills = DB::table('job_skills')
                    ->where('job_id', $jobId)
                    ->where('importance', 'Required')
                    ->count();

                $skillMatch = 0.0;
                if ($requiredSkills > 0) {
                    $matchedSkills = DB::table('job_skills as js')
                        ->join('user_skills as us', 'us.skill_id', '=', 'js.skill_id')
                        ->where('js.job_id', $jobId)
                        ->where('js.importance', 'Required')
                        ->where('us.user_id', $userId)
                        ->distinct()
                        ->count('js.skill_id');
                    $skillMatch = round(($matchedSkills / $requiredSkills) * 100, 2);
                }

                $application = Application::query()->create([
                    'job_id' => $jobId,
                    'user_id' => $userId,
                    'cover_letter' => trim((string) ($validated['cover_letter'] ?? '')) ?: null,
                    'cover_letter_file' => $storedFileName,
                    'application_status' => 'Applied',
                    'skill_match_score' => $skillMatch,
                    'ai_rank' => null,
                ]);

                DB::table('jobs')->where('job_id', $jobId)->increment('total_applications');

                return $application;
            });
        } catch (QueryException $exception) {
            if ($storedFilePath && is_file($storedFilePath)) {
                unlink($storedFilePath);
            }

            if ($this->isUniqueConstraintViolation($exception)) {
                return redirect()->route('jobs.show', ['id' => $jobId])
                    ->with('danger', 'You have already applied for this job.');
            }

            throw $exception;
        } catch (Throwable $exception) {
            if ($storedFilePath && is_file($storedFilePath)) {
                unlink($storedFilePath);
            }

            throw $exception;
        }

        $this->notifyEmployer($job, $application, $request);
        $this->logActivity($request, $userId, 'Applied to job #' . $job->job_id . ' (' . $job->job_title . ')');

        return redirect()->route('applications.index')->with('success', 'Your application was submitted successfully.');
    }

    public function index(Request $request)
    {
        $userId = (int) $request->user()->user_id;
        $requestedStatus = trim((string) $request->query('status', ''));
        $status = in_array($requestedStatus, self::STATUSES, true) ? $requestedStatus : '';
        $queryText = trim((string) $request->query('q', ''));
        $queryText = Str::limit($queryText, 100, '');

        $query = Application::query()->from('applications as a')
            ->join('jobs as j', 'j.job_id', '=', 'a.job_id')
            ->leftJoin('companies as c', 'c.company_id', '=', 'j.company_id')
            ->where('a.user_id', $userId)
            ->when($status !== '', fn ($builder) => $builder->where('a.application_status', $status))
            ->when($queryText !== '', function ($builder) use ($queryText) {
                $builder->where(function ($search) use ($queryText) {
                    $search->where('j.job_title', 'like', '%' . $queryText . '%')
                        ->orWhere('c.company_name', 'like', '%' . $queryText . '%');
                });
            })
            ->select([
                'a.application_id',
                'a.application_status',
                'a.skill_match_score',
                'a.applied_at',
                'j.job_id',
                'j.job_title',
                'j.employment_type',
                'j.workplace',
                'c.company_name',
                'c.logo',
            ])
            ->orderByDesc('a.application_id');

        $applications = $query->paginate(15)->withQueryString();
        $statusCounts = array_fill_keys(self::STATUSES, 0);
        $counts = Application::query()
            ->where('user_id', $userId)
            ->select('application_status', DB::raw('COUNT(*) AS total'))
            ->groupBy('application_status')
            ->pluck('total', 'application_status');

        foreach ($counts as $applicationStatus => $count) {
            if (array_key_exists($applicationStatus, $statusCounts)) {
                $statusCounts[$applicationStatus] = (int) $count;
            }
        }

        return view('applications.index', [
            'applications' => $applications,
            'status' => $status,
            'queryText' => $queryText,
            'statusCounts' => $statusCounts,
            'totalApplications' => array_sum($statusCounts),
            'statuses' => self::STATUSES,
        ]);
    }

    public function withdraw(Request $request)
    {
        $validated = $request->validate([
            'application_id' => ['required', 'integer', 'min:1'],
        ]);
        $userId = (int) $request->user()->user_id;
        $application = Application::query()
            ->where('application_id', $validated['application_id'])
            ->where('user_id', $userId)
            ->first();

        if (!$application) {
            return redirect()->route('applications.index')->with('danger', 'Application not found.');
        }

        if (!in_array($application->application_status, ['Applied', 'Under Review', 'Shortlisted'], true)) {
            return redirect()->route('applications.index')->with(
                'warning',
                'This application cannot be withdrawn at this stage (' . $application->application_status . ').'
            );
        }

        $updated = Application::query()
            ->where('application_id', $application->application_id)
            ->where('user_id', $userId)
            ->whereIn('application_status', ['Applied', 'Under Review', 'Shortlisted'])
            ->update([
                'application_status' => 'Withdrawn',
                'updated_at' => now(),
            ]);

        if ($updated !== 1) {
            return redirect()->route('applications.index')
                ->with('warning', 'Application could not be withdrawn. Please refresh and try again.');
        }

        $this->logActivity($request, $userId, 'Withdrew application #' . $application->application_id . ' (' . $application->job->job_title . ')');

        return redirect()->route('applications.index')->with('success', 'Application withdrawn successfully.');
    }

    private function acceptsApplications(Job $job): bool
    {
        if ($job->job_status !== 'Open') {
            return false;
        }

        return !$job->application_deadline
            || !Carbon::parse($job->application_deadline)->startOfDay()->isBefore(today());
    }

    private function notifyEmployer(Job $job, Application $application, Request $request): void
    {
        $employerUserId = DB::table('employers')
            ->where('employer_id', $job->employer_id)
            ->value('user_id');

        if (!$employerUserId) {
            return;
        }

        try {
            DB::table('notifications')->insert([
                'user_id' => $employerUserId,
                'title' => 'New Job Application',
                'message' => $request->user()->full_name . ' has applied for your job "' . $job->job_title . '".',
                'notification_type' => 'Application',
                'is_read' => false,
                'created_at' => now(),
            ]);
        } catch (QueryException $exception) {
            Log::warning('Application notification could not be created.', [
                'application_id' => $application->application_id,
                'exception' => $exception->getMessage(),
            ]);
        }

    }

    private function logActivity(Request $request, int $userId, string $activity): void
    {
        try {
            DB::table('activity_logs')->insert([
                'user_id' => $userId,
                'activity' => Str::limit($activity, 255, ''),
                'module' => 'Jobseeker',
                'ip_address' => $request->ip(),
                'device_info' => Str::limit((string) $request->userAgent(), 255, ''),
            ]);
        } catch (QueryException $exception) {
            Log::warning('Job seeker activity could not be recorded.', [
                'user_id' => $userId,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        return ($exception->errorInfo[0] ?? null) === '23000';
    }
}
