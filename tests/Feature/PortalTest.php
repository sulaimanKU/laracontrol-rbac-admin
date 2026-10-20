<?php

namespace Tests\Feature;

use App\Models\AuditEvent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\PortalTestCase;

class PortalTest extends PortalTestCase
{
    public function test_guests_are_redirected_from_all_protected_pages(): void
    {
        foreach (['/dashboard', '/users', '/users/create', '/roles', '/audit', '/profile'] as $uri) {
            $this->get($uri)->assertRedirect('/login');
        }
    }

    public function test_public_pages_render(): void
    {
        foreach (['/', '/login', '/register', '/forgot-password', '/reset-password/example'] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_admin_can_render_every_application_screen(): void
    {
        $admin = $this->person('Admin');
        $member = $this->person();
        $this->actingAs($admin);
        foreach (['/dashboard', '/users', '/users/create', "/users/$member->id", "/users/$member->id/edit", '/roles', '/audit', '/profile'] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_member_cannot_read_or_mutate_administration(): void
    {
        $member = $this->person();
        $target = $this->person();
        $this->actingAs($member);
        $this->get('/dashboard')->assertOk()->assertSee('YOUR PERSONAL WORKSPACE')->assertDontSee('Total people');
        foreach (['/users', '/roles', '/audit', "/users/$target->id", '/users/create', "/users/$target->id/edit"] as $uri) {
            $this->get($uri)->assertForbidden();
        }
        $this->put("/users/$target->id", $this->payload($target))->assertForbidden();
        $this->post('/users', [])->assertForbidden();
        $this->put('/roles/1', ['permissions' => []])->assertForbidden();
    }

    public function test_manager_can_manage_members_but_cannot_escalate_roles(): void
    {
        $manager = $this->person('Manager');
        $member = $this->person();
        $admin = $this->person('Admin');
        $this->actingAs($manager);
        foreach (['/dashboard', '/users', '/roles', "/users/$member->id/edit"] as $uri) {
            $this->get($uri)->assertOk();
        }
        $this->get('/audit')->assertForbidden();
        $this->get("/users/$admin->id/edit")->assertForbidden();
        $this->put("/users/$admin->id", $this->payload($admin))->assertForbidden();
        $this->put("/users/$manager->id", $this->payload($manager))->assertForbidden();
        $this->put("/users/$member->id", $this->payload($member, ['role_id' => $admin->role_id]))->assertSessionHasErrors('role_id');
        $this->put("/users/$member->id", $this->payload($member, ['name' => 'Updated member', 'is_active' => 0]))->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $member->id, 'name' => 'Updated member', 'is_active' => 0]);
        $this->assertDatabaseHas('audit_events', ['action' => 'user.updated', 'actor_id' => $manager->id, 'subject_id' => $member->id]);
        $this->put('/roles/'.$member->role_id, ['permissions' => ['manage roles']])->assertForbidden();
    }

    public function test_admin_can_create_and_reassign_accounts_with_safe_audit_metadata(): void
    {
        $admin = $this->person('Admin');
        $member = $this->person();
        $this->actingAs($admin);
        $this->post('/users', ['name' => 'New colleague', 'email' => 'colleague@example.test', 'password' => 'long-secure-password', 'password_confirmation' => 'long-secure-password', 'role_id' => $member->role_id, 'is_active' => 1])->assertRedirect();
        $this->assertDatabaseHas('audit_events', ['action' => 'user.created']);
        $manager = Role::where('name', 'Manager')->firstOrFail();
        $this->put("/users/$member->id", $this->payload($member, ['role_id' => $manager->id, 'password' => 'another-long-password', 'password_confirmation' => 'another-long-password']))->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $member->id, 'role_id' => $manager->id]);
        $metadata = json_encode(AuditEvent::where('action', 'user.updated')->firstOrFail()->metadata);
        $this->assertStringNotContainsString('another-long-password', $metadata);
        $this->assertStringNotContainsString('remember_token', $metadata);
    }

