<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminCompanyManagementTest extends TestCase
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
            $table->string('company_phone')->nullable();
            $table->string('website')->nullable();
            $table->string('industry')->nullable();
            $table->string('company_size')->nullable();
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('founded_year')->nullable();
            $table->string('status');
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('employers', function (Blueprint $table) {
            $table->increments('employer_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('company_id');
            $table->boolean('is_primary_contact')->default(true);
        });
        Schema::create('jobs', function (Blueprint $table) {
            $table->increments('job_id');
            $table->unsignedInteger('company_id');
            $table->string('job_status');
        });
        DB::table('users')->insert([
            ['user_id' => 1, 'role_id' => 1, 'full_name' => 'Admin One', 'email' => 'admin@example.test', 'password' => 'unused', 'account_status' => 'Active'],
            ['user_id' => 2, 'role_id' => 2, 'full_name' => 'Employer One', 'email' => 'employer@example.test', 'password' => 'unused', 'account_status' => 'Active'],
        ]);
        DB::table('companies')->insert([
            ['company_id' => 10, 'company_name' => 'Pending Company', 'company_email' => 'hello@pending.test', 'industry' => 'Technology', 'city' => 'Kathmandu', 'status' => 'Pending', 'created_at' => now()],
            ['company_id' => 11, 'company_name' => 'Approved Company', 'company_email' => 'hello@approved.test', 'industry' => 'Finance', 'city' => 'Pokhara', 'status' => 'Approved', 'created_at' => now()],
        ]);
        DB::table('employers')->insert(['employer_id' => 20, 'user_id' => 2, 'company_id' => 10, 'is_primary_contact' => true]);
        DB::table('jobs')->insert([
            ['job_id' => 100, 'company_id' => 10, 'job_status' => 'Open'],
            ['job_id' => 101, 'company_id' => 10, 'job_status' => 'Draft'],
        ]);
    }

    public function test_admin_can_filter_and_review_companies(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.companies', ['status' => 'Pending', 'q' => 'Kathmandu']))
            ->assertOk()
            ->assertSee('Pending Company')
            ->assertSee('Employer One')
            ->assertDontSee('Approved Company')
            ->assertSee('1 open', false);

        $this->actingAs($this->admin())
            ->get(route('admin.company-details', ['id' => 10]))
            ->assertOk()
            ->assertSee('Technology')
            ->assertSee('Verification actions')
            ->assertSee('Approve company');
    }

    public function test_admin_company_approval_rejection_and_state_guards(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.company-status.update'), ['company_id' => 10, 'action' => 'approve'])
            ->assertRedirect(route('admin.company-details', ['id' => 10]))
            ->assertSessionHas('success', 'Company approved successfully.');
        $this->assertDatabaseHas('companies', ['company_id' => 10, 'status' => 'Approved']);

        $this->actingAs($this->admin())
            ->from(route('admin.company-details', ['id' => 10]))
            ->post(route('admin.company-status.update'), ['company_id' => 10, 'action' => 'reject'])
            ->assertRedirect(route('admin.company-details', ['id' => 10]))
            ->assertSessionHasErrors('reason');

        $this->actingAs($this->admin())
            ->post(route('admin.company-status.update'), ['company_id' => 10, 'action' => 'reject', 'reason' => 'Missing registration documents.'])
            ->assertSessionHas('success', 'Company rejected successfully.');
        $this->assertDatabaseHas('companies', ['company_id' => 10, 'status' => 'Rejected']);
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
