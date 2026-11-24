<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class EmployerCompanyProfileController extends Controller
{
    public function show(Request $request)
    {
        $employer = $this->employerFor($request);
        $company = $employer->company_id
            ? DB::table('companies')->where('company_id', $employer->company_id)->first()
            : null;

        if (!$company) {
            $company = (object) [
                'company_id' => null,
                'company_name' => '',
                'company_email' => '',
                'company_phone' => '',
                'website' => '',
                'description' => '',
                'address' => '',
                'logo' => 'company.png',
                'status' => 'Pending',
                'rejection_reason' => null,
            ];
        }

        return view('employer.company-profile', [
            'employer' => $employer,
            'company' => $company,
            'logoUrl' => $this->companyLogoUrl((string) ($company->logo ?: 'company.png')),
            'statusClass' => strtolower(str_replace(' ', '-', (string) $company->status)),
        ]);
    }

    public function update(Request $request)
    {
        $employer = $this->employerFor($request);
        $companyId = $employer->company_id ? (int) $employer->company_id : null;

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:150'],
            'company_email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('companies', 'company_email')->ignore($companyId, 'company_id'),
            ],
            'company_phone' => ['nullable', 'regex:/^(98|97)[0-9]{8}$/'],
            'company_website' => ['nullable', 'url', 'max:255'],
            'company_description' => ['nullable', 'string', 'max:5000'],
            'company_address' => ['nullable', 'string', 'max:1000'],
            'designation' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'company_logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:2048'],
            'submit_for_verification' => ['nullable', 'in:1'],
        ]);

        $logoFile = $request->file('company_logo');
        $newLogo = null;
        $destination = null;
        $oldLogo = 'company.png';

        if ($companyId !== null) {
            $existingCompany = DB::table('companies')
                ->where('company_id', $companyId)
                ->first(['logo', 'status']);
            if ($existingCompany) {
                $oldLogo = (string) ($existingCompany->logo ?: 'company.png');
            }
        }

        if ($logoFile) {
            $imageInfo = @getimagesize($logoFile->getRealPath());
            $extensions = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
            ];
            if ($imageInfo === false || !isset($extensions[$imageInfo['mime'] ?? ''])) {
                return back()->withInput()->with('company_profile_error', 'Invalid logo format. Only valid JPG, PNG and WEBP images are allowed.');
            }

            $newLogo = 'company_' . (int) $request->user()->user_id . '_' . bin2hex(random_bytes(8)) . '.' . $extensions[$imageInfo['mime']];
            $directory = $this->companyLogoDirectory();
            File::ensureDirectoryExists($directory, 0755, true);
            $destination = $directory . DIRECTORY_SEPARATOR . $newLogo;
            $logoFile->move($directory, $newLogo);
        }

        $resubmitted = $companyId !== null
            && ($request->input('submit_for_verification') === '1')
            && (($existingCompany->status ?? '') === 'Rejected');

        try {
            DB::transaction(function () use ($employer, $companyId, $validated, $newLogo, $resubmitted) {
                $companyValues = [
                    'company_name' => trim($validated['company_name']),
                    'company_email' => strtolower(trim($validated['company_email'])),
                    'company_phone' => $validated['company_phone'] ?? null,
                    'website' => $validated['company_website'] ?? null,
                    'description' => $validated['company_description'] ?? null,
                    'address' => $validated['company_address'] ?? null,
                    'updated_at' => now(),
                ];

                if ($companyId === null) {
                    $companyValues['logo'] = $newLogo ?? 'company.png';
                    $companyValues['status'] = 'Pending';
                    $companyId = DB::table('companies')->insertGetId($companyValues);
                    DB::table('employers')
                        ->where('employer_id', $employer->employer_id)
                        ->where('user_id', $employer->user_id)
                        ->update(['company_id' => $companyId, 'updated_at' => now()]);
                } else {
                    if ($newLogo !== null) {
                        $companyValues['logo'] = $newLogo;
                    }
                    if ($resubmitted) {
                        $companyValues['status'] = 'Pending';
                        foreach (['rejection_reason', 'verified_at', 'verified_by'] as $optionalColumn) {
                            if (Schema::hasColumn('companies', $optionalColumn)) {
                                $companyValues[$optionalColumn] = null;
                            }
                        }
                    }
                    DB::table('companies')
                        ->where('company_id', $companyId)
                        ->update($companyValues);
                }

                DB::table('employers')
                    ->where('employer_id', $employer->employer_id)
                    ->where('user_id', $employer->user_id)
                    ->update([
                        'designation' => $validated['designation'] ?? null,
                        'department' => $validated['department'] ?? null,
                        'updated_at' => now(),
                    ]);
            });
        } catch (Throwable $exception) {
            if ($destination && is_file($destination)) {
                File::delete($destination);
            }
            Log::error('Company profile update failed.', [
                'user_id' => $request->user()->user_id,
                'exception' => $exception->getMessage(),
            ]);

            return back()->withInput()->with('company_profile_error', 'Unable to update company profile. Please try again.');
        }

        if ($newLogo !== null && $oldLogo !== 'company.png') {
            $oldPath = $this->companyLogoDirectory() . DIRECTORY_SEPARATOR . basename($oldLogo);
            if (is_file($oldPath)) {
                File::delete($oldPath);
            }
        }

        return redirect()->route('employer.company-profile')
            ->with('company_profile_success', $resubmitted
                ? 'Company profile updated and resubmitted for verification.'
                : 'Company profile updated successfully.');
    }

    public function logo(string $filename): BinaryFileResponse
    {
        $filename = basename($filename);
        abort_unless(preg_match('/\A[a-zA-Z0-9_.-]+\.(?:jpg|jpeg|png|webp)\z/i', $filename), 404);

        $paths = [
            $this->companyLogoDirectory() . DIRECTORY_SEPARATOR . $filename,
            dirname(base_path()) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'companies' . DIRECTORY_SEPARATOR . $filename,
        ];
        foreach ($paths as $path) {
            if (is_file($path)) {
                return response()->file($path, ['Cache-Control' => 'public, max-age=3600']);
            }
        }

        abort(404);
    }

    private function employerFor(Request $request): object
    {
        $employer = DB::table('employers as e')
            ->join('users as u', 'u.user_id', '=', 'e.user_id')
            ->where('e.user_id', $request->user()->user_id)
            ->first([
                'e.employer_id',
                'e.user_id',
                'e.company_id',
                'e.designation',
                'e.department',
                'e.is_primary_contact',
                'u.email',
            ]);

        abort_unless($employer, 403, 'Employer profile not found.');

        return $employer;
    }

    private function companyLogoUrl(string $logo): string
    {
        $filename = basename($logo);
        if ($filename === 'company.png') {
            return asset('assets/images/default-company.svg');
        }

        return route('company.logo', ['filename' => $filename]);
    }

    private function companyLogoDirectory(): string
    {
        return dirname(base_path()) . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'company_logos';
    }
}
