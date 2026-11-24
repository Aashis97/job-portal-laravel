<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class AdminProfileController extends Controller
{
    public function show(Request $request)
    {
        $profile = DB::table('users')
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->first(['user_id', 'full_name', 'email', 'phone', 'profile_image', 'created_at']);
        abort_unless($profile, 404, 'Your administrator profile could not be loaded.');

        $imageName = basename((string) ($profile->profile_image ?: 'default.png'));
        $profileImageUrl = $imageName === 'default.png'
            ? asset('assets/images/default-profile.svg')
            : route('admin.profile-image', ['filename' => $imageName]);

        return view('admin.profile', compact('profile', 'imageName', 'profileImageUrl'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
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
        $userId = (int) $request->user()->getAuthIdentifier();
        $fullName = trim($validated['full_name']);
        $phone = trim((string) ($validated['phone'] ?? ''));
        if ($fullName === '') {
            return redirect()->route('admin.profile')->withInput()->with('danger', 'Enter a name up to 100 characters long.');
        }

        $upload = $request->file('profile_image');
        $newImageName = null;
        $newImagePath = null;
        $oldImageName = basename((string) $request->user()->profile_image);
        if ($upload) {
            $extension = strtolower($upload->getClientOriginalExtension());
            $extension = $extension === 'jpeg' ? 'jpg' : $extension;
            $newImageName = 'admin_' . $userId . '_' . bin2hex(random_bytes(16)) . '.' . $extension;
            $imageDirectory = $this->profileImageDirectory();
            File::ensureDirectoryExists($imageDirectory, 0755, true);
            $newImagePath = $imageDirectory . DIRECTORY_SEPARATOR . $newImageName;
            $upload->move($imageDirectory, $newImageName);
        }

        try {
            DB::table('users')->where('user_id', $userId)->update([
                'full_name' => $fullName,
                'phone' => $phone !== '' ? $phone : null,
                'profile_image' => $newImageName ?? $request->user()->profile_image,
                'updated_at' => now(),
            ]);
        } catch (Throwable $exception) {
            if ($newImagePath !== null && is_file($newImagePath)) {
                unlink($newImagePath);
            }
            Log::error('Admin profile update failed.', [
                'user_id' => $userId,
                'exception' => $exception->getMessage(),
            ]);

            return redirect()->route('admin.profile')->withInput()->with('danger', 'Unable to save your profile right now. Please try again.');
        }

        $request->session()->put('full_name', $fullName);
        if ($newImageName !== null) {
            $request->session()->put('profile_image', $newImageName);
            $this->deleteOwnedProfileImage($oldImageName, $userId, $newImageName);
        }
        $this->logActivity($request, $userId);

        return redirect()->route('admin.profile')->with('success', 'Your profile has been updated.');
    }

    public function image(Request $request, string $filename)
    {
        $filename = basename($filename);
        $currentImage = basename((string) $request->user()->profile_image);
        abort_unless($filename === $currentImage && $filename !== '' && $filename !== 'default.png', 404);

        $directory = $this->profileImageDirectory();
        $path = $directory . DIRECTORY_SEPARATOR . $filename;
        $realPath = realpath($path);
        $realDirectory = realpath($directory);
        abort_unless(
            $realPath !== false
                && $realDirectory !== false
                && str_starts_with($realPath, $realDirectory . DIRECTORY_SEPARATOR)
                && is_file($realPath),
            404
        );

        $mimeType = File::mimeType($realPath);
        abort_unless(in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true), 404);

        return response()->file($realPath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function profileImageDirectory(): string
    {
        return dirname(base_path()) . DIRECTORY_SEPARATOR . 'assets'
            . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profiles';
    }

    private function deleteOwnedProfileImage(string $oldName, int $userId, string $newName): void
    {
        if (!Str::startsWith($oldName, 'admin_' . $userId . '_') || $oldName === $newName) {
            return;
        }

        $directory = $this->profileImageDirectory();
        $path = $directory . DIRECTORY_SEPARATOR . $oldName;
        $realPath = realpath($path);
        $realDirectory = realpath($directory);
        if (
            $realPath !== false
            && $realDirectory !== false
            && str_starts_with($realPath, $realDirectory . DIRECTORY_SEPARATOR)
            && is_file($realPath)
        ) {
            unlink($realPath);
        }
    }

    private function logActivity(Request $request, int $userId): void
    {
        try {
            DB::table('activity_logs')->insert([
                'user_id' => $userId,
                'activity' => 'Updated administrator profile',
                'module' => 'Profile',
                'ip_address' => $request->ip(),
                'device_info' => Str::limit((string) $request->userAgent(), 255, ''),
                'created_at' => now(),
            ]);
        } catch (QueryException $exception) {
            Log::warning('Admin profile activity could not be recorded.', [
                'user_id' => $userId,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
