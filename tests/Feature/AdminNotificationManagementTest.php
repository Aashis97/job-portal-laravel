<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminNotificationManagementTest extends TestCase
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
        Schema::create('notifications', function (Blueprint $table) {
            $table->increments('notification_id');
            $table->unsignedInteger('user_id');
            $table->string('title', 200);
            $table->text('message');
            $table->string('notification_type')->default('System');
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->useCurrent();
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
            ['user_id' => 2, 'role_id' => 2, 'full_name' => 'Employer One', 'email' => 'employer@example.test', 'password' => 'unused', 'account_status' => 'Active'],
            ['user_id' => 3, 'role_id' => 3, 'full_name' => 'Candidate One', 'email' => 'candidate@example.test', 'password' => 'unused', 'account_status' => 'Active'],
            ['user_id' => 4, 'role_id' => 3, 'full_name' => 'Inactive Candidate', 'email' => 'inactive@example.test', 'password' => 'unused', 'account_status' => 'Inactive'],
        ]);
        DB::table('notifications')->insert([
            ['notification_id' => 10, 'user_id' => 1, 'title' => 'Admin notice', 'message' => 'Review the platform report', 'notification_type' => 'System', 'is_read' => false, 'created_at' => now()],
            ['notification_id' => 11, 'user_id' => 1, 'title' => 'Earlier notice', 'message' => 'A previous item', 'notification_type' => 'Job', 'is_read' => true, 'created_at' => now()->subDays(10)],
            ['notification_id' => 12, 'user_id' => 2, 'title' => 'Employer private notice', 'message' => 'Not for admin', 'notification_type' => 'Application', 'is_read' => false, 'created_at' => now()],
        ]);
    }

    public function test_admin_inbox_filters_own_notifications_and_actions_are_user_scoped(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.notifications', ['tab' => 'unread', 'type' => 'System', 'q' => 'platform']))
            ->assertOk()
            ->assertSee('Admin notice')
            ->assertDontSee('Earlier notice')
            ->assertDontSee('Employer private notice');

        $this->actingAs($this->admin())
            ->post(route('admin.notification-action'), [
                'action' => 'mark_read',
                'notification_id' => 12,
                'tab' => 'unread',
                'page' => 2,
            ])
            ->assertRedirect(route('admin.notifications', ['tab' => 'unread', 'page' => 2]))
            ->assertSessionHas('danger', 'Notification not found.');
        $this->assertDatabaseHas('notifications', ['notification_id' => 12, 'is_read' => 0]);

        $this->actingAs($this->admin())
            ->post(route('admin.notification-action'), ['action' => 'mark_read', 'notification_id' => 10])
            ->assertSessionHas('success', 'Notification marked as read.');
        $this->assertDatabaseHas('notifications', ['notification_id' => 10, 'is_read' => 1]);

        DB::table('notifications')->insert([
            'notification_id' => 13,
            'user_id' => 1,
            'title' => 'Unread admin item',
            'message' => 'Needs attention',
            'notification_type' => 'Reminder',
            'is_read' => false,
            'created_at' => now(),
        ]);
        $this->actingAs($this->admin())
            ->post(route('admin.notification-action'), ['action' => 'mark_all_read'])
            ->assertSessionHas('success', '1 notification marked as read.');
        $this->assertDatabaseHas('notifications', ['notification_id' => 13, 'is_read' => 1]);
        $this->assertDatabaseHas('notifications', ['notification_id' => 12, 'is_read' => 0]);

        $this->actingAs($this->admin())
            ->post(route('admin.notification-action'), ['action' => 'delete', 'notification_id' => 11])
            ->assertSessionHas('success', 'Notification deleted.');
        $this->assertDatabaseMissing('notifications', ['notification_id' => 11]);
    }

    public function test_broadcast_sends_to_active_audiences_or_one_specific_user(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.broadcast'))
            ->assertOk()
            ->assertSee('Send Broadcast')
            ->assertSee('3 active recipients');

        $this->actingAs($this->admin())
            ->post(route('admin.broadcast.send'), [
                'audience' => 'employers',
                'notif_type' => 'Job',
                'title' => 'Service update',
                'message' => 'New job guidance is available.',
            ])
            ->assertRedirect(route('admin.notifications'))
            ->assertSessionHas('success', 'Broadcast delivered to 1 recipient.');
        $this->assertDatabaseHas('notifications', [
            'user_id' => 2,
            'title' => 'Service update',
            'notification_type' => 'Job',
            'is_read' => 0,
        ]);
        $this->assertDatabaseMissing('notifications', ['user_id' => 3, 'title' => 'Service update']);
        $this->assertDatabaseMissing('notifications', ['user_id' => 4, 'title' => 'Service update']);

        $this->actingAs($this->admin())
            ->post(route('admin.broadcast.send'), [
                'audience' => 'specific',
                'specific_email' => 'candidate@example.test',
                'notif_type' => 'Reminder',
                'title' => 'One recipient',
                'message' => 'This is for one user.',
            ])
            ->assertSessionHas('success', 'Broadcast delivered to 1 recipient.');
        $this->assertDatabaseHas('notifications', ['user_id' => 3, 'title' => 'One recipient', 'notification_type' => 'Reminder']);
    }

    public function test_broadcast_rejects_missing_specific_recipient_and_invalid_notification_data(): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.broadcast'))
            ->post(route('admin.broadcast.send'), [
                'audience' => 'specific',
                'specific_email' => 'missing@example.test',
                'notif_type' => 'System',
                'title' => 'Missing recipient',
                'message' => 'Must not be delivered.',
            ])
            ->assertRedirect(route('admin.broadcast'))
            ->assertSessionHas('danger', 'No user found with that email address.');
        $this->assertDatabaseMissing('notifications', ['title' => 'Missing recipient']);

        $this->actingAs($this->admin())
            ->from(route('admin.broadcast'))
            ->post(route('admin.broadcast.send'), [
                'audience' => 'all_users',
                'notif_type' => 'Invalid',
                'title' => '',
                'message' => 'A message',
            ])
            ->assertRedirect(route('admin.broadcast'))
            ->assertSessionHasErrors(['notif_type', 'title']);
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
