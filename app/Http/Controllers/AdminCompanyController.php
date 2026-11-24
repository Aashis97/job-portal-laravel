<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminCompanyController extends Controller
{
    private const STATUSES = ['Pending', 'Approved', 'Rejected', 'Suspended'];

    public function index(Request $request)
    {
        $status = in_array($request->query('status'), self::STATUSES, true)
            ? $request->query('status')
            : '';
        $search = mb_substr(trim((string) $request->query('q', '')), 0, 100);

        $query = DB::table('companies as c')
            ->leftJoin('employers as e', function ($join) {
                $join->on('e.company_id', '=', 'c.company_id')
                    ->where('e.is_primary_contact', '=', 1);
            })
            ->leftJoin('users as u', 'u.user_id', '=', 'e.user_id')
            ->when($status !== '', fn ($query) => $query->where('c.status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('c.company_name', 'like', '%' . $search . '%')
                        ->orWhere('c.company_email', 'like', '%' . $search . '%')
                        ->orWhere('c.industry', 'like', '%' . $search . '%')
                        ->orWhere('c.city', 'like', '%' . $search . '%')
                        ->orWhere('u.full_name', 'like', '%' . $search . '%')
                        ->orWhere('u.email', 'like', '%' . $search . '%');
                });
            });

        $companies = $query
            ->orderByRaw($status === '' ? "(c.status = 'Pending') DESC" : 'c.company_id DESC')
            ->orderByDesc('c.company_id')
            ->select([
                'c.company_id',
                'c.company_name',
                'c.company_email',
                'c.logo',
                'c.industry',
                'c.company_size',
                'c.city',
                'c.country',
                'c.status',
                'c.created_at',
                'u.user_id as owner_user_id',
                'u.full_name as owner_name',
                'u.email as owner_email',
            ])
            ->selectSub(
                DB::table('jobs')->selectRaw('COUNT(*)')->whereColumn('jobs.company_id', 'c.company_id'),
                'total_jobs'
            )
            ->selectSub(
                DB::table('jobs')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('jobs.company_id', 'c.company_id')
                    ->where('job_status', 'Open'),
                'open_jobs'
            )
            ->paginate(20)
            ->withQueryString();

        $tabCounts = DB::table('companies')
            ->selectRaw('COUNT(*) AS all_count')
            ->selectRaw("SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending_count")
            ->selectRaw("SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) AS approved_count")
            ->selectRaw("SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) AS rejected_count")
            ->selectRaw("SUM(CASE WHEN status = 'Suspended' THEN 1 ELSE 0 END) AS suspended_count")
            ->first();

        return view('admin.companies', compact('companies', 'tabCounts', 'status', 'search'));
    }

    public function show(Request $request)
    {
        $validated = $request->validate(['id' => ['required', 'integer', 'min:1']]);
        $company = DB::table('companies as c')
            ->leftJoin('employers as e', function ($join) {
                $join->on('e.company_id', '=', 'c.company_id')
                    ->where('e.is_primary_contact', '=', 1);
            })
            ->leftJoin('users as u', 'u.user_id', '=', 'e.user_id')
            ->where('c.company_id', $validated['id'])
            ->select([
                'c.*',
                'u.user_id as owner_user_id',
                'u.full_name as owner_name',
                'u.email as owner_email',
            ])
            ->selectSub(
                DB::table('jobs')->selectRaw('COUNT(*)')->whereColumn('jobs.company_id', 'c.company_id'),
                'total_jobs'
            )
            ->selectSub(
                DB::table('jobs')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('jobs.company_id', 'c.company_id')
                    ->where('job_status', 'Open'),
                'open_jobs'
            )
            ->first();
        abort_unless($company, 404);

        return view('admin.company-details', compact('company'));
    }

    public function updateStatus(Request $request)
    {
        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'min:1'],
            'action' => ['required', 'in:approve,reject,suspend'],
            'reason' => ['nullable', 'string', 'max:1000', 'required_if:action,reject'],
        ]);
        $companyId = (int) $validated['company_id'];
        if (!DB::table('companies')->where('company_id', $companyId)->exists()) {
            return redirect()->route('admin.companies')->with('danger', 'Company not found.');
        }

        $action = $validated['action'];
        $status = ['approve' => 'Approved', 'reject' => 'Rejected', 'suspend' => 'Suspended'][$action];
        $values = ['status' => $status];
        if (Schema::hasColumn('companies', 'verified_at')) {
            $values['verified_at'] = now();
        }
        if (Schema::hasColumn('companies', 'verified_by')) {
            $values['verified_by'] = $request->user()->user_id;
        }
        if (Schema::hasColumn('companies', 'rejection_reason')) {
            $values['rejection_reason'] = $action === 'reject' ? trim($validated['reason']) : null;
        }

        $result = DB::transaction(function () use ($companyId, $action, $values) {
            $currentStatus = DB::table('companies')
                ->where('company_id', $companyId)
                ->lockForUpdate()
                ->value('status');
            if ($currentStatus === null) {
                return 'missing';
            }
            if (($action === 'approve' && $currentStatus === 'Approved')
                || ($action === 'reject' && $currentStatus === 'Rejected')) {
                return 'already';
            }
            if ($action === 'suspend' && $currentStatus !== 'Approved') {
                return 'invalid';
            }

            DB::table('companies')->where('company_id', $companyId)->update($values);

            return 'updated';
        });
        if ($result === 'missing') {
            return redirect()->route('admin.companies')->with('danger', 'Company not found.');
        }
        if ($result === 'already') {
            return redirect()->route('admin.company-details', ['id' => $companyId])
                ->with('info', 'Company is already ' . strtolower($status) . '.');
        }
        if ($result === 'invalid') {
            return redirect()->route('admin.company-details', ['id' => $companyId])
                ->with('danger', 'Only approved companies can be suspended.');
        }

        $message = match ($action) {
            'approve' => 'Company approved successfully.',
            'reject' => 'Company rejected successfully.',
            'suspend' => 'Company suspended.',
        };

        return redirect()->route('admin.company-details', ['id' => $companyId])->with('success', $message);
    }
}
