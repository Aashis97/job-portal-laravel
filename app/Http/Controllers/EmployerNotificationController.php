<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class EmployerNotificationController extends Controller
{
    public function index(Request $request)
    {
        $userId = (int) $request->user()->user_id;
        $total = DB::table('notifications')->where('user_id', $userId)->count();
        $unreadCount = DB::table('notifications')
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->count();
        $totalPages = max(1, (int) ceil($total / 10));
        $inputPage = filter_var($request->query('page'), FILTER_VALIDATE_INT);
        $page = $inputPage !== false && $inputPage !== null && $inputPage > 0
            ? min($inputPage, $totalPages)
            : 1;
        $notifications = DB::table('notifications')
            ->where('user_id', $userId)
            ->orderBy('is_read')
            ->orderByDesc('created_at')
            ->orderByDesc('notification_id')
            ->offset(($page - 1) * 10)
            ->limit(10)
            ->get([
                'notification_id',
                'title',
                'message',
                'notification_type',
                'is_read',
                'created_at',
            ]);

        return view('employer.notifications', compact(
            'notifications',
            'total',
            'unreadCount',
            'page',
            'totalPages'
        ));
    }

    public function get(Request $request)
    {
        $limit = $request->query('limit', 5);
        $limit = filter_var($limit, FILTER_VALIDATE_INT);
        $limit = max(1, min($limit === false ? 0 : (int) $limit, 20));
        $userId = (int) $request->user()->user_id;

        try {
            $notifications = DB::table('notifications')
                ->where('user_id', $userId)
                ->orderByDesc('created_at')
                ->orderByDesc('notification_id')
                ->limit($limit)
                ->get([
                    'notification_id',
                    'title',
                    'message',
                    'notification_type',
                    'is_read',
                    'created_at',
                ]);
            $unreadCount = $this->unreadCount($userId);
        } catch (Throwable $exception) {
            Log::error('Employer notifications could not be loaded.', [
                'user_id' => $userId,
                'exception' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load notifications.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'unread_count' => $unreadCount,
            'total' => $notifications->count(),
            'notifications' => $notifications->map(static fn ($notification) => [
                'notification_id' => (int) $notification->notification_id,
                'title' => (string) $notification->title,
                'message' => (string) $notification->message,
                'notification_type' => (string) $notification->notification_type,
                'is_read' => (int) $notification->is_read,
                'created_at' => (string) $notification->created_at,
            ])->all(),
        ]);
    }

    public function markRead(Request $request)
    {
        $notificationId = filter_var($request->input('notification_id'), FILTER_VALIDATE_INT);
        if ($notificationId === false || $notificationId === null || $notificationId < 1) {
            return response()->json(['success' => false, 'message' => 'Invalid notification ID.'], 422);
        }

        $userId = (int) $request->user()->user_id;
        $notification = DB::table('notifications')
            ->where('notification_id', $notificationId)
            ->where('user_id', $userId)
            ->first(['is_read']);
        if (!$notification) {
            return response()->json(['success' => false, 'message' => 'Notification not found.'], 404);
        }

        if (!(bool) $notification->is_read) {
            DB::table('notifications')
                ->where('notification_id', $notificationId)
                ->where('user_id', $userId)
                ->where('is_read', 0)
                ->update(['is_read' => 1]);
        }

        return response()->json([
            'success' => true,
            'message' => (bool) $notification->is_read
                ? 'Notification is already marked as read.'
                : 'Notification marked as read.',
            'notification_id' => (int) $notificationId,
            'unread_count' => $this->unreadCount($userId),
        ]);
    }

    public function markAllRead(Request $request)
    {
        $userId = (int) $request->user()->user_id;
        try {
            DB::table('notifications')
                ->where('user_id', $userId)
                ->where('is_read', 0)
                ->update(['is_read' => 1]);
            $unreadCount = $this->unreadCount($userId);
        } catch (Throwable $exception) {
            Log::error('Employer notifications could not be marked as read.', [
                'user_id' => $userId,
                'exception' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to mark notifications as read.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read.',
            'unread_count' => $unreadCount,
        ]);
    }

    public function delete(Request $request)
    {
        $notificationId = filter_var($request->input('notification_id'), FILTER_VALIDATE_INT);
        if ($notificationId === false || $notificationId === null || $notificationId < 1) {
            return response()->json(['success' => false, 'message' => 'Invalid notification ID.'], 422);
        }

        $userId = (int) $request->user()->user_id;
        $deleted = DB::table('notifications')
            ->where('notification_id', $notificationId)
            ->where('user_id', $userId)
            ->delete();
        if ($deleted !== 1) {
            return response()->json(['success' => false, 'message' => 'Notification not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully.',
            'notification_id' => (int) $notificationId,
            'unread_count' => $this->unreadCount($userId),
        ]);
    }

    private function unreadCount(int $userId): int
    {
        return DB::table('notifications')
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->count();
    }
}
