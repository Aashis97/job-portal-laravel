<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminJobManagementTest extends TestCase
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
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('logo')->nullable();
        });
        Schema::create('categories', function (Blueprint $table) {
            $table->increments('category_id');
            $table->string('category_name');
            $table->string('status');
        });
        Schema::create('employers', function (Blueprint $table) {
            $table->increments('employer_id');
            $table->unsignedInteger('user_id');
        });
        Schema::create('jobs', function (Blueprint $table) {
            $table->increments('job_id');
            $table->unsignedInteger('company_id');
            $table->unsignedInteger('employer_id');
            $table->unsignedInteger('category_id');
            $table->string('job_title');
            $table->text('job_description');
            $table->text('job_requirements')->nullable();
            $table->text('job_responsibilities')->nullable();
            $table->string('employment_type');
            $table->string('workplace');
            $table->string('experience_level');
            $table->decimal('minimum_experience', 4, 1)->nullable();
            $table->decimal('minimum_salary', 12, 2)->nullable();
            $table->decimal('maximum_salary', 12, 2)->nullable();
            $table->string('currency')->default('USD');
            $table->unsignedInteger('vacancy')->default(1);
            $table->string('location')->nullable();
            $table->date('application_deadline')->nullable();
            $table->string('job_status');
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('total_applications')->default(0);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
        Schema::create('applications', function (Blueprint $table) {
            $table->increments('application_id');
            $table->unsignedInteger('job_id');
            $table->unsignedInteger('user_id');
            $table->string('application_status');
            $table->decimal('skill_match_score', 5, 2)->nullable();
            $table->timestamp('applied_at')->nullable();
        });
        Schema::create('interviews', function (Blueprint $table) {
            $table->increments('interview_id');
            $table->unsignedInteger('application_id');
        });
        Schema::create('saved_jobs', function (Blueprint $table) {
            $table->increments('saved_job_id');
            $table->unsignedInteger('job_id');
            $table->unsignedInteger('user_id');
        });
        Schema::create('skills', function (Blueprint $table) {
            $table->increments('skill_id');
            $table->string('skill_name');
        });
        Schema::create('job_skills', function (Blueprint $table) {
            $table->increments('job_skill_id');
            $table->unsignedInteger('job_id');
            $table->unsignedInteger('skill_id');
            $table->string('importance');
        });

        DB::table('users')->insert([
            ['user_id' => 1, 'role_id' => 1, 'full_name' => 'Admin One', 'email' => 'admin@example.test', 'password' => 'unused', 'account_status' => 'Active'],
            ['user_id' => 2, 'role_id' => 2, 'full_name' => 'Employer One', 'email' => 'employer@example.test', 'password' => 'unused', 'account_status' => 'Active'],
            ['user_id' => 3, 'role_id' => 3, 'full_name' => 'Candidate One', 'email' => 'candidate@example.test', 'password' => 'unused', 'account_status' => 'Active'],
        ]);
        DB::table('companies')->insert(['company_id' => 10, 'company_name' => 'Portal Co', 'status' => 'Approved', 'city' => 'Kathmandu', 'country' => 'Nepal', 'logo' => 'company.png']);
        DB::table('categories')->insert([
            ['category_id' => 1, 'category_name' => 'Technology', 'status' => 'Active'],
            ['category_id' => 2, 'category_name' => 'Finance', 'status' => 'Active'],
        ]);
        DB::table('employers')->insert(['employer_id' => 5, 'user_id' => 2]);
        $this->insertJob(100, 'Laravel Engineer', 'Open', '2026-11-30', 1);
        $this->insertJob(101, 'Accountant', 'Draft', null, 2);
        DB::table('applications')->insert(['application_id' => 200, 'job_id' => 100, 'user_id' => 3, 'application_status' => 'Applied', 'skill_match_score' => 85, 'applied_at' => now()]);
        DB::table('interviews')->insert(['interview_id' => 300, 'application_id' => 200]);
        DB::table('saved_jobs')->insert(['saved_job_id' => 400, 'job_id' => 100, 'user_id' => 3]);
        DB::table('skills')->insert(['skill_id' => 1, 'skill_name' => 'Laravel']);
        DB::table('job_skills')->insert(['job_skill_id' => 500, 'job_id' => 100, 'skill_id' => 1, 'importance' => 'Required']);
    }

    public function test_admin_job_filters_details_and_analytics_are_available(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.jobs', ['status' => 'Open', 'category_id' => 1, 'q' => 'Kathmandu']))
            ->assertOk()
            ->assertSee('Laravel Engineer')
            ->assertDontSee('Accountant')
            ->assertSee('Total views');

        $this->actingAs($this->admin())
            ->get(route('admin.job-details', ['id' => 100]))
            ->assertOk()
            ->assertSee('Laravel Engineer')
            ->assertSee('Required Skills')
            ->assertSee('Candidate One')
            ->assertSee('Delete job and applications');
    }

    public function test_admin_job_status_transitions_and_deletion_cascade(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.job-status.update'), ['job_id' => 100, 'action' => 'close'])
            ->assertRedirect(route('admin.job-details', ['id' => 100]))
            ->assertSessionHas('success', 'Job closed. It will no longer accept applications.');
        $this->assertDatabaseHas('jobs', ['job_id' => 100, 'job_status' => 'Closed']);

        $this->actingAs($this->admin())
            ->post(route('admin.job-status.update'), ['job_id' => 100, 'action' => 'reopen'])
            ->assertSessionHas('success', 'Job reopened and accepting applications again.');

        $this->actingAs($this->admin())
            ->post(route('admin.job-status.update'), ['job_id' => 101, 'action' => 'close'])
            ->assertSessionHas('danger', 'Only open jobs can be closed.');

        $this->actingAs($this->admin())
            ->post(route('admin.job-status.update'), ['job_id' => 100, 'action' => 'delete'])
            ->assertRedirect(route('admin.jobs'))
            ->assertSessionHas('success', 'Job and all associated applications deleted permanently.');
        $this->assertDatabaseMissing('jobs', ['job_id' => 100]);
        $this->assertDatabaseMissing('applications', ['application_id' => 200]);
        $this->assertDatabaseMissing('interviews', ['interview_id' => 300]);
        $this->assertDatabaseMissing('saved_jobs', ['saved_job_id' => 400]);
        $this->assertDatabaseMissing('job_skills', ['job_skill_id' => 500]);
    }

    private function insertJob(int $id, string $title, string $status, ?string $deadline, int $categoryId): void
    {
        DB::table('jobs')->insert([
            'job_id' => $id,
            'company_id' => 10,
            'employer_id' => 5,
            'category_id' => $categoryId,
            'job_title' => $title,
            'job_description' => 'Job description.',
            'job_requirements' => 'Experience required.',
            'job_responsibilities' => 'Deliver work.',
            'employment_type' => 'Full-Time',
            'workplace' => 'Remote',
            'experience_level' => 'Junior',
            'minimum_experience' => 1,
            'minimum_salary' => null,
            'maximum_salary' => null,
            'currency' => 'USD',
            'vacancy' => 1,
            'location' => 'Kathmandu',
            'application_deadline' => $deadline,
            'job_status' => $status,
            'views' => 50,
            'total_applications' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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
