<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmployerAccountTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->increments('user_id');
            $table->unsignedInteger('role_id');
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->string('profile_image')->default('default.png');
            $table->string('account_status');
            $table->boolean('email_verified')->default(false);
            $table->unsignedInteger('session_version')->default(1);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
        Schema::create('companies', function (Blueprint $table) {
            $table->increments('company_id');
            $table->string('company_name');
        });
        Schema::create('employers', function (Blueprint $table) {
            $table->increments('employer_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('company_id')->nullable();
            $table->string('designation')->nullable();
            $table->string('department')->nullable();
            $table->boolean('is_primary_contact')->default(false);
        });
        Schema::create('remember_tokens', function (Blueprint $table) {
            $table->increments('token_id');
            $table->unsignedInteger('user_id');
        });
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->bigIncrements('log_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('activity');
            $table->string('module')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('device_info', 255)->nullable();
            $table->dateTime('created_at')->nullable();
        });

        DB::table('users')->insert([
            'user_id' => 6,
            'role_id' => 2,
            'full_name' => 'Employer Test',
            'email' => 'employer@example.test',
            'password' => password_hash('Old-Password1!', PASSWORD_ARGON2ID),
            'phone' => '9812345678',
            'profile_image' => 'default.png',
            'account_status' => 'Active',
            'email_verified' => true,
            'session_version' => 1,
            'created_at' => now()->subMonth(),
        ]);
        DB::table('companies')->insert(['company_id' => 10, 'company_name' => 'Example Company']);
        DB::table('employers')->insert([
            'user_id' => 6,
            'company_id' => 10,
            'designation' => 'Recruiter',
            'department' => 'People',
            'is_primary_contact' => 1,
        ]);
    }

    public function test_employer_settings_update_account_details_and_require_reverification_after_email_change(): void
    {
        $this->actingAsEmployer()
            ->get(route('employer.settings'))
            ->assertOk()
            ->assertSee('Account Settings')
            ->assertSee('Example Company')
            ->assertSee('Recruiter');

        $this->actingAsEmployer()
            ->from(route('employer.settings'))
            ->post(route('employer.settings.update'), [
                'full_name' => 'Updated Employer',
                'email' => 'updated@example.test',
                'phone' => '+977 (981) 234-5678',
            ])
            ->assertRedirect(route('employer.settings'))
            ->assertSessionHas('success', 'Account information updated successfully. Your email address has been changed and requires verification again.');

        $this->assertDatabaseHas('users', [
            'user_id' => 6,
            'full_name' => 'Updated Employer',
            'email' => 'updated@example.test',
            'phone' => '+977 (981) 234-5678',
            'email_verified' => 0,
        ]);
    }

    public function test_employer_settings_reject_duplicate_email_without_modifying_profile(): void
    {
        DB::table('users')->insert([
            'user_id' => 7,
            'role_id' => 3,
            'full_name' => 'Other User',
            'email' => 'other@example.test',
            'password' => 'unused',
            'account_status' => 'Active',
        ]);

        $this->actingAsEmployer()
            ->from(route('employer.settings'))
            ->post(route('employer.settings.update'), [
                'full_name' => 'Should Not Save',
                'email' => 'other@example.test',
            ])
            ->assertRedirect(route('employer.settings'))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseHas('users', [
            'user_id' => 6,
            'full_name' => 'Employer Test',
            'email' => 'employer@example.test',
        ]);
    }

    public function test_employer_password_change_validates_current_password_and_revokes_remember_tokens(): void
    {
        DB::table('remember_tokens')->insert(['user_id' => 6]);

        $this->actingAsEmployer()
            ->get(route('employer.password.form'))
            ->assertOk()
            ->assertSee('Update Your Password');

        $this->actingAsEmployer()
            ->from(route('employer.password.form'))
            ->post(route('employer.password.update'), [
                'current_password' => 'incorrect',
                'new_password' => 'New-Password2!',
                'confirm_password' => 'New-Password2!',
            ])
            ->assertRedirect(route('employer.password.form'))
            ->assertSessionHas('danger', 'The current password you entered is incorrect.');

        $this->actingAsEmployer()
            ->from(route('employer.password.form'))
            ->post(route('employer.password.update'), [
                'current_password' => 'Old-Password1!',
                'new_password' => 'weak',
                'confirm_password' => 'weak',
            ])
            ->assertSessionHasErrors('new_password');

        $this->actingAsEmployer()
            ->post(route('employer.password.update'), [
                'current_password' => 'Old-Password1!',
                'new_password' => 'New-Password2!',
                'confirm_password' => 'New-Password2!',
            ])
            ->assertRedirect(route('employer.password.form'))
            ->assertSessionHas('success', 'Your password has been changed successfully.');

        $user = DB::table('users')->where('user_id', 6)->first();
        $this->assertTrue(password_verify('New-Password2!', $user->password));
        $this->assertSame(2, (int) $user->session_version);
        $this->assertDatabaseCount('remember_tokens', 0);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => 6,
            'activity' => 'Password changed',
            'module' => 'Authentication',
        ]);
    }

    public function test_employer_deactivation_requires_confirmation_and_password_then_logs_out(): void
    {
        DB::table('remember_tokens')->insert(['user_id' => 6]);

        $this->actingAsEmployer()
            ->get(route('employer.account'))
            ->assertOk()
            ->assertSee('Employer Test')
            ->assertSee('Deactivate account');

        $this->actingAsEmployer()
            ->from(route('employer.account'))
            ->post(route('employer.account.deactivate'), [
                'current_password' => 'Old-Password1!',
                'confirmation' => 'no',
            ])
            ->assertRedirect(route('employer.account'))
            ->assertSessionHas('danger', 'Please enter your current password and confirm by typing DEACTIVATE.');
        $this->assertDatabaseHas('users', ['user_id' => 6, 'account_status' => 'Active']);

        $this->actingAsEmployer()
            ->post(route('employer.account.deactivate'), [
                'current_password' => 'Old-Password1!',
                'confirmation' => 'deactivate',
            ])
            ->assertRedirect(route('landing'))
            ->assertSessionHas('warning', 'Your account has been deactivated. You can reactivate it by contacting support.');

        $this->assertDatabaseHas('users', ['user_id' => 6, 'account_status' => 'Inactive']);
        $this->assertDatabaseCount('remember_tokens', 0);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => 6,
            'activity' => 'Deactivated account',
            'module' => 'Account',
        ]);
    }

    private function actingAsEmployer()
    {
        return $this->actingAs($this->employer())->withSession(['session_version' => 1]);
    }

    private function employer(): User
    {
        $user = new User([
            'role_id' => 2,
            'full_name' => 'Employer Test',
            'email' => 'employer@example.test',
            'account_status' => 'Active',
        ]);
        $user->setAttribute('user_id', 6);
        $user->exists = true;

        return $user;
    }
}
