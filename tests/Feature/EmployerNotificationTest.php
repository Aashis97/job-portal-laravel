<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmployerNotificationTest extends TestCase
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
            ['user_id' => 6, 'role_id' => 2, 'full_name' => 'Employer One', 'email' => 'employer@example.test', 'password' => 'unused', 'account_status' => 'Active'],
            ['user_id' => 7, 'role_id' => 2, 'full_name' => 'Employer Two', 'email' => 'other@example.test', 'password' => 'unused', 'account_status' => 'Active'],
        ]);
        DB::table('notifications')->insert([
            ['notification_id' => 1, 'user_id' => 6, 'title' => 'Old application', 'message' => 'A candidate applied.', 'notification_type' => 'Application', 'is_read' => 0, 'created_at' => now()->subMinutes(10)],
            ['notification_id' => 2, 'user_id' => 6, 'title' => 'Interview update', 'message' => 'An interview was updated.', 'notification_type' => 'Interview', 'is_read' => 1, 'created_at' => now()->subMinutes(5)],
            ['notification_id' => 3, 'user_id' => 7, 'title' => 'Private notification', 'message' => 'This belongs to another employer.', 'notification_type' => 'Message', 'is_read' => 0, 'created_at' => now()->subMinute()],
            ['notification_id' => 4, 'user_id' => 6, 'title' => 'New applicant', 'message' => 'A new applicant is waiting.', 'notification_type' => 'Application', 'is_read' => 0, 'created_at' => now()],
        ]);
    }

    public function test_employer_notification_page_and_json_feed_are_user_scoped_and_limited(): void
    {
        $this->actingAs($this->employer())
            ->get(route('employer.notifications'))
            ->assertOk()
            ->assertSee('Notifications')
            ->assertSee('New applicant')
            ->assertSee('Old application')
            ->assertDontSee('Private notification')
            ->assertSee('Mark All as Read');

        $this->actingAs($this->employer())
            ->getJson(route('employer.notifications.get', ['limit' => 50]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('unread_count', 2)
            ->assertJsonPath('total', 3)
            ->assertJsonPath('notifications.0.notification_id', 4)
            ->assertJsonMissing(['title' => 'Private notification']);

        $this->actingAs($this->employer())
            ->getJson(route('employer.notifications.get', ['limit' => 1]))
            ->assertJsonPath('total', 1)
            ->assertJsonPath('notifications.0.title', 'New applicant');
    }

    public function test_employer_notification_actions_only_change_notifications_owned_by_the_employer(): void
    {
        $this->actingAs($this->employer())
            ->postJson(route('employer.notifications.read'), ['notification_id' => 1])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('unread_count', 1);
        $this->assertDatabaseHas('notifications', ['notification_id' => 1, 'user_id' => 6, 'is_read' => 1]);

        $this->actingAs($this->employer())
            ->postJson(route('employer.notifications.read'), ['notification_id' => 3])
            ->assertNotFound()
            ->assertJsonPath('success', false);
        $this->actingAs($this->employer())
            ->postJson(route('employer.notifications.read'), ['notification_id' => 'invalid'])
            ->assertUnprocessable();
        $this->assertDatabaseHas('notifications', ['notification_id' => 3, 'user_id' => 7, 'is_read' => 0]);

        $this->actingAs($this->employer())
            ->postJson(route('employer.notifications.read-all'))
            ->assertOk()
            ->assertJsonPath('unread_count', 0);
        $this->assertDatabaseHas('notifications', ['notification_id' => 4, 'user_id' => 6, 'is_read' => 1]);
        $this->assertDatabaseHas('notifications', ['notification_id' => 3, 'user_id' => 7, 'is_read' => 0]);

        $this->actingAs($this->employer())
            ->postJson(route('employer.notifications.delete'), ['notification_id' => 1])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('unread_count', 0);
        $this->assertDatabaseMissing('notifications', ['notification_id' => 1]);

        $this->actingAs($this->employer())
            ->postJson(route('employer.notifications.delete'), ['notification_id' => 3])
            ->assertNotFound();
        $this->assertDatabaseHas('notifications', ['notification_id' => 3, 'user_id' => 7]);
    }

    private function employer(): User
    {
        $user = new User([
            'role_id' => 2,
            'full_name' => 'Employer One',
            'email' => 'employer@example.test',
            'account_status' => 'Active',
        ]);
        $user->setAttribute('user_id', 6);
        $user->exists = true;

        return $user;
    }
}
