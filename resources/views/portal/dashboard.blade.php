@extends('layouts.app')
@section('title','Dashboard')
@section('content')
<x-page-header :title="'Welcome back, '.auth()->user()->name" description="Here's what's happening in your workspace today.">
    <span class="date-chip"><x-icon name="calendar"/>{{ now()->format('D, M j, Y') }}</span>
    @can('create',App\Models\User::class)<a class="button" href="{{ route('users.create') }}"><x-icon name="plus"/>Add User</a>@endcan
</x-page-header>
<div class="stats">
    <x-stat-card label="Total Users" :value="$total" description="All workspace accounts" icon="users" tone="teal"/>
    <x-stat-card label="Active Users" :value="$active" description="Enabled to sign in" icon="user" tone="blue"/>
    <x-stat-card label="Administrators" :value="$admins" description="Workspace administrators" icon="shield" tone="violet"/>
    <x-stat-card label="Managers" :value="$managers" description="People management role" icon="users" tone="amber"/>
</div>
<div class="analytics-grid">
    <section class="card growth-card">
        <div class="card-heading"><div><h2>User Growth</h2><p>New registrations by month</p></div><span class="period-chip"><x-icon name="calendar"/>Last 8 months</span></div>
        <div class="growth-summary"><strong>{{ array_sum(array_column($registrations,'count')) }}</strong><span>registrations in this period</span><span class="chart-key"><i></i>Registered users</span></div>
        @php
            $maximum = max(4, (int) ceil(max(array_column($registrations,'count')) / 4) * 4);
            $points = collect($registrations)->map(fn($month,$i) => (42 + $i * (548/7)).','.round(168 - ($month['count'] / $maximum * 136),2))->implode(' ');
        @endphp
        <div class="growth-chart">
            <svg viewBox="0 0 624 205" role="img" aria-labelledby="growth-title growth-description">
                <title id="growth-title">Monthly user registrations</title><desc id="growth-description">Actual new accounts from {{ $registrations[0]['date'] }} to {{ $registrations[7]['date'] }}. Exact counts are in the data table below.</desc>
                <defs><linearGradient id="growth-fill" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="var(--chart-teal)" stop-opacity=".15"/><stop offset="100%" stop-color="var(--chart-teal)" stop-opacity=".01"/></linearGradient></defs>
                @for($tick=0;$tick<=4;$tick++)
                    <line class="chart-grid-line" x1="42" x2="590" y1="{{ 168-$tick*34 }}" y2="{{ 168-$tick*34 }}"/>
                    <text class="chart-label" x="25" y="{{ 172-$tick*34 }}" text-anchor="end">{{ $maximum*$tick/4 }}</text>
                @endfor
                <polygon points="42,168 {{ $points }} 590,168" fill="url(#growth-fill)"/>
                <polyline points="{{ $points }}" fill="none" stroke="var(--chart-teal)" stroke-width="2.5" stroke-linejoin="round"/>
                @foreach($registrations as $month)
                    <circle class="chart-point" cx="{{ 42+$loop->index*(548/7) }}" cy="{{ 168-$month['count']/$maximum*136 }}" r="4"><title>{{ $month['date'] }}: {{ $month['count'] }} registrations</title></circle>
                    <text class="chart-label" x="{{ 42+$loop->index*(548/7) }}" y="195" text-anchor="middle">{{ $month['label'] }}</text>
                @endforeach
            </svg>
        </div>
        <details class="chart-data"><summary>View registration data</summary><div class="table-wrap"><table><thead><tr><th>Month</th><th>New users</th></tr></thead><tbody>@foreach($registrations as $month)<tr><td>{{ $month['date'] }}</td><td>{{ $month['count'] }}</td></tr>@endforeach</tbody></table></div></details>
    </section>
    <section class="card distribution-card">
        <div class="card-heading"><div><h2>Role Distribution</h2><p>How access is assigned</p></div><span class="icon-muted"><x-icon name="shield"/></span></div>
        @php($offset = 0)
        <div class="donut-wrap"><svg class="donut-chart" viewBox="0 0 160 160" role="img" aria-label="Role distribution. {{ $total }} total users. See role counts below."><circle class="donut-track" cx="80" cy="80" r="60" fill="none" stroke-width="17"/>
            @foreach($distribution as $role)
                @php($percent = $total ? $role['count']/$total*100 : 0)
                <circle class="role-segment role-color-{{ strtolower($role['name']) }}" cx="80" cy="80" r="60" fill="none" stroke-width="17" pathLength="100" stroke-dasharray="{{ $percent }} {{ 100-$percent }}" stroke-dashoffset="{{ -$offset }}" transform="rotate(-90 80 80)"><title>{{ $role['name'] }}: {{ $role['count'] }} users</title></circle>
                @php($offset += $percent)
            @endforeach
        </svg><div class="donut-center" aria-hidden="true"><strong>{{ number_format($total) }}</strong><span>Total users</span></div></div>
        <ul class="role-legend">@foreach($distribution as $role)<li><span class="legend-label"><i class="role-dot role-color-{{ strtolower($role['name']) }}"></i>{{ $role['name']==='Admin'?'Administrators':($role['name']==='Manager'?'Managers':($role['name']==='Member'?'Members':$role['name'])) }}</span><strong>{{ $role['count'] }}</strong><small>{{ $total ? round($role['count']/$total*100) : 0 }}%</small></li>@endforeach</ul>
    </section>
