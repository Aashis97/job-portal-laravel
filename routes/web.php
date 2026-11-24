<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\SavedJobController;
use App\Http\Controllers\JobSeekerProfileController;
use App\Http\Controllers\ResumeController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\JobSeekerAccountController;
use App\Http\Controllers\EmployerCompanyProfileController;
use App\Http\Controllers\EmployerJobController;
use App\Http\Controllers\EmployerApplicantController;
use App\Http\Controllers\EmployerInterviewController;
use App\Http\Controllers\EmployerAnalyticsController;
use App\Http\Controllers\EmployerCandidateScoreController;
use App\Http\Controllers\EmployerCandidateToolsController;
use App\Http\Controllers\EmployerProfileImageController;
use App\Http\Controllers\EmployerAccountController;
use App\Http\Controllers\EmployerNotificationController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminUserActionController;
use App\Http\Controllers\AdminCompanyController;
use App\Http\Controllers\AdminJobController;
use App\Http\Controllers\AdminApplicationController;
use App\Http\Controllers\AdminTaxonomyController;
use App\Http\Controllers\AdminSettingsController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AdminAuditLogController;
use App\Http\Controllers\AdminNotificationController;
use App\Http\Controllers\AdminProfileController;
use App\Http\Controllers\AdminMessageController;
use App\Http\Controllers\AdminAnalyticsController;

Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::get('/index.php', [LandingController::class, 'index']);
Route::get('/uploads/company_logos/{filename}', [EmployerCompanyProfileController::class, 'logo'])
    ->where('filename', '[^/]+')
    ->name('company.logo');

Route::middleware(['auth', 'role:3'])->prefix('jobseeker')->group(function () {
    Route::get('/jobs.php', [JobController::class, 'index'])->name('jobs.index');
    Route::get('/job_details.php', [JobController::class, 'show'])->name('jobs.show');
    Route::post('/save_job.php', [JobController::class, 'save'])->name('jobs.save');
    Route::post('/unsave_job.php', [JobController::class, 'unsave'])->name('jobs.unsave');
    Route::get('/apply_job.php', [ApplicationController::class, 'create'])->name('applications.create');
    Route::post('/apply_job.php', [ApplicationController::class, 'store'])->name('applications.store');
    Route::get('/applications.php', [ApplicationController::class, 'index'])->name('applications.index');
    Route::post('/withdraw_application.php', [ApplicationController::class, 'withdraw'])->name('applications.withdraw');
    Route::get('/saved_jobs.php', [SavedJobController::class, 'index'])->name('saved_jobs.index');
    Route::get('/profile.php', [JobSeekerProfileController::class, 'show'])->name('jobseeker.profile');
    Route::post('/process_profile.php', [JobSeekerProfileController::class, 'update'])->name('jobseeker.profile.update');
    Route::get('/resume.php', [ResumeController::class, 'index'])->name('jobseeker.resume');
    Route::post('/process_resume.php', [ResumeController::class, 'store'])->name('jobseeker.resume.upload');
    Route::post('/delete_resume.php', [ResumeController::class, 'destroy'])->name('jobseeker.resume.delete');
    Route::get('/resume_file.php', [ResumeController::class, 'file'])->name('jobseeker.resume.file');
    Route::post('/resume_parser.php', [ResumeController::class, 'parse'])->name('jobseeker.resume.parse');
    Route::get('/recommendations.php', [RecommendationController::class, 'index'])->name('jobseeker.recommendations');
    Route::post('/generate_recommendations.php', [RecommendationController::class, 'generate'])->name('jobseeker.recommendations.generate');
    Route::get('/notifications.php', [NotificationController::class, 'index'])->name('jobseeker.notifications');
    Route::post('/mark_notification_read.php', [NotificationController::class, 'markRead'])->name('jobseeker.notifications.read');
    Route::post('/mark_all_notifications_read.php', [NotificationController::class, 'markAllRead'])->name('jobseeker.notifications.read-all');
    Route::get('/settings.php', [JobSeekerAccountController::class, 'settings'])->name('jobseeker.settings');
    Route::post('/change_password.php', [JobSeekerAccountController::class, 'changePassword'])->name('jobseeker.password.change');
    Route::get('/account.php', [JobSeekerAccountController::class, 'account'])->name('jobseeker.account');
    Route::post('/process_account_deactivation.php', [JobSeekerAccountController::class, 'deactivate'])->name('jobseeker.account.deactivate');
    Route::get('/messages.php', [MessageController::class, 'index'])->name('jobseeker.messages');
    Route::get('/send_message.php', [MessageController::class, 'compose'])->name('jobseeker.messages.compose');
    Route::post('/send_message.php', [MessageController::class, 'send'])->name('jobseeker.messages.send');
    Route::get('/conversation.php', [MessageController::class, 'conversation'])->name('jobseeker.conversation');
    Route::get('/view_message.php', [MessageController::class, 'viewMessage'])->name('jobseeker.message.view');
    Route::get('/profile-image/{filename}', [JobSeekerProfileController::class, 'image'])
        ->where('filename', '[^/]+')
        ->name('jobseeker.profile-image');
});

