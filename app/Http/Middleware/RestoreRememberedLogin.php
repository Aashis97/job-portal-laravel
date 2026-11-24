<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RestoreRememberedLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() || !$request->cookies->has('jobportal_remember')) {
            return $next($request);
        }
        if (!Schema::hasTable('remember_tokens')) {
            return $next($request)->withCookie(Cookie::create('jobportal_remember')->withExpires(1));
        }

        $cookie = (string) $request->cookie('jobportal_remember');
        if (!preg_match('/^([a-f0-9]{32}):([a-f0-9]{64})$/i', $cookie, $matches)) {
            return $next($request)->withCookie(Cookie::create('jobportal_remember')->withExpires(1));
        }

        [$selector, $validator] = [$matches[1], $matches[2]];
        $token = DB::table('remember_tokens as tokens')
            ->join('users', 'users.user_id', '=', 'tokens.user_id')
            ->where('tokens.selector', $selector)
            ->first([
                'tokens.token_id',
                'tokens.user_id',
                'tokens.validator_hash',
                'tokens.expires_at',
                'users.account_status',
            ]);
        if (!$token) {
            return $next($request)->withCookie(Cookie::create('jobportal_remember')->withExpires(1));
        }
        if (!hash_equals((string) $token->validator_hash, hash('sha256', $validator))) {
            DB::table('remember_tokens')->where('user_id', $token->user_id)->delete();

            return $next($request)->withCookie(Cookie::create('jobportal_remember')->withExpires(1));
        }
        if ($token->account_status !== 'Active' || now()->greaterThan($token->expires_at)) {
            DB::table('remember_tokens')->where('token_id', $token->token_id)->delete();

            return $next($request)->withCookie(Cookie::create('jobportal_remember')->withExpires(1));
        }

        $user = User::query()->find($token->user_id);
        if (!$user) {
            DB::table('remember_tokens')->where('token_id', $token->token_id)->delete();

            return $next($request)->withCookie(Cookie::create('jobportal_remember')->withExpires(1));
        }

        $selector = bin2hex(random_bytes(16));
        $validator = bin2hex(random_bytes(32));
        try {
            DB::transaction(function () use ($token, $selector, $validator) {
                $deleted = DB::table('remember_tokens')->where('token_id', $token->token_id)->delete();
                if ($deleted !== 1) {
                    throw new \RuntimeException('Remember-me token was already rotated.');
                }
                DB::table('remember_tokens')->insert([
                    'user_id' => $token->user_id,
                    'selector' => $selector,
                    'validator_hash' => hash('sha256', $validator),
                    'expires_at' => now()->addDays(30),
                ]);
            });
        } catch (Throwable $exception) {
            Log::error('Remember-me token rotation failed.', [
                'user_id' => $token->user_id,
                'exception' => $exception->getMessage(),
            ]);
            DB::table('remember_tokens')->where('token_id', $token->token_id)->delete();

            return $next($request)->withCookie(Cookie::create('jobportal_remember')->withExpires(1));
        }

        Auth::login($user, false);
        $request->session()->regenerate();
        $request->session()->put('session_version', max(1, (int) ($user->session_version ?? 1)));

        return $next($request)->withCookie(cookie(
            'jobportal_remember',
            $selector . ':' . $validator,
            60 * 24 * 30,
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            'lax'
        ));
    }
}
