<?php

namespace App\Http\Controllers;

use App\Models\JobSeekerProfile;
use App\Models\Resume;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

class ResumeController extends Controller
{
    public function index(Request $request)
    {
        $resume = Resume::query()->where('user_id', $request->user()->user_id)->first();
        $parsedSkills = [];
        if ($resume && $resume->extracted_skills) {
            $decoded = json_decode((string) $resume->extracted_skills, true);
            $parsedSkills = is_array($decoded) ? $decoded : [];
        }

        return view('jobseeker.resume', [
            'resume' => $resume,
            'parsedSkills' => $parsedSkills,
            'parserStatus' => $resume?->parser_status ?? '',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'action' => ['required', 'in:upload'],
            'resume_title' => ['nullable', 'string', 'max:150'],
            'resume_file' => ['required', 'file', 'mimes:pdf,doc,docx', 'extensions:pdf,doc,docx', 'max:5120'],
        ]);

        $userId = (int) $request->user()->user_id;
        $upload = $request->file('resume_file');
        $extension = strtolower($upload->getClientOriginalExtension());
        $resumeTitle = trim((string) ($validated['resume_title'] ?? '')) ?: 'My Resume';
        $newFileName = 'resume_' . $userId . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $directory = $this->resumeDirectory();
        File::ensureDirectoryExists($directory, 0755, true);
        $newFilePath = $directory . DIRECTORY_SEPARATOR . $newFileName;
        $upload->move($directory, $newFileName);

        $currentResume = Resume::query()->where('user_id', $userId)->first();
        $oldFileName = $currentResume ? basename((string) $currentResume->resume_file) : null;

        try {
            DB::transaction(function () use ($userId, $resumeTitle, $newFileName) {
                $resume = Resume::query()->firstOrNew(['user_id' => $userId]);
                $resume->fill([
                    'resume_title' => $resumeTitle,
                    'resume_file' => $newFileName,
                    'resume_text' => null,
                    'extracted_skills' => null,
                    'parser_status' => 'Pending',
                    'uploaded_at' => now(),
                    'updated_at' => now(),
                ]);
                $resume->save();

                JobSeekerProfile::query()->where('user_id', $userId)->update([
                    'resume_file' => $newFileName,
                ]);
            });
        } catch (Throwable $exception) {
            if (is_file($newFilePath)) {
                unlink($newFilePath);
            }

            Log::error('Resume upload could not be saved.', [
                'user_id' => $userId,
                'exception' => $exception->getMessage(),
            ]);

            return redirect()->route('jobseeker.resume')
                ->withInput()
                ->with('danger', 'Unable to save resume information. Please try again.');
        }

        if ($oldFileName && $oldFileName !== $newFileName) {
            $this->deleteResumeFile($oldFileName);
        }

        $this->logActivity($request, $userId, 'Uploaded resume');

        return redirect()->route('jobseeker.resume')->with('success', 'Your resume has been uploaded successfully.');
    }

    public function destroy(Request $request)
    {
        $userId = (int) $request->user()->user_id;
        $resume = Resume::query()->where('user_id', $userId)->first();

        if (!$resume) {
            return redirect()->route('jobseeker.resume')->with('info', 'You do not have a resume to delete.');
        }

        $fileName = basename((string) $resume->resume_file);
        try {
            DB::transaction(function () use ($userId) {
                Resume::query()->where('user_id', $userId)->delete();
                JobSeekerProfile::query()->where('user_id', $userId)->update(['resume_file' => null]);
            });
        } catch (Throwable $exception) {
            Log::error('Resume delete failed.', [
                'user_id' => $userId,
                'exception' => $exception->getMessage(),
            ]);

            return redirect()->route('jobseeker.resume')->with('danger', 'Unable to delete your resume. Please try again.');
        }

        $this->deleteResumeFile($fileName);
        $this->logActivity($request, $userId, 'Deleted resume');

        return redirect()->route('jobseeker.resume')->with('success', 'Your resume has been deleted.');
    }

    public function file(Request $request)
    {
        $resume = Resume::query()->where('user_id', $request->user()->user_id)->first();
        abort_unless($resume, 404);

        $fileName = basename((string) $resume->resume_file);
        $path = $this->safeResumePath($fileName);
        abort_unless($path, 404);

        $mimeType = File::mimeType($path) ?: 'application/octet-stream';
        if ($request->boolean('download')) {
            return response()->download($path, $fileName, ['X-Content-Type-Options' => 'nosniff']);
        }

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }

