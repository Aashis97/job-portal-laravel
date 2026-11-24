<?php

namespace App\Http\Controllers;

use App\Services\EmployerCandidateScoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployerCandidateToolsController extends Controller
{
    private const APPLICATION_STATUSES = [
        'Applied',
        'Under Review',
        'Shortlisted',
        'Interview Scheduled',
        'Selected',
    ];

    public function ranking(Request $request)
    {
        $employer = $this->approvedEmployer($request);
        $jobs = DB::table('jobs as j')
            ->leftJoin('applications as a', function ($join) {
                $join->on('a.job_id', '=', 'j.job_id')
                    ->whereNotIn('a.application_status', ['Rejected', 'Withdrawn']);
            })
            ->where('j.employer_id', $employer->employer_id)
            ->where('j.company_id', $employer->company_id)
            ->groupBy('j.job_id', 'j.job_title', 'j.job_status')
            ->orderByDesc('j.job_id')
            ->get([
                'j.job_id',
                'j.job_title',
                'j.job_status',
                DB::raw('COUNT(a.application_id) AS eligible_applications'),
            ]);

        return view('employer.candidate-ranking', compact('jobs'));
    }

    public function rank(Request $request, EmployerCandidateScoringService $scoring)
    {
        $validated = $request->validate([
            'job_id' => ['required', 'integer', 'min:1'],
        ]);
        $employer = $this->approvedEmployer($request);
        $job = DB::table('jobs')
            ->where('job_id', $validated['job_id'])
            ->where('employer_id', $employer->employer_id)
            ->where('company_id', $employer->company_id)
            ->first(['job_id', 'job_title']);

        if (!$job) {
            return redirect()->route('employer.candidate-ranking')
                ->with('error', 'Job not found or you do not have permission to rank its candidates.');
        }

        $requiredSkills = DB::table('job_skills as js')
            ->join('skills as s', 's.skill_id', '=', 'js.skill_id')
            ->where('js.job_id', $job->job_id)
            ->orderBy('s.skill_name')
            ->pluck('s.skill_name')
            ->all();
        $applications = DB::table('applications as a')
            ->join('users as u', 'u.user_id', '=', 'a.user_id')
            ->leftJoin('job_seekers as js', 'js.user_id', '=', 'a.user_id')
            ->where('a.job_id', $job->job_id)
            ->whereNotIn('a.application_status', ['Rejected', 'Withdrawn'])
            ->get([
                'a.application_id',
                'a.user_id',
                'js.resume_file',
                'js.profile_completed',
            ]);

        if ($applications->isEmpty()) {
            return redirect()->route('employer.candidate-ranking')
                ->with('warning', 'There are no eligible candidates to rank for this job.');
        }

        $skillsByUser = [];
        foreach (DB::table('user_skills as us')
            ->join('skills as s', 's.skill_id', '=', 'us.skill_id')
            ->whereIn('us.user_id', $applications->pluck('user_id')->unique())
            ->orderBy('s.skill_name')
            ->get(['us.user_id', 's.skill_name']) as $row) {
            $skillsByUser[(int) $row->user_id][] = $row->skill_name;
        }

        $ranked = $applications->map(function ($application) use ($requiredSkills, $skillsByUser, $scoring) {
            $result = $scoring->score(
                $requiredSkills,
                $skillsByUser[(int) $application->user_id] ?? [],
                (bool) ($application->profile_completed ?? false),
                (string) ($application->resume_file ?? '')
            );

            return [
                'application_id' => (int) $application->application_id,
                'skill_match' => $result['skillMatch'],
                'overall' => $result['overall'],
            ];
        })->all();

        usort($ranked, static function (array $left, array $right): int {
            return ($right['overall'] <=> $left['overall'])
                ?: ($right['skill_match'] <=> $left['skill_match'])
                ?: ($left['application_id'] <=> $right['application_id']);
        });

        DB::transaction(function () use ($ranked) {
            foreach ($ranked as $index => $candidate) {
                DB::table('applications')
                    ->where('application_id', $candidate['application_id'])
                    ->update([
                        'skill_match_score' => $candidate['skill_match'],
                        'ai_rank' => $index + 1,
                    ]);
            }
        });

        return redirect()->route('employer.recommended-candidates', ['job_id' => $job->job_id])
            ->with('success', count($ranked) . ' candidates ranked for ' . $job->job_title . '.');
    }

    public function recommendations(Request $request)
    {
        $employer = $this->approvedEmployer($request);
        $jobId = filter_var($request->query('job_id', 0), FILTER_VALIDATE_INT);
        $jobId = $jobId !== false && $jobId > 0 ? $jobId : 0;
        $minimumScoreInput = $request->query('minimum_score', '0');
        $minimumScore = is_scalar($minimumScoreInput) && is_numeric((string) $minimumScoreInput)
            ? max(0.0, min(100.0, (float) $minimumScoreInput))
            : 0.0;
        $statusFilter = $request->query('status', '');
        $statusFilter = is_string($statusFilter) && in_array($statusFilter, self::APPLICATION_STATUSES, true)
            ? $statusFilter
            : '';
        $search = $request->query('search', '');
        $search = is_string($search) ? mb_substr(trim($search), 0, 100) : '';

        $jobs = DB::table('jobs as j')
            ->leftJoin('applications as a', function ($join) {
                $join->on('a.job_id', '=', 'j.job_id')
                    ->whereNotIn('a.application_status', ['Rejected', 'Withdrawn']);
            })
            ->where('j.employer_id', $employer->employer_id)
            ->where('j.company_id', $employer->company_id)
            ->groupBy('j.job_id', 'j.job_title', 'j.job_status')
            ->orderByDesc('j.job_id')
            ->get([
                'j.job_id',
                'j.job_title',
                'j.job_status',
                DB::raw('COUNT(DISTINCT a.application_id) AS application_count'),
            ]);
        if (!$jobs->contains('job_id', $jobId)) {
            $jobId = 0;
        }

        $query = DB::table('applications as a')
            ->join('jobs as j', 'j.job_id', '=', 'a.job_id')
            ->join('users as u', 'u.user_id', '=', 'a.user_id')
            ->leftJoin('job_seekers as js', 'js.user_id', '=', 'a.user_id')
            ->leftJoin('categories as c', 'c.category_id', '=', 'j.category_id')
            ->where('j.employer_id', $employer->employer_id)
            ->where('j.company_id', $employer->company_id)
            ->whereNotIn('a.application_status', ['Rejected', 'Withdrawn']);
        if ($jobId > 0) {
            $query->where('j.job_id', $jobId);
        }
        if ($minimumScore > 0) {
            $query->where('a.skill_match_score', '>=', $minimumScore);
        }
        if ($statusFilter !== '') {
            $query->where('a.application_status', $statusFilter);
        }
        if ($search !== '') {
            $query->where(function ($where) use ($search) {
                $term = '%' . $search . '%';
                $where->where('u.full_name', 'like', $term)
                    ->orWhere('u.email', 'like', $term)
                    ->orWhere('j.job_title', 'like', $term);
            });
        }
        $candidates = $query
            ->orderByRaw('COALESCE(a.skill_match_score, 0) DESC')
            ->orderByRaw('CASE WHEN a.ai_rank IS NULL THEN 999999 ELSE a.ai_rank END ASC')
            ->orderByDesc('a.application_id')
            ->get([
                'a.application_id',
                'a.job_id',
                'a.user_id',
                'a.application_status',
                'a.skill_match_score',
                'a.ai_rank',
                'u.full_name',
                'u.email',
                'u.profile_image',
                'js.resume_file',
                'js.profile_completed',
                'js.availability',
                'j.job_title',
                'c.category_name',
            ]);

        $skillsByUser = [];
        if ($candidates->isNotEmpty()) {
            foreach (DB::table('user_skills as us')
                ->join('skills as s', 's.skill_id', '=', 'us.skill_id')
                ->whereIn('us.user_id', $candidates->pluck('user_id')->unique())
                ->orderBy('s.skill_name')
                ->get(['us.user_id', 's.skill_name']) as $skill) {
                $skillsByUser[(int) $skill->user_id][] = $skill->skill_name;
            }
        }

        $scoreCounts = ['excellent' => 0, 'strong' => 0, 'good' => 0, 'moderate' => 0, 'low' => 0];
        $totalScore = 0.0;
        foreach ($candidates as $candidate) {
            $score = max(0.0, min(100.0, (float) ($candidate->skill_match_score ?? 0)));
            $totalScore += $score;
            $bucket = match (true) {
                $score >= 90 => 'excellent',
                $score >= 80 => 'strong',
                $score >= 70 => 'good',
                $score >= 60 => 'moderate',
                default => 'low',
            };
            $scoreCounts[$bucket]++;
        }

        return view('employer.recommended-candidates', [
            'employer' => $employer,
            'jobs' => $jobs,
            'jobId' => $jobId,
            'minimumScore' => $minimumScore,
            'statusFilter' => $statusFilter,
            'allowedApplicationStatuses' => self::APPLICATION_STATUSES,
            'search' => $search,
            'candidates' => $candidates,
            'skillsByUser' => $skillsByUser,
            'scoreCounts' => $scoreCounts,
            'totalRecommended' => $candidates->count(),
            'averageScore' => $candidates->isNotEmpty() ? round($totalScore / $candidates->count(), 1) : 0.0,
        ]);
    }

    public function compare(Request $request, EmployerCandidateScoringService $scoring)
    {
        $employer = $this->approvedEmployer($request);
        $idsRaw = $request->query('ids', '');
        if (!is_string($idsRaw) || trim($idsRaw) === '') {
            return $this->comparisonRedirect('Please select candidates to compare.');
        }

        $applicationIds = array_values(array_unique(array_map(
            'intval',
            array_filter(
                array_map('trim', explode(',', $idsRaw)),
                static fn ($value): bool => ctype_digit((string) $value)
            )
        )));
        if (count($applicationIds) < 2) {
            return $this->comparisonRedirect('Please select at least two candidates to compare.');
        }
        if (count($applicationIds) > 4) {
            return $this->comparisonRedirect('You can compare a maximum of four candidates at a time.');
        }
        if (in_array(0, $applicationIds, true)) {
            return $this->comparisonRedirect('Invalid candidate selection.');
        }

        $rows = DB::table('applications as a')
            ->join('users as u', 'u.user_id', '=', 'a.user_id')
            ->join('job_seekers as js', 'js.user_id', '=', 'a.user_id')
            ->join('jobs as j', 'j.job_id', '=', 'a.job_id')
            ->join('companies as c', 'c.company_id', '=', 'j.company_id')
            ->leftJoin('categories as cat', 'cat.category_id', '=', 'j.category_id')
            ->whereIn('a.application_id', $applicationIds)
            ->where('j.employer_id', $employer->employer_id)
            ->where('j.company_id', $employer->company_id)
            ->whereNotIn('a.application_status', ['Rejected', 'Withdrawn'])
            ->get([
                'a.application_id',
                'a.job_id',
                'a.user_id',
                'a.application_status',
                'a.ai_rank',
                'u.full_name',
                'u.email',
                'js.resume_file',
                'js.profile_completed',
                'js.availability',
                'j.job_title',
                'c.company_name',
                'cat.category_name',
            ]);
        if ($rows->count() !== count($applicationIds)) {
            return $this->comparisonRedirect('One or more selected candidates are not available for comparison.');
        }

        $jobIds = $rows->pluck('job_id')->unique();
        if ($jobIds->count() !== 1) {
            return $this->comparisonRedirect('Candidates must belong to the same job to be compared.');
        }

        $jobId = (int) $jobIds->first();
        $requiredSkills = DB::table('job_skills as js')
            ->join('skills as s', 's.skill_id', '=', 'js.skill_id')
            ->where('js.job_id', $jobId)
            ->orderBy('s.skill_name')
            ->pluck('s.skill_name')
            ->all();
        $candidateIds = $rows->pluck('user_id')->unique();
        $skillsByUser = [];
        foreach (DB::table('user_skills as us')
            ->join('skills as s', 's.skill_id', '=', 'us.skill_id')
            ->whereIn('us.user_id', $candidateIds)
            ->orderBy('s.skill_name')
            ->get(['us.user_id', 's.skill_name']) as $row) {
            $skillsByUser[(int) $row->user_id][] = $row->skill_name;
        }

        $requiredSkillSet = array_fill_keys(array_map(
            static fn ($skill): string => mb_strtolower(trim((string) preg_replace('/\s+/', ' ', $skill))),
            $requiredSkills
        ), true);
        $candidates = $rows->map(function ($candidate) use ($scoring, $requiredSkills, $skillsByUser, $requiredSkillSet) {
            $candidateSkills = $skillsByUser[(int) $candidate->user_id] ?? [];
            $result = $scoring->score(
                $requiredSkills,
                $candidateSkills,
                (bool) ($candidate->profile_completed ?? false),
                (string) ($candidate->resume_file ?? ''),
                true
            );
            $additionalSkills = array_values(array_filter($candidateSkills, static function ($skill) use ($requiredSkillSet) {
                $normalized = mb_strtolower(trim((string) preg_replace('/\s+/', ' ', $skill)));
                return $normalized !== '' && !isset($requiredSkillSet[$normalized]);
            }));

            return [
                'applicationId' => (int) $candidate->application_id,
                'userId' => (int) $candidate->user_id,
                'name' => (string) $candidate->full_name,
                'email' => (string) $candidate->email,
                'jobTitle' => (string) $candidate->job_title,
                'companyName' => (string) $candidate->company_name,
                'categoryName' => (string) ($candidate->category_name ?? ''),
                'status' => (string) ($candidate->application_status ?? 'Applied'),
                'skillMatch' => $result['skillMatch'],
                'experienceMatch' => $result['experienceMatch'],
                'profileScore' => $result['profileScore'],
                'resumeScore' => $result['resumeScore'],
                'readinessScore' => $result['readiness'],
                'overallScore' => $result['overall'],
                'recommendation' => $result['recommendation'],
                'aiRank' => $candidate->ai_rank !== null ? (int) $candidate->ai_rank : null,
                'availability' => (string) ($candidate->availability ?? ''),
                'skills' => $candidateSkills,
                'matchedSkills' => $result['matchedSkills'],
                'missingSkills' => $result['missingSkills'],
                'additionalSkills' => $additionalSkills,
            ];
        })->all();

        usort($candidates, static function (array $left, array $right): int {
            return ($right['overallScore'] <=> $left['overallScore'])
                ?: ($right['skillMatch'] <=> $left['skillMatch'])
                ?: ($left['applicationId'] <=> $right['applicationId']);
        });
        foreach ($candidates as $index => &$candidate) {
            $candidate['comparisonRank'] = $index + 1;
            $candidate['scoreClass'] = match (true) {
                $candidate['overallScore'] >= 90 => 'score-excellent',
                $candidate['overallScore'] >= 80 => 'score-strong',
                $candidate['overallScore'] >= 70 => 'score-good',
                $candidate['overallScore'] >= 60 => 'score-moderate',
                default => 'score-low',
            };
        }
        unset($candidate);

        return view('employer.candidate-comparison', [
            'candidates' => $candidates,
            'jobId' => $jobId,
            'jobTitle' => $candidates[0]['jobTitle'],
            'companyName' => $candidates[0]['companyName'],
        ]);
    }

    public function insights(Request $request, EmployerCandidateScoringService $scoring)
    {
        $validated = $request->validate([
            'application_id' => ['required', 'integer', 'min:1'],
        ]);
        $employer = $this->approvedEmployer($request);
        $application = DB::table('applications as a')
            ->join('jobs as j', 'j.job_id', '=', 'a.job_id')
            ->join('users as u', 'u.user_id', '=', 'a.user_id')
            ->leftJoin('job_seekers as js', 'js.user_id', '=', 'a.user_id')
            ->where('a.application_id', $validated['application_id'])
            ->where('j.employer_id', $employer->employer_id)
            ->where('j.company_id', $employer->company_id)
            ->first([
                'a.application_id',
                'a.job_id',
                'a.user_id',
                'a.application_status',
                'a.skill_match_score',
                'a.ai_rank',
                'j.job_title',
                'j.experience_level',
                'j.job_status',
                'u.full_name',
                'u.email',
                'js.resume_file',
                'js.profile_completed',
                'js.availability',
            ]);
        abort_unless($application, 404, 'Application not found.');

        $requiredSkills = DB::table('job_skills as js')
            ->join('skills as s', 's.skill_id', '=', 'js.skill_id')
            ->where('js.job_id', $application->job_id)
            ->orderBy('s.skill_name')
            ->pluck('s.skill_name')
            ->all();
        $candidateSkills = DB::table('user_skills as us')
            ->join('skills as s', 's.skill_id', '=', 'us.skill_id')
            ->where('us.user_id', $application->user_id)
            ->orderBy('s.skill_name')
            ->pluck('s.skill_name')
            ->all();
        $score = $scoring->score(
            $requiredSkills,
            $candidateSkills,
            (bool) ($application->profile_completed ?? false),
            (string) ($application->resume_file ?? '')
        );

        $requiredSkillSet = array_fill_keys(array_map(
            static fn ($skill): string => mb_strtolower(trim((string) preg_replace('/\s+/', ' ', $skill))),
            $requiredSkills
        ), true);
        $additionalSkills = array_values(array_filter($candidateSkills, static function ($skill) use ($requiredSkillSet) {
            $normalized = mb_strtolower(trim((string) preg_replace('/\s+/', ' ', $skill)));
            return $normalized !== '' && !isset($requiredSkillSet[$normalized]);
        }));

        $strengths = [];
        if ($score['skillMatch'] >= 90) {
            $strengths[] = 'Candidate matches nearly all required job skills.';
        } elseif ($score['skillMatch'] >= 75) {
            $strengths[] = 'Candidate has a strong match with the required skills.';
        } elseif ($score['skillMatch'] >= 50) {
            $strengths[] = 'Candidate has several of the required skills.';
        }
        if ((bool) ($application->profile_completed ?? false)) {
            $strengths[] = 'Candidate profile is marked as completed.';
        }
        if (trim((string) ($application->resume_file ?? '')) !== '') {
            $strengths[] = 'A resume is available for employer review.';
        }
        if ($additionalSkills) {
            $strengths[] = 'Candidate has additional skills beyond the current job requirements.';
        }

        $areasToReview = [];
        if ($score['missingSkills']) {
            $areasToReview[] = 'Candidate is missing ' . count($score['missingSkills']) . ' required job skill(s).';
        }
        if (!(bool) ($application->profile_completed ?? false)) {
            $areasToReview[] = 'Candidate profile is not marked as completed.';
        }
        if (trim((string) ($application->resume_file ?? '')) === '') {
            $areasToReview[] = 'No resume is currently available.';
        }
        $areasToReview[] = 'Candidate experience-level compatibility should be verified manually because a confirmed candidate experience field is not currently available.';

        $humanReviewRecommendation = match (true) {
            $score['overall'] >= 90 => 'Prioritize this candidate for detailed human review. The available application data indicates a very strong match.',
            $score['overall'] >= 80 => 'Consider this candidate for shortlisting after reviewing the resume and interview requirements.',
            $score['overall'] >= 70 => 'Candidate appears potentially suitable. Review the missing skills and resume before shortlisting.',
            $score['overall'] >= 60 => 'Additional review is recommended before making a shortlist decision.',
            default => 'The candidate has several areas requiring review before being considered for the next recruitment stage.',
        };
        $insightsData = [
            'applicationId' => (int) $application->application_id,
            'jobId' => (int) $application->job_id,
            'userId' => (int) $application->user_id,
            'overallScore' => $score['overall'],
            'skillMatch' => $score['skillMatch'],
            'experienceMatch' => $score['experienceMatch'],
            'profileScore' => $score['profileScore'],
            'resumeScore' => $score['resumeScore'],
            'readinessScore' => $score['readiness'],
            'matchedSkills' => $score['matchedSkills'],
            'missingSkills' => $score['missingSkills'],
            'additionalSkills' => $additionalSkills,
            'recommendation' => $score['recommendation'],
            'aiRank' => $application->ai_rank,
        ];

        return view('employer.candidate-insights', compact(
            'application',
            'requiredSkills',
            'candidateSkills',
            'additionalSkills',
            'score',
            'strengths',
            'areasToReview',
            'humanReviewRecommendation',
            'insightsData'
        ));
    }

    private function comparisonRedirect(string $message)
    {
        return redirect()->route('employer.recommended-candidates')->with('error', $message);
    }

    private function approvedEmployer(Request $request): object
    {
        $employer = DB::table('employers as e')
            ->join('companies as c', 'c.company_id', '=', 'e.company_id')
            ->where('e.user_id', $request->user()->user_id)
            ->first(['e.employer_id', 'e.company_id', 'c.company_name', 'c.status as company_status']);
        abort_unless($employer, 403, 'Employer profile could not be found.');
        if ($employer->company_status !== 'Approved') {
            abort(403, 'Your company must be approved to use candidate tools.');
        }

        return $employer;
    }
}
