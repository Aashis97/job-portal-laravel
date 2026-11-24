<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class JobSeekerDashboardTest extends TestCase
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
        Schema::create('job_seekers', function (Blueprint $table) {
            $table->increments('seeker_id');
            $table->unsignedInteger('user_id')->unique();
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('education_level')->nullable();
            $table->decimal('years_of_experience', 4, 1)->nullable();
            $table->string('current_job_title')->nullable();
            $table->decimal('expected_salary', 12, 2)->nullable();
            $table->text('profile_summary')->nullable();
            $table->string('availability')->nullable();
            $table->string('resume_file')->nullable();
        });
        Schema::create('companies', function (Blueprint $table) {
            $table->increments('company_id');
            $table->string('company_name');
        });
        Schema::create('jobs', function (Blueprint $table) {
            $table->increments('job_id');
            $table->unsignedInteger('company_id')->nullable();
            $table->string('job_title');
            $table->string('employment_type');
            $table->string('workplace');
            $table->string('experience_level');
            $table->string('job_status');
            $table->dateTime('created_at')->nullable();
        });
        Schema::create('applications', function (Blueprint $table) {
            $table->increments('application_id');
            $table->unsignedInteger('job_id');
            $table->unsignedInteger('user_id');
            $table->string('application_status');
            $table->decimal('skill_match_score', 5, 2)->nullable();
            $table->dateTime('applied_at')->nullable();
        });
        Schema::create('saved_jobs', function (Blueprint $table) {
            $table->increments('saved_job_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('job_id');
        });
        Schema::create('resume', function (Blueprint $table) {
            $table->increments('resume_id');
            $table->unsignedInteger('user_id')->unique();
            $table->string('resume_title')->nullable();
            $table->string('resume_file');
            $table->dateTime('updated_at')->nullable();
        });

        DB::table('users')->insert([
            'user_id' => 8,
            'role_id' => 3,
            'full_name' => 'Test Seeker',
            'email' => 'seeker@example.test',
            'password' => 'unused',
            'account_status' => 'Active',
        ]);
    }

    public function test_dashboard_renders_original_statistics_recent_activity_and_profile_resume_status(): void
    {
        DB::table('job_seekers')->insert([
            'user_id' => 8,
            'city' => 'Kathmandu',
            'education_level' => 'Bachelor',
            'years_of_experience' => 0,
            'availability' => 'Immediately',
            'resume_file' => 'resume_8.pdf',
        ]);
        DB::table('companies')->insert(['company_id' => 4, 'company_name' => 'Example Company']);
        DB::table('jobs')->insert([
            [
                'job_id' => 20,
                'company_id' => 4,
                'job_title' => 'Senior Engineer',
                'employment_type' => 'Full-Time',
                'workplace' => 'Hybrid',
                'experience_level' => 'Senior',
                'job_status' => 'Closed',
                'created_at' => now()->subDay(),
            ],
            [
                'job_id' => 21,
                'company_id' => 4,
                'job_title' => 'Latest Open Role',
                'employment_type' => 'Contract',
                'workplace' => 'Remote',
                'experience_level' => 'Mid',
                'job_status' => 'Open',
                'created_at' => now(),
            ],
        ]);
        DB::table('applications')->insert([
            [
                'application_id' => 1,
                'job_id' => 20,
                'user_id' => 8,
                'application_status' => 'Shortlisted',
                'skill_match_score' => 85,
                'applied_at' => now()->subDay(),
            ],
            [
                'application_id' => 2,
                'job_id' => 20,
                'user_id' => 8,
                'application_status' => 'Interview Scheduled',
                'skill_match_score' => 92,
                'applied_at' => now(),
            ],
        ]);
        DB::table('saved_jobs')->insert(['user_id' => 8, 'job_id' => 21]);
        DB::table('resume')->insert([
            'user_id' => 8,
            'resume_title' => 'My Engineering Resume',
            'resume_file' => 'resume_8.pdf',
            'updated_at' => now()->subHour(),
        ]);

        $this->actingAs($this->jobSeeker())
            ->get(route('jobseeker.dashboard'))
            ->assertOk()
            ->assertSee('Welcome back, Test Seeker!')
            ->assertSee('Applications')
            ->assertSee('Shortlisted')
            ->assertSee('Interviews')
            ->assertSee('My Engineering Resume')
            ->assertSee('36%')
            ->assertSee('Senior Engineer')
            ->assertSee('Latest Open Role')
            ->assertDontSee('Closed Job Not Listed');
    }

    public function test_dashboard_does_not_create_a_profile_when_one_is_missing(): void
    {
        $this->actingAs($this->jobSeeker())
            ->get(route('jobseeker.dashboard'))
            ->assertOk()
            ->assertSee('0%')
            ->assertSee('No resume uploaded');

        $this->assertDatabaseCount('job_seekers', 0);
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
}
