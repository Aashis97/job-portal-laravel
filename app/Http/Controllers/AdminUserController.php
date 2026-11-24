<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminUserController extends Controller
{
    private const STATUSES = ['Active', 'Pending', 'Inactive', 'Blocked'];

    public function index(Request $request)
    {
        $role = filter_var($request->query('role'), FILTER_VALIDATE_INT);
        $role = in_array($role, [1, 2, 3], true) ? $role : 0;
        $status = in_array($request->query('status'), self::STATUSES, true)
            ? $request->query('status')
            : '';
        $verified = in_array($request->query('verified'), ['0', '1'], true)
            ? $request->query('verified')
            : '';
        $search = mb_substr(trim((string) $request->query('q', '')), 0, 100);

        $query = DB::table('users as u')
            ->leftJoin('employers as e', 'e.user_id', '=', 'u.user_id')
            ->leftJoin('companies as c', 'c.company_id', '=', 'e.company_id')
            ->leftJoin('job_seekers as js', 'js.user_id', '=', 'u.user_id')
            ->when($role > 0, fn ($query) => $query->where('u.role_id', $role))
            ->when($status !== '', fn ($query) => $query->where('u.account_status', $status))
            ->when($verified !== '', fn ($query) => $query->where('u.email_verified', (int) $verified))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('u.full_name', 'like', '%' . $search . '%')
                        ->orWhere('u.email', 'like', '%' . $search . '%')
                        ->orWhere('u.phone', 'like', '%' . $search . '%');
                });
            });

        $users = $query
            ->orderByDesc('u.user_id')
            ->paginate(20, [
                'u.user_id',
                'u.role_id',
                'u.full_name',
                'u.email',
                'u.phone',
                'u.account_status',
                'u.email_verified',
                'u.profile_image',
                'u.created_at',
                'u.last_login',
                'c.company_name',
                'c.status as company_status',
                'js.years_of_experience',
            ])
            ->withQueryString();

        $tabCounts = DB::table('users')
            ->selectRaw('COUNT(*) AS all_count')
            ->selectRaw('SUM(CASE WHEN role_id = 1 THEN 1 ELSE 0 END) AS admin_count')
            ->selectRaw('SUM(CASE WHEN role_id = 2 THEN 1 ELSE 0 END) AS employer_count')
            ->selectRaw('SUM(CASE WHEN role_id = 3 THEN 1 ELSE 0 END) AS jobseeker_count')
            ->first();

        $pageTitle = match ($role) {
            1 => 'Administrators',
            2 => 'Employers',
            3 => 'Job Seekers',
            default => 'All Users',
        };

        return view('admin.users', [
            'users' => $users,
            'roleFilter' => $role,
            'statusFilter' => $status,
            'verifiedFilter' => $verified,
            'search' => $search,
            'statuses' => self::STATUSES,
            'tabCounts' => $tabCounts,
            'pageTitle' => $pageTitle,
        ]);
    }

    public function show(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'min:1'],
        ]);
        $user = DB::table('users as u')
            ->leftJoin('employers as e', 'e.user_id', '=', 'u.user_id')
            ->leftJoin('companies as c', 'c.company_id', '=', 'e.company_id')
            ->leftJoin('job_seekers as js', 'js.user_id', '=', 'u.user_id')
            ->where('u.user_id', $validated['id'])
            ->first([
                'u.user_id',
                'u.role_id',
                'u.full_name',
                'u.email',
                'u.phone',
                'u.account_status',
                'u.email_verified',
                'u.profile_image',
                'u.created_at',
                'u.last_login',
                'c.company_name',
                'c.status as company_status',
                'js.years_of_experience',
            ]);
        abort_unless($user, 404);

        return view('admin.user-details', compact('user'));
    }
}
