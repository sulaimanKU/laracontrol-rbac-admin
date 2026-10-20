<?php

namespace Database\Seeders;

use App\Models\AuditEvent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PortfolioDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new RuntimeException('Portfolio demo data is available only in the local environment.');
        }

        $roles = Role::all()->keyBy('name');
        foreach (['Admin', 'Manager', 'Member'] as $name) {
            if (! $roles->has($name)) {
                throw new RuntimeException('Existing LaraControl roles are required. Run your normal database setup first.');
            }
        }

        $names = [
            'Alex Rivera', 'Jordan Ellis', 'Morgan Chen', 'Taylor Brooks', 'Casey Patel', 'Riley Hart',
            'Avery Quinn', 'Jamie Park', 'Cameron Reed', 'Drew Carter', 'Sam Bennett', 'Robin Hayes',
            'Harper Lane', 'Skyler James', 'Parker Cole', 'Reese Morgan', 'Dakota Wells', 'Finley Shaw',
            'Rowan Blake', 'Sage Miller', 'Emerson Lee', 'Quinn Foster', 'Hayden Cruz', 'Blair Davis',
        ];

        DB::transaction(function () use ($names, $roles) {
            foreach ($names as $index => $name) {
                $email = sprintf('portfolio-demo-%02d@example.test', $index + 1);
                if (User::where('email', $email)->exists()) {
                    continue; // Preserve all existing accounts, including prior demo data.
                }

                $role = $roles[$index < 2 ? 'Admin' : ($index < 8 ? 'Manager' : 'Member')];
                $joined = now()->startOfMonth()->subMonths(7 - intdiv($index, 3))->addDays(2 + ($index % 3) * 7);
                if ($joined->isFuture()) {
                    $joined = now()->subMinutes($index + 1);
                }

                $user = new User([
                    'name' => $name.' (Demo)',
                    'email' => $email,
                    'password' => Str::random(48),
                ]);
                $user->forceFill([
                    'role_id' => $role->id,
                    'is_active' => $index < 8 || $index % 5 !== 0,
                    'created_at' => $joined,
                    'updated_at' => $joined,
                ])->save();

                AuditEvent::create([
                    'actor_id' => null,
                    'subject_id' => $user->id,
                    'action' => 'user.created',
                    'metadata' => ['source' => 'optional_portfolio_demo_seeder', 'role' => $role->name, 'active' => $user->is_active],
                    // This is the time of the actual seed operation, not an invented historical event.
                    'created_at' => now(),
                ]);
            }
        });
    }
}
