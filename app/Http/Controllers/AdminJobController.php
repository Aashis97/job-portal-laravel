<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminJobController extends Controller
{
    private const STATUSES = ['Draft', 'Open', 'Closed', 'Expired'];

    public function index(Request $request)
    {
        $status = in_array($request->query('status'), self::STATUSES, true)
            ? $request->query('status')
            : '';
        $categoryId = filter_var($request->query('category_id'), FILTER_VALIDATE_INT);
        $categoryId = $categoryId && $categoryId > 0 ? $categoryId : 0;
        $companyId = filter_var($request->query('company_id'), FILTER_VALIDATE_INT);
        $companyId = $companyId && $companyId > 0 ? $companyId : 0;
        $overdue = $request->query('overdue') === '1';
        $search = mb_substr(trim((string) $request->query('q', '')), 0, 100);

        $query = DB::table('jobs as j')
            ->leftJoin('categories as c', 'c.category_id', '=', 'j.category_id')
            ->leftJoin('companies as co', 'co.company_id', '=', 'j.company_id')
            ->when($status !== '', fn ($query) => $query->where('j.job_status', $status))
            ->when($categoryId > 0, fn ($query) => $query->where('j.category_id', $categoryId))
            ->when($companyId > 0, fn ($query) => $query->where('j.company_id', $companyId))
            ->when($overdue, fn ($query) => $query->where('j.job_status', 'Open')
                ->whereNotNull('j.application_deadline')
                ->whereDate('j.application_deadline', '<', today()))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('j.job_title', 'like', '%' . $search . '%')
                        ->orWhere('j.location', 'like', '%' . $search . '%')
                        ->orWhere('co.company_name', 'like', '%' . $search . '%');
                });
            });

        $jobs = $query->orderByDesc('j.job_id')->paginate(25, [
            'j.job_id',
            'j.job_title',
            'j.employment_type',
            'j.workplace',
            'j.experience_level',
            'j.location',
            'j.job_status',
            'j.application_deadline',
            'j.views',
            'j.total_applications',
            'j.created_at',
            'c.category_name',
            'co.company_id',
            'co.company_name',
        ])->withQueryString();

        $categories = DB::table('categories')
            ->where('status', 'Active')
            ->orderBy('category_name')
            ->get(['category_id', 'category_name']);

        $stats = DB::table('jobs')
            ->selectRaw('COUNT(*) AS all_count')
            ->selectRaw("SUM(CASE WHEN job_status = 'Open' THEN 1 ELSE 0 END) AS open_count")
            ->selectRaw("SUM(CASE WHEN job_status = 'Draft' THEN 1 ELSE 0 END) AS draft_count")
            ->selectRaw("SUM(CASE WHEN job_status = 'Closed' THEN 1 ELSE 0 END) AS closed_count")
            ->selectRaw("SUM(CASE WHEN job_status = 'Expired' THEN 1 ELSE 0 END) AS expired_count")
            ->selectRaw('COALESCE(SUM(views), 0) AS total_views')
            ->selectRaw('COALESCE(SUM(total_applications), 0) AS total_applications')
            ->selectRaw("SUM(CASE WHEN job_status = 'Open' AND application_deadline IS NOT NULL AND application_deadline < ? THEN 1 ELSE 0 END) AS overdue_open", [today()->toDateString()])
            ->first();

        return view('admin.jobs', compact(
            'jobs',
            'categories',
            'stats',
            'status',
            'categoryId',
            'companyId',
            'overdue',
            'search'
        ));
    }

    public function show(Request $request)
    {
        $validated = $request->validate(['id' => ['required', 'integer', 'min:1']]);
        $job = DB::table('jobs as j')
            ->join('categories as c', 'c.category_id', '=', 'j.category_id')
            ->leftJoin('companies as co', 'co.company_id', '=', 'j.company_id')
            ->leftJoin('employers as e', 'e.employer_id', '=', 'j.employer_id')
            ->leftJoin('users as eu', 'eu.user_id', '=', 'e.user_id')
            ->where('j.job_id', $validated['id'])
            ->select([
                'j.*',
                'c.category_name',
                'co.company_name',
                'co.logo as company_logo',
                'co.status as company_status',
                'co.city as company_city',
                'co.country as company_country',
                'eu.user_id as employer_user_id',
                'eu.full_name as employer_name',
                'eu.email as employer_email',
                'eu.account_status as employer_status',
            ])
            ->selectSub(DB::table('applications')->selectRaw('COUNT(*)')->whereColumn('applications.job_id', 'j.job_id'), 'applications_count')
            ->selectSub(DB::table('saved_jobs')->selectRaw('COUNT(*)')->whereColumn('saved_jobs.job_id', 'j.job_id'), 'saved_count')
            ->first();
        abort_unless($job, 404);

        $skills = DB::table('job_skills as js')
            ->join('skills as s', 's.skill_id', '=', 'js.skill_id')
            ->where('js.job_id', $job->job_id)
            ->orderBy('s.skill_name')
            ->get(['s.skill_id', 's.skill_name', 'js.importance']);
        $recentApplications = DB::table('applications as a')
            ->join('users as u', 'u.user_id', '=', 'a.user_id')
            ->where('a.job_id', $job->job_id)
            ->orderByDesc('a.application_id')
            ->limit(8)
            ->get([
                'a.application_id',
                'a.application_status',
                'a.skill_match_score',
                'a.applied_at',
                'u.user_id',
                'u.full_name as applicant_name',
                'u.email as applicant_email',
            ]);

        return view('admin.job-details', compact('job', 'skills', 'recentApplications'));
    }

    public function updateStatus(Request $request)
    {
        $validated = $request->validate([
            'job_id' => ['required', 'integer', 'min:1'],
            'action' => ['required', 'in:publish,close,reopen,expire,delete'],
        ]);
        $jobId = (int) $validated['job_id'];
        $action = $validated['action'];

        $result = DB::transaction(function () use ($jobId, $action) {
            $job = DB::table('jobs')->where('job_id', $jobId)->lockForUpdate()->first(['job_id', 'job_title', 'job_status']);
            if (!$job) {
                return 'missing';
            }

            $allowed = match ($action) {
                'publish' => $job->job_status !== 'Open',
                'close', 'expire' => $job->job_status === 'Open',
                'reopen' => $job->job_status === 'Closed',
                'delete' => true,
            };
            if (!$allowed) {
                return 'invalid';
            }

            if ($action === 'delete') {
                $applicationIds = DB::table('applications')
                    ->where('job_id', $jobId)
                    ->pluck('application_id');
                if ($applicationIds->isNotEmpty()) {
                    DB::table('interviews')->whereIn('application_id', $applicationIds)->delete();
                }
                DB::table('applications')->where('job_id', $jobId)->delete();
                DB::table('saved_jobs')->where('job_id', $jobId)->delete();
                DB::table('job_skills')->where('job_id', $jobId)->delete();
                DB::table('jobs')->where('job_id', $jobId)->delete();

                return 'deleted';
            }

            $status = match ($action) {
                'publish', 'reopen' => 'Open',
                'close' => 'Closed',
                'expire' => 'Expired',
            };
            DB::table('jobs')->where('job_id', $jobId)->update([
                'job_status' => $status,
                'updated_at' => now(),
            ]);

            return $status;
        });

        if ($result === 'missing') {
            return redirect()->route('admin.jobs')->with('danger', 'Job not found.');
        }
        if ($result === 'invalid') {
            $message = match ($action) {
                'close' => 'Only open jobs can be closed.',
                'reopen' => 'Only closed jobs can be reopened.',
                'expire' => 'Only open jobs can be marked expired.',
                default => 'Job is already open.',
            };
            return redirect()->route('admin.job-details', ['id' => $jobId])->with('danger', $message);
        }
        if ($result === 'deleted') {
            return redirect()->route('admin.jobs')->with('success', 'Job and all associated applications deleted permanently.');
        }

        $message = match ($action) {
            'publish' => 'Job published and visible to job seekers.',
            'close' => 'Job closed. It will no longer accept applications.',
            'reopen' => 'Job reopened and accepting applications again.',
            'expire' => 'Job marked as expired.',
        };
        return redirect()->route('admin.job-details', ['id' => $jobId])->with('success', $message);
    }
}
