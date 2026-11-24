<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminMessagingTest extends TestCase
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
        DB::table('users')->insert([
            ['user_id' => 1, 'role_id' => 1, 'full_name' => 'Admin One', 'email' => 'admin@example.test', 'password' => 'unused', 'account_status' => 'Active'],
            ['user_id' => 2, 'role_id' => 2, 'full_name' => 'Employer One', 'email' => 'employer@example.test', 'password' => 'unused', 'account_status' => 'Active'],
            ['user_id' => 3, 'role_id' => 3, 'full_name' => 'Seeker One', 'email' => 'seeker@example.test', 'password' => 'unused', 'account_status' => 'Active'],
            ['user_id' => 4, 'role_id' => 2, 'full_name' => 'Other Employer', 'email' => 'other@example.test', 'password' => 'unused', 'account_status' => 'Active'],
        ]);
        DB::table('messages')->insert([
            [
                'message_id' => 1,
                'sender_id' => 2,
                'receiver_id' => 1,
                'subject' => 'Company review',
                'message' => 'Please review our company information.',
                'is_read' => false,
                'sent_at' => now()->subMinutes(3),
            ],
            [
                'message_id' => 2,
                'sender_id' => 1,
                'receiver_id' => 3,
                'subject' => 'Account update',
                'message' => 'Your profile has been updated.',
                'is_read' => false,
                'sent_at' => now()->subMinutes(2),
            ],
            [
                'message_id' => 3,
                'sender_id' => 4,
                'receiver_id' => 2,
                'subject' => 'Private message',
                'message' => 'This message does not involve the administrator.',
                'is_read' => false,
                'sent_at' => now()->subMinute(),
            ],
        ]);
    }

    public function test_admin_can_search_inbox_and_view_sent_folder_counts(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.messages', ['q' => 'review']))
            ->assertOk()
            ->assertSee('Employer One')
            ->assertSee('Company review')
            ->assertDontSee('Private message');

        $this->actingAs($this->admin())
            ->get(route('admin.messages', ['tab' => 'sent']))
            ->assertOk()
            ->assertSee('Account update')
            ->assertSee('Sent');
    }

    public function test_admin_can_view_owned_message_and_only_received_message_is_marked_read(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.messages', ['view' => 1]))
            ->assertOk()
            ->assertSee('Please review our company information.')
            ->assertSee('Employer One');

        $this->assertDatabaseHas('messages', ['message_id' => 1, 'is_read' => 1]);
        $this->assertDatabaseHas('messages', ['message_id' => 2, 'is_read' => 0]);

        $this->actingAs($this->admin())
            ->get(route('admin.messages', ['view' => 3]))
            ->assertOk()
            ->assertSee('That message is unavailable or does not belong to your account.');
        $this->assertDatabaseHas('messages', ['message_id' => 3, 'is_read' => 0]);
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
