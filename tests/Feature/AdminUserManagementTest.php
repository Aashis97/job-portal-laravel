<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->increments('user_id');
            $table->unsignedInteger('role_id');
            $table->string('full_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('password');
            $table->string('account_status');
            $table->boolean('email_verified')->default(false);
            $table->string('profile_image')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('last_login')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unsignedInteger('session_version')->default(1);
            $table->unsignedInteger('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
        });
        Schema::create('companies', function (Blueprint $table) {
            $table->increments('company_id');
            $table->string('company_name');
            $table->string('company_email')->nullable();
            $table->string('company_phone')->nullable();
            $table->string('status');
        });
        Schema::create('employers', function (Blueprint $table) {
            $table->increments('employer_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('company_id')->nullable();
            $table->string('designation')->nullable();
            $table->boolean('is_primary_contact')->default(true);
        });
        Schema::create('job_seekers', function (Blueprint $table) {
            $table->increments('seeker_id');
            $table->unsignedInteger('user_id');
            $table->decimal('years_of_experience', 4, 1)->nullable();
            $table->boolean('profile_completed')->default(false);
            $table->string('availability')->nullable();
        });
        Schema::create('remember_tokens', function (Blueprint $table) {
            $table->increments('token_id');
            $table->unsignedInteger('user_id');
        });
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->increments('reset_id');
            $table->unsignedInteger('user_id');
        });
        Schema::create('email_verifications', function (Blueprint $table) {
            $table->increments('verification_id');
            $table->unsignedInteger('user_id');
        });
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->bigIncrements('log_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('activity');
            $table->string('module')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('device_info')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        DB::table('users')->insert([
            ['user_id' => 1, 'role_id' => 1, 'full_name' => 'Admin One', 'email' => 'admin@example.test', 'phone' => null, 'password' => 'unused', 'account_status' => 'Active', 'email_verified' => true, 'profile_image' => null, 'created_at' => now(), 'last_login' => null],
            ['user_id' => 2, 'role_id' => 2, 'full_name' => 'Employer One', 'email' => 'employer@example.test', 'phone' => '555-0100', 'password' => 'unused', 'account_status' => 'Pending', 'email_verified' => false, 'profile_image' => null, 'created_at' => now(), 'last_login' => null],
            ['user_id' => 3, 'role_id' => 3, 'full_name' => 'Candidate One', 'email' => 'candidate@example.test', 'phone' => '555-0101', 'password' => 'unused', 'account_status' => 'Active', 'email_verified' => true, 'profile_image' => null, 'created_at' => now(), 'last_login' => null],
        ]);
        DB::table('companies')->insert(['company_id' => 10, 'company_name' => 'Example Company', 'status' => 'Approved']);
        DB::table('employers')->insert(['employer_id' => 20, 'user_id' => 2, 'company_id' => 10]);
        DB::table('job_seekers')->insert(['seeker_id' => 30, 'user_id' => 3, 'years_of_experience' => 4.5]);
    }

    public function test_admin_user_list_filters_by_role_status_verification_and_search(): void
    {
        $this->actingAsAdmin()
            ->get(route('admin.users', ['role' => 2, 'status' => 'Pending', 'verified' => '0', 'q' => 'Example']))
            ->assertOk()
            ->assertSee('Employer One')
            ->assertSee('Example Company')
            ->assertDontSee('Candidate One')
            ->assertSee('unverified email');
    }

    public function test_admin_can_view_role_specific_user_details(): void
    {
        $this->actingAsAdmin()
            ->get(route('admin.user-details', ['id' => 3]))
            ->assertOk()
            ->assertSee('Candidate One')
            ->assertSee('4.5 years')
            ->assertSee('Email verification')
            ->assertSee('Block user')
            ->assertSee('Apply role change');
    }

    public function test_admin_user_actions_update_status_verify_email_and_revoke_sessions(): void
    {
        DB::table('remember_tokens')->insert(['user_id' => 3]);
        $this->actingAsAdmin()
            ->post(route('admin.user-action'), ['user_id' => 3, 'action' => 'block'])
            ->assertRedirect(route('admin.user-details', ['id' => 3]))
            ->assertSessionHas('success', 'User blocked. All their active sessions will be terminated on their next request.');
        $this->assertDatabaseHas('users', ['user_id' => 3, 'account_status' => 'Blocked', 'session_version' => 2]);
        $this->assertDatabaseMissing('remember_tokens', ['user_id' => 3]);

        $this->actingAsAdmin()
            ->post(route('admin.user-action'), ['user_id' => 3, 'action' => 'activate'])
            ->assertSessionHas('success', 'User activated successfully.');
        $this->assertDatabaseHas('users', ['user_id' => 3, 'account_status' => 'Active']);

        $this->actingAsAdmin()
            ->post(route('admin.user-action'), ['user_id' => 3, 'action' => 'verify_email'])
            ->assertSessionHas('success', 'Email marked as verified.');
        $this->assertDatabaseHas('users', ['user_id' => 3, 'email_verified' => 1]);

        $this->actingAsAdmin()
            ->post(route('admin.user-action'), ['user_id' => 3, 'action' => 'force_logout'])
            ->assertSessionHas('success', 'User will be logged out of every device.');
        $this->assertDatabaseHas('users', ['user_id' => 3, 'session_version' => 3]);
        $this->assertDatabaseHas('activity_logs', ['user_id' => 1, 'module' => 'Admin']);
    }

    public function test_role_change_creates_role_profile_and_protects_self_and_last_admin(): void
    {
        $this->actingAsAdmin()
            ->post(route('admin.user-action'), ['user_id' => 3, 'action' => 'change_role', 'role_id' => 2])
            ->assertSessionHas('success', 'User role updated. They will need to log in again.');
        $this->assertDatabaseHas('users', ['user_id' => 3, 'role_id' => 2, 'session_version' => 2]);
        $this->assertDatabaseHas('companies', ['company_name' => "Candidate One's Company", 'status' => 'Pending']);
        $this->assertDatabaseHas('employers', ['user_id' => 3]);

        $this->actingAsAdmin()
            ->post(route('admin.user-action'), ['user_id' => 2, 'action' => 'change_role', 'role_id' => 3])
            ->assertSessionHas('success');
        $this->assertDatabaseHas('job_seekers', ['user_id' => 2, 'availability' => 'Immediately']);

        $this->actingAsAdmin()
            ->post(route('admin.user-action'), ['user_id' => 1, 'action' => 'block'])
            ->assertSessionHas('danger', 'You cannot perform this action on your own account.');

        DB::table('users')->insert([
            'user_id' => 4,
            'role_id' => 1,
            'full_name' => 'Last Admin',
            'email' => 'lastadmin@example.test',
            'password' => 'unused',
            'account_status' => 'Active',
        ]);
        DB::table('users')->where('user_id', 1)->update(['account_status' => 'Inactive']);
        $this->actingAsAdmin()
            ->post(route('admin.user-action'), ['user_id' => 4, 'action' => 'suspend'])
            ->assertSessionHas('danger', 'Cannot modify the only remaining active administrator.');
        $this->assertDatabaseHas('users', ['user_id' => 4, 'account_status' => 'Active']);
    }

    public function test_user_deletion_requires_exact_confirmation_and_cleans_auth_records(): void
    {
        DB::table('remember_tokens')->insert(['user_id' => 3]);
        DB::table('password_reset_tokens')->insert(['user_id' => 3]);
        DB::table('email_verifications')->insert(['user_id' => 3]);

        $this->actingAsAdmin()
            ->post(route('admin.user-action'), ['user_id' => 3, 'action' => 'delete', 'delete_confirmation' => 'Wrong Name'])
            ->assertSessionHas('danger', 'Type the user’s full name exactly to confirm deletion.');
        $this->assertDatabaseHas('users', ['user_id' => 3]);

        $this->actingAs($this->admin())
            ->post(route('admin.user-action'), ['user_id' => 3, 'action' => 'delete', 'delete_confirmation' => 'Candidate One'])
            ->assertRedirect(route('admin.users'))
            ->assertSessionHas('success', 'User deleted permanently.');
        $this->assertDatabaseMissing('users', ['user_id' => 3]);
        $this->assertDatabaseMissing('remember_tokens', ['user_id' => 3]);
        $this->assertDatabaseMissing('password_reset_tokens', ['user_id' => 3]);
        $this->assertDatabaseMissing('email_verifications', ['user_id' => 3]);
    }

    private function admin(): User
    {
        $user = new User([
            'role_id' => 1,
            'full_name' => 'Admin One',
            'email' => 'admin@example.test',
            'account_status' => 'Active',
        ]);
        $user->setAttribute('user_id', 1);
        $user->exists = true;

        return $user;
    }

    private function actingAsAdmin()
    {
        return $this->actingAs($this->admin())->withSession(['session_version' => 1]);
    }
}
