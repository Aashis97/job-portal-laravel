<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AdminReportController extends Controller
{
    private const STATUSES = ['open', 'in_review', 'resolved', 'dismissed'];
    private const TARGETS = ['job', 'company', 'user', 'application', 'message'];
    private const REASONS = ['spam', 'inappropriate', 'fraud', 'fake', 'harassment', 'copyright', 'other'];

    public function index(Request $request)
    {
        $status = $request->query('status', '');
        $status = is_string($status) && in_array($status, self::STATUSES, true) ? $status : '';
        $targetType = $request->query('target_type', '');
        $targetType = is_string($targetType) && in_array($targetType, self::TARGETS, true) ? $targetType : '';
        $reason = $request->query('reason', '');
        $reason = is_string($reason) && in_array($reason, self::REASONS, true) ? $reason : '';
        $search = $request->query('q', '');
        $search = is_string($search) ? Str::limit(trim($search), 100, '') : '';

        $query = DB::table('reports as r')
            ->leftJoin('users as ru', 'ru.user_id', '=', 'r.reporter_user_id')
            ->leftJoin('users as hu', 'hu.user_id', '=', 'r.handled_by')
            ->select([
                'r.report_id',
                'r.reporter_user_id',
                'r.reporter_email',
                'r.target_type',
                'r.target_id',
                'r.reason',
                'r.details',
                'r.status',
                'r.action_taken',
                'r.created_at',
                'r.handled_at',
                'ru.full_name as reporter_name',
                'ru.role_id as reporter_role',
                'hu.full_name as handler_name',
            ]);

        if ($status !== '') {
            $query->where('r.status', $status);
        }
        if ($targetType !== '') {
            $query->where('r.target_type', $targetType);
        }
        if ($reason !== '') {
            $query->where('r.reason', $reason);
        }
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $like = '%' . $search . '%';
                $builder->where('r.details', 'like', $like)
                    ->orWhere('r.reporter_email', 'like', $like)
                    ->orWhere('ru.full_name', 'like', $like)
                    ->orWhere('ru.email', 'like', $like);
            });
        }

        $reports = $query
            ->orderByRaw("CASE r.status WHEN 'open' THEN 1 WHEN 'in_review' THEN 2 WHEN 'resolved' THEN 3 WHEN 'dismissed' THEN 4 ELSE 5 END")
            ->orderByDesc('r.report_id')
            ->paginate(25)
            ->appends(array_filter([
                'status' => $status ?: null,
                'target_type' => $targetType ?: null,
                'reason' => $reason ?: null,
                'q' => $search ?: null,
            ]));

        $reports->getCollection()->transform(function ($report) {
            $target = $this->resolveTarget((string) $report->target_type, (int) $report->target_id);
            $report->target_title = $target['title'] ?? null;
            $report->target_url = $target['url'] ?? '';
            $report->target_status = $target['status'] ?? '';
            return $report;
        });

        $statsRow = DB::table('reports')
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) AS open_count")
            ->selectRaw("SUM(CASE WHEN status = 'in_review' THEN 1 ELSE 0 END) AS review_count")
            ->selectRaw("SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) AS resolved_count")
            ->selectRaw("SUM(CASE WHEN status = 'dismissed' THEN 1 ELSE 0 END) AS dismissed_count")
            ->selectRaw('SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) AS last_7d', [now()->subDays(7)])
            ->first();
        $stats = [
            'total' => (int) ($statsRow->total ?? 0),
            'open' => (int) ($statsRow->open_count ?? 0),
            'in_review' => (int) ($statsRow->review_count ?? 0),
            'resolved' => (int) ($statsRow->resolved_count ?? 0),
            'dismissed' => (int) ($statsRow->dismissed_count ?? 0),
            'last_7d' => (int) ($statsRow->last_7d ?? 0),
        ];

        return view('admin.reports', compact('reports', 'stats', 'status', 'targetType', 'reason', 'search'));
    }

    public function show(Request $request)
    {
        $reportId = filter_var($request->query('id'), FILTER_VALIDATE_INT);
        if (!$reportId || $reportId < 1) {
            return redirect()->route('admin.reports')->with('danger', 'Invalid report selected.');
        }

        $report = DB::table('reports as r')
            ->leftJoin('users as ru', 'ru.user_id', '=', 'r.reporter_user_id')
            ->leftJoin('users as hu', 'hu.user_id', '=', 'r.handled_by')
            ->where('r.report_id', $reportId)
            ->first([
                'r.*',
                'ru.full_name as reporter_name',
                'ru.email as reporter_account_email',
                'ru.role_id as reporter_role',
                'ru.account_status as reporter_status',
                'hu.full_name as handler_name',
                'hu.email as handler_email',
            ]);
        if (!$report) {
            return redirect()->route('admin.reports')->with('danger', 'Report not found.');
        }

        $target = $this->resolveTarget((string) $report->target_type, (int) $report->target_id);
        $followUpActions = match ($report->target_type) {
            'job' => ['close_job' => 'Close the job', 'delete_job' => 'Delete the job'],
            'company' => ['suspend_company' => 'Suspend the company', 'reject_company' => 'Reject the company'],
            'user' => ['block_user' => 'Block the user', 'suspend_user' => 'Suspend the user'],
            'application' => ['withdraw_application' => 'Force-withdraw the application', 'delete_application' => 'Delete the application'],
            default => [],
        };
        $canReview = in_array($report->status, ['open', 'in_review'], true);

        return view('admin.report-details', compact('report', 'target', 'followUpActions', 'canReview'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'report_id' => ['required', 'integer', 'min:1'],
            'action' => ['required', 'in:mark_in_review,dismiss,resolve_with_action,save_notes'],
        ]);
        $reportId = (int) $validated['report_id'];
        $action = $validated['action'];
        $redirect = redirect()->route('admin.report-details', ['id' => $reportId]);

        if ($action === 'save_notes') {
            $notes = $request->input('admin_notes', '');
            $notes = $notes ?? '';
            if (!is_string($notes)) {
                return $redirect->with('danger', 'Invalid internal notes.');
            }
            $notes = trim($notes);
            DB::table('reports')->where('report_id', $reportId)->update([
                'admin_notes' => $notes === '' ? null : Str::limit($notes, 2000, ''),
                'updated_at' => now(),
            ]);
            if (!DB::table('reports')->where('report_id', $reportId)->exists()) {
                return redirect()->route('admin.reports')->with('danger', 'Report not found.');
            }

            return $redirect->with('success', 'Internal notes saved.');
        }

        $followUp = 'none';
        $actionNote = '';
        if ($action === 'resolve_with_action') {
            $followUp = $request->input('follow_up', 'none');
            $actionNote = $request->input('action_note', '');
            $actionNote = $actionNote ?? '';
            if (!is_string($followUp) || !is_string($actionNote)) {
                return $redirect->with('danger', 'Invalid follow-up action for this target.');
            }
            $actionNote = Str::limit(trim($actionNote), 500, '');
        }

        try {
            $result = DB::transaction(function () use ($request, $reportId, $action, $followUp, $actionNote) {
                $report = DB::table('reports')->where('report_id', $reportId)->lockForUpdate()->first();
                if (!$report) {
                    return ['state' => 'missing'];
                }
                if (!in_array($report->status, ['open', 'in_review'], true)) {
                    return ['state' => 'closed'];
                }

                $adminId = $request->user()->getAuthIdentifier();
                if ($action === 'mark_in_review') {
                    DB::table('reports')->where('report_id', $reportId)->update([
                        'status' => 'in_review',
                        'handled_by' => $adminId,
                        'handled_at' => now(),
                        'updated_at' => now(),
                    ]);
                    return ['state' => 'review'];
                }
                if ($action === 'dismiss') {
                    DB::table('reports')->where('report_id', $reportId)->update([
                        'status' => 'dismissed',
                        'action_taken' => 'Dismissed — no violation found',
                        'handled_by' => $adminId,
                        'handled_at' => now(),
                        'updated_at' => now(),
                    ]);
                    return ['state' => 'dismissed'];
                }

                $allowed = $this->allowedFollowUps((string) $report->target_type);
                if (!in_array($followUp, $allowed, true)) {
                    return ['state' => 'invalid_follow_up'];
                }

                $targetId = (int) $report->target_id;
                $this->applyFollowUp($followUp, $targetId, $reportId, (int) $adminId);
                $actionLabel = $this->actionLabel($followUp, $targetId);
                if ($actionNote !== '') {
                    $actionLabel .= ' — ' . $actionNote;
                }
                $actionLabel = Str::limit($actionLabel, 100, '');
                DB::table('reports')->where('report_id', $reportId)->update([
                    'status' => 'resolved',
                    'action_taken' => $actionLabel,
                    'handled_by' => $adminId,
                    'handled_at' => now(),
                    'updated_at' => now(),
                ]);

                return ['state' => 'resolved', 'action_label' => $actionLabel];
            });
        } catch (QueryException $exception) {
            Log::error('Admin report action failed.', [
                'action' => $action,
                'report_id' => $reportId,
                'exception' => $exception->getMessage(),
            ]);
            return $redirect->with('danger', 'Unable to complete the action. Please try again.');
        }

        if ($result['state'] === 'missing') {
            return redirect()->route('admin.reports')->with('danger', 'Report not found.');
        }
        if ($result['state'] === 'closed') {
            return $redirect->with('warning', 'This report is already closed.');
        }
        if ($result['state'] === 'invalid_follow_up') {
            return $redirect->with('danger', 'Invalid follow-up action for this target.');
        }

        [$activity, $successMessage] = match ($result['state']) {
            'review' => ['Marked report #' . $reportId . ' as in review', 'Report marked as in review.'],
            'dismissed' => ['Dismissed report #' . $reportId, 'Report dismissed.'],
            'resolved' => [
                'Resolved report #' . $reportId . ' — ' . $result['action_label'],
                'Report resolved. Action: ' . $result['action_label'],
            ],
        };
        $this->logActivity($request, $activity);

        return $redirect->with('success', $successMessage);
    }

    private function resolveTarget(string $type, int $id): ?array
    {
        if ($id < 1) {
            return null;
        }

        return match ($type) {
            'job' => $this->resolveJob($id),
            'company' => $this->resolveCompany($id),
            'user' => $this->resolveUser($id),
            'application' => $this->resolveApplication($id),
            'message' => [
                'type' => 'message',
                'id' => $id,
                'title' => 'Message #' . $id,
                'status' => '',
                'url' => '',
                'meta' => 'Message viewer not available yet.',
            ],
            default => null,
        };
    }

    private function resolveJob(int $id): ?array
    {
        $row = DB::table('jobs as j')
            ->leftJoin('companies as c', 'c.company_id', '=', 'j.company_id')
            ->where('j.job_id', $id)
            ->first(['j.job_id as id', 'j.job_title as title', 'j.job_status as status', 'c.company_name']);
        if (!$row) {
            return null;
        }
        return [
            'type' => 'job',
            'id' => (int) $row->id,
            'title' => $row->title,
            'status' => $row->status,
            'url' => route('admin.job-details', ['id' => $row->id]),
            'meta' => $row->company_name ? 'Company: ' . $row->company_name : '',
        ];
    }

    private function resolveCompany(int $id): ?array
    {
        $row = DB::table('companies')->where('company_id', $id)->first(['company_id as id', 'company_name as title', 'status']);
        return $row ? [
            'type' => 'company',
            'id' => (int) $row->id,
            'title' => $row->title,
            'status' => $row->status,
            'url' => route('admin.company-details', ['id' => $row->id]),
            'meta' => '',
        ] : null;
    }

    private function resolveUser(int $id): ?array
    {
        $row = DB::table('users')->where('user_id', $id)->first(['user_id as id', 'full_name as title', 'role_id', 'account_status as status']);
        if (!$row) {
            return null;
        }
        $role = match ((int) $row->role_id) {
            1 => 'Administrator',
            2 => 'Employer',
            3 => 'Job Seeker',
            default => 'User',
        };
        return [
            'type' => 'user',
            'id' => (int) $row->id,
            'title' => $row->title,
            'status' => $row->status,
            'url' => route('admin.user-details', ['id' => $row->id]),
            'meta' => $role,
        ];
    }

    private function resolveApplication(int $id): ?array
    {
        $row = DB::table('applications as a')
            ->join('jobs as j', 'j.job_id', '=', 'a.job_id')
            ->join('users as u', 'u.user_id', '=', 'a.user_id')
            ->where('a.application_id', $id)
            ->first([
                'a.application_id as id',
                'a.application_status as status',
                'j.job_title',
                'u.full_name as applicant_name',
            ]);
        return $row ? [
            'type' => 'application',
            'id' => (int) $row->id,
            'title' => 'Application for ' . $row->job_title,
            'status' => $row->status,
            'url' => route('admin.application-details', ['id' => $row->id]),
            'meta' => 'Applicant: ' . $row->applicant_name,
        ] : null;
    }

    private function allowedFollowUps(string $targetType): array
    {
        return match ($targetType) {
            'job' => ['close_job', 'delete_job', 'none'],
            'company' => ['suspend_company', 'reject_company', 'none'],
            'user' => ['block_user', 'suspend_user', 'none'],
            'application' => ['withdraw_application', 'delete_application', 'none'],
            default => ['none'],
        };
    }

    private function applyFollowUp(string $action, int $targetId, int $reportId, int $adminId): void
    {
        switch ($action) {
            case 'close_job':
                $values = ['job_status' => 'Closed'];
                if (Schema::hasColumn('jobs', 'updated_at')) {
                    $values['updated_at'] = now();
                }
                DB::table('jobs')->where('job_id', $targetId)->update($values);
                break;
            case 'delete_job':
                if (Schema::hasTable('applications')) {
                    $applicationIds = DB::table('applications')->where('job_id', $targetId)->pluck('application_id');
                    if ($applicationIds->isNotEmpty() && Schema::hasTable('interviews')) {
                        DB::table('interviews')->whereIn('application_id', $applicationIds)->delete();
                    }
                    DB::table('applications')->where('job_id', $targetId)->delete();
                }
                foreach (['saved_jobs' => 'job_id', 'job_skills' => 'job_id'] as $table => $column) {
                    if (Schema::hasTable($table)) {
                        DB::table($table)->where($column, $targetId)->delete();
                    }
                }
                DB::table('jobs')->where('job_id', $targetId)->delete();
                break;
            case 'suspend_company':
                $values = ['status' => 'Suspended'];
                if (Schema::hasColumn('companies', 'updated_at')) {
                    $values['updated_at'] = now();
                }
                DB::table('companies')->where('company_id', $targetId)->update($values);
                break;
            case 'reject_company':
                $values = ['status' => 'Rejected'];
                if (Schema::hasColumn('companies', 'rejection_reason')) {
                    $values['rejection_reason'] = 'Rejected following report #' . $reportId;
                }
                if (Schema::hasColumn('companies', 'verified_at')) {
                    $values['verified_at'] = now();
                }
                if (Schema::hasColumn('companies', 'verified_by')) {
                    $values['verified_by'] = $adminId;
                }
                DB::table('companies')->where('company_id', $targetId)->update($values);
                break;
            case 'block_user':
            case 'suspend_user':
                $values = ['account_status' => $action === 'block_user' ? 'Blocked' : 'Inactive'];
                if (Schema::hasColumn('users', 'session_version')) {
                    DB::table('users')->where('user_id', $targetId)->increment('session_version');
                }
                if (Schema::hasColumn('users', 'updated_at')) {
                    $values['updated_at'] = now();
                }
                DB::table('users')->where('user_id', $targetId)->update($values);
                if (Schema::hasTable('remember_tokens')) {
                    DB::table('remember_tokens')->where('user_id', $targetId)->delete();
                }
                break;
            case 'withdraw_application':
                $values = ['application_status' => 'Withdrawn'];
                if (Schema::hasColumn('applications', 'updated_at')) {
                    $values['updated_at'] = now();
                }
                DB::table('applications')->where('application_id', $targetId)->update($values);
                break;
            case 'delete_application':
                if (Schema::hasTable('interviews')) {
                    DB::table('interviews')->where('application_id', $targetId)->delete();
                }
                $jobId = DB::table('applications')->where('application_id', $targetId)->value('job_id');
                DB::table('applications')->where('application_id', $targetId)->delete();
                if ($jobId && Schema::hasColumn('jobs', 'total_applications')) {
                    DB::table('jobs')->where('job_id', $jobId)->update([
                        'total_applications' => DB::raw('CASE WHEN total_applications > 0 THEN total_applications - 1 ELSE 0 END'),
                    ]);
                }
                break;
        }
    }

    private function actionLabel(string $action, int $targetId): string
    {
        return match ($action) {
            'close_job' => 'Closed job #' . $targetId,
            'delete_job' => 'Deleted job #' . $targetId,
            'suspend_company' => 'Suspended company #' . $targetId,
            'reject_company' => 'Rejected company #' . $targetId,
            'block_user' => 'Blocked user #' . $targetId,
            'suspend_user' => 'Suspended user #' . $targetId,
            'withdraw_application' => 'Withdrew application #' . $targetId,
            'delete_application' => 'Deleted application #' . $targetId,
            default => 'Resolved — no follow-up action',
        };
    }

    private function logActivity(Request $request, string $activity): void
    {
        try {
            DB::table('activity_logs')->insert([
                'user_id' => $request->user()->getAuthIdentifier(),
                'activity' => Str::limit($activity, 255, ''),
                'module' => 'Admin',
                'ip_address' => $request->ip(),
                'device_info' => Str::limit((string) $request->userAgent(), 255, ''),
                'created_at' => now(),
            ]);
        } catch (QueryException $exception) {
            Log::warning('Admin report activity could not be recorded.', [
                'user_id' => $request->user()->getAuthIdentifier(),
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
