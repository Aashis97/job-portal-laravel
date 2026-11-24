<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class LandingController extends Controller
{
    public function index()
    {
        $stats = [
            'jobs' => DB::table('jobs')->where('job_status', 'Open')->count(),
            'companies' => DB::table('companies')->where('status', 'Approved')->count(),
            'candidates' => DB::table('users')->where('role_id', 3)->where('account_status', 'Active')->count(),
        ];

        $categories = DB::table('categories')
            ->leftJoin('jobs', function ($join) {
                $join->on('jobs.category_id', '=', 'categories.category_id')
                    ->where('jobs.job_status', '=', 'Open');
            })
            ->where('categories.status', 'Active')
            ->select('categories.category_id', 'categories.category_name')
            ->selectRaw('COUNT(jobs.job_id) AS job_count')
            ->groupBy('categories.category_id', 'categories.category_name')
            ->orderByDesc('job_count')
            ->orderBy('categories.category_name')
            ->limit(8)
            ->get();

        $featuredJobs = DB::table('jobs')
            ->join('companies', 'companies.company_id', '=', 'jobs.company_id')
            ->where('jobs.job_status', 'Open')
            ->where(function ($query) {
                $query->whereNull('jobs.application_deadline')
                    ->orWhere('jobs.application_deadline', '>=', now()->toDateString());
            })
            ->select(
                'jobs.*',
                'companies.company_id',
                'companies.company_name',
                'companies.logo'
            )
            ->orderByDesc('jobs.job_id')
            ->limit(6)
            ->get();

        return view('landing', compact('stats', 'categories', 'featuredJobs'));
    }
}
