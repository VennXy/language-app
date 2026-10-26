<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminSettingsNavigationTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    public function test_super_admin_settings_and_profile_and_access_open_super_admin_pages(): void
    {
        $superAdmin = $this->user('super_admin');

        // Settings opens the dedicated Super Admin settings page.
        $this->actingAs($superAdmin)->get(route('super.admin.settings'))
            ->assertOk()
            ->assertSee(route('super.admin.settings.profile'))
            ->assertSee(route('super.admin.settings.access'))
            ->assertDontSee('student.dashboard');

        // Profile opens the intended Super Admin profile page directly (no redirect to a dashboard).
        $profileResponse = $this->actingAs($superAdmin)->get(route('super.admin.settings.profile'));
        $profileResponse->assertOk();
        $this->assertFalse(
            $profileResponse->isRedirect(route('student.dashboard')),
            'Super Admin profile page must not redirect to the User Dashboard.'
        );

        // Manage Access opens the intended Super Admin manage access page directly.
        $accessResponse = $this->actingAs($superAdmin)->get(route('super.admin.settings.access'));
        $accessResponse->assertOk();
        $this->assertFalse(
            $accessResponse->isRedirect(route('student.dashboard')),
            'Super Admin manage access page must not redirect to the User Dashboard.'
        );
    }

    public function test_super_admin_profile_pages_keep_navigation_inside_super_admin_area(): void
    {
        $superAdmin = $this->user('super_admin');

        // The shared profile/access views must link Super Admins to the super admin routes,
        // never to the regular user profile routes.
        $this->actingAs($superAdmin)->get(route('super.admin.settings.profile'))
            ->assertOk()
            ->assertSee(route('super.admin.settings.access'))
            ->assertDontSee('href="' . route('profile.access') . '"', false);

        $this->actingAs($superAdmin)->get(route('super.admin.settings.access'))
            ->assertOk()
            ->assertSee(route('super.admin.settings.profile'))
            ->assertDontSee('href="' . route('profile.edit') . '"', false);
    }

    public function test_super_admin_profile_dropdown_contains_only_dashboard(): void
    {
        $superAdmin = $this->user('super_admin');

        $response = $this->actingAs($superAdmin)->get(route('super.admin.dashboard'))->assertOk();

        $content = $response->getContent();

        // The account/avatar dropdown control is removed from the Super Admin dashboard layout.
        $this->assertStringNotContainsString('id="adminProfileDropdown"', $content);
        $this->assertStringNotContainsString('data-bs-toggle="dropdown"', $content);

        // Those features still exist elsewhere, per the new layout:
        //  - Back to Home Page + a single Settings entry live in the sidebar.
        //  - Profile + Manage Access are reached FROM the Settings page (not the sidebar).
        $this->assertStringContainsString(route('home'), $content);                       // Back to Home Page
        $this->assertStringContainsString(route('super.admin.settings'), $content);       // Settings

        // The public Home Page dropdown is a SEPARATE component (partials/app-navbar) and must
        // still offer a role-correct Dashboard + Logout. It is not the control we removed above.
        $homeContent = $this->actingAs($superAdmin)->get(route('home'))->assertOk()->getContent();
        $this->assertStringContainsString('data-bs-toggle="dropdown"', $homeContent);
        $this->assertStringContainsString('Logout', $homeContent);
        $this->assertStringContainsString(route('super.admin.dashboard'), $homeContent); // Dashboard → Super Admin Dashboard

        // The dashboard sidebar must NOT expose separate My Profile / Manage Access links any more.
        $this->assertStringNotContainsString('href="' . route('super.admin.settings.profile') . '"', $content);
        $this->assertStringNotContainsString('href="' . route('super.admin.settings.access') . '"', $content);

        // The dedicated Super Admin Settings page is the single entry point for Profile + Manage Access.
        $this->actingAs($superAdmin)->get(route('super.admin.settings'))->assertOk()
            ->assertSee(route('super.admin.settings.profile'))
            ->assertSee(route('super.admin.settings.access'));
    }

    public function test_regular_user_settings_and_profile_navigation_is_unchanged(): void
    {
        $student = $this->user('student');

        // Regular users keep using their own profile routes and still render them fine.
        $this->actingAs($student)->get(route('profile.edit'))->assertOk()
            ->assertSee('href="' . route('profile.edit') . '"', false)
            ->assertSee('href="' . route('profile.access') . '"', false);

        $this->actingAs($student)->get(route('profile.access'))->assertOk()
            ->assertSee('href="' . route('profile.edit') . '"', false)
            ->assertSee('href="' . route('profile.access') . '"', false);

        // Regular users cannot reach the Super Admin settings routes.
        $this->actingAs($student)->get(route('super.admin.settings'))->assertForbidden();
        $this->actingAs($student)->get(route('super.admin.settings.profile'))->assertForbidden();
        $this->actingAs($student)->get(route('super.admin.settings.access'))->assertForbidden();
    }

    public function test_regular_admin_dashboard_does_not_leak_super_admin_settings_urls(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()
            ->assertDontSee(route('super.admin.settings'))
            ->assertDontSee(route('super.admin.settings.profile'))
            ->assertDontSee(route('super.admin.settings.access'));
    }
}
