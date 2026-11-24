<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ApplicationWorkflowTest extends TestCase
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
            $table->string('logo')->nullable();
        });
        Schema::create('employers', function (Blueprint $table) {
            $table->increments('employer_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('company_id');
        });
        Schema::create('jobs', function (Blueprint $table) {
            $table->increments('job_id');
            $table->unsignedInteger('company_id');
            $table->unsignedInteger('employer_id');
            $table->string('job_title');
            $table->string('job_status');
            $table->string('employment_type')->default('Full-Time');
            $table->string('workplace')->default('Remote');
            $table->string('experience_level')->default('Junior');
            $table->string('location')->nullable();
            $table->date('application_deadline')->nullable();
            $table->decimal('minimum_salary', 12, 2)->nullable();
            $table->decimal('maximum_salary', 12, 2)->nullable();
            $table->string('currency')->nullable();
            $table->unsignedInteger('total_applications')->default(0);
        });
        Schema::create('applications', function (Blueprint $table) {
            $table->increments('application_id');
            $table->unsignedInteger('job_id');
            $table->unsignedInteger('user_id');
            $table->text('cover_letter')->nullable();
            $table->string('cover_letter_file')->nullable();
            $table->string('application_status')->default('Applied');
            $table->decimal('skill_match_score', 5, 2)->default(0);
            $table->unsignedInteger('ai_rank')->nullable();
            $table->dateTime('applied_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique(['job_id', 'user_id']);
        });
        Schema::create('job_skills', function (Blueprint $table) {
            $table->unsignedInteger('job_id');
            $table->unsignedInteger('skill_id');
            $table->string('importance');
        });
        Schema::create('user_skills', function (Blueprint $table) {
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('skill_id');
        });
        Schema::create('saved_jobs', function (Blueprint $table) {
            $table->increments('saved_job_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('job_id');
            $table->dateTime('saved_at')->nullable();
            $table->unique(['user_id', 'job_id']);
        });
        Schema::create('notifications', function (Blueprint $table) {
            $table->increments('notification_id');
            $table->unsignedInteger('user_id');
            $table->string('title');
            $table->text('message');
            $table->string('notification_type');
            $table->boolean('is_read')->default(false);
            $table->dateTime('created_at')->nullable();
        });
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->bigIncrements('log_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('activity');
            $table->string('module')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('device_info')->nullable();
        });

        DB::table('users')->insert([
            ['user_id' => 8, 'role_id' => 3, 'full_name' => 'Test Seeker', 'email' => 'seeker@example.test', 'password' => 'unused', 'account_status' => 'Active'],
            ['user_id' => 2, 'role_id' => 2, 'full_name' => 'Test Employer', 'email' => 'employer@example.test', 'password' => 'unused', 'account_status' => 'Active'],
            ['user_id' => 9, 'role_id' => 3, 'full_name' => 'Other Seeker', 'email' => 'other@example.test', 'password' => 'unused', 'account_status' => 'Active'],
        ]);
        DB::table('companies')->insert(['company_id' => 4, 'company_name' => 'Example Company']);
        DB::table('employers')->insert(['employer_id' => 5, 'user_id' => 2, 'company_id' => 4]);
        DB::table('jobs')->insert([
            ['job_id' => 11, 'company_id' => 4, 'employer_id' => 5, 'job_title' => 'PHP Engineer', 'job_status' => 'Open', 'application_deadline' => null, 'total_applications' => 0],
            ['job_id' => 12, 'company_id' => 4, 'employer_id' => 5, 'job_title' => 'Closed Engineer', 'job_status' => 'Closed', 'application_deadline' => null, 'total_applications' => 0],
            ['job_id' => 13, 'company_id' => 4, 'employer_id' => 5, 'job_title' => 'Expired Engineer', 'job_status' => 'Open', 'application_deadline' => now()->subDay()->toDateString(), 'total_applications' => 0],
        ]);
        DB::table('job_skills')->insert([
            ['job_id' => 11, 'skill_id' => 1, 'importance' => 'Required'],
            ['job_id' => 11, 'skill_id' => 2, 'importance' => 'Required'],
            ['job_id' => 11, 'skill_id' => 3, 'importance' => 'Preferred'],
        ]);
        DB::table('user_skills')->insert(['user_id' => 8, 'skill_id' => 1]);
    }

    public function test_job_seeker_can_apply_once_and_not_to_closed_or_expired_jobs(): void
    {
        $this->actingAs($this->jobSeeker())
            ->get('/jobseeker/apply_job.php?job_id=11')
            ->assertOk()
            ->assertSee('Apply for PHP Engineer');

        $response = $this->actingAs($this->jobSeeker())
            ->post('/jobseeker/apply_job.php', ['job_id' => 11, 'cover_letter' => 'I am interested.']);

        $response->assertRedirect(route('applications.index'));
        $this->assertDatabaseHas('applications', [
            'job_id' => 11,
            'user_id' => 8,
            'application_status' => 'Applied',
            'skill_match_score' => 50,
        ]);
        $this->assertDatabaseHas('jobs', ['job_id' => 11, 'total_applications' => 1]);
        $this->assertDatabaseHas('notifications', ['user_id' => 2, 'notification_type' => 'Application']);
        $this->assertDatabaseHas('activity_logs', ['user_id' => 8, 'module' => 'Jobseeker']);

        $this->actingAs($this->jobSeeker())
            ->post('/jobseeker/apply_job.php', ['job_id' => 11])
            ->assertRedirect(route('jobs.show', ['id' => 11]));
        $this->assertDatabaseCount('applications', 1);

        $this->actingAs($this->jobSeeker())
            ->get('/jobseeker/apply_job.php?job_id=12')
            ->assertRedirect(route('jobs.show', ['id' => 12]));
        $this->actingAs($this->jobSeeker())
            ->post('/jobseeker/apply_job.php', ['job_id' => 13])
            ->assertRedirect(route('jobs.show', ['id' => 13]));
        $this->assertDatabaseCount('applications', 1);
    }

    public function test_cover_letter_upload_is_validated_and_saved_in_the_legacy_upload_directory(): void
    {
        $uploadPath = dirname(base_path()) . DIRECTORY_SEPARATOR . 'assets'
            . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'cover_letters';

        $response = $this->actingAs($this->jobSeeker())
            ->post('/jobseeker/apply_job.php', [
                'job_id' => 11,
                'cover_letter_file' => UploadedFile::fake()->createWithContent('letter.pdf', "%PDF-1.4\ncover letter"),
            ]);

        $response->assertRedirect(route('applications.index'));
        $fileName = DB::table('applications')->value('cover_letter_file');
        $this->assertNotEmpty($fileName);
        $this->assertFileExists($uploadPath . DIRECTORY_SEPARATOR . $fileName);
        unlink($uploadPath . DIRECTORY_SEPARATOR . $fileName);
    }

    public function test_cover_letter_rejects_an_unsupported_filename_extension(): void
    {
        $this->actingAs($this->jobSeeker())
            ->from('/jobseeker/apply_job.php?job_id=11')
            ->post('/jobseeker/apply_job.php', [
                'job_id' => 11,
                'cover_letter_file' => UploadedFile::fake()->createWithContent('letter.txt', "%PDF-1.4\ncover letter"),
            ])
            ->assertRedirect('/jobseeker/apply_job.php?job_id=11')
            ->assertSessionHasErrors('cover_letter_file');

        $this->assertDatabaseCount('applications', 0);
    }

    public function test_application_history_filters_by_status_and_search_and_withdrawal_checks_ownership(): void
    {
        DB::table('applications')->insert([
            ['application_id' => 21, 'job_id' => 11, 'user_id' => 8, 'application_status' => 'Under Review'],
            ['application_id' => 22, 'job_id' => 12, 'user_id' => 8, 'application_status' => 'Selected'],
            ['application_id' => 23, 'job_id' => 11, 'user_id' => 9, 'application_status' => 'Applied'],
        ]);

        $this->actingAs($this->jobSeeker())
            ->get('/jobseeker/applications.php?status=Under%20Review&q=Engineer')
            ->assertOk()
            ->assertSee('PHP Engineer')
            ->assertDontSee('Closed Engineer');

        $this->actingAs($this->jobSeeker())
            ->post('/jobseeker/withdraw_application.php', ['application_id' => 21])
            ->assertRedirect(route('applications.index'));
        $this->assertDatabaseHas('applications', ['application_id' => 21, 'application_status' => 'Withdrawn']);

        $this->actingAs($this->jobSeeker())
            ->post('/jobseeker/withdraw_application.php', ['application_id' => 22])
            ->assertRedirect(route('applications.index'))
            ->assertSessionHas('warning');
        $this->actingAs($this->jobSeeker())
            ->post('/jobseeker/withdraw_application.php', ['application_id' => 23])
            ->assertRedirect(route('applications.index'))
            ->assertSessionHas('danger');
        $this->assertDatabaseHas('applications', ['application_id' => 23, 'application_status' => 'Applied']);
    }

    public function test_saved_jobs_search_displays_application_state_and_removes_only_the_current_users_bookmark(): void
    {
        DB::table('saved_jobs')->insert([
            ['saved_job_id' => 31, 'user_id' => 8, 'job_id' => 11, 'saved_at' => now()],
            ['saved_job_id' => 32, 'user_id' => 9, 'job_id' => 11, 'saved_at' => now()],
            ['saved_job_id' => 33, 'user_id' => 8, 'job_id' => 12, 'saved_at' => now()],
        ]);
        DB::table('applications')->insert([
            ['application_id' => 41, 'job_id' => 11, 'user_id' => 8, 'application_status' => 'Applied'],
        ]);

        $this->actingAs($this->jobSeeker())
            ->get('/jobseeker/saved_jobs.php?q=PHP')
            ->assertOk()
            ->assertSee('PHP Engineer')
            ->assertSee('Applied')
            ->assertDontSee('Closed Engineer');

        $this->actingAs($this->jobSeeker())
            ->post('/jobseeker/unsave_job.php', ['job_id' => 11, 'return_to' => 'saved'])
            ->assertRedirect(route('saved_jobs.index'));

        $this->assertDatabaseMissing('saved_jobs', ['user_id' => 8, 'job_id' => 11]);
        $this->assertDatabaseHas('saved_jobs', ['user_id' => 9, 'job_id' => 11]);
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