    public function test_admin_cannot_remove_their_own_administrative_access(): void
    {
        $admin = $this->person('Admin');
        $this->actingAs($admin);
        $this->put("/users/$admin->id", $this->payload($admin, ['role_id' => $admin->role_id, 'is_active' => 0]))->assertUnprocessable();
        $this->put("/users/$admin->id", $this->payload($admin, ['role_id' => Role::where('name', 'Member')->value('id')]))->assertUnprocessable();
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_permissions_are_enforced_on_direct_requests(): void
    {
        $manager = $this->person('Manager');
        $member = $this->person();
        $manager->role->update(['permissions' => ['view roles']]);
        $this->actingAs($manager->fresh());
        $this->get('/users')->assertForbidden();
        $this->put("/users/$member->id", $this->payload($member))->assertForbidden();
        $this->get('/roles')->assertOk();
    }

    public function test_admin_can_change_permissions_and_changes_are_audited(): void
    {
        $admin = $this->person('Admin');
        $manager = $this->person('Manager');
        $this->actingAs($admin);
        $this->put('/roles/'.$manager->role_id, ['permissions' => ['view users']])->assertRedirect();
        $this->assertDatabaseHas('audit_events', ['action' => 'role.permissions_updated']);
        $this->put('/roles/'.$admin->role_id, ['permissions' => []])->assertUnprocessable();
        $this->put('/roles/'.$manager->role_id, ['permissions' => ['invented permission']])->assertSessionHasErrors('permissions.0');
        $this->actingAs($manager->fresh());
        $this->get('/users')->assertOk();
        $this->get('/roles')->assertForbidden();
    }

    public function test_inactive_accounts_are_blocked_during_existing_sessions(): void
    {
        $admin = $this->person('Admin', ['is_active' => false]);
        $this->actingAs($admin)->get('/users')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_registration_ignores_submitted_privileged_attributes(): void
    {
        $this->post('/register', ['name' => 'New member', 'email' => 'member@example.test', 'password' => 'long-secure-password', 'password_confirmation' => 'long-secure-password', 'role_id' => Role::where('name', 'Admin')->value('id'), 'is_active' => 0])->assertRedirect('/dashboard');
        $u = User::where('email', 'member@example.test')->firstOrFail();
        $this->assertSame('Member', $u->role->name);
        $this->assertTrue($u->is_active);
        $this->assertAuthenticatedAs($u);
    }

    public function test_login_logout_and_inactive_login(): void
    {
        $u = $this->person('Member', ['password' => 'long-secure-password']);
        $this->post('/login', ['email' => $u->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => $u->email, 'password' => 'long-secure-password'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($u);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
        $u->forceFill(['is_active' => false])->save();
        $this->post('/login', ['email' => $u->email, 'password' => 'long-secure-password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_profile_requires_current_password_and_does_not_allow_role_assignment(): void
    {
        $u = $this->person('Member', ['password' => 'long-secure-password']);
        $this->actingAs($u);
        $this->put('/profile', ['name' => 'Changed', 'email' => $u->email, 'current_password' => 'wrong'])->assertSessionHasErrors('current_password');
        $this->put('/profile', ['name' => 'Changed', 'email' => $u->email, 'current_password' => 'long-secure-password', 'role_id' => Role::where('name', 'Admin')->value('id')])->assertRedirect();
        $this->assertSame('Member', $u->fresh()->role->name);
        $this->assertSame('Changed', $u->fresh()->name);
    }

    public function test_password_reset_uses_laravel_broker(): void
    {
        Notification::fake();
        $u = $this->person();
        $this->post('/forgot-password', ['email' => $u->email])->assertSessionHas('status');
        Notification::assertSentTo($u, ResetPassword::class);
        $token = Password::createToken($u);
        $this->post('/reset-password', ['email' => $u->email, 'token' => $token, 'password' => 'reset-long-password', 'password_confirmation' => 'reset-long-password'])->assertRedirect('/login');
        $this->assertTrue(Hash::check('reset-long-password', $u->fresh()->password));
    }

    public function test_search_and_pagination(): void
    {
        $admin = $this->person('Admin');
        User::factory()->count(14)->create();
        $target = $this->person('Member', ['name' => 'Unique Needle']);
        $this->actingAs($admin);
        $this->get('/users?q=Unique+Needle')->assertOk()->assertSee('Unique Needle')->assertDontSee($admin->email);
        $this->get('/users?page=2')->assertOk()->assertSee('page=1', false);
    }

    public function test_manager_creates_active_member_and_cannot_supply_a_role(): void
    {
        $manager = $this->person('Manager');
        $this->actingAs($manager)->get('/users/create')->assertOk()->assertSee('value="1" selected', false);
        $data = ['name' => 'Created member', 'email' => 'created@example.test', 'password' => 'long-secure-password', 'password_confirmation' => 'long-secure-password', 'is_active' => 1];
        $this->post('/users', [...$data, 'role_id' => $manager->role_id])->assertSessionHasErrors('role_id');
        $this->post('/users', $data)->assertRedirect();
        $created = User::where('email', $data['email'])->firstOrFail();
        $this->assertSame('Member', $created->role->name);
        $this->assertTrue($created->is_active);
        $this->assertDatabaseHas('audit_events', ['action' => 'user.created', 'actor_id' => $manager->id, 'subject_id' => $created->id]);
    }

    public function test_non_admin_cannot_change_permissions_even_when_granted_the_capability(): void
    {
        $manager = $this->person('Manager');
        $manager->role->update(['permissions' => ['manage roles']]);
        $this->actingAs($manager->fresh())->put('/roles/'.$manager->role_id, ['permissions' => []])->assertForbidden();
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_repeated_failed_logins_are_throttled(): void
    {
        $user = $this->person();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        }
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
