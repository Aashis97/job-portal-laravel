<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        $allowed = array_map('intval', explode('|', $roles));
        if (!in_array((int) $user->role_id, $allowed, true)) {
            abort(403);
        }

        if ($user->account_status !== 'Active') {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('danger', 'Your account is not active.');
        }

        if (Schema::hasColumn('users', 'session_version')) {
            $currentVersion = (int) DB::table('users')
                ->where('user_id', $user->user_id)
                ->value('session_version');
            $sessionVersion = (int) $request->session()->get('session_version', 0);
            if ($currentVersion < 1 || $currentVersion !== $sessionVersion) {
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->with('warning', 'Your password was changed. Please login again.')
                    ->withCookie(cookie()->forget('jobportal_remember'));
            }
        }

        return $next($request);
    }
}
