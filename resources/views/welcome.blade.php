@extends('layouts.app')
@section('title', 'Administration, with clarity')
@section('content')<section class="hero">
        <div><span class="eyebrow">LARAVEL BACKEND ENGINEERING / PORTFOLIO PROJECT</span>
            <h1>The right access.<br><span>A clearer workspace.</span></h1>
            <p>A considered administration portal for managing people, defining permissions, and keeping important changes
                accountable.</p>
            <div class="actions"><a class="button" href="{{ route('login') }}">Open your workspace &rarr;</a><a
                    class="button secondary" href="{{ route('register') }}">Join as a member</a></div>
            <p class="hero-note">Three roles. Server-side authorization. A traceable history.</p>
        </div>
        <div class="hero-preview"><span class="eyebrow">ACCESS CONTROL / 01</span>
            <h2>Every role has a purpose.</h2>
            <p>A focused permission model, enforced on protected requests.</p>
            @foreach (['Admin' => ['Workspace administration', 'Full oversight'], 'Manager' => ['People operations', 'Member management'], 'Member' => ['Personal workspace', 'Account access']] as $role => $info)
                <div class="role-preview"><span class="avatar">{{ substr($role, 0, 1) }}</span>
                    <div><strong>{{ $role }}</strong><small>{{ $info[0] }}</small></div><span
                        class="badge">{{ $info[1] }}</span>
                </div>
            @endforeach
            <div class="preview-foot">
                POLICIES + GATES + MIDDLEWARE &rarr;</div>
        </div>
    </section>
    <div class="section-heading"><span class="eyebrow">ENGINEERED BEHIND THE INTERFACE</span>
        <h2>Small application. Thoughtful foundations.</h2>
    </div>
    <section class="feature-grid">
        @foreach (['01' => ['Authentication', 'Session authentication, password reset flows, request throttling, and protected application routes.'], '02' => ['Roles & permissions', 'Three defined roles, editable capabilities, and Laravel policies that validate user management requests.'], '03' => ['Accountable operations', 'Administrative changes recorded transactionally, with restricted audit browsing.'], '04' => ['Laravel architecture', 'Form Requests, Eloquent relationships, policies, middleware, and focused feature tests.']] as $n => $feature)
            <article class="card feature"><span class="eyebrow">{{ $n }}</span>
                <h3>{{ $feature[0] }}</h3>
                <p>{{ $feature[1] }}</p>
            </article>
        @endforeach
    </section>
    <div class="landing-footer">Portfolio application &middot; Use sample data only. <span>Laravel 12 / Blade / Tailwind
        4</span></div>@endsection
