<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmployerJobManagementTest extends TestCase
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
            $table->string('company_email')->nullable();
            $table->string('logo')->default('company.png');
            $table->string('status')->default('Pending');
        });
        Schema::create('employers', function (Blueprint $table) {
            $table->increments('employer_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('company_id')->nullable();
            $table->string('designation')->nullable();
            $table->string('department')->nullable();
        });
        Schema::create('categories', function (Blueprint $table) {
            $table->increments('category_id');
            $table->string('category_name');
            $table->string('status')->default('Active');
        });
        Schema::create('skills', function (Blueprint $table) {
            $table->increments('skill_id');
            $table->string('skill_name');
        });
        Schema::create('jobs', function (Blueprint $table) {
            $table->increments('job_id');
            $table->unsignedInteger('company_id');
            $table->unsignedInteger('employer_id');
            $table->unsignedInteger('category_id');
            $table->string('job_title', 150);
            $table->text('job_description');
            $table->text('job_requirements')->nullable();
            $table->text('job_responsibilities')->nullable();
            $table->string('employment_type');
            $table->string('workplace')->default('Onsite');
            $table->string('experience_level')->default('Junior');
            $table->decimal('minimum_experience', 4, 1)->default(0);
            $table->decimal('minimum_salary', 12, 2)->nullable();
            $table->decimal('maximum_salary', 12, 2)->nullable();
            $table->string('currency', 10)->default('USD');
            $table->unsignedInteger('vacancy')->default(1);
            $table->string('location', 150)->nullable();
            $table->date('application_deadline')->nullable();
            $table->string('job_status')->default('Draft');
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('total_applications')->default(0);
            $table->timestamps();
        });
        Schema::create('job_skills', function (Blueprint $table) {
            $table->increments('job_skill_id');
            $table->unsignedInteger('job_id');
            $table->unsignedInteger('skill_id');
            $table->string('importance')->default('Required');
            $table->timestamp('created_at')->useCurrent();
        });
        Schema::create('applications', function (Blueprint $table) {
            $table->increments('application_id');
            $table->unsignedInteger('job_id');
        });

        DB::table('users')->insert([
            ['user_id' => 6, 'role_id' => 2, 'full_name' => 'Employer One', 'email' => 'one@example.test', 'password' => 'unused', 'account_status' => 'Active'],
            ['user_id' => 7, 'role_id' => 2, 'full_name' => 'Employer Two', 'email' => 'two@example.test', 'password' => 'unused', 'account_status' => 'Active'],
        ]);
        DB::table('companies')->insert([
            ['company_id' => 10, 'company_name' => 'Approved Company', 'company_email' => 'one@company.test', 'logo' => 'company.png', 'status' => 'Approved'],
            ['company_id' => 11, 'company_name' => 'Second Company', 'company_email' => 'two@company.test', 'logo' => 'company.png', 'status' => 'Approved'],
        ]);
        DB::table('employers')->insert([
            ['employer_id' => 3, 'user_id' => 6, 'company_id' => 10],
            ['employer_id' => 4, 'user_id' => 7, 'company_id' => 11],
        ]);
        DB::table('categories')->insert([
            ['category_id' => 1, 'category_name' => 'Technology', 'status' => 'Active'],
            ['category_id' => 2, 'category_name' => 'Inactive Category', 'status' => 'Inactive'],
        ]);
        DB::table('skills')->insert([
            ['skill_id' => 1, 'skill_name' => 'PHP'],
            ['skill_id' => 2, 'skill_name' => 'Laravel'],
        ]);
    }

    public function test_employer_can_list_and_search_only_owned_jobs(): void
    {
        $this->insertJob(100, 3, 10, 'PHP Engineer', 'Remote');
        $this->insertJob(101, 4, 11, 'Other Company Engineer', 'Onsite');

        $this->actingAs($this->employer())
            ->get(route('employer.jobs', ['search' => 'PHP']))
            ->assertOk()
            ->assertSee('PHP Engineer')
            ->assertDontSee('Other Company Engineer')
            ->assertSee('Approved Company');
    }

    public function test_employer_can_create_a_job_and_sync_selected_skills(): void
    {
        $this->actingAs($this->employer())
            ->get(route('employer.jobs.create'))
            ->assertOk()
            ->assertSee('Post a New Job')
            ->assertSee('name="skills[]"', false);

        $this->actingAs($this->employer())
            ->post(route('employer.jobs.store'), $this->validJob([
                'job_title' => 'Laravel Engineer',
                'skills' => ['1', '2'],
                'job_status' => 'Open',
            ]))
            ->assertRedirect(route('employer.jobs'))
            ->assertSessionHas('success', 'Job posted successfully and is now open for applications.');

        $job = DB::table('jobs')->where('job_title', 'Laravel Engineer')->first();
        $this->assertNotNull($job);
        $this->assertSame(3, (int) $job->employer_id);
        $this->assertSame(10, (int) $job->company_id);
        $this->assertEqualsCanonicalizing([1, 2], DB::table('job_skills')->where('job_id', $job->job_id)->pluck('skill_id')->map(fn ($id) => (int) $id)->all());
    }

    public function test_job_creation_requires_approved_company_and_valid_salary_range(): void
    {
        DB::table('companies')->where('company_id', 10)->update(['status' => 'Pending']);

        $this->actingAs($this->employer())
            ->post(route('employer.jobs.store'), $this->validJob())
            ->assertRedirect(route('employer.company-profile'))
            ->assertSessionHas('company_profile_error');

        DB::table('companies')->where('company_id', 10)->update(['status' => 'Approved']);
        $this->actingAs($this->employer())
            ->from(route('employer.jobs.create'))
            ->post(route('employer.jobs.store'), $this->validJob([
                'minimum_salary' => '70000',
                'maximum_salary' => '60000',
            ]))
            ->assertRedirect(route('employer.jobs.create'))
            ->assertSessionHasErrors('maximum_salary');

        $this->assertDatabaseCount('jobs', 0);

        $this->actingAs($this->employer())
            ->post(route('employer.jobs.store'), $this->validJob([
                'maximum_salary' => '60000',
            ]))
            ->assertRedirect(route('employer.jobs'));

        $this->assertDatabaseHas('jobs', ['job_title' => 'PHP Developer', 'maximum_salary' => 60000]);
    }

    public function test_employer_can_edit_owned_job_and_replace_skills(): void
    {
        $this->insertJob(100, 3, 10, 'Old Title', 'Remote');
        DB::table('job_skills')->insert([
            ['job_id' => 100, 'skill_id' => 1],
            ['job_id' => 100, 'skill_id' => 2],
        ]);

        $this->actingAs($this->employer())
            ->get(route('employer.jobs.edit', ['id' => 100]))
            ->assertOk()
            ->assertSee('Old Title')
            ->assertSee('Inactive Category');

        $this->actingAs($this->employer())
            ->post(route('employer.jobs.update'), $this->validJob([
                'job_id' => 100,
                'job_title' => 'Updated Title',
                'category_id' => 2,
                'skills' => ['2'],
                'job_status' => 'Closed',
            ]))
            ->assertRedirect(route('employer.jobs'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('jobs', ['job_id' => 100, 'job_title' => 'Updated Title', 'category_id' => 2, 'job_status' => 'Closed']);
        $this->assertSame([2], DB::table('job_skills')->where('job_id', 100)->pluck('skill_id')->map(fn ($id) => (int) $id)->all());
    }

    public function test_employer_cannot_edit_or_delete_another_employers_job(): void
    {
        $this->insertJob(101, 4, 11, 'Private Job', 'Onsite');

        $this->actingAs($this->employer())
            ->get(route('employer.jobs.edit', ['id' => 101]))
            ->assertRedirect(route('employer.jobs'))
            ->assertSessionHas('danger');

        $this->actingAs($this->employer())
            ->post(route('employer.jobs.delete'), ['job_id' => 101])
            ->assertRedirect(route('employer.jobs'))
            ->assertSessionHas('danger');

        $this->assertDatabaseHas('jobs', ['job_id' => 101, 'job_title' => 'Private Job']);
    }

    public function test_job_with_applications_cannot_be_deleted_but_unused_job_can(): void
    {
        $this->insertJob(100, 3, 10, 'Applied Job', 'Remote');
        $this->insertJob(102, 3, 10, 'Unused Job', 'Remote');
        DB::table('applications')->insert(['application_id' => 50, 'job_id' => 100]);
        DB::table('job_skills')->insert(['job_id' => 100, 'skill_id' => 1]);
        DB::table('job_skills')->insert(['job_id' => 102, 'skill_id' => 2]);

        $this->actingAs($this->employer())
            ->post(route('employer.jobs.delete'), ['job_id' => 100])
            ->assertRedirect(route('employer.jobs'))
            ->assertSessionHas('danger');
        $this->assertDatabaseHas('jobs', ['job_id' => 100]);

        $this->actingAs($this->employer())
            ->post(route('employer.jobs.delete'), ['job_id' => 102])
            ->assertRedirect(route('employer.jobs'))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('jobs', ['job_id' => 102]);
        $this->assertDatabaseMissing('job_skills', ['job_id' => 102]);
    }

    private function validJob(array $overrides = []): array
    {
        return array_merge([
            'job_title' => 'PHP Developer',
            'category_id' => 1,
            'job_description' => 'Build and maintain web applications.',
            'job_requirements' => 'PHP experience.',
            'job_responsibilities' => 'Develop features.',
            'employment_type' => 'Full-Time',
            'workplace' => 'Remote',
            'experience_level' => 'Junior',
            'minimum_experience' => '1',
            'minimum_salary' => '',
            'maximum_salary' => '',
            'currency' => 'USD',
            'vacancy' => '1',
            'location' => 'Remote',
            'application_deadline' => '',
            'job_status' => 'Draft',
            'skills' => [],
        ], $overrides);
    }

    private function insertJob(int $jobId, int $employerId, int $companyId, string $title, string $workplace): void
    {
        DB::table('jobs')->insert([
            'job_id' => $jobId,
            'company_id' => $companyId,
            'employer_id' => $employerId,
            'category_id' => 1,
            'job_title' => $title,
            'job_description' => 'Job description',
            'job_requirements' => null,
            'job_responsibilities' => null,
            'employment_type' => 'Full-Time',
            'workplace' => $workplace,
            'experience_level' => 'Junior',
            'minimum_experience' => 0,
            'minimum_salary' => null,
            'maximum_salary' => null,
            'currency' => 'USD',
            'vacancy' => 1,
            'location' => 'Remote',
            'application_deadline' => null,
            'job_status' => 'Open',
            'views' => 0,
            'total_applications' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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