Route::middleware(['auth', 'role:2|3'])->post('/api/chat.php', [MessageController::class, 'chat'])->name('messages.chat');

Route::get('/authentication/login.php', [AuthController::class, 'loginForm'])->name('login');
Route::post('/authentication/process_login.php', [AuthController::class, 'login'])->name('login.submit');
Route::get('/authentication/register.php', [AuthController::class, 'registerForm'])->name('register');
Route::post('/authentication/process_register.php', [AuthController::class, 'register'])->name('register.submit');
Route::get('/authentication/logout.php', [AuthController::class, 'logout'])->name('logout');
Route::get('/authentication/forgot_password.php', [AuthController::class, 'forgotPasswordForm'])->name('auth.password.forgot');
Route::post('/authentication/process_forgot_password.php', [AuthController::class, 'forgotPassword'])->name('auth.password.email');
Route::get('/authentication/reset_password.php', [AuthController::class, 'resetPasswordForm'])->name('auth.password.reset');
Route::post('/authentication/process_reset_password.php', [AuthController::class, 'resetPassword'])->name('auth.password.update');
Route::get('/authentication/verify_email.php', [AuthController::class, 'verifyEmail'])->name('auth.verify-email');
Route::get('/authentication/resend_verification.php', [AuthController::class, 'resendVerificationForm'])->name('auth.verification.resend');
Route::post('/authentication/process_resend_verification.php', [AuthController::class, 'resendVerification'])->name('auth.verification.send');

