<?php

namespace Tests\Feature;

use App\Models\AuditEvent;
use App\Models\Role;
use App\Models\User;
use App\Queries\DashboardMetrics;
use Database\Seeders\PortfolioDemoSeeder;
use RuntimeException;
use Tests\PortalTestCase;

class WorkspacePresentationTest extends PortalTestCase
{
    public function test_charts_use_actual_counts_and_an_eight_month_window(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 20)->setTime(12, 0));
        $admin = $this->person('Admin');
        $this->person('Member', ['created_at' => now()->startOfMonth()->subMonths(7)]);
        $this->person('Manager', ['created_at' => now()->startOfMonth()->subMonths(8)]);
        $this->person('Member', ['created_at' => now()->startOfMonth()->addMonth()]);
        $this->person('Member', ['role_id' => null]);

        $this->actingAs($admin)->get('/dashboard')->assertOk()
            ->assertViewHas('total', 5)
            ->assertViewHas('registrations', function (array $months) {
                return count($months) === 8
                    && $months[0] === ['label' => 'Mar', 'date' => 'Mar 2026', 'count' => 1]
                    && $months[7]['count'] === 2
                    && array_sum(array_column($months, 'count')) === 3;
            })
            ->assertViewHas('distribution', function (array $roles) {
                $counts = array_column($roles, 'count', 'name');

                return $counts === ['Admin' => 1, 'Manager' => 1, 'Member' => 2, 'Unassigned' => 1];
            });
    }

    public function test_empty_chart_data_has_real_zero_counts(): void
    {
        $charts = app(DashboardMetrics::class)->charts();
        $this->assertCount(8, $charts['registrations']);
        $this->assertSame(0, array_sum(array_column($charts['registrations'], 'count')));
        $this->assertSame(0, array_sum(array_column($charts['distribution'], 'count')));
    }

    public function test_dashboard_does_not_expose_names_without_directory_or_audit_permissions(): void
    {
        $manager = $this->person('Manager');
        $manager->role->update(['permissions' => ['access dashboard']]);
        $private = $this->person('Member', ['name' => 'Private Directory Person']);
        AuditEvent::create(['actor_id' => $private->id, 'subject_id' => $private->id, 'action' => 'user.updated', 'metadata' => [], 'created_at' => now()]);

        $this->actingAs($manager->fresh())->get('/dashboard')->assertOk()
            ->assertSee('User Growth')->assertSee('Role Distribution')
            ->assertDontSee('Private Directory Person')->assertDontSee('user.updated')
            ->assertSee('User directory is restricted')->assertSee('Activity access is restricted');
    }

    public function test_user_filters_combine_with_search_and_are_validated(): void
    {
        $admin = $this->person('Admin');
        $wanted = $this->person('Member', ['name' => 'Target Member', 'is_active' => false]);
        $this->person('Member', ['name' => 'Target Active Member']);
        $this->person('Manager', ['name' => 'Target Manager', 'is_active' => false]);

        $this->actingAs($admin)->get('/users?q=Target&status=inactive&role='.$wanted->role_id)
            ->assertOk()->assertViewHas('users', fn ($users) => $users->total() === 1 && $users->first()->id === $wanted->id);
        $this->get('/users?status=invalid')->assertSessionHasErrors('status');
        $this->get('/users?role=999999')->assertSessionHasErrors('role');
    }

    public function test_audit_filters_are_combined_and_remain_restricted(): void
    {
        $admin = $this->person('Admin');
        $manager = $this->person('Manager');
        foreach ([
            [$admin->id, 'user.updated', '2026-10-20 10:00:00'],
            [$admin->id, 'user.created', '2026-10-20 11:00:00'],
            [$manager->id, 'user.updated', '2026-10-20 12:00:00'],
            [$admin->id, 'user.updated', '2026-10-19 10:00:00'],
        ] as [$actor, $action, $created]) {
            AuditEvent::create(['actor_id' => $actor, 'subject_id' => null, 'action' => $action, 'metadata' => [], 'created_at' => $created]);
        }

        $uri = '/audit?q=updated&actor='.$admin->id.'&date=2026-10-20';
        $this->actingAs($admin)->get($uri)->assertOk()->assertViewHas('events', fn ($events) => $events->total() === 1);
        $this->get('/audit?date=invalid')->assertSessionHasErrors('date');
        $this->actingAs($manager)->get($uri)->assertForbidden();
    }

    public function test_settings_require_an_active_session_and_have_only_local_appearance_controls(): void
    {
        $this->get('/settings')->assertRedirect('/login');
        $member = $this->person();
        $this->actingAs($member)->get('/settings')->assertOk()->assertSee('data-theme-choice', false)
            ->assertSee('saved in this browser')->assertSee(route('profile.edit'));
        $member->forceFill(['is_active' => false])->save();
        $this->get('/settings')->assertRedirect('/login');
    }

    public function test_permission_matrix_uses_existing_role_update_forms(): void
    {
        $admin = $this->person('Admin');
        $manager = Role::where('name', 'Manager')->firstOrFail();
        $this->actingAs($admin)->get('/roles')->assertOk()->assertSee('Permission Matrix')
            ->assertSee('form="permissions-'.$manager->id.'"', false)
            ->assertSee('action="'.route('roles.update', $manager).'"', false)
            ->assertDontSee('form="permissions-'.$admin->role_id.'"', false);
    }

    public function test_optional_demo_seeder_refuses_non_local_environments(): void
    {
        $this->expectException(RuntimeException::class);
        app(PortfolioDemoSeeder::class)->run();
    }

    public function test_demo_fixture_is_additive_idempotent_and_preserves_existing_admin(): void
    {
        // Only the isolated SQLite fixture is seeded, never the application database.
        $admin = $this->person('Admin');
        $before = $admin->fresh()->getAttributes();
        $environment = app()->environment();
        app()->instance('env', 'local');
        try {
            app(PortfolioDemoSeeder::class)->run();
            app(PortfolioDemoSeeder::class)->run();
        } finally {
            app()->instance('env', $environment);
        }

        $this->assertDatabaseCount('users', 25);
        $this->assertDatabaseCount('audit_events', 24);
        $this->assertSame($before, $admin->fresh()->getAttributes());
        $this->assertSame(24, User::where('email', 'like', 'portfolio-demo-%@example.test')->count());
        $this->assertStringContainsString('(Demo)', User::where('email', 'portfolio-demo-01@example.test')->firstOrFail()->name);
    }
}
