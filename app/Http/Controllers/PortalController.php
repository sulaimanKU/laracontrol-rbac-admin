<?php

namespace App\Http\Controllers;

use App\Actions\RecordAudit;
use App\Enums\Permission;
use App\Models\AuditEvent;
use App\Models\Role;
use App\Models\User;
use App\Queries\DashboardMetrics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class PortalController extends Controller
{
    public function dashboard(Request $request, DashboardMetrics $metrics)
    {
        if (! $request->user()->hasPermission(Permission::Dashboard)) {
            return view('portal.member');
        }

        return view('portal.dashboard', [
            'total' => User::count(),
            'active' => User::where('is_active', true)->count(),
            'admins' => User::whereHas('role', fn ($q) => $q->where('name', 'Admin'))->count(),
            'managers' => User::whereHas('role', fn ($q) => $q->where('name', 'Manager'))->count(),
            'recent' => $request->user()->can('view users') ? User::with('role')->latest()->limit(5)->get() : collect(),
            'events' => $request->user()->can('view audit logs') ? AuditEvent::with(['actor.role', 'subject'])->latest('created_at')->latest('id')->limit(4)->get() : collect(),
            ...$metrics->charts(),
        ]);
    }

    public function audit(Request $request)
    {
        $request->validate(['q' => 'nullable|string|max:100', 'actor' => 'nullable|integer|exists:users,id', 'date' => 'nullable|date_format:Y-m-d']);

        return view('portal.audit', [
            'events' => AuditEvent::with(['actor.role', 'subject'])
                ->when($request->filled('q'), fn ($q) => $q->where('action', 'like', '%'.$request->string('q').'%'))
                ->when($request->filled('actor'), fn ($q) => $q->where('actor_id', $request->integer('actor')))
                ->when($request->filled('date'), fn ($q) => $q->whereDate('created_at', $request->string('date')->toString()))
                ->latest('created_at')->latest('id')->paginate(20)->withQueryString(),
            'actors' => User::whereIn('id', AuditEvent::select('actor_id')->whereNotNull('actor_id'))->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function roles()
    {
        return view('portal.roles', ['roles' => Role::withCount('users')->get(), 'permissions' => Permission::cases()]);
    }

    public function updateRole(Request $request, Role $role)
    {
        abort_unless($request->user()->isAdmin(), 403);
        abort_if($role->name === 'Admin', 422, 'Administrator permissions are protected.');
        $data = $request->validate(['permissions' => 'nullable|array', 'permissions.*' => ['string', Rule::enum(Permission::class)]]);
        DB::transaction(function () use ($role, $data) {
            $before = $role->permissions;
            $role->update(['permissions' => $data['permissions'] ?? []]);
            RecordAudit::write('role.permissions_updated', null, ['role' => $role->name, 'before' => $before, 'after' => $role->permissions]);
        });

        return back()->with('status', 'Permissions updated.');
    }

    public function profile()
    {
        return view('portal.profile');
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:100', 'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($request->user()->id)], 'current_password' => 'required|current_password', 'password' => ['nullable', 'confirmed', Password::min(12)]]);
        DB::transaction(function () use ($request, $data) {
            $request->user()->fill(collect($data)->only(['name', 'email'])->all());
            if (! empty($data['password'])) {
                $request->user()->password = $data['password'];
            }
            $request->user()->save();
            RecordAudit::write('profile.updated', $request->user()->id, ['fields' => ['name', 'email'], 'password_changed' => ! empty($data['password'])]);
        });
        $request->session()->regenerate();

        return back()->with('status', 'Profile updated.');
    }
}
