<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployerJobController extends Controller
{
    private const STATUSES = ['Draft', 'Open', 'Closed', 'Expired'];
    private const POST_STATUSES = ['Draft', 'Open'];
    private const EMPLOYMENT_TYPES = ['Full-Time', 'Part-Time', 'Contract', 'Internship', 'Freelance'];
    private const WORKPLACES = ['Onsite', 'Remote', 'Hybrid'];
    private const EXPERIENCE_LEVELS = ['Fresher', 'Junior', 'Mid', 'Senior', 'Expert'];
    private const CURRENCIES = ['USD', 'NPR', 'EUR', 'GBP', 'INR'];

    public function index(Request $request)
    {
        $employer = $this->employerFor($request);
        $status = in_array($request->query('status'), self::STATUSES, true)
            ? $request->query('status')
            : '';
        $search = mb_substr(trim((string) $request->query('search', '')), 0, 100);

        $query = DB::table('jobs as j')
            ->join('categories as c', 'c.category_id', '=', 'j.category_id')
            ->join('companies as co', 'co.company_id', '=', 'j.company_id')
            ->where('j.employer_id', $employer->employer_id)
            ->when($status !== '', fn ($query) => $query->where('j.job_status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('j.job_title', 'like', '%' . $search . '%')
                        ->orWhere('j.location', 'like', '%' . $search . '%')
                        ->orWhere('c.category_name', 'like', '%' . $search . '%');
                });
            });

        $jobs = $query->orderByDesc('j.job_id')->get([
            'j.job_id',
            'j.company_id',
            'j.employer_id',
            'j.category_id',
            'j.job_title',
            'j.employment_type',
            'j.workplace',
            'j.experience_level',
            'j.location',
            'j.job_status',
            'c.category_name',
            'co.company_name',
            'co.logo',
        ]);
        $skillMap = collect();
        if ($jobs->isNotEmpty()) {
            $skillMap = DB::table('job_skills as js')
                ->join('skills as s', 's.skill_id', '=', 'js.skill_id')
                ->whereIn('js.job_id', $jobs->pluck('job_id'))
                ->orderBy('s.skill_name')
                ->get(['js.job_id', 's.skill_name'])
                ->groupBy('job_id')
                ->map(fn ($skills) => $skills->pluck('skill_name'));
        }

        $counts = DB::table('jobs')
            ->where('employer_id', $employer->employer_id)
            ->select('job_status')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('job_status')
            ->pluck('total', 'job_status');
        $statusCounts = [
            'All' => $counts->only(self::STATUSES)->sum(),
            'Draft' => (int) ($counts['Draft'] ?? 0),
            'Open' => (int) ($counts['Open'] ?? 0),
            'Closed' => (int) ($counts['Closed'] ?? 0),
            'Expired' => (int) ($counts['Expired'] ?? 0),
        ];
        $company = $employer->company_id
            ? DB::table('companies')->where('company_id', $employer->company_id)->first(['company_id', 'company_name', 'logo', 'status'])
            : null;

        return view('employer.manage-jobs', compact(
            'employer',
            'company',
            'jobs',
            'skillMap',
            'status',
            'search',
            'statusCounts'
        ));
    }

    public function create(Request $request)
    {
        $employer = $this->employerFor($request);
        $company = $this->approvedCompany($employer);
        if (!$company) {
            return redirect()->route('employer.company-profile')
                ->with('company_profile_error', 'Your company must be approved before you can post a job.');
        }

        return view('employer.post-job', [
            'company' => $company,
            'categories' => DB::table('categories')->where('status', 'Active')->orderBy('category_name')->get(['category_id', 'category_name']),
            'skills' => DB::table('skills')->orderBy('skill_name')->get(['skill_id', 'skill_name']),
        ]);
    }

    public function store(Request $request)
    {
        $employer = $this->employerFor($request);
        $company = $this->approvedCompany($employer);
        if (!$company) {
            return redirect()->route('employer.company-profile')
                ->with('company_profile_error', 'Your company must be approved before you can post a job.');
        }

        $validated = $this->validateJob($request, true);
        $categoryExists = DB::table('categories')
            ->where('category_id', $validated['category_id'])
            ->where('status', 'Active')
            ->exists();
        if (!$categoryExists) {
            return back()->withInput()->with('danger', 'The selected job category does not exist.');
        }

        try {
            DB::transaction(function () use ($validated, $employer, $company) {
                $jobId = DB::table('jobs')->insertGetId($this->jobValues($validated, [
                    'company_id' => $company->company_id,
                    'employer_id' => $employer->employer_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
                $this->syncSkills($jobId, $validated['skills'] ?? []);
            });
        } catch (QueryException $exception) {
            Log::error('Employer job posting failed.', [
                'user_id' => $request->user()->user_id,
                'exception' => $exception->getMessage(),
            ]);

            return back()->withInput()->with('danger', 'Unable to save the job posting. Please try again.');
        }

        return redirect()->route('employer.jobs')
            ->with('success', $validated['job_status'] === 'Open'
                ? 'Job posted successfully and is now open for applications.'
                : 'Job saved successfully as a draft.');
    }

    public function edit(Request $request)
    {
        $jobId = filter_var($request->query('id'), FILTER_VALIDATE_INT);
        if ($jobId === false || $jobId === null || $jobId < 1) {
            return redirect()->route('employer.jobs')->with('danger', 'Invalid job selected.');
        }

        $employer = $this->employerFor($request);
        $job = DB::table('jobs as j')
            ->join('categories as c', 'c.category_id', '=', 'j.category_id')
            ->join('companies as co', 'co.company_id', '=', 'j.company_id')
            ->where('j.job_id', $jobId)
            ->where('j.employer_id', $employer->employer_id)
            ->first([
                'j.*',
                'c.category_name',
                'co.company_name',
                'co.logo',
                'co.status as company_status',
            ]);
        if (!$job || (int) $job->company_id !== (int) $employer->company_id) {
            return redirect()->route('employer.jobs')->with('danger', 'Job not found or you do not have permission to edit it.');
        }

        $selectedSkills = DB::table('job_skills')
            ->where('job_id', $jobId)
            ->pluck('skill_id')
            ->map(fn ($skillId) => (int) $skillId)
            ->all();

        return view('employer.edit-job', [
            'job' => $job,
            'categories' => DB::table('categories')->orderBy('category_name')->get(['category_id', 'category_name']),
            'skills' => DB::table('skills')->orderBy('skill_name')->get(['skill_id', 'skill_name']),
            'selectedSkills' => $selectedSkills,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $this->validateJob($request, false);
        $jobId = (int) $validated['job_id'];
        $employer = $this->employerFor($request);
        $existingJob = DB::table('jobs')
            ->where('job_id', $jobId)
            ->where('employer_id', $employer->employer_id)
            ->first(['job_id', 'company_id']);
        if (!$existingJob || (int) $existingJob->company_id !== (int) $employer->company_id) {
            return redirect()->route('employer.jobs')->with('danger', 'Job not found or you do not have permission to edit it.');
        }

        if (!DB::table('categories')->where('category_id', $validated['category_id'])->exists()) {
            return back()->withInput()->with('danger', 'Selected job category does not exist.');
        }
        $companyStatus = DB::table('companies')->where('company_id', $employer->company_id)->value('status');
        if ($validated['job_status'] === 'Open' && $companyStatus !== 'Approved') {
            return back()->withInput()->with('danger', 'Your company must be approved before a job can be published.');
        }

        try {
            DB::transaction(function () use ($validated, $employer, $jobId) {
                DB::table('jobs')
                    ->where('job_id', $jobId)
                    ->where('employer_id', $employer->employer_id)
                    ->update($this->jobValues($validated, ['updated_at' => now()]));
                $this->syncSkills($jobId, $validated['skills'] ?? []);
            });
        } catch (QueryException $exception) {
            Log::error('Employer job update failed.', [
                'user_id' => $request->user()->user_id,
                'job_id' => $jobId,
                'exception' => $exception->getMessage(),
            ]);

            return back()->withInput()->with('danger', 'Unable to update the job posting. Please try again.');
        }

        return redirect()->route('employer.jobs')->with('success', 'Job posting updated successfully.');
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate(['job_id' => ['required', 'integer', 'min:1']]);
        $employer = $this->employerFor($request);
        $job = DB::table('jobs')
            ->where('job_id', $validated['job_id'])
            ->where('employer_id', $employer->employer_id)
            ->first(['job_id', 'company_id', 'job_title']);

        if (!$job || (int) $job->company_id !== (int) $employer->company_id) {
            return redirect()->route('employer.jobs')
                ->with('danger', 'Job not found or you do not have permission to delete this job.');
        }

        try {
            $applicationCount = DB::transaction(function () use ($job, $employer) {
                $lockedJob = DB::table('jobs')
                    ->where('job_id', $job->job_id)
                    ->where('employer_id', $employer->employer_id)
                    ->lockForUpdate()
                    ->first(['job_id']);
                if (!$lockedJob) {
                    return -1;
                }

                $count = DB::table('applications')->where('job_id', $job->job_id)->count();
                if ($count > 0) {
                    return $count;
                }

                DB::table('job_skills')->where('job_id', $job->job_id)->delete();
                DB::table('jobs')
                    ->where('job_id', $job->job_id)
                    ->where('employer_id', $employer->employer_id)
                    ->delete();

                return 0;
            });
        } catch (QueryException $exception) {
            Log::error('Employer job deletion failed.', [
                'user_id' => $request->user()->user_id,
                'job_id' => $job->job_id,
                'exception' => $exception->getMessage(),
            ]);

            return redirect()->route('employer.jobs')->with('danger', 'Unable to delete the job. Please try again.');
        }

        if ($applicationCount < 0) {
            return redirect()->route('employer.jobs')->with('danger', 'The job could not be deleted.');
        }
        if ($applicationCount > 0) {
            return redirect()->route('employer.jobs')->with(
                'danger',
                'This job cannot be deleted because it already has ' . $applicationCount
                    . ' application(s). Please close the job instead.'
            );
        }

        return redirect()->route('employer.jobs')
            ->with('success', sprintf('Job "%s" was deleted successfully.', $job->job_title));
    }

    private function validateJob(Request $request, bool $isCreate): array
    {
        $rules = [
            'job_title' => ['required', 'string', 'max:150'],
            'category_id' => ['required', 'integer', 'min:1'],
            'job_description' => ['required', 'string', 'max:10000'],
            'job_requirements' => ['nullable', 'string', 'max:10000'],
            'job_responsibilities' => ['nullable', 'string', 'max:10000'],
            'employment_type' => ['required', 'in:' . implode(',', self::EMPLOYMENT_TYPES)],
            'workplace' => ['required', 'in:' . implode(',', self::WORKPLACES)],
            'experience_level' => ['required', 'in:' . implode(',', self::EXPERIENCE_LEVELS)],
            'minimum_experience' => ['nullable', 'numeric', 'min:0', 'max:99'],
            'minimum_salary' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'maximum_salary' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'currency' => ['required', 'in:' . implode(',', self::CURRENCIES)],
            'vacancy' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'location' => [$isCreate ? 'nullable' : 'required', 'string', 'max:150'],
            'application_deadline' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'job_status' => ['required', 'in:' . implode(',', $isCreate ? self::POST_STATUSES : self::STATUSES)],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['integer', 'exists:skills,skill_id'],
        ];
        if ($request->filled('minimum_salary')) {
            $rules['maximum_salary'][] = 'gte:minimum_salary';
        }
        if (!$isCreate) {
            $rules['job_id'] = ['required', 'integer', 'min:1'];
        }

        $validated = $request->validate($rules);
        $validated['minimum_experience'] = round((float) ($validated['minimum_experience'] ?? 0), 1);
        $validated['minimum_salary'] = isset($validated['minimum_salary'])
            ? round((float) $validated['minimum_salary'], 2)
            : null;
        $validated['maximum_salary'] = isset($validated['maximum_salary'])
            ? round((float) $validated['maximum_salary'], 2)
            : null;
        $validated['vacancy'] = (int) ($validated['vacancy'] ?? 1);
        $validated['currency'] = strtoupper($validated['currency']);
        $validated['skills'] = array_values(array_unique(array_map('intval', $validated['skills'] ?? [])));

        return $validated;
    }

    private function jobValues(array $validated, array $relations = []): array
    {
        return $relations + [
            'category_id' => $validated['category_id'],
            'job_title' => trim($validated['job_title']),
            'job_description' => trim($validated['job_description']),
            'job_requirements' => trim((string) ($validated['job_requirements'] ?? '')) ?: null,
            'job_responsibilities' => trim((string) ($validated['job_responsibilities'] ?? '')) ?: null,
            'employment_type' => $validated['employment_type'],
            'workplace' => $validated['workplace'],
            'experience_level' => $validated['experience_level'],
            'minimum_experience' => $validated['minimum_experience'],
            'minimum_salary' => $validated['minimum_salary'],
            'maximum_salary' => $validated['maximum_salary'],
            'currency' => $validated['currency'],
            'vacancy' => $validated['vacancy'],
            'location' => trim((string) ($validated['location'] ?? '')) ?: null,
            'application_deadline' => $validated['application_deadline'] ?? null,
            'job_status' => $validated['job_status'],
        ];
    }

    private function syncSkills(int $jobId, array $skillIds): void
    {
        DB::table('job_skills')->where('job_id', $jobId)->delete();
        if ($skillIds) {
            DB::table('job_skills')->insert(array_map(
                static fn ($skillId) => ['job_id' => $jobId, 'skill_id' => $skillId],
                $skillIds
            ));
        }
    }

    private function employerFor(Request $request): object
    {
        $employer = DB::table('employers')
            ->where('user_id', $request->user()->user_id)
            ->first(['employer_id', 'user_id', 'company_id', 'designation', 'department']);
        abort_unless($employer, 403, 'Employer profile could not be found.');

        return $employer;
    }

    private function approvedCompany(object $employer): ?object
    {
        if (!$employer->company_id) {
            return null;
        }

        $company = DB::table('companies')
            ->where('company_id', $employer->company_id)
            ->first(['company_id', 'company_name', 'logo', 'status']);

        return $company && $company->status === 'Approved' ? $company : null;
    }
}
