<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    private const TYPES = ['System', 'Application', 'Interview', 'Job', 'Message', 'Reminder'];

    public function index(Request $request)
    {
        $userId = (int) $request->user()->user_id;
        $tab = in_array($request->query('tab'), ['all', 'unread', 'read'], true)
            ? $request->query('tab')
            : 'all';
        $type = in_array($request->query('type'), self::TYPES, true)
            ? $request->query('type')
            : '';

        $query = DB::table('notifications')->where('user_id', $userId);
        if ($tab === 'unread') {
            $query->where('is_read', 0);
        } elseif ($tab === 'read') {
            $query->where('is_read', 1);
        }
        if ($type !== '') {
            $query->where('notification_type', $type);
        }

        $total = (clone $query)->count();
        $totalPages = max(1, (int) ceil($total / 20));
        $pageInput = filter_var($request->query('page'), FILTER_VALIDATE_INT);
        $page = $pageInput !== false && $pageInput !== null && $pageInput > 0
            ? min($pageInput, $totalPages)
            : 1;
        $notifications = $query
            ->orderBy('is_read')
            ->orderByDesc('created_at')
            ->orderByDesc('notification_id')
            ->paginate(20, ['*'], 'page', $page);

        $stats = DB::table('notifications')
            ->where('user_id', $userId)
            ->selectRaw(
                'COUNT(*) AS total, COALESCE(SUM(is_read = 0), 0) AS unread, COALESCE(SUM(is_read = 1), 0) AS read_count'
            )
            ->first();

        return view('jobseeker.notifications', [
            'notifications' => $notifications,
            'tab' => $tab,
            'typeFilter' => $type,
            'allowedTypes' => self::TYPES,
            'totalAll' => (int) $stats->total,
            'totalUnread' => (int) $stats->unread,
            'totalRead' => (int) $stats->read_count,
            'currentPage' => $page,
            'totalPages' => $totalPages,
        ]);
    }

    public function markRead(Request $request)
    {
        $query = $this->returnContext($request);
        $notificationId = filter_var($request->input('notification_id'), FILTER_VALIDATE_INT);

        if ($notificationId === false || $notificationId === null || $notificationId < 1) {
            return redirect()->route('jobseeker.notifications', $query)
                ->with('danger', 'Invalid notification selected.');
        }

        $notification = DB::table('notifications')
            ->where('notification_id', $notificationId)
            ->where('user_id', $request->user()->user_id)
            ->first(['is_read']);

        if (!$notification) {
            return redirect()->route('jobseeker.notifications', $query)
                ->with('danger', 'Notification not found.');
        }

        if ((int) $notification->is_read === 1) {
            return redirect()->route('jobseeker.notifications', $query)
                ->with('info', 'This notification is already marked as read.');
        }

        $updated = DB::table('notifications')
            ->where('notification_id', $notificationId)
            ->where('user_id', $request->user()->user_id)
            ->where('is_read', 0)
            ->update(['is_read' => 1]);

        return redirect()->route('jobseeker.notifications', $query)->with(
            $updated > 0 ? 'success' : 'info',
            $updated > 0
                ? 'Notification marked as read.'
                : 'Nothing to update — the notification may have been changed.'
        );
    }

    public function markAllRead(Request $request)
    {
        $count = DB::table('notifications')
            ->where('user_id', $request->user()->user_id)
            ->where('is_read', 0)
            ->update(['is_read' => 1]);

        return redirect()->route('jobseeker.notifications')->with(
            $count > 0 ? 'success' : 'info',
            $count > 0
                ? $count . ' notification' . ($count === 1 ? '' : 's') . ' marked as read.'
                : 'No unread notifications.'
        );
    }

    private function returnContext(Request $request): array
    {
        $tab = in_array($request->input('tab'), ['unread', 'read'], true)
            ? $request->input('tab')
            : null;
        $type = in_array($request->input('type'), self::TYPES, true)
            ? $request->input('type')
            : null;
        $page = filter_var($request->input('page'), FILTER_VALIDATE_INT);
        $page = $page !== false && $page !== null && $page > 1 ? $page : null;

        return array_filter([
            'tab' => $tab,
            'type' => $type,
            'page' => $page,
        ], static fn ($value) => $value !== null);
    }
}
