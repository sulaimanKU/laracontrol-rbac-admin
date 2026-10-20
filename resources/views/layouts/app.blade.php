<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="description" content="LaraControl: a Laravel administration portal with clear permissions and accountable operations.">
    <title>@yield('title', 'Dashboard') &middot; LaraControl</title>
    <script>
        (() => {
            document.documentElement.classList.add('js');
            let preference = 'system';
            try { preference = localStorage.getItem('laracontrol.theme') || 'system'; } catch {}
            document.documentElement.dataset.theme = preference === 'dark' || (preference !== 'light' && matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
@auth
    <div class="app-shell">
        <button type="button" class="drawer-backdrop" id="drawer-backdrop" aria-label="Close navigation" hidden></button>
        <aside class="sidebar" id="workspace-sidebar" aria-label="Workspace navigation" tabindex="-1">
            <div class="sidebar-brand"><a class="brand" href="{{ route('dashboard') }}"><span class="brand-mark"><x-icon name="shield"/></span><span>LaraControl<small>Admin Portal</small></span></a><button class="icon-button drawer-close" type="button" data-drawer-close aria-label="Close navigation"><x-icon name="close"/></button></div>
            <span class="nav-label">WORKSPACE</span>
            <nav aria-label="Main">
                <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="grid">Dashboard</x-nav-link>
                @can('viewAny', App\Models\User::class)<x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')" icon="users">Users</x-nav-link>@endcan
                @can('view roles')<x-nav-link :href="route('roles.index')" :active="request()->routeIs('roles.*')" icon="shield">Roles &amp; Permissions</x-nav-link>@endcan
                @can('view audit logs')<x-nav-link :href="route('audit.index')" :active="request()->routeIs('audit.*')" icon="activity">Activity Log</x-nav-link>@endcan
                <x-nav-link :href="route('settings')" :active="request()->routeIs('settings')" icon="settings">Settings</x-nav-link>
            </nav>
            <div class="nav-separator"></div><span class="nav-label">PERSONAL</span>
            <nav aria-label="Personal"><x-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')" icon="user">My Account</x-nav-link></nav>
            <div class="sidebar-bottom">
                <div class="rbac-note"><span class="rbac-note-icon"><x-icon name="shield"/></span><strong>Role-Based Access Control</strong><p>Secure, scalable, and easy to manage.</p><span class="rbac-note-foot">Clear access. Confident teams.</span></div>
                <div class="sidebar-account"><x-avatar :name="auth()->user()->name"/><div class="account-copy"><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->role?->name ?? 'Unassigned' }}</small></div><form method="POST" action="{{ route('logout') }}">@csrf<button class="icon-button" aria-label="Sign out" title="Sign out"><x-icon name="logout"/></button></form></div>
            </div>
        </aside>
        <div class="main-shell" id="workspace-main">
            <header class="topbar">
                <button class="icon-button menu-button" type="button" data-drawer-open aria-controls="workspace-sidebar" aria-expanded="false" aria-label="Open navigation"><x-icon name="menu"/></button>
                <button class="header-search" type="button" data-search-open aria-haspopup="dialog" aria-controls="workspace-search"><x-icon name="search"/><span>Search users, roles, or activity...</span><kbd>Ctrl K</kbd></button>
                <div class="topbar-actions">
                    <button class="icon-button theme-toggle" type="button" data-theme-toggle aria-label="Switch color theme" title="Switch color theme"><x-icon name="sun" class="theme-sun"/><x-icon name="moon" class="theme-moon"/></button>
                    <details class="dropdown updates-dropdown"><summary class="icon-button" aria-label="Workspace updates" title="Workspace updates"><x-icon name="bell"/></summary><div class="dropdown-panel updates-panel"><strong>Workspace updates</strong>@can('view audit logs')<p>Review recent administrative changes in your activity log.</p><a href="{{ route('audit.index') }}"><x-icon name="activity"/>Open activity log</a>@else<p>Keep your account details and security up to date.</p><a href="{{ route('profile.edit') }}"><x-icon name="user"/>Manage my account</a>@endcan</div></details>
                    <span class="header-divider"></span>
                    <details class="dropdown account-dropdown"><summary class="account-trigger"><x-avatar :name="auth()->user()->name"/><span class="account-copy"><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->role?->name ?? 'Unassigned' }}</small></span><x-icon name="chevron"/></summary><div class="dropdown-panel"><a href="{{ route('profile.edit') }}"><x-icon name="user"/>My account</a><a href="{{ route('settings') }}"><x-icon name="settings"/>Settings</a><form method="POST" action="{{ route('logout') }}">@csrf<button><x-icon name="logout"/>Sign out</button></form></div></details>
                </div>
            </header>
            <main id="main" tabindex="-1">
                <div class="breadcrumb">Workspace <span>/</span> <strong>@yield('title','Dashboard')</strong></div>
