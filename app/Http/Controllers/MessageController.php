<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $userId = (int) $request->user()->user_id;
        $isEmployer = (int) $request->user()->role_id === 2;
        $tab = in_array($request->query('tab'), ['inbox', 'sent'], true)
            ? $request->query('tab')
            : 'inbox';
        $search = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        $ownerColumn = $tab === 'sent' ? 'm.sender_id' : 'm.receiver_id';
        $peerColumn = $tab === 'sent' ? 'm.receiver_id' : 'm.sender_id';

        $query = DB::table('messages as m')
            ->join('users as u', $peerColumn, '=', 'u.user_id')
            ->where($ownerColumn, $userId)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('m.subject', 'like', '%' . $search . '%')
                        ->orWhere('m.message', 'like', '%' . $search . '%')
                        ->orWhere('u.full_name', 'like', '%' . $search . '%');
                });
            });

        $total = (clone $query)->count();
        $totalPages = max(1, (int) ceil($total / 15));
        $pageInput = filter_var($request->query('page'), FILTER_VALIDATE_INT);
        $page = $pageInput !== false && $pageInput !== null && $pageInput > 0
            ? min($pageInput, $totalPages)
            : 1;
        $messages = $query
            ->orderByDesc('m.message_id')
            ->paginate(15, [
                'm.message_id',
                'm.sender_id',
                'm.receiver_id',
                'm.subject',
                'm.message',
                'm.is_read',
                'm.sent_at',
                'u.user_id as peer_id',
                'u.full_name as peer_name',
                'u.profile_image as peer_avatar',
            ], 'page', $page);

        $counts = DB::table('messages')
            ->where('receiver_id', $userId)
            ->selectRaw('COUNT(*) AS inbox_total, COALESCE(SUM(is_read = 0), 0) AS unread_total')
            ->first();
        $sentTotal = DB::table('messages')->where('sender_id', $userId)->count();

        return view($isEmployer ? 'employer.messages' : 'jobseeker.messages', [
            'messages' => $messages,
            'tab' => $tab,
            'search' => $search,
            'page' => $page,
            'totalPages' => $totalPages,
            'inboxTotal' => (int) $counts->inbox_total,
            'unreadTotal' => (int) $counts->unread_total,
            'sentTotal' => $sentTotal,
        ]);
    }

    public function compose(Request $request)
    {
        $userId = (int) $request->user()->user_id;
        $isEmployer = (int) $request->user()->role_id === 2;
        if ($isEmployer) {
            $recipients = DB::table('users')
                ->where('user_id', '<>', $userId)
                ->where('account_status', 'Active')
                ->orderBy('full_name')
                ->get(['user_id', 'full_name', 'email', 'role_id']);
        } else {
            $appliedEmployers = DB::table('applications as a')
                ->join('jobs as j', 'j.job_id', '=', 'a.job_id')
                ->join('employers as e', 'e.employer_id', '=', 'j.employer_id')
                ->where('a.user_id', $userId)
                ->select('e.user_id');

            $recipients = DB::table('users')
                ->where('user_id', '<>', $userId)
                ->where('account_status', 'Active')
                ->where(function ($query) use ($userId, $appliedEmployers) {
                    $query->whereIn('user_id', $appliedEmployers)
                        ->orWhereIn('user_id', DB::table('messages')->select('receiver_id')->where('sender_id', $userId))
                        ->orWhereIn('user_id', DB::table('messages')->select('sender_id')->where('receiver_id', $userId));
                })
                ->orderBy('full_name')
                ->get(['user_id', 'full_name', 'email', 'role_id']);
        }

        $prefillId = filter_var($request->query('to'), FILTER_VALIDATE_INT);
        $prefillId = $prefillId !== false && $prefillId !== null && $prefillId > 0 ? $prefillId : 0;
        $prefillRecipient = null;
        if ($prefillId > 0) {
            $prefillRecipient = $recipients->firstWhere('user_id', $prefillId);
            if (!$prefillRecipient) {
                $prefillRecipient = DB::table('users')
                    ->where('user_id', $prefillId)
                    ->where('account_status', 'Active')
                    ->first(['user_id', 'full_name', 'email', 'role_id']);
                if ($prefillRecipient) {
                    $recipients->prepend($prefillRecipient);
                }
            }
        }

        return view($isEmployer ? 'employer.message-compose' : 'jobseeker.message-compose', compact('recipients', 'prefillId', 'prefillRecipient'));
    }

    public function send(Request $request)
    {
        $validated = $request->validate([
            'receiver_id' => ['required', 'integer', 'min:1'],
            'subject' => ['nullable', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:5000'],
            'attachment' => ['nullable', 'url', 'max:500'],
        ]);

        $senderId = (int) $request->user()->user_id;
        $receiverId = (int) $validated['receiver_id'];
        if ($receiverId === $senderId) {
            return back()->withInput()->with('danger', 'You cannot send a message to yourself.');
        }

        $recipient = DB::table('users')
            ->where('user_id', $receiverId)
            ->where('account_status', 'Active')
            ->first(['user_id', 'full_name']);
        if (!$recipient) {
            return back()->withInput()->with('danger', 'Recipient not found or is inactive.');
        }

        $subject = trim((string) ($validated['subject'] ?? ''));
        $message = trim($validated['message']);
        $messageId = DB::table('messages')->insertGetId([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'subject' => $subject !== '' ? $subject : '(no subject)',
            'message' => $message,
            'attachment' => $validated['attachment'] ?? null,
            'is_read' => 0,
            'sent_at' => now(),
        ]);

        $this->notifyRecipient($request, $senderId, $receiverId, $message);
        $isEmployer = (int) $request->user()->role_id === 2;
        $this->logActivity(
            $request,
            $senderId,
            'Sent message #' . $messageId . ' to user #' . $receiverId,
            $isEmployer ? 'Employer' : 'Jobseeker'
        );

        return redirect()->route($isEmployer ? 'employer.conversation' : 'jobseeker.conversation', ['user' => $receiverId])
            ->with('success', 'Message sent successfully.');
    }

    public function conversation(Request $request)
    {
        $peerId = filter_var($request->query('user'), FILTER_VALIDATE_INT);
        $userId = (int) $request->user()->user_id;
        $isEmployer = (int) $request->user()->role_id === 2;
        $messagesRoute = $isEmployer ? 'employer.messages' : 'jobseeker.messages';
        if ($peerId === false || $peerId === null || $peerId < 1) {
            return redirect()->route($messagesRoute)->with('danger', 'Invalid conversation.');
        }
        if ($peerId === $userId) {
            return redirect()->route($messagesRoute)->with('warning', 'You cannot view a conversation with yourself.');
        }

        $peer = DB::table('users')->where('user_id', $peerId)->first([
            'user_id',
            'full_name',
            'email',
            'profile_image',
            'role_id',
        ]);
        if (!$peer) {
            return redirect()->route($messagesRoute)->with('danger', 'That user no longer exists.');
        }

        $thread = DB::table('messages')
            ->where(function ($query) use ($userId, $peerId) {
                $query->where('sender_id', $userId)->where('receiver_id', $peerId);
            })
            ->orWhere(function ($query) use ($userId, $peerId) {
                $query->where('sender_id', $peerId)->where('receiver_id', $userId);
            })
            ->orderBy('sent_at')
            ->orderBy('message_id')
            ->get();

        DB::table('messages')
            ->where('sender_id', $peerId)
            ->where('receiver_id', $userId)
            ->where('is_read', 0)
            ->update(['is_read' => 1]);

        return view($isEmployer ? 'employer.conversation' : 'jobseeker.conversation', [
            'peer' => $peer,
            'thread' => $thread,
            'lastMessageId' => (int) ($thread->last()->message_id ?? 0),
            'currentUserId' => $userId,
        ]);
    }

    public function viewMessage(Request $request)
    {
        $messageId = filter_var($request->query('id'), FILTER_VALIDATE_INT);
        $userId = (int) $request->user()->user_id;
        if ($messageId === false || $messageId === null || $messageId < 1) {
            return redirect()->route('jobseeker.messages')->with('danger', 'Invalid message selected.');
        }

        $message = DB::table('messages')
            ->where('message_id', $messageId)
            ->where(function ($query) use ($userId) {
                $query->where('sender_id', $userId)->orWhere('receiver_id', $userId);
            })
            ->first(['sender_id', 'receiver_id', 'is_read']);

        if (!$message) {
            return redirect()->route('jobseeker.messages')->with('danger', 'Message not found.');
        }

        $peerId = (int) $message->sender_id === $userId
            ? (int) $message->receiver_id
            : (int) $message->sender_id;

        if ((int) $message->receiver_id === $userId && (int) $message->is_read === 0) {
            DB::table('messages')
                ->where('message_id', $messageId)
                ->where('receiver_id', $userId)
                ->update(['is_read' => 1]);
        }

        return redirect()->route('jobseeker.conversation', ['user' => $peerId]);
    }

    public function chat(Request $request)
    {
        $validated = $request->validate([
            'action' => ['required', 'in:send,poll'],
            'peer_id' => ['required', 'integer', 'min:1'],
            'after_id' => ['required_if:action,poll', 'nullable', 'integer', 'min:0'],
            'message' => ['required_if:action,send', 'nullable', 'string', 'max:5000'],
            'subject' => ['nullable', 'string', 'max:200'],
            'attachment' => ['nullable', 'url', 'max:500'],
        ]);

        $userId = (int) $request->user()->user_id;
        $peerId = (int) $validated['peer_id'];
        if ($peerId === $userId) {
            return response()->json(['success' => false, 'message' => 'Invalid conversation.'], 422);
        }

        $peer = DB::table('users')
            ->where('user_id', $peerId)
            ->where('account_status', 'Active')
            ->first(['user_id']);
        if (!$peer) {
            return response()->json(['success' => false, 'message' => 'Recipient is unavailable.'], 404);
        }

        if ($validated['action'] === 'send') {
            $message = trim((string) $validated['message']);
            if ($message === '') {
                return response()->json(['success' => false, 'message' => 'Message must contain 1–5,000 characters.'], 422);
            }

            $messageId = DB::table('messages')->insertGetId([
                'sender_id' => $userId,
                'receiver_id' => $peerId,
                'subject' => trim((string) ($validated['subject'] ?? '')) ?: null,
                'message' => $message,
                'attachment' => $validated['attachment'] ?? null,
                'is_read' => 0,
                'sent_at' => now(),
            ]);
            $sent = DB::table('messages')->where('message_id', $messageId)->first(['message_id', 'sender_id', 'subject', 'message', 'attachment', 'sent_at']);
            $this->notifyRecipient($request, $userId, $peerId, $message);

            return response()->json([
                'success' => true,
                'message' => [
                    'message_id' => (int) $sent->message_id,
                    'sender_id' => (int) $sent->sender_id,
                    'subject' => $sent->subject,
                    'message' => $sent->message,
                    'attachment' => $sent->attachment,
                    'sent_at' => $sent->sent_at,
                ],
            ]);
        }

        $afterId = (int) $validated['after_id'];
        $messages = DB::table('messages')
            ->where('message_id', '>', $afterId)
            ->where(function ($query) use ($userId, $peerId) {
                $query->where(function ($query) use ($userId, $peerId) {
                    $query->where('sender_id', $userId)->where('receiver_id', $peerId);
                })->orWhere(function ($query) use ($userId, $peerId) {
                    $query->where('sender_id', $peerId)->where('receiver_id', $userId);
                });
            })
            ->orderBy('message_id')
            ->limit(100)
            ->get(['message_id', 'sender_id', 'subject', 'message', 'attachment', 'sent_at']);

        $lastMessageId = $afterId;
        foreach ($messages as $message) {
            $message->message_id = (int) $message->message_id;
            $message->sender_id = (int) $message->sender_id;
            $lastMessageId = max($lastMessageId, $message->message_id);
        }

        if ($lastMessageId > $afterId) {
            DB::table('messages')
                ->where('sender_id', $peerId)
                ->where('receiver_id', $userId)
                ->where('message_id', '<=', $lastMessageId)
                ->where('is_read', 0)
                ->update(['is_read' => 1]);
        }

        return response()->json([
            'success' => true,
            'messages' => $messages,
            'last_message_id' => $lastMessageId,
            'unread_messages' => DB::table('messages')->where('receiver_id', $userId)->where('is_read', 0)->count(),
            'unread_notifications' => DB::table('notifications')->where('user_id', $userId)->where('is_read', 0)->count(),
        ]);
    }

    private function notifyRecipient(Request $request, int $senderId, int $receiverId, string $message): void
    {
        $senderName = (string) $request->user()->full_name;
        try {
            DB::table('notifications')->insert([
                'user_id' => $receiverId,
                'title' => 'New Message',
                'message' => $senderName . ' sent you a message: "' . mb_strimwidth($message, 0, 80, '…') . '"',
                'notification_type' => 'Message',
                'is_read' => 0,
                'created_at' => now(),
            ]);
        } catch (\Illuminate\Database\QueryException $exception) {
            Log::warning('Message notification could not be recorded.', [
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    private function logActivity(Request $request, int $userId, string $activity, string $module): void
    {
        try {
            DB::table('activity_logs')->insert([
                'user_id' => $userId,
                'activity' => Str::limit($activity, 255, ''),
                'module' => $module,
                'ip_address' => $request->ip(),
                'device_info' => Str::limit((string) $request->userAgent(), 255, ''),
                'created_at' => now(),
            ]);
        } catch (\Illuminate\Database\QueryException $exception) {
            Log::warning('Message activity could not be recorded.', [
                'user_id' => $userId,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