</div>
<div class="dashboard-grid">
    <section class="card recent-users"><div class="card-heading"><div><h2>Recently Joined Users</h2><p>The latest additions to your workspace</p></div>@can('viewAny',App\Models\User::class)<a class="text-link" href="{{ route('users.index') }}">View all users <x-icon name="arrow"/></a>@endcan</div>
        @can('viewAny',App\Models\User::class)
        <div class="table-wrap"><table><thead><tr><th>User</th><th>Role</th><th>Status</th><th>Joined</th><th><span class="sr-only">Actions</span></th></tr></thead><tbody>
        @forelse($recent as $person)<tr><td><div class="person"><x-avatar :name="$person->name" :tone="match($person->role?->name){'Admin'=>'violet','Manager'=>'amber',default=>'blue'}"/><div><a class="user-name" href="{{ route('users.show',$person) }}">{{ $person->name }}</a><small>{{ $person->email }}</small></div></div></td><td><x-badge :tone="match($person->role?->name){'Admin'=>'violet','Manager'=>'amber',default=>'blue'}">{{ $person->role?->name ?? 'Unassigned' }}</x-badge></td><td><x-badge :tone="$person->is_active?'green':'neutral'"><span class="status-dot"></span>{{ $person->is_active?'Active':'Inactive' }}</x-badge></td><td class="muted nowrap">{{ $person->created_at->format('M j, Y') }}</td><td><x-user-actions :user="$person"/></td></tr>
        @empty<tr><td colspan="5"><x-empty-state title="No users yet" description="New accounts will appear here."/></td></tr>@endforelse
        </tbody></table></div>
        @else<x-empty-state title="User directory is restricted" description="Your current permissions do not include viewing user accounts." icon="lock"/>@endcan
    </section>
    <section class="card recent-activity"><div class="card-heading"><div><h2>Recent Activity</h2><p>A record of important changes</p></div><span class="icon-muted"><x-icon name="clock"/></span></div>
        @can('view audit logs')<div class="activity-list">@forelse($events as $event)<x-activity-item :event="$event"/>@empty<x-empty-state title="A fresh start" description="Administrative changes will appear here." icon="activity"/>@endforelse</div><a class="activity-footer" href="{{ route('audit.index') }}">View all activity <x-icon name="arrow"/></a>
        @else<x-empty-state title="Activity access is restricted" description="Audit history is available to authorized accounts." icon="lock"/>@endcan
    </section>
</div>
@endsection
