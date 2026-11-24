<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminProfileTest extends TestCase
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
            $table->string('phone')->nullable();
            $table->string('profile_image')->default('default.png');
            $table->string('account_status');
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
            'user_id' => 1,
            'role_id' => 1,
            'full_name' => 'Admin One',
            'email' => 'admin@example.test',
            'password' => 'unused',
            'phone' => '555-0101',
            'profile_image' => 'default.png',
            'account_status' => 'Active',
            'created_at' => now(),
        ]);
    }

    public function test_admin_profile_shows_personal_details_and_existing_design(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.profile'))
            ->assertOk()
            ->assertSee('My Profile')
            ->assertSee('admin@example.test')
            ->assertSee('name="full_name"', false)
            ->assertSee('name="profile_image"', false);
    }

    public function test_admin_can_update_name_and_phone_and_activity_is_logged(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.profile.update'), [
                'full_name' => 'Updated Admin',
                'phone' => '555-0199',
            ])
            ->assertRedirect(route('admin.profile'))
            ->assertSessionHas('success', 'Your profile has been updated.');

        $this->assertDatabaseHas('users', [
            'user_id' => 1,
            'full_name' => 'Updated Admin',
            'phone' => '555-0199',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => 1,
            'activity' => 'Updated administrator profile',
            'module' => 'Profile',
        ]);
    }

    public function test_admin_photo_upload_is_validated_and_served_only_to_owner(): void
    {
        $uploadDirectory = dirname(base_path()) . DIRECTORY_SEPARATOR . 'assets'
            . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profiles';

        $this->actingAs($this->admin())
            ->post(route('admin.profile.update'), [
                'full_name' => 'Admin One',
                'profile_image' => UploadedFile::fake()->createWithContent('profile.png', $this->pngImage(100, 100)),
            ])
            ->assertRedirect(route('admin.profile'));

        $fileName = DB::table('users')->where('user_id', 1)->value('profile_image');
        $this->assertStringStartsWith('admin_1_', $fileName);
        $filePath = $uploadDirectory . DIRECTORY_SEPARATOR . $fileName;
        $this->assertFileExists($filePath);

        try {
            $owner = $this->admin();
            $owner->setAttribute('profile_image', $fileName);
            $this->actingAs($owner)
                ->get(route('admin.profile-image', ['filename' => $fileName]))
                ->assertOk()
                ->assertHeader('Content-Type', 'image/png');

            $otherAdmin = $this->admin();
            $otherAdmin->setAttribute('user_id', 2);
            $otherAdmin->setAttribute('profile_image', 'another-image.jpg');
            $this->actingAs($otherAdmin)
                ->get(route('admin.profile-image', ['filename' => $fileName]))
                ->assertNotFound();
        } finally {
            if (is_file($filePath)) {
                unlink($filePath);
            }
        }
    }

    public function test_invalid_name_and_photo_are_rejected(): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.profile'))
            ->post(route('admin.profile.update'), [
                'full_name' => str_repeat('a', 101),
                'profile_image' => UploadedFile::fake()->create('profile.svg', 10, 'image/svg+xml'),
            ])
            ->assertRedirect(route('admin.profile'))
            ->assertSessionHasErrors(['full_name', 'profile_image']);

        $this->assertDatabaseHas('users', ['user_id' => 1, 'full_name' => 'Admin One']);
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
        $user->setAttribute('profile_image', 'default.png');
        $user->exists = true;

        return $user;
    }

    private function pngImage(int $width, int $height): string
    {
        $chunk = static function (string $type, string $data): string {
            $content = $type . $data;

            return pack('N', strlen($data)) . $content . pack('N', crc32($content));
        };
        $imageData = str_repeat("\0" . str_repeat("\xff", $width * 3), $height);
        $header = pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0);

        return "\x89PNG\r\n\x1a\n"
            . $chunk('IHDR', $header)
            . $chunk('IDAT', gzcompress($imageData))
            . $chunk('IEND', '');
    }
}
