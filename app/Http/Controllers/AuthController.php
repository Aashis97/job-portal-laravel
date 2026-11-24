<?php

namespace App\Http\Controllers;

use App\Mail\AuthMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class AuthController extends Controller
{
    public function loginForm(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route(match ((int) Auth::user()->role_id) {
                1 => 'admin.dashboard',
                2 => 'employer.dashboard',
                default => 'jobseeker.dashboard',
            });
        }

        return view('auth.login', [
            'captchaQuestion' => $this->captchaQuestion($request),
        ]);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:150'],
            'password' => ['required', 'string'],
            'captcha' => ['required', 'string'],
        ]);

        if (!$this->verifyCaptcha($request, $validated['captcha'])) {
            return back()->withInput($request->only('email'))
                ->with('danger', 'Incorrect security check answer.');
        }

        $email = strtolower(trim($validated['email']));
        $ip = (string) $request->ip();
        if ($this->loginRateLimited($email, $ip)) {
            $minutes = max(1, (int) ceil($this->loginRateLimitSeconds($email, $ip) / 60));

            return back()->withInput($request->only('email'))
                ->with('danger', 'Too many failed login attempts. Please try again in ' . $minutes . ' minute(s).');
        }
        $user = User::query()->where('email', $email)->first();

        if (!$user || !password_verify($validated['password'], (string) $user->password)) {
            $this->recordLoginFailure($email, $ip);
            $this->logAuthActivity(null, 'Failed login attempt for ' . $email);

            return back()->withInput($request->only('email'))
                ->with('danger', 'Invalid email or password.');
        }

        if ($user->account_status !== 'Active') {
            $this->recordLoginFailure($email, $ip);

            return back()->withInput($request->only('email'))
                ->with('danger', 'Your account is not active. Please verify your email or contact support.');
        }

        $this->clearLoginFailures($email);
        Auth::login($user, false);
        $request->session()->regenerate();
        $request->session()->put('session_version', max(1, (int) ($user->session_version ?? 1)));

        $updates = ['last_login' => now()];
        foreach (['failed_login_attempts', 'locked_until'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                $updates[$column] = $column === 'failed_login_attempts' ? 0 : null;
            }
        }
        DB::table('users')->where('user_id', $user->user_id)->update($updates);
        $this->logAuthActivity((int) $user->user_id, 'Logged in');

        $response = $this->dashboardRedirect((int) $user->role_id);
        if ($request->boolean('remember')) {
            $response->withCookie($this->issueRememberCookie((int) $user->user_id, $request));
        }

        return $response;
    }

    public function registerForm(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route(match ((int) Auth::user()->role_id) {
                1 => 'admin.dashboard',
                2 => 'employer.dashboard',
                default => 'jobseeker.dashboard',
            });
        }

        return view('auth.register', [
            'captchaQuestion' => $this->captchaQuestion($request),
        ]);
    }

    public function register(Request $request)
    {
        if (!$request->filled('password_confirmation') && $request->filled('confirm_password')) {
            $request->merge(['password_confirmation' => $request->input('confirm_password')]);
        }
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'min:3', 'max:100'],
            'gender' => ['nullable', 'in:Male,Female,Other'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['exclude_unless:role,job_seeker', 'nullable', 'regex:/^(98|97)[0-9]{8}$/'],
            'company_phone' => ['exclude_unless:role,employer', 'nullable', 'regex:/^(98|97)[0-9]{8}$/'],
            'role' => ['required', 'in:job_seeker,employer'],
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'regex:/[^A-Za-z0-9]/',
                'confirmed',
            ],
            'terms' => ['accepted'],
            'captcha' => ['required', 'string'],
            'company_name' => ['required_if:role,employer', 'nullable', 'max:150'],
            'company_email' => ['required_if:role,employer', 'nullable', 'email', 'max:150'],
            'company_website' => ['nullable', 'url', 'max:255'],
            'company_description' => ['nullable', 'max:5000'],
            'company_address' => ['nullable', 'max:1000'],
            'profile_photo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'company_logo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if (!$this->verifyCaptcha($request, $validated['captcha'])) {
            return back()->withInput()->with('danger', 'Incorrect security check answer.');
        }

        $email = strtolower(trim($validated['email']));
        $ip = (string) $request->ip();
        if ($this->loginRateLimited($email, $ip)) {
            return back()->withInput()->withErrors([
                'email' => 'Too many attempts. Please try again later.',
            ]);
        }
        if (User::query()->where('email', $email)->exists()) {
            return back()->withInput()->with('errors_list', ['An account with this email already exists.']);
        }
        if (
            $validated['role'] === 'employer'
            && DB::table('companies')->where('company_email', strtolower(trim($validated['company_email'])))->exists()
        ) {
            return back()->withInput()->with('errors_list', ['A company account with this email already exists.']);
        }

        $roleId = $validated['role'] === 'employer' ? 2 : 3;
        $token = bin2hex(random_bytes(32));
        $profileImage = 'default.png';
        $companyLogo = 'company.png';
        $uploadedFiles = [];
        $profileUpload = $roleId === 3 ? $request->file('profile_photo') : null;
        $companyUpload = $roleId === 2 ? $request->file('company_logo') : null;
        try {
            if ($profileUpload) {
                $profileImage = $this->storeRegistrationImage($profileUpload, 'profile_images');
                $uploadedFiles[] = $this->registrationImagePath('profile_images', $profileImage);
            }
            if ($companyUpload) {
                $companyLogo = $this->storeRegistrationImage($companyUpload, 'company_logos');
                $uploadedFiles[] = $this->registrationImagePath('company_logos', $companyLogo);
            }
        } catch (Throwable $exception) {
            foreach ($uploadedFiles as $uploadedFile) {
                if (is_file($uploadedFile)) {
                    File::delete($uploadedFile);
                }
            }
            Log::error('Registration image upload failed.', ['exception' => $exception->getMessage()]);

            return back()->withInput()->withErrors([
                'upload' => 'Unable to save the uploaded image. Please try again.',
            ]);
        }
        $userId = null;

        try {
            DB::transaction(function () use (&$userId, $validated, $email, $roleId, $token, $profileImage, $companyLogo) {
                $userId = DB::table('users')->insertGetId([
                    'role_id' => $roleId,
                    'full_name' => trim($validated['full_name']),
                    'gender' => $validated['gender'] ?? null,
                    'email' => $email,
                    'password' => password_hash($validated['password'], PASSWORD_ARGON2ID),
                    'phone' => $roleId === 2
                        ? ($validated['company_phone'] ?? null)
                        : ($validated['phone'] ?? null),
                    'profile_image' => $profileImage,
                    'preferred_language' => 'en',
                    'account_status' => 'Pending',
                    'email_verified' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('email_verifications')->insert([
                    'user_id' => $userId,
                    'token' => hash('sha256', $token),
                    'expires_at' => now()->addHours(24),
                    'created_at' => now(),
                ]);

                if ($roleId === 3) {
                    DB::table('job_seekers')->insert([
                        'user_id' => $userId,
                        'gender' => $validated['gender'] ?? null,
                        'profile_completed' => 0,
                        'availability' => 'Immediately',
                    ]);
                } else {
                    $companyId = DB::table('companies')->insertGetId([
                        'company_name' => trim($validated['company_name']),
                        'company_email' => strtolower(trim($validated['company_email'])),
                        'company_phone' => $validated['company_phone'] ?? null,
                        'website' => $validated['company_website'] ?? null,
                        'logo' => $companyLogo,
                        'description' => $validated['company_description'] ?? null,
                        'address' => $validated['company_address'] ?? null,
                        'status' => 'Pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('employers')->insert([
                        'user_id' => $userId,
                        'company_id' => $companyId,
                        'designation' => 'Employer',
                        'department' => null,
                        'is_primary_contact' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
        } catch (Throwable $exception) {
            foreach ($uploadedFiles as $uploadedFile) {
                if (is_file($uploadedFile)) {
                    File::delete($uploadedFile);
                }
            }
            Log::error('Account registration transaction failed.', [
                'email' => $email,
                'exception' => $exception->getMessage(),
            ]);

            return back()->withInput()->with('errors_list', ['Registration failed. Please try again later.']);
        }

        try {
            Mail::to($email)->send(new AuthMail(
                'Verify your email address',
                'Verify Your Email',
                'Confirm your email address to activate your Online Job Portal account. This link expires in 24 hours.',
                'Verify Email',
                route('auth.verify-email', ['token' => $token])
            ));
        } catch (Throwable $exception) {
            Log::warning('Registration verification email could not be sent.', [
                'user_id' => $userId,
                'exception' => $exception->getMessage(),
            ]);

            return redirect()->route('login')->with(
                'warning',
                'Your account was created, but the verification email could not be sent. Use Resend Verification before signing in.'
            );
        }
        $this->logAuthActivity((int) $userId, 'Registered as ' . $validated['role']);

        return redirect()->route('login')->with(
            'success',
            $roleId === 2
                ? 'Employer registration is complete. Verify your work email, then wait for admin approval of your company before posting jobs.'
                : 'Registration successful. Please check your email and follow the verification link before signing in.'
        );
    }

    public function forgotPasswordForm(Request $request)
    {
        if (Auth::check()) {
            return $this->dashboardRedirect((int) Auth::user()->role_id);
        }

        return view('auth.forgot-password', [
            'captchaQuestion' => $this->captchaQuestion($request),
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:150'],
            'captcha' => ['required', 'string'],
        ]);
        if (!$this->verifyCaptcha($request, $validated['captcha'])) {
            return back()->withInput($request->only('email'))
                ->with('danger', 'Incorrect security check answer.');
        }

        $email = strtolower(trim($validated['email']));
        if ($this->loginRateLimited($email, (string) $request->ip())) {
            return back()->with('danger', 'Too many requests. Please try again later.');
        }
        $this->recordLoginFailure($email, (string) $request->ip());

        $generic = 'If the email address is registered, a password reset link has been sent.';
        $user = DB::table('users')
            ->where('email', $email)
            ->first(['user_id', 'full_name', 'email', 'account_status']);
        if (!$user || $user->account_status !== 'Active') {
            return back()->with('success', $generic);
        }
        if (!Schema::hasTable('password_reset_tokens')) {
            Log::error('Password reset is unavailable because its token table is missing.');

            return back()->with('success', $generic);
        }

        $plainToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $plainToken);
        try {
            DB::transaction(function () use ($user, $tokenHash, $request) {
                DB::table('password_reset_tokens')->where('user_id', $user->user_id)->delete();
                DB::table('password_reset_tokens')->insert([
                    'user_id' => $user->user_id,
                    'token_hash' => $tokenHash,
                    'expires_at' => now()->addMinutes(30),
                    'used_at' => null,
                    'ip_address' => mb_substr((string) $request->ip(), 0, 45),
                    'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
                ]);
            });
        } catch (Throwable $exception) {
            Log::error('Password reset token could not be created.', [
                'user_id' => $user->user_id,
                'exception' => $exception->getMessage(),
            ]);

            return back()->with('success', $generic);
        }

        $resetUrl = route('auth.password.reset', ['token' => $plainToken]);
        try {
            Mail::to($user->email)->send(new AuthMail(
                'Reset your password',
                'Reset Password',
                'Use the secure link below to reset your Online Job Portal password. This link expires in 30 minutes.',
                'Reset Password',
                $resetUrl
            ));
        } catch (Throwable $exception) {
            Log::error('Password reset email could not be sent.', [
                'user_id' => $user->user_id,
                'exception' => $exception->getMessage(),
            ]);
            DB::table('password_reset_tokens')
                ->where('user_id', $user->user_id)
                ->where('token_hash', $tokenHash)
                ->delete();
        }

        $this->logAuthActivity((int) $user->user_id, 'Password reset requested');

        return back()->with('success', $generic);
    }

    public function resetPasswordForm(Request $request)
    {
        if (Auth::check()) {
            return $this->dashboardRedirect((int) Auth::user()->role_id);
        }
        $token = trim((string) $request->query('token', ''));
        if (!$this->validResetToken($token)) {
            return redirect()->route('auth.password.forgot')
                ->with('danger', 'This password reset link is invalid or has expired.');
        }
        $tokenHash = hash('sha256', $token);
        $reset = DB::table('password_reset_tokens as tokens')
            ->join('users', 'users.user_id', '=', 'tokens.user_id')
            ->where('tokens.token_hash', $tokenHash)
            ->whereNull('tokens.used_at')
            ->where('tokens.expires_at', '>', now())
            ->first(['users.email']);
        if (!$reset) {
            return redirect()->route('auth.password.forgot')
                ->with('danger', 'This password reset link is invalid or has expired.');
        }

        return view('auth.reset-password', [
            'token' => $token,
            'maskedEmail' => preg_replace('/(?<=.).(?=[^@]*?@)/', '*', (string) $reset->email),
        ]);
    }

    public function resetPassword(Request $request)
    {
        if (Auth::check()) {
            return $this->dashboardRedirect((int) Auth::user()->role_id);
        }
        $validated = $request->validate([
            'token' => ['required', 'string', 'size:64', 'regex:/^[a-fA-F0-9]+$/'],
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'regex:/[^A-Za-z0-9]/',
                'same:confirm_password',
            ],
            'confirm_password' => ['required', 'string'],
        ]);
        if (!Schema::hasTable('password_reset_tokens')) {
            Log::error('Password reset was requested but its token table is missing.');

            return redirect()->route('auth.password.forgot')
                ->with('danger', 'Unable to reset your password right now. Please try again later.');
        }

        $tokenHash = hash('sha256', $validated['token']);
        $reset = null;
        try {
            $reset = DB::transaction(function () use ($validated, $tokenHash) {
                $token = DB::table('password_reset_tokens')
                    ->where('token_hash', $tokenHash)
                    ->whereNull('used_at')
                    ->where('expires_at', '>', now())
                    ->lockForUpdate()
                    ->first();
                if (!$token) {
                    return null;
                }

                $userUpdates = [
                    'password' => password_hash($validated['password'], PASSWORD_ARGON2ID),
                ];
                if (Schema::hasColumn('users', 'session_version')) {
                    $userUpdates['session_version'] = DB::raw('session_version + 1');
                }
                if (Schema::hasColumn('users', 'updated_at')) {
                    $userUpdates['updated_at'] = now();
                }
                DB::table('users')->where('user_id', $token->user_id)->update($userUpdates);

                $consumed = DB::table('password_reset_tokens')
                    ->where('reset_id', $token->reset_id)
                    ->whereNull('used_at')
                    ->where('expires_at', '>', now())
                    ->update(['used_at' => now()]);
                if ($consumed !== 1) {
                    throw new \RuntimeException('Password reset token was already consumed.');
                }
                DB::table('password_reset_tokens')
                    ->where('user_id', $token->user_id)
                    ->where('reset_id', '<>', $token->reset_id)
                    ->delete();
                if (Schema::hasTable('remember_tokens')) {
                    DB::table('remember_tokens')->where('user_id', $token->user_id)->delete();
                }

                return $token;
            });
        } catch (Throwable $exception) {
            Log::error('Password reset transaction failed.', [
                'exception' => $exception->getMessage(),
            ]);

            return redirect()->route('auth.password.forgot')
                ->with('danger', 'Unable to reset your password. Please try again later.');
        }

        if (!$reset) {
            return redirect()->route('auth.password.forgot')
                ->with('danger', 'This password reset link is invalid or has expired.');
        }

        $user = DB::table('users')->where('user_id', $reset->user_id)->first(['email', 'full_name']);
        if ($user) {
            try {
                Mail::to($user->email)->send(new AuthMail(
                    'Your password was changed',
                    'Password Changed',
                    'Your Online Job Portal password was changed. If you did not make this change, contact support.'
                ));
            } catch (Throwable $exception) {
                Log::warning('Password change security email could not be sent.', [
                    'user_id' => $reset->user_id,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }
        $this->logAuthActivity((int) $reset->user_id, 'Password reset completed');

        return redirect()->route('login')
            ->with('success', 'Password changed successfully. Please login with your new password.');
    }

    public function verifyEmail(Request $request)
    {
        if (Auth::check()) {
            return $this->dashboardRedirect((int) Auth::user()->role_id);
        }
        $token = trim((string) $request->query('token', ''));
        if (!$this->validResetToken($token)) {
            return redirect()->route('login')->with('danger', 'Invalid verification link.');
        }

        $result = null;
        try {
            $result = DB::transaction(function () use ($token) {
                $verification = DB::table('email_verifications')
                    ->join('users', 'users.user_id', '=', 'email_verifications.user_id')
                    ->where('email_verifications.token', hash('sha256', $token))
                    ->lockForUpdate()
                    ->first([
                        'email_verifications.verification_id',
                        'email_verifications.user_id',
                        'email_verifications.expires_at',
                        'email_verifications.verified_at',
                        'users.email',
                        'users.full_name',
                    ]);
                if (!$verification) {
                    return 'invalid';
                }
                if ($verification->verified_at !== null) {
                    return 'already_verified';
                }
                if (now()->greaterThanOrEqualTo($verification->expires_at)) {
                    return 'expired';
                }

                $updated = DB::table('email_verifications')
                    ->where('verification_id', $verification->verification_id)
                    ->whereNull('verified_at')
                    ->where('expires_at', '>', now())
                    ->update(['verified_at' => now()]);
                if ($updated !== 1) {
                    return 'expired';
                }
                $userUpdates = [
                    'email_verified' => 1,
                    'account_status' => DB::raw("CASE WHEN account_status = 'Pending' THEN 'Active' ELSE account_status END"),
                ];
                if (Schema::hasColumn('users', 'updated_at')) {
                    $userUpdates['updated_at'] = now();
                }
                DB::table('users')->where('user_id', $verification->user_id)->update($userUpdates);

                return $verification;
            });
        } catch (Throwable $exception) {
            Log::error('Email verification failed.', ['exception' => $exception->getMessage()]);

            return redirect()->route('login')
                ->with('danger', 'Unable to verify your account right now. Please try again.');
        }

        if ($result === 'expired') {
            return redirect()->route('auth.verification.resend')
                ->with('warning', 'Verification link has expired. Please request a new one.');
        }
        if ($result === 'invalid') {
            return redirect()->route('login')->with('danger', 'Verification link is invalid.');
        }
        if ($result === 'already_verified') {
            return redirect()->route('login')->with('success', 'Your email has already been verified.');
        }

        try {
            Mail::to($result->email)->send(new AuthMail(
                'Welcome to Online Job Portal',
                'Email Verified',
                'Your email address is verified. You can now sign in to your account.',
                'Login',
                route('login')
            ));
        } catch (Throwable $exception) {
            Log::warning('Verification welcome email could not be sent.', [
                'user_id' => $result->user_id,
                'exception' => $exception->getMessage(),
            ]);
        }
        $this->logAuthActivity((int) $result->user_id, 'Email verified');

        return redirect()->route('login')->with('success', 'Email verified successfully. You can now login.');
    }

    public function resendVerificationForm(Request $request)
    {
        if (Auth::check()) {
            return $this->dashboardRedirect((int) Auth::user()->role_id);
        }

        return view('auth.resend-verification');
    }

    public function resendVerification(Request $request)
    {
        $validated = $request->validate(['email' => ['required', 'email', 'max:150']]);
        $email = strtolower(trim($validated['email']));
        if ($this->loginRateLimited($email, (string) $request->ip())) {
            return back()->with('danger', 'Too many requests. Please try again later.');
        }
        $this->recordLoginFailure($email, (string) $request->ip());
        $generic = 'If the email address is registered, a verification email has been sent.';
        $user = DB::table('users')
            ->where('email', $email)
            ->first(['user_id', 'full_name', 'email', 'email_verified', 'account_status']);
        if (!$user || (bool) $user->email_verified || $user->account_status !== 'Pending') {
            return back()->with('success', $generic);
        }

        $token = bin2hex(random_bytes(32));
        try {
            DB::transaction(function () use ($user, $token) {
                DB::table('email_verifications')->where('user_id', $user->user_id)->delete();
                DB::table('email_verifications')->insert([
                    'user_id' => $user->user_id,
                    'token' => hash('sha256', $token),
                    'expires_at' => now()->addHours(24),
                ]);
            });
        } catch (Throwable $exception) {
            Log::error('Verification token could not be recreated.', [
                'user_id' => $user->user_id,
                'exception' => $exception->getMessage(),
            ]);

            return back()->with('success', $generic);
        }

        try {
            Mail::to($user->email)->send(new AuthMail(
                'Verify your email address',
                'Verify Your Email',
                'Confirm your email address to activate your Online Job Portal account. This link expires in 24 hours.',
                'Verify Email',
                route('auth.verify-email', ['token' => $token])
            ));
        } catch (Throwable $exception) {
            Log::error('Verification email could not be sent.', [
                'user_id' => $user->user_id,
                'exception' => $exception->getMessage(),
            ]);
        }
        $this->logAuthActivity((int) $user->user_id, 'Verification email resent');

        return back()->with('success', $generic);
    }

    public function logout(Request $request)
    {
        $userId = Auth::id();
        if ($userId && Schema::hasTable('remember_tokens')) {
            DB::table('remember_tokens')->where('user_id', $userId)->delete();
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing')->withCookie(Cookie::forget('jobportal_remember'));
    }

    private function captchaQuestion(Request $request): string
    {
        $captcha = $request->session()->get('captcha');
        if (
            !is_array($captcha)
            || empty($captcha['question'])
            || (int) ($captcha['expires_at'] ?? 0) < time()
        ) {
            $first = random_int(2, 9);
            $second = random_int(2, 9);
            $captcha = [
                'answer' => $first + $second,
                'question' => 'What is ' . $first . ' + ' . $second . '?',
                'expires_at' => time() + 300,
            ];
            $request->session()->put('captcha', $captcha);
        }

        return (string) $captcha['question'];
    }

    private function verifyCaptcha(Request $request, string $input): bool
    {
        $captcha = $request->session()->pull('captcha');
        if (!is_array($captcha) || (int) ($captcha['expires_at'] ?? 0) < time()) {
            return false;
        }
        $answer = trim($input);

        return ctype_digit($answer)
            && hash_equals((string) ($captcha['answer'] ?? ''), $answer);
    }

    private function validResetToken(string $token): bool
    {
        return strlen($token) === 64 && ctype_xdigit($token);
    }

    private function dashboardRedirect(int $roleId)
    {
        return redirect()->route(match ($roleId) {
            1 => 'admin.dashboard',
            2 => 'employer.dashboard',
            default => 'jobseeker.dashboard',
        });
    }

    private function loginRateLimited(string $email, string $ip): bool
    {
        $cache = Cache::store('file');

        return (int) $cache->get($this->emailRateKey($email) . ':attempts', 0) >= 5
            || (int) $cache->get($this->ipRateKey($ip) . ':attempts', 0) >= 15;
    }

    private function loginRateLimitSeconds(string $email, string $ip): int
    {
        $cache = Cache::store('file');
        $now = time();

        return max(
            (int) $cache->get($this->emailRateKey($email) . ':expires', $now) - $now,
            (int) $cache->get($this->ipRateKey($ip) . ':expires', $now) - $now
        );
    }

    private function recordLoginFailure(string $email, string $ip): void
    {
        $this->incrementFailureCounter($this->emailRateKey($email));
        $this->incrementFailureCounter($this->ipRateKey($ip));
    }

    private function clearLoginFailures(string $email): void
    {
        $cache = Cache::store('file');
        $cache->forget($this->emailRateKey($email) . ':attempts');
        $cache->forget($this->emailRateKey($email) . ':expires');
    }

    private function incrementFailureCounter(string $key): void
    {
        $cache = Cache::store('file');
        $expiresAt = (int) $cache->get($key . ':expires', 0);
        if ($expiresAt <= time()) {
            $cache->put($key . ':attempts', 0, now()->addMinutes(15));
            $expiresAt = time() + 900;
            $cache->put($key . ':expires', $expiresAt, now()->addMinutes(15));
        }
        $cache->increment($key . ':attempts');
    }

    private function emailRateKey(string $email): string
    {
        return 'auth:email:' . hash('sha256', Str::lower($email));
    }

    private function ipRateKey(string $ip): string
    {
        return 'auth:ip:' . hash('sha256', $ip);
    }

    private function issueRememberCookie(int $userId, Request $request)
    {
        if (!Schema::hasTable('remember_tokens')) {
            Log::warning('Remember-me was requested but remember_tokens is unavailable.');

            return Cookie::forget('jobportal_remember');
        }

        $selector = bin2hex(random_bytes(16));
        $validator = bin2hex(random_bytes(32));
        $expiresAt = now()->addDays(30);
        try {
            DB::transaction(function () use ($userId, $selector, $validator, $expiresAt) {
                DB::table('remember_tokens')->where('user_id', $userId)->delete();
                DB::table('remember_tokens')->insert([
                    'user_id' => $userId,
                    'selector' => $selector,
                    'validator_hash' => hash('sha256', $validator),
                    'expires_at' => $expiresAt,
                ]);
            });
        } catch (Throwable $exception) {
            Log::error('Remember-me token could not be issued.', [
                'user_id' => $userId,
                'exception' => $exception->getMessage(),
            ]);

            return Cookie::forget('jobportal_remember');
        }

        return cookie(
            'jobportal_remember',
            $selector . ':' . $validator,
            60 * 24 * 30,
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            'lax'
        );
    }

    private function storeRegistrationImage(UploadedFile $upload, string $directoryName): string
    {
        $extension = strtolower($upload->extension());
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;
        if (!in_array($extension, ['jpg', 'png', 'webp'], true)) {
            throw new \RuntimeException('Uploaded image type is not supported.');
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $directory = dirname(base_path()) . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . $directoryName;
        File::ensureDirectoryExists($directory, 0755, true);
        $upload->move($directory, $filename);

        return $filename;
    }

    private function registrationImagePath(string $directoryName, string $filename): string
    {
        return dirname(base_path()) . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . $directoryName . DIRECTORY_SEPARATOR . $filename;
    }

    private function logAuthActivity(?int $userId, string $activity): void
    {
        if (!Schema::hasTable('activity_logs')) {
            return;
        }

        try {
            DB::table('activity_logs')->insert([
                'user_id' => $userId,
                'activity' => Str::limit($activity, 255, ''),
                'module' => 'Authentication',
                'created_at' => now(),
            ]);
        } catch (Throwable $exception) {
            Log::warning('Authentication activity could not be recorded.', [
                'user_id' => $userId,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