    public function parse(Request $request)
    {
        $userId = (int) $request->user()->user_id;
        $resume = Resume::query()->where('user_id', $userId)->first();

        if (!$resume) {
            return redirect()->route('jobseeker.resume')->with('warning', 'Please upload a resume before running the parser.');
        }

        $path = $this->safeResumePath(basename((string) $resume->resume_file));
        if (!$path) {
            return redirect()->route('jobseeker.resume')->with('danger', 'The uploaded resume file could not be found on the server.');
        }

        $resume->parser_status = 'Processing';
        $resume->save();

        try {
            $resumeText = $this->extractText($path);
        } catch (Throwable $exception) {
            $resume->parser_status = 'Failed';
            $resume->save();
            Log::warning('Resume parsing failed.', [
                'resume_id' => $resume->resume_id,
                'exception' => $exception->getMessage(),
            ]);

            return redirect()->route('jobseeker.resume')
                ->with('danger', 'Unable to parse the resume: ' . $exception->getMessage());
        }

        $resumeText = (string) preg_replace('/[ \t]+/', ' ', $resumeText);
        $resumeText = (string) preg_replace("/\n{3,}/", "\n\n", $resumeText);
        $resumeText = trim($resumeText);

        if ($resumeText === '') {
            $resume->parser_status = 'Failed';
            $resume->save();

            return redirect()->route('jobseeker.resume')->with('danger', 'No readable text could be extracted from the resume.');
        }

        $detectedSkills = $this->detectSkills($resumeText);
        [$education, $experience] = $this->extractEducationAndExperience($resumeText);

        try {
            $resume->fill([
                'resume_text' => $resumeText,
                'extracted_skills' => $detectedSkills ? json_encode($detectedSkills, JSON_UNESCAPED_UNICODE) : null,
                'highest_education' => $education,
                'total_experience' => $experience,
                'parser_status' => 'Completed',
                'updated_at' => now(),
            ])->save();
        } catch (Throwable $exception) {
            $resume->parser_status = 'Failed';
            $resume->save();
            Log::error('Resume parser results could not be saved.', [
                'resume_id' => $resume->resume_id,
                'exception' => $exception->getMessage(),
            ]);

            return redirect()->route('jobseeker.resume')->with('danger', 'Resume parsed but results could not be saved.');
        }

        $this->logActivity($request, $userId, 'Parsed resume — ' . count($detectedSkills) . ' skill(s) detected');
        $message = $detectedSkills
            ? 'Resume parsed successfully. ' . count($detectedSkills) . ' skill(s) detected.'
            : 'Resume parsed successfully. No known skills were detected.';

        return redirect()->route('jobseeker.resume')->with('success', $message);
    }

    private function extractText(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'txt' => $this->readTextFile($path),
            'pdf' => $this->extractPdfText($path),
            'docx' => $this->extractDocxText($path),
            'doc' => throw new RuntimeException('Legacy .doc files are not supported. Please re-upload as PDF or DOCX.'),
            default => throw new RuntimeException('Unsupported resume format.'),
        };
    }

    private function readTextFile(string $path): string
    {
        $text = file_get_contents($path);
        if ($text === false) {
            throw new RuntimeException('Unable to read the resume file.');
        }

        return $text;
    }

    private function extractPdfText(string $path): string
    {
        $parser = new \Smalot\PdfParser\Parser();

        return (string) $parser->parseFile($path)->getText();
    }

    private function extractDocxText(string $path): string
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('The PHP zip extension is required to parse DOCX files.');
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Unable to open the DOCX file.');
        }

        $document = $zip->statName('word/document.xml');
        if (!$document || $document['size'] > 10 * 1024 * 1024) {
            $zip->close();
            throw new RuntimeException('DOCX document content is missing or too large to parse.');
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if ($xml === false || $xml === '') {
            throw new RuntimeException('DOCX file does not contain a readable document.xml.');
        }

        $text = str_replace(['</w:p>', '</w:tr>', '<w:br/>', '<w:br />'], "\n", $xml);

        return html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function detectSkills(string $resumeText): array
    {
        try {
            $skills = DB::table('skills')->orderByRaw('LENGTH(skill_name) DESC')->get(['skill_name']);
        } catch (QueryException $exception) {
            Log::warning('Resume skill detection could not load skills.', [
                'exception' => $exception->getMessage(),
            ]);

            return [];
        }

        $detected = [];
        $haystack = ' ' . mb_strtolower($resumeText) . ' ';
        foreach ($skills as $skill) {
            $name = trim((string) $skill->skill_name);
            if ($name === '') {
                continue;
            }
            $needle = mb_strtolower($name);
            $pattern = '/(?<![a-z0-9+#.])' . preg_quote($needle, '/') . '(?![a-z0-9+#.])/u';
            if (preg_match($pattern, $haystack)) {
                $detected[] = $name;
            }
        }

        return array_values(array_unique($detected));
    }

    private function extractEducationAndExperience(string $resumeText): array
    {
        $education = null;
        foreach ([
            'PhD' => 'PhD',
            'Master' => 'Master',
            "Master's" => 'Master',
            'Bachelor' => 'Bachelor',
            "Bachelor's" => 'Bachelor',
            'BSc' => 'Bachelor',
            'MSc' => 'Master',
            'MBA' => 'Master',
        ] as $keyword => $level) {
            if (stripos($resumeText, $keyword) !== false) {
                $education = $level;
                break;
            }
        }

        $experience = null;
        if (preg_match('/(\d+(?:\.\d+)?)\s*\+?\s*(?:years?|yrs?)\s+(?:of\s+)?experience/i', $resumeText, $matches)) {
            $experience = (float) $matches[1];
        }

        return [$education, $experience];
    }

    private function resumeDirectory(): string
    {
        return dirname(base_path()) . DIRECTORY_SEPARATOR . 'assets'
            . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'resumes';
    }

    private function safeResumePath(string $fileName): ?string
    {
        if ($fileName === '' || basename($fileName) !== $fileName) {
            return null;
        }

        $directory = realpath($this->resumeDirectory());
        $path = realpath($this->resumeDirectory() . DIRECTORY_SEPARATOR . $fileName);
        if (
            $directory === false ||
            $path === false ||
            strpos($path, $directory . DIRECTORY_SEPARATOR) !== 0 ||
            !is_file($path)
        ) {
            return null;
        }

        return $path;
    }

    private function deleteResumeFile(string $fileName): void
    {
        $path = $this->safeResumePath($fileName);
        if ($path !== null) {
            unlink($path);
        }
    }

    private function logActivity(Request $request, int $userId, string $activity): void
    {
        try {
            DB::table('activity_logs')->insert([
                'user_id' => $userId,
                'activity' => Str::limit($activity, 255, ''),
                'module' => 'Jobseeker',
                'ip_address' => $request->ip(),
                'device_info' => Str::limit((string) $request->userAgent(), 255, ''),
            ]);
        } catch (QueryException $exception) {
            Log::warning('Job seeker resume activity could not be recorded.', [
                'user_id' => $userId,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
