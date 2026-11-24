<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class EmployerProfileImageController extends Controller
{
    public function show(Request $request)
    {
        $user = DB::table('users')
            ->where('user_id', $request->user()->user_id)
            ->first(['user_id', 'full_name', 'email', 'profile_image']);

        if (!$user) {
            return redirect()->route('login')->with('danger', 'User account could not be found.');
        }

        $imageName = basename((string) ($user->profile_image ?: 'default.png'));
        $profileImageUrl = $imageName === 'default.png'
            ? asset('assets/images/default-profile.svg')
            : route('employer.profile-image', ['filename' => $imageName]);

        return view('employer.profile-image', compact('user', 'profileImageUrl'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'profile_image' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
                'dimensions:min_width=100,min_height=100,max_width=6000,max_height=6000',
            ],
        ]);

        $userId = (int) $request->user()->user_id;
        $oldFilename = basename((string) ($request->user()->profile_image ?: ''));
        $upload = $validated['profile_image'];
        $mimeType = File::mimeType($upload->getRealPath());
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        if (!isset($extensions[$mimeType])) {
            return redirect()->route('employer.profile-image.form')
                ->with('profile_image_error', 'Invalid image type. Only valid JPG, JPEG, PNG and WEBP files are allowed.');
        }

        $filename = null;
        $path = null;
        try {
            $filename = 'user_' . $userId . '_' . bin2hex(random_bytes(16)) . '.' . $extensions[$mimeType];
            $directory = $this->profileImageDirectory();
            File::ensureDirectoryExists($directory, 0755, true);
            $upload->move($directory, $filename);
            $path = $directory . DIRECTORY_SEPARATOR . $filename;

            DB::transaction(function () use ($userId, $filename) {
                $updated = DB::table('users')
                    ->where('user_id', $userId)
                    ->update(['profile_image' => $filename, 'updated_at' => now()]);

                if ($updated !== 1) {
                    throw new \RuntimeException('Profile image database update failed.');
                }
            });
        } catch (Throwable $exception) {
            if ($path !== null && is_file($path)) {
                File::delete($path);
            }
            Log::error('Employer profile image update failed.', [
                'user_id' => $userId,
                'exception' => $exception->getMessage(),
            ]);

            return redirect()->route('employer.profile-image.form')
                ->with('profile_image_error', 'The profile image could not be updated. Please try again.');
        }

        $this->deleteOwnedProfileImage($oldFilename, $userId, $filename);
        $this->logActivity($request, $userId);

        return redirect()->route('employer.profile-image.form')
            ->with('profile_image_success', 'Your profile image has been uploaded successfully.');
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

    private function deleteOwnedProfileImage(string $oldFilename, int $userId, string $newFilename): void
    {
        $prefix = 'user_' . $userId . '_';
        if (!Str::startsWith($oldFilename, $prefix) || $oldFilename === $newFilename) {
            return;
        }

        foreach ($this->profileImageDirectories() as $directory) {
            $path = $directory . DIRECTORY_SEPARATOR . $oldFilename;
            $realPath = realpath($path);
            $realDirectory = realpath($directory);
            if (
                $realPath !== false &&
                $realDirectory !== false &&
                strpos($realPath, $realDirectory . DIRECTORY_SEPARATOR) === 0 &&
                is_file($realPath)
            ) {
                File::delete($realPath);
            }
        }
    }

    private function logActivity(Request $request, int $userId): void
    {
        try {
            DB::table('activity_logs')->insert([
                'user_id' => $userId,
                'activity' => 'Updated profile image',
                'module' => 'Employer',
                'ip_address' => $request->ip(),
                'device_info' => Str::limit((string) $request->userAgent(), 255, ''),
                'created_at' => now(),
            ]);
        } catch (QueryException $exception) {
            Log::warning('Employer profile image activity could not be recorded.', [
                'user_id' => $userId,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
