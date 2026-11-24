<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminTaxonomyManagementTest extends TestCase
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
        Schema::create('categories', function (Blueprint $table) {
            $table->increments('category_id');
            $table->string('category_name')->unique();
            $table->text('category_description')->nullable();
            $table->string('status')->default('Active');
        });
        Schema::create('jobs', function (Blueprint $table) {
            $table->increments('job_id');
            $table->unsignedInteger('category_id');
        });
        Schema::create('skills', function (Blueprint $table) {
            $table->increments('skill_id');
            $table->string('skill_name')->unique();
            $table->string('skill_category')->nullable();
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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->increments('log_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('activity');
            $table->string('module')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('device_info')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        DB::table('users')->insert([
            'user_id' => 1,
            'role_id' => 1,
            'full_name' => 'Admin One',
            'email' => 'admin@example.test',
            'password' => 'unused',
            'account_status' => 'Active',
        ]);
        DB::table('categories')->insert([
            'category_id' => 10,
            'category_name' => 'Technology',
            'category_description' => 'Technology jobs',
            'status' => 'Active',
        ]);
        DB::table('jobs')->insert(['job_id' => 20, 'category_id' => 10]);
        DB::table('skills')->insert([
            ['skill_id' => 30, 'skill_name' => 'Laravel', 'skill_category' => 'Backend'],
            ['skill_id' => 31, 'skill_name' => 'Unused skill', 'skill_category' => null],
        ]);
        DB::table('job_skills')->insert(['job_id' => 20, 'skill_id' => 30]);
    }

    public function test_admin_can_view_and_manage_categories_and_skills(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.taxonomy'))
            ->assertOk()
            ->assertSee('Technology')
            ->assertSee('1 jobs')
            ->assertSee('Laravel');

        $this->actingAs($this->admin())
            ->post(route('admin.taxonomy.update'), [
                'action' => 'category_create',
                'category_name' => '  Design  ',
                'category_description' => 'Creative roles',
                'status' => 'Active',
            ])
            ->assertRedirect(route('admin.taxonomy'))
            ->assertSessionHas('success', 'Category added.');
        $this->assertDatabaseHas('categories', ['category_name' => 'Design', 'category_description' => 'Creative roles']);
        $this->assertDatabaseHas('activity_logs', ['user_id' => 1, 'activity' => 'Created job category #11', 'module' => 'Admin']);

        $this->actingAs($this->admin())
            ->post(route('admin.taxonomy.update'), [
                'action' => 'category_update',
                'category_id' => 10,
                'category_name' => 'Technology & IT',
                'category_description' => '',
                'status' => 'Inactive',
            ])
            ->assertSessionHas('success', 'Category updated.');
        $this->assertDatabaseHas('categories', [
            'category_id' => 10,
            'category_name' => 'Technology & IT',
            'category_description' => null,
            'status' => 'Inactive',
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.taxonomy.update'), [
                'action' => 'skill_create',
                'skill_name' => '  PHP  ',
                'skill_category' => 'Backend',
            ])
            ->assertSessionHas('success', 'Skill added.');
        $this->assertDatabaseHas('skills', ['skill_name' => 'PHP', 'skill_category' => 'Backend']);
    }

    public function test_duplicate_names_are_rejected_and_skills_in_use_cannot_be_deleted(): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.taxonomy'))
            ->post(route('admin.taxonomy.update'), [
                'action' => 'category_create',
                'category_name' => ' Technology ',
                'status' => 'Active',
            ])
            ->assertRedirect(route('admin.taxonomy'))
            ->assertSessionHasErrors('category_name');

        $this->actingAs($this->admin())
            ->post(route('admin.taxonomy.update'), ['action' => 'skill_delete', 'skill_id' => 30])
            ->assertSessionHas('danger', 'This skill is assigned to jobs or candidate profiles. Remove those assignments before deleting it.');
        $this->assertDatabaseHas('skills', ['skill_id' => 30]);

        $this->actingAs($this->admin())
            ->post(route('admin.taxonomy.update'), ['action' => 'skill_delete', 'skill_id' => 31])
            ->assertSessionHas('success', 'Skill deleted.');
        $this->assertDatabaseMissing('skills', ['skill_id' => 31]);
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
