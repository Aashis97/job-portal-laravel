<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminAuditLogTest extends TestCase
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
            $table->string('account_status');
        });
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->bigIncrements('log_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('activity');
            $table->string('module')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('device_info')->nullable();
            $table->string('browser', 100)->nullable();
            $table->string('operating_system', 100)->nullable();
            $table->timestamp('created_at')->nullable();
        });
        DB::table('users')->insert([
            ['user_id' => 1, 'role_id' => 1, 'full_name' => 'Admin One', 'email' => 'admin@example.test', 'password' => 'unused', 'account_status' => 'Active'],
            ['user_id' => 2, 'role_id' => 3, 'full_name' => 'Reporter Candidate', 'email' => 'reporter@example.test', 'password' => 'unused', 'account_status' => 'Active'],
        ]);
        DB::table('activity_logs')->insert([
            ['log_id' => 10, 'user_id' => 2, 'activity' => 'Sent message to user', 'module' => 'Jobseeker', 'ip_address' => '10.0.0.1', 'browser' => 'Chrome', 'operating_system' => 'Windows', 'created_at' => now()],
            ['log_id' => 11, 'user_id' => 2, 'activity' => 'Updated profile', 'module' => 'Jobseeker', 'ip_address' => '10.0.0.2', 'browser' => 'Firefox', 'operating_system' => 'Linux', 'created_at' => now()->subHours(6)],
            ['log_id' => 12, 'user_id' => null, 'activity' => 'Scheduled cleanup', 'module' => 'System', 'ip_address' => null, 'browser' => null, 'operating_system' => null, 'created_at' => now()->subDays(10)],
        ]);
    }

    public function test_admin_can_filter_audit_log_and_view_activity_statistics(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.audit-log', [
                'user_id' => 2,
                'module' => 'Jobseeker',
                'date_from' => now()->toDateString(),
                'date_to' => now()->toDateString(),
                'q' => 'Chrome',
            ]))
            ->assertOk()
            ->assertSee('Sent message to user')
            ->assertSee('Reporter Candidate')
            ->assertSee('10.0.0.1')
            ->assertDontSee('Updated profile')
            ->assertSee('Total events')
            ->assertSee('Last 24 hours');

        $this->actingAs($this->admin())
            ->get(route('admin.audit-log', ['q' => 'Reporter']))
            ->assertOk()
            ->assertSee('Sent message to user')
            ->assertSee('Updated profile');
    }

    public function test_audit_log_paginates_and_remains_admin_only(): void
    {
        $entries = [];
        for ($index = 1; $index <= 51; $index++) {
            $entries[] = [
                'user_id' => 2,
                'activity' => 'Employer activity ' . $index,
                'module' => 'Employer',
                'ip_address' => null,
                'device_info' => null,
                'browser' => null,
                'operating_system' => null,
                'created_at' => now(),
            ];
        }
        DB::table('activity_logs')->insert($entries);

        $this->actingAs($this->admin())
            ->get(route('admin.audit-log', ['module' => 'Employer', 'page' => 2]))
            ->assertOk()
            ->assertSee('Employer activity 1')
            ->assertDontSee('Employer activity 2');

        $candidate = new User([
            'role_id' => 3,
            'full_name' => 'Reporter Candidate',
            'email' => 'reporter@example.test',
            'account_status' => 'Active',
        ]);
        $candidate->setAttribute('user_id', 2);
        $candidate->exists = true;

        $this->actingAs($candidate)->get(route('admin.audit-log'))->assertForbidden();
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
}
