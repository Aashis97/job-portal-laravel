<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class JobSeekerAccountTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->increments('user_id');
            $table->unsignedInteger('role_id');
            $table->string('full_name');
            $table->string('email');
            $table->string('password');
            $table->string('phone')->nullable();
            $table->string('profile_image')->nullable();
            $table->string('account_status');
            $table->boolean('email_verified')->default(false);
            $table->unsignedInteger('session_version')->default(1);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->bigIncrements('log_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('activity');
            $table->string('module')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('device_info')->nullable();
            $table->dateTime('created_at')->nullable();
        });

        DB::table('users')->insert([
            'user_id' => 8,
            'role_id' => 3,
            'full_name' => 'Test Seeker',
            'email' => 'seeker@example.test',
            'password' => password_hash('OldPassword9!', PASSWORD_DEFAULT),
            'phone' => '9812345678',
            'profile_image' => 'default.png',
            'account_status' => 'Active',
            'email_verified' => true,
            'session_version' => 1,
            'created_at' => now()->subMonth(),
            'updated_at' => now(),
        ]);
    }

    public function test_settings_and_account_pages_show_the_existing_account_information(): void
    {
        $this->actingAsJobSeeker()
            ->get(route('jobseeker.settings'))
            ->assertOk()
            ->assertSee('Test Seeker')
            ->assertSee('seeker@example.test')
            ->assertSee('9812345678')
            ->assertSee('Change Password');

        $this->actingAsJobSeeker()
            ->get(route('jobseeker.account'))
            ->assertOk()
            ->assertSee('Account status')
            ->assertSee('Deactivate account');
    }

    public function test_password_change_checks_the_current_password_and_strength_then_updates_the_hash(): void
    {
        $this->actingAsJobSeeker()
            ->from(route('jobseeker.settings'))
            ->post(route('jobseeker.password.change'), [
                'current_password' => 'wrong-password',
                'new_password' => 'NewPassword9!',
                'confirm_password' => 'NewPassword9!',
            ])
            ->assertRedirect(route('jobseeker.settings'))
            ->assertSessionHas('danger', 'Current password is incorrect.');

        $this->assertDatabaseHas('users', ['user_id' => 8, 'session_version' => 1]);

        $this->actingAsJobSeeker()
            ->from(route('jobseeker.settings'))
            ->post(route('jobseeker.password.change'), [
                'current_password' => 'OldPassword9!',
                'new_password' => 'weak',
                'confirm_password' => 'weak',
            ])
            ->assertSessionHasErrors('new_password');

        $this->actingAsJobSeeker()
            ->post(route('jobseeker.password.change'), [
                'current_password' => 'OldPassword9!',
                'new_password' => 'NewPassword9!',
                'confirm_password' => 'NewPassword9!',
            ])
            ->assertRedirect(route('jobseeker.settings'))
            ->assertSessionHas('success', 'Your password has been changed successfully.');

        $this->assertTrue(password_verify('NewPassword9!', (string) DB::table('users')->where('user_id', 8)->value('password')));
        $this->assertDatabaseHas('users', ['user_id' => 8, 'session_version' => 2]);
        $this->assertDatabaseHas('activity_logs', ['user_id' => 8, 'activity' => 'Password changed']);
    }

    public function test_account_deactivation_requires_password_and_explicit_confirmation(): void
    {
        $this->actingAsJobSeeker()
            ->post(route('jobseeker.account.deactivate'), [
                'current_password' => 'OldPassword9!',
                'confirmation' => 'no',
            ])
            ->assertRedirect(route('jobseeker.account'))
            ->assertSessionHas('danger');

        $this->assertDatabaseHas('users', ['user_id' => 8, 'account_status' => 'Active']);

        $this->actingAsJobSeeker()
            ->post(route('jobseeker.account.deactivate'), [
                'current_password' => 'OldPassword9!',
                'confirmation' => 'DEACTIVATE',
            ])
            ->assertRedirect(route('landing'))
            ->assertSessionHas('warning', 'Your account has been deactivated. You can reactivate it by contacting support.');

        $this->assertDatabaseHas('users', ['user_id' => 8, 'account_status' => 'Inactive']);
        $this->assertDatabaseHas('activity_logs', ['user_id' => 8, 'activity' => 'Deactivated account']);
    }

    private function jobSeeker(): User
    {
        $user = new User([
            'role_id' => 3,
            'full_name' => 'Test Seeker',
            'email' => 'seeker@example.test',
            'account_status' => 'Active',
        ]);
        $user->setAttribute('user_id', 8);
        $user->exists = true;

        return $user;
    }

    private function actingAsJobSeeker()
    {
        return $this->actingAs($this->jobSeeker())->withSession(['session_version' => 1]);
    }
}
