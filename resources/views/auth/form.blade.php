@extends('layouts.app')
@section('title',match($mode){'register'=>'Create your account','forgot'=>'Forgot your password?','reset'=>'Choose a new password',default=>'Welcome back'})
@section('content')
<div class="auth-grid">
<section class="auth-intro"><div class="auth-intro-content"><span class="auth-kicker"><x-icon name="shield"/>BUILT AROUND TRUST</span><h1>Secure access.<br>Clear responsibilities.</h1><p>A thoughtful workspace for managing people, permissions, and the work that connects them.</p><div class="auth-feature"><span><x-icon name="shield"/></span><div><strong>Access with purpose</strong><small>The right permissions for every role.</small></div></div><div class="auth-feature"><span><x-icon name="activity"/></span><div><strong>Changes with context</strong><small>A clear record of important administrative actions.</small></div></div><div class="auth-feature"><span><x-icon name="users"/></span><div><strong>A workspace for your people</strong><small>Considered tools for everyday administration.</small></div></div><div class="auth-caption"><span class="brand-mark"><x-icon name="shield"/></span><div>LaraControl<small>Laravel administration &middot; Role-based access</small></div></div></div><div class="auth-decoration" aria-hidden="true"></div></section>
<section class="auth-form-panel"><div class="auth-card"><span class="eyebrow">{{ $mode==='register'?'GET STARTED':'YOUR WORKSPACE AWAITS' }}</span><h2>@yield('title')</h2><p>{{ match($mode){'register'=>'Create an account to join as a Member.','forgot'=>'Enter your email and we will help you request a reset link.','reset'=>'Choose a strong password to secure your account.',default=>'Sign in to continue to your workspace.'} }}</p>
<form method="POST" action="{{ match($mode){'register'=>route('register'),'forgot'=>route('password.email'),'reset'=>route('password.update'),default=>route('login')} }}">
    @csrf @if($mode==='reset')<input type="hidden" name="token" value="{{ $token }}">@endif
    @if($mode==='register')<x-form-input name="name" label="Full name" required maxlength="100" autocomplete="name" placeholder="Your full name"/>@endif
    <x-form-input name="email" label="Email address" type="email" :value="request('email')" required autocomplete="email" placeholder="you@example.com"/>
    @if($mode!=='forgot')<x-form-input name="password" label="Password" type="password" required :minlength="$mode==='login'?null:12" :autocomplete="$mode==='login'?'current-password':'new-password'" :hint="$mode==='login'?null:'Use at least 12 characters.'"/>@endif
    @if(in_array($mode,['register','reset']))<x-form-input name="password_confirmation" label="Confirm password" type="password" required autocomplete="new-password"/>@endif
    @if($mode==='login')<div class="form-between"><label class="checkbox"><input type="checkbox" name="remember" value="1" @checked(old('remember'))>Remember me</label><a href="{{ route('password.request') }}">Forgot password?</a></div>@endif
    <button class="button full">{{ match($mode){'register'=>'Create Account','forgot'=>'Request Reset Link','reset'=>'Reset Password',default=>'Sign In'} }}<x-icon name="arrow"/></button>
</form>
<p class="auth-switch">@if($mode==='login')New to LaraControl? <a href="{{ route('register') }}">Create an account</a>@else<a href="{{ route('login') }}">Back to sign in</a>@endif</p>
<div class="auth-security"><x-icon name="lock"/>Secure, permission-aware administration</div>
</div></section></div>
@endsection