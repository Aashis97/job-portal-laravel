<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployerInterviewController extends Controller
{
    private const STATUSES = ['Scheduled', 'Completed', 'Cancelled', 'Rescheduled'];
    private const TYPES = ['Online', 'Onsite', 'Phone'];

    public function index(Request $request)
    {
        $employer = $this->employerFor($request);
        $statusValue = $request->query('status');
        $typeValue = $request->query('type');
        $searchValue = $request->query('search', '');
        $status = is_string($statusValue) && in_array($statusValue, self::STATUSES, true) ? $statusValue : '';
        $type = is_string($typeValue) && in_array($typeValue, self::TYPES, true) ? $typeValue : '';
        $search = is_string($searchValue) ? mb_substr(trim($searchValue), 0, 100) : '';

        $query = $this->interviewQuery($employer)
            ->when($status !== '', fn ($query) => $query->where('i.interview_status', $status))
            ->when($type !== '', fn ($query) => $query->where('i.interview_type', $type))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $like = '%' . $search . '%';
                    $query->where('u.full_name', 'like', $like)
                        ->orWhere('u.email', 'like', $like)
                        ->orWhere('j.job_title', 'like', $like)
                        ->orWhere('i.interviewer_name', 'like', $like)
                        ->orWhere('c.category_name', 'like', $like);
                });
            });

        $interviews = $query
            ->orderByRaw("CASE i.interview_status WHEN 'Scheduled' THEN 1 WHEN 'Rescheduled' THEN 2 WHEN 'Completed' THEN 3 WHEN 'Cancelled' THEN 4 ELSE 5 END")
            ->orderBy('i.interview_date')
            ->orderBy('i.interview_time')
            ->orderByDesc('i.interview_id')
            ->get([
                'i.interview_id',
                'i.application_id',
                'i.interviewer_name',
                'i.interview_type',
                'i.interview_date',
                'i.interview_time',
                'i.meeting_link',
                'i.location',
                'i.interview_status',
                'i.remarks',
                'i.created_at',
                'i.updated_at',
                'a.user_id',
                'a.job_id',
                'a.application_status',
                'a.skill_match_score',
                'a.ai_rank',
                'u.full_name',
                'u.email',
                'js.seeker_id',
                'js.resume_file',
                'js.profile_completed',
                'js.availability',
                'j.job_title',
                'j.location as job_location',
                'c.category_name',
                'co.company_name',
                'co.logo as company_logo',
            ]);

        $stats = DB::table('interviews as i')
            ->join('applications as a', 'a.application_id', '=', 'i.application_id')
            ->join('jobs as j', 'j.job_id', '=', 'a.job_id')
            ->where('j.employer_id', $employer->employer_id)
            ->where('j.company_id', $employer->company_id)
            ->selectRaw('COUNT(*) AS total_interviews')
            ->selectRaw("SUM(CASE WHEN i.interview_status = 'Scheduled' THEN 1 ELSE 0 END) AS scheduled_interviews")
            ->selectRaw("SUM(CASE WHEN i.interview_status = 'Completed' THEN 1 ELSE 0 END) AS completed_interviews")
            ->selectRaw("SUM(CASE WHEN i.interview_status = 'Cancelled' THEN 1 ELSE 0 END) AS cancelled_interviews")
            ->selectRaw("SUM(CASE WHEN i.interview_status = 'Rescheduled' THEN 1 ELSE 0 END) AS rescheduled_interviews")
            ->first();

        $today = now()->toDateString();
        $upcomingCount = $interviews->filter(
            fn ($interview) => in_array($interview->interview_status, ['Scheduled', 'Rescheduled'], true)
                && $interview->interview_date >= $today
        )->count();

        return view('employer.interviews', compact(
            'interviews',
            'stats',
            'upcomingCount',
            'status',
            'type',
            'search',
            'today'
        ));
    }

    public function schedule(Request $request)
    {
        $applicationId = filter_var($request->query('application_id'), FILTER_VALIDATE_INT);
        if ($applicationId === false || $applicationId === null || $applicationId < 1) {
            return redirect()->route('employer.applications')->with('error', 'Invalid application ID.');
        }

        $employer = $this->employerFor($request);
        $application = $this->applicationQuery($employer)
            ->where('a.application_id', $applicationId)
            ->first([
                'a.application_id',
                'a.job_id',
                'a.user_id',
                'a.application_status',
                'a.skill_match_score',
                'a.ai_rank',
                'u.full_name',
                'u.email',
                'js.resume_file',
                'js.profile_completed',
                'js.availability',
                'j.job_title',
                'j.employment_type',
                'j.workplace',
                'j.experience_level',
                'j.location as job_location',
                'j.job_status',
                'c.category_name',
                'co.company_id',
                'co.company_name',
                'co.logo',
                'co.status as company_status',
            ]);
        abort_unless($application, 404, 'Application not found or you are not authorized to schedule an interview.');
        if (in_array($application->application_status, ['Rejected', 'Withdrawn'], true)) {
            return redirect()->route('employer.applicant-details', ['application_id' => $applicationId])
                ->with('error', 'An interview cannot be scheduled for a rejected or withdrawn application.');
        }

        $existingInterview = DB::table('interviews')
            ->where('application_id', $applicationId)
            ->orderByDesc('interview_id')
            ->first();

        return view('employer.interview-form', [
            'isEdit' => false,
            'application' => $application,
            'interview' => null,
            'existingInterview' => $existingInterview,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateInterview($request);
        $employer = $this->employerFor($request);
        $application = $this->applicationQuery($employer)
            ->where('a.application_id', $validated['application_id'])
            ->first(['a.application_id', 'a.user_id', 'a.job_id', 'a.application_status', 'j.job_title']);
        if (!$application) {
            return redirect()->route('employer.applications')->with('error', 'Application not found or you are not authorized to schedule an interview.');
        }
        if (in_array($application->application_status, ['Rejected', 'Withdrawn'], true)) {
            return redirect()->route('employer.applicant-details', ['application_id' => $application->application_id])
                ->with('error', 'An interview cannot be scheduled for a rejected or withdrawn application.');
        }

        try {
            $result = DB::transaction(function () use ($validated, $application) {
                $lockedApplication = DB::table('applications')
                    ->where('application_id', $application->application_id)
                    ->lockForUpdate()
                    ->first(['application_id']);
                if (!$lockedApplication) {
                    return null;
                }

                $existing = DB::table('interviews')
                    ->where('application_id', $application->application_id)
                    ->orderByDesc('interview_id')
                    ->first(['interview_id']);
                if ($existing) {
                    return ['interview_id' => (int) $existing->interview_id, 'existing' => true];
                }

                $id = DB::table('interviews')->insertGetId([
                    'application_id' => $application->application_id,
                    'interviewer_name' => $validated['interviewer_name'],
                    'interview_type' => $validated['interview_type'],
                    'interview_date' => $validated['interview_date'],
                    'interview_time' => $validated['interview_time'],
                    'meeting_link' => $validated['meeting_link'],
                    'location' => $validated['location'],
                    'interview_status' => 'Scheduled',
                    'remarks' => $validated['remarks'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('applications')
                    ->where('application_id', $application->application_id)
                    ->update([
                        'application_status' => 'Interview Scheduled',
                        'updated_at' => now(),
                    ]);
                DB::table('notifications')->insert([
                    'user_id' => $application->user_id,
                    'title' => 'Interview scheduled',
                    'message' => sprintf(
                        'An interview for %s is scheduled for %s at %s (%s).',
                        $application->job_title,
                        $validated['interview_date'],
                        $validated['interview_time'],
                        $validated['interview_type']
                    ),
                    'notification_type' => 'Interview',
                    'is_read' => false,
                    'created_at' => now(),
                ]);

                return ['interview_id' => (int) $id, 'existing' => false];
            });
        } catch (QueryException $exception) {
            Log::error('Employer interview scheduling failed.', [
                'user_id' => $request->user()->user_id,
                'application_id' => $validated['application_id'],
                'exception' => $exception->getMessage(),
            ]);

            return back()->withInput()->with('error', 'The interview could not be scheduled. Please try again.');
        }

        if ($result === null) {
            return back()->withInput()->with('error', 'The application could not be found.');
        }
        if ($result['existing']) {
            return redirect()->route('employer.interview-details', ['interview_id' => $result['interview_id']])
                ->with('error', 'An interview already exists for this application. Please edit the existing interview instead.');
        }

        return redirect()->route('employer.interview-details', ['interview_id' => $result['interview_id']])
            ->with('success', 'Interview scheduled successfully.');
    }

    public function cancel(Request $request)
    {
        $interviewId = filter_var($request->query('interview_id'), FILTER_VALIDATE_INT);
        if ($interviewId === false || $interviewId === null || $interviewId < 1) {
            return redirect()->route('employer.interviews')->with('error', 'Invalid interview ID.');
        }

        $employer = $this->employerFor($request);
        $interview = $this->ownedInterview($employer, $interviewId);
        if (!$interview) {
            return redirect()->route('employer.interviews')->with('error', 'Interview not found or access denied.');
        }
        if ($interview->interview_status === 'Cancelled') {
            return redirect()->route('employer.interview-details', ['interview_id' => $interviewId])
                ->with('error', 'This interview has already been cancelled.');
        }
        if ($interview->interview_status === 'Completed') {
            return redirect()->route('employer.interview-details', ['interview_id' => $interviewId])
                ->with('error', 'Completed interviews cannot be cancelled.');
        }
        if (in_array($interview->application_status, ['Rejected', 'Withdrawn'], true)) {
            return redirect()->route('employer.interview-details', ['interview_id' => $interviewId])
                ->with('error', 'The interview cannot be cancelled because the application is no longer active.');
        }

        return view('employer.cancel-interview', compact('interview'));
    }

    public function details(Request $request)
    {
        $interviewId = filter_var($request->query('interview_id'), FILTER_VALIDATE_INT);
        if ($interviewId === false || $interviewId === null || $interviewId < 1) {
            return redirect()->route('employer.interviews')->with('error', 'Invalid interview request.');
        }

        $employer = $this->employerFor($request);
        $interview = $this->ownedInterview($employer, $interviewId);
        if (!$interview) {
            return redirect()->route('employer.interviews')->with('error', 'Interview not found or access denied.');
        }

        $candidateSkills = DB::table('user_skills as us')
            ->join('skills as s', 's.skill_id', '=', 'us.skill_id')
            ->where('us.user_id', $interview->user_id)
            ->orderBy('s.skill_name')
            ->get(['s.skill_id', 's.skill_name']);
        $jobSkills = DB::table('job_skills as js')
            ->join('skills as s', 's.skill_id', '=', 'js.skill_id')
            ->where('js.job_id', $interview->job_id)
            ->orderBy('s.skill_name')
            ->get(['s.skill_id', 's.skill_name']);
        $candidateSkillIds = $candidateSkills->pluck('skill_id')->map(fn ($id) => (int) $id)->all();
        $matchedSkillCount = $jobSkills->filter(fn ($skill) => in_array((int) $skill->skill_id, $candidateSkillIds, true))->count();
        $calculatedMatchScore = $jobSkills->isNotEmpty()
            ? round(($matchedSkillCount / $jobSkills->count()) * 100, 2)
            : 0;
        $matchScore = $interview->skill_match_score !== null
            ? (float) $interview->skill_match_score
            : $calculatedMatchScore;

        return view('employer.interview-details', compact(
            'interview',
            'candidateSkills',
            'jobSkills',
            'matchedSkillCount',
            'matchScore'
        ));
    }

    public function edit(Request $request)
    {
        $interviewId = filter_var($request->query('interview_id'), FILTER_VALIDATE_INT);
        if ($interviewId === false || $interviewId === null || $interviewId < 1) {
            return redirect()->route('employer.interviews')->with('error', 'Invalid interview request.');
        }

        $employer = $this->employerFor($request);
        $interview = $this->ownedInterview($employer, $interviewId);
        if (!$interview) {
            return redirect()->route('employer.interviews')->with('error', 'Interview not found or access denied.');
        }
        if (in_array($interview->interview_status, ['Completed', 'Cancelled'], true)) {
            return redirect()->route('employer.interview-details', ['interview_id' => $interviewId])
                ->with('error', 'Completed or cancelled interviews cannot be edited.');
        }
        if (in_array($interview->application_status, ['Rejected', 'Withdrawn'], true)) {
            return redirect()->route('employer.interview-details', ['interview_id' => $interviewId])
                ->with('error', 'This application is no longer eligible for interview management.');
        }

        return view('employer.interview-form', [
            'isEdit' => true,
            'application' => $interview,
            'interview' => $interview,
            'existingInterview' => $interview,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $this->validateInterview($request, true);
        $employer = $this->employerFor($request);
        $interview = $this->ownedInterview($employer, $validated['interview_id']);
        if (!$interview) {
            return redirect()->route('employer.interviews')->with('error', 'Interview not found or access denied.');
        }
        if (in_array($interview->interview_status, ['Completed', 'Cancelled'], true)) {
            return redirect()->route('employer.interview-details', ['interview_id' => $interview->interview_id])
                ->with('error', 'Completed or cancelled interviews cannot be edited.');
        }
        if (in_array($interview->application_status, ['Rejected', 'Withdrawn'], true)) {
            return redirect()->route('employer.interview-details', ['interview_id' => $interview->interview_id])
                ->with('error', 'This application is no longer eligible for interview management.');
        }

        $conflict = DB::table('interviews as i')
            ->join('applications as a', 'a.application_id', '=', 'i.application_id')
            ->join('jobs as j', 'j.job_id', '=', 'a.job_id')
            ->where('j.employer_id', $employer->employer_id)
            ->where('i.interview_id', '<>', $interview->interview_id)
            ->where('i.interviewer_name', $validated['interviewer_name'])
            ->where('i.interview_date', $validated['interview_date'])
            ->whereTime('i.interview_time', '=', $validated['interview_time'] . ':00')
            ->whereIn('i.interview_status', ['Scheduled', 'Rescheduled'])
            ->exists();
        if ($conflict) {
            return back()->withInput()->with('error', 'This interviewer already has another interview scheduled at the selected date and time.');
        }

        try {
            DB::transaction(function () use ($validated, $interview) {
                DB::table('interviews')
                    ->where('interview_id', $interview->interview_id)
                    ->update([
                        'interviewer_name' => $validated['interviewer_name'],
                        'interview_type' => $validated['interview_type'],
                        'interview_date' => $validated['interview_date'],
                        'interview_time' => $validated['interview_time'],
                        'meeting_link' => $validated['meeting_link'],
                        'location' => $validated['location'],
                        'remarks' => $validated['remarks'],
                        'updated_at' => now(),
                    ]);
            });
        } catch (QueryException $exception) {
            Log::error('Employer interview update failed.', [
                'user_id' => $request->user()->user_id,
                'interview_id' => $interview->interview_id,
                'exception' => $exception->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Unable to update the interview right now. Please try again.');
        }

        return redirect()->route('employer.interview-details', ['interview_id' => $interview->interview_id])
            ->with('success', 'Interview updated successfully.');
    }

    public function updateStatus(Request $request)
    {
        $validated = $request->validate([
            'interview_id' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'in:' . implode(',', self::STATUSES)],
        ]);
        $employer = $this->employerFor($request);

        try {
            $result = DB::transaction(function () use ($validated, $employer) {
                $interview = DB::table('interviews as i')
                    ->join('applications as a', 'a.application_id', '=', 'i.application_id')
                    ->join('jobs as j', 'j.job_id', '=', 'a.job_id')
                    ->where('i.interview_id', $validated['interview_id'])
                    ->where('j.employer_id', $employer->employer_id)
                    ->where('j.company_id', $employer->company_id)
                    ->lockForUpdate()
                    ->first([
                        'i.interview_id',
                        'i.interview_status',
                        'a.application_id',
                        'a.application_status',
                    ]);
                if (!$interview) {
                    return 'missing';
                }
                if ($interview->interview_status === $validated['status']) {
                    return 'same';
                }
                if (in_array($interview->interview_status, ['Completed', 'Cancelled'], true)) {
                    return 'closed';
                }
                if (in_array($interview->application_status, ['Rejected', 'Withdrawn'], true)) {
                    return 'inactive';
                }

                DB::table('interviews')
                    ->where('interview_id', $interview->interview_id)
                    ->update(['interview_status' => $validated['status'], 'updated_at' => now()]);

                if (in_array($validated['status'], ['Scheduled', 'Rescheduled'], true)) {
                    DB::table('applications')
                        ->where('application_id', $interview->application_id)
                        ->update(['application_status' => 'Interview Scheduled', 'updated_at' => now()]);
                } elseif ($validated['status'] === 'Cancelled') {
                    DB::table('applications')
                        ->where('application_id', $interview->application_id)
                        ->where('application_status', 'Interview Scheduled')
                        ->update(['application_status' => 'Under Review', 'updated_at' => now()]);
                }

                return 'updated';
            });
        } catch (QueryException $exception) {
            Log::error('Employer interview status update failed.', [
                'user_id' => $request->user()->user_id,
                'interview_id' => $validated['interview_id'],
                'exception' => $exception->getMessage(),
            ]);

            return back()->with('error', 'Unable to update the interview status. Please try again later.');
        }

        if ($result !== 'updated') {
            $message = match ($result) {
                'same' => 'The interview is already marked with that status.',
                'closed' => 'A completed or cancelled interview cannot be changed.',
                'inactive' => 'The interview cannot be updated because the application is no longer active.',
                default => 'Interview not found or you do not have permission to update it.',
            };
            if ($result === 'missing') {
                return redirect()->route('employer.interviews')->with('error', $message);
            }

            return redirect()->route('employer.interview-details', ['interview_id' => $validated['interview_id']])
                ->with('error', $message);
        }

        $message = match ($validated['status']) {
            'Cancelled' => 'Interview cancelled successfully. The interview record has been preserved.',
            'Completed' => 'Interview marked as completed successfully.',
            'Rescheduled' => 'Interview marked as rescheduled successfully.',
            default => 'Interview status updated successfully.',
        };

        return redirect()->route('employer.interview-details', ['interview_id' => $validated['interview_id']])
            ->with('success', $message);
    }

    private function validateInterview(Request $request, bool $isEdit = false): array
    {
        $rules = [
            'application_id' => [$isEdit ? 'nullable' : 'required', 'integer', 'min:1'],
            'interview_id' => [$isEdit ? 'required' : 'nullable', 'integer', 'min:1'],
            'interviewer_name' => ['required', 'string', 'max:150'],
            'interview_type' => ['required', 'in:' . implode(',', self::TYPES)],
            'interview_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'interview_time' => ['required', 'date_format:H:i'],
            'meeting_link' => ['nullable', 'string', 'max:1000', 'required_if:interview_type,Online', 'url'],
            'location' => ['nullable', 'string', 'max:255', 'required_if:interview_type,Onsite'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
        if (!$isEdit) {
            $rules['interview_id'] = ['prohibited'];
        } else {
            $rules['application_id'] = ['prohibited'];
        }

        $validated = $request->validate($rules);
        if (!empty($validated['meeting_link'])) {
            $scheme = strtolower((string) parse_url($validated['meeting_link'], PHP_URL_SCHEME));
            if (!in_array($scheme, ['http', 'https'], true)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'meeting_link' => 'Meeting link must use HTTP or HTTPS.',
                ]);
            }
        }
        if ($validated['interview_type'] !== 'Online') {
            $validated['meeting_link'] = null;
        }
        if ($validated['interview_type'] !== 'Onsite') {
            $validated['location'] = null;
        }
        $validated['interviewer_name'] = trim($validated['interviewer_name']);
        $validated['remarks'] = trim((string) ($validated['remarks'] ?? '')) ?: null;

        return $validated;
    }

    private function employerFor(Request $request): object
    {
        $employer = DB::table('employers')
            ->where('user_id', $request->user()->user_id)
            ->first(['employer_id', 'company_id']);
        abort_unless($employer, 403, 'Employer profile could not be found.');

        return $employer;
    }

    private function interviewQuery(object $employer)
    {
        return DB::table('interviews as i')
            ->join('applications as a', 'a.application_id', '=', 'i.application_id')
            ->join('users as u', 'u.user_id', '=', 'a.user_id')
            ->leftJoin('job_seekers as js', 'js.user_id', '=', 'a.user_id')
            ->join('jobs as j', 'j.job_id', '=', 'a.job_id')
            ->join('categories as c', 'c.category_id', '=', 'j.category_id')
            ->join('companies as co', 'co.company_id', '=', 'j.company_id')
            ->where('j.employer_id', $employer->employer_id)
            ->where('j.company_id', $employer->company_id);
    }

    private function applicationQuery(object $employer)
    {
        return DB::table('applications as a')
            ->join('users as u', 'u.user_id', '=', 'a.user_id')
            ->join('job_seekers as js', 'js.user_id', '=', 'a.user_id')
            ->join('jobs as j', 'j.job_id', '=', 'a.job_id')
            ->join('categories as c', 'c.category_id', '=', 'j.category_id')
            ->join('companies as co', 'co.company_id', '=', 'j.company_id')
            ->where('j.employer_id', $employer->employer_id)
            ->where('j.company_id', $employer->company_id);
    }

    private function ownedInterview(object $employer, int $interviewId): ?object
    {
        return DB::table('interviews as i')
            ->join('applications as a', 'a.application_id', '=', 'i.application_id')
            ->join('users as u', 'u.user_id', '=', 'a.user_id')
            ->leftJoin('job_seekers as js', 'js.user_id', '=', 'a.user_id')
            ->join('jobs as j', 'j.job_id', '=', 'a.job_id')
            ->join('categories as c', 'c.category_id', '=', 'j.category_id')
            ->join('companies as co', 'co.company_id', '=', 'j.company_id')
            ->where('i.interview_id', $interviewId)
            ->where('j.employer_id', $employer->employer_id)
            ->where('j.company_id', $employer->company_id)
            ->first([
                'i.interview_id',
                'i.application_id',
                'i.interviewer_name',
                'i.interview_type',
                'i.interview_date',
                'i.interview_time',
                'i.meeting_link',
                'i.location',
                'i.interview_status',
                'i.remarks',
                'i.created_at',
                'i.updated_at',
                'a.user_id',
                'a.job_id',
                'a.application_status',
                'a.skill_match_score',
                'a.ai_rank',
                'u.full_name',
                'u.email',
                'js.seeker_id',
                'js.resume_file',
                'js.profile_completed',
                'js.availability',
                'j.job_title',
                'j.location as job_location',
                'j.employment_type',
                'j.workplace',
                'j.experience_level',
                'j.job_status',
                'c.category_id',
                'c.category_name',
                'co.company_id',
                'co.company_name',
                'co.logo',
                'co.status as company_status',
            ]);
    }
}
