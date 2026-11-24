<?php

namespace App\Http\Controllers;

use App\Services\EmployerCandidateScoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployerCandidateScoreController extends Controller
{
    public function show(Request $request, EmployerCandidateScoringService $scoring)
    {
        $validated = $request->validate([
            'application_id' => ['required', 'integer', 'min:1'],
        ]);
        $employer = DB::table('employers as e')
            ->join('companies as c', 'c.company_id', '=', 'e.company_id')
            ->where('e.user_id', $request->user()->user_id)
            ->first([
                'e.employer_id',
                'e.company_id',
                'c.status as company_status',
            ]);

        abort_unless($employer, 403, 'Employer profile could not be found.');
        if ($employer->company_status !== 'Approved') {
            return redirect()->route('employer.applications')
                ->with('error', 'Your company must be approved before using AI candidate scoring.');
        }

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
                'a.ai_rank',
                'j.job_title',
                'j.job_status',
                'u.full_name',
                'u.email',
                'js.resume_file',
                'js.profile_completed',
                'js.availability',
            ]);

        abort_unless($application, 404, 'Application not found.');
        if (in_array($application->application_status, ['Rejected', 'Withdrawn'], true)) {
            return redirect()->route('employer.applicant-details', [
                'application_id' => $application->application_id,
            ])->with('error', 'Rejected or withdrawn applications cannot be scored.');
        }

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

        DB::table('applications')
            ->where('application_id', $application->application_id)
            ->update(['skill_match_score' => $score['skillMatch']]);

        return view('employer.candidate-score', [
            'application' => $application,
            'candidateSkills' => $candidateSkills,
            'score' => $score,
        ]);
    }
}
