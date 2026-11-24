<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RecommendationWorkflowTest extends TestCase
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
            $table->string('city')->nullable();
            $table->decimal('years_of_experience', 4, 1)->default(0);
        });
        Schema::create('companies', function (Blueprint $table) {
            $table->increments('company_id');
            $table->string('company_name');
            $table->string('logo')->nullable();
        });
        Schema::create('categories', function (Blueprint $table) {
            $table->increments('category_id');
            $table->string('category_name');
        });
        Schema::create('jobs', function (Blueprint $table) {
            $table->increments('job_id');
            $table->unsignedInteger('company_id');
            $table->unsignedInteger('category_id');
            $table->string('job_title');
            $table->text('job_description');
            $table->string('job_status');
            $table->string('employment_type');
            $table->string('workplace');
            $table->string('experience_level');
            $table->decimal('minimum_experience', 4, 1)->nullable();
            $table->decimal('minimum_salary', 12, 2)->nullable();
            $table->decimal('maximum_salary', 12, 2)->nullable();
            $table->string('currency')->nullable();
            $table->string('location')->nullable();
            $table->date('application_deadline')->nullable();
        });
        Schema::create('user_skills', function (Blueprint $table) {
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('skill_id');
        });
        Schema::create('job_skills', function (Blueprint $table) {
            $table->unsignedInteger('job_id');
            $table->unsignedInteger('skill_id');
        });
        Schema::create('applications', function (Blueprint $table) {
            $table->increments('application_id');
            $table->unsignedInteger('job_id');
            $table->unsignedInteger('user_id');
        });
        Schema::create('recommendations', function (Blueprint $table) {
            $table->increments('recommendation_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('job_id');
            $table->string('algorithm')->nullable();
            $table->decimal('matching_score', 5, 2)->nullable();
            $table->decimal('cosine_similarity', 8, 6)->nullable();
            $table->decimal('tfidf_score', 8, 6)->nullable();
            $table->decimal('collaborative_score', 8, 6)->nullable();
            $table->unsignedInteger('cluster_id')->nullable();
            $table->dateTime('generated_at')->nullable();
            $table->unique(['user_id', 'job_id']);
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
            'user_id' => 8,
            'role_id' => 3,
            'full_name' => 'Test Seeker',
            'email' => 'seeker@example.test',
            'password' => 'unused',
            'account_status' => 'Active',
        ]);
        DB::table('job_seekers')->insert(['user_id' => 8, 'city' => 'Kathmandu', 'years_of_experience' => 2]);
        DB::table('companies')->insert(['company_id' => 4, 'company_name' => 'Example Company']);
        DB::table('categories')->insert(['category_id' => 5, 'category_name' => 'Technology']);
        DB::table('jobs')->insert([
            [
                'job_id' => 11,
                'company_id' => 4,
                'category_id' => 5,
                'job_title' => 'Excellent Match',
                'job_description' => 'PHP and Laravel engineering position',
                'job_status' => 'Open',
                'employment_type' => 'Full-Time',
                'workplace' => 'Onsite',
                'experience_level' => 'Mid',
                'minimum_experience' => 2,
                'minimum_salary' => 50000,
                'maximum_salary' => 60000,
                'currency' => 'NPR',
                'location' => 'Kathmandu',
                'application_deadline' => null,
            ],
            [
                'job_id' => 12,
                'company_id' => 4,
                'category_id' => 5,
                'job_title' => 'Remote Role',
                'job_description' => 'A role with fewer matching signals',
                'job_status' => 'Open',
                'employment_type' => 'Contract',
                'workplace' => 'Remote',
                'experience_level' => 'Senior',
                'minimum_experience' => 6,
                'minimum_salary' => null,
                'maximum_salary' => null,
                'currency' => 'NPR',
                'location' => 'Pokhara',
                'application_deadline' => null,
            ],
            [
                'job_id' => 13,
                'company_id' => 4,
                'category_id' => 5,
                'job_title' => 'Already Applied',
                'job_description' => 'Do not recommend applied jobs',
                'job_status' => 'Open',
                'employment_type' => 'Full-Time',
                'workplace' => 'Onsite',
                'experience_level' => 'Mid',
                'minimum_experience' => 2,
                'minimum_salary' => null,
                'maximum_salary' => null,
                'currency' => 'NPR',
                'location' => 'Kathmandu',
                'application_deadline' => null,
            ],
            [
                'job_id' => 14,
                'company_id' => 4,
                'category_id' => 5,
                'job_title' => 'Expired Role',
                'job_description' => 'No longer accepting applications',
                'job_status' => 'Open',
                'employment_type' => 'Full-Time',
                'workplace' => 'Onsite',
                'experience_level' => 'Mid',
                'minimum_experience' => 2,
                'minimum_salary' => null,
                'maximum_salary' => null,
                'currency' => 'NPR',
                'location' => 'Kathmandu',
                'application_deadline' => now()->subDay()->toDateString(),
            ],
            [
                'job_id' => 15,
                'company_id' => 4,
                'category_id' => 5,
                'job_title' => 'Closed Role',
                'job_description' => 'Closed job',
                'job_status' => 'Closed',
                'employment_type' => 'Full-Time',
                'workplace' => 'Onsite',
                'experience_level' => 'Mid',
                'minimum_experience' => 2,
                'minimum_salary' => null,
                'maximum_salary' => null,
                'currency' => 'NPR',
                'location' => 'Kathmandu',
                'application_deadline' => null,
            ],
        ]);
        DB::table('user_skills')->insert([
            ['user_id' => 8, 'skill_id' => 1],
            ['user_id' => 8, 'skill_id' => 2],
        ]);
        DB::table('job_skills')->insert([
            ['job_id' => 11, 'skill_id' => 1],
            ['job_id' => 11, 'skill_id' => 2],
        ]);
        DB::table('applications')->insert(['job_id' => 13, 'user_id' => 8]);
        DB::table('recommendations')->insert([
            'user_id' => 8,
            'job_id' => 15,
            'algorithm' => 'old',
            'matching_score' => 1,
        ]);
    }

    public function test_generation_replaces_old_matches_and_uses_the_existing_weighted_signals(): void
    {
        $this->actingAs($this->jobSeeker())
            ->post('/jobseeker/generate_recommendations.php')
            ->assertRedirect(route('jobseeker.recommendations'))
            ->assertSessionHas('success', '2 job recommendations generated.');

        $this->assertDatabaseHas('recommendations', ['user_id' => 8, 'job_id' => 11, 'matching_score' => 100]);
        $this->assertDatabaseHas('recommendations', ['user_id' => 8, 'job_id' => 12, 'matching_score' => 43.5]);
        $this->assertDatabaseMissing('recommendations', ['user_id' => 8, 'job_id' => 13]);
        $this->assertDatabaseMissing('recommendations', ['user_id' => 8, 'job_id' => 14]);
        $this->assertDatabaseMissing('recommendations', ['user_id' => 8, 'job_id' => 15]);
        $this->assertDatabaseHas('activity_logs', ['user_id' => 8, 'activity' => 'Generated 2 recommendations']);

        $this->actingAs($this->jobSeeker())
            ->get('/jobseeker/recommendations.php')
            ->assertOk()
            ->assertSee('Excellent Match')
            ->assertSee('100%')
            ->assertDontSee('Closed Role')
            ->assertDontSee('Expired Role');
    }

    public function test_generation_clears_previous_results_and_warns_when_no_open_jobs_are_available(): void
    {
        DB::table('jobs')->whereIn('job_id', [11, 12, 13])->update(['job_status' => 'Closed']);

        $this->actingAs($this->jobSeeker())
            ->post('/jobseeker/generate_recommendations.php')
            ->assertRedirect(route('jobseeker.recommendations'))
            ->assertSessionHas('warning');

        $this->assertDatabaseCount('recommendations', 0);
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
