<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminSettingsController extends Controller
{
    private const TABS = ['general', 'registration', 'jobs', 'applications', 'email'];

    private const TAB_LABELS = [
        'general' => 'General',
        'registration' => 'Registration',
        'jobs' => 'Jobs',
        'applications' => 'Applications',
        'email' => 'Email',
    ];

    public function index(Request $request)
    {
        $tab = $request->query('tab', 'general');
        $tab = is_string($tab) && in_array($tab, self::TABS, true) ? $tab : 'general';
        $settings = DB::table('settings')
            ->where('setting_group', $tab)
            ->orderBy('sort_order')
            ->orderBy('setting_id')
            ->get();

        return view('admin.settings', [
            'tab' => $tab,
            'tabLabels' => self::TAB_LABELS,
            'settings' => $settings,
            'infrastructure' => [
                'app_url' => config('app.url'),
                'environment' => config('app.env'),
                'smtp_host' => config('mail.mailers.smtp.host'),
                'smtp_port' => config('mail.mailers.smtp.port'),
                'mail_from' => config('mail.from.address'),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $tab = $request->input('tab', 'general');
        $tab = is_string($tab) && in_array($tab, self::TABS, true) ? $tab : 'general';
        $redirect = redirect()->route('admin.settings', ['tab' => $tab]);
        $submitted = $request->input('setting');

        if (!is_array($submitted) || $submitted === []) {
            return $redirect->with('warning', 'No settings were submitted.');
        }

        $settings = DB::table('settings')
            ->where('setting_group', $tab)
            ->get(['setting_key', 'setting_type']);
        if ($settings->isEmpty()) {
            return $redirect->with('warning', 'This settings group has no entries.');
        }

        $values = [];
        $errors = [];
        foreach ($settings as $setting) {
            $key = $setting->setting_key;
            if (!array_key_exists($key, $submitted)) {
                continue;
            }

            $raw = $submitted[$key];
            if (!is_scalar($raw) && $raw !== null) {
                $errors[] = "Invalid value for {$key}.";
                continue;
            }
            $value = trim((string) $raw);

            switch ($setting->setting_type) {
                case 'bool':
                    $value = in_array($value, ['1', 'true', 'on'], true) ? '1' : '0';
                    break;
                case 'int':
                    if ($value !== '' && !ctype_digit($value)) {
                        $errors[] = "{$key} must be a whole number.";
                        continue 2;
                    }
                    $value = (string) (int) $value;
                    break;
                case 'email':
                    if (($value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) || mb_strlen($value) > 150) {
                        $errors[] = $value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL) === false
                            ? "{$key} must be a valid email address."
                            : "{$key} is too long.";
                        continue 2;
                    }
                    break;
                case 'url':
                    if (($value !== '' && filter_var($value, FILTER_VALIDATE_URL) === false) || mb_strlen($value) > 255) {
                        $errors[] = $value !== '' && filter_var($value, FILTER_VALIDATE_URL) === false
                            ? "{$key} must be a valid URL."
                            : "{$key} is too long.";
                        continue 2;
                    }
                    break;
                case 'text':
                    if (mb_strlen($value) > 1000) {
                        $errors[] = "{$key} cannot exceed 1000 characters.";
                        continue 2;
                    }
                    break;
                default:
                    if (mb_strlen($value) > 255) {
                        $errors[] = "{$key} is too long.";
                        continue 2;
                    }
                    break;
            }

            $values[$key] = $value;
        }

        if ($errors !== []) {
            return $redirect->with('danger', implode(' ', $errors));
        }

        try {
            DB::transaction(function () use ($values, $tab, $request) {
                foreach ($values as $key => $value) {
                    DB::table('settings')
                        ->where('setting_group', $tab)
                        ->where('setting_key', $key)
                        ->update([
                            'setting_value' => $value,
                            'updated_by' => $request->user()->getAuthIdentifier(),
                            'updated_at' => now(),
                        ]);
                }
            });
        } catch (QueryException $exception) {
            Log::error('Admin platform settings could not be saved.', [
                'group' => $tab,
                'user_id' => $request->user()->getAuthIdentifier(),
                'exception' => $exception->getMessage(),
            ]);

            return $redirect->with('danger', 'Unable to save settings. Please try again.');
        }

        $this->logActivity($request, 'Updated ' . count($values) . ' setting(s) in group "' . $tab . '"');

        return $redirect->with('success', count($values) . ' setting' . (count($values) === 1 ? '' : 's') . ' saved.');
    }

    private function logActivity(Request $request, string $activity): void
    {
        try {
            DB::table('activity_logs')->insert([
                'user_id' => $request->user()->getAuthIdentifier(),
                'activity' => Str::limit($activity, 255, ''),
                'module' => 'Admin',
                'ip_address' => $request->ip(),
                'device_info' => Str::limit((string) $request->userAgent(), 255, ''),
                'created_at' => now(),
            ]);
        } catch (QueryException $exception) {
            Log::warning('Admin settings activity could not be recorded.', [
                'user_id' => $request->user()->getAuthIdentifier(),
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
