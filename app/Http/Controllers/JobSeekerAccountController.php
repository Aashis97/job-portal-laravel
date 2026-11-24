<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class JobSeekerAccountController extends Controller
{
    public function settings(Request $request)
    {
        $user = DB::table('users')
            ->where('user_id', $request->user()->user_id)
            ->first([
                'user_id',
                'full_name',
                'email',
                'phone',
                'profile_image',
                'account_status',
                'email_verified',
                'created_at',
            ]);

        if (!$user) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('danger', 'User account could not be found.');
        }

        return view('jobseeker.settings', compact('user'));
    }

    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => [
                'required',
                'string',
                'min:8',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'regex:/[^A-Za-z0-9]/',
            ],
            'confirm_password' => ['required', 'same:new_password'],
        ], [
            'new_password.regex' => 'Password must include uppercase, lowercase, number and special characters.',
            'confirm_password.same' => 'New password and confirmation do not match.',
        ]);

        $userId = (int) $request->user()->user_id;
        $user = DB::table('users')
            ->where('user_id', $userId)
            ->first(['password']);

        if (!$user || !password_verify($validated['current_password'], (string) $user->password)) {
            return redirect()->route('jobseeker.settings')
                ->with('danger', $user ? 'Current password is incorrect.' : 'User account could not be found.');
        }

        if ($validated['current_password'] === $validated['new_password']) {
            return redirect()->route('jobseeker.settings')
                ->with('danger', 'New password must be different from your current password.');
        }

        $passwordHash = password_hash($validated['new_password'], PASSWORD_ARGON2ID);
        if ($passwordHash === false) {
            Log::error('Password hashing failed while changing a Job Seeker password.', ['user_id' => $userId]);

            return redirect()->route('jobseeker.settings')
                ->with('danger', 'Unable to update password. Please try again.');
        }

        DB::transaction(function () use ($userId, $passwordHash) {
            DB::table('users')->where('user_id', $userId)->update([
                'password' => $passwordHash,
                'session_version' => DB::raw('session_version + 1'),
                'updated_at' => now(),
            ]);
        });

        $request->session()->put(
            'session_version',
            (int) $request->session()->get('session_version', 1) + 1
        );
        $this->logActivity($request, $userId, 'Password changed', 'Authentication');

        return redirect()->route('jobseeker.settings')
            ->with('success', 'Your password has been changed successfully.');
    }

    public function account(Request $request)
    {
        $user = DB::table('users')
            ->where('user_id', $request->user()->user_id)
            ->first(['user_id', 'full_name', 'email', 'account_status', 'created_at']);

        if (!$user) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('danger', 'Your account could not be loaded.');
        }

        return view('jobseeker.account', compact('user'));
    }

    public function deactivate(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'confirmation' => ['required', 'string'],
        ]);

        $userId = (int) $request->user()->user_id;
        if (strtoupper(trim($validated['confirmation'])) !== 'DEACTIVATE') {
            return redirect()->route('jobseeker.account')
                ->with('danger', 'Please enter your current password and confirm by typing DEACTIVATE.');
        }

        $user = DB::table('users')->where('user_id', $userId)->first(['password']);
        if (!$user || !password_verify($validated['current_password'], (string) $user->password)) {
            return redirect()->route('jobseeker.account')
                ->with('danger', 'The current password you entered was incorrect.');
        }

        DB::table('users')->where('user_id', $userId)->update([
            'account_status' => 'Inactive',
            'updated_at' => now(),
        ]);
        $this->logActivity($request, $userId, 'Deactivated account', 'Account');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing')
            ->with('warning', 'Your account has been deactivated. You can reactivate it by contacting support.');
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
            Log::warning('Job Seeker account activity could not be recorded.', [
                'user_id' => $userId,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
