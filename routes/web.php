<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EnsureActive;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.form', ['mode' => 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::view('/register', 'auth.form', ['mode' => 'register'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::view('/forgot-password', 'auth.form', ['mode' => 'forgot'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'forgot'])->middleware('throttle:3,1')->name('password.email');
    Route::get('/reset-password/{token}', fn (string $token) => view('auth.form', ['mode' => 'reset', 'token' => $token]))->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'reset'])->middleware('throttle:5,1')->name('password.update');
});
Route::middleware(['auth', EnsureActive::class])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [PortalController::class, 'dashboard'])->name('dashboard');
    Route::resource('users', UserController::class)->except('destroy');
    Route::get('/roles', [PortalController::class, 'roles'])->middleware('can:view roles')->name('roles.index');
    Route::put('/roles/{role}', [PortalController::class, 'updateRole'])->middleware('can:manage roles')->name('roles.update');
    Route::get('/audit', [PortalController::class, 'audit'])->middleware('can:view audit logs')->name('audit.index');
    Route::get('/profile', [PortalController::class, 'profile'])->name('profile.edit');
    Route::view('/settings', 'portal.settings')->name('settings');
    Route::put('/profile', [PortalController::class, 'updateProfile'])->name('profile.update');
});
