<?php

namespace App\Services;

class EmployerCandidateScoringService
{
    /**
     * @return array{
     *     matchedSkills: array<int, string>,
     *     missingSkills: array<int, string>,
     *     skillMatch: float,
     *     experienceMatch: float,
     *     profileScore: float,
     *     resumeScore: float,
     *     readiness: float,
     *     overall: float,
     *     recommendation: string,
     *     recommendationClass: string
     * }
     */
    public function score(
        array $requiredSkills,
        array $candidateSkills,
        bool $profileCompleted,
        string $resumeFile,
        bool $neutralSkillMatchWhenNoRequirements = false
    ): array {
        $candidateSkillSet = [];
        foreach ($candidateSkills as $skill) {
            $normalized = $this->normalize((string) $skill);
            if ($normalized !== '') {
                $candidateSkillSet[$normalized] = true;
            }
        }

        $matchedSkills = [];
        $missingSkills = [];
        foreach ($requiredSkills as $skill) {
            $name = trim((string) $skill);
            if ($name !== '' && isset($candidateSkillSet[$this->normalize($name)])) {
                $matchedSkills[] = $name;
            } else {
                $missingSkills[] = $name;
            }
        }

        $skillMatch = count($requiredSkills) > 0
            ? round((count($matchedSkills) / count($requiredSkills)) * 100, 2)
            : ($neutralSkillMatchWhenNoRequirements ? 50.0 : 100.0);
        $experienceMatch = 50.0;
        $profileScore = $profileCompleted ? 100.0 : 0.0;
        $resumeScore = trim($resumeFile) !== '' ? 100.0 : 0.0;
        $readinessComponents = (int) $profileCompleted
            + (int) ($resumeScore > 0)
            + (int) (count($candidateSkills) > 0);
        $readiness = round(($readinessComponents / 3) * 100, 2);
        $overall = round(
            ($skillMatch * 0.50)
            + ($experienceMatch * 0.20)
            + ($profileScore * 0.10)
            + ($resumeScore * 0.10)
            + ($readiness * 0.10),
            2
        );

        [$recommendation, $recommendationClass] = match (true) {
            $overall >= 90 => ['Excellent Match', 'excellent'],
            $overall >= 80 => ['Strong Match', 'strong'],
            $overall >= 70 => ['Good Match', 'good'],
            $overall >= 60 => ['Moderate Match', 'moderate'],
            default => ['Low Match', 'low'],
        };

        return compact(
            'matchedSkills',
            'missingSkills',
            'skillMatch',
            'experienceMatch',
            'profileScore',
            'resumeScore',
            'readiness',
            'overall',
            'recommendation',
            'recommendationClass'
        );
    }

    private function normalize(string $skill): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/', ' ', $skill)));
    }
}
