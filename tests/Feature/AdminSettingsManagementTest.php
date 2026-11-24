<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminSettingsManagementTest extends TestCase
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
        Schema::create('settings', function (Blueprint $table) {
            $table->increments('setting_id');
            $table->string('setting_key')->unique();
            $table->text('setting_value')->nullable();
            $table->string('setting_type')->default('string');
            $table->string('setting_group')->default('general');
            $table->string('label');
            $table->string('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamp('updated_at')->nullable();
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
        DB::table('settings')->insert([
            ['setting_key' => 'site_name', 'setting_value' => 'Job Portal', 'setting_type' => 'string', 'setting_group' => 'general', 'label' => 'Site Name', 'sort_order' => 10],
            ['setting_key' => 'contact_email', 'setting_value' => 'support@example.test', 'setting_type' => 'email', 'setting_group' => 'general', 'label' => 'Contact Email', 'sort_order' => 20],
            ['setting_key' => 'maintenance_mode', 'setting_value' => '0', 'setting_type' => 'bool', 'setting_group' => 'general', 'label' => 'Maintenance Mode', 'sort_order' => 30],
            ['setting_key' => 'job_auto_expire_days', 'setting_value' => '60', 'setting_type' => 'int', 'setting_group' => 'jobs', 'label' => 'Auto-Expire Jobs After (Days)', 'sort_order' => 10],
            ['setting_key' => 'require_job_approval', 'setting_value' => '0', 'setting_type' => 'bool', 'setting_group' => 'jobs', 'label' => 'Require Admin Job Approval', 'sort_order' => 20],
        ]);
    }

    public function test_admin_settings_page_filters_by_tab_and_saves_typed_values(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.settings', ['tab' => 'jobs']))
            ->assertOk()
            ->assertSee('Auto-Expire Jobs After (Days)')
            ->assertDontSee('Contact Email');

        $this->actingAs($this->admin())
            ->post(route('admin.settings.update'), [
                'tab' => 'jobs',
                'setting' => [
                    'job_auto_expire_days' => ' 90 ',
                    'require_job_approval' => '1',
                    'site_name' => 'Should be ignored',
                ],
            ])
            ->assertRedirect(route('admin.settings', ['tab' => 'jobs']))
            ->assertSessionHas('success', '2 settings saved.');

        $this->assertDatabaseHas('settings', [
            'setting_key' => 'job_auto_expire_days',
            'setting_value' => '90',
            'updated_by' => 1,
        ]);
        $this->assertDatabaseHas('settings', ['setting_key' => 'require_job_approval', 'setting_value' => '1']);
        $this->assertDatabaseHas('settings', ['setting_key' => 'site_name', 'setting_value' => 'Job Portal']);
        $this->assertDatabaseHas('activity_logs', ['user_id' => 1, 'module' => 'Admin']);
    }

    public function test_invalid_setting_rolls_back_the_whole_group_and_unknown_tab_uses_general(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.settings', ['tab' => 'unknown']))
            ->assertOk()
            ->assertSee('Site Name')
            ->assertDontSee('Auto-Expire Jobs After (Days)');

        $this->actingAs($this->admin())
            ->post(route('admin.settings.update'), [
                'tab' => 'jobs',
                'setting' => [
                    'job_auto_expire_days' => '90',
                    'require_job_approval' => ['invalid'],
                ],
            ])
            ->assertRedirect(route('admin.settings', ['tab' => 'jobs']))
            ->assertSessionHas('danger', 'Invalid value for require_job_approval.');

        $this->assertDatabaseHas('settings', ['setting_key' => 'job_auto_expire_days', 'setting_value' => '60']);
        $this->assertDatabaseHas('settings', ['setting_key' => 'require_job_approval', 'setting_value' => '0']);
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
