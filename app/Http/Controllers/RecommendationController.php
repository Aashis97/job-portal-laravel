<?php

namespace App\Http\Controllers;

use App\Services\RecommendationService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class RecommendationController extends Controller
{
    public function index(Request $request)
    {
        $recommendations = DB::table('recommendations as r')
            ->join('jobs as j', 'j.job_id', '=', 'r.job_id')
            ->join('companies as c', 'c.company_id', '=', 'j.company_id')
            ->leftJoin('categories as cat', 'cat.category_id', '=', 'j.category_id')
            ->where('r.user_id', $request->user()->user_id)
            ->where('j.job_status', 'Open')
            ->where(function ($query) {
                $query->whereNull('j.application_deadline')
                    ->orWhereDate('j.application_deadline', '>=', today());
            })
            ->orderByDesc('r.matching_score')
            ->orderByDesc('r.generated_at')
            ->get([
                'r.recommendation_id',
                'r.job_id',
                'r.algorithm',
                'r.matching_score',
                'r.generated_at',
                'j.job_title',
                'j.job_description',
                'j.employment_type',
                'j.workplace',
                'j.experience_level',
                'j.minimum_salary',
                'j.maximum_salary',
                'j.currency',
                'j.location',
                'j.application_deadline',
                'j.job_status',
                'c.company_id',
                'c.company_name',
                'c.logo',
                'cat.category_name',
            ]);

        return view('jobseeker.recommendations', [
            'recommendations' => $recommendations,
            'lastGenerated' => $recommendations->first()?->generated_at,
        ]);
    }

    public function generate(Request $request, RecommendationService $recommendations)
    {
        $userId = (int) $request->user()->user_id;
        try {
            $results = $recommendations->generate($userId, 12);
        } catch (Throwable $exception) {
            Log::error('Job recommendations could not be generated.', [
                'user_id' => $userId,
                'exception' => $exception->getMessage(),
            ]);

            return redirect()->route('jobseeker.recommendations')
                ->with('danger', 'Unable to generate recommendations right now. Please try again.');
        }

        if (!$results) {
            return redirect()->route('jobseeker.recommendations')
                ->with('warning', 'No suitable jobs were found. Try completing your profile, adding skills, or check back later.');
        }

        $this->logActivity($request, $userId, 'Generated ' . count($results) . ' recommendations');

        $count = count($results);
        return redirect()->route('jobseeker.recommendations')
            ->with('success', $count . ' job recommendation' . ($count === 1 ? '' : 's') . ' generated.');
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
            Log::warning('Recommendation activity could not be recorded.', [
                'user_id' => $userId,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
