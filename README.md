# LaraControl

A Laravel role-based administration portal demonstrating authentication, user management, RBAC, editable permissions, audit logging, responsive UI, and secure server-side authorization.

LaraControl pairs a focused Laravel backend with a modern SaaS-style workspace: a dark teal sidebar, clear access controls, real application analytics, and a consistent light/dark design system. This is a portfolio project; use fictional data for public demonstrations.

## Portfolio Highlights

- Laravel session authentication, registration, and password resets.
- Server-side RBAC through policies, gates, middleware, and validated requests.
- Role/permission management with protected administrator capabilities.
- User administration with search, role/status filters, and pagination.
- Transactional audit logging with action, actor, and date filters.
- Responsive SaaS dashboard with real database totals and analytics.
- Light/dark themes with a system option and local preference persistence.
- Automated feature tests for access boundaries, authentication, and workspace data.

## Features

### Workspace

- Dark teal sidebar, active navigation, account menu, and a mobile navigation drawer.
- Four live KPIs: Total Users, Active Users, Administrators, Managers.
- Eight-month registration chart and current role-distribution donut, rendered as lightweight SVG.
- Exact registration counts available in an accessible data table.
- Recent users and administrative activity, restricted by existing permissions.
- Workspace search launcher (Ctrl/Cmd+K): search users or audit actions, or navigate to roles and account settings.
- Settings for browser-local appearance, plus links to existing account and password controls.
- Keyboard focus states, password visibility controls, native confirmation dialogs, empty states, and reduced-motion support.

The header's bell opens a workspace-updates shortcut. It does not claim to provide a personal notification feed, unread counts, or a notification delivery service.

### Administration

- Create, view, and edit accounts; assign roles and activate/deactivate accounts.
- Search by name/email and combine role and status filters.
- Admin, Manager, and Member role cards with the actual permission matrix.
- Existing per-role update forms; protected or read-only cells cannot be edited.
- Audit actor/action/target/date display and inspectable, secret-free metadata.
- Profile updates and optional password changes requiring the current password.

There is no user deletion, MFA, email verification, invitation, or global session-revocation feature.

## Tech Stack

- Laravel 12.70.0 / PHP 8.2+ (development CLI: 8.2.12)
- Blade components and Eloquent
- Tailwind CSS 4.3.3, custom CSS design tokens
- Vite 7.3.7 and vanilla JavaScript
- PHPUnit 11 and SQLite in-memory test fixtures

No frontend framework, chart library, icon package, or other dependency was added for the redesign. Icons and charts are inline SVG; fonts use the system stack and do not require a third-party request.

## Architecture

- **Controllers:** existing authentication and business flows, with read-only filtering and dashboard presentation data.
- **Form Requests:** validation and policy authorization for account administration.
- **Policies and gates:** user-management boundaries and persisted role capabilities.
- **Middleware:** authentication plus active-account checks on protected requests.
- **DashboardMetrics:** portable SQL aggregates for monthly registration counts and Eloquent role counts. No analytics tables or writes.
- **RecordAudit:** whitelisted event payloads, saved transactionally with administrative changes.
- **Blade components:** icons, avatars, badges, navigation, page headers, stats, inputs, action menus, empty states, and activity entries.
- **Frontend:** native details/dialog controls, focus-managed mobile drawer, and local theme preference.
- **Tests:** isolated SQLite fixtures without invoking migration files.

The redesign preserves existing write endpoints, policies, validation, password hashing, and audit behavior. The only added route is authenticated GET /settings.

## RBAC

The matrix below describes the default role setup. Manager and Member capabilities can be edited by an authorized administrator.

| Capability | Admin | Manager | Member |
| --- | --- | --- | --- |
| Administrative dashboard | Yes | Yes | Personal workspace only |
| View users | Yes | Yes | No |
| Create users | Any role | Member only | No |
| Edit user details/status/password | Yes | Members only | No |
| Assign account roles | Yes | No | No |
| View role permissions | Yes | Yes | No |
| Change role permissions | Manager/Member roles | No | No |
| View audit history | Yes | No | No |
| Manage own profile | Yes | Yes | Yes |
| Local appearance settings | Yes | Yes | Yes |

The six persisted permissions are: access dashboard, view users, manage users, view roles, manage roles, and view audit logs. There is no second permission system. Capability grants do not bypass policy boundaries: managers cannot assign roles or edit administrators/other managers, and role-permission writes additionally require the Admin role. Administrator permission lists are protected.

## Installation

Requirements: PHP 8.2+, Composer, Node compatible with Vite 7, npm, and a Laravel-supported database. Tests require PDO SQLite.

For a fresh clone:

1. Run composer install and npm ci.
2. Copy .env.example to .env only if an environment file does not already exist.
3. Configure your database, application URL, and mail transport locally.
4. Generate a key for a new installation with php artisan key:generate. Preserve an existing application key.
5. Back up any existing database, then manually run:

~~~sh
php artisan migrate
php artisan portal:admin admin@example.test
~~~

The administrator command asks for a name and hidden password of at least 12 characters. It creates a new administrator, refuses existing email addresses, and records the operation. No shared administrator credentials are seeded.

Existing users become Members when applying the original RBAC migration. The default DatabaseSeeder intentionally creates no accounts.

Avoid composer run setup when manual migration control is required: the original scaffold setup script runs migrations.

## Local Setup

