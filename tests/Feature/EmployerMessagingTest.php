<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmployerMessagingTest extends TestCase
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
            $table->string('profile_image')->default('default.png');
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
            ['user_id' => 6, 'role_id' => 2, 'full_name' => 'Employer One', 'email' => 'employer@example.test', 'password' => 'unused', 'account_status' => 'Active', 'profile_image' => 'default.png'],
            ['user_id' => 7, 'role_id' => 3, 'full_name' => 'Candidate One', 'email' => 'candidate@example.test', 'password' => 'unused', 'account_status' => 'Active', 'profile_image' => 'default.png'],
            ['user_id' => 8, 'role_id' => 1, 'full_name' => 'Admin One', 'email' => 'admin@example.test', 'password' => 'unused', 'account_status' => 'Active', 'profile_image' => 'default.png'],
            ['user_id' => 9, 'role_id' => 3, 'full_name' => 'Inactive Candidate', 'email' => 'inactive@example.test', 'password' => 'unused', 'account_status' => 'Inactive', 'profile_image' => 'default.png'],
        ]);
        DB::table('messages')->insert([
            ['message_id' => 1, 'sender_id' => 7, 'receiver_id' => 6, 'subject' => 'Project application', 'message' => 'I am interested in the role.', 'attachment' => null, 'is_read' => 0, 'sent_at' => now()->subMinutes(2)],
            ['message_id' => 2, 'sender_id' => 6, 'receiver_id' => 7, 'subject' => 'Project application', 'message' => 'Thank you for applying.', 'attachment' => null, 'is_read' => 0, 'sent_at' => now()->subMinute()],
        ]);
    }

    public function test_employer_inbox_and_compose_follow_the_legacy_recruitment_workflow(): void
    {
        $this->actingAs($this->employer())
            ->get(route('employer.messages', ['q' => 'interested']))
            ->assertOk()
            ->assertSee('Messages')
            ->assertSee('Candidate One')
            ->assertSee('I am interested in the role.')
            ->assertDontSee('Thank you for applying.');

        $this->actingAs($this->employer())
            ->get(route('employer.messages.compose'))
            ->assertOk()
            ->assertSee('Candidate One')
            ->assertSee('Admin One')
            ->assertDontSee('Inactive Candidate');
    }

    public function test_employer_can_send_messages_and_conversation_reads_only_incoming_messages(): void
    {
        $this->actingAs($this->employer())
            ->post(route('employer.messages.send'), [
                'receiver_id' => 7,
                'subject' => '',
                'message' => 'Could you attend an interview?',
                'attachment' => '',
            ])
            ->assertRedirect(route('employer.conversation', ['user' => 7]))
            ->assertSessionHas('success', 'Message sent successfully.');

        $this->assertDatabaseHas('messages', [
            'sender_id' => 6,
            'receiver_id' => 7,
            'subject' => '(no subject)',
            'message' => 'Could you attend an interview?',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => 7,
            'title' => 'New Message',
            'notification_type' => 'Message',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => 6,
            'module' => 'Employer',
        ]);

        $this->actingAs($this->employer())
            ->get(route('employer.conversation', ['user' => 7]))
            ->assertOk()
            ->assertSee('Candidate One')
            ->assertSee('I am interested in the role.')
            ->assertSee('Could you attend an interview?');
        $this->assertDatabaseHas('messages', ['message_id' => 1, 'is_read' => 1]);
        $this->assertDatabaseHas('messages', ['message_id' => 2, 'is_read' => 0]);
    }

    public function test_employer_live_chat_send_and_poll_are_scoped_to_the_conversation(): void
    {
        $this->actingAs($this->employer())
            ->postJson(route('messages.chat'), [
                'action' => 'poll',
                'peer_id' => 7,
                'after_id' => 0,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('last_message_id', 2)
            ->assertJsonCount(2, 'messages');

        $this->actingAs($this->employer())
            ->postJson(route('messages.chat'), [
                'action' => 'send',
                'peer_id' => 7,
                'message' => 'A live employer reply',
                'subject' => 'Re: Project application',
            ])
            ->assertOk()
            ->assertJsonPath('message.message', 'A live employer reply');
        $this->assertDatabaseHas('messages', [
            'sender_id' => 6,
            'receiver_id' => 7,
            'message' => 'A live employer reply',
        ]);
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
