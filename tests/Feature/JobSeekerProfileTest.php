<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class JobSeekerProfileTest extends TestCase
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
            $table->dateTime('updated_at')->nullable();
        });
        Schema::create('job_seekers', function (Blueprint $table) {
            $table->increments('seeker_id');
            $table->unsignedInteger('user_id')->unique();
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('education_level')->nullable();
            $table->decimal('years_of_experience', 4, 1)->default(0);
            $table->string('current_job_title')->nullable();
            $table->decimal('expected_salary', 12, 2)->nullable();
            $table->text('profile_summary')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('github_url')->nullable();
            $table->string('portfolio_url')->nullable();
            $table->string('resume_file')->nullable();
            $table->boolean('profile_completed')->default(false);
            $table->string('availability')->nullable();
        });
        Schema::create('resume', function (Blueprint $table) {
            $table->increments('resume_id');
            $table->unsignedInteger('user_id')->unique();
            $table->string('resume_title')->nullable();
            $table->string('resume_file');
        });
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->bigIncrements('log_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('activity');
            $table->string('module')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('device_info')->nullable();
        });

        DB::table('users')->insert([
            'user_id' => 8,
            'role_id' => 3,
            'full_name' => 'Test Seeker',
            'email' => 'seeker@example.test',
            'password' => 'unused',
            'phone' => '9812345678',
            'profile_image' => 'default.png',
            'account_status' => 'Active',
        ]);
    }

    public function test_profile_page_creates_missing_profile_and_displays_editable_fields(): void
    {
        $response = $this->actingAs($this->jobSeeker())
            ->get('/jobseeker/profile.php');

        $response->assertOk()
            ->assertSee('My Profile')
            ->assertSee('Profile Completion')
            ->assertSee('name="profile_summary"', false);
        $this->assertDatabaseHas('job_seekers', [
            'user_id' => 8,
            'profile_completed' => 0,
            'availability' => 'Immediately',
        ]);
    }

    public function test_profile_update_saves_fields_and_calculates_completion(): void
    {
        $this->actingAs($this->jobSeeker())
            ->post('/jobseeker/process_profile.php', [
                'date_of_birth' => '1990-04-12',
                'gender' => 'Other',
                'address' => '123 Example Road',
                'city' => 'Kathmandu',
                'state' => 'Bagmati',
                'country' => 'Nepal',
                'postal_code' => '44600',
                'education_level' => "Bachelor's Degree",
                'years_of_experience' => '2.5',
                'current_job_title' => 'PHP Developer',
                'expected_salary' => '60000',
                'profile_summary' => 'Experienced web developer.',
                'linkedin_url' => 'https://linkedin.com/in/test',
                'github_url' => 'https://github.com/test',
                'portfolio_url' => 'https://example.test/portfolio',
                'availability' => '15 Days',
            ])
            ->assertRedirect(route('jobseeker.profile'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('job_seekers', [
            'user_id' => 8,
            'date_of_birth' => '1990-04-12',
            'city' => 'Kathmandu',
            'state' => 'Bagmati',
            'postal_code' => '44600',
            'years_of_experience' => '2.5',
            'expected_salary' => '60000.00',
            'profile_completed' => 1,
        ]);
        $this->assertDatabaseHas('activity_logs', ['user_id' => 8, 'activity' => 'Updated profile']);
    }

    public function test_profile_photo_is_dimension_checked_saved_and_private_to_its_owner(): void
    {
        $uploadDirectory = dirname(base_path()) . DIRECTORY_SEPARATOR . 'assets'
            . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profiles';

        $response = $this->actingAs($this->jobSeeker())
            ->post('/jobseeker/process_profile.php', [
                'years_of_experience' => '0',
                'profile_image' => UploadedFile::fake()->createWithContent('profile.png', $this->pngImage(100, 100)),
            ]);

        $response->assertRedirect(route('jobseeker.profile'));
        $fileName = DB::table('users')->where('user_id', 8)->value('profile_image');
        $this->assertStringStartsWith('jobseeker_8_', $fileName);
        $filePath = $uploadDirectory . DIRECTORY_SEPARATOR . $fileName;
        $this->assertFileExists($filePath);

        try {
            $owner = $this->jobSeeker();
            $owner->setAttribute('profile_image', $fileName);
            $this->actingAs($owner)
                ->get(route('jobseeker.profile-image', ['filename' => $fileName]))
                ->assertOk()
                ->assertHeader('Content-Type', 'image/png');

            $otherUser = new User([
                'role_id' => 3,
                'full_name' => 'Other Seeker',
                'email' => 'other@example.test',
                'account_status' => 'Active',
            ]);
            $otherUser->setAttribute('user_id', 9);
            $otherUser->setAttribute('profile_image', 'another-image.jpg');
            $otherUser->exists = true;
            $this->actingAs($otherUser)
                ->get(route('jobseeker.profile-image', ['filename' => $fileName]))
                ->assertNotFound();
        } finally {
            if (is_file($filePath)) {
                unlink($filePath);
            }
        }
    }

    public function test_invalid_profile_fields_are_rejected_without_writing(): void
    {
        $this->actingAs($this->jobSeeker())
            ->from('/jobseeker/profile.php')
            ->post('/jobseeker/process_profile.php', [
                'years_of_experience' => '-1',
                'linkedin_url' => 'not-a-url',
                'availability' => 'Whenever',
            ])
            ->assertRedirect('/jobseeker/profile.php')
            ->assertSessionHasErrors(['years_of_experience', 'linkedin_url', 'availability']);

        $this->assertDatabaseCount('job_seekers', 0);
    }

    private function jobSeeker(): User
    {
        $user = new User([
            'role_id' => 3,
            'full_name' => 'Test Seeker',
            'email' => 'seeker@example.test',
            'phone' => '9812345678',
            'account_status' => 'Active',
        ]);
        $user->setAttribute('user_id', 8);
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
