<?php

namespace Tests\Feature;

use App\Mail\AuthMail;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthParityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        Schema::create('users', function (Blueprint $table) {
            $table->increments('user_id');
            $table->unsignedInteger('role_id');
            $table->string('full_name');
            $table->string('gender')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->string('profile_image')->nullable();
            $table->string('preferred_language')->default('en');
            $table->string('account_status');
            $table->boolean('email_verified')->default(false);
            $table->timestamp('last_login')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unsignedInteger('session_version')->default(1);
        });
        Schema::create('email_verifications', function (Blueprint $table) {
            $table->increments('verification_id');
            $table->unsignedInteger('user_id');
            $table->string('token', 255);
            $table->dateTime('expires_at');
            $table->dateTime('verified_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->increments('reset_id');
            $table->unsignedInteger('user_id');
            $table->string('token_hash', 64)->unique();
            $table->dateTime('expires_at');
            $table->dateTime('used_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('remember_tokens', function (Blueprint $table) {
            $table->increments('token_id');
            $table->unsignedInteger('user_id');
            $table->string('selector', 32)->unique();
            $table->string('validator_hash', 64);
            $table->dateTime('expires_at');
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->bigIncrements('log_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('activity');
            $table->string('module')->nullable();
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('job_seekers', function (Blueprint $table) {
            $table->increments('seeker_id');
            $table->unsignedInteger('user_id')->unique();
            $table->string('gender')->nullable();
            $table->boolean('profile_completed')->default(false);
            $table->string('availability')->default('Immediately');
        });
        Schema::create('companies', function (Blueprint $table) {
            $table->increments('company_id');
            $table->string('company_name', 150);
            $table->string('company_email', 150)->nullable()->unique();
            $table->string('company_phone', 20)->nullable();
            $table->string('website', 255)->nullable();
            $table->string('logo', 255)->default('company.png');
            $table->text('description')->nullable();
            $table->text('address')->nullable();
            $table->string('status')->default('Pending');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
        Schema::create('employers', function (Blueprint $table) {
            $table->increments('employer_id');
            $table->unsignedInteger('user_id')->unique();
            $table->unsignedInteger('company_id');
            $table->string('designation', 100)->nullable();
            $table->string('department', 100)->nullable();
            $table->boolean('is_primary_contact')->default(true);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        DB::table('users')->insert([
            'user_id' => 1,
            'role_id' => 3,
            'full_name' => 'Test Seeker',
            'gender' => null,
            'email' => 'seeker@example.test',
            'password' => password_hash('Old-Password1!', PASSWORD_ARGON2ID),
            'phone' => null,
            'profile_image' => 'default.png',
            'preferred_language' => 'en',
            'account_status' => 'Active',
            'email_verified' => true,
            'created_at' => now(),
            'session_version' => 4,
        ]);
        $this->clearRateKeys('seeker@example.test', '127.0.0.1');
    }

    protected function tearDown(): void
    {
        if (Schema::hasTable('users')) {
            foreach (DB::table('users')->where('profile_image', '<>', 'default.png')->pluck('profile_image') as $filename) {
                File::delete(dirname(base_path()) . DIRECTORY_SEPARATOR . 'uploads'
                    . DIRECTORY_SEPARATOR . 'profile_images' . DIRECTORY_SEPARATOR . $filename);
            }
        }
        if (Schema::hasTable('companies')) {
            foreach (DB::table('companies')->where('logo', '<>', 'company.png')->pluck('logo') as $filename) {
                File::delete(dirname(base_path()) . DIRECTORY_SEPARATOR . 'uploads'
                    . DIRECTORY_SEPARATOR . 'company_logos' . DIRECTORY_SEPARATOR . $filename);
            }
        }

        parent::tearDown();
    }

    public function test_job_seeker_registration_saves_optional_profile_photo_and_verification_token(): void
    {
        $response = $this->withSession([
            'captcha' => ['answer' => 11, 'question' => 'What is 5 + 6?', 'expires_at' => time() + 300],
        ])->post(route('register.submit'), [
            'full_name' => 'New Job Seeker',
            'gender' => 'Female',
            'email' => 'new-seeker@example.test',
            'role' => 'job_seeker',
            'phone' => '9812345678',
            'password' => 'New-Password1!',
            'confirm_password' => 'New-Password1!',
            'terms' => '1',
            'captcha' => '11',
            'profile_photo' => $this->registrationPng(),
        ]);

        $response->assertRedirect(route('login'))
            ->assertSessionHas('success', 'Registration successful. Please check your email and follow the verification link before signing in.');
        $user = DB::table('users')->where('email', 'new-seeker@example.test')->first();
        $this->assertSame(3, (int) $user->role_id);
        $this->assertSame('9812345678', $user->phone);
        $this->assertNotSame('default.png', $user->profile_image);
        $this->assertFileExists(dirname(base_path()) . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'profile_images' . DIRECTORY_SEPARATOR . $user->profile_image);
        $this->assertDatabaseHas('job_seekers', [
            'user_id' => $user->user_id,
            'profile_completed' => 0,
            'availability' => 'Immediately',
        ]);
        $verificationHash = DB::table('email_verifications')->where('user_id', $user->user_id)->value('token');
        $this->assertSame(1, DB::table('email_verifications')->where('user_id', $user->user_id)->count());
        Mail::assertSent(AuthMail::class, function (AuthMail $mail) use ($verificationHash) {
            parse_str((string) parse_url((string) $mail->actionUrl, PHP_URL_QUERY), $query);
            $token = (string) ($query['token'] ?? '');

            return $mail->subjectLine === 'Verify your email address'
                && strlen($token) === 64
                && hash_equals((string) $verificationHash, hash('sha256', $token));
        });
    }

    public function test_employer_registration_saves_company_logo_and_role_records(): void
    {
        $this->withSession([
            'captcha' => ['answer' => 11, 'question' => 'What is 5 + 6?', 'expires_at' => time() + 300],
        ])->post(route('register.submit'), [
            'full_name' => 'New Employer',
            'email' => 'new-employer@example.test',
            'role' => 'employer',
            'company_name' => 'Example Company',
            'company_email' => 'jobs@example.test',
            'company_phone' => '9712345678',
            'password' => 'New-Password1!',
            'confirm_password' => 'New-Password1!',
            'terms' => '1',
            'captcha' => '11',
            'company_logo' => $this->registrationPng(),
        ])->assertRedirect(route('login'))
            ->assertSessionHas('success');

        $user = DB::table('users')->where('email', 'new-employer@example.test')->first();
        $company = DB::table('companies')->where('company_email', 'jobs@example.test')->first();
        $this->assertSame(2, (int) $user->role_id);
        $this->assertSame('9712345678', $user->phone);
        $this->assertSame('Pending', $company->status);
        $this->assertNotSame('company.png', $company->logo);
        $this->assertFileExists(dirname(base_path()) . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'company_logos' . DIRECTORY_SEPARATOR . $company->logo);
        $this->assertDatabaseHas('employers', [
            'user_id' => $user->user_id,
            'company_id' => $company->company_id,
            'designation' => 'Employer',
            'is_primary_contact' => 1,
        ]);
    }

    public function test_registration_rejects_non_image_uploads_without_creating_an_account(): void
    {
        $this->withSession([
            'captcha' => ['answer' => 11, 'question' => 'What is 5 + 6?', 'expires_at' => time() + 300],
        ])->post(route('register.submit'), [
            'full_name' => 'New Job Seeker',
            'email' => 'invalid-photo@example.test',
            'role' => 'job_seeker',
            'password' => 'New-Password1!',
            'confirm_password' => 'New-Password1!',
            'terms' => '1',
            'captcha' => '11',
            'profile_photo' => UploadedFile::fake()->create('not-an-image.txt', 1, 'text/plain'),
        ])->assertSessionHasErrors('profile_photo');

        $this->assertDatabaseMissing('users', ['email' => 'invalid-photo@example.test']);
        $this->assertDatabaseCount('job_seekers', 0);
    }

    public function test_forgot_password_returns_generic_result_and_emails_hashed_single_use_token(): void
    {
        $plainToken = null;
        $response = $this->from(route('auth.password.forgot'))->withSession([
            'captcha' => ['answer' => 11, 'question' => 'What is 5 + 6?', 'expires_at' => time() + 300],
        ])->post(route('auth.password.email'), [
            'email' => 'seeker@example.test',
            'captcha' => '11',
        ]);

        $response->assertRedirect(route('auth.password.forgot'))
            ->assertSessionHas('success', 'If the email address is registered, a password reset link has been sent.');
        Mail::assertSent(AuthMail::class, function (AuthMail $mail) use (&$plainToken) {
            parse_str((string) parse_url((string) $mail->actionUrl, PHP_URL_QUERY), $query);
            $token = (string) ($query['token'] ?? '');
            $plainToken = $token;

            return $mail->subjectLine === 'Reset your password'
                && strlen($token) === 64
                && ctype_xdigit($token)
                && DB::table('password_reset_tokens')->where('token_hash', hash('sha256', $token))->exists();
        });
        $this->assertDatabaseHas('password_reset_tokens', ['token_hash' => hash('sha256', (string) $plainToken)]);
        $this->assertDatabaseMissing('password_reset_tokens', ['token_hash' => $plainToken]);
    }

    public function test_reset_password_consumes_token_revokes_other_auth_tokens_and_updates_session_version(): void
    {
        $plainToken = str_repeat('a', 64);
        DB::table('password_reset_tokens')->insert([
            'reset_id' => 1,
            'user_id' => 1,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addMinutes(30),
        ]);
        DB::table('password_reset_tokens')->insert([
            'reset_id' => 2,
            'user_id' => 1,
            'token_hash' => hash('sha256', str_repeat('b', 64)),
            'expires_at' => now()->addMinutes(30),
        ]);
        DB::table('remember_tokens')->insert([
            'user_id' => 1,
            'selector' => str_repeat('c', 32),
            'validator_hash' => str_repeat('d', 64),
            'expires_at' => now()->addDays(30),
        ]);

        $this->get(route('auth.password.reset', ['token' => $plainToken]))
            ->assertOk()
            ->assertSee('Create New Password')
            ->assertSee('s*****@example.test');
        $this->post(route('auth.password.update'), [
            'token' => $plainToken,
            'password' => 'New-Password2!',
            'confirm_password' => 'New-Password2!',
        ])->assertRedirect(route('login'))
            ->assertSessionHas('success', 'Password changed successfully. Please login with your new password.');

        $user = DB::table('users')->where('user_id', 1)->first();
        $this->assertTrue(password_verify('New-Password2!', $user->password));
        $this->assertSame(5, (int) $user->session_version);
        $this->assertNotNull(DB::table('password_reset_tokens')->where('reset_id', 1)->value('used_at'));
        $this->assertDatabaseMissing('password_reset_tokens', ['reset_id' => 2]);
        $this->assertDatabaseCount('remember_tokens', 0);
        Mail::assertSent(AuthMail::class, fn (AuthMail $mail) => $mail->subjectLine === 'Your password was changed');

        $this->post(route('auth.password.update'), [
            'token' => $plainToken,
            'password' => 'Another-Password3!',
            'confirm_password' => 'Another-Password3!',
        ])->assertRedirect(route('auth.password.forgot'))
            ->assertSessionHas('danger', 'This password reset link is invalid or has expired.');
    }

    public function test_email_verification_activates_pending_account_and_resend_replaces_token(): void
    {
        DB::table('users')->where('user_id', 1)->update([
            'account_status' => 'Pending',
            'email_verified' => 0,
        ]);
        $oldToken = str_repeat('e', 64);
        DB::table('email_verifications')->insert([
            'verification_id' => 1,
            'user_id' => 1,
            'token' => hash('sha256', $oldToken),
            'expires_at' => now()->addHours(24),
        ]);

        $this->get(route('auth.verify-email', ['token' => $oldToken]))
            ->assertRedirect(route('login'))
            ->assertSessionHas('success', 'Email verified successfully. You can now login.');
        $this->assertDatabaseHas('users', [
            'user_id' => 1,
            'account_status' => 'Active',
            'email_verified' => 1,
        ]);
        Mail::assertSent(AuthMail::class, fn (AuthMail $mail) => $mail->subjectLine === 'Welcome to Online Job Portal');

        DB::table('users')->where('user_id', 1)->update([
            'account_status' => 'Pending',
            'email_verified' => 0,
        ]);
        $this->from(route('auth.verification.resend'))->withSession([])
            ->post(route('auth.verification.send'), ['email' => 'seeker@example.test'])
            ->assertRedirect(route('auth.verification.resend'))
            ->assertSessionHas('success', 'If the email address is registered, a verification email has been sent.');
        $this->assertDatabaseCount('email_verifications', 1);
        $newTokenHash = DB::table('email_verifications')->value('token');
        $this->assertNotSame(hash('sha256', $oldToken), $newTokenHash);
        Mail::assertSent(AuthMail::class, fn (AuthMail $mail) => $mail->subjectLine === 'Verify your email address');
    }

    public function test_invalid_or_expired_verification_and_reset_links_do_not_update_accounts(): void
    {
        $expiredToken = str_repeat('f', 64);
        DB::table('password_reset_tokens')->insert([
            'user_id' => 1,
            'token_hash' => hash('sha256', $expiredToken),
            'expires_at' => now()->subMinute(),
        ]);
        DB::table('email_verifications')->insert([
            'user_id' => 1,
            'token' => hash('sha256', $expiredToken),
            'expires_at' => now()->subMinute(),
        ]);

        $this->get(route('auth.password.reset', ['token' => $expiredToken]))
            ->assertRedirect(route('auth.password.forgot'));
        $this->get(route('auth.verify-email', ['token' => $expiredToken]))
            ->assertRedirect(route('auth.verification.resend'))
            ->assertSessionHas('warning');
        $this->assertDatabaseHas('users', ['user_id' => 1, 'account_status' => 'Active']);
    }

    public function test_login_enforces_legacy_attempt_limits_and_remember_cookie_restores_rotating_token(): void
    {
        $email = 'seeker@example.test';
        $ip = '192.0.2.77';
        $this->clearRateKeys($email, $ip);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => $ip])
                ->from(route('login'))
                ->withSession([
                    'captcha' => ['answer' => 10, 'question' => 'What is 4 + 6?', 'expires_at' => time() + 300],
                ])
                ->post(route('login.submit'), [
                    'email' => $email,
                    'password' => 'wrong-password',
                    'captcha' => '10',
                ])
                ->assertRedirect(route('login'))
                ->assertSessionHas('danger', 'Invalid email or password.');
        }

        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->from(route('login'))
            ->withSession([
                'captcha' => ['answer' => 10, 'question' => 'What is 4 + 6?', 'expires_at' => time() + 300],
            ])
            ->post(route('login.submit'), [
                'email' => $email,
                'password' => 'Old-Password1!',
                'captcha' => '10',
            ])
            ->assertSessionHas('danger', 'Too many failed login attempts. Please try again in 15 minute(s).');

        $this->clearRateKeys($email, $ip);
        $selector = str_repeat('1', 32);
        $validator = str_repeat('2', 64);
        DB::table('remember_tokens')->insert([
            'user_id' => 1,
            'selector' => $selector,
            'validator_hash' => hash('sha256', $validator),
            'expires_at' => now()->addDays(30),
        ]);
        Auth::logout();
        $this->app['auth']->forgetGuards();

        $this->withCookie('jobportal_remember', $selector . ':' . $validator)
            ->get(route('login'))
            ->assertRedirect(route('jobseeker.dashboard'));

        $this->assertDatabaseCount('remember_tokens', 1);
        $this->assertDatabaseMissing('remember_tokens', ['selector' => $selector]);
    }

    private function clearRateKeys(string $email, string $ip): void
    {
        $cache = Cache::store('file');
        foreach ([
            'auth:email:' . hash('sha256', strtolower($email)),
            'auth:ip:' . hash('sha256', $ip),
        ] as $key) {
            $cache->forget($key . ':attempts');
            $cache->forget($key . ':expires');
        }
    }

    private function registrationPng(): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lS8AAAAASUVORK5CYII=', true);
        $this->assertNotFalse($png);

        return UploadedFile::fake()->createWithContent('registration.png', $png);
    }
}
