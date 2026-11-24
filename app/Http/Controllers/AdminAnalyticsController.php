<?php

namespace App\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAnalyticsController extends Controller
{
    private const PERIODS = [
        '7d' => 7,
        '30d' => 30,
        '90d' => 90,
        '365d' => 365,
        'all' => 365,
    ];

    public function index(Request $request)
    {
        $period = (string) $request->query('period', '30d');
        if (!array_key_exists($period, self::PERIODS)) {
            $period = '30d';
        }
        $days = self::PERIODS[$period];
        $startDate = CarbonImmutable::today()->subDays($days - 1)->toDateString();
        $kpis = $this->kpis($startDate);

        $signupsSeries = $this->dailySeries('users', 'created_at', $startDate, $days);
        $jobsSeries = $this->dailySeries('jobs', 'created_at', $startDate, $days);
        $applicationsSeries = $this->dailySeries('applications', 'applied_at', $startDate, $days);

        $roleDistribution = $this->roleDistribution();
        $statusDistribution = $this->applicationStatusDistribution();
        $jobsByCategory = $this->jobsByCategory();
        $topCompanies = $this->topCompanies();
        $topSkills = $this->topSkills();
        $interviewSummary = $this->interviewSummary();
        $chartsPayload = [
            'signups' => $signupsSeries,
            'jobs' => $jobsSeries,
            'applications' => $applicationsSeries,
            'roleDistribution' => $roleDistribution,
            'statusDistribution' => $statusDistribution,
            'jobsByCategory' => $jobsByCategory,
            'topSkills' => $topSkills,
        ];

        return view('admin.analytics', compact(
            'period',
            'kpis',
            'topCompanies',
            'interviewSummary',
            'chartsPayload'
        ));
    }

    private function kpis(string $startDate): array
    {
        $newUsers = DB::table('users')->where('created_at', '>=', $startDate)->count();
        $newEmployers = DB::table('users')->where('role_id', 2)->where('created_at', '>=', $startDate)->count();
        $newJobseekers = DB::table('users')->where('role_id', 3)->where('created_at', '>=', $startDate)->count();
        $newCompanies = DB::table('companies')->where('created_at', '>=', $startDate)->count();
        $newJobs = DB::table('jobs')->where('created_at', '>=', $startDate)->count();
        $applications = DB::table('applications')->where('applied_at', '>=', $startDate);
        $newApplications = (clone $applications)->count();
        $selectedCount = (clone $applications)
            ->where('application_status', 'Selected')
            ->count();

        return [
            'new_users' => $newUsers,
            'new_employers' => $newEmployers,
            'new_jobseekers' => $newJobseekers,
            'new_companies' => $newCompanies,
            'new_jobs' => $newJobs,
            'new_applications' => $newApplications,
            'selected_count' => $selectedCount,
            'hiring_rate' => $newApplications > 0
                ? round(($selectedCount / $newApplications) * 100, 1)
                : 0.0,
            'pending_users' => DB::table('users')->where('account_status', 'Pending')->count(),
            'pending_companies' => DB::table('companies')->where('status', 'Pending')->count(),
            'draft_jobs' => DB::table('jobs')->where('job_status', 'Draft')->count(),
        ];
    }

    private function dailySeries(string $table, string $dateColumn, string $startDate, int $days): array
    {
        $rows = DB::table($table)
            ->where($dateColumn, '>=', $startDate)
            ->selectRaw("DATE($dateColumn) AS d, COUNT(*) AS c")
            ->groupBy('d')
            ->orderBy('d')
            ->get();
        $counts = [];
        foreach ($rows as $row) {
            $counts[(string) $row->d] = (int) $row->c;
        }

        $cursor = CarbonImmutable::parse($startDate);
        $end = CarbonImmutable::today();
        $labels = [];
        $values = [];
        while ($cursor->lessThanOrEqualTo($end)) {
            $key = $cursor->toDateString();
            $labels[] = $key;
            $values[] = $counts[$key] ?? 0;
            $cursor = $cursor->addDay();
        }

        $bucketSize = $days <= 60 ? 1 : ($days <= 180 ? 7 : 30);
        if ($bucketSize === 1) {
            return [
                'labels' => array_map(
                    static fn (string $date): string => CarbonImmutable::parse($date)->format('M j'),
                    $labels
                ),
                'values' => $values,
            ];
        }

        $bucketedLabels = [];
        $bucketedValues = [];
        $runningSum = 0;
        $runningCount = 0;
        $bucketStart = null;
        foreach ($labels as $index => $date) {
            $bucketStart ??= $date;
            $runningSum += $values[$index];
            $runningCount++;
            if ($runningCount >= $bucketSize) {
                $bucketedLabels[] = CarbonImmutable::parse($bucketStart)->format('M j');
                $bucketedValues[] = $runningSum;
                $runningSum = 0;
                $runningCount = 0;
                $bucketStart = null;
            }
        }
        if ($runningCount > 0) {
            $bucketedLabels[] = CarbonImmutable::parse((string) $bucketStart)->format('M j');
            $bucketedValues[] = $runningSum;
        }

        return ['labels' => $bucketedLabels, 'values' => $bucketedValues];
    }

