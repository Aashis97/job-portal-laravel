<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
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
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('companies', function (Blueprint $table) {
            $table->increments('company_id');
            $table->string('company_name');
            $table->string('status');
        });
        Schema::create('jobs', function (Blueprint $table) {
            $table->increments('job_id');
            $table->unsignedInteger('company_id');
            $table->string('job_title');
            $table->string('employment_type');
            $table->string('workplace');
            $table->string('job_status');
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('applications', function (Blueprint $table) {
            $table->increments('application_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('job_id');
            $table->string('application_status');
            $table->decimal('skill_match_score', 5, 2)->nullable();
            $table->timestamp('applied_at')->nullable();
        });

        DB::table('users')->insert([
            ['user_id' => 1, 'role_id' => 1, 'full_name' => 'Admin One', 'email' => 'admin@example.test', 'password' => 'unused', 'account_status' => 'Active', 'created_at' => now()],
            ['user_id' => 2, 'role_id' => 2, 'full_name' => 'Employer One', 'email' => 'employer@example.test', 'password' => 'unused', 'account_status' => 'Pending', 'created_at' => now()],
            ['user_id' => 3, 'role_id' => 3, 'full_name' => 'Candidate One', 'email' => 'candidate@example.test', 'password' => 'unused', 'account_status' => 'Blocked', 'created_at' => now()],
        ]);
        DB::table('companies')->insert([
            ['company_id' => 10, 'company_name' => 'Pending Company', 'status' => 'Pending'],
        ]);
        DB::table('jobs')->insert([
            ['job_id' => 100, 'company_id' => 10, 'job_title' => 'PHP Engineer', 'employment_type' => 'Full-Time', 'workplace' => 'Remote', 'job_status' => 'Open', 'created_at' => now()],
            ['job_id' => 101, 'company_id' => 10, 'job_title' => 'Laravel Engineer', 'employment_type' => 'Contract', 'workplace' => 'Hybrid', 'job_status' => 'Draft', 'created_at' => now()],
        ]);
        DB::table('applications')->insert([
            ['application_id' => 200, 'user_id' => 3, 'job_id' => 100, 'application_status' => 'Applied', 'skill_match_score' => 88.5, 'applied_at' => now()],
        ]);
    }

    public function test_admin_dashboard_shows_platform_statistics_alerts_and_recent_activity(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Total Users')
            ->assertSee('3')
            ->assertSee('Employers')
            ->assertSee('Job Seekers')
            ->assertSee('pending company verification')
            ->assertSee('user awaiting activation')
            ->assertSee('blocked account')
            ->assertSee('Pending Company')
            ->assertSee('PHP Engineer')
            ->assertSee('Candidate One')
            ->assertSee('88.5%');
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
