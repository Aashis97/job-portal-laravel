<?php

namespace App\Http\Controllers;

use App\Models\SavedJob;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SavedJobController extends Controller
{
    public function index(Request $request)
    {
        $userId = (int) $request->user()->user_id;
        $queryText = trim((string) $request->query('q', ''));
        $queryText = Str::limit($queryText, 100, '');

        $savedJobs = SavedJob::query()->from('saved_jobs as sj')
            ->join('jobs as j', 'j.job_id', '=', 'sj.job_id')
            ->leftJoin('companies as c', 'c.company_id', '=', 'j.company_id')
            ->leftJoin('applications as a', function ($join) use ($userId) {
                $join->on('a.job_id', '=', 'j.job_id')
                    ->where('a.user_id', '=', $userId);
            })
            ->where('sj.user_id', $userId)
            ->when($queryText !== '', function ($query) use ($queryText) {
                $query->where(function ($search) use ($queryText) {
                    $search->where('j.job_title', 'like', '%' . $queryText . '%')
                        ->orWhere('c.company_name', 'like', '%' . $queryText . '%');
                });
            })
            ->select([
                'sj.saved_job_id',
                'sj.saved_at',
                'j.job_id',
                'j.job_title',
                'j.employment_type',
                'j.workplace',
                'j.experience_level',
                'j.location',
                'j.job_status',
                'j.application_deadline',
                'j.minimum_salary',
                'j.maximum_salary',
                'j.currency',
                'c.company_id',
                'c.company_name',
                'c.logo',
            ])
            ->selectRaw('CASE WHEN a.application_id IS NULL THEN 0 ELSE 1 END AS is_applied')
            ->orderByDesc('sj.saved_at')
            ->paginate(12)
            ->withQueryString();

        return view('saved-jobs.index', compact('savedJobs', 'queryText'));
    }
}
