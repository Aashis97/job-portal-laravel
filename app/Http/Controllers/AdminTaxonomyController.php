<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminTaxonomyController extends Controller
{
    public function index()
    {
        $categories = DB::table('categories as c')
            ->leftJoin('jobs as j', 'j.category_id', '=', 'c.category_id')
            ->groupBy('c.category_id', 'c.category_name', 'c.category_description', 'c.status')
            ->orderBy('c.status')
            ->orderBy('c.category_name')
            ->select([
                'c.category_id',
                'c.category_name',
                'c.category_description',
                'c.status',
            ])
            ->selectRaw('COUNT(j.job_id) AS job_count')
            ->get();

        $skills = DB::table('skills as s')
            ->orderBy('s.skill_category')
            ->orderBy('s.skill_name')
            ->select(['s.skill_id', 's.skill_name', 's.skill_category'])
            ->selectSub(DB::table('job_skills')->selectRaw('COUNT(*)')->whereColumn('job_skills.skill_id', 's.skill_id'), 'job_count')
            ->selectSub(DB::table('user_skills')->selectRaw('COUNT(*)')->whereColumn('user_skills.skill_id', 's.skill_id'), 'seeker_count')
            ->get();

        return view('admin.taxonomy', compact('categories', 'skills'));
    }

    public function update(Request $request)
    {
        $action = $request->input('action');
        if (!in_array($action, ['category_create', 'category_update', 'skill_create', 'skill_update', 'skill_delete'], true)) {
            return redirect()->route('admin.taxonomy')->with('danger', 'Invalid taxonomy action.');
        }

        if (str_starts_with($action, 'category_')) {
            $categoryId = $action === 'category_update' ? (int) $request->input('category_id') : null;
            $request->merge([
                'category_name' => trim((string) $request->input('category_name', '')),
                'category_description' => trim((string) $request->input('category_description', '')),
            ]);
            $validated = $request->validate([
                'category_id' => [$action === 'category_update' ? 'required' : 'nullable', 'integer', 'min:1'],
                'category_name' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('categories', 'category_name')->ignore($categoryId, 'category_id'),
                ],
                'category_description' => ['nullable', 'string', 'max:5000'],
                'status' => ['required', Rule::in(['Active', 'Inactive'])],
            ]);
            $values = [
                'category_name' => trim($validated['category_name']),
                'category_description' => trim((string) ($validated['category_description'] ?? '')) ?: null,
                'status' => $validated['status'],
            ];
            if ($action === 'category_create') {
                $categoryId = DB::table('categories')->insertGetId($values);
                $this->logActivity($request, 'Created job category #' . $categoryId);
                return redirect()->route('admin.taxonomy')->with('success', 'Category added.');
            }

            $updated = DB::table('categories')->where('category_id', $categoryId)->update($values);
            abort_unless($updated > 0 || DB::table('categories')->where('category_id', $categoryId)->exists(), 404);
            $this->logActivity($request, 'Updated job category #' . $categoryId);

            return redirect()->route('admin.taxonomy')->with('success', 'Category updated.');
        }

        if ($action === 'skill_delete') {
            $validated = $request->validate(['skill_id' => ['required', 'integer', 'min:1']]);
            $deleted = DB::transaction(function () use ($validated) {
                $skill = DB::table('skills')->where('skill_id', $validated['skill_id'])->lockForUpdate()->first(['skill_id']);
                abort_unless($skill, 404);
                $inUse = DB::table('job_skills')->where('skill_id', $skill->skill_id)->exists()
                    || DB::table('user_skills')->where('skill_id', $skill->skill_id)->exists();
                if ($inUse) {
                    return false;
                }

                DB::table('skills')->where('skill_id', $skill->skill_id)->delete();
                return true;
            });

            if ($deleted) {
                $this->logActivity($request, 'Deleted skill #' . $validated['skill_id']);
            }

            return redirect()->route('admin.taxonomy')->with(
                $deleted ? 'success' : 'danger',
                $deleted ? 'Skill deleted.' : 'This skill is assigned to jobs or candidate profiles. Remove those assignments before deleting it.'
            );
        }

        $skillId = $action === 'skill_update' ? (int) $request->input('skill_id') : null;
        $request->merge([
            'skill_name' => trim((string) $request->input('skill_name', '')),
            'skill_category' => trim((string) $request->input('skill_category', '')),
        ]);
        $validated = $request->validate([
            'skill_id' => [$action === 'skill_update' ? 'required' : 'nullable', 'integer', 'min:1'],
            'skill_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('skills', 'skill_name')->ignore($skillId, 'skill_id'),
            ],
            'skill_category' => ['nullable', 'string', 'max:100'],
        ]);
        $values = [
            'skill_name' => trim($validated['skill_name']),
            'skill_category' => trim((string) ($validated['skill_category'] ?? '')) ?: null,
        ];
        if ($action === 'skill_create') {
            $skillId = DB::table('skills')->insertGetId($values);
            $this->logActivity($request, 'Created skill #' . $skillId);
            return redirect()->route('admin.taxonomy')->with('success', 'Skill added.');
        }

        $updated = DB::table('skills')->where('skill_id', $skillId)->update($values);
        abort_unless($updated > 0 || DB::table('skills')->where('skill_id', $skillId)->exists(), 404);
        $this->logActivity($request, 'Updated skill #' . $skillId);

        return redirect()->route('admin.taxonomy')->with('success', 'Skill updated.');
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
            Log::warning('Admin taxonomy activity could not be recorded.', [
                'user_id' => $request->user()->getAuthIdentifier(),
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
