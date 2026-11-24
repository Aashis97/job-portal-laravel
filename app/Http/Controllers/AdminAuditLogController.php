<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminAuditLogController extends Controller
{
    public function index(Request $request)
    {
        $userId = filter_var($request->query('user_id'), FILTER_VALIDATE_INT);
        $userId = $userId !== false && $userId > 0 ? $userId : 0;
        $module = $request->query('module', '');
        $module = is_string($module) ? trim($module) : '';
        $search = $request->query('q', '');
        $search = is_string($search) ? Str::limit(trim($search), 100, '') : '';
        $dateFrom = $this->normalizeDate($request->query('date_from'));
        $dateTo = $this->normalizeDate($request->query('date_to'));

        $query = DB::table('activity_logs as a')
            ->leftJoin('users as u', 'u.user_id', '=', 'a.user_id')
            ->select([
                'a.log_id',
                'a.user_id',
                'a.activity',
                'a.module',
                'a.ip_address',
                'a.browser',
                'a.operating_system',
                'a.created_at',
                'u.full_name as user_name',
                'u.email as user_email',
                'u.role_id as user_role',
            ]);

        if ($userId > 0) {
            $query->where('a.user_id', $userId);
        }
        if ($module !== '') {
            $query->where('a.module', $module);
        }
        if ($dateFrom !== '') {
            $query->where('a.created_at', '>=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo !== '') {
            $query->where('a.created_at', '<=', $dateTo . ' 23:59:59');
        }
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $like = '%' . $search . '%';
                $builder->where('a.activity', 'like', $like)
                    ->orWhere('a.ip_address', 'like', $like)
                    ->orWhere('a.browser', 'like', $like)
                    ->orWhere('u.full_name', 'like', $like)
                    ->orWhere('u.email', 'like', $like);
            });
        }

        $filters = array_filter([
            'user_id' => $userId > 0 ? $userId : null,
            'module' => $module !== '' ? $module : null,
            'date_from' => $dateFrom !== '' ? $dateFrom : null,
            'date_to' => $dateTo !== '' ? $dateTo : null,
            'q' => $search !== '' ? $search : null,
        ], fn ($value) => $value !== null);
        $logs = $query->orderByDesc('a.log_id')->paginate(50)->appends($filters);

        $modules = DB::table('activity_logs')
            ->whereNotNull('module')
            ->where('module', '<>', '')
            ->distinct()
            ->orderBy('module')
            ->pluck('module')
            ->map(fn ($value) => (string) $value);

        $now = now();
        $statsRow = DB::table('activity_logs')
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) AS last_24h', [$now->copy()->subDay()])
            ->selectRaw('SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) AS last_7d', [$now->copy()->subDays(7)])
            ->selectRaw('COUNT(DISTINCT user_id) AS unique_users')
            ->first();
        $stats = [
            'total' => (int) ($statsRow->total ?? 0),
            'last_24h' => (int) ($statsRow->last_24h ?? 0),
            'last_7d' => (int) ($statsRow->last_7d ?? 0),
            'unique_users' => (int) ($statsRow->unique_users ?? 0),
        ];

        return view('admin.audit-log', compact(
            'logs',
            'modules',
            'stats',
            'userId',
            'module',
            'dateFrom',
            'dateTo',
            'search'
        ));
    }

    private function normalizeDate(mixed $value): string
    {
        if (!is_string($value) || trim($value) === '') {
            return '';
        }
        $timestamp = strtotime(trim($value));

        return $timestamp === false ? '' : date('Y-m-d', $timestamp);
    }
}