@else
    <header class="public-header"><a class="brand" href="{{ route('home') }}"><span class="brand-mark"><x-icon name="shield"/></span>LaraControl</a><nav aria-label="Public"><button class="icon-button theme-toggle" type="button" data-theme-toggle aria-label="Switch color theme"><x-icon name="sun" class="theme-sun"/><x-icon name="moon" class="theme-moon"/></button><a href="{{ route('login') }}">Sign in</a><a class="button" href="{{ route('register') }}">Create account <x-icon name="arrow"/></a></nav></header>
    <main class="public-main" id="main" tabindex="-1">
@endauth
                @if(session('status'))<div class="notice" role="status"><x-icon name="check"/><span>{{ session('status') }}</span></div>@endif
                @if($errors->any())<div class="error-box" role="alert"><x-icon name="info"/><div><strong>Please check the following</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
                @yield('content')
            </main>
@auth
            <footer class="footer"><span>&copy; {{ now()->year }} LaraControl <span class="footer-dot">&middot;</span> Laravel Admin Portal</span><span><span class="status-dot"></span> Access with accountability</span></footer>
        </div>
    </div>
    <dialog id="workspace-search" class="search-dialog" aria-labelledby="workspace-search-title"><div class="dialog-heading"><h2 id="workspace-search-title">Search your workspace</h2><button type="button" class="icon-button" data-dialog-close aria-label="Close search"><x-icon name="close"/></button></div><label class="sr-only" for="workspace-query">Search users, roles, or activity</label><div class="search-dialog-input"><x-icon name="search"/><input id="workspace-query" type="search" maxlength="100" placeholder="Search users, roles, or activity..." autocomplete="off"></div><div class="search-destinations">
        @can('viewAny', App\Models\User::class)<a data-search-query href="{{ route('users.index') }}"><x-icon name="users"/><span>Search users<small>Find by name or email</small></span><x-icon name="arrow"/></a>@endcan
        @can('view roles')<a href="{{ route('roles.index') }}"><x-icon name="shield"/><span>Roles &amp; Permissions<small>Admin, Manager, and Member access</small></span><x-icon name="arrow"/></a>@endcan
        @can('view audit logs')<a data-search-query href="{{ route('audit.index') }}"><x-icon name="activity"/><span>Search activity<small>Find an administrative action</small></span><x-icon name="arrow"/></a>@endcan
        <a href="{{ route('profile.edit') }}"><x-icon name="user"/><span>My Account<small>Profile and security</small></span><x-icon name="arrow"/></a>
    </div><p class="dialog-foot">Choose where to search <kbd>Esc</kbd> to close</p></dialog>
@endauth
<dialog class="confirm-dialog" id="confirm-dialog" aria-labelledby="confirm-title" aria-describedby="confirm-message"><form method="dialog"><div class="confirm-icon"><x-icon name="shield"/></div><h2 id="confirm-title">Confirm your changes</h2><p id="confirm-message"></p><div class="confirm-actions"><button class="button secondary" value="cancel">Cancel</button><button class="button" value="confirm">Confirm changes</button></div></form></dialog>
</body>
</html>
