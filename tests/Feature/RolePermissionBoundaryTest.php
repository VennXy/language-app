<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_is_blocked_from_super_admin_management_routes(): void
    {
        $admin = $this->user('admin');
        $superAdmin = $this->user('super_admin');
        $originalEmail = $superAdmin->email;

        $this->actingAs($admin)
            ->get(route('super.admin.admins.index'))
            ->assertForbidden();
        $this->post(route('super.admin.admins.store'), [])->assertForbidden();
        $this->get(route('super.admin.admins.show', $superAdmin))->assertForbidden();
        $this->put(route('super.admin.admins.update', $superAdmin), [
            'name' => 'Changed Name',
            'email' => 'changed@example.test',
        ])->assertForbidden();
        $this->delete(route('super.admin.admins.destroy', $superAdmin))->assertForbidden();

        $superAdmin->refresh();
        $this->assertSame('super_admin', $superAdmin->role);
        $this->assertSame($originalEmail, $superAdmin->email);
    }

    public function test_admin_is_blocked_from_lesson_category_exam_management_and_review(): void
    {
        $this->actingAs($this->user('admin'));

        $this->get(route('super.admin.dashboard'))->assertForbidden();
        $this->get(route('super.admin.lessons.index'))->assertForbidden();
        $this->post(route('super.admin.lessons.store'), [])->assertForbidden();
        $this->get(route('super.admin.categories.index'))->assertForbidden();
        $this->post(route('super.admin.categories.store'), [])->assertForbidden();
        $this->get(route('super.admin.exams.index'))->assertForbidden();
        $this->get(route('super.admin.exam.questions.exams'))->assertForbidden();
        $this->post(route('super.admin.lessons.vocabularies.import', 1), [])->assertForbidden();
    }

    public function test_admin_dashboard_and_own_exam_question_workflow_remain_available(): void
    {
        $admin = $this->user('admin');

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertOk()
            ->assertSee('Admin Dashboard')
            ->assertSee(route('admin.exams.index'))
            ->assertDontSee(route('super.admin.dashboard'))
            ->assertDontSee(route('super.admin.admins.index'))
            ->assertDontSee(route('super.admin.lessons.index'))
            ->assertDontSee(route('super.admin.categories.index'))
            ->assertDontSee(route('super.admin.exams.index'))
            ->assertDontSee(route('super.admin.exam.questions.exams'));

        $this->get(route('admin.exams.index'))->assertOk();
    }

    public function test_super_admin_can_access_super_admin_management_routes(): void
    {
        $this->actingAs($this->user('super_admin'));

        $this->get(route('super.admin.dashboard'))->assertOk();
        $this->get(route('super.admin.admins.index'))->assertOk();
        $this->get(route('super.admin.lessons.index'))->assertOk();
        $this->get(route('super.admin.categories.index'))->assertOk();
        $this->get(route('super.admin.exams.index'))->assertOk();
        $this->get(route('super.admin.exam.questions.exams'))->assertOk();
    }

    public function test_admin_management_mutations_can_target_admins_but_not_super_admins(): void
    {
        $superAdmin = $this->user('super_admin');
        $anotherSuperAdmin = $this->user('super_admin');
        $admin = $this->user('admin');

        $this->actingAs($superAdmin)
            ->put(route('super.admin.admins.update', $anotherSuperAdmin), [
                'name' => 'Changed Name',
                'email' => 'changed@example.test',
            ])->assertNotFound();
        $this->delete(route('super.admin.admins.destroy', $anotherSuperAdmin))->assertNotFound();

        $this->put(route('super.admin.admins.update', $admin), [
            'name' => 'Updated Admin',
            'email' => 'updated-admin@example.test',
        ])->assertRedirect();

        $this->assertSame('super_admin', $anotherSuperAdmin->fresh()->role);
        $this->assertSame($anotherSuperAdmin->email, $anotherSuperAdmin->fresh()->email);
        $this->assertSame('Updated Admin', $admin->fresh()->name);
    }

    public function test_student_is_blocked_from_admin_and_super_admin_management_routes(): void
    {
        $this->actingAs($this->user('student'));

        $this->get(route('admin.dashboard'))->assertForbidden();
        $this->get(route('admin.exams.index'))->assertForbidden();
        $this->get(route('super.admin.dashboard'))->assertForbidden();
        $this->get(route('super.admin.admins.index'))->assertForbidden();
        $this->get(route('super.admin.lessons.index'))->assertForbidden();
        $this->get(route('super.admin.categories.index'))->assertForbidden();
        $this->get(route('super.admin.exams.index'))->assertForbidden();
        $this->get(route('super.admin.exam.questions.exams'))->assertForbidden();
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }
}
