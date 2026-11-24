<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmployerInterviewWorkflowTest extends TestCase
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
            $table->string('status')->default('Approved');
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
        Schema::create('interviews', function (Blueprint $table) {
            $table->increments('interview_id');
            $table->unsignedInteger('application_id');
            $table->string('interviewer_name', 150);
            $table->string('interview_type');
            $table->date('interview_date');
            $table->time('interview_time');
            $table->string('meeting_link', 1000)->nullable();
            $table->string('location', 255)->nullable();
            $table->string('interview_status')->default('Scheduled');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
        Schema::create('notifications', function (Blueprint $table) {
            $table->increments('notification_id');
            $table->unsignedInteger('user_id');
            $table->string('title', 200);
            $table->text('message');
            $table->string('notification_type')->default('System');
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->nullable();
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
            ['user_id' => 20, 'role_id' => 3, 'full_name' => 'Candidate One', 'email' => 'candidate@example.test', 'password' => 'unused', 'account_status' => 'Active', 'email_verified' => true, 'profile_image' => null, 'phone' => null],
            ['user_id' => 21, 'role_id' => 3, 'full_name' => 'Candidate Two', 'email' => 'candidate2@example.test', 'password' => 'unused', 'account_status' => 'Active', 'email_verified' => true, 'profile_image' => null, 'phone' => null],
        ]);
        DB::table('companies')->insert([
            ['company_id' => 10, 'company_name' => 'First Company', 'logo' => 'company.png', 'status' => 'Approved'],
            ['company_id' => 11, 'company_name' => 'Second Company', 'logo' => 'company.png', 'status' => 'Approved'],
        ]);
        DB::table('employers')->insert([
            ['employer_id' => 3, 'user_id' => 6, 'company_id' => 10],
            ['employer_id' => 4, 'user_id' => 7, 'company_id' => 11],
        ]);
        DB::table('job_seekers')->insert([
            ['seeker_id' => 30, 'user_id' => 20, 'resume_file' => null, 'profile_completed' => true, 'availability' => 'Immediately'],
            ['seeker_id' => 31, 'user_id' => 21, 'resume_file' => null, 'profile_completed' => false, 'availability' => '30 Days'],
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

    public function test_interview_list_filters_and_details_are_scoped_to_the_employer(): void
    {
        $this->insertInterview(300, 200, 'Scheduled');
        $this->insertInterview(301, 201, 'Scheduled');

        $this->actingAs($this->employer())
            ->get(route('employer.interviews', ['status' => 'Scheduled', 'type' => 'Online', 'search' => 'Candidate One']))
            ->assertOk()
            ->assertSee('Candidate One')
            ->assertDontSee('Candidate Two');

        $this->actingAs($this->employer())
            ->get(route('employer.interview-details', ['interview_id' => 300]))
            ->assertOk()
            ->assertSee('Interview Details')
            ->assertSee('PHP Engineer')
            ->assertSee('AI Candidate Analysis');

        $this->actingAs($this->employer())
            ->get(route('employer.interview-details', ['interview_id' => 301]))
            ->assertRedirect(route('employer.interviews'))
            ->assertSessionHas('error');
    }

    public function test_employer_can_schedule_once_and_candidate_is_notified(): void
    {
        $this->actingAs($this->employer())
            ->get(route('employer.interviews.schedule', ['application_id' => 200]))
            ->assertOk()
            ->assertSee('Schedule Interview');

        $this->actingAs($this->employer())
            ->post(route('employer.interviews.store'), $this->validSchedule())
            ->assertSessionHas('success', 'Interview scheduled successfully.');

        $interview = DB::table('interviews')->where('application_id', 200)->first();
        $this->assertNotNull($interview);
        $this->assertSame('Scheduled', $interview->interview_status);
        $this->assertDatabaseHas('applications', ['application_id' => 200, 'application_status' => 'Interview Scheduled']);
        $this->assertDatabaseHas('notifications', [
            'user_id' => 20,
            'title' => 'Interview scheduled',
            'notification_type' => 'Interview',
        ]);

        $this->actingAs($this->employer())
            ->post(route('employer.interviews.store'), $this->validSchedule())
            ->assertRedirect(route('employer.interview-details', ['interview_id' => $interview->interview_id]))
            ->assertSessionHas('error');
        $this->assertDatabaseCount('interviews', 1);
    }

    public function test_interview_status_transitions_sync_application_and_keep_history(): void
    {
        $this->insertInterview(300, 200, 'Scheduled');
        DB::table('applications')->where('application_id', 200)->update(['application_status' => 'Interview Scheduled']);

        $this->actingAs($this->employer())
            ->post(route('employer.interviews.status'), ['interview_id' => 300, 'status' => 'Rescheduled'])
            ->assertSessionHas('success', 'Interview marked as rescheduled successfully.');
        $this->assertDatabaseHas('applications', ['application_id' => 200, 'application_status' => 'Interview Scheduled']);

        $this->actingAs($this->employer())
            ->post(route('employer.interviews.status'), ['interview_id' => 300, 'status' => 'Cancelled'])
            ->assertSessionHas('success', 'Interview cancelled successfully. The interview record has been preserved.');
        $this->assertDatabaseHas('interviews', ['interview_id' => 300, 'interview_status' => 'Cancelled']);
        $this->assertDatabaseHas('applications', ['application_id' => 200, 'application_status' => 'Under Review']);

        $this->actingAs($this->employer())
            ->post(route('employer.interviews.status'), ['interview_id' => 300, 'status' => 'Scheduled'])
            ->assertSessionHas('error', 'A completed or cancelled interview cannot be changed.');
    }

    public function test_interview_schedule_validation_and_ownership_are_enforced(): void
    {
        $this->actingAs($this->employer())
            ->from(route('employer.interviews.schedule', ['application_id' => 200]))
            ->post(route('employer.interviews.store'), $this->validSchedule([
                'interview_type' => 'Online',
                'meeting_link' => 'ftp://invalid.example.test/meeting',
            ]))
            ->assertRedirect(route('employer.interviews.schedule', ['application_id' => 200]))
            ->assertSessionHasErrors('meeting_link');
        $this->assertDatabaseCount('interviews', 0);

        $this->actingAs($this->employer())
            ->post(route('employer.interviews.store'), $this->validSchedule(['application_id' => 201]))
            ->assertRedirect(route('employer.applications'))
            ->assertSessionHas('error');
        $this->assertDatabaseCount('interviews', 0);
    }

    public function test_edit_rejects_interviewer_time_conflicts(): void
    {
        $this->insertInterview(300, 200, 'Scheduled');
        DB::table('applications')->insert([
            'application_id' => 202,
            'job_id' => 100,
            'user_id' => 21,
            'application_status' => 'Applied',
            'skill_match_score' => 50,
            'ai_rank' => 2,
            'applied_at' => now(),
        ]);
        $this->insertInterview(302, 202, 'Scheduled');

        $this->actingAs($this->employer())
            ->post(route('employer.interviews.update'), $this->validSchedule([
                'interview_id' => 302,
                'application_id' => null,
            ]))
            ->assertSessionHas('error', 'This interviewer already has another interview scheduled at the selected date and time.');
        $this->assertDatabaseHas('interviews', ['interview_id' => 302, 'interviewer_name' => 'Recruiter']);
    }

    private function validSchedule(array $overrides = []): array
    {
        return array_merge([
            'application_id' => 200,
            'interviewer_name' => 'Recruiter',
            'interview_type' => 'Online',
            'interview_date' => now()->addDays(3)->toDateString(),
            'interview_time' => '14:30',
            'meeting_link' => 'https://meet.example.test/room',
            'location' => '',
            'remarks' => 'Bring portfolio.',
        ], $overrides);
    }

    private function insertInterview(int $interviewId, int $applicationId, string $status): void
    {
        DB::table('interviews')->insert([
            'interview_id' => $interviewId,
            'application_id' => $applicationId,
            'interviewer_name' => 'Recruiter',
            'interview_type' => 'Online',
            'interview_date' => now()->addDays(3)->toDateString(),
            'interview_time' => '14:30:00',
            'meeting_link' => 'https://meet.example.test/room',
            'location' => null,
            'interview_status' => $status,
            'remarks' => null,
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
