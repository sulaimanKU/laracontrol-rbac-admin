<?php

use App\Actions\RecordAudit;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('portal:admin {email}', function () {
    $email = $this->argument('email');
    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->error('Provide a valid email.');

        return 1;
    }
    if (User::where('email', $email)->exists()) {
        $this->error('Account exists; use an administrator to change its role.');

        return 1;
    }
    $name = $this->ask('Administrator name');
    $password = $this->secret('Password (at least 12 characters)');
    if (! $name || strlen($password ?? '') < 12) {
        $this->error('Name and a password of at least 12 characters required.');

        return 1;
    }
    DB::transaction(function () use ($email, $name, $password) {
        $u = new User(compact('email', 'name', 'password'));
        $u->role_id = Role::where('name', 'Admin')->firstOrFail()->id;
        $u->is_active = true;
        $u->save();
        RecordAudit::write('admin.bootstrapped', $u->id);
    });
    $this->info('Administrator created.');
})->purpose('Create an administrator with an interactive password prompt');
