<?php

namespace App\Queries;

use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;

class DashboardMetrics
{
    public function charts(): array
    {
        $start = CarbonImmutable::now()->startOfMonth()->subMonths(7);
        $query = User::query()->where('created_at', '>=', $start)->where('created_at', '<', $start->addMonths(8));
        $months = [];

        // Conditional aggregates work on SQLite, MySQL, and PostgreSQL alike.
        for ($index = 0; $index < 8; $index++) {
            $month = $start->addMonths($index);
            $query->selectRaw('SUM(CASE WHEN created_at >= ? AND created_at < ? THEN 1 ELSE 0 END) AS month_'.$index, [$month, $month->addMonth()]);
            $months[] = ['label' => $month->format('M'), 'date' => $month->format('M Y')];
        }

        $counts = $query->first();
        foreach ($months as $index => &$month) {
            $month['count'] = (int) $counts?->getAttribute('month_'.$index);
        }
        unset($month);

        $roles = Role::withCount('users')->orderBy('id')->get()->map(fn (Role $role) => ['name' => $role->name, 'count' => $role->users_count])->all();
        $unassigned = User::whereNull('role_id')->count();
        if ($unassigned > 0) {
            $roles[] = ['name' => 'Unassigned', 'count' => $unassigned];
        }

        return ['registrations' => $months, 'distribution' => $roles];
    }
}
