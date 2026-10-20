@extends('layouts.app')
@section('title','My Account')
@section('content')
<x-page-header title="My Account" description="Manage your profile and keep your account secure."/>
<div class="form-layout"><form class="card form-card" method="POST" action="{{ route('profile.update') }}">@csrf @method('PUT')
    <div class="profile-banner"><x-avatar :name="auth()->user()->name" size="large"/><div><h2>{{ auth()->user()->name }}</h2><p>{{ auth()->user()->email }}</p></div><x-badge tone="green">{{ auth()->user()->role?->name ?? 'Unassigned' }}</x-badge></div><hr>
    <div class="form-section-heading"><span class="section-icon"><x-icon name="user"/></span><div><h2>Profile Information</h2><p>Your name and email address in this workspace.</p></div></div>
    <div class="form-grid"><x-form-input name="name" label="Full name" :value="auth()->user()->name" required maxlength="100" autocomplete="name"/><x-form-input name="email" label="Email address" type="email" :value="auth()->user()->email" required autocomplete="email"/></div>
    <hr><div class="form-section-heading" id="security"><span class="section-icon"><x-icon name="lock"/></span><div><h2>Password &amp; Security</h2><p>Confirm your current password to save any profile changes.</p></div></div>
    <x-form-input name="current_password" label="Current password" type="password" required autocomplete="current-password"/>
    <div class="form-grid"><x-form-input name="password" label="New password (optional)" type="password" minlength="12" autocomplete="new-password" hint="At least 12 characters. Leave blank to keep your current password."/><x-form-input name="password_confirmation" label="Confirm new password" type="password" autocomplete="new-password"/></div>
    <div class="form-footer"><span class="small-text muted"><x-icon name="lock"/>Protected account settings</span><button class="button">Save Changes <x-icon name="arrow"/></button></div>
</form><aside class="form-aside"><div class="card guidance-card"><span class="guidance-icon"><x-icon name="lock"/></span><h3>A more secure account</h3><p>Use a long, unique password you do not use elsewhere. Your current password is required for changes to your profile.</p><hr><h3>Your workspace role</h3><p>Your administrator manages role assignments. Contact them if your work requires a different level of access.</p><a class="text-link" href="{{ route('settings') }}">Appearance settings <x-icon name="arrow"/></a></div></aside></div>
@endsection