<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmployerCandidateToolsTest extends TestCase
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
            $table->string('profile_image')->nullable();
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
            $table->unsignedInteger('category_id');
            $table->string('job_title');
            $table->string('job_status');
            $table->string('experience_level')->default('Mid');
        });
        Schema::create('categories', function (Blueprint $table) {
            $table->increments('category_id');
            $table->string('category_name');
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
            ['user_id' => 20, 'role_id' => 3, 'full_name' => 'Candidate One', 'email' => 'one@example.test', 'password' => 'unused', 'account_status' => 'Active'],
            ['user_id' => 21, 'role_id' => 3, 'full_name' => 'Candidate Two', 'email' => 'two@example.test', 'password' => 'unused', 'account_status' => 'Active'],
        ]);
        DB::table('companies')->insert([
            ['company_id' => 10, 'company_name' => 'First Company', 'status' => 'Approved'],
            ['company_id' => 11, 'company_name' => 'Second Company', 'status' => 'Approved'],
        ]);
        DB::table('employers')->insert(['employer_id' => 3, 'user_id' => 6, 'company_id' => 10]);
        DB::table('categories')->insert(['category_id' => 1, 'category_name' => 'Technology']);
        DB::table('jobs')->insert([
            ['job_id' => 100, 'employer_id' => 3, 'company_id' => 10, 'category_id' => 1, 'job_title' => 'PHP Engineer', 'job_status' => 'Open'],
            ['job_id' => 101, 'employer_id' => 4, 'company_id' => 11, 'category_id' => 1, 'job_title' => 'Private Job', 'job_status' => 'Open'],
        ]);
        DB::table('applications')->insert([
            ['application_id' => 200, 'job_id' => 100, 'user_id' => 20, 'application_status' => 'Applied', 'skill_match_score' => 50],
            ['application_id' => 201, 'job_id' => 100, 'user_id' => 21, 'application_status' => 'Under Review', 'skill_match_score' => 100],
            ['application_id' => 202, 'job_id' => 100, 'user_id' => 21, 'application_status' => 'Rejected', 'skill_match_score' => 99],
            ['application_id' => 203, 'job_id' => 101, 'user_id' => 21, 'application_status' => 'Applied', 'skill_match_score' => 99],
        ]);
        DB::table('job_seekers')->insert([
            ['user_id' => 20, 'resume_file' => 'candidate-one.pdf', 'profile_completed' => true, 'availability' => 'Immediately'],
            ['user_id' => 21, 'resume_file' => null, 'profile_completed' => false, 'availability' => '30 Days'],
        ]);
        DB::table('skills')->insert([
            ['skill_id' => 1, 'skill_name' => 'PHP'],
            ['skill_id' => 2, 'skill_name' => 'Laravel'],
        ]);
        DB::table('job_skills')->insert([
            ['job_id' => 100, 'skill_id' => 1],
            ['job_id' => 100, 'skill_id' => 2],
        ]);
        DB::table('user_skills')->insert([
            ['user_id' => 20, 'skill_id' => 1],
            ['user_id' => 21, 'skill_id' => 1],
            ['user_id' => 21, 'skill_id' => 2],
        ]);
    }

    public function test_ranking_only_updates_eligible_applications_for_the_owned_job(): void
    {
        $this->actingAs($this->employer())
            ->get(route('employer.candidate-ranking'))
            ->assertOk()
            ->assertSee('PHP Engineer')
            ->assertSee('2 eligible candidates')
            ->assertDontSee('Private Job');

        $this->actingAs($this->employer())
            ->post(route('employer.candidate-ranking.run'), ['job_id' => 100])
            ->assertRedirect(route('employer.recommended-candidates', ['job_id' => 100]))
            ->assertSessionHas('success', '2 candidates ranked for PHP Engineer.');

        $this->assertDatabaseHas('applications', [
            'application_id' => 200,
            'skill_match_score' => 50,
            'ai_rank' => 1,
        ]);
        $this->assertDatabaseHas('applications', [
            'application_id' => 201,
            'skill_match_score' => 100,
            'ai_rank' => 2,
        ]);
        $this->assertDatabaseHas('applications', [
            'application_id' => 202,
            'skill_match_score' => 99,
            'ai_rank' => null,
        ]);
    }

    public function test_recommendations_filters_candidates_to_the_employer_and_selected_criteria(): void
    {
        $this->actingAs($this->employer())
            ->get(route('employer.recommended-candidates', [
                'job_id' => 100,
                'minimum_score' => 80,
                'status' => 'Under Review',
                'search' => 'Candidate Two',
            ]))
            ->assertOk()
            ->assertSee('Candidate Two')
            ->assertSee('PHP Engineer')
            ->assertDontSee('Candidate One')
            ->assertDontSee('Private Job');
    }

    public function test_candidate_comparison_uses_legacy_neutral_score_and_requires_owned_same_job_candidates(): void
    {
        DB::table('job_skills')->where('job_id', 100)->delete();

        $this->actingAs($this->employer())
            ->get(route('employer.candidate-comparison', ['ids' => '200,201']))
            ->assertOk()
            ->assertSee('Candidate Comparison')
            ->assertSee('50.0%')
            ->assertSee('Candidate One')
            ->assertSee('Candidate Two');

        $this->actingAs($this->employer())
            ->get(route('employer.candidate-comparison', ['ids' => '200,203']))
            ->assertRedirect(route('employer.recommended-candidates'))
            ->assertSessionHas('error');

        $this->actingAs($this->employer())
            ->get(route('employer.candidate-comparison', ['ids' => '200']))
            ->assertRedirect(route('employer.recommended-candidates'))
            ->assertSessionHas('error');
    }

    public function test_candidate_insights_are_explained_and_scoped_to_the_employer(): void
    {
        $this->actingAs($this->employer())
            ->get(route('employer.candidate-insights', ['application_id' => 200]))
            ->assertOk()
            ->assertSee('Candidate AI Insights')
            ->assertSee('Candidate Strengths')
            ->assertSee('Areas to Review')
            ->assertSee('Candidate is missing 1 required job skill(s).')
            ->assertSee('A resume is available for employer review.');

        $this->actingAs($this->employer())
            ->get(route('employer.candidate-insights', ['application_id' => 203]))
            ->assertNotFound();
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
