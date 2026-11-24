<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NotificationWorkflowTest extends TestCase
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
            $table->string('title');
            $table->text('message');
            $table->string('notification_type');
            $table->boolean('is_read')->default(false);
            $table->dateTime('created_at');
        });

        DB::table('users')->insert([
            [
                'user_id' => 8,
                'role_id' => 3,
                'full_name' => 'Test Seeker',
                'email' => 'seeker@example.test',
                'password' => 'unused',
                'account_status' => 'Active',
            ],
            [
                'user_id' => 9,
                'role_id' => 3,
                'full_name' => 'Other Seeker',
                'email' => 'other@example.test',
                'password' => 'unused',
                'account_status' => 'Active',
            ],
        ]);
        DB::table('notifications')->insert([
            [
                'notification_id' => 1,
                'user_id' => 8,
                'title' => 'Application update',
                'message' => 'Your application was reviewed.',
                'notification_type' => 'Application',
                'is_read' => false,
                'created_at' => now()->subMinutes(3),
            ],
            [
                'notification_id' => 2,
                'user_id' => 8,
                'title' => 'Interview invitation',
                'message' => 'An interview has been scheduled.',
                'notification_type' => 'Interview',
                'is_read' => false,
                'created_at' => now()->subMinutes(2),
            ],
            [
                'notification_id' => 3,
                'user_id' => 8,
                'title' => 'Old system update',
                'message' => 'A previous platform update.',
                'notification_type' => 'System',
                'is_read' => true,
                'created_at' => now()->subMinute(),
            ],
            [
                'notification_id' => 4,
                'user_id' => 9,
                'title' => 'Private notification',
                'message' => 'This belongs to another seeker.',
                'notification_type' => 'Message',
                'is_read' => false,
                'created_at' => now(),
            ],
        ]);
    }

    public function test_job_seeker_can_filter_notifications_and_mark_only_owned_notifications_read(): void
    {
        $this->actingAs($this->jobSeeker())
            ->get(route('jobseeker.notifications', ['tab' => 'unread', 'type' => 'Application']))
            ->assertOk()
            ->assertSee('Application update')
            ->assertDontSee('Interview invitation')
            ->assertDontSee('Private notification')
            ->assertSee('Unread')
            ->assertSee('2');

        $this->actingAs($this->jobSeeker())
            ->post(route('jobseeker.notifications.read'), [
                'notification_id' => 1,
                'tab' => 'unread',
                'type' => 'Application',
            ])
            ->assertRedirect(route('jobseeker.notifications', ['tab' => 'unread', 'type' => 'Application']))
            ->assertSessionHas('success', 'Notification marked as read.');

        $this->assertDatabaseHas('notifications', ['notification_id' => 1, 'user_id' => 8, 'is_read' => 1]);
        $this->assertDatabaseHas('notifications', ['notification_id' => 4, 'user_id' => 9, 'is_read' => 0]);

        $this->actingAs($this->jobSeeker())
            ->post(route('jobseeker.notifications.read'), ['notification_id' => 4])
            ->assertSessionHas('danger', 'Notification not found.');
    }

    public function test_mark_all_read_updates_only_the_authenticated_users_unread_notifications(): void
    {
        $this->actingAs($this->jobSeeker())
            ->post(route('jobseeker.notifications.read-all'))
            ->assertRedirect(route('jobseeker.notifications'))
            ->assertSessionHas('success', '2 notifications marked as read.');

        $this->assertDatabaseHas('notifications', ['notification_id' => 1, 'is_read' => 1]);
        $this->assertDatabaseHas('notifications', ['notification_id' => 2, 'is_read' => 1]);
        $this->assertDatabaseHas('notifications', ['notification_id' => 4, 'is_read' => 0]);

        $this->actingAs($this->jobSeeker())
            ->post(route('jobseeker.notifications.read-all'))
            ->assertSessionHas('info', 'No unread notifications.');
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
