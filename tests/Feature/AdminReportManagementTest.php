<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminReportManagementTest extends TestCase
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
            $table->timestamp('updated_at')->nullable();
        });
        Schema::create('jobs', function (Blueprint $table) {
            $table->increments('job_id');
            $table->unsignedInteger('company_id')->nullable();
            $table->string('job_title');
            $table->string('job_status');
            $table->unsignedInteger('total_applications')->default(0);
            $table->timestamp('updated_at')->nullable();
        });
        Schema::create('applications', function (Blueprint $table) {
            $table->increments('application_id');
            $table->unsignedInteger('job_id');
            $table->unsignedInteger('user_id');
            $table->string('application_status');
            $table->timestamp('updated_at')->nullable();
        });
        Schema::create('interviews', function (Blueprint $table) {
            $table->increments('interview_id');
            $table->unsignedInteger('application_id');
        });
        Schema::create('saved_jobs', function (Blueprint $table) {
            $table->increments('saved_job_id');
            $table->unsignedInteger('job_id');
        });
        Schema::create('job_skills', function (Blueprint $table) {
            $table->increments('job_skill_id');
            $table->unsignedInteger('job_id');
        });
        Schema::create('reports', function (Blueprint $table) {
            $table->bigIncrements('report_id');
            $table->unsignedInteger('reporter_user_id')->nullable();
            $table->string('reporter_email')->nullable();
            $table->string('target_type');
            $table->unsignedBigInteger('target_id');
            $table->string('reason');
            $table->text('details')->nullable();
            $table->string('status')->default('open');
            $table->text('admin_notes')->nullable();
            $table->string('action_taken', 100)->nullable();
            $table->unsignedInteger('handled_by')->nullable();
            $table->timestamp('handled_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->bigIncrements('log_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('activity');
            $table->string('module')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('device_info')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        DB::table('users')->insert([
            ['user_id' => 1, 'role_id' => 1, 'full_name' => 'Admin One', 'email' => 'admin@example.test', 'password' => 'unused', 'account_status' => 'Active'],
            ['user_id' => 2, 'role_id' => 3, 'full_name' => 'Reporter Candidate', 'email' => 'reporter@example.test', 'password' => 'unused', 'account_status' => 'Active'],
        ]);
        DB::table('companies')->insert(['company_id' => 10, 'company_name' => 'Portal Co', 'status' => 'Approved']);
        DB::table('jobs')->insert([
            ['job_id' => 20, 'company_id' => 10, 'job_title' => 'Software Engineer', 'job_status' => 'Open', 'total_applications' => 1],
            ['job_id' => 21, 'company_id' => 10, 'job_title' => 'Accountant', 'job_status' => 'Open', 'total_applications' => 1],
        ]);
        DB::table('applications')->insert(['application_id' => 30, 'job_id' => 20, 'user_id' => 2, 'application_status' => 'Applied']);
        DB::table('interviews')->insert(['interview_id' => 40, 'application_id' => 30]);
        DB::table('saved_jobs')->insert(['saved_job_id' => 50, 'job_id' => 20]);
        DB::table('job_skills')->insert(['job_skill_id' => 60, 'job_id' => 20]);
        DB::table('reports')->insert([
            ['report_id' => 100, 'reporter_user_id' => 2, 'reporter_email' => null, 'target_type' => 'job', 'target_id' => 20, 'reason' => 'fraud', 'details' => 'Suspected scam listing', 'status' => 'open', 'created_at' => now(), 'updated_at' => now()],
            ['report_id' => 101, 'reporter_user_id' => null, 'reporter_email' => 'guest@example.test', 'target_type' => 'company', 'target_id' => 10, 'reason' => 'spam', 'details' => 'Repeated spam', 'status' => 'dismissed', 'created_at' => now()->subDays(10), 'updated_at' => now()],
            ['report_id' => 102, 'reporter_user_id' => 2, 'reporter_email' => null, 'target_type' => 'application', 'target_id' => 30, 'reason' => 'other', 'details' => 'Duplicate application', 'status' => 'open', 'created_at' => now(), 'updated_at' => now()],
            ['report_id' => 103, 'reporter_user_id' => 2, 'reporter_email' => null, 'target_type' => 'company', 'target_id' => 10, 'reason' => 'fraud', 'details' => 'Company verification concern', 'status' => 'open', 'created_at' => now(), 'updated_at' => now()],
            ['report_id' => 104, 'reporter_user_id' => 2, 'reporter_email' => null, 'target_type' => 'user', 'target_id' => 2, 'reason' => 'harassment', 'details' => 'User conduct report', 'status' => 'open', 'created_at' => now(), 'updated_at' => now()],
            ['report_id' => 105, 'reporter_user_id' => 2, 'reporter_email' => null, 'target_type' => 'job', 'target_id' => 21, 'reason' => 'spam', 'details' => 'Spam job listing', 'status' => 'open', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('saved_jobs')->insert(['saved_job_id' => 51, 'job_id' => 21]);
        DB::table('job_skills')->insert(['job_skill_id' => 61, 'job_id' => 21]);
    }

    public function test_admin_can_filter_report_queue_and_review_target_details(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.reports', ['status' => 'open', 'target_type' => 'job', 'q' => 'scam']))
            ->assertOk()
            ->assertSee('Software Engineer')
            ->assertSee('Fraud / Scam')
            ->assertSee('Reporter Candidate')
            ->assertDontSee('Accountant');

        $this->actingAs($this->admin())
            ->get(route('admin.report-details', ['id' => 100]))
            ->assertOk()
            ->assertSee('Suspected scam listing')
            ->assertSee('Portal Co')
            ->assertSee('Internal Notes')
            ->assertSee('Resolve with Action')
            ->assertSee('Delete the job');
    }

    public function test_report_notes_and_review_transitions_preserve_state_guards(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.report-action'), ['report_id' => 100, 'action' => 'save_notes', 'admin_notes' => '  Checked with the employer.  '])
            ->assertRedirect(route('admin.report-details', ['id' => 100]))
            ->assertSessionHas('success', 'Internal notes saved.');
        $this->assertDatabaseHas('reports', ['report_id' => 100, 'status' => 'open', 'admin_notes' => 'Checked with the employer.']);

        $this->actingAs($this->admin())
            ->post(route('admin.report-action'), ['report_id' => 100, 'action' => 'save_notes', 'admin_notes' => ''])
            ->assertSessionHas('success', 'Internal notes saved.');
        $this->assertDatabaseHas('reports', ['report_id' => 100, 'admin_notes' => null]);

        $this->actingAs($this->admin())
            ->post(route('admin.report-action'), ['report_id' => 100, 'action' => 'mark_in_review'])
            ->assertSessionHas('success', 'Report marked as in review.');
        $this->assertDatabaseHas('reports', ['report_id' => 100, 'status' => 'in_review', 'handled_by' => 1]);

        $this->actingAs($this->admin())
            ->post(route('admin.report-action'), ['report_id' => 100, 'action' => 'dismiss'])
            ->assertSessionHas('success', 'Report dismissed.');
        $this->assertDatabaseHas('reports', ['report_id' => 100, 'status' => 'dismissed', 'action_taken' => 'Dismissed — no violation found']);

        $this->actingAs($this->admin())
            ->post(route('admin.report-action'), ['report_id' => 100, 'action' => 'dismiss'])
            ->assertSessionHas('warning', 'This report is already closed.');
    }

    public function test_resolution_applies_follow_up_atomically_and_rejects_wrong_target_actions(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.report-action'), [
                'report_id' => 100,
                'action' => 'resolve_with_action',
                'follow_up' => 'close_job',
                'action_note' => 'Confirmed with employer',
            ])
            ->assertSessionHas('success');
        $this->assertDatabaseHas('jobs', ['job_id' => 20, 'job_status' => 'Closed']);
        $this->assertDatabaseHas('reports', [
            'report_id' => 100,
            'status' => 'resolved',
            'handled_by' => 1,
            'action_taken' => 'Closed job #20 — Confirmed with employer',
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.report-action'), [
                'report_id' => 102,
                'action' => 'resolve_with_action',
                'follow_up' => 'close_job',
            ])
            ->assertSessionHas('danger', 'Invalid follow-up action for this target.');
        $this->assertDatabaseHas('reports', ['report_id' => 102, 'status' => 'open']);

        $this->actingAs($this->admin())
            ->post(route('admin.report-action'), [
                'report_id' => 102,
                'action' => 'resolve_with_action',
                'follow_up' => 'delete_application',
            ])
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('applications', ['application_id' => 30]);
        $this->assertDatabaseMissing('interviews', ['application_id' => 30]);
        $this->assertDatabaseHas('jobs', ['job_id' => 20, 'total_applications' => 0]);

        $this->assertDatabaseHas('activity_logs', ['user_id' => 1, 'module' => 'Admin']);
    }

    public function test_company_user_and_job_follow_ups_handle_optional_schema_fields_safely(): void
    {
        $this->actingAs($this->admin())->post(route('admin.report-action'), [
            'report_id' => 103,
            'action' => 'resolve_with_action',
            'follow_up' => 'reject_company',
        ])->assertSessionHas('success');
        $this->assertDatabaseHas('companies', ['company_id' => 10, 'status' => 'Rejected']);

        $this->actingAs($this->admin())->post(route('admin.report-action'), [
            'report_id' => 104,
            'action' => 'resolve_with_action',
            'follow_up' => 'block_user',
        ])->assertSessionHas('success');
        $this->assertDatabaseHas('users', ['user_id' => 2, 'account_status' => 'Blocked']);

        $this->actingAs($this->admin())->post(route('admin.report-action'), [
            'report_id' => 105,
            'action' => 'resolve_with_action',
            'follow_up' => 'delete_job',
        ])->assertSessionHas('success');
        $this->assertDatabaseMissing('jobs', ['job_id' => 21]);
        $this->assertDatabaseMissing('saved_jobs', ['job_id' => 21]);
        $this->assertDatabaseMissing('job_skills', ['job_id' => 21]);
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
