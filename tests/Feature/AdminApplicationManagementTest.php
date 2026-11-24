<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminApplicationManagementTest extends TestCase
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
            $table->string('phone')->nullable();
            $table->string('profile_image')->nullable();
            $table->boolean('email_verified')->default(false);
        });
        Schema::create('companies', function (Blueprint $table) {
            $table->increments('company_id');
            $table->string('company_name');
            $table->string('logo')->nullable();
            $table->string('status');
        });
        Schema::create('employers', function (Blueprint $table) {
            $table->increments('employer_id');
            $table->unsignedInteger('user_id');
        });
        Schema::create('job_seekers', function (Blueprint $table) {
            $table->increments('seeker_id');
            $table->unsignedInteger('user_id');
            $table->string('current_job_title')->nullable();
            $table->decimal('years_of_experience', 4, 1)->nullable();
            $table->string('education_level')->nullable();
            $table->string('availability')->nullable();
            $table->boolean('profile_completed')->default(false);
            $table->string('resume_file')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
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
            $table->string('employment_type');
            $table->string('workplace');
            $table->string('experience_level');
            $table->string('location')->nullable();
            $table->string('job_status');
            $table->unsignedInteger('total_applications')->default(0);
        });
        Schema::create('applications', function (Blueprint $table) {
            $table->increments('application_id');
            $table->unsignedInteger('job_id');
            $table->unsignedInteger('user_id');
            $table->text('cover_letter')->nullable();
            $table->string('application_status');
            $table->decimal('skill_match_score', 5, 2)->nullable();
            $table->unsignedInteger('ai_rank')->nullable();
            $table->text('employer_notes')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
        Schema::create('interviews', function (Blueprint $table) {
            $table->increments('interview_id');
            $table->unsignedInteger('application_id');
        });

        DB::table('users')->insert([
            ['user_id' => 1, 'role_id' => 1, 'full_name' => 'Admin One', 'email' => 'admin@example.test', 'password' => 'unused', 'account_status' => 'Active', 'phone' => null, 'email_verified' => true],
            ['user_id' => 2, 'role_id' => 2, 'full_name' => 'Employer One', 'email' => 'employer@example.test', 'password' => 'unused', 'account_status' => 'Active', 'phone' => null, 'email_verified' => true],
            ['user_id' => 3, 'role_id' => 3, 'full_name' => 'Candidate One', 'email' => 'candidate@example.test', 'password' => 'unused', 'account_status' => 'Active', 'phone' => '555-0101', 'email_verified' => true],
        ]);
        DB::table('companies')->insert(['company_id' => 10, 'company_name' => 'Portal Co', 'logo' => 'company.png', 'status' => 'Approved']);
        DB::table('employers')->insert(['employer_id' => 5, 'user_id' => 2]);
        DB::table('job_seekers')->insert(['seeker_id' => 8, 'user_id' => 3, 'current_job_title' => 'Developer', 'years_of_experience' => 4.5, 'education_level' => 'Bachelor', 'availability' => 'Immediate', 'profile_completed' => true, 'resume_file' => null]);
        DB::table('categories')->insert(['category_id' => 1, 'category_name' => 'Technology']);
        DB::table('jobs')->insert(['job_id' => 100, 'company_id' => 10, 'employer_id' => 5, 'category_id' => 1, 'job_title' => 'Laravel Engineer', 'employment_type' => 'Full-Time', 'workplace' => 'Remote', 'experience_level' => 'Mid', 'location' => 'Kathmandu', 'job_status' => 'Open', 'total_applications' => 1]);
        DB::table('applications')->insert([
            ['application_id' => 200, 'job_id' => 100, 'user_id' => 3, 'cover_letter' => 'I build Laravel applications.', 'application_status' => 'Applied', 'skill_match_score' => 88.5, 'ai_rank' => 1, 'employer_notes' => 'Strong profile.', 'applied_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('interviews')->insert(['interview_id' => 300, 'application_id' => 200]);
    }

    public function test_admin_can_filter_and_inspect_applications(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.applications', ['status' => 'Applied', 'company_id' => 10, 'min_score' => 80, 'q' => 'Laravel']))
            ->assertOk()
            ->assertSee('Candidate One')
            ->assertSee('Laravel Engineer')
            ->assertSee('88.5%');

        $this->actingAs($this->admin())
            ->get(route('admin.application-details', ['id' => 200]))
            ->assertOk()
            ->assertSee('I build Laravel applications.')
            ->assertSee('Candidate Snapshot')
            ->assertSee('Force withdraw')
            ->assertSee('Developer');
    }

    public function test_admin_force_withdraws_and_deletes_application_dependencies(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.application-action'), ['application_id' => 200, 'action' => 'force_withdraw'])
            ->assertRedirect(route('admin.application-details', ['id' => 200]))
            ->assertSessionHas('success', 'Application force-withdrawn.');
        $this->assertDatabaseHas('applications', ['application_id' => 200, 'application_status' => 'Withdrawn']);

        $this->actingAs($this->admin())
            ->post(route('admin.application-action'), ['application_id' => 200, 'action' => 'delete'])
            ->assertRedirect(route('admin.applications'))
            ->assertSessionHas('success', 'Application deleted permanently.');
        $this->assertDatabaseMissing('applications', ['application_id' => 200]);
        $this->assertDatabaseMissing('interviews', ['interview_id' => 300]);
        $this->assertDatabaseHas('jobs', ['job_id' => 100, 'total_applications' => 0]);
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
