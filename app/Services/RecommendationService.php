<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class RecommendationService
{
    private const WEIGHT_SKILL = 0.65;
    private const WEIGHT_EXPERIENCE = 0.20;
    private const WEIGHT_LOCATION = 0.15;
    private const NEUTRAL_SKILL = 40.0;
    private const NEUTRAL_LOCATION = 50.0;

    public function generate(int $userId, int $limit = 12): array
    {
        $limit = max(1, min($limit, 50));

        return DB::transaction(function () use ($userId, $limit) {
            DB::table('recommendations')->where('user_id', $userId)->delete();

            $userSkillSet = DB::table('user_skills')
                ->where('user_id', $userId)
                ->pluck('skill_id')
                ->mapWithKeys(static fn ($skillId) => [(int) $skillId => true])
                ->all();
            $profile = DB::table('job_seekers')
                ->where('user_id', $userId)
                ->first(['city', 'years_of_experience']);
            $userCity = mb_strtolower(trim((string) ($profile->city ?? '')));
            $userExperience = (float) ($profile->years_of_experience ?? 0);

            $jobs = DB::table('jobs')
                ->where('job_status', 'Open')
                ->where(function ($query) {
                    $query->whereNull('application_deadline')
                        ->orWhereDate('application_deadline', '>=', today());
                })
                ->orderBy('job_id')
                ->get(['job_id', 'minimum_experience', 'location', 'workplace']);

            if ($jobs->isEmpty()) {
                return [];
            }

            $jobIds = $jobs->pluck('job_id')->map(static fn ($jobId) => (int) $jobId)->all();
            $jobSkills = [];
            foreach (DB::table('job_skills')->whereIn('job_id', $jobIds)->get(['job_id', 'skill_id']) as $row) {
                $jobSkills[(int) $row->job_id][] = (int) $row->skill_id;
            }
            $appliedJobIds = DB::table('applications')
                ->where('user_id', $userId)
                ->pluck('job_id')
                ->mapWithKeys(static fn ($jobId) => [(int) $jobId => true])
                ->all();

            $scored = [];
            foreach ($jobs as $job) {
                $jobId = (int) $job->job_id;
                if (isset($appliedJobIds[$jobId])) {
                    continue;
                }

                $skillScore = $this->scoreSkillMatch($userSkillSet, $jobSkills[$jobId] ?? []);
                $experienceScore = $this->scoreExperienceMatch(
                    $userExperience,
                    (float) ($job->minimum_experience ?? 0)
                );
                $locationScore = $this->scoreLocationMatch(
                    $userCity,
                    (string) ($job->location ?? ''),
                    (string) ($job->workplace ?? '')
                );
                $scored[] = [
                    'job_id' => $jobId,
                    'score' => round(
                        ($skillScore * self::WEIGHT_SKILL)
                        + ($experienceScore * self::WEIGHT_EXPERIENCE)
                        + ($locationScore * self::WEIGHT_LOCATION),
                        2
                    ),
                ];
            }

            usort($scored, static fn (array $left, array $right): int => $right['score'] <=> $left['score']);
            $top = array_slice($scored, 0, $limit);
            if ($top) {
                $now = now();
                DB::table('recommendations')->insert(array_map(
                    static fn (array $row): array => [
                        'user_id' => $userId,
                        'job_id' => $row['job_id'],
                        'algorithm' => 'hybrid_v1',
                        'matching_score' => $row['score'],
                        'cosine_similarity' => 0,
                        'tfidf_score' => 0,
                        'collaborative_score' => 0,
                        'cluster_id' => null,
                        'generated_at' => $now,
                    ],
                    $top
                ));
            }

            return $top;
        });
    }

    private function scoreSkillMatch(array $userSkillSet, array $requiredSkills): float
    {
        if (!$requiredSkills) {
            return self::NEUTRAL_SKILL;
        }

        $matched = 0;
        foreach ($requiredSkills as $skillId) {
            if (isset($userSkillSet[$skillId])) {
                $matched++;
            }
        }

        return round(max(0, min(100, ($matched / count($requiredSkills)) * 100)), 2);
    }

    private function scoreExperienceMatch(float $userYears, float $jobMinimumYears): float
    {
        if ($jobMinimumYears <= 0 || $userYears >= $jobMinimumYears) {
            return 100.0;
        }

        $deficit = $jobMinimumYears - $userYears;
        if ($deficit <= 1) {
            return 75.0;
        }
        if ($deficit <= 3) {
            return 45.0;
        }

        return 20.0;
    }

    private function scoreLocationMatch(string $userCity, string $jobLocation, string $workplace): float
    {
        $jobLocation = mb_strtolower(trim($jobLocation));
        $workplace = mb_strtolower(trim($workplace));
        if ($workplace === 'remote') {
            return 90.0;
        }
        if ($jobLocation === '') {
            return self::NEUTRAL_LOCATION;
        }
        if ($userCity !== '' && str_contains($jobLocation, $userCity)) {
            return 100.0;
        }
        if (str_contains($jobLocation, 'remote')) {
            return 80.0;
        }
        if ($workplace === 'hybrid') {
            return 60.0;
        }

        return 30.0;
    }
}
