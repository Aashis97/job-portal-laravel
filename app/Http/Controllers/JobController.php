<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Category;
use App\Models\Job;
use App\Models\SavedJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JobController extends Controller
{
    private const WORKPLACES = ['Onsite', 'Remote', 'Hybrid'];
    private const EMPLOYMENT_TYPES = ['Full-Time', 'Part-Time', 'Contract', 'Internship', 'Freelance'];
    private const EXPERIENCE_LEVELS = ['Fresher', 'Junior', 'Mid', 'Senior', 'Expert'];

    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'min:0'],
            'workplace' => ['nullable', 'in:Onsite,Remote,Hybrid'],
            'employment_type' => ['nullable', 'in:Full-Time,Part-Time,Contract,Internship,Freelance'],
            'experience_level' => ['nullable', 'in:Fresher,Junior,Mid,Senior,Expert'],
            'min_salary' => ['nullable', 'numeric', 'min:0'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $userId = (int) $request->user()->user_id;
        $query = Job::query()->from('jobs as j')
            ->join('companies as c', 'c.company_id', '=', 'j.company_id')
            ->leftJoin('categories as cat', 'cat.category_id', '=', 'j.category_id')
            ->leftJoin('saved_jobs as sj', function ($join) use ($userId) {
                $join->on('sj.job_id', '=', 'j.job_id')->where('sj.user_id', '=', $userId);
            })
            ->leftJoin('applications as a', function ($join) use ($userId) {
                $join->on('a.job_id', '=', 'j.job_id')->where('a.user_id', '=', $userId);
            })
            ->where('j.job_status', 'Open')
            ->select([
                'j.job_id',
                'j.job_title',
                'j.employment_type',
                'j.workplace',
                'j.experience_level',
                'j.location',
                'j.minimum_salary',
                'j.maximum_salary',
                'j.currency',
                'j.created_at',
                'c.company_id',
                'c.company_name',
                'c.logo',
                'cat.category_name',
                'sj.saved_job_id',
                'a.application_id',
            ]);

        $query->when($filters['q'] ?? null, function ($query, $term) {
            $like = '%' . $term . '%';
            $query->where(function ($subquery) use ($like) {
                $subquery->where('j.job_title', 'like', $like)
                    ->orWhere('c.company_name', 'like', $like)
                    ->orWhere('j.location', 'like', $like);
            });
        });
        $query->when(($filters['category_id'] ?? 0) > 0, fn ($query) => $query->where('j.category_id', $filters['category_id']));
        $query->when($filters['workplace'] ?? null, fn ($query, $value) => $query->where('j.workplace', $value));
        $query->when($filters['employment_type'] ?? null, fn ($query, $value) => $query->where('j.employment_type', $value));
        $query->when($filters['experience_level'] ?? null, fn ($query, $value) => $query->where('j.experience_level', $value));
        $query->when(($filters['min_salary'] ?? 0) > 0, function ($query) use ($filters) {
            $query->whereRaw('COALESCE(j.maximum_salary, j.minimum_salary, 0) >= ?', [$filters['min_salary']]);
        });

        $jobs = $query->orderByDesc('j.job_id')->paginate(12)->withQueryString();
        $categories = Category::query()
            ->where('status', 'Active')
            ->orderBy('category_name')
            ->get(['category_id', 'category_name']);

        return view('jobs.index', [
            'jobs' => $jobs,
            'categories' => $categories,
            'filters' => array_merge([
                'q' => '',
                'category_id' => 0,
                'workplace' => '',
                'employment_type' => '',
                'experience_level' => '',
                'min_salary' => '',
            ], $filters),
            'workplaces' => self::WORKPLACES,
            'employmentTypes' => self::EMPLOYMENT_TYPES,
            'experienceLevels' => self::EXPERIENCE_LEVELS,
        ]);
    }

    public function show(Request $request)
    {
        $id = filter_var($request->query('id'), FILTER_VALIDATE_INT);
        if (!$id || $id < 1) {
            return redirect()->route('jobs.index')->with('danger', 'Invalid job selected.');
        }

        $userId = (int) $request->user()->user_id;
        $job = Job::query()->from('jobs as j')
            ->join('companies as c', 'c.company_id', '=', 'j.company_id')
            ->leftJoin('categories as cat', 'cat.category_id', '=', 'j.category_id')
            ->where('j.job_id', $id)
            ->select([
                'j.*',
                'c.company_name',
                'c.logo',
                'c.company_email',
                'c.website',
                'c.industry',
                'c.description as company_description',
                'c.city as company_city',
                'c.country as company_country',
                'cat.category_name',
            ])
            ->first();

        if (!$job) {
            return redirect()->route('jobs.index')->with('danger', 'The job you requested does not exist.');
        }

        Job::query()->where('job_id', $id)->increment('views');
        $job->views = (int) $job->views + 1;

        $requiredSkills = DB::table('job_skills as js')
            ->join('skills as s', 's.skill_id', '=', 'js.skill_id')
            ->where('js.job_id', $id)
            ->orderBy('js.importance')
            ->orderBy('s.skill_name')
            ->get(['s.skill_id', 's.skill_name', 'js.importance']);

        $application = Application::query()
            ->where('user_id', $userId)
            ->where('job_id', $id)
            ->first(['application_id', 'application_status', 'applied_at']);

        $isSaved = SavedJob::query()
            ->where('user_id', $userId)
            ->where('job_id', $id)
            ->exists();

        $relatedJobs = Job::query()
            ->where('company_id', $job->company_id)
            ->where('job_id', '<>', $id)
            ->where('job_status', 'Open')
            ->orderByDesc('job_id')
            ->limit(4)
            ->get(['job_id', 'job_title', 'employment_type', 'workplace', 'created_at']);

        return view('jobs.show', compact('job', 'requiredSkills', 'application', 'isSaved', 'relatedJobs'));
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'job_id' => ['required', 'integer', 'min:1'],
            'return_to' => ['nullable', 'in:jobs,details,saved'],
        ]);
        $jobId = (int) $validated['job_id'];

        $isOpen = DB::table('jobs')->where('job_id', $jobId)->where('job_status', 'Open')->exists();
        if (!$isOpen) {
            return redirect()->route('jobs.index')->with('danger', 'The selected job is no longer available.');
        }

        SavedJob::query()->updateOrCreate(
            ['user_id' => (int) $request->user()->user_id, 'job_id' => $jobId],
            ['saved_at' => now()]
        );

        return $this->returnFromSave($validated['return_to'] ?? '', $jobId, 'Job saved.');
    }

    public function unsave(Request $request)
    {
        $validated = $request->validate([
            'job_id' => ['required', 'integer', 'min:1'],
            'return_to' => ['nullable', 'in:jobs,details,saved'],
        ]);
        $jobId = (int) $validated['job_id'];

        SavedJob::query()
            ->where('user_id', (int) $request->user()->user_id)
            ->where('job_id', $jobId)
            ->delete();

        return $this->returnFromSave($validated['return_to'] ?? '', $jobId, 'Job removed from saved list.');
    }

    private function returnFromSave(string $returnTo, int $jobId, string $message)
    {
        $redirect = match ($returnTo) {
            'details' => redirect()->route('jobs.show', ['id' => $jobId]),
            'jobs' => redirect()->route('jobs.index'),
            'saved' => redirect()->route('saved_jobs.index'),
            default => redirect()->route('jobs.index'),
        };

        return $redirect->with('success', $message);
    }
}
