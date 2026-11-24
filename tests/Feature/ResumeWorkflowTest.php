<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use ZipArchive;

class ResumeWorkflowTest extends TestCase
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
        Schema::create('job_seekers', function (Blueprint $table) {
            $table->increments('seeker_id');
            $table->unsignedInteger('user_id')->unique();
            $table->string('resume_file')->nullable();
        });
        Schema::create('resume', function (Blueprint $table) {
            $table->increments('resume_id');
            $table->unsignedInteger('user_id')->unique();
            $table->string('resume_title')->nullable();
            $table->string('resume_file');
            $table->longText('resume_text')->nullable();
            $table->longText('extracted_skills')->nullable();
            $table->string('highest_education')->nullable();
            $table->decimal('total_experience', 4, 1)->nullable();
            $table->string('parser_status')->default('Pending');
            $table->dateTime('uploaded_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
        Schema::create('skills', function (Blueprint $table) {
            $table->increments('skill_id');
            $table->string('skill_name');
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
            'account_status' => 'Active',
        ]);
        DB::table('job_seekers')->insert(['user_id' => 8]);
    }

    public function test_resume_upload_replacement_view_and_download_keep_database_and_file_in_sync(): void
    {
        $uploadDirectory = $this->resumeDirectory();
        $savedPaths = [];

        try {
            $this->actingAs($this->jobSeeker())
                ->post('/jobseeker/process_resume.php', [
                    'action' => 'upload',
                    'resume_title' => 'First Resume',
                    'resume_file' => UploadedFile::fake()->createWithContent('first.pdf', "%PDF-1.4\nfirst resume"),
                ])
                ->assertRedirect(route('jobseeker.resume'))
                ->assertSessionHas('success');

            $firstResume = DB::table('resume')->where('user_id', 8)->first();
            $this->assertSame('First Resume', $firstResume->resume_title);
            $this->assertSame($firstResume->resume_file, DB::table('job_seekers')->where('user_id', 8)->value('resume_file'));
            $firstPath = $uploadDirectory . DIRECTORY_SEPARATOR . $firstResume->resume_file;
            $savedPaths[] = $firstPath;
            $this->assertFileExists($firstPath);

            $this->actingAs($this->jobSeeker())
                ->get('/jobseeker/resume.php')
                ->assertOk()
                ->assertSee('First Resume')
                ->assertSee('Current Resume');

            $this->actingAs($this->jobSeeker())
                ->get('/jobseeker/resume_file.php')
                ->assertOk();
            $this->actingAs($this->jobSeeker())
                ->get('/jobseeker/resume_file.php?download=1')
                ->assertOk()
                ->assertHeader('Content-Disposition', 'attachment; filename=' . $firstResume->resume_file);

            $this->actingAs($this->jobSeeker())
                ->post('/jobseeker/process_resume.php', [
                    'action' => 'upload',
                    'resume_title' => 'Updated Resume',
                    'resume_file' => UploadedFile::fake()->createWithContent('updated.pdf', "%PDF-1.4\nupdated resume"),
                ])
                ->assertRedirect(route('jobseeker.resume'));

            $updatedResume = DB::table('resume')->where('user_id', 8)->first();
            $updatedPath = $uploadDirectory . DIRECTORY_SEPARATOR . $updatedResume->resume_file;
            $savedPaths[] = $updatedPath;
            $this->assertSame('Updated Resume', $updatedResume->resume_title);
            $this->assertNotSame($firstResume->resume_file, $updatedResume->resume_file);
            $this->assertFileDoesNotExist($firstPath);
            $this->assertFileExists($updatedPath);
            $this->assertSame($updatedResume->resume_file, DB::table('job_seekers')->where('user_id', 8)->value('resume_file'));
        } finally {
            foreach ($savedPaths as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }
    }

    public function test_resume_upload_rejects_unsupported_extensions(): void
    {
        $this->actingAs($this->jobSeeker())
            ->from('/jobseeker/resume.php')
            ->post('/jobseeker/process_resume.php', [
                'action' => 'upload',
                'resume_file' => UploadedFile::fake()->createWithContent('resume.txt', 'plain text'),
            ])
            ->assertRedirect('/jobseeker/resume.php')
            ->assertSessionHasErrors('resume_file');

        $this->assertDatabaseCount('resume', 0);
    }

    public function test_resume_delete_removes_only_the_current_seekers_record_and_file(): void
    {
        $fileName = 'resume_8_delete-test.pdf';
        $filePath = $this->resumeDirectory() . DIRECTORY_SEPARATOR . $fileName;
        File::ensureDirectoryExists($this->resumeDirectory());
        file_put_contents($filePath, "%PDF-1.4\ntest");
        DB::table('resume')->insert([
            'user_id' => 8,
            'resume_title' => 'My Resume',
            'resume_file' => $fileName,
            'parser_status' => 'Pending',
        ]);
        DB::table('job_seekers')->where('user_id', 8)->update(['resume_file' => $fileName]);

        try {
            $this->actingAs($this->jobSeeker())
                ->post('/jobseeker/delete_resume.php')
                ->assertRedirect(route('jobseeker.resume'))
                ->assertSessionHas('success');

            $this->assertDatabaseCount('resume', 0);
            $this->assertDatabaseHas('job_seekers', ['user_id' => 8, 'resume_file' => null]);
            $this->assertFileDoesNotExist($filePath);
        } finally {
            if (is_file($filePath)) {
                unlink($filePath);
            }
        }
    }

    public function test_resume_parser_extracts_docx_text_skills_education_and_experience(): void
    {
        $fileName = 'resume_8_parser-test.docx';
        $filePath = $this->resumeDirectory() . DIRECTORY_SEPARATOR . $fileName;
        File::ensureDirectoryExists($this->resumeDirectory());
        file_put_contents($filePath, $this->docxContent());
        DB::table('resume')->insert([
            'user_id' => 8,
            'resume_title' => 'Parsed Resume',
            'resume_file' => $fileName,
            'parser_status' => 'Pending',
        ]);
        DB::table('skills')->insert([
            ['skill_name' => 'PHP'],
            ['skill_name' => 'Laravel'],
            ['skill_name' => 'Java'],
        ]);

        try {
            $this->actingAs($this->jobSeeker())
                ->post('/jobseeker/resume_parser.php')
                ->assertRedirect(route('jobseeker.resume'))
                ->assertSessionHas('success');

            $resume = DB::table('resume')->where('user_id', 8)->first();
            $this->assertSame('Completed', $resume->parser_status);
            $this->assertStringContainsString('PHP Laravel', $resume->resume_text);
            $this->assertSame(['Laravel', 'PHP'], json_decode($resume->extracted_skills, true));
            $this->assertSame('Bachelor', $resume->highest_education);
            $this->assertSame(3.0, (float) $resume->total_experience);
        } finally {
            if (is_file($filePath)) {
                unlink($filePath);
            }
        }
    }

    public function test_resume_parser_marks_unreadable_pdf_as_failed(): void
    {
        $fileName = 'resume_8_invalid-test.pdf';
        $filePath = $this->resumeDirectory() . DIRECTORY_SEPARATOR . $fileName;
        File::ensureDirectoryExists($this->resumeDirectory());
        file_put_contents($filePath, 'not a readable PDF');
        DB::table('resume')->insert([
            'user_id' => 8,
            'resume_title' => 'Invalid PDF',
            'resume_file' => $fileName,
            'parser_status' => 'Pending',
        ]);

        try {
            $this->actingAs($this->jobSeeker())
                ->post('/jobseeker/resume_parser.php')
                ->assertRedirect(route('jobseeker.resume'))
                ->assertSessionHas('danger');

            $this->assertDatabaseHas('resume', ['user_id' => 8, 'parser_status' => 'Failed']);
        } finally {
            if (is_file($filePath)) {
                unlink($filePath);
            }
        }
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

    private function resumeDirectory(): string
    {
        return dirname(base_path()) . DIRECTORY_SEPARATOR . 'assets'
            . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'resumes';
    }

    private function docxContent(): string
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'resume-docx-');
        $archive = new ZipArchive();
        if ($archive->open($temporaryPath, ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Unable to prepare DOCX test content.');
        }
        $archive->addFromString(
            'word/document.xml',
            '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>PHP Laravel Bachelor 3 years of experience</w:t></w:r></w:p></w:body></w:document>'
        );
        $archive->close();
        $content = file_get_contents($temporaryPath);
        unlink($temporaryPath);

        if ($content === false) {
            throw new \RuntimeException('Unable to read DOCX test content.');
        }

        return $content;
    }
}
