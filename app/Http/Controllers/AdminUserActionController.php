<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AdminUserActionController extends Controller
{
    public function update(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'min:1'],
            'action' => ['required', 'in:activate,block,suspend,force_logout,verify_email,change_role,delete'],
            'role_id' => ['required_if:action,change_role', 'nullable', 'integer', 'in:1,2,3'],
            'delete_confirmation' => ['required_if:action,delete', 'nullable', 'string', 'max:100'],
        ]);
        $userId = (int) $validated['user_id'];
        $action = $validated['action'];
        $adminId = (int) $request->user()->getAuthIdentifier();
        $redirect = redirect()->route('admin.user-details', ['id' => $userId]);

        try {
            $result = DB::transaction(function () use ($request, $userId, $action, $adminId, $validated) {
                $target = DB::table('users')->where('user_id', $userId)->lockForUpdate()->first([
                    'user_id',
                    'role_id',
                    'full_name',
                    'email',
                    'account_status',
                    'email_verified',
                ]);
                if (!$target) {
                    return 'missing';
                }

                $isSelf = $adminId === $userId;
                if ($isSelf && in_array($action, ['block', 'suspend', 'change_role', 'delete', 'force_logout'], true)) {
                    return 'self';
                }

                if (
                    in_array($action, ['change_role', 'delete', 'block', 'suspend'], true)
                    && (int) $target->role_id === 1
                    && (int) DB::table('users')->where('role_id', 1)->where('account_status', 'Active')->count() <= 1
                ) {
                    return 'last_admin';
                }

                $values = [];
                if (Schema::hasColumn('users', 'updated_at')) {
                    $values['updated_at'] = now();
                }

                switch ($action) {
                    case 'activate':
                        $values['account_status'] = 'Active';
                        if (Schema::hasColumn('users', 'failed_login_attempts')) {
                            $values['failed_login_attempts'] = 0;
                        }
                        if (Schema::hasColumn('users', 'locked_until')) {
                            $values['locked_until'] = null;
                        }
                        DB::table('users')->where('user_id', $userId)->update($values);
                        return 'activated';

                    case 'block':
                    case 'suspend':
                        $values['account_status'] = $action === 'block' ? 'Blocked' : 'Inactive';
                        $this->revokeSessions($userId);
                        DB::table('users')->where('user_id', $userId)->update($values);
                        return $action === 'block' ? 'blocked' : 'suspended';

                    case 'force_logout':
                        if (Schema::hasColumn('users', 'session_version')) {
                            DB::table('users')->where('user_id', $userId)->increment('session_version');
                        }
                        $this->deleteUserTokens('remember_tokens', $userId);
                        DB::table('users')->where('user_id', $userId)->update($values);
                        return 'logged_out';

                    case 'verify_email':
                        $values['email_verified'] = 1;
                        DB::table('users')->where('user_id', $userId)->update($values);
                        return 'verified';

                    case 'change_role':
                        $newRole = (int) $validated['role_id'];
                        if ($newRole === (int) $target->role_id) {
                            return 'same_role';
                        }

                        $values['role_id'] = $newRole;
                        if (Schema::hasColumn('users', 'session_version')) {
                            DB::table('users')->where('user_id', $userId)->increment('session_version');
                        }
                        DB::table('users')->where('user_id', $userId)->update($values);
                        $this->ensureRoleProfile($newRole, $userId, (string) $target->full_name, (string) $target->email);
                        $this->deleteUserTokens('remember_tokens', $userId);
                        return 'role_changed';

                    case 'delete':
                        if (($validated['delete_confirmation'] ?? null) !== $target->full_name) {
                            return 'delete_confirmation';
                        }
                        foreach (['remember_tokens', 'password_reset_tokens', 'email_verifications'] as $table) {
                            $this->deleteUserTokens($table, $userId);
                        }
                        DB::table('users')->where('user_id', $userId)->delete();
                        return 'deleted';
                }

                return 'invalid';
            });
        } catch (\Throwable $exception) {
            Log::error('Admin user action failed.', [
                'action' => $action,
                'user_id' => $userId,
                'exception' => $exception->getMessage(),
            ]);

            return $redirect->with('danger', 'Unable to complete the action. Please try again.');
        }

        if ($result === 'missing') {
            return redirect()->route('admin.users')->with('danger', 'User not found.');
        }
        if ($result === 'self') {
            return $redirect->with('danger', 'You cannot perform this action on your own account.');
        }
        if ($result === 'last_admin') {
            return $redirect->with('danger', 'Cannot modify the only remaining active administrator.');
        }
        if ($result === 'same_role') {
            return $redirect->with('info', 'User already has this role.');
        }
        if ($result === 'delete_confirmation') {
            return $redirect->with('danger', 'Type the user’s full name exactly to confirm deletion.');
        }
        if ($result === 'invalid') {
            return redirect()->route('admin.users')->with('danger', 'Invalid action.');
        }
        if ($result === 'deleted') {
            $this->logActivity($request, 'Admin deleted user #' . $userId, 'Admin');
            return redirect()->route('admin.users')->with('success', 'User deleted permanently.');
        }

        $activity = match ($result) {
            'activated' => 'Admin activated user #' . $userId,
            'blocked' => 'Admin blocked user #' . $userId,
            'suspended' => 'Admin suspended user #' . $userId,
            'logged_out' => 'Admin forced logout for user #' . $userId,
            'verified' => 'Admin verified email for user #' . $userId,
            'role_changed' => 'Admin changed role for user #' . $userId,
        };
        $this->logActivity($request, $activity, 'Admin');

        $message = match ($result) {
            'activated' => 'User activated successfully.',
            'blocked' => 'User blocked. All their active sessions will be terminated on their next request.',
            'suspended' => 'User suspended successfully.',
            'logged_out' => 'User will be logged out of every device.',
            'verified' => 'Email marked as verified.',
            'role_changed' => 'User role updated. They will need to log in again.',
        };

        return $redirect->with('success', $message);
    }

    private function ensureRoleProfile(int $roleId, int $userId, string $name, string $email): void
    {
        if ($roleId === 2 && Schema::hasTable('employers') && !DB::table('employers')->where('user_id', $userId)->exists()) {
            if (!Schema::hasTable('companies')) {
                throw new \RuntimeException('Companies table is required to create an employer profile.');
            }
            $companyId = DB::table('companies')->insertGetId([
                'company_name' => $name . "'s Company",
                'company_email' => $email,
                'company_phone' => null,
                'status' => 'Pending',
            ]);
            DB::table('employers')->insert([
                'user_id' => $userId,
                'company_id' => $companyId,
                'designation' => 'Employer',
                'is_primary_contact' => true,
            ]);
        }

        if ($roleId === 3 && Schema::hasTable('job_seekers') && !DB::table('job_seekers')->where('user_id', $userId)->exists()) {
            DB::table('job_seekers')->insert([
                'user_id' => $userId,
                'profile_completed' => 0,
                'availability' => 'Immediately',
            ]);
        }
    }

    private function revokeSessions(int $userId): void
    {
        if (Schema::hasColumn('users', 'session_version')) {
            DB::table('users')->where('user_id', $userId)->increment('session_version');
        }
        $this->deleteUserTokens('remember_tokens', $userId);
    }

    private function deleteUserTokens(string $table, int $userId): void
    {
        if (Schema::hasTable($table)) {
            DB::table($table)->where('user_id', $userId)->delete();
        }
    }

    private function logActivity(Request $request, string $activity, string $module): void
    {
        try {
            DB::table('activity_logs')->insert([
                'user_id' => $request->user()->getAuthIdentifier(),
                'activity' => Str::limit($activity, 255, ''),
                'module' => $module,
                'ip_address' => $request->ip(),
                'device_info' => Str::limit((string) $request->userAgent(), 255, ''),
                'created_at' => now(),
            ]);
        } catch (QueryException $exception) {
            Log::warning('Admin user activity could not be recorded.', [
                'user_id' => $request->user()->getAuthIdentifier(),
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
