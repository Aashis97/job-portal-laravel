<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmployerCompanyProfileTest extends TestCase
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
            $table->string('company_name', 150);
            $table->string('company_email', 150)->unique();
            $table->string('company_phone', 20)->nullable();
            $table->string('website', 255)->nullable();
            $table->text('description')->nullable();
            $table->text('address')->nullable();
            $table->string('logo')->default('company.png');
            $table->string('status')->default('Pending');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
        Schema::create('employers', function (Blueprint $table) {
            $table->increments('employer_id');
            $table->unsignedInteger('user_id')->unique();
            $table->unsignedInteger('company_id')->nullable();
            $table->string('designation', 100)->nullable();
            $table->string('department', 100)->nullable();
            $table->boolean('is_primary_contact')->default(true);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        DB::table('users')->insert([
            'user_id' => 6,
            'role_id' => 2,
            'full_name' => 'Employer Test',
            'email' => 'employer@example.test',
            'password' => 'unused',
            'account_status' => 'Active',
        ]);
        DB::table('companies')->insert([
            'company_id' => 10,
            'company_name' => 'Old Company',
            'company_email' => 'old@company.test',
            'company_phone' => null,
            'website' => null,
            'description' => 'Old description',
            'address' => null,
            'logo' => 'company.png',
            'status' => 'Pending',
        ]);
        DB::table('employers')->insert([
            'employer_id' => 3,
            'user_id' => 6,
            'company_id' => 10,
            'designation' => 'Recruiter',
            'department' => 'People',
        ]);
    }

    public function test_employer_can_view_and_update_owned_company_and_contact_details(): void
    {
        $this->actingAs($this->employer())
            ->get(route('employer.company-profile'))
            ->assertOk()
            ->assertSee('Old Company')
            ->assertSee('old@company.test');

        $this->actingAs($this->employer())
            ->post(route('employer.company-profile.update'), [
                'company_name' => 'Updated Company',
                'company_email' => 'contact@company.test',
                'company_phone' => '9812345678',
                'company_website' => 'https://company.test',
                'company_description' => 'A company profile description.',
                'company_address' => 'Kathmandu',
                'designation' => 'Hiring Manager',
                'department' => 'Talent',
            ])
            ->assertRedirect(route('employer.company-profile'))
            ->assertSessionHas('company_profile_success', 'Company profile updated successfully.');

        $this->assertDatabaseHas('companies', [
            'company_id' => 10,
            'company_name' => 'Updated Company',
            'company_email' => 'contact@company.test',
            'website' => 'https://company.test',
            'description' => 'A company profile description.',
        ]);
        $this->assertDatabaseHas('employers', [
            'employer_id' => 3,
            'designation' => 'Hiring Manager',
            'department' => 'Talent',
        ]);
    }

    public function test_rejected_company_resubmission_resets_verification_state(): void
    {
        DB::table('companies')->where('company_id', 10)->update([
            'status' => 'Rejected',
        ]);

        $this->actingAs($this->employer())
            ->post(route('employer.company-profile.update'), [
                'company_name' => 'Updated Company',
                'company_email' => 'old@company.test',
                'submit_for_verification' => '1',
            ])
            ->assertRedirect(route('employer.company-profile'))
            ->assertSessionHas('company_profile_success', 'Company profile updated and resubmitted for verification.');

        $this->assertDatabaseHas('companies', [
            'company_id' => 10,
            'status' => 'Pending',
        ]);
    }

    public function test_company_email_conflicts_are_rejected_without_writing(): void
    {
        DB::table('companies')->insert([
            'company_id' => 11,
            'company_name' => 'Other Company',
            'company_email' => 'other@company.test',
            'status' => 'Pending',
        ]);

        $this->actingAs($this->employer())
            ->from(route('employer.company-profile'))
            ->post(route('employer.company-profile.update'), [
                'company_name' => 'Should Not Save',
                'company_email' => 'other@company.test',
            ])
            ->assertRedirect(route('employer.company-profile'))
            ->assertSessionHasErrors('company_email');

        $this->assertDatabaseHas('companies', ['company_id' => 10, 'company_name' => 'Old Company']);
    }

    public function test_employer_without_a_company_can_create_and_attach_one(): void
    {
        DB::table('employers')->where('employer_id', 3)->update(['company_id' => null]);

        $this->actingAs($this->employer())
            ->post(route('employer.company-profile.update'), [
                'company_name' => 'New Company',
                'company_email' => 'new@company.test',
            ])
            ->assertRedirect(route('employer.company-profile'));

        $companyId = DB::table('companies')->where('company_email', 'new@company.test')->value('company_id');
        $this->assertNotNull($companyId);
        $this->assertDatabaseHas('employers', ['employer_id' => 3, 'company_id' => $companyId]);
    }

    private function employer(): User
    {
        $user = new User([
            'role_id' => 2,
            'full_name' => 'Employer Test',
            'email' => 'employer@example.test',
            'account_status' => 'Active',
        ]);
        $user->setAttribute('user_id', 6);
        $user->exists = true;

        return $user;
    }
}
