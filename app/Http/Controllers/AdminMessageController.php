<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminMessageController extends Controller
{
    public function index(Request $request)
    {
        $userId = (int) $request->user()->user_id;
        $tab = in_array($request->query('tab'), ['inbox', 'sent'], true)
            ? $request->query('tab')
            : 'inbox';
        $search = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        $viewId = filter_var($request->query('view'), FILTER_VALIDATE_INT);
        $viewId = $viewId !== false && $viewId !== null && $viewId > 0 ? $viewId : 0;
        $message = null;

        if ($viewId > 0) {
            $message = DB::table('messages as m')
                ->join('users as sender', 'sender.user_id', '=', 'm.sender_id')
                ->join('users as receiver', 'receiver.user_id', '=', 'm.receiver_id')
                ->where('m.message_id', $viewId)
                ->where(function ($query) use ($userId) {
                    $query->where('m.sender_id', $userId)->orWhere('m.receiver_id', $userId);
                })
                ->first([
                    'm.message_id',
                    'm.sender_id',
                    'm.receiver_id',
                    'm.subject',
                    'm.message',
                    'm.attachment',
                    'm.is_read',
                    'm.sent_at',
                    'sender.full_name as sender_name',
                    'sender.email as sender_email',
                    'receiver.full_name as receiver_name',
                    'receiver.email as receiver_email',
                ]);
            if ($message && (int) $message->receiver_id === $userId && !(bool) $message->is_read) {
                DB::table('messages')
                    ->where('message_id', $viewId)
                    ->where('receiver_id', $userId)
                    ->update(['is_read' => 1]);
                $message->is_read = 1;
            }
        }

        $inboxTotal = DB::table('messages')->where('receiver_id', $userId)->count();
        $sentTotal = DB::table('messages')->where('sender_id', $userId)->count();
        $unreadTotal = DB::table('messages')
            ->where('receiver_id', $userId)
            ->where('is_read', 0)
            ->count();

        $ownerColumn = $tab === 'sent' ? 'm.sender_id' : 'm.receiver_id';
        $peerColumn = $tab === 'sent' ? 'm.receiver_id' : 'm.sender_id';
        $query = DB::table('messages as m')
            ->join('users as peer', $peerColumn, '=', 'peer.user_id')
            ->where($ownerColumn, $userId)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('m.subject', 'like', '%' . $search . '%')
                        ->orWhere('m.message', 'like', '%' . $search . '%')
                        ->orWhere('peer.full_name', 'like', '%' . $search . '%');
                });
            });
        $total = (clone $query)->count();
        $totalPages = max(1, (int) ceil($total / 20));
        $pageInput = filter_var($request->query('page'), FILTER_VALIDATE_INT);
        $page = $pageInput !== false && $pageInput !== null && $pageInput > 0
            ? min($pageInput, $totalPages)
            : 1;
        $messages = $query
            ->orderByDesc('m.sent_at')
            ->orderByDesc('m.message_id')
            ->paginate(20, [
                'm.message_id',
                'm.sender_id',
                'm.receiver_id',
                'm.subject',
                'm.message',
                'm.is_read',
                'm.sent_at',
                'peer.full_name as peer_name',
                'peer.role_id as peer_role',
            ], 'page', $page);

        return view('admin.messages', compact(
            'tab',
            'search',
            'viewId',
            'message',
            'inboxTotal',
            'sentTotal',
            'unreadTotal',
            'messages',
            'page',
            'totalPages'
        ));
    }
}
