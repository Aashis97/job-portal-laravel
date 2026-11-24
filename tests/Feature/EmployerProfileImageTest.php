<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmployerProfileImageTest extends TestCase
{
    private array $cleanupPaths = [];

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->increments('user_id');
            $table->unsignedInteger('role_id');
            $table->string('full_name');
            $table->string('email');
            $table->string('password')->nullable();
            $table->string('profile_image')->default('default.png');
            $table->string('account_status');
            $table->dateTime('updated_at')->nullable();
        });
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->bigIncrements('log_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('activity');
            $table->string('module')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('device_info')->nullable();
            $table->dateTime('created_at')->nullable();
        });

        DB::table('users')->insert([
            'user_id' => 6,
            'role_id' => 2,
            'full_name' => 'Employer Test',
            'email' => 'employer@example.test',
            'profile_image' => 'default.png',
            'account_status' => 'Active',
        ]);
    }

    protected function tearDown(): void
    {
        foreach ($this->cleanupPaths as $path) {
            if (is_file($path)) {
                File::delete($path);
            }
        }

        parent::tearDown();
    }

    public function test_employer_can_upload_and_view_only_their_own_profile_image(): void
    {
        $directory = dirname(base_path()) . DIRECTORY_SEPARATOR . 'assets'
            . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profiles';
        File::ensureDirectoryExists($directory);

        $oldFilename = 'user_6_previous.png';
        $otherFilename = 'user_7_keep.png';
        $oldPath = $directory . DIRECTORY_SEPARATOR . $oldFilename;
        $otherPath = $directory . DIRECTORY_SEPARATOR . $otherFilename;
        file_put_contents($oldPath, $this->pngImage(100, 100));
        file_put_contents($otherPath, $this->pngImage(100, 100));
        $this->cleanupPaths = [$oldPath, $otherPath];
        DB::table('users')->where('user_id', 6)->update(['profile_image' => $oldFilename]);

        $this->actingAs($this->employer())
            ->get(route('employer.profile-image.form'))
            ->assertOk()
            ->assertSee('Update Profile Image')
            ->assertSee(route('employer.profile-image', ['filename' => $oldFilename]), false);

        $this->actingAs($this->employer())
            ->from(route('employer.profile-image.form'))
            ->post(route('employer.profile-image.update'), [
                'profile_image' => UploadedFile::fake()->createWithContent('new-profile.png', $this->pngImage(100, 100)),
            ])
            ->assertRedirect(route('employer.profile-image.form'))
            ->assertSessionHas('profile_image_success', 'Your profile image has been uploaded successfully.');

        $filename = DB::table('users')->where('user_id', 6)->value('profile_image');
        $this->assertStringStartsWith('user_6_', $filename);
        $newPath = $directory . DIRECTORY_SEPARATOR . $filename;
        $this->cleanupPaths[] = $newPath;
        $this->assertFileExists($newPath);
        $this->assertFileDoesNotExist($oldPath);
        $this->assertFileExists($otherPath);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => 6,
            'activity' => 'Updated profile image',
            'module' => 'Employer',
        ]);

        $owner = $this->employer();
        $owner->setAttribute('profile_image', $filename);
        $this->actingAs($owner)
            ->get(route('employer.profile-image', ['filename' => $filename]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        $otherEmployer = new User([
            'role_id' => 2,
            'full_name' => 'Other Employer',
            'email' => 'other@example.test',
            'account_status' => 'Active',
        ]);
        $otherEmployer->setAttribute('user_id', 7);
        $otherEmployer->setAttribute('profile_image', 'another-image.png');
        $otherEmployer->exists = true;
        $this->actingAs($otherEmployer)
            ->get(route('employer.profile-image', ['filename' => $filename]))
            ->assertNotFound();
    }

    public function test_employer_profile_image_rejects_images_below_the_legacy_dimensions(): void
    {
        $this->actingAs($this->employer())
            ->from(route('employer.profile-image.form'))
            ->post(route('employer.profile-image.update'), [
                'profile_image' => UploadedFile::fake()->createWithContent('small.png', $this->pngImage(50, 50)),
            ])
            ->assertSessionHasErrors('profile_image');

        $this->assertDatabaseHas('users', ['user_id' => 6, 'profile_image' => 'default.png']);
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
        $user->setAttribute('profile_image', DB::table('users')->where('user_id', 6)->value('profile_image'));
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
