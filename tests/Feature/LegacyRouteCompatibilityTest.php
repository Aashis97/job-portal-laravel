<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegacyRouteCompatibilityTest extends TestCase
{
    public function test_legacy_authentication_urls_render_laravel_pages(): void
    {
        $this->get('/authentication/login.php')->assertOk();
        $this->get('/authentication/register.php')->assertOk();
    }

    public function test_role_specific_pages_require_authentication(): void
    {
        $loginUrl = route('login');

        $this->get('/admin/dashboard.php')->assertRedirect($loginUrl);
        $this->get('/employer/dashboard.php')->assertRedirect($loginUrl);
        $this->get('/jobseeker/dashboard.php')->assertRedirect($loginUrl);
        $this->get('/jobseeker/jobs.php')->assertRedirect($loginUrl);
    }
}
