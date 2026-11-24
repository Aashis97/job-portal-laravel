<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminNotificationController extends Controller
{
    private const TYPES = ['System', 'Application', 'Interview', 'Job', 'Message', 'Reminder'];

    public function index(Request $request)
    {
        $tab = $request->query('tab', 'all');
        $tab = is_string($tab) && in_array($tab, ['all', 'unread', 'read'], true) ? $tab : 'all';
        $type = $request->query('type', '');
        $type = is_string($type) && in_array($type, self::TYPES, true) ? $type : '';
        $search = $request->query('q', '');
        $search = is_string($search) ? Str::limit(trim($search), 100, '') : '';
        $userId = (int) $request->user()->getAuthIdentifier();

        $query = DB::table('notifications')
            ->where('user_id', $userId)
            ->when($tab === 'unread', fn ($builder) => $builder->where('is_read', 0))
            ->when($tab === 'read', fn ($builder) => $builder->where('is_read', 1))
            ->when($type !== '', fn ($builder) => $builder->where('notification_type', $type))
            ->when($search !== '', function ($builder) use ($search) {
                $like = '%' . $search . '%';
                $builder->where(function ($nested) use ($like) {
                    $nested->where('title', 'like', $like)->orWhere('message', 'like', $like);
                });
            });

        $notifications = $query
            ->orderBy('is_read')
            ->orderByDesc('created_at')
            ->orderByDesc('notification_id')
            ->paginate(20)
            ->appends(array_filter([
                'tab' => $tab !== 'all' ? $tab : null,
                'type' => $type !== '' ? $type : null,
                'q' => $search !== '' ? $search : null,
            ]));

        $statsRow = DB::table('notifications')->where('user_id', $userId)
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) AS unread')
            ->selectRaw('SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) AS read_count')
            ->selectRaw('SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) AS this_week', [now()->subDays(7)])
            ->first();
        $stats = [
            'total' => (int) ($statsRow->total ?? 0),
            'unread' => (int) ($statsRow->unread ?? 0),
            'read' => (int) ($statsRow->read_count ?? 0),
            'this_week' => (int) ($statsRow->this_week ?? 0),
        ];

        return view('admin.notifications', compact('notifications', 'stats', 'tab', 'type', 'search'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'action' => ['required', 'in:mark_read,mark_unread,delete,mark_all_read'],
            'notification_id' => ['required_unless:action,mark_all_read', 'nullable', 'integer', 'min:1'],
            'tab' => ['nullable', 'in:all,unread,read'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $tab = $validated['tab'] ?? 'all';
        $page = (int) ($validated['page'] ?? 1);
        $redirect = redirect()->route('admin.notifications', array_filter([
            'tab' => $tab !== 'all' ? $tab : null,
            'page' => $page > 1 ? $page : null,
        ]));
        $userId = (int) $request->user()->getAuthIdentifier();
        $action = $validated['action'];

        if ($action === 'mark_all_read') {
            $count = DB::table('notifications')
                ->where('user_id', $userId)
                ->where('is_read', 0)
                ->update(['is_read' => 1]);
            if ($count > 0) {
                $this->logActivity($request, 'Marked all notifications as read (' . $count . ')');
                return $redirect->with('success', $count . ' notification' . ($count === 1 ? '' : 's') . ' marked as read.');
            }
            return $redirect->with('info', 'No unread notifications to mark.');
        }

        $notificationId = (int) $validated['notification_id'];
        $notification = DB::table('notifications')
            ->where('notification_id', $notificationId)
            ->where('user_id', $userId)
            ->first(['notification_id', 'is_read']);
        if (!$notification) {
            return $redirect->with('danger', 'Notification not found.');
        }

        if ($action === 'delete') {
            DB::table('notifications')->where('notification_id', $notificationId)->where('user_id', $userId)->delete();
            return $redirect->with('success', 'Notification deleted.');
        }

        DB::table('notifications')->where('notification_id', $notificationId)->where('user_id', $userId)->update([
            'is_read' => $action === 'mark_read' ? 1 : 0,
        ]);

        return $redirect->with('success', $action === 'mark_read' ? 'Notification marked as read.' : 'Notification marked as unread.');
    }

    public function broadcast()
    {
        $counts = DB::table('users')->where('account_status', 'Active')
            ->whereIn('role_id', [1, 2, 3])
            ->selectRaw('COUNT(*) AS all_users')
            ->selectRaw('SUM(CASE WHEN role_id = 2 THEN 1 ELSE 0 END) AS employers')
            ->selectRaw('SUM(CASE WHEN role_id = 3 THEN 1 ELSE 0 END) AS jobseekers')
            ->selectRaw('SUM(CASE WHEN role_id = 1 THEN 1 ELSE 0 END) AS admins')
            ->first();
        $counts = [
            'all_users' => (int) ($counts->all_users ?? 0),
            'employers' => (int) ($counts->employers ?? 0),
            'jobseekers' => (int) ($counts->jobseekers ?? 0),
            'admins' => (int) ($counts->admins ?? 0),
            'specific' => 1,
        ];

        return view('admin.broadcast', compact('counts'));
    }

    public function sendBroadcast(Request $request)
    {
        $validated = $request->validate([
            'audience' => ['required', 'in:all_users,employers,jobseekers,admins,specific'],
            'specific_email' => ['required_if:audience,specific', 'nullable', 'email', 'max:150'],
            'notif_type' => ['required', 'in:' . implode(',', self::TYPES)],
            'title' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:1000'],
        ]);
        $audience = $validated['audience'];
        $title = trim($validated['title']);
        $message = trim($validated['message']);
        if ($title === '' || $message === '') {
            return redirect()->route('admin.broadcast')->with('danger', 'Title and message are required.');
        }

        try {
            $count = DB::transaction(function () use ($request, $validated, $audience, $title, $message) {
                if ($audience === 'specific') {
                    $recipientId = DB::table('users')
                        ->where('email', mb_strtolower(trim($validated['specific_email'])))
                        ->value('user_id');
                    if (!$recipientId) {
                        return -1;
                    }
                    DB::table('notifications')->insert([
                        'user_id' => $recipientId,
                        'title' => $title,
                        'message' => $message,
                        'notification_type' => $validated['notif_type'],
                        'is_read' => 0,
                    ]);
                    return 1;
                }

                $recipients = DB::table('users')->where('account_status', 'Active');
                if ($audience !== 'all_users') {
                    $roleId = match ($audience) {
                        'admins' => 1,
                        'employers' => 2,
                        'jobseekers' => 3,
                    };
                    $recipients->where('role_id', $roleId);
                } else {
                    $recipients->whereIn('role_id', [1, 2, 3]);
                }
                $count = (clone $recipients)->count();
                if ($count > 0) {
                    DB::table('notifications')->insertUsing(
                        ['user_id', 'title', 'message', 'notification_type', 'is_read'],
                        $recipients->select('user_id')
                            ->selectRaw('? AS title, ? AS message, ? AS notification_type, 0 AS is_read', [
                                $title,
                                $message,
                                $validated['notif_type'],
                            ])
                    );
                }
                return $count;
            });
        } catch (\Throwable $exception) {
            Log::error('Admin broadcast failed.', [
                'audience' => $audience,
                'user_id' => $request->user()->getAuthIdentifier(),
                'exception' => $exception->getMessage(),
            ]);

            return redirect()->route('admin.broadcast')->with('danger', 'Unable to send broadcast. Please try again.');
        }

        if ($count < 0) {
            return redirect()->route('admin.broadcast')->with('danger', 'No user found with that email address.');
        }

        $this->logActivity($request, 'Broadcast sent to ' . $count . ' recipient(s): ' . $title);

        return redirect()->route('admin.notifications')
            ->with('success', 'Broadcast delivered to ' . number_format($count) . ' recipient' . ($count === 1 ? '' : 's') . '.');
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
            Log::warning('Admin notification activity could not be recorded.', [
                'user_id' => $request->user()->getAuthIdentifier(),
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
