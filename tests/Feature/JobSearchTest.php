<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class JobSearchTest extends TestCase
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
        Schema::create('categories', function (Blueprint $table) {
            $table->increments('category_id');
            $table->string('category_name');
            $table->string('status');
        });
        Schema::create('jobs', function (Blueprint $table) {
            $table->increments('job_id');
            $table->unsignedInteger('company_id');
            $table->unsignedInteger('category_id');
            $table->string('job_title');
            $table->string('job_status');
            $table->string('employment_type');
            $table->string('workplace');
            $table->string('experience_level');
            $table->string('location')->nullable();
            $table->decimal('minimum_salary', 12, 2)->nullable();
            $table->decimal('maximum_salary', 12, 2)->nullable();
            $table->string('currency')->nullable();
            $table->dateTime('created_at')->nullable();
        });
        Schema::create('saved_jobs', function (Blueprint $table) {
            $table->increments('saved_job_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('job_id');
        });
        Schema::create('applications', function (Blueprint $table) {
            $table->increments('application_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('job_id');
        });

        DB::table('users')->insert([
            'user_id' => 8,
            'role_id' => 3,
            'full_name' => 'Test Seeker',
            'email' => 'seeker@example.test',
            'password' => 'irrelevant',
            'account_status' => 'Active',
        ]);
        DB::table('companies')->insert([
            ['company_id' => 1, 'company_name' => 'Example Company'],
            ['company_id' => 2, 'company_name' => 'Other Company'],
        ]);
        DB::table('categories')->insert([
            ['category_id' => 1, 'category_name' => 'Technology', 'status' => 'Active'],
            ['category_id' => 2, 'category_name' => 'Design', 'status' => 'Active'],
        ]);
        DB::table('jobs')->insert([
            [
                'job_id' => 11,
                'company_id' => 1,
                'category_id' => 1,
                'job_title' => 'PHP Developer',
                'job_status' => 'Open',
                'employment_type' => 'Full-Time',
                'workplace' => 'Remote',
                'experience_level' => 'Junior',
                'location' => 'Kathmandu',
                'currency' => 'NPR',
            ],
            [
                'job_id' => 12,
                'company_id' => 2,
                'category_id' => 2,
                'job_title' => 'Closed Developer Role',
                'job_status' => 'Closed',
                'employment_type' => 'Full-Time',
                'workplace' => 'Onsite',
                'experience_level' => 'Senior',
                'location' => 'Pokhara',
                'currency' => 'NPR',
            ],
        ]);
    }

    public function test_search_returns_open_jobs_matching_filters_only(): void
    {
        $user = new User([
            'user_id' => 8,
            'role_id' => 3,
            'full_name' => 'Test Seeker',
            'email' => 'seeker@example.test',
            'account_status' => 'Active',
        ]);
        $user->exists = true;

        $response = $this->actingAs($user)
            ->get('/jobseeker/jobs.php?q=Developer&category_id=1');

        $response->assertOk()
            ->assertSee('PHP Developer')
            ->assertDontSee('Closed Developer Role')
            ->assertSee('Example Company');
    }
}
