<?php

namespace Tests;

use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

abstract class PortalTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Never touch the developer database, even when local configuration is cached.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'database.connections.sqlite.url' => null, 'session.driver' => 'array', 'cache.default' => 'array', 'mail.default' => 'array']);
        DB::purge('sqlite');
        $this->withoutVite();
        // Explicit test fixture: no migration command or migration files are executed.
        Schema::create('roles', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->json('permissions');
            $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->timestamp('email_verified_at')->nullable();
            $t->string('password');
            $t->rememberToken();
            $t->foreignId('role_id')->nullable()->constrained()->restrictOnDelete();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('audit_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('subject_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('action')->index();
            $t->json('metadata');
            $t->timestamp('created_at')->index();
        });
        Schema::create('password_reset_tokens', function (Blueprint $t) {
            $t->string('email')->primary();
            $t->string('token');
            $t->timestamp('created_at')->nullable();
        });
        foreach (['Admin' => array_column(Permission::cases(), 'value'), 'Manager' => ['access dashboard', 'view users', 'manage users', 'view roles'], 'Member' => []] as $name => $permissions) {
            Role::create(compact('name', 'permissions'));
        }
    }

    protected function person(string $role = 'Member', array $attributes = []): User
    {
        return User::factory()->create(['role_id' => Role::where('name', $role)->firstOrFail()->id, ...$attributes]);
    }

    protected function payload(User $u, array $extra = []): array
    {
        return ['name' => $u->name, 'email' => $u->email, 'is_active' => 1, ...$extra];
    }
}
