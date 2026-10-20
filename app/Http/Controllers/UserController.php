<?php

namespace App\Http\Controllers;

use App\Actions\RecordAudit;
use App\Http\Requests\SaveUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', User::class);
        $request->validate(['q' => 'nullable|string|max:100', 'role' => 'nullable|integer|exists:roles,id', 'status' => 'nullable|in:active,inactive']);

        return view('users.index', [
            'users' => User::with('role')
                ->when($request->filled('q'), fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%')->orWhere('email', 'like', '%'.$request->string('q').'%')))
                ->when($request->filled('role'), fn ($q) => $q->where('role_id', $request->integer('role')))
                ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->input('status') === 'active'))
                ->latest()->paginate(12)->withQueryString(),
            'roles' => Role::orderBy('id')->get(),
        ]);
    }

    public function create()
    {
        Gate::authorize('create', User::class);

        $user = new User;
        $user->is_active = true;

        return view('users.form', ['subject' => $user, 'roles' => Role::all()]);
    }

    public function show(User $user)
    {
        Gate::authorize('view', $user);

        return view('users.show', ['subject' => $user->load('role')]);
    }

    public function edit(User $user)
    {
        Gate::authorize('update', $user);

        return view('users.form', ['subject' => $user, 'roles' => Role::all()]);
    }

    public function store(SaveUserRequest $request)
    {
        $user = DB::transaction(function () use ($request) {
            $u = new User($request->safe()->only(['name', 'email', 'password']));
            $u->role_id = $request->user()->isAdmin() ? $request->integer('role_id') : Role::where('name', 'Member')->firstOrFail()->id;
            $u->is_active = $request->boolean('is_active');
            $u->save();
            RecordAudit::write('user.created', $u->id, ['role' => $u->role->name, 'active' => $u->is_active]);

            return $u;
        });

        return to_route('users.show', $user)->with('status', 'User created.');
    }

    public function update(SaveUserRequest $request, User $user)
    {
        DB::transaction(function () use ($request, $user) {
            Role::where('name', 'Admin')->lockForUpdate()->firstOrFail();
            $user->refresh();
            Gate::authorize('update', $user);
            $roleId = $request->user()->isAdmin() ? $request->integer('role_id') : $user->role_id;
            $active = $request->boolean('is_active');
            abort_if($user->id === $request->user()->id && (! $active || $roleId !== $user->role_id), 422, 'You cannot deactivate or change your own role.');
            abort_if($user->isAdmin() && $user->is_active && (! $active || $roleId !== $user->role_id) && User::where('role_id', $user->role_id)->where('is_active', true)->count() <= 1, 422, 'Keep an active administrator.');
            $before = $user->only(['name', 'email', 'role_id', 'is_active']);
            $user->fill($request->safe()->only(['name', 'email']));
            if ($request->filled('password')) {
                $user->password = $request->input('password');
            }
            $user->role_id = $roleId;
            $user->is_active = $active;
            $user->save();
            RecordAudit::write('user.updated', $user->id, ['before' => $before, 'after' => $user->only(['name', 'email', 'role_id', 'is_active']), 'password_changed' => $request->filled('password')]);
        });

        return to_route('users.show', $user)->with('status', 'User updated.');
    }
}