For an already functional LaraControl installation, **this UI upgrade requires no migrations or additional dependencies**. No database schema was changed.

~~~sh
npm run build
php artisan serve
~~~

Open the address printed by Laravel. For live frontend development, run npm run dev in another terminal.

Important routes:

| Method | Path | Purpose |
| --- | --- | --- |
| GET | / | Portfolio landing |
| GET, POST | /login, /register | Authentication |
| POST | /logout | End session |
| GET, POST | /forgot-password | Password reset request |
| GET | /reset-password/{token} | Reset form |
| POST | /reset-password | Apply reset |
| GET | /dashboard | Administrative/personal workspace |
| GET, POST | /users | Directory / create |
| GET | /users/create | Create form |
| GET | /users/{user}, /users/{user}/edit | Details / edit |
| PUT, PATCH | /users/{user} | Update account |
| GET | /roles | Role cards and permission matrix |
| PUT | /roles/{role} | Existing role-permission update |
| GET | /audit | Audit history |
| GET, PUT | /profile | Own account |
| GET | /settings | Local appearance and account links |
| GET | /up | Laravel health |

Run php artisan route:list for the full route list.

## Demo Data

Optional, local-only fictional data can make portfolio screenshots more representative:

~~~sh
php artisan db:seed --class=PortfolioDemoSeeder
~~~

This command has **not been run against the application database**. It refuses non-local environments.

- Adds 24 clearly marked fictional "(Demo)" accounts at example.test addresses.
- Uses backdated demo registration dates across eight months for chart presentation.
- Includes administrators, managers, members, and a few inactive member accounts.
- Records each account creation as an actual seeding-time audit event; it does not invent historical administrative actions.
- Skips existing demo addresses and preserves all existing accounts, including your administrator.
- Is additive and idempotent; it does not update/delete existing records.
- Generates random passwords rather than publishing shared credentials. Use your existing administrator to sign in.

Only run it in a disposable local portfolio/demo environment where those additional accounts are wanted. Re-running does not reset any demo account changes.

## Testing

~~~sh
php artisan test
npm run build
~~~

The original authorization suite remains in place. Additional coverage checks chart month boundaries and real counts, zero-data handling, role/status filters, actor/action/date filters, restricted dashboard records, Settings protection, permission-matrix form associations, and demo-seeder guards/idempotence.

PortalTestCase forcibly selects an isolated SQLite :memory: database, array cache/sessions/mail, and bypasses Vite. Tests construct a fixture schema without running migrations. The demo-seeder test runs only against that fixture, never the application database.

The manually mirrored fixture does not verify migration execution or production database locking semantics.

## Security Notes

- Authorization is enforced on the server. Blade visibility complements existing policies and gates.
- All mutating web forms retain Laravel CSRF fields and the existing validated update routes.
- Registration always creates a Member; role/status are not publicly mass assignable.
- Passwords are hashed and excluded from audit metadata, as are tokens.
- Deactivated users are blocked at login and on their next protected request.
- Self-demotion/self-deactivation and last-active-administrator protections remain in place.
- Audit metadata includes names/emails and role/status changes; restrict access accordingly.
- Audit records have no application update/delete interface, but are not tamper-proof against database administrators.
- Theme settings are browser-local and are not presented as server-persisted organization settings.
- Password reset delivery depends on your existing mail transport. With a log mailer, reset links are written to private local logs.
- Global session revocation, production backups, monitoring, deployment, and retention/export remain outside this implementation.

No .env changes, migrations, destructive database commands, or Git commits were performed for the UI redesign.

## Screenshots

Add real screenshots after local setup. Recommended captures at normal browser zoom:

1. Dashboard at 1440px: branding, KPIs, growth/distribution charts, recent users/activity.
2. Roles & Permissions: role cards, protected Admin column, editable permission matrix.
3. Users or Activity Log with meaningful filters.
4. Dark theme and the mobile drawer.

Screenshots and browser-based visual verification are not included in this environment.

## UI Redesign File Inventory

Created:

- app/Queries/DashboardMetrics.php
- database/seeders/PortfolioDemoSeeder.php
- tests/Feature/WorkspacePresentationTest.php
- resources/views/portal/settings.blade.php
- resources/views/components/{icon,avatar,badge,page-header,empty-state,nav-link,stat-card,user-actions,activity-item,form-input}.blade.php

Modified:

- app/Http/Controllers/{PortalController,UserController}.php
- routes/web.php
- resources/css/app.css
- resources/js/app.js
- resources/views/layouts/app.blade.php
- resources/views/auth/form.blade.php
- resources/views/portal/{dashboard,roles,audit,profile,member}.blade.php
- resources/views/users/{index,form,show}.blade.php
- README.md

Vite also regenerates ignored public/build assets.

## Verification Results

- Full automated suite: **29 tests passed, 177 assertions**.
- Frontend production build: passed.
- Blade template compilation: passed.
- New/modified PHP syntax and frontend JavaScript syntax: passed.
- Application route list: 23 routes resolved (excluding vendor routes).
- Production manifest entry assets: present.
- No application migrations or application demo-seeder execution performed.
- Browser screenshots, visual inspection at target viewport sizes, and browser interaction testing were unavailable. Responsive behavior was reviewed from layout rules and rendered server responses; it was not measured in a browser.
- Outbound email delivery and production database-specific behavior were not reverified.
