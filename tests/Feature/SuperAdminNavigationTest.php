<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminNavigationTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => 'super_admin']);
    }

    public function test_super_admin_sidebar_has_back_to_home_page_pointing_to_public_home(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('super.admin.dashboard'))
            ->assertOk()
            ->assertSee('Back to Home Page')
            // The link target is the public home route, not a dashboard route.
            ->assertSee(route('home', [], false), false);
    }

    public function test_super_admin_sidebar_has_settings_pointing_to_super_admin_settings(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('super.admin.dashboard'))
            ->assertOk()
            ->assertSee('Settings')
            ->assertSee(route('super.admin.settings', [], false), false);
    }

    public function test_super_admin_settings_page_links_to_profile_manage_access_and_home(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('super.admin.settings'))
            ->assertOk()
            ->assertSee('Profile')
            ->assertSee('Manage Access')
            ->assertSee('Back to Home Page')
            ->assertSee(route('super.admin.settings.profile', [], false), false)
            ->assertSee(route('super.admin.settings.access', [], false), false);
    }

    public function test_super_admin_settings_profile_and_access_render_shared_pages(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->get(route('super.admin.settings.profile'))->assertOk();
        $this->actingAs($admin)->get(route('super.admin.settings.access'))->assertOk();
    }

    public function test_super_admin_dashboard_has_no_account_dropdown(): void
    {
        $content = $this->actingAs($this->superAdmin())
            ->get(route('super.admin.dashboard'))
            ->assertOk()
            ->getContent();

        // The Super Admin account/avatar dropdown is removed from the dashboard layout.
        $this->assertStringNotContainsString('adminProfileDropdown', $content);
        $this->assertStringNotContainsString('data-bs-toggle="dropdown"', $content);
        $this->assertStringNotContainsString('dropdown-menu', $content);

        // Sidebar navigation (Settings + Back to Home Page) still works.
        $this->assertStringContainsString('Back to Home Page', $content);
        $this->assertStringContainsString(route('home', [], false), $content);
    }

    public function test_regular_user_settings_route_is_unchanged_and_distinct(): void
    {
        // The user-facing profile.edit route still resolves to the shared profile controller.
        $this->assertSame('/profile', parse_url(route('profile.edit'), PHP_URL_PATH));
        $this->assertSame('/profile/access', parse_url(route('profile.access'), PHP_URL_PATH));

        $student = User::factory()->create(['role' => 'student']);
        $this->actingAs($student)->get(route('profile.edit'))->assertOk();
    }

    public function test_unauthorized_users_cannot_access_super_admin_settings_routes(): void
    {
        foreach (['student', 'admin'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('super.admin.settings'))->assertForbidden();
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('super.admin.settings.profile'))->assertForbidden();
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('super.admin.settings.access'))->assertForbidden();
        }
    }

    public function test_guests_are_redirected_to_login_from_super_admin_settings(): void
    {
        $this->get(route('super.admin.settings'))->assertRedirect(route('login'));
    }
}
