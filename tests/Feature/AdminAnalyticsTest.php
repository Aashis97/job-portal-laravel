<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminAnalyticsTest extends TestCase
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
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('categories', function (Blueprint $table) {
            $table->increments('category_id');
            $table->string('category_name');
        });
        Schema::create('jobs', function (Blueprint $table) {
            $table->increments('job_id');
            $table->unsignedInteger('company_id');
            $table->unsignedInteger('category_id');
            $table->timestamp('created_at')->nullable();
            $table->string('job_status');
        });
        Schema::create('applications', function (Blueprint $table) {
            $table->increments('application_id');
            $table->unsignedInteger('job_id');
            $table->timestamp('applied_at')->nullable();
            $table->string('application_status');
        });
        Schema::create('skills', function (Blueprint $table) {
            $table->increments('skill_id');
            $table->string('skill_name');
        });
        Schema::create('job_skills', function (Blueprint $table) {
            $table->unsignedInteger('job_id');
            $table->unsignedInteger('skill_id');
        });
        Schema::create('interviews', function (Blueprint $table) {
            $table->increments('interview_id');
            $table->string('interview_status');
        });

        DB::table('users')->insert([
            ['user_id' => 1, 'role_id' => 1, 'full_name' => 'Admin One', 'email' => 'admin@example.test', 'password' => 'unused', 'account_status' => 'Active', 'created_at' => now()],
            ['user_id' => 2, 'role_id' => 2, 'full_name' => 'Employer One', 'email' => 'employer@example.test', 'password' => 'unused', 'account_status' => 'Active', 'created_at' => now()],
            ['user_id' => 3, 'role_id' => 3, 'full_name' => 'Seeker One', 'email' => 'seeker@example.test', 'password' => 'unused', 'account_status' => 'Active', 'created_at' => now()],
            ['user_id' => 4, 'role_id' => 3, 'full_name' => 'Pending Seeker', 'email' => 'pending@example.test', 'password' => 'unused', 'account_status' => 'Pending', 'created_at' => now()->subYears(2)],
        ]);
        DB::table('companies')->insert([
            ['company_id' => 1, 'company_name' => 'Top Company', 'status' => 'Approved', 'created_at' => now()],
            ['company_id' => 2, 'company_name' => 'Pending Company', 'status' => 'Pending', 'created_at' => now()],
        ]);
        DB::table('categories')->insert(['category_id' => 1, 'category_name' => 'Engineering']);
        DB::table('jobs')->insert([
            ['job_id' => 1, 'company_id' => 1, 'category_id' => 1, 'created_at' => now(), 'job_status' => 'Open'],
            ['job_id' => 2, 'company_id' => 1, 'category_id' => 1, 'created_at' => now(), 'job_status' => 'Draft'],
        ]);
        DB::table('applications')->insert([
            ['application_id' => 1, 'job_id' => 1, 'applied_at' => now(), 'application_status' => 'Selected'],
            ['application_id' => 2, 'job_id' => 1, 'applied_at' => now(), 'application_status' => 'Applied'],
        ]);
        DB::table('skills')->insert(['skill_id' => 1, 'skill_name' => 'Laravel']);
        DB::table('job_skills')->insert(['job_id' => 1, 'skill_id' => 1]);
        DB::table('interviews')->insert([
            ['interview_id' => 1, 'interview_status' => 'Scheduled'],
            ['interview_id' => 2, 'interview_status' => 'Completed'],
        ]);
    }

    public function test_admin_analytics_filters_period_and_reports_platform_metrics_and_charts(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.analytics', ['period' => '7d']));

        $response->assertOk()
            ->assertSee('Platform Analytics')
            ->assertSee('New Users')
            ->assertSee('Top Company')
            ->assertSee('Interview Activity')
            ->assertSee('Last 7 days');

        preg_match('/window\.adminAnalytics = (.*?);<\/script>/s', $response->getContent(), $matches);
        $charts = json_decode($matches[1] ?? '', true);
        $this->assertIsArray($charts);
        $this->assertSame(['Administrators', 'Employers', 'Job Seekers'], $charts['roleDistribution']['labels']);
        $this->assertSame([1, 1, 2], $charts['roleDistribution']['values']);
        $this->assertSame(['Engineering'], $charts['jobsByCategory']['labels']);
        $this->assertSame([2], $charts['jobsByCategory']['values']);
        $this->assertSame(['Laravel'], $charts['topSkills']['labels']);
        $this->assertSame([1], $charts['topSkills']['values']);
        $this->assertSame(7, count($charts['signups']['labels']));
        $this->assertSame(3, $charts['signups']['values'][6]);

        $response->assertSee('50.0% hiring rate');
        $response->assertSee('1 users · 1 companies');
    }

    public function test_invalid_analytics_period_defaults_to_thirty_days(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.analytics', ['period' => 'invalid']));

        $response->assertOk()
            ->assertSee('Last 30 days')
            ->assertDontSee('aria-selected="true">All time');

        preg_match('/window\.adminAnalytics = (.*?);<\/script>/s', $response->getContent(), $matches);
        $charts = json_decode($matches[1] ?? '', true);
        $this->assertSame(30, count($charts['signups']['labels']));
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
