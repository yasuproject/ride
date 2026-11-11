<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RegisteredUserController;
use App\Http\Controllers\Admin\RideController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('login');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/rides', [RideController::class, 'index'])->name('rides.index');

    // App-registered users (separate table: app_users)
    Route::resource('registered-users', RegisteredUserController::class)
        ->parameters(['registered-users' => 'registered_user'])
        ->names('registered-users');

    // Dashboard/admin panel accounts (separate table: users)
    Route::get('/user-management', [UserController::class, 'index'])->name('admin-users.index');
    Route::resource('user-management', UserController::class)
        ->parameters(['user-management' => 'admin_user'])
        ->names('admin-users')
        ->except(['index']);

    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
