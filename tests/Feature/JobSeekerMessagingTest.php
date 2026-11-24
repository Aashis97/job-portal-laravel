<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class JobSeekerMessagingTest extends TestCase
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
            $table->string('profile_image')->nullable();
        });
        Schema::create('employers', function (Blueprint $table) {
            $table->increments('employer_id');
            $table->unsignedInteger('user_id');
        });
        Schema::create('jobs', function (Blueprint $table) {
            $table->increments('job_id');
            $table->unsignedInteger('employer_id');
        });
        Schema::create('applications', function (Blueprint $table) {
            $table->increments('application_id');
            $table->unsignedInteger('job_id');
            $table->unsignedInteger('user_id');
        });
        Schema::create('messages', function (Blueprint $table) {
            $table->increments('message_id');
            $table->unsignedInteger('sender_id');
            $table->unsignedInteger('receiver_id');
            $table->string('subject')->nullable();
            $table->text('message');
            $table->string('attachment')->nullable();
            $table->boolean('is_read')->default(false);
            $table->dateTime('sent_at');
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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->bigIncrements('log_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('activity');
            $table->string('module')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('device_info')->nullable();
            $table->dateTime('created_at')->nullable();
        });

        DB::table('users')->insert([
            [
                'user_id' => 8,
                'role_id' => 3,
                'full_name' => 'Test Seeker',
                'email' => 'seeker@example.test',
                'password' => 'unused',
                'account_status' => 'Active',
                'profile_image' => 'default.png',
            ],
            [
                'user_id' => 9,
                'role_id' => 2,
                'full_name' => 'Employer User',
                'email' => 'employer@example.test',
                'password' => 'unused',
                'account_status' => 'Active',
                'profile_image' => 'default.png',
            ],
            [
                'user_id' => 10,
                'role_id' => 3,
                'full_name' => 'Inactive User',
                'email' => 'inactive@example.test',
                'password' => 'unused',
                'account_status' => 'Inactive',
                'profile_image' => 'default.png',
            ],
        ]);
        DB::table('employers')->insert(['employer_id' => 3, 'user_id' => 9]);
        DB::table('jobs')->insert(['job_id' => 12, 'employer_id' => 3]);
        DB::table('applications')->insert(['job_id' => 12, 'user_id' => 8]);
        DB::table('messages')->insert([
            [
                'message_id' => 1,
                'sender_id' => 9,
                'receiver_id' => 8,
                'subject' => 'Interview',
                'message' => 'Can we schedule an interview?',
                'attachment' => null,
                'is_read' => false,
                'sent_at' => now()->subMinutes(3),
            ],
            [
                'message_id' => 2,
                'sender_id' => 8,
                'receiver_id' => 9,
                'subject' => 'Re: Interview',
                'message' => 'Yes, that works for me.',
                'attachment' => null,
                'is_read' => false,
                'sent_at' => now()->subMinutes(2),
            ],
        ]);
    }

    public function test_inbox_search_and_compose_recipients_follow_the_original_workflow(): void
    {
        $this->actingAs($this->jobSeeker())
            ->get(route('jobseeker.messages', ['q' => 'schedule']))
            ->assertOk()
            ->assertSee('Can we schedule an interview?')
            ->assertDontSee('Yes, that works for me.');

        $this->actingAs($this->jobSeeker())
            ->get(route('jobseeker.messages.compose'))
            ->assertOk()
            ->assertSee('Employer User')
            ->assertDontSee('Inactive User');
    }

    public function test_send_and_conversation_marking_are_scoped_and_create_message_notifications(): void
    {
        $this->actingAs($this->jobSeeker())
            ->post(route('jobseeker.messages.send'), [
                'receiver_id' => 9,
                'subject' => '',
                'message' => 'Thank you for reaching out.',
                'attachment' => '',
            ])
            ->assertRedirect(route('jobseeker.conversation', ['user' => 9]))
            ->assertSessionHas('success', 'Message sent successfully.');

        $this->assertDatabaseHas('messages', [
            'sender_id' => 8,
            'receiver_id' => 9,
            'subject' => '(no subject)',
            'message' => 'Thank you for reaching out.',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => 9,
            'title' => 'New Message',
            'notification_type' => 'Message',
        ]);

        $this->actingAs($this->jobSeeker())
            ->get(route('jobseeker.conversation', ['user' => 9]))
            ->assertOk()
            ->assertSee('Employer User')
            ->assertSee('Can we schedule an interview?')
            ->assertSee('Thank you for reaching out.');

        $this->assertDatabaseHas('messages', ['message_id' => 1, 'is_read' => 1]);
        $this->assertDatabaseHas('messages', ['message_id' => 2, 'is_read' => 0]);

        $this->actingAs($this->jobSeeker())
            ->get(route('jobseeker.message.view', ['id' => 2]))
            ->assertRedirect(route('jobseeker.conversation', ['user' => 9]));
    }

    public function test_live_chat_poll_and_send_only_return_messages_in_the_selected_thread(): void
    {
        $this->actingAs($this->jobSeeker())
            ->postJson(route('messages.chat'), [
                'action' => 'poll',
                'peer_id' => 9,
                'after_id' => 0,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('last_message_id', 2)
            ->assertJsonCount(2, 'messages');

        $this->actingAs($this->jobSeeker())
            ->postJson(route('messages.chat'), [
                'action' => 'send',
                'peer_id' => 9,
                'message' => 'A live reply',
                'subject' => 'Re: Interview',
                'attachment' => '',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message.message', 'A live reply');

        $this->assertDatabaseHas('messages', [
            'sender_id' => 8,
            'receiver_id' => 9,
            'message' => 'A live reply',
        ]);
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