Route::middleware(['auth', 'role:1'])->prefix('admin')->group(function () {
    Route::get('/dashboard.php', [DashboardController::class, 'admin'])->name('admin.dashboard');
    Route::get('/users.php', [AdminUserController::class, 'index'])->name('admin.users');
    Route::get('/user_details.php', [AdminUserController::class, 'show'])->name('admin.user-details');
    Route::post('/process_user_action.php', [AdminUserActionController::class, 'update'])->name('admin.user-action');
    Route::get('/companies.php', [AdminCompanyController::class, 'index'])->name('admin.companies');
    Route::get('/company_details.php', [AdminCompanyController::class, 'show'])->name('admin.company-details');
    Route::post('/process_company_action.php', [AdminCompanyController::class, 'updateStatus'])->name('admin.company-status.update');
    Route::get('/jobs.php', [AdminJobController::class, 'index'])->name('admin.jobs');
    Route::get('/job_details.php', [AdminJobController::class, 'show'])->name('admin.job-details');
    Route::post('/process_job_action.php', [AdminJobController::class, 'updateStatus'])->name('admin.job-status.update');
    Route::get('/applications.php', [AdminApplicationController::class, 'index'])->name('admin.applications');
    Route::get('/application_details.php', [AdminApplicationController::class, 'show'])->name('admin.application-details');
    Route::get('/application_resume.php', [AdminApplicationController::class, 'resume'])->name('admin.application-resume');
    Route::post('/process_application_action.php', [AdminApplicationController::class, 'update'])->name('admin.application-action');
    Route::get('/taxonomy.php', [AdminTaxonomyController::class, 'index'])->name('admin.taxonomy');
    Route::post('/process_taxonomy_action.php', [AdminTaxonomyController::class, 'update'])->name('admin.taxonomy.update');
    Route::get('/settings.php', [AdminSettingsController::class, 'index'])->name('admin.settings');
    Route::post('/process_settings.php', [AdminSettingsController::class, 'update'])->name('admin.settings.update');
    Route::get('/reports.php', [AdminReportController::class, 'index'])->name('admin.reports');
    Route::get('/report_details.php', [AdminReportController::class, 'show'])->name('admin.report-details');
    Route::post('/process_report_action.php', [AdminReportController::class, 'update'])->name('admin.report-action');
    Route::get('/audit_log.php', [AdminAuditLogController::class, 'index'])->name('admin.audit-log');
    Route::get('/notifications.php', [AdminNotificationController::class, 'index'])->name('admin.notifications');
    Route::post('/process_notification_action.php', [AdminNotificationController::class, 'update'])->name('admin.notification-action');
    Route::get('/broadcast.php', [AdminNotificationController::class, 'broadcast'])->name('admin.broadcast');
    Route::post('/process_broadcast.php', [AdminNotificationController::class, 'sendBroadcast'])->name('admin.broadcast.send');
    Route::get('/profile.php', [AdminProfileController::class, 'show'])->name('admin.profile');
    Route::post('/process_profile.php', [AdminProfileController::class, 'update'])->name('admin.profile.update');
    Route::get('/profile-image/{filename}', [AdminProfileController::class, 'image'])
        ->where('filename', '[^/]+')
        ->name('admin.profile-image');
    Route::get('/messages.php', [AdminMessageController::class, 'index'])->name('admin.messages');
    Route::get('/analytics.php', [AdminAnalyticsController::class, 'index'])->name('admin.analytics');
});

