<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmployerAnalyticsTest extends TestCase
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
        Schema::create('companies', function (Blueprint $table) {
            $table->increments('company_id');
            $table->string('company_name');
        });
        Schema::create('employers', function (Blueprint $table) {
            $table->increments('employer_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('company_id');
        });
        Schema::create('jobs', function (Blueprint $table) {
            $table->increments('job_id');
            $table->unsignedInteger('employer_id');
            $table->unsignedInteger('company_id');
            $table->string('job_title');
            $table->string('job_status');
        });
        Schema::create('applications', function (Blueprint $table) {
            $table->increments('application_id');
            $table->unsignedInteger('job_id');
            $table->unsignedInteger('user_id');
            $table->string('application_status');
            $table->decimal('skill_match_score', 5, 2)->nullable();
            $table->unsignedInteger('ai_rank')->nullable();
        });
        Schema::create('interviews', function (Blueprint $table) {
            $table->increments('interview_id');
            $table->unsignedInteger('application_id');
            $table->string('interview_status');
        });
        Schema::create('skills', function (Blueprint $table) {
            $table->increments('skill_id');
            $table->string('skill_name');
        });
        Schema::create('job_skills', function (Blueprint $table) {
            $table->increments('job_skill_id');
            $table->unsignedInteger('job_id');
            $table->unsignedInteger('skill_id');
        });

        DB::table('users')->insert([
            ['user_id' => 6, 'role_id' => 2, 'full_name' => 'Employer One', 'email' => 'one@example.test', 'password' => 'unused', 'account_status' => 'Active'],
            ['user_id' => 7, 'role_id' => 2, 'full_name' => 'Employer Two', 'email' => 'two@example.test', 'password' => 'unused', 'account_status' => 'Active'],
            ['user_id' => 20, 'role_id' => 3, 'full_name' => 'Candidate One', 'email' => 'candidate@example.test', 'password' => 'unused', 'account_status' => 'Active'],
            ['user_id' => 21, 'role_id' => 3, 'full_name' => 'Candidate Two', 'email' => 'candidate2@example.test', 'password' => 'unused', 'account_status' => 'Active'],
        ]);
        DB::table('companies')->insert([
            ['company_id' => 10, 'company_name' => 'First Company'],
            ['company_id' => 11, 'company_name' => 'Second Company'],
        ]);
        DB::table('employers')->insert([
            ['employer_id' => 3, 'user_id' => 6, 'company_id' => 10],
            ['employer_id' => 4, 'user_id' => 7, 'company_id' => 11],
        ]);
        DB::table('jobs')->insert([
            ['job_id' => 100, 'employer_id' => 3, 'company_id' => 10, 'job_title' => 'Laravel Engineer', 'job_status' => 'Open'],
            ['job_id' => 101, 'employer_id' => 4, 'company_id' => 11, 'job_title' => 'Private Job', 'job_status' => 'Open'],
        ]);
        DB::table('applications')->insert([
            ['application_id' => 200, 'job_id' => 100, 'user_id' => 20, 'application_status' => 'Selected', 'skill_match_score' => 90, 'ai_rank' => 1],
            ['application_id' => 201, 'job_id' => 100, 'user_id' => 21, 'application_status' => 'Shortlisted', 'skill_match_score' => 66.5, 'ai_rank' => 2],
            ['application_id' => 202, 'job_id' => 101, 'user_id' => 20, 'application_status' => 'Rejected', 'skill_match_score' => 10, 'ai_rank' => null],
        ]);
        DB::table('interviews')->insert([
            ['interview_id' => 300, 'application_id' => 200, 'interview_status' => 'Completed'],
            ['interview_id' => 301, 'application_id' => 201, 'interview_status' => 'Scheduled'],
            ['interview_id' => 302, 'application_id' => 202, 'interview_status' => 'Cancelled'],
        ]);
        DB::table('skills')->insert(['skill_id' => 1, 'skill_name' => 'Laravel']);
        DB::table('job_skills')->insert(['job_id' => 100, 'skill_id' => 1]);
    }

    public function test_analytics_shows_aggregates_and_only_the_employers_data(): void
    {
        $this->actingAs($this->employer())
            ->get(route('employer.analytics'))
            ->assertOk()
            ->assertSee('First Company')
            ->assertSee('Laravel Engineer')
            ->assertSee('Candidate One')
            ->assertSee('Candidate Two')
            ->assertDontSee('Private Job')
            ->assertDontSee('Second Company')
            ->assertSee('2 unique candidates')
            ->assertSee('78.3%')
            ->assertSee('50.0%')
            ->assertSee('Most requested skills');
    }

    public function test_employer_without_profile_cannot_view_analytics(): void
    {
        DB::table('employers')->where('user_id', 6)->delete();

        $this->actingAs($this->employer())
            ->get(route('employer.analytics'))
            ->assertForbidden();
    }

    private function employer(): User
    {
        $user = new User([
            'role_id' => 2,
            'full_name' => 'Employer One',
            'email' => 'one@example.test',
            'account_status' => 'Active',
        ]);
        $user->setAttribute('user_id', 6);
        $user->exists = true;

        return $user;
    }
}
