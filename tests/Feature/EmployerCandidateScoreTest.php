<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmployerCandidateScoreTest extends TestCase
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
            $table->string('status');
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
        Schema::create('job_seekers', function (Blueprint $table) {
            $table->increments('seeker_id');
            $table->unsignedInteger('user_id');
            $table->string('resume_file')->nullable();
            $table->boolean('profile_completed')->default(false);
            $table->string('availability')->nullable();
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
        Schema::create('user_skills', function (Blueprint $table) {
            $table->increments('user_skill_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('skill_id');
        });

        DB::table('users')->insert([
            ['user_id' => 6, 'role_id' => 2, 'full_name' => 'Employer One', 'email' => 'employer@example.test', 'password' => 'unused', 'account_status' => 'Active'],
            ['user_id' => 20, 'role_id' => 3, 'full_name' => 'Candidate One', 'email' => 'candidate@example.test', 'password' => 'unused', 'account_status' => 'Active'],
        ]);
        DB::table('companies')->insert([
            ['company_id' => 10, 'company_name' => 'First Company', 'status' => 'Approved'],
            ['company_id' => 11, 'company_name' => 'Second Company', 'status' => 'Approved'],
        ]);
        DB::table('employers')->insert(['employer_id' => 3, 'user_id' => 6, 'company_id' => 10]);
        DB::table('jobs')->insert([
            ['job_id' => 100, 'employer_id' => 3, 'company_id' => 10, 'job_title' => 'PHP Engineer', 'job_status' => 'Open'],
            ['job_id' => 101, 'employer_id' => 4, 'company_id' => 11, 'job_title' => 'Private Job', 'job_status' => 'Open'],
        ]);
        DB::table('applications')->insert([
            ['application_id' => 200, 'job_id' => 100, 'user_id' => 20, 'application_status' => 'Applied'],
            ['application_id' => 201, 'job_id' => 101, 'user_id' => 20, 'application_status' => 'Applied'],
            ['application_id' => 202, 'job_id' => 100, 'user_id' => 20, 'application_status' => 'Rejected'],
        ]);
        DB::table('job_seekers')->insert([
            'user_id' => 20,
            'resume_file' => 'candidate.pdf',
            'profile_completed' => true,
            'availability' => 'Immediately',
        ]);
        DB::table('skills')->insert([
            ['skill_id' => 1, 'skill_name' => 'PHP'],
            ['skill_id' => 2, 'skill_name' => 'Laravel'],
        ]);
        DB::table('job_skills')->insert([
            ['job_id' => 100, 'skill_id' => 1],
            ['job_id' => 100, 'skill_id' => 2],
        ]);
        DB::table('user_skills')->insert(['user_id' => 20, 'skill_id' => 1]);
    }

    public function test_score_page_uses_weighted_legacy_formula_and_updates_skill_match(): void
    {
        $this->actingAs($this->employer())
            ->get(route('employer.candidate-score', ['application_id' => 200]))
            ->assertOk()
            ->assertSee('65%')
            ->assertSee('Matched Skills')
            ->assertSee('Laravel');

        $this->assertDatabaseHas('applications', [
            'application_id' => 200,
            'skill_match_score' => 50,
        ]);
    }

    public function test_score_page_rejects_other_employers_and_closed_applications(): void
    {
        $this->actingAs($this->employer())
            ->get(route('employer.candidate-score', ['application_id' => 201]))
            ->assertNotFound();

        $this->actingAs($this->employer())
            ->get(route('employer.candidate-score', ['application_id' => 202]))
            ->assertRedirect(route('employer.applicant-details', ['application_id' => 202]))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('applications', [
            'application_id' => 202,
            'skill_match_score' => null,
        ]);
    }

    private function employer(): User
    {
        $user = new User([
            'role_id' => 2,
            'full_name' => 'Employer One',
            'email' => 'employer@example.test',
            'account_status' => 'Active',
        ]);
        $user->setAttribute('user_id', 6);
        $user->exists = true;

        return $user;
    }
}