Route::middleware(['auth', 'role:2'])->prefix('employer')->group(function () {
    Route::get('/dashboard.php', [DashboardController::class, 'employer'])->name('employer.dashboard');
    Route::get('/settings.php', [EmployerAccountController::class, 'settings'])->name('employer.settings');
    Route::post('/process_settings.php', [EmployerAccountController::class, 'updateSettings'])->name('employer.settings.update');
    Route::get('/change_password.php', [EmployerAccountController::class, 'changePasswordForm'])->name('employer.password.form');
    Route::post('/process_change_password.php', [EmployerAccountController::class, 'changePassword'])->name('employer.password.update');
    Route::get('/account.php', [EmployerAccountController::class, 'account'])->name('employer.account');
    Route::post('/process_account_deactivation.php', [EmployerAccountController::class, 'deactivate'])->name('employer.account.deactivate');
    Route::get('/notifications.php', [EmployerNotificationController::class, 'index'])->name('employer.notifications');
    Route::get('/get_notifications.php', [EmployerNotificationController::class, 'get'])->name('employer.notifications.get');
    Route::post('/mark_notifications_read.php', [EmployerNotificationController::class, 'markRead'])->name('employer.notifications.read');
    Route::post('/mark_all_notifications_read.php', [EmployerNotificationController::class, 'markAllRead'])->name('employer.notifications.read-all');
    Route::post('/delete_notifications.php', [EmployerNotificationController::class, 'delete'])->name('employer.notifications.delete');
    Route::get('/messages.php', [MessageController::class, 'index'])->name('employer.messages');
    Route::get('/send_message.php', [MessageController::class, 'compose'])->name('employer.messages.compose');
    Route::post('/send_message.php', [MessageController::class, 'send'])->name('employer.messages.send');
    Route::get('/conversation.php', [MessageController::class, 'conversation'])->name('employer.conversation');
    Route::get('/manage_jobs.php', [EmployerJobController::class, 'index'])->name('employer.jobs');
    Route::get('/post_job.php', [EmployerJobController::class, 'create'])->name('employer.jobs.create');
    Route::post('/process_job.php', [EmployerJobController::class, 'store'])->name('employer.jobs.store');
    Route::get('/edit_job.php', [EmployerJobController::class, 'edit'])->name('employer.jobs.edit');
    Route::post('/process_edit_job.php', [EmployerJobController::class, 'update'])->name('employer.jobs.update');
    Route::post('/delete_job.php', [EmployerJobController::class, 'destroy'])->name('employer.jobs.delete');
    Route::get('/applications.php', [EmployerApplicantController::class, 'applications'])->name('employer.applications');
    Route::get('/applicants.php', [EmployerApplicantController::class, 'index'])->name('employer.applicants');
    Route::get('/applicant_details.php', [EmployerApplicantController::class, 'show'])->name('employer.applicant-details');
    Route::get('/ai.candidate_score.php', [EmployerCandidateScoreController::class, 'show'])->name('employer.candidate-score');
    Route::post('/process_applicant_status.php', [EmployerApplicantController::class, 'updateStatus'])->name('employer.applicant-status.update');
    Route::get('/view_resume.php', [EmployerApplicantController::class, 'resume'])->name('employer.applicant-resume');
    Route::get('/applicant-image/{applicationId}', [EmployerApplicantController::class, 'profileImage'])
        ->whereNumber('applicationId')
        ->name('employer.applicant-image');
    Route::get('/interviews.php', [EmployerInterviewController::class, 'index'])->name('employer.interviews');
    Route::get('/analytics.php', [EmployerAnalyticsController::class, 'index'])->name('employer.analytics');
    Route::get('/ai_candidate_ranking.php', [EmployerCandidateToolsController::class, 'ranking'])->name('employer.candidate-ranking');
    Route::post('/ai_rank_candidate.php', [EmployerCandidateToolsController::class, 'rank'])->name('employer.candidate-ranking.run');
    Route::get('/recommended_candidates.php', [EmployerCandidateToolsController::class, 'recommendations'])->name('employer.recommended-candidates');
    Route::get('/candidate_comparison.php', [EmployerCandidateToolsController::class, 'compare'])->name('employer.candidate-comparison');
    Route::get('/candidate_ai_insights.php', [EmployerCandidateToolsController::class, 'insights'])->name('employer.candidate-insights');
    Route::get('/schedule_interview.php', [EmployerInterviewController::class, 'schedule'])->name('employer.interviews.schedule');
    Route::post('/process_schedule_interview.php', [EmployerInterviewController::class, 'store'])->name('employer.interviews.store');
    Route::get('/interview_details.php', [EmployerInterviewController::class, 'details'])->name('employer.interview-details');
    Route::get('/edit_interview.php', [EmployerInterviewController::class, 'edit'])->name('employer.interviews.edit');
    Route::post('/process_edit_interview.php', [EmployerInterviewController::class, 'update'])->name('employer.interviews.update');
    Route::get('/cancel_interview.php', [EmployerInterviewController::class, 'cancel'])->name('employer.interviews.cancel');
    Route::post('/process_interview_status.php', [EmployerInterviewController::class, 'updateStatus'])->name('employer.interviews.status');
    Route::get('/company_profile.php', [EmployerCompanyProfileController::class, 'show'])->name('employer.company-profile');
    Route::post('/process_company_profile.php', [EmployerCompanyProfileController::class, 'update'])->name('employer.company-profile.update');
    Route::get('/profile_image.php', [EmployerProfileImageController::class, 'show'])->name('employer.profile-image.form');
    Route::post('/process_profile_image.php', [EmployerProfileImageController::class, 'update'])->name('employer.profile-image.update');
    Route::get('/profile-image/{filename}', [EmployerProfileImageController::class, 'image'])
        ->where('filename', '[^/]+')
        ->name('employer.profile-image');
});

Route::middleware(['auth', 'role:3'])->prefix('jobseeker')->group(function () {
    Route::get('/dashboard.php', [DashboardController::class, 'jobSeeker'])->name('jobseeker.dashboard');
});
