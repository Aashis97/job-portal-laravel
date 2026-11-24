<?php

namespace App\Http\Controllers;

use App\Models\JobSeekerProfile;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class JobSeekerProfileController extends Controller
{
    private const COMPLETION_FIELDS = [
        'date_of_birth',
        'gender',
        'address',
        'city',
        'country',
        'education_level',
        'years_of_experience',
        'current_job_title',
        'expected_salary',
        'profile_summary',
        'availability',
    ];

    public function show(Request $request)
    {
        $user = $request->user();
        $profile = JobSeekerProfile::query()->firstOrCreate(
            ['user_id' => $user->user_id],
            ['profile_completed' => 0, 'availability' => 'Immediately']
        );
        $resume = DB::table('resume')
            ->where('user_id', $user->user_id)
            ->first(['resume_title', 'resume_file']);
        $imageName = basename((string) ($user->profile_image ?: 'default.png'));
        $profileImageUrl = $imageName === 'default.png'
            ? asset('assets/images/default-profile.svg')
            : route('jobseeker.profile-image', ['filename' => $imageName]);

        return view('jobseeker.profile', [
            'user' => $user,
            'profile' => $profile,
            'resume' => $resume,
            'profileCompletion' => $this->completionPercentage($profile->getAttributes()),
            'profileImageUrl' => $profileImageUrl,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'date_of_birth' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'gender' => ['nullable', 'in:Male,Female,Other'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'education_level' => ['nullable', 'string', 'max:150'],
            'years_of_experience' => ['nullable', 'numeric', 'min:0', 'max:999.9', 'decimal:0,1'],
            'current_job_title' => ['nullable', 'string', 'max:150'],
            'expected_salary' => ['nullable', 'numeric', 'min:0'],
            'profile_summary' => ['nullable', 'string', 'max:3000'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'github_url' => ['nullable', 'url', 'max:255'],
            'portfolio_url' => ['nullable', 'url', 'max:255'],
            'availability' => ['nullable', 'in:Immediately,15 Days,30 Days,60 Days'],
            'profile_image' => [
                'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'extensions:jpg,jpeg,png,webp',
                'max:5120',
                'dimensions:min_width=100,min_height=100,max_width=6000,max_height=6000',
            ],
        ]);

        $user = $request->user();
        $userId = (int) $user->user_id;
        $attributes = [];
        $profileFields = [
            'date_of_birth',
            'gender',
            'address',
            'city',
            'state',
            'country',
            'postal_code',
            'education_level',
            'current_job_title',
            'expected_salary',
            'profile_summary',
            'linkedin_url',
            'github_url',
            'portfolio_url',
        ];
        foreach ($profileFields as $field) {
            $attributes[$field] = isset($validated[$field]) && trim((string) $validated[$field]) !== ''
                ? trim((string) $validated[$field])
                : null;
        }
        $attributes['years_of_experience'] = isset($validated['years_of_experience']) && $validated['years_of_experience'] !== ''
            ? number_format((float) $validated['years_of_experience'], 1, '.', '')
            : '0.0';
        $attributes['availability'] = $validated['availability'] ?? null;
        if (isset($validated['expected_salary']) && $validated['expected_salary'] !== '') {
            $attributes['expected_salary'] = number_format((float) $validated['expected_salary'], 2, '.', '');
        }

        $upload = $request->file('profile_image');
        $newImageName = null;
        $newImagePath = null;
        $oldImageName = basename((string) $user->profile_image);

        if ($upload) {
            $extension = strtolower($upload->getClientOriginalExtension());
            $extension = $extension === 'jpeg' ? 'jpg' : $extension;
            $newImageName = 'jobseeker_' . $userId . '_' . bin2hex(random_bytes(16)) . '.' . $extension;
            $imageDirectory = $this->profileImageDirectory();
            File::ensureDirectoryExists($imageDirectory, 0755, true);
            $newImagePath = $imageDirectory . DIRECTORY_SEPARATOR . $newImageName;
            $upload->move($imageDirectory, $newImageName);
        }

        try {
            DB::transaction(function () use ($userId, $attributes, $newImageName) {
                $profile = JobSeekerProfile::query()->firstOrNew(['user_id' => $userId]);
                $profile->fill($attributes);
                $profile->profile_completed = $this->completionPercentage(array_merge(
                    $profile->getAttributes(),
                    $attributes
                )) >= 100 ? 1 : 0;
                $profile->save();

                if ($newImageName !== null) {
                    DB::table('users')->where('user_id', $userId)->update([
                        'profile_image' => $newImageName,
                        'updated_at' => now(),
                    ]);
                }
            });
        } catch (Throwable $exception) {
            if ($newImagePath !== null && is_file($newImagePath)) {
                unlink($newImagePath);
            }

            Log::error('Job seeker profile update failed.', [
                'user_id' => $userId,
                'exception' => $exception->getMessage(),
            ]);

            return redirect()->route('jobseeker.profile')
                ->withInput()
                ->with('danger', 'Unable to save your profile. Please try again.');
        }

        if ($newImageName !== null) {
            $request->session()->put('profile_image', $newImageName);
            $this->deleteOwnedProfileImage($oldImageName, $userId, $newImageName);
        }

        $this->logActivity($request, $userId);

        return redirect()->route('jobseeker.profile')->with('success', 'Your profile has been updated successfully.');
    }

    public function image(Request $request, string $filename)
    {
        $filename = basename($filename);
        $currentImage = basename((string) $request->user()->profile_image);
        abort_unless($filename === $currentImage && $filename !== '', 404);

        foreach ($this->profileImageDirectories() as $directory) {
            $path = $directory . DIRECTORY_SEPARATOR . $filename;
            $realPath = realpath($path);
            $realDirectory = realpath($directory);

            if (
                $realPath === false ||
                $realDirectory === false ||
                strpos($realPath, $realDirectory . DIRECTORY_SEPARATOR) !== 0 ||
                !is_file($realPath)
            ) {
                continue;
            }

            $mimeType = File::mimeType($realPath);
            abort_unless(in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true), 404);

            return response()->file($realPath, [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'private, max-age=3600',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        abort(404);
    }

    private function completionPercentage(array $profile): int
    {
        $filled = 0;
        foreach (self::COMPLETION_FIELDS as $field) {
            $value = $profile[$field] ?? null;
            if ($value !== null && trim((string) $value) !== '') {
                $filled++;
            }
        }

        return (int) round(($filled / count(self::COMPLETION_FIELDS)) * 100);
    }

    private function profileImageDirectory(): string
    {
        return dirname(base_path()) . DIRECTORY_SEPARATOR . 'assets'
            . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profiles';
    }

    private function profileImageDirectories(): array
    {
        $projectRoot = dirname(base_path());

        return [
            $this->profileImageDirectory(),
            $projectRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profile_images',
        ];
    }

    private function deleteOwnedProfileImage(string $oldName, int $userId, string $newName): void
    {
        $prefix = 'jobseeker_' . $userId . '_';
        if (!Str::startsWith($oldName, $prefix) || $oldName === $newName) {
            return;
        }

        foreach ($this->profileImageDirectories() as $directory) {
            $path = $directory . DIRECTORY_SEPARATOR . $oldName;
            $realPath = realpath($path);
            $realDirectory = realpath($directory);
            if (
                $realPath !== false &&
                $realDirectory !== false &&
                strpos($realPath, $realDirectory . DIRECTORY_SEPARATOR) === 0 &&
                is_file($realPath)
            ) {
                unlink($realPath);
            }
        }
    }

    private function logActivity(Request $request, int $userId): void
    {
        try {
            DB::table('activity_logs')->insert([
                'user_id' => $userId,
                'activity' => 'Updated profile',
                'module' => 'Jobseeker',
                'ip_address' => $request->ip(),
                'device_info' => Str::limit((string) $request->userAgent(), 255, ''),
            ]);
        } catch (QueryException $exception) {
            Log::warning('Job seeker profile activity could not be recorded.', [
                'user_id' => $userId,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
