<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class EmployerAccountController extends Controller
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
            return redirect()->route('login')->with('danger', 'Unable to load your account information.');
        }

        $employer = DB::table('employers as e')
            ->leftJoin('companies as c', 'c.company_id', '=', 'e.company_id')
            ->where('e.user_id', $user->user_id)
            ->first([
                'e.designation',
                'e.department',
                'e.is_primary_contact',
                'c.company_name',
            ]);

        return view('employer.settings', compact('user', 'employer'));
    }

    public function updateSettings(Request $request)
    {
        $userId = (int) $request->user()->user_id;
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($userId, 'user_id')],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s().]*$/'],
        ], [
            'phone.regex' => 'Please enter a valid phone number.',
        ]);
        $currentEmail = (string) DB::table('users')->where('user_id', $userId)->value('email');
        if ($currentEmail === '') {
            return redirect()->route('employer.settings')
                ->with('danger', 'Your account could not be found.');
        }

        $emailChanged = strcasecmp(trim($currentEmail), trim($validated['email'])) !== 0;
        try {
            DB::transaction(function () use ($userId, $validated, $emailChanged) {
                $updates = [
                    'full_name' => trim($validated['full_name']),
                    'email' => trim($validated['email']),
                    'phone' => $validated['phone'] ?? null,
                    'updated_at' => now(),
                ];
                if ($emailChanged) {
                    $updates['email_verified'] = 0;
                }

                DB::table('users')->where('user_id', $userId)->update($updates);
            });
        } catch (Throwable $exception) {
            Log::error('Employer account settings update failed.', [
                'user_id' => $userId,
                'exception' => $exception->getMessage(),
            ]);

            return redirect()->route('employer.settings')
                ->withInput()
                ->with('danger', 'Unable to update your account information right now. Please try again.');
        }

        $this->logActivity($request, $userId, 'Updated account settings', 'Employer');
        $message = $emailChanged
            ? 'Account information updated successfully. Your email address has been changed and requires verification again.'
            : 'Account information updated successfully.';

        return redirect()->route('employer.settings')->with('success', $message);
    }

    public function changePasswordForm()
    {
        return view('employer.change-password');
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
            'confirm_password.same' => 'New password and confirmation password do not match.',
        ]);

        $userId = (int) $request->user()->user_id;
        $storedPassword = (string) DB::table('users')->where('user_id', $userId)->value('password');
        if ($storedPassword === '' || !password_verify($validated['current_password'], $storedPassword)) {
            return redirect()->route('employer.password.form')
                ->with('danger', 'The current password you entered is incorrect.');
        }
        if (hash_equals($validated['current_password'], $validated['new_password'])) {
            return redirect()->route('employer.password.form')
                ->with('danger', 'Your new password must be different from your current password.');
        }

        $passwordHash = password_hash($validated['new_password'], PASSWORD_ARGON2ID);
        if ($passwordHash === false) {
            Log::error('Employer password hashing failed.', ['user_id' => $userId]);

            return redirect()->route('employer.password.form')
                ->with('danger', 'Unable to change your password right now. Please try again.');
        }

        $hasSessionVersion = Schema::hasColumn('users', 'session_version');
        try {
            DB::transaction(function () use ($userId, $passwordHash, $hasSessionVersion) {
                $updates = ['password' => $passwordHash, 'updated_at' => now()];
                if ($hasSessionVersion) {
                    $updates['session_version'] = DB::raw('session_version + 1');
                }
                DB::table('users')->where('user_id', $userId)->update($updates);

                if (Schema::hasTable('remember_tokens')) {
                    DB::table('remember_tokens')->where('user_id', $userId)->delete();
                }
            });
        } catch (Throwable $exception) {
            Log::error('Employer password change failed.', [
                'user_id' => $userId,
                'exception' => $exception->getMessage(),
            ]);

            return redirect()->route('employer.password.form')
                ->with('danger', 'Unable to change your password right now. Please try again.');
        }

        if ($hasSessionVersion) {
            $request->session()->put(
                'session_version',
                (int) $request->session()->get('session_version', 1) + 1
            );
        }
        $this->logActivity($request, $userId, 'Password changed', 'Authentication');

        return redirect()->route('employer.password.form')
            ->with('success', 'Your password has been changed successfully.');
    }

    public function account(Request $request)
    {
        $user = DB::table('users')
            ->where('user_id', $request->user()->user_id)
            ->first(['user_id', 'full_name', 'email', 'account_status', 'created_at']);

        if (!$user) {
            return redirect()->route('login')->with('danger', 'Your account could not be loaded.');
        }

        return view('employer.account', compact('user'));
    }

    public function deactivate(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'confirmation' => ['required', 'string'],
        ]);
        $userId = (int) $request->user()->user_id;
        if (strtoupper(trim($validated['confirmation'])) !== 'DEACTIVATE') {
            return redirect()->route('employer.account')
                ->with('danger', 'Please enter your current password and confirm by typing DEACTIVATE.');
        }

        $storedPassword = (string) DB::table('users')->where('user_id', $userId)->value('password');
        if ($storedPassword === '' || !password_verify($validated['current_password'], $storedPassword)) {
            return redirect()->route('employer.account')
                ->with('danger', 'The current password you entered was incorrect.');
        }

        try {
            DB::transaction(function () use ($userId) {
                DB::table('users')->where('user_id', $userId)->update([
                    'account_status' => 'Inactive',
                    'updated_at' => now(),
                ]);
                if (Schema::hasTable('remember_tokens')) {
                    DB::table('remember_tokens')->where('user_id', $userId)->delete();
                }
            });
        } catch (Throwable $exception) {
            Log::error('Employer account deactivation failed.', [
                'user_id' => $userId,
                'exception' => $exception->getMessage(),
            ]);

            return redirect()->route('employer.account')
                ->with('danger', 'Unable to deactivate your account right now. Please try again.');
        }

        $this->logActivity($request, $userId, 'Deactivated account', 'Account');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing')
            ->with('warning', 'Your account has been deactivated. You can reactivate it by contacting support.');
    }

    private function logActivity(Request $request, int $userId, string $activity, string $module): void
    {
        if (!Schema::hasTable('activity_logs')) {
            return;
        }

        try {
            DB::table('activity_logs')->insert([
                'user_id' => $userId,
                'activity' => Str::limit($activity, 255, ''),
                'module' => $module,
                'ip_address' => $request->ip(),
                'device_info' => Str::limit((string) $request->userAgent(), 255, ''),
                'created_at' => now(),
            ]);
        } catch (QueryException $exception) {
            Log::warning('Employer account activity could not be recorded.', [
                'user_id' => $userId,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