    private function roleDistribution(): array
    {
        $counts = DB::table('users')
            ->select('role_id')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('role_id')
            ->pluck('total', 'role_id');
        $values = [];
        foreach ([1, 2, 3] as $roleId) {
            $values[] = (int) ($counts[$roleId] ?? 0);
        }

        return ['labels' => ['Administrators', 'Employers', 'Job Seekers'], 'values' => $values];
    }

    private function applicationStatusDistribution(): array
    {
        $ordered = [
            'Applied' => 0,
            'Under Review' => 0,
            'Shortlisted' => 0,
            'Interview Scheduled' => 0,
            'Selected' => 0,
            'Rejected' => 0,
            'Withdrawn' => 0,
        ];
        $counts = DB::table('applications')
            ->select('application_status')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('application_status')
            ->pluck('total', 'application_status');
        foreach ($ordered as $status => $total) {
            $ordered[$status] = (int) ($counts[$status] ?? 0);
        }

        return ['labels' => array_keys($ordered), 'values' => array_values($ordered)];
    }

    private function jobsByCategory(): array
    {
        $rows = DB::table('categories as c')
            ->leftJoin('jobs as j', 'j.category_id', '=', 'c.category_id')
            ->groupBy('c.category_id', 'c.category_name')
            ->select('c.category_name')
            ->selectRaw('COUNT(j.job_id) AS total')
            ->orderByDesc('total')
            ->orderBy('c.category_name')
            ->limit(8)
            ->get();

        return [
            'labels' => $rows->pluck('category_name')->map(fn ($name) => (string) $name)->all(),
            'values' => $rows->pluck('total')->map(fn ($total) => (int) $total)->all(),
        ];
    }

    private function topCompanies(): Collection
    {
        return DB::table('companies as c')
            ->leftJoin('jobs as j', 'j.company_id', '=', 'c.company_id')
            ->leftJoin('applications as a', 'a.job_id', '=', 'j.job_id')
            ->where('c.status', 'Approved')
            ->groupBy('c.company_id', 'c.company_name')
            ->select('c.company_id', 'c.company_name')
            ->selectRaw('COUNT(DISTINCT j.job_id) AS job_count')
            ->selectRaw('COUNT(a.application_id) AS application_count')
            ->orderByDesc('application_count')
            ->orderByDesc('job_count')
            ->limit(10)
            ->get();
    }

    private function topSkills(): array
    {
        $rows = DB::table('skills as s')
            ->join('job_skills as js', 'js.skill_id', '=', 's.skill_id')
            ->join('jobs as j', 'j.job_id', '=', 'js.job_id')
            ->where('j.job_status', 'Open')
            ->groupBy('s.skill_id', 's.skill_name')
            ->select('s.skill_name')
            ->selectRaw('COUNT(js.job_id) AS usage_count')
            ->orderByDesc('usage_count')
            ->orderBy('s.skill_name')
            ->limit(10)
            ->get();

        return [
            'labels' => $rows->pluck('skill_name')->map(fn ($name) => (string) $name)->all(),
            'values' => $rows->pluck('usage_count')->map(fn ($count) => (int) $count)->all(),
        ];
    }

    private function interviewSummary(): array
    {
        $row = DB::table('interviews')
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("SUM(CASE WHEN interview_status = 'Scheduled' THEN 1 ELSE 0 END) AS scheduled")
            ->selectRaw("SUM(CASE WHEN interview_status = 'Completed' THEN 1 ELSE 0 END) AS completed")
            ->selectRaw("SUM(CASE WHEN interview_status = 'Cancelled' THEN 1 ELSE 0 END) AS cancelled")
            ->selectRaw("SUM(CASE WHEN interview_status = 'Rescheduled' THEN 1 ELSE 0 END) AS rescheduled")
            ->first();
        $total = (int) $row->total;
        $completed = (int) $row->completed;

        return [
            'total' => $total,
            'scheduled' => (int) $row->scheduled,
            'completed' => $completed,
            'cancelled' => (int) $row->cancelled,
            'rescheduled' => (int) $row->rescheduled,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0.0,
        ];
    }
}
