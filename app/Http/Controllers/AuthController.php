<?php

namespace App\Http\Controllers;

use App\Actions\RecordAudit;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        $key = 'login:'.hash('sha256', strtolower($data['email']).'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Too many attempts. Try again in a minute.']);
        }
        if (! Auth::attempt([...$data, 'is_active' => true], $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'Invalid credentials or inactive account.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function register(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:100', 'email' => 'required|email|max:255|unique:users', 'password' => ['required', 'confirmed', PasswordRule::min(12)]]);
        $u = DB::transaction(function () use ($data) {
            $u = new User($data);
            $u->role_id = Role::where('name', 'Member')->firstOrFail()->id;
            $u->is_active = true;
            $u->save();
            RecordAudit::write('user.registered', $u->id);

            return $u;
        });
        Auth::login($u);
        $request->session()->regenerate();

        return to_route('dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login');
    }

    public function forgot(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        Password::sendResetLink($request->only('email'));

        return back()->with('status', 'If an account exists, a reset link has been requested.');
    }

    public function reset(Request $request)
    {
        $data = $request->validate(['token' => 'required', 'email' => 'required|email', 'password' => ['required', 'confirmed', PasswordRule::min(12)]]);
        $status = Password::reset($data, function (User $u, string $password) {
            $u->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($u));
        });

        return $status === Password::PASSWORD_RESET ? to_route('login')->with('status', __($status)) : back()->withErrors(['email' => __($status)]);
    }
}
