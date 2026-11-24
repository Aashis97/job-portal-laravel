<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmployerApplicantWorkflowTest extends TestCase
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
            $table->boolean('email_verified')->default(false);
            $table->string('profile_image')->nullable();
            $table->string('phone')->nullable();
        });
        Schema::create('companies', function (Blueprint $table) {
            $table->increments('company_id');
            $table->string('company_name');
            $table->string('logo')->default('company.png');
        });
        Schema::create('employers', function (Blueprint $table) {
            $table->increments('employer_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('company_id');
        });
        Schema::create('job_seekers', function (Blueprint $table) {
            $table->increments('seeker_id');
            $table->unsignedInteger('user_id');
            $table->string('resume_file')->nullable();
            $table->boolean('profile_completed')->default(false);
            $table->string('availability')->nullable();
        });
        Schema::create('categories', function (Blueprint $table) {
            $table->increments('category_id');
            $table->string('category_name');
        });
        Schema::create('jobs', function (Blueprint $table) {
            $table->increments('job_id');
            $table->unsignedInteger('company_id');
            $table->unsignedInteger('employer_id');
            $table->unsignedInteger('category_id');
            $table->string('job_title');
            $table->string('job_status');
            $table->string('location')->nullable();
            $table->string('employment_type');
            $table->string('workplace');
            $table->string('experience_level');
            $table->unsignedInteger('total_applications')->default(0);
        });
        Schema::create('applications', function (Blueprint $table) {
            $table->increments('application_id');
            $table->unsignedInteger('job_id');
            $table->unsignedInteger('user_id');
            $table->string('application_status')->default('Applied');
            $table->decimal('skill_match_score', 5, 2)->nullable();
            $table->unsignedInteger('ai_rank')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
        Schema::create('skills', function (Blueprint $table) {
            $table->increments('skill_id');
            $table->string('skill_name');
        });
        Schema::create('user_skills', function (Blueprint $table) {
            $table->increments('user_skill_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('skill_id');
        });
        Schema::create('job_skills', function (Blueprint $table) {
            $table->increments('job_skill_id');
            $table->unsignedInteger('job_id');
            $table->unsignedInteger('skill_id');
        });

        DB::table('users')->insert([
            ['user_id' => 6, 'role_id' => 2, 'full_name' => 'Employer One', 'email' => 'one@example.test', 'password' => 'unused', 'account_status' => 'Active', 'email_verified' => false, 'profile_image' => null, 'phone' => null],
            ['user_id' => 7, 'role_id' => 2, 'full_name' => 'Employer Two', 'email' => 'two@example.test', 'password' => 'unused', 'account_status' => 'Active', 'email_verified' => false, 'profile_image' => null, 'phone' => null],
            ['user_id' => 20, 'role_id' => 3, 'full_name' => 'Candidate One', 'email' => 'candidate@example.test', 'password' => 'unused', 'account_status' => 'Active', 'email_verified' => true, 'profile_image' => null, 'phone' => '123456789'],
            ['user_id' => 21, 'role_id' => 3, 'full_name' => 'Candidate Two', 'email' => 'candidate2@example.test', 'password' => 'unused', 'account_status' => 'Active', 'email_verified' => true, 'profile_image' => null, 'phone' => null],
        ]);
        DB::table('companies')->insert([
            ['company_id' => 10, 'company_name' => 'First Company', 'logo' => 'company.png'],
            ['company_id' => 11, 'company_name' => 'Second Company', 'logo' => 'company.png'],
        ]);
        DB::table('employers')->insert([
            ['employer_id' => 3, 'user_id' => 6, 'company_id' => 10],
            ['employer_id' => 4, 'user_id' => 7, 'company_id' => 11],
        ]);
        DB::table('job_seekers')->insert([
            ['seeker_id' => 30, 'user_id' => 20, 'resume_file' => null, 'profile_completed' => true, 'availability' => 'Immediately'],
            ['seeker_id' => 31, 'user_id' => 21, 'resume_file' => 'candidate.pdf', 'profile_completed' => false, 'availability' => '30 Days'],
        ]);
        DB::table('categories')->insert(['category_id' => 1, 'category_name' => 'Technology']);
        DB::table('jobs')->insert([
            ['job_id' => 100, 'company_id' => 10, 'employer_id' => 3, 'category_id' => 1, 'job_title' => 'PHP Engineer', 'job_status' => 'Open', 'location' => 'Remote', 'employment_type' => 'Full-Time', 'workplace' => 'Remote', 'experience_level' => 'Junior'],
            ['job_id' => 101, 'company_id' => 11, 'employer_id' => 4, 'category_id' => 1, 'job_title' => 'Private Job', 'job_status' => 'Open', 'location' => 'Onsite', 'employment_type' => 'Full-Time', 'workplace' => 'Onsite', 'experience_level' => 'Senior'],
        ]);
        DB::table('applications')->insert([
            ['application_id' => 200, 'job_id' => 100, 'user_id' => 20, 'application_status' => 'Applied', 'skill_match_score' => 80.5, 'ai_rank' => 1, 'applied_at' => now()],
            ['application_id' => 201, 'job_id' => 101, 'user_id' => 21, 'application_status' => 'Applied', 'skill_match_score' => 50, 'ai_rank' => 2, 'applied_at' => now()],
        ]);
        DB::table('skills')->insert([
            ['skill_id' => 1, 'skill_name' => 'PHP'],
            ['skill_id' => 2, 'skill_name' => 'Laravel'],
        ]);
        DB::table('user_skills')->insert([
            ['user_id' => 20, 'skill_id' => 1],
            ['user_id' => 20, 'skill_id' => 2],
        ]);
        DB::table('job_skills')->insert([
            ['job_id' => 100, 'skill_id' => 1],
            ['job_id' => 100, 'skill_id' => 2],
        ]);
    }

    public function test_applicants_list_and_details_are_scoped_to_the_employers_jobs(): void
    {
        $this->actingAs($this->employer())
            ->get(route('employer.applications'))
            ->assertOk()
            ->assertSee('PHP Engineer')
            ->assertSee('applications');

        $this->actingAs($this->employer())
            ->get(route('employer.applicants', ['job_id' => 100]))
            ->assertOk()
            ->assertSee('Candidate One')
            ->assertDontSee('Candidate Two');

        $this->actingAs($this->employer())
            ->get(route('employer.applicant-details', ['application_id' => 200]))
            ->assertOk()
            ->assertSee('Candidate One')
            ->assertSee('Matched Skills')
            ->assertSee('PHP')
            ->assertSee('AI Skill Match');

        $this->actingAs($this->employer())
            ->get(route('employer.applicant-details', ['application_id' => 201]))
            ->assertRedirect(route('employer.jobs'))
            ->assertSessionHas('danger');
    }

    public function test_employer_can_update_status_only_for_owned_application(): void
    {
        $this->actingAs($this->employer())
            ->post(route('employer.applicant-status.update'), [
                'application_id' => 200,
                'status' => 'Shortlisted',
            ])
            ->assertRedirect(route('employer.applicant-details', ['application_id' => 200]))
            ->assertSessionHas('success', 'Application status updated successfully to "Shortlisted".');
        $this->assertDatabaseHas('applications', ['application_id' => 200, 'application_status' => 'Shortlisted']);

        $this->actingAs($this->employer())
            ->post(route('employer.applicant-status.update'), [
                'application_id' => 201,
                'status' => 'Rejected',
            ])
            ->assertRedirect(route('employer.jobs'))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('applications', ['application_id' => 201, 'application_status' => 'Applied']);
    }

    public function test_status_values_are_validated_and_resume_is_private_to_the_job_owner(): void
    {
        $this->actingAs($this->employer())
            ->from(route('employer.applicant-details', ['application_id' => 200]))
            ->post(route('employer.applicant-status.update'), [
                'application_id' => 200,
                'status' => 'Unknown',
            ])
            ->assertRedirect(route('employer.applicant-details', ['application_id' => 200]))
            ->assertSessionHasErrors('status');

        $this->actingAs($this->employer())
            ->get(route('employer.applicant-resume', ['application_id' => 201]))
            ->assertNotFound();
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
